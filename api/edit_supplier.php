<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'غیر مجاز']);
    exit();
}

require_once '../config/database.php';
require_once '../includes/functions.php';

header('Content-Type: application/json');

// CSRF Protection
if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    echo json_encode(['success' => false, 'message' => 'درخواست نامعتبر']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'روش درخواست نامعتبر']);
    exit();
}

$id = $_POST['id'] ?? '';
$name = trim($_POST['name'] ?? '');
$phone = trim($_POST['phone'] ?? '');
$address = trim($_POST['address'] ?? '');

if (empty($id) || !is_numeric($id)) {
    echo json_encode(['success' => false, 'message' => 'شناسه نامعتبر']);
    exit();
}

if (empty($name)) {
    echo json_encode(['success' => false, 'message' => 'نام تأمینکننده الزامی است']);
    exit();
}

// Phone validation
if (!empty($phone) && !preg_match('/^07\d{8}$/', $phone)) {
    echo json_encode(['success' => false, 'message' => 'شماره تلفن باید با 07 شروع شود و 10 رقم باشد']);
    exit();
}

$database = new Database();
$db = $database->getConnection();

try {
    // Check if supplier exists
    $checkQuery = "SELECT phone FROM suppliers WHERE id = ?";
    $checkStmt = $db->prepare($checkQuery);
    $checkStmt->execute([$id]);
    $currentSupplier = $checkStmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$currentSupplier) {
        echo json_encode(['success' => false, 'message' => 'تأمینکننده یافت نشد']);
        exit();
    }
    
    // Check phone uniqueness only if phone changed
    if (!empty($phone) && $phone !== $currentSupplier['phone']) {
        $phoneQuery = "SELECT id FROM suppliers WHERE phone = ? AND id != ?";
        $phoneStmt = $db->prepare($phoneQuery);
        $phoneStmt->execute([$phone, $id]);
        
        if ($phoneStmt->fetch()) {
            echo json_encode(['success' => false, 'message' => 'شماره تلفن قبلاً ثبت شده است']);
            exit();
        }
    }
    
    $query = "UPDATE suppliers SET name = ?, phone = ?, address = ? WHERE id = ?";
    $stmt = $db->prepare($query);
    
    if ($stmt->execute([$name, $phone, $address, $id])) {
        echo json_encode(['success' => true, 'message' => 'تأمینکننده با موفقیت بهروزرسانی شد']);
    } else {
        echo json_encode(['success' => false, 'message' => 'خطا در بهروزرسانی تأمینکننده']);
    }
    
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'خطا در پایگاه داده']);
}
?>