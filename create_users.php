<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

try {
    require_once 'config/database.php';
    $database = new Database();
    $db = $database->getConnection();
    
    echo "<h2>ایجاد جدول users</h2>";
    
    // ایجاد جدول users
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
    
    // تست کاربر
    $stmt = $db->query("SELECT * FROM users WHERE username = 'admin'");
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "<p>✅ کاربر تست: " . $user['username'] . " - " . $user['full_name'] . "</p>";
    
    echo "<hr>";
    echo "<h3>✅ آماده است!</h3>";
    echo "<p><strong>اطلاعات ورود:</strong></p>";
    echo "<p>نام کاربری: <code>admin</code></p>";
    echo "<p>رمز عبور: <code>password</code></p>";
    echo "<p><a href='simple_login.php' style='background: #007bff; color: white; padding: 10px 20px; text-decoration: none;'>رفتن به صفحه ورود</a></p>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>خطا: " . $e->getMessage() . "</p>";
}
?>