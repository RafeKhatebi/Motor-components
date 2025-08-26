<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

try {
    require_once 'config/database.php';
    $database = new Database();
    $db = $database->getConnection();
    
    echo "<h2>حل مشکل جدول users</h2>";
    
    // حذف tablespace
    try {
        $db->exec("DROP TABLE IF EXISTS users");
        echo "<p style='color: green;'>✅ جدول قدیمی حذف شد</p>";
    } catch (Exception $e) {
        echo "<p style='color: orange;'>⚠️ " . $e->getMessage() . "</p>";
    }
    
    // ایجاد مجدد جدول
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
    
    // تست
    $stmt = $db->query("SELECT username, full_name FROM users");
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "<h3>کاربران موجود:</h3>";
    foreach ($users as $user) {
        echo "<p>👤 " . $user['username'] . " - " . $user['full_name'] . "</p>";
    }
    
    echo "<hr>";
    echo "<h3>🎉 مشکل حل شد!</h3>";
    echo "<p><a href='simple_login.php' style='background: #28a745; color: white; padding: 10px 20px; text-decoration: none;'>ورود به سیستم</a></p>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>خطا: " . $e->getMessage() . "</p>";
}
?>