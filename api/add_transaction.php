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
    $transaction_type = $_POST['transaction_type'] ?? '';
    $type_id = $_POST['type_id'] ?? '';
    $amount = $_POST['amount'] ?? '';
    $person_name = $_POST['person_name'] ?? '';
    $transaction_date = $_POST['transaction_date'] ?? '';
    $description = $_POST['description'] ?? '';

    // اعتبارسنجی
    if (empty($transaction_type) || empty($type_id) || empty($amount) || empty($person_name) || empty($transaction_date)) {
        throw new Exception('لطفاً تمام فیلدهای اجباری را پر کنید');
    }

    if (!in_array($transaction_type, ['expense', 'withdrawal'])) {
        throw new Exception('نوع تراکنش نامعتبر است');
    }

    if (!is_numeric($amount) || $amount <= 0) {
        throw new Exception('مبلغ باید عددی مثبت باشد');
    }

    // تولید کد منحصر به فرد
    $transaction_code = 'TXN' . date('Ymd') . str_pad(mt_rand(1, 9999), 4, '0', STR_PAD_LEFT);
    
    // بررسی یکتا بودن کد
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM expense_transactions WHERE transaction_code = ?");
    $stmt->execute([$transaction_code]);
    
    while ($stmt->fetchColumn() > 0) {
        $transaction_code = 'TXN' . date('Ymd') . str_pad(mt_rand(1, 9999), 4, '0', STR_PAD_LEFT);
        $stmt->execute([$transaction_code]);
    }

    // درج تراکنش
    $stmt = $pdo->prepare("
        INSERT INTO expense_transactions 
        (transaction_code, transaction_type, type_id, amount, person_name, description, transaction_date) 
        VALUES (?, ?, ?, ?, ?, ?, ?)
    ");
    
    $stmt->execute([
        $transaction_code,
        $transaction_type,
        $type_id,
        $amount,
        $person_name,
        $description,
        $transaction_date
    ]);

    echo json_encode(['success' => true, 'message' => 'تراکنش با موفقیت ثبت شد']);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}