<?php
require_once '../init_security.php';
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'غیر مجاز']);
    exit();
}

require_once '../config/database.php';
header('Content-Type: application/json');

try {
    $database = new Database();
    $db = $database->getConnection();
    $notifications = [];

    // هشدار موجودی کم
    $low_stock_query = "SELECT id, name, stock_quantity, min_stock FROM products WHERE stock_quantity <= min_stock AND stock_quantity > 0";
    $low_stock_stmt = $db->prepare($low_stock_query);
    $low_stock_stmt->execute();
    $low_stock_products = $low_stock_stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($low_stock_products as $product) {
        $notifications[] = [
            'type' => 'warning',
            'title' => 'موجودی کم',
            'message' => "محصول {$product['name']} تنها {$product['stock_quantity']} عدد موجودی دارد",
            'link' => 'products.php',
            'icon' => 'fas fa-exclamation-triangle'
        ];
    }

    // محصولات تمام شده
    $out_of_stock_query = "SELECT id, name FROM products WHERE stock_quantity = 0";
    $out_of_stock_stmt = $db->prepare($out_of_stock_query);
    $out_of_stock_stmt->execute();
    $out_of_stock_products = $out_of_stock_stmt->fetchAll(PDO::FETCH_ASSOC);

    if (count($out_of_stock_products) > 0) {
        $notifications[] = [
            'type' => 'danger',
            'title' => 'محصولات تمام شده',
            'message' => count($out_of_stock_products) . ' محصول موجودی ندارد',
            'link' => 'products.php',
            'icon' => 'fas fa-times-circle'
        ];
    }

    // فروش بالای روز
    $today_sales_query = "SELECT COALESCE(SUM(final_amount), 0) as today_sales FROM sales WHERE DATE(created_at) = CURDATE() AND (status IS NULL OR status != 'returned')";
    $today_sales_stmt = $db->prepare($today_sales_query);
    $today_sales_stmt->execute();
    $today_sales = $today_sales_stmt->fetch(PDO::FETCH_ASSOC);

    $avg_sales_query = "SELECT AVG(daily_sales) as avg_sales FROM (SELECT COALESCE(SUM(final_amount), 0) as daily_sales FROM sales WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY) AND (status IS NULL OR status != 'returned') GROUP BY DATE(created_at)) as daily";
    $avg_sales_stmt = $db->prepare($avg_sales_query);
    $avg_sales_stmt->execute();
    $avg_sales = $avg_sales_stmt->fetch(PDO::FETCH_ASSOC);

    if ($today_sales['today_sales'] > ($avg_sales['avg_sales'] * 1.5)) {
        $notifications[] = [
            'type' => 'success',
            'title' => 'فروش عالی',
            'message' => 'فروش امروز ' . number_format($today_sales['today_sales']) . ' افغانی است',
            'link' => 'sales.php',
            'icon' => 'fas fa-chart-line'
        ];
    }

    // فاکتورهای امروز
    $today_invoices_query = "SELECT COUNT(*) as count FROM sales WHERE DATE(created_at) = CURDATE() AND (status IS NULL OR status != 'returned')";
    $today_invoices_stmt = $db->prepare($today_invoices_query);
    $today_invoices_stmt->execute();
    $today_invoices = $today_invoices_stmt->fetch(PDO::FETCH_ASSOC);

    if ($today_invoices['count'] > 10) {
        $notifications[] = [
            'type' => 'info',
            'title' => 'فعالیت بالا',
            'message' => 'امروز ' . $today_invoices['count'] . ' فاکتور ثبت شده است',
            'link' => 'sales.php',
            'icon' => 'fas fa-file-invoice'
        ];
    }

    echo json_encode(['success' => true, 'notifications' => $notifications]);

} catch (Exception $e) {
    error_log('Notifications error: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'خطا در دریافت اعلانات']);
}
?>