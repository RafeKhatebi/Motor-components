<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

echo "<h2>تست اجزای سیستم</h2>";

// تست 1: فایلهای مورد نیاز
$required_files = [
    'init_security.php',
    'config/database.php',
    'includes/functions.php',
    'includes/SettingsHelper.php',
    'includes/setup_helper.php'
];

foreach ($required_files as $file) {
    if (file_exists($file)) {
        echo "<p style='color: green;'>✅ $file موجود است</p>";
    } else {
        echo "<p style='color: red;'>❌ $file موجود نیست</p>";
    }
}

// تست 2: اتصال دیتابیس
try {
    require_once 'config/database.php';
    $database = new Database();
    $db = $database->getConnection();
    echo "<p style='color: green;'>✅ اتصال دیتابیس موفق</p>";
    
    // تست جدول users
    $stmt = $db->query("SELECT COUNT(*) FROM users");
    $count = $stmt->fetchColumn();
    echo "<p>تعداد کاربران: $count</p>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ خطا در دیتابیس: " . $e->getMessage() . "</p>";
}

// تست 3: session
session_start();
echo "<p style='color: green;'>✅ Session کار میکند</p>";

echo "<hr><p><a href='login.php'>رفتن به صفحه ورود</a></p>";
?>