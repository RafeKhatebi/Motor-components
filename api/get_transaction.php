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

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    echo json_encode(['success' => false, 'message' => 'روش نامعتبر']);
    exit;
}

try {
    $id = $_GET['id'] ?? '';
    
    if (empty($id)) {
        throw new Exception('شناسه تراکنش الزامی است');
    }

    $stmt = $pdo->prepare("SELECT * FROM expense_transactions WHERE id = ?");
    $stmt->execute([$id]);
    $transaction = $stmt->fetch();

    if (!$transaction) {
        throw new Exception('تراکنش یافت نشد');
    }

    echo json_encode(['success' => true, 'transaction' => $transaction]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}