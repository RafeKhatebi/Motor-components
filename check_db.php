<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

try {
    require_once 'config/database.php';
    $database = new Database();
    $db = $database->getConnection();
    
    echo "<h2>بررسی دیتابیس</h2>";
    
    // نمایش نام دیتابیس فعلی
    $stmt = $db->query("SELECT DATABASE() as db_name");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "<p>دیتابیس فعلی: <strong>" . $result['db_name'] . "</strong></p>";
    
    // نمایش جداول موجود
    $stmt = $db->query("SHOW TABLES");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    echo "<h3>جداول موجود:</h3>";
    if (empty($tables)) {
        echo "<p style='color: red;'>❌ هیچ جدولی موجود نیست!</p>";
        
        // ایجاد جدول users
        echo "<p>در حال ایجاد جدول users...</p>";
        $sql = "CREATE TABLE users (
            id int(11) NOT NULL AUTO_INCREMENT,
            username varchar(50) NOT NULL,
            password varchar(255) NOT NULL,
            full_name varchar(100) NOT NULL,
            role enum('admin','manager','employee') DEFAULT 'employee',
            created_at timestamp NOT NULL DEFAULT current_timestamp(),
            PRIMARY KEY (id),
            UNIQUE KEY username (username)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        
        $db->exec($sql);
        echo "<p style='color: green;'>✅ جدول users ایجاد شد</p>";
        
        // اضافه کردن کاربر admin
        $password = password_hash('password', PASSWORD_DEFAULT);
        $stmt = $db->prepare("INSERT INTO users (username, password, full_name, role) VALUES (?, ?, ?, ?)");
        $stmt->execute(['admin', $password, 'مدیر اصلی سیستم', 'admin']);
        echo "<p style='color: green;'>✅ کاربر admin اضافه شد</p>";
        
        echo "<p><a href='simple_login.php'>رفتن به صفحه ورود</a></p>";
        
    } else {
        foreach ($tables as $table) {
            echo "<p>✅ " . $table . "</p>";
        }
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>خطا: " . $e->getMessage() . "</p>";
}
?>