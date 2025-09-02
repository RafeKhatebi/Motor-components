<?php
require_once '../config/database.php';
require_once '../includes/functions.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

$database = new Database();
$db = $database->getConnection();

$start_date = $_POST['start_date'] ?? '';
$end_date = $_POST['end_date'] ?? '';
$report_type = $_POST['report_type'] ?? 'sales';
$category_id = $_POST['category_id'] ?? '';

if (empty($start_date) || empty($end_date)) {
    echo json_encode(['success' => false, 'message' => 'تاریخ شروع و پایان الزامی است']);
    exit;
}

try {
    $data = [];
    $summary = [];
    $detailed_stats = [];

    switch ($report_type) {
        case 'sales':
            // گزارش فروش کلی
            $query = "SELECT s.id, s.created_at, c.name as customer_name, s.total_amount, s.discount, s.final_amount
                      FROM sales s
                      LEFT JOIN customers c ON s.customer_id = c.id
                      WHERE DATE(s.created_at) BETWEEN ? AND ?
                      AND (s.status IS NULL OR s.status != 'returned')
                      ORDER BY s.created_at DESC";
            
            $stmt = $db->prepare($query);
            $stmt->execute([$start_date, $end_date]);
            $sales = $stmt->fetchAll(PDO::FETCH_ASSOC);

            foreach ($sales as $sale) {
                $data[] = [
                    'تاریخ' => date('Y/m/d', strtotime($sale['created_at'])),
                    'شماره فاکتور' => $sale['id'],
                    'مشتری' => $sale['customer_name'] ?: 'مشتری عادی',
                    'مبلغ کل' => number_format($sale['total_amount']) . ' افغانی',
                    'تخفیف' => number_format($sale['discount']) . ' افغانی',
                    'مبلغ نهایی' => number_format($sale['final_amount']) . ' افغانی'
                ];
            }

            // خلاصه آمار
            $summary = [
                'total_sales' => array_sum(array_column($sales, 'final_amount')),
                'total_invoices' => count($sales),
                'total_profit' => 0,
                'avg_invoice' => count($sales) > 0 ? array_sum(array_column($sales, 'final_amount')) / count($sales) : 0
            ];

            // محاسبه سود
            $profit_query = "SELECT COALESCE(SUM((si.unit_price - p.buy_price) * si.quantity), 0) as total_profit
                            FROM sale_items si
                            JOIN products p ON si.product_id = p.id
                            JOIN sales s ON si.sale_id = s.id
                            WHERE DATE(s.created_at) BETWEEN ? AND ?
                            AND (s.status IS NULL OR s.status != 'returned')";
            $profit_stmt = $db->prepare($profit_query);
            $profit_stmt->execute([$start_date, $end_date]);
            $summary['total_profit'] = $profit_stmt->fetch(PDO::FETCH_ASSOC)['total_profit'];

            // آمار تفصیلی
            $detailed_stats = [
                'max_daily_sales' => 0,
                'min_daily_sales' => 0,
                'avg_daily_sales' => 0,
                'top_product' => '-',
                'top_product_qty' => 0,
                'top_customer' => '-',
                'top_customer_amount' => 0,
                'active_days' => 0
            ];

            break;

        case 'bestsellers':
            // گزارش محصولات پرفروش
            $query = "SELECT p.name, SUM(si.quantity) as total_sold, 
                             SUM(si.total_price) as total_revenue,
                             SUM((si.unit_price - p.buy_price) * si.quantity) as profit
                      FROM sale_items si
                      JOIN products p ON si.product_id = p.id
                      JOIN sales s ON si.sale_id = s.id
                      WHERE DATE(s.created_at) BETWEEN ? AND ?";
            
            if (!empty($category_id)) {
                $query .= " AND p.category_id = ?";
            }
            
            $query .= " AND (s.status IS NULL OR s.status != 'returned')
                       GROUP BY si.product_id
                       ORDER BY total_sold DESC";

            $stmt = $db->prepare($query);
            $params = [$start_date, $end_date];
            if (!empty($category_id)) {
                $params[] = $category_id;
            }
            $stmt->execute($params);
            $products = $stmt->fetchAll(PDO::FETCH_ASSOC);

            foreach ($products as $product) {
                $data[] = [
                    'نام محصول' => $product['name'],
                    'تعداد فروش' => $product['total_sold'],
                    'درآمد کل' => number_format($product['total_revenue']) . ' افغانی',
                    'سود' => number_format($product['profit']) . ' افغانی'
                ];
            }

            $summary = [
                'total_sales' => array_sum(array_column($products, 'total_revenue')),
                'total_invoices' => count($products),
                'total_profit' => array_sum(array_column($products, 'profit')),
                'avg_invoice' => 0
            ];

            break;

        case 'inventory':
            // گزارش موجودی کالاها
            $query = "SELECT p.name, c.name as category_name, p.stock_quantity, p.min_stock,
                             CASE 
                                WHEN p.stock_quantity <= p.min_stock THEN 'کم موجود'
                                WHEN p.stock_quantity <= 5 THEN 'بحرانی'
                                ELSE 'عادی'
                             END as status
                      FROM products p
                      LEFT JOIN categories c ON p.category_id = c.id";
            
            if (!empty($category_id)) {
                $query .= " WHERE p.category_id = ?";
            }
            
            $query .= " ORDER BY p.stock_quantity ASC";

            $stmt = $db->prepare($query);
            $params = [];
            if (!empty($category_id)) {
                $params[] = $category_id;
            }
            $stmt->execute($params);
            $products = $stmt->fetchAll(PDO::FETCH_ASSOC);

            foreach ($products as $product) {
                $data[] = [
                    'نام محصول' => $product['name'],
                    'دسته بندی' => $product['category_name'] ?: '-',
                    'موجودی فعلی' => $product['stock_quantity'],
                    'حداقل موجودی' => $product['min_stock'],
                    'وضعیت' => $product['status']
                ];
            }

            $summary = [
                'total_sales' => 0,
                'total_invoices' => count($products),
                'total_profit' => 0,
                'avg_invoice' => 0
            ];

            break;

        case 'profit':
            // گزارش سود و زیان
            $query = "SELECT DATE(s.created_at) as sale_date,
                             SUM(s.final_amount) as daily_revenue,
                             SUM((si.unit_price - p.buy_price) * si.quantity) as daily_profit
                      FROM sales s
                      JOIN sale_items si ON s.id = si.sale_id
                      JOIN products p ON si.product_id = p.id
                      WHERE DATE(s.created_at) BETWEEN ? AND ?
                      AND (s.status IS NULL OR s.status != 'returned')
                      GROUP BY DATE(s.created_at)
                      ORDER BY sale_date DESC";

            $stmt = $db->prepare($query);
            $stmt->execute([$start_date, $end_date]);
            $daily_profits = $stmt->fetchAll(PDO::FETCH_ASSOC);

            foreach ($daily_profits as $day) {
                $expenses = 0; // می‌توانید هزینه‌های روزانه را اضافه کنید
                $net_profit = $day['daily_profit'] - $expenses;
                $profit_margin = $day['daily_revenue'] > 0 ? ($net_profit / $day['daily_revenue']) * 100 : 0;

                $data[] = [
                    'تاریخ' => date('Y/m/d', strtotime($day['sale_date'])),
                    'درآمد' => number_format($day['daily_revenue']) . ' افغانی',
                    'هزینه' => number_format($expenses) . ' افغانی',
                    'سود خالص' => number_format($net_profit) . ' افغانی',
                    'درصد سود' => number_format($profit_margin, 1) . '%'
                ];
            }

            $summary = [
                'total_sales' => array_sum(array_column($daily_profits, 'daily_revenue')),
                'total_invoices' => count($daily_profits),
                'total_profit' => array_sum(array_column($daily_profits, 'daily_profit')),
                'avg_invoice' => 0
            ];

            break;
    }

    echo json_encode([
        'success' => true,
        'data' => $data,
        'summary' => $summary,
        'detailed_stats' => $detailed_stats
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'خطا در تولید گزارش: ' . $e->getMessage()
    ]);
}
?>