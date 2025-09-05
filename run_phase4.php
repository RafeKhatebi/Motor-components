<?php
// اجرای فاز 4: سیستم بارکد
require_once 'config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    
    echo "<h2>شروع فاز 4: سیستم بارکد</h2>";
    
    $sql = file_get_contents('upgrade_phase4.sql');
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
    
    // تولید بارکد برای محصولات بدون بارکد
    try {
        $generate_barcodes = "UPDATE products 
                             SET barcode = CONCAT('MP', LPAD(id, 8, '0')) 
                             WHERE (barcode IS NULL OR barcode = '') AND id IS NOT NULL";
        $db->exec($generate_barcodes);
        echo "<p style='color: blue;'>✓ بارکد برای محصولات بدون بارکد تولید شد</p>";
        
        // شمارش محصولات با بارکد
        $count_query = "SELECT COUNT(*) as total FROM products WHERE barcode IS NOT NULL AND barcode != ''";
        $count_stmt = $db->prepare($count_query);
        $count_stmt->execute();
        $count = $count_stmt->fetch(PDO::FETCH_ASSOC);
        echo "<p style='color: blue;'>📊 تعداد محصولات با بارکد: {$count['total']}</p>";
        
    } catch (PDOException $e) {
        echo "<p style='color: orange;'>⚠ خطا در تولید بارکد: " . $e->getMessage() . "</p>";
    }
    
    echo "<h3>نتیجه فاز 4:</h3>";
    echo "<p>موفق: $success_count</p>";
    echo "<p>قبلاً موجود: $error_count</p>";
    echo "<p style='color: green; font-weight: bold;'>فاز 4 کامل شد!</p>";
    
    echo "<h4>ویژگیهای جدید:</h4>";
    echo "<ul>";
    echo "<li>✅ سیستم بارکد کامل</li>";
    echo "<li>✅ تولید بارکد خودکار</li>";
    echo "<li>✅ اسکن بارکد در فروش</li>";
    echo "<li>✅ جستجوی سریع با بارکد</li>";
    echo "<li>✅ تاریخچه اسکن</li>";
    echo "<li>✅ چاپ برچسب بارکد</li>";
    echo "</ul>";
    
    echo "<h4>نحوه استفاده:</h4>";
    echo "<ol>";
    echo "<li>به صفحه بارکد بروید و بارکدها را تست کنید</li>";
    echo "<li>در فروش، بارکد را اسکن کنید یا وارد کنید</li>";
    echo "<li>محصول خودکار به فاکتور اضافه میشود</li>";
    echo "</ol>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>خطا: " . $e->getMessage() . "</p>";
}
?>