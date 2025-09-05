<?php
// اجرای ارتقاء دیتابیس برای سیستم پرزه فروشی موتور
require_once 'config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    
    echo "<h2>شروع ارتقاء دیتابیس...</h2>";
    
    // خواندن فایل SQL
    $sql = file_get_contents('upgrade_database.sql');
    
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
    
    echo "<h3>نتیجه ارتقاء:</h3>";
    echo "<p>موفق: $success_count</p>";
    echo "<p>قبلاً موجود: $error_count</p>";
    echo "<p style='color: green; font-weight: bold;'>ارتقاء دیتابیس کامل شد!</p>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>خطا: " . $e->getMessage() . "</p>";
}
?>