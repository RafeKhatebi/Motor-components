<?php
require_once '../config/database.php';
require_once '../includes/functions.php';

header('Content-Type: application/json');

$database = new Database();
$db = $database->getConnection();

try {
    // Get purchases as expenses
    $query = "SELECT p.id, 'expense' as transaction_type, 'خرید' as type_name,
                     p.total_amount as amount, s.name as person_name,
                     p.created_at as transaction_date, CONCAT('خرید شماره ', p.id) as description,
                     CONCAT('PUR-', LPAD(p.id, 6, '0')) as transaction_code
              FROM purchases p
              LEFT JOIN suppliers s ON p.supplier_id = s.id
              WHERE p.status != 'returned'
              ORDER BY p.created_at DESC";
    
    $stmt = $db->prepare($query);
    $stmt->execute();
    $transactions = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Calculate summary
    $total_expenses = array_sum(array_column($transactions, 'amount'));
    $total_withdrawals = 0; // No withdrawals in current system

    $summary = [
        'total_expenses' => $total_expenses,
        'total_withdrawals' => $total_withdrawals
    ];

    echo json_encode([
        'success' => true,
        'transactions' => $transactions,
        'summary' => $summary
    ]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'خطا در بارگذاری گزارش: ' . $e->getMessage()]);
}
?>