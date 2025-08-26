<?php
require_once '../init_security.php';
require_once '../config/database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Access denied']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit();
}

$input = json_decode(file_get_contents('php://input'), true);
$language = $input['language'] ?? 'fa';

if (!in_array($language, ['fa', 'ps'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid language']);
    exit();
}

try {
    $database = new Database();
    $db = $database->getConnection();
    
    // بروزرسانی زبان در دیتابیس
    $query = "INSERT INTO settings (setting_key, setting_value) VALUES ('language', ?) 
              ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)";
    $stmt = $db->prepare($query);
    $stmt->execute([$language]);
    
    // بروزرسانی زبان در جلسه
    $_SESSION['language'] = $language;
    
    echo json_encode(['success' => true, 'message' => 'Language updated successfully']);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>