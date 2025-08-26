<?php
require_once '../init_security.php';
require_once '../config/database.php';
require_once '../includes/SettingsHelper.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'دسترسی غیرمجاز']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'متد غیرمجاز']);
    exit();
}

try {
    $database = new Database();
    $db = $database->getConnection();
    
    $shop_name = $_POST['shop_name'] ?? '';
    
    if (empty($shop_name)) {
        echo json_encode(['success' => false, 'message' => 'نام فروشگاه الزامی است']);
        exit();
    }
    
    // بهروزرسانی نام فروشگاه
    $query = "INSERT INTO settings (setting_key, setting_value) VALUES (?, ?) 
             ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)";
    $stmt = $db->prepare($query);
    $stmt->execute(['shop_name', $shop_name]);
    
    echo json_encode([
        'success' => true, 
        'message' => 'نام فروشگاه با موفقیت بهروزرسانی شد',
        'shop_name' => $shop_name
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'خطا در بهروزرسانی: ' . $e->getMessage()]);
}
?>