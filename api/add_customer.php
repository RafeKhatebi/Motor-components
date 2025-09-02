<?php
require_once '../init_security.php';

function sendJsonResponse($success, $message, $httpCode = 200) {
    http_response_code($httpCode);
    header('Content-Type: application/json');
    echo json_encode(['success' => $success, 'message' => $message]);
    exit();
}

if (!isset($_SESSION['user_id'])) {
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
    $database = new Database();
    $db = $database->getConnection();
    
    $name = trim($_POST['name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $customer_type = in_array($_POST['customer_type'] ?? 'retail', ['retail', 'wholesale', 'garage', 'dealer']) ? $_POST['customer_type'] : 'retail';
    $discount_percentage = filter_input(INPUT_POST, 'discount_percentage', FILTER_VALIDATE_FLOAT) ?: 0;
    $credit_limit = filter_input(INPUT_POST, 'credit_limit', FILTER_VALIDATE_FLOAT) ?: 0;
    
    // Sanitize inputs
    $name = htmlspecialchars($name, ENT_QUOTES, 'UTF-8');
    $phone = htmlspecialchars($phone, ENT_QUOTES, 'UTF-8');
    $address = htmlspecialchars($address, ENT_QUOTES, 'UTF-8');
    
    // Input validation
    if (empty($name)) {
        sendJsonResponse(false, 'نام مشتری الزامی است', 400);
    }
    
    if ($phone && !preg_match('/^07\d{8}$/', $phone)) {
        sendJsonResponse(false, 'شماره تلفن باید با 07 شروع شود و 10 رقم باشد', 400);
    }
    
    if (strlen($address) > 500) {
        sendJsonResponse(false, 'آدرس خیلی طولانی است', 400);
    }
    
    // Check phone uniqueness
    if ($phone) {
        $phone_check = "SELECT id FROM customers WHERE phone = ?";
        $phone_stmt = $db->prepare($phone_check);
        $phone_stmt->execute([$phone]);
        if ($phone_stmt->fetch()) {
            sendJsonResponse(false, 'شماره تلفن قبلاً ثبت شده است', 400);
        }
    }
    
    $query = "INSERT INTO customers (name, phone, address, customer_type, discount_percentage, credit_limit) VALUES (?, ?, ?, ?, ?, ?)";
    $stmt = $db->prepare($query);
    $result = $stmt->execute([$name, $phone, $address, $customer_type, $discount_percentage, $credit_limit]);
    
    if ($result && $stmt->rowCount() > 0) {
        sendJsonResponse(true, 'مشتری با موفقیت اضافه شد');
    } else {
        sendJsonResponse(false, 'خطا در ذخیره اطلاعات', 500);
    }
    
} catch (PDOException $e) {
    error_log('Customer add error: ' . $e->getMessage());
    sendJsonResponse(false, 'خطا در ذخیره اطلاعات', 500);
} catch (Exception $e) {
    error_log('Unexpected error: ' . $e->getMessage());
    sendJsonResponse(false, 'خطای غیرمنتظره', 500);
}
?>