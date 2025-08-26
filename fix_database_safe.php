<?php
/**
 * اسکریپت رفع امن مشکلات دیتابیس
 */
require_once 'config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    
    echo "✅ اتصال به دیتابیس موفق\n";
    
    // بررسی و رفع AUTO_INCREMENT
    $db->exec("ALTER TABLE products AUTO_INCREMENT = 5");
    echo "✅ AUTO_INCREMENT محصولات اصلاح شد\n";
    
    // بررسی وجود کاربر admin
    $stmt = $db->prepare("SELECT COUNT(*) FROM users WHERE username = 'admin'");
    $stmt->execute();
    $adminExists = $stmt->fetchColumn();
    
    if (!$adminExists) {
        $hashedPassword = password_hash('admin123', PASSWORD_DEFAULT);
        $stmt = $db->prepare("INSERT INTO users (username, password, full_name, role) VALUES (?, ?, ?, ?)");
        $stmt->execute(['admin', $hashedPassword, 'مدیر سیستم', 'admin']);
        echo "✅ کاربر admin ایجاد شد (رمز: admin123)\n";
    } else {
        echo "ℹ️ کاربر admin قبلاً وجود دارد\n";
    }
    
    // بررسی تنظیمات پایه
    $stmt = $db->prepare("SELECT COUNT(*) FROM settings WHERE setting_key = 'shop_name'");
    $stmt->execute();
    if (!$stmt->fetchColumn()) {
        $db->exec("INSERT INTO settings (setting_key, setting_value) VALUES 
                   ('shop_name', 'فروشگاه قطعات موتورسیکلت'),
                   ('currency', 'afghani'),
                   ('language', 'fa'),
                   ('date_format', 'jalali')");
        echo "✅ تنظیمات پایه اضافه شد\n";
    }
    
    echo "🎉 سیستم آماده استفاده است!\n";
    echo "<a href='login.php'>ورود به سیستم</a>\n";
    
} catch (Exception $e) {
    echo "❌ خطا: " . $e->getMessage() . "\n";
}
?>