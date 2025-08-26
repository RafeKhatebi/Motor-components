<?php
require_once '../init_security.php';

function sendJsonResponse($success, $message, $httpCode = 200) {
    http_response_code($httpCode);
    header('Content-Type: application/json');
    echo json_encode(['success' => $success, 'message' => $message]);
    exit();
}

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    sendJsonResponse(false, 'غیر مجاز', 401);
}

// CSRF protection
if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf_token'])) {
    sendJsonResponse(false, 'درخواست نامعتبر', 403);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendJsonResponse(false, 'درخواست نامعتبر', 405);
}

require_once '../config/database.php';

try {
    $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
    
    if (!$id || $id <= 0) {
        sendJsonResponse(false, 'شناسه کاربر نامعتبر', 400);
    }
    
    if ($id == $_SESSION['user_id']) {
        sendJsonResponse(false, 'نمیتوانید خودتان را حذف کنید', 400);
    }
    
    $database = new Database();
    $db = $database->getConnection();
    
    // Check if user exists
    $check_query = "SELECT id FROM users WHERE id = ?";
    $check_stmt = $db->prepare($check_query);
    $check_stmt->execute([$id]);
    
    if (!$check_stmt->fetch()) {
        sendJsonResponse(false, 'کاربر یافت نشد', 404);
    }
    
    $query = "DELETE FROM users WHERE id = ?";
    $stmt = $db->prepare($query);
    $result = $stmt->execute([$id]);
    
    if ($result && $stmt->rowCount() > 0) {
        sendJsonResponse(true, 'کاربر با موفقیت حذف شد');
    } else {
        sendJsonResponse(false, 'خطا در حذف کاربر', 500);
    }
    
} catch (PDOException $e) {
    error_log('User delete error: ' . $e->getMessage());
    sendJsonResponse(false, 'خطا در حذف کاربر', 500);
} catch (Exception $e) {
    error_log('Unexpected error: ' . $e->getMessage());
    sendJsonResponse(false, 'خطای غیرمنتظره', 500);
}
?>