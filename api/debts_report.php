<?php
require_once '../init_security.php';
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'غیر مجاز']);
    exit();
}

require_once '../config/database.php';
require_once '../includes/functions.php';

header('Content-Type: application/json');

$database = new Database();
$db = $database->getConnection();

try {
    // بدهی مشتریان
    $customer_debts_query = "SELECT s.customer_id, COALESCE(c.name, 'مشتری نقدی') as customer_name,
                                    SUM(COALESCE(s.remaining_amount, 0)) as remaining_amount,
                                    CASE 
                                        WHEN SUM(COALESCE(s.remaining_amount, 0)) = 0 THEN 'paid'
                                        WHEN SUM(COALESCE(s.paid_amount, 0)) > 0 THEN 'partial'
                                        ELSE 'unpaid'
                                    END as payment_status
                             FROM sales s
                             LEFT JOIN customers c ON s.customer_id = c.id
                             WHERE COALESCE(s.payment_type, 'cash') = 'credit' 
                             AND COALESCE(s.remaining_amount, 0) > 0
                             GROUP BY s.customer_id, c.name
                             ORDER BY remaining_amount DESC";
    
    $customer_debts_stmt = $db->prepare($customer_debts_query);
    $customer_debts_stmt->execute();
    $customer_debts = $customer_debts_stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // طلب از تأمینکنندگان
    $supplier_credits_query = "SELECT p.supplier_id, s.name as supplier_name,
                                      SUM(COALESCE(p.remaining_amount, 0)) as remaining_amount,
                                      CASE 
                                          WHEN SUM(COALESCE(p.remaining_amount, 0)) = 0 THEN 'paid'
                                          WHEN SUM(COALESCE(p.paid_amount, 0)) > 0 THEN 'partial'
                                          ELSE 'unpaid'
                                      END as payment_status
                               FROM purchases p
                               LEFT JOIN suppliers s ON p.supplier_id = s.id
                               WHERE COALESCE(p.payment_type, 'cash') = 'credit' 
                               AND COALESCE(p.remaining_amount, 0) > 0
                               GROUP BY p.supplier_id, s.name
                               ORDER BY remaining_amount DESC";
    
    $supplier_credits_stmt = $db->prepare($supplier_credits_query);
    $supplier_credits_stmt->execute();
    $supplier_credits = $supplier_credits_stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // محاسبه خلاصه
    $total_customer_debt = array_sum(array_column($customer_debts, 'remaining_amount'));
    $total_supplier_credit = array_sum(array_column($supplier_credits, 'remaining_amount'));
    
    echo json_encode([
        'success' => true,
        'customer_debts' => $customer_debts,
        'supplier_credits' => $supplier_credits,
        'summary' => [
            'total_customer_debt' => $total_customer_debt,
            'total_supplier_credit' => $total_supplier_credit,
            'financial_balance' => $total_supplier_credit - $total_customer_debt
        ]
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'خطا در تولید گزارش: ' . $e->getMessage()
    ]);
}
?>