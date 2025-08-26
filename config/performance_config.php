<?php
// تنظیمات بهینهسازی عملکرد
class PerformanceConfig {
    
    // تنظیمات Cache
    const CACHE_ENABLED = true;
    const CACHE_DURATION = 300; // 5 دقیقه
    
    // تنظیمات Pagination
    const DEFAULT_PAGE_SIZE = 50;
    const MAX_PAGE_SIZE = 200;
    
    // تنظیمات دیتابیس
    public static function getDatabaseConfig() {
        return [
            'charset' => 'utf8mb4',
            'options' => [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::MYSQL_ATTR_USE_BUFFERED_QUERY => false,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET sql_mode='STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION'"
            ]
        ];
    }
    
    // کوئریهای بهینه شده
    public static function getOptimizedQueries() {
        return [
            'dashboard_stats' => "
                SELECT 
                    (SELECT COUNT(*) FROM products) as total_products,
                    (SELECT COUNT(*) FROM customers) as total_customers,
                    (SELECT COALESCE(SUM(final_amount), 0) FROM sales WHERE DATE(created_at) = CURDATE()) as today_sales,
                    (SELECT COUNT(*) FROM products WHERE stock_quantity <= min_stock) as low_stock
            ",
            'notifications_optimized' => "
                SELECT 'low_stock' as type, COUNT(*) as count 
                FROM products WHERE stock_quantity <= min_stock AND stock_quantity > 0
                UNION ALL
                SELECT 'out_of_stock' as type, COUNT(*) as count 
                FROM products WHERE stock_quantity = 0
                LIMIT 10
            "
        ];
    }
}
?>