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

try {
    // دریافت تمام تراکنشات مالی
    $query = "SELECT ft.*, tt.name as type_name 
              FROM expense_transactions ft 
              JOIN transaction_types tt ON ft.type_id = tt.id 
              ORDER BY ft.transaction_date DESC, ft.created_at DESC";
    
    $stmt = $pdo->prepare($query);
    $stmt->execute();
    $transactions = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // محاسبه خلاصه
    $total_expenses = 0;
    $total_withdrawals = 0;
    
    foreach ($transactions as $transaction) {
        if ($transaction['transaction_type'] === 'expense') {
            $total_expenses += $transaction['amount'];
        } else {
            $total_withdrawals += $transaction['amount'];
        }
    }
    
    $summary = [
        'total_expenses' => $total_expenses,
        'total_withdrawals' => $total_withdrawals,
        'total_transactions' => $total_expenses + $total_withdrawals,
        'transaction_count' => count($transactions)
    ];
    
    echo json_encode([
        'success' => true,
        'transactions' => $transactions,
        'summary' => $summary
    ]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}