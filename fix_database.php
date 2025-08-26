<?php
/**
 * اسکریپت رفع مشکلات دیتابیس
 */
require_once 'config/database.php';

try {
    // اتصال به MySQL بدون انتخاب دیتابیس
    $pdo = new PDO("mysql:host=localhost", "root", "");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // ایجاد دیتابیس در صورت عدم وجود
    $pdo->exec("CREATE DATABASE IF NOT EXISTS motor_shop CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    echo "✅ دیتابیس motor_shop ایجاد شد\n";
    
    // انتخاب دیتابیس
    $pdo->exec("USE motor_shop");
    
    // خواندن و اجرای فایل SQL
    $sql = file_get_contents('database.sql');
    if ($sql) {
        $pdo->exec($sql);
        echo "✅ جداول دیتابیس ایجاد شدند\n";
    }
    
    // رفع مشکل AUTO_INCREMENT در products
    $pdo->exec("ALTER TABLE products AUTO_INCREMENT = 5");
    echo "✅ AUTO_INCREMENT محصولات اصلاح شد\n";
    
    // بررسی وجود کاربر admin
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE username = 'admin'");
    $stmt->execute();
    $adminExists = $stmt->fetchColumn();
    
    if (!$adminExists) {
        // ایجاد کاربر admin پیشفرض
        $hashedPassword = password_hash('admin123', PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("INSERT INTO users (username, password, full_name, role) VALUES (?, ?, ?, ?)");
        $stmt->execute(['admin', $hashedPassword, 'مدیر سیستم', 'admin']);
        echo "✅ کاربر admin ایجاد شد (رمز: admin123)\n";
    }
    
    echo "🎉 تمام مشکلات دیتابیس برطرف شد!\n";
    
} catch (Exception $e) {
    echo "❌ خطا: " . $e->getMessage() . "\n";
}
?>