<?php
// Prevent path traversal attacks by using absolute paths
$base_dir = dirname(__DIR__);
require_once $base_dir . '/init_security.php';
require_once $base_dir . '/config/database.php';
require_once $base_dir . '/includes/functions.php';
require_once $base_dir . '/includes/DateManager.php';
require_once $base_dir . '/includes/SettingsHelper.php';
require_once $base_dir . '/includes/DateHelper.php';

// Authentication check
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

$database = new Database();
$db = $database->getConnection();
SettingsHelper::loadSettings($db);

$input = json_decode(file_get_contents('php://input'), true);

// Input validation and sanitization
$start_date = trim($input['start_date'] ?? '');
$end_date = trim($input['end_date'] ?? '');
$report_type = trim($input['report_type'] ?? '');
$category_id = filter_var($input['category_id'] ?? '', FILTER_VALIDATE_INT);

// تبدیل تاریخهای شمسی به میلادی در صورت نیاز
$dateFormat = SettingsHelper::getSetting('date_format', 'gregorian');
if ($dateFormat === 'jalali') {
    // اگر تاریخ شمسی است، تبدیل کن
    if ($start_date && preg_match('/^\d{4}\/\d{2}\/\d{2}$/', $start_date)) {
        $start_date = DateHelper::convertJalaliToMysqlDate($start_date);
    }
    if ($end_date && preg_match('/^\d{4}\/\d{2}\/\d{2}$/', $end_date)) {
        $end_date = DateHelper::convertJalaliToMysqlDate($end_date);
    }
}

// Validate date format
if ($start_date && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $start_date)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid start date format']);
    exit();
}

if ($end_date && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $end_date)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid end date format']);
    exit();
}

// Validate report type
$allowed_types = ['sales', 'bestsellers', 'inventory', 'profit'];
if (!in_array($report_type, $allowed_types, true)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid report type']);
    exit();
}

if (!$start_date || !$end_date || !$report_type) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Missing required parameters']);
    exit();
}

try {
    $data = [];
    $summary = [];
    $chart_data = ['labels' => [], 'data' => []];
    
    switch ($report_type) {
        case 'sales':
            // گزارش فروش کلی
            $query = "SELECT DATE(s.created_at) as sale_date, s.id, 
                             COALESCE(c.name, 'مشتری نقدی') as customer_name,
                             s.total_amount, s.discount, s.final_amount
                      FROM sales s
                      LEFT JOIN customers c ON s.customer_id = c.id
                      WHERE DATE(s.created_at) BETWEEN :start_date AND :end_date
                      AND (s.status IS NULL OR s.status != 'returned')
                      ORDER BY s.created_at DESC";
            
            $stmt = $db->prepare($query);
            $stmt->bindParam(':start_date', $start_date);
            $stmt->bindParam(':end_date', $end_date);
            $stmt->execute();
            $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // خلاصه با محاسبه سود
            $summary_query = "SELECT COUNT(DISTINCT s.id) as total_invoices, 
                                     SUM(s.final_amount) as total_sales,
                                     AVG(s.final_amount) as avg_invoice,
                                     COALESCE(SUM((si.unit_price - COALESCE(p.buy_price, 0)) * si.quantity), 0) as total_profit
                              FROM sales s
                              LEFT JOIN sale_items si ON s.id = si.sale_id
                              LEFT JOIN products p ON si.product_id = p.id
                              WHERE DATE(s.created_at) BETWEEN :start_date AND :end_date
                              AND (s.status IS NULL OR s.status != 'returned')
                              AND p.buy_price IS NOT NULL AND p.buy_price > 0";
            $summary_stmt = $db->prepare($summary_query);
            $summary_stmt->bindParam(':start_date', $start_date);
            $summary_stmt->bindParam(':end_date', $end_date);
            $summary_stmt->execute();
            $summary = $summary_stmt->fetch(PDO::FETCH_ASSOC);
            
            // Debug: بررسی وجود داده
            $debug_query = "SELECT COUNT(*) as total_sales_count,
                                   COUNT(si.id) as total_items_count,
                                   COUNT(CASE WHEN p.buy_price > 0 THEN 1 END) as items_with_buy_price
                            FROM sales s
                            LEFT JOIN sale_items si ON s.id = si.sale_id
                            LEFT JOIN products p ON si.product_id = p.id
                            WHERE DATE(s.created_at) BETWEEN :start_date AND :end_date
                            AND (s.status IS NULL OR s.status != 'returned')";
            $debug_stmt = $db->prepare($debug_query);
            $debug_stmt->bindParam(':start_date', $start_date);
            $debug_stmt->bindParam(':end_date', $end_date);
            $debug_stmt->execute();
            $debug_info = $debug_stmt->fetch(PDO::FETCH_ASSOC);
            $summary['debug_info'] = $debug_info;
            
            // داده نمودار
            $chart_query = "SELECT DATE(created_at) as date, SUM(final_amount) as total
                           FROM sales 
                           WHERE DATE(created_at) BETWEEN :start_date AND :end_date
                           AND (status IS NULL OR status != 'returned')
                           GROUP BY DATE(created_at)
                           ORDER BY date";
            $chart_stmt = $db->prepare($chart_query);
            $chart_stmt->bindParam(':start_date', $start_date);
            $chart_stmt->bindParam(':end_date', $end_date);
            $chart_stmt->execute();
            $chart_results = $chart_stmt->fetchAll(PDO::FETCH_ASSOC);
            
            foreach ($chart_results as $row) {
                $chart_data['labels'][] = $row['date'];
                $chart_data['data'][] = (float)$row['total'];
            }
            break;
            
        case 'bestsellers':
            // محصولات پرفروش
            $category_filter = $category_id ? "AND p.category_id = :category_id" : "";
            
            $query = "SELECT p.name, SUM(si.quantity) as total_sold, 
                             SUM(si.total_price) as total_revenue,
                             COALESCE(SUM((si.unit_price - COALESCE(p.buy_price, 0)) * si.quantity), 0) as profit
                      FROM sale_items si
                      JOIN products p ON si.product_id = p.id
                      JOIN sales s ON si.sale_id = s.id
                      WHERE DATE(s.created_at) BETWEEN :start_date AND :end_date 
                      AND (s.status IS NULL OR s.status != 'returned') $category_filter
                      GROUP BY si.product_id
                      ORDER BY total_sold DESC
                      LIMIT 20";
            
            $stmt = $db->prepare($query);
            $stmt->bindParam(':start_date', $start_date);
            $stmt->bindParam(':end_date', $end_date);
            if ($category_id) $stmt->bindParam(':category_id', $category_id);
            $stmt->execute();
            $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // نمودار
            foreach (array_slice($data, 0, 10) as $row) {
                $chart_data['labels'][] = $row['name'];
                $chart_data['data'][] = (int)$row['total_sold'];
            }
            break;
            
        case 'inventory':
            // گزارش موجودی
            $category_filter = $category_id ? "WHERE p.category_id = :category_id" : "";
            
            $query = "SELECT p.name, c.name as category_name, p.stock_quantity, 
                             p.min_stock,
                             CASE 
                                 WHEN p.stock_quantity <= 0 THEN 'تمام شده'
                                 WHEN p.stock_quantity <= p.min_stock THEN 'کم'
                                 ELSE 'کافی'
                             END as status
                      FROM products p
                      LEFT JOIN categories c ON p.category_id = c.id
                      $category_filter
                      ORDER BY p.stock_quantity ASC";
            
            $stmt = $db->prepare($query);
            if ($category_id) $stmt->bindParam(':category_id', $category_id);
            $stmt->execute();
            $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // نمودار وضعیت موجودی
            $status_counts = array_count_values(array_column($data, 'status'));
            $chart_data['labels'] = array_keys($status_counts);
            $chart_data['data'] = array_values($status_counts);
            break;
            
        case 'profit':
            // گزارش سود و زیان
            $query = "SELECT DATE(s.created_at) as date,
                             SUM(si.total_price) as revenue,
                             SUM(COALESCE(p.buy_price, 0) * si.quantity) as cost,
                             COALESCE(SUM((si.unit_price - COALESCE(p.buy_price, 0)) * si.quantity), 0) as profit,
                             CASE 
                                 WHEN SUM(si.total_price) > 0 THEN ROUND(COALESCE(SUM((si.unit_price - COALESCE(p.buy_price, 0)) * si.quantity), 0) / SUM(si.total_price) * 100, 2)
                                 ELSE 0
                             END as profit_margin
                      FROM sale_items si
                      JOIN products p ON si.product_id = p.id
                      JOIN sales s ON si.sale_id = s.id
                      WHERE DATE(s.created_at) BETWEEN :start_date AND :end_date
                      AND (s.status IS NULL OR s.status != 'returned')
                      GROUP BY DATE(s.created_at)
                      ORDER BY date DESC";
            
            $stmt = $db->prepare($query);
            $stmt->bindParam(':start_date', $start_date);
            $stmt->bindParam(':end_date', $end_date);
            $stmt->execute();
            $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // خلاصه سود
            $total_profit = array_sum(array_column($data, 'profit'));
            $summary['total_profit'] = $total_profit;
            
            // نمودار سود روزانه
            foreach ($data as $row) {
                $chart_data['labels'][] = $row['date'];
                $chart_data['data'][] = (float)$row['profit'];
            }
            break;
    }
    
    // محاسبه آمار تفصیلی
    $detailed_stats = [];
    
    if ($report_type === 'sales') {
        // آمار تفصیلی فروش
        $stats_query = "SELECT 
                               MAX(daily_sales.total) as max_daily_sales,
                               MIN(daily_sales.total) as min_daily_sales,
                               AVG(daily_sales.total) as avg_daily_sales,
                               COUNT(daily_sales.date) as active_days
                        FROM (
                            SELECT DATE(created_at) as date, SUM(final_amount) as total
                            FROM sales 
                            WHERE DATE(created_at) BETWEEN :start_date AND :end_date
                            AND (status IS NULL OR status != 'returned')
                            GROUP BY DATE(created_at)
                        ) as daily_sales";
        
        $stats_stmt = $db->prepare($stats_query);
        $stats_stmt->bindParam(':start_date', $start_date);
        $stats_stmt->bindParam(':end_date', $end_date);
        $stats_stmt->execute();
        $stats_result = $stats_stmt->fetch(PDO::FETCH_ASSOC);
        
        // پرفروشترین محصول
        $top_product_query = "SELECT p.name, SUM(si.quantity) as total_qty
                             FROM sale_items si
                             JOIN products p ON si.product_id = p.id
                             JOIN sales s ON si.sale_id = s.id
                             WHERE DATE(s.created_at) BETWEEN :start_date AND :end_date
                             AND (s.status IS NULL OR s.status != 'returned')
                             GROUP BY si.product_id
                             ORDER BY total_qty DESC
                             LIMIT 1";
        
        $top_product_stmt = $db->prepare($top_product_query);
        $top_product_stmt->bindParam(':start_date', $start_date);
        $top_product_stmt->bindParam(':end_date', $end_date);
        $top_product_stmt->execute();
        $top_product = $top_product_stmt->fetch(PDO::FETCH_ASSOC);
        
        // بهترین مشتری
        $top_customer_query = "SELECT COALESCE(c.name, 'مشتری نقدی') as customer_name, SUM(s.final_amount) as total_amount
                              FROM sales s
                              LEFT JOIN customers c ON s.customer_id = c.id
                              WHERE DATE(s.created_at) BETWEEN :start_date AND :end_date
                              AND (s.status IS NULL OR s.status != 'returned')
                              GROUP BY s.customer_id
                              ORDER BY total_amount DESC
                              LIMIT 1";
        
        $top_customer_stmt = $db->prepare($top_customer_query);
        $top_customer_stmt->bindParam(':start_date', $start_date);
        $top_customer_stmt->bindParam(':end_date', $end_date);
        $top_customer_stmt->execute();
        $top_customer = $top_customer_stmt->fetch(PDO::FETCH_ASSOC);
        
        $detailed_stats = [
            'max_daily_sales' => $stats_result['max_daily_sales'] ?? 0,
            'min_daily_sales' => $stats_result['min_daily_sales'] ?? 0,
            'avg_daily_sales' => $stats_result['avg_daily_sales'] ?? 0,
            'active_days' => $stats_result['active_days'] ?? 0,
            'top_product' => $top_product['name'] ?? '-',
            'top_product_qty' => $top_product['total_qty'] ?? 0,
            'top_customer' => $top_customer['customer_name'] ?? '-',
            'top_customer_amount' => $top_customer['total_amount'] ?? 0
        ];
    }
    
    echo json_encode([
        'success' => true,
        'data' => $data,
        'summary' => $summary,
        'chart_data' => $chart_data,
        'detailed_stats' => $detailed_stats
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'خطا در تولید گزارش: ' . $e->getMessage()
    ]);
}
?>