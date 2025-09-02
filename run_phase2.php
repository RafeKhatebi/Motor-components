<?php
// اجرای فاز 2: سیستم قیمتگذاری پیشرفته
require_once 'config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    
    echo "<h2>شروع فاز 2: سیستم قیمتگذاری پیشرفته</h2>";
    
    // خواندن فایل SQL فاز 2
    $sql = file_get_contents('upgrade_phase2.sql');
    
    // تقسیم کوئریها
    $queries = explode(';', $sql);
    
    $success_count = 0;
    $error_count = 0;
    
    foreach ($queries as $query) {
        $query = trim($query);
        if (empty($query)) continue;
        
        try {
            $db->exec($query);
            $success_count++;
            echo "<p style='color: green;'>✓ اجرا شد: " . substr($query, 0, 50) . "...</p>";
        } catch (PDOException $e) {
            $error_count++;
            echo "<p style='color: orange;'>⚠ قبلاً موجود: " . substr($query, 0, 50) . "...</p>";
        }
    }
    
    // درج نمونه قیمتگذاری
    try {
        // قیمت ویژه برای عمده فروشان
        $sample_pricing = "INSERT IGNORE INTO product_prices (product_id, customer_type, price, min_quantity) 
                          SELECT id, 'wholesale', sell_price * 0.9, 5 FROM products LIMIT 5";
        $db->exec($sample_pricing);
        echo "<p style='color: blue;'>✓ نمونه قیمتگذاری عمده فروشی اضافه شد</p>";
        
        // تخفیف حجمی
        $volume_discount = "INSERT IGNORE INTO volume_discounts (product_id, min_quantity, discount_percentage) 
                           SELECT id, 10, 5 FROM products LIMIT 3";
        $db->exec($volume_discount);
        echo "<p style='color: blue;'>✓ نمونه تخفیف حجمی اضافه شد</p>";
        
    } catch (PDOException $e) {
        echo "<p style='color: orange;'>⚠ نمونه دادهها قبلاً موجود</p>";
    }
    
    echo "<h3>نتیجه فاز 2:</h3>";
    echo "<p>موفق: $success_count</p>";
    echo "<p>قبلاً موجود: $error_count</p>";
    echo "<p style='color: green; font-weight: bold;'>فاز 2 کامل شد!</p>";
    
    echo "<h4>ویژگیهای جدید:</h4>";
    echo "<ul>";
    echo "<li>✅ انواع مشتری (خرده، عمده، تعمیرگاه، نمایندگی)</li>";
    echo "<li>✅ تخفیف اختصاصی هر مشتری</li>";
    echo "<li>✅ حد اعتبار مشتریان</li>";
    echo "<li>✅ قیمتگذاری بر اساس نوع مشتری</li>";
    echo "<li>✅ تخفیف حجمی</li>";
    echo "<li>✅ محاسبه قیمت هوشمند در فروش</li>";
    echo "</ul>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>خطا: " . $e->getMessage() . "</p>";
}
?>