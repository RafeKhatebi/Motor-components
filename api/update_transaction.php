<?php
require_once '../init_security.php';
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'دسترسی غیرمجاز']);
    exit;
}

require_once '../config/database.php';

$database = new Database();
$pdo = $database->getConnection();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'روش نامعتبر']);
    exit;
}

try {
    $id = $_POST['id'] ?? '';
    $transaction_type = $_POST['transaction_type'] ?? '';
    $type_id = $_POST['type_id'] ?? '';
    $amount = $_POST['amount'] ?? '';
    $person_name = $_POST['person_name'] ?? '';
    $transaction_date = $_POST['transaction_date'] ?? '';
    $description = $_POST['description'] ?? '';

    // اعتبارسنجی
    if (empty($id) || empty($transaction_type) || empty($type_id) || empty($amount) || empty($person_name) || empty($transaction_date)) {
        throw new Exception('لطفاً تمام فیلدهای اجباری را پر کنید');
    }

    if (!in_array($transaction_type, ['expense', 'withdrawal'])) {
        throw new Exception('نوع تراکنش نامعتبر است');
    }

    if (!is_numeric($amount) || $amount <= 0) {
        throw new Exception('مبلغ باید عددی مثبت باشد');
    }

    // بروزرسانی تراکنش
    $stmt = $pdo->prepare("
        UPDATE expense_transactions 
        SET transaction_type = ?, type_id = ?, amount = ?, person_name = ?, description = ?, transaction_date = ?
        WHERE id = ?
    ");
    
    $result = $stmt->execute([
        $transaction_type,
        $type_id,
        $amount,
        $person_name,
        $description,
        $transaction_date,
        $id
    ]);

    if (!$result) {
        throw new Exception('خطا در بروزرسانی تراکنش');
    }

    echo json_encode(['success' => true, 'message' => 'تراکنش با موفقیت بروزرسانی شد']);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}