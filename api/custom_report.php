<?php
require_once '../config/database.php';
require_once '../includes/functions.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$start_date = $input['start_date'] ?? '';
$end_date = $input['end_date'] ?? '';
$report_type = $input['report_type'] ?? 'sales';
$category_id = $input['category_id'] ?? '';

if (!$start_date || !$end_date) {
    echo json_encode(['success' => false, 'message' => 'تاریخ شروع و پایان الزامی است']);
    exit;
}

$database = new Database();
$db = $database->getConnection();

try {
    $data = [];
    $summary = [];
    $detailed_stats = [];

    switch ($report_type) {
        case 'sales':
            $query = "SELECT DATE(s.created_at) as date, CONCAT('INV-', LPAD(s.id, 6, '0')) as invoice_number, 
                             COALESCE(c.name, 'مشتری نقدی') as customer_name,
                             s.total_amount, s.discount as discount_amount, s.final_amount
                      FROM sales s
                      LEFT JOIN customers c ON s.customer_id = c.id
                      WHERE DATE(s.created_at) BETWEEN ? AND ?
                      AND (s.status IS NULL OR s.status != 'returned')
                      ORDER BY s.created_at DESC";
            
            $stmt = $db->prepare($query);
            $stmt->execute([$start_date, $end_date]);
            $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // محاسبه سود برای فروش
            $profit_query = "SELECT COALESCE(SUM((si.unit_price - p.buy_price) * si.quantity), 0) as total_profit
                            FROM sale_items si
                            JOIN products p ON si.product_id = p.id
                            JOIN sales s ON si.sale_id = s.id
                            WHERE DATE(s.created_at) BETWEEN ? AND ?
                            AND (s.status IS NULL OR s.status != 'returned')";
            $profit_stmt = $db->prepare($profit_query);
            $profit_stmt->execute([$start_date, $end_date]);
            $total_profit = $profit_stmt->fetch(PDO::FETCH_ASSOC)['total_profit'];
            
            $summary = [
                'total_sales' => array_sum(array_column($data, 'final_amount')),
                'total_invoices' => count($data),
                'total_profit' => $total_profit,
                'avg_invoice' => count($data) > 0 ? array_sum(array_column($data, 'final_amount')) / count($data) : 0
            ];
            break;

        case 'bestsellers':
            $query = "SELECT p.name, SUM(si.quantity) as total_sold,
                             SUM(si.total_price) as total_revenue,
                             SUM((si.unit_price - p.buy_price) * si.quantity) as profit
                      FROM sale_items si
                      JOIN products p ON si.product_id = p.id
                      JOIN sales s ON si.sale_id = s.id
                      WHERE DATE(s.created_at) BETWEEN ? AND ?
                      AND (s.status IS NULL OR s.status != 'returned')";
            
            if ($category_id) {
                $query .= " AND p.category_id = ?";
            }
            
            $query .= " GROUP BY si.product_id ORDER BY total_sold DESC LIMIT 20";
            
            $stmt = $db->prepare($query);
            $params = [$start_date, $end_date];
            if ($category_id) $params[] = $category_id;
            
            $stmt->execute($params);
            $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
            break;

        case 'inventory':
            $query = "SELECT p.name, c.name as category_name, p.stock_quantity,
                             p.min_stock as min_stock_level,
                             CASE 
                                 WHEN p.stock_quantity <= p.min_stock THEN 'کم'
                                 WHEN p.stock_quantity <= 5 THEN 'بحرانی'
                                 ELSE 'عادی'
                             END as status
                      FROM products p
                      LEFT JOIN categories c ON p.category_id = c.id";
            
            if ($category_id) {
                $query .= " WHERE p.category_id = ?";
            }
            
            $query .= " ORDER BY p.stock_quantity ASC";
            
            $stmt = $db->prepare($query);
            if ($category_id) {
                $stmt->execute([$category_id]);
            } else {
                $stmt->execute();
            }
            $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
            break;

        case 'profit':
            $query = "SELECT DATE(s.created_at) as date,
                             SUM(s.final_amount) as revenue,
                             0 as expenses,
                             SUM((si.unit_price - p.buy_price) * si.quantity) as net_profit,
                             ROUND((SUM((si.unit_price - p.buy_price) * si.quantity) / SUM(s.final_amount)) * 100, 2) as profit_percentage
                      FROM sales s
                      JOIN sale_items si ON s.id = si.sale_id
                      JOIN products p ON si.product_id = p.id
                      WHERE DATE(s.created_at) BETWEEN ? AND ?
                      AND (s.status IS NULL OR s.status != 'returned')
                      GROUP BY DATE(s.created_at)
                      ORDER BY date DESC";
            
            $stmt = $db->prepare($query);
            $stmt->execute([$start_date, $end_date]);
            $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
            break;
    }

    echo json_encode([
        'success' => true,
        'data' => $data,
        'summary' => $summary,
        'detailed_stats' => $detailed_stats
    ]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'خطا در پردازش: ' . $e->getMessage()]);
}
?>