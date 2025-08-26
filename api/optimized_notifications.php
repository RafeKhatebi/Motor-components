<?php
require_once '../init_security.php';
require_once '../includes/CacheManager.php';

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'غیر مجاز']);
    exit();
}

header('Content-Type: application/json');

try {
    // بررسی Cache
    $cache_key = 'notifications_' . $_SESSION['user_id'];
    $cached_notifications = CacheManager::get($cache_key);
    
    if ($cached_notifications !== null) {
        echo json_encode(['success' => true, 'notifications' => $cached_notifications, 'cached' => true]);
        exit();
    }
    
    require_once '../config/database.php';
    $database = new Database();
    $db = $database->getConnection();
    $notifications = [];

    // کوئری بهینه شده برای اعلانات
    $notification_query = "
        SELECT 
            'low_stock' as type,
            COUNT(*) as count,
            GROUP_CONCAT(name LIMIT 3) as sample_names
        FROM products 
        WHERE stock_quantity <= min_stock AND stock_quantity > 0
        UNION ALL
        SELECT 
            'out_of_stock' as type,
            COUNT(*) as count,
            NULL as sample_names
        FROM products 
        WHERE stock_quantity = 0
        UNION ALL
        SELECT 
            'high_sales' as type,
            CASE WHEN today.sales > COALESCE(avg_sales.avg * 1.5, 0) THEN 1 ELSE 0 END as count,
            today.sales as sample_names
        FROM (
            SELECT COALESCE(SUM(final_amount), 0) as sales 
            FROM sales 
            WHERE DATE(created_at) = CURDATE() AND (status IS NULL OR status != 'returned')
        ) today
        CROSS JOIN (
            SELECT AVG(daily_sales) as avg 
            FROM (
                SELECT COALESCE(SUM(final_amount), 0) as daily_sales 
                FROM sales 
                WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY) 
                AND (status IS NULL OR status != 'returned') 
                GROUP BY DATE(created_at)
            ) daily_avg
        ) avg_sales
        LIMIT 10
    ";
    
    $stmt = $db->prepare($notification_query);
    $stmt->execute();
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($results as $result) {
        if ($result['count'] > 0) {
            switch ($result['type']) {
                case 'low_stock':
                    $notifications[] = [
                        'type' => 'warning',
                        'title' => 'موجودی کم',
                        'message' => $result['count'] . ' محصول موجودی کم دارد',
                        'link' => 'products.php',
                        'icon' => 'fas fa-exclamation-triangle'
                    ];
                    break;
                    
                case 'out_of_stock':
                    $notifications[] = [
                        'type' => 'danger',
                        'title' => 'محصولات تمام شده',
                        'message' => $result['count'] . ' محصول موجودی ندارد',
                        'link' => 'products.php',
                        'icon' => 'fas fa-times-circle'
                    ];
                    break;
                    
                case 'high_sales':
                    $notifications[] = [
                        'type' => 'success',
                        'title' => 'فروش عالی',
                        'message' => 'فروش امروز ' . number_format($result['sample_names']) . ' افغانی',
                        'link' => 'sales.php',
                        'icon' => 'fas fa-chart-line'
                    ];
                    break;
            }
        }
    }
    
    // ذخیره در Cache برای 5 دقیقه
    CacheManager::set($cache_key, $notifications, 300);
    
    echo json_encode(['success' => true, 'notifications' => $notifications]);

} catch (Exception $e) {
    error_log('Notifications error: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'خطا در دریافت اعلانات']);
}
?>