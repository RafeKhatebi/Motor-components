<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'دسترسی غیرمجاز']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'روش درخواست نامعتبر']);
    exit();
}

require_once '../config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    
    $date_format = $_POST['date_format'] ?? 'gregorian';
    
    // اعتبارسنجی
    if (!in_array($date_format, ['gregorian', 'jalali'])) {
        echo json_encode(['success' => false, 'message' => 'فرمت تاریخ نامعتبر']);
        exit();
    }
    
    // ایجاد جدول settings در صورت عدم وجود
    $create_table = "CREATE TABLE IF NOT EXISTS settings (
        id INT PRIMARY KEY AUTO_INCREMENT,
        setting_key VARCHAR(255) UNIQUE,
        setting_value TEXT,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )";
    $db->exec($create_table);
    
    // بروزرسانی تنظیمات
    $query = "INSERT INTO settings (setting_key, setting_value) VALUES (?, ?) 
             ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)";
    $stmt = $db->prepare($query);
    $stmt->execute(['date_format', $date_format]);
    
    echo json_encode(['success' => true, 'message' => 'تنظیمات با موفقیت بروزرسانی شد']);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'خطا در بروزرسانی تنظیمات']);
}
?>