<?php
require_once '../config/database.php';
require_once '../includes/functions.php';

header('Content-Type: application/json');

$database = new Database();
$db = $database->getConnection();

try {
    // Get customer debts
    $customer_debts_query = "SELECT c.name as customer_name, 
                                    SUM(s.final_amount - COALESCE(sp.total_paid, 0)) as remaining_amount,
                                    CASE 
                                        WHEN SUM(COALESCE(sp.total_paid, 0)) = 0 THEN 'unpaid'
                                        WHEN SUM(COALESCE(sp.total_paid, 0)) < SUM(s.final_amount) THEN 'partial'
                                        ELSE 'paid'
                                    END as payment_status
                             FROM sales s
                             JOIN customers c ON s.customer_id = c.id
                             LEFT JOIN (
                                 SELECT sale_id, SUM(amount) as total_paid
                                 FROM sale_payments
                                 GROUP BY sale_id
                             ) sp ON s.id = sp.sale_id
                             WHERE s.payment_status != 'paid'
                             GROUP BY s.customer_id
                             HAVING remaining_amount > 0
                             ORDER BY remaining_amount DESC";
    
    $stmt = $db->prepare($customer_debts_query);
    $stmt->execute();
    $customer_debts = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Get supplier credits (what we owe to suppliers)
    $supplier_credits_query = "SELECT s.name as supplier_name,
                                      SUM(p.total_amount - COALESCE(pp.total_paid, 0)) as remaining_amount,
                                      CASE 
                                          WHEN SUM(COALESCE(pp.total_paid, 0)) = 0 THEN 'unpaid'
                                          WHEN SUM(COALESCE(pp.total_paid, 0)) < SUM(p.total_amount) THEN 'partial'
                                          ELSE 'paid'
                                      END as payment_status
                               FROM purchases p
                               JOIN suppliers s ON p.supplier_id = s.id
                               LEFT JOIN (
                                   SELECT purchase_id, SUM(amount) as total_paid
                                   FROM purchase_payments
                                   GROUP BY purchase_id
                               ) pp ON p.id = pp.purchase_id
                               WHERE p.payment_status != 'paid'
                               GROUP BY p.supplier_id
                               HAVING remaining_amount > 0
                               ORDER BY remaining_amount DESC";
    
    $stmt = $db->prepare($supplier_credits_query);
    $stmt->execute();
    $supplier_credits = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Calculate totals
    $total_customer_debt = array_sum(array_column($customer_debts, 'remaining_amount'));
    $total_supplier_credit = array_sum(array_column($supplier_credits, 'remaining_amount'));

    $summary = [
        'total_customer_debt' => $total_customer_debt,
        'total_supplier_credit' => $total_supplier_credit
    ];

    echo json_encode([
        'success' => true,
        'customer_debts' => $customer_debts,
        'supplier_credits' => $supplier_credits,
        'summary' => $summary
    ]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'خطا در بارگذاری گزارش: ' . $e->getMessage()]);
}
?>