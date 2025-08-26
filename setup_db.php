<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once 'config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    
    echo "<h2>راهاندازی دیتابیس</h2>";
    
    // ایجاد جدول users
    $sql = "CREATE TABLE IF NOT EXISTS `users` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `username` varchar(50) NOT NULL,
        `password` varchar(255) NOT NULL,
        `full_name` varchar(100) NOT NULL,
        `role` enum('admin','manager','employee') DEFAULT 'employee',
        `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
        PRIMARY KEY (`id`),
        UNIQUE KEY `username` (`username`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    
    $db->exec($sql);
    echo "<p style='color: green;'>✅ جدول users ایجاد شد</p>";
    
    // اضافه کردن کاربر admin
    $password = password_hash('password', PASSWORD_DEFAULT);
    $stmt = $db->prepare("INSERT IGNORE INTO users (username, password, full_name, role) VALUES (?, ?, ?, ?)");
    $stmt->execute(['admin', $password, 'مدیر اصلی سیستم', 'admin']);
    echo "<p style='color: green;'>✅ کاربر admin اضافه شد</p>";
    
    // ایجاد جدول categories
    $sql = "CREATE TABLE IF NOT EXISTS `categories` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `name` varchar(100) NOT NULL,
        `description` text DEFAULT NULL,
        `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
        PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    
    $db->exec($sql);
    echo "<p style='color: green;'>✅ جدول categories ایجاد شد</p>";
    
    // ایجاد جدول products
    $sql = "CREATE TABLE IF NOT EXISTS `products` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `name` varchar(200) NOT NULL,
        `category_id` int(11) DEFAULT NULL,
        `code` varchar(50) NOT NULL,
        `buy_price` decimal(10,2) NOT NULL,
        `sell_price` decimal(10,2) NOT NULL,
        `stock_quantity` int(11) DEFAULT 0,
        `min_stock` int(11) DEFAULT 5,
        `description` text DEFAULT NULL,
        `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
        PRIMARY KEY (`id`),
        UNIQUE KEY `code` (`code`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    
    $db->exec($sql);
    echo "<p style='color: green;'>✅ جدول products ایجاد شد</p>";
    
    // ایجاد جدول customers
    $sql = "CREATE TABLE IF NOT EXISTS `customers` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `name` varchar(100) NOT NULL,
        `phone` varchar(20) DEFAULT NULL,
        `address` text DEFAULT NULL,
        `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
        PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    
    $db->exec($sql);
    echo "<p style='color: green;'>✅ جدول customers ایجاد شد</p>";
    
    // ایجاد جدول settings
    $sql = "CREATE TABLE IF NOT EXISTS `settings` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `setting_key` varchar(255) DEFAULT NULL,
        `setting_value` text DEFAULT NULL,
        `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
        PRIMARY KEY (`id`),
        UNIQUE KEY `setting_key` (`setting_key`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    
    $db->exec($sql);
    echo "<p style='color: green;'>✅ جدول settings ایجاد شد</p>";
    
    // اضافه کردن تنظیمات پایه
    $settings = [
        ['shop_name', 'فروشگاه قطعات موتورسیکلت'],
        ['shop_phone', '021-12345678'],
        ['shop_address', 'آدرس فروشگاه'],
        ['currency', 'afghani'],
        ['language', 'fa'],
        ['date_format', 'jalali']
    ];
    
    $stmt = $db->prepare("INSERT IGNORE INTO settings (setting_key, setting_value) VALUES (?, ?)");
    foreach ($settings as $setting) {
        $stmt->execute($setting);
    }
    echo "<p style='color: green;'>✅ تنظیمات پایه اضافه شد</p>";
    
    echo "<hr>";
    echo "<h3>✅ دیتابیس با موفقیت راهاندازی شد!</h3>";
    echo "<p><strong>اطلاعات ورود:</strong></p>";
    echo "<p>نام کاربری: <code>admin</code></p>";
    echo "<p>رمز عبور: <code>password</code></p>";
    echo "<p><a href='login.php' style='background: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>رفتن به صفحه ورود</a></p>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ خطا: " . $e->getMessage() . "</p>";
}
?>