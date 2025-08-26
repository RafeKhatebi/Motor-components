<?php
require_once '../init_security.php';
require_once '../config/database.php';
require_once '../includes/functions.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit();
}

// CSRF protection
if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf_token'])) {
    echo json_encode(['success' => false, 'message' => 'درخواست نامعتبر']);
    exit();
}

$id = $_POST['id'] ?? 0;
$name = trim($_POST['name'] ?? '');
$phone = trim($_POST['phone'] ?? '');
$address = trim($_POST['address'] ?? '');

if (!$id || !$name) {
    echo json_encode(['success' => false, 'message' => 'اطلاعات ناقص است']);
    exit();
}

// Validate phone format if provided
if ($phone && !preg_match('/^07\d{8}$/', $phone)) {
    echo json_encode(['success' => false, 'message' => 'شماره تلفن باید با 07 شروع شود و 10 رقم باشد']);
    exit();
}

try {
    $database = new Database();
    $db = $database->getConnection();
    
    // Check if customer exists
    $check_query = "SELECT id FROM customers WHERE id = ?";
    $check_stmt = $db->prepare($check_query);
    $check_stmt->execute([$id]);
    
    if (!$check_stmt->fetch()) {
        echo json_encode(['success' => false, 'message' => 'مشتری یافت نشد']);
        exit();
    }
    
    // Check for duplicate phone (excluding current customer)
    if ($phone) {
        $duplicate_query = "SELECT id FROM customers WHERE phone = ? AND id != ?";
        $duplicate_stmt = $db->prepare($duplicate_query);
        $duplicate_stmt->execute([$phone, $id]);
        
        if ($duplicate_stmt->fetch()) {
            echo json_encode(['success' => false, 'message' => 'شماره تلفن تکراری است']);
            exit();
        }
    }
    
    // Update customer
    $update_query = "UPDATE customers SET name = ?, phone = ?, address = ? WHERE id = ?";
    $update_stmt = $db->prepare($update_query);
    
    if ($update_stmt->execute([$name, $phone, $address, $id])) {
        echo json_encode(['success' => true, 'message' => 'مشتری با موفقیت بروزرسانی شد']);
    } else {
        echo json_encode(['success' => false, 'message' => 'خطا در بروزرسانی مشتری']);
    }
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'خطا در پردازش درخواست']);
}
?>