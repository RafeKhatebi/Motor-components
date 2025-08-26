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
    $input = json_decode(file_get_contents('php://input'), true);
    $id = $input['id'] ?? '';
    
    if (empty($id)) {
        throw new Exception('شناسه تراکنش الزامی است');
    }

    $stmt = $pdo->prepare("DELETE FROM expense_transactions WHERE id = ?");
    $result = $stmt->execute([$id]);

    if (!$result) {
        throw new Exception('خطا در حذف تراکنش');
    }

    echo json_encode(['success' => true, 'message' => 'تراکنش با موفقیت حذف شد']);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}