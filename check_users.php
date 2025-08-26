<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

try {
    require_once 'config/database.php';
    $database = new Database();
    $db = $database->getConnection();
    
    echo "<h2>بررسی جدول users</h2>";
    
    // بررسی وجود جدول
    $stmt = $db->query("SHOW TABLES LIKE 'users'");
    if ($stmt->rowCount() > 0) {
        echo "<p style='color: green;'>✅ جدول users موجود است</p>";
        
        // بررسی محتوای جدول
        try {
            $stmt = $db->query("SELECT * FROM users");
            $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            if (empty($users)) {
                echo "<p style='color: orange;'>⚠️ جدول خالی است</p>";
                
                // اضافه کردن کاربر admin
                $password = password_hash('password', PASSWORD_DEFAULT);
                $stmt = $db->prepare("INSERT INTO users (username, password, full_name, role) VALUES (?, ?, ?, ?)");
                $stmt->execute(['admin', $password, 'مدیر اصلی سیستم', 'admin']);
                echo "<p style='color: green;'>✅ کاربر admin اضافه شد</p>";
            } else {
                echo "<h3>کاربران موجود:</h3>";
                foreach ($users as $user) {
                    echo "<p>👤 " . $user['username'] . " - " . $user['full_name'] . " (" . $user['role'] . ")</p>";
                }
            }
            
        } catch (Exception $e) {
            echo "<p style='color: red;'>خطا در خواندن جدول: " . $e->getMessage() . "</p>";
        }
    } else {
        echo "<p style='color: red;'>❌ جدول users موجود نیست</p>";
    }
    
    // حذف جدول اضافی
    try {
        $db->exec("DROP TABLE IF EXISTS system_users");
        echo "<p style='color: blue;'>🗑️ جدول اضافی حذف شد</p>";
    } catch (Exception $e) {
        // نادیده گرفته شود
    }
    
    echo "<hr>";
    echo "<p><a href='simple_login.php' style='background: #007bff; color: white; padding: 10px 20px; text-decoration: none;'>تست ورود</a></p>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>خطا: " . $e->getMessage() . "</p>";
}
?>