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

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'روش درخواست نامعتبر']);
    exit();
}

$database = new Database();
$db = $database->getConnection();

$type = $_POST['type'] ?? ''; // 'sale' or 'purchase'
$id = (int)($_POST['id'] ?? 0);
$amount = (float)($_POST['amount'] ?? 0);
$payment_date = $_POST['payment_date'] ?? date('Y-m-d');
$notes = $_POST['notes'] ?? '';

if (!in_array($type, ['sale', 'purchase']) || $id <= 0 || $amount <= 0) {
    echo json_encode(['success' => false, 'message' => 'اطلاعات نامعتبر']);
    exit();
}

try {
    $db->beginTransaction();
    
    if ($type === 'sale') {
        // بررسی وجود فروش
        $check_query = "SELECT remaining_amount, payment_status FROM sales WHERE id = ?";
        $check_stmt = $db->prepare($check_query);
        $check_stmt->execute([$id]);
        $sale = $check_stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$sale) {
            throw new Exception('فروش یافت نشد');
        }
        
        if ($amount > $sale['remaining_amount']) {
            throw new Exception('مبلغ پرداخت بیشتر از باقیمانده است');
        }
        
        // ثبت پرداخت
        $payment_query = "INSERT INTO sale_payments (sale_id, amount, payment_date, notes) VALUES (?, ?, ?, ?)";
        $payment_stmt = $db->prepare($payment_query);
        $payment_stmt->execute([$id, $amount, $payment_date, $notes]);
        
        // بروزرسانی وضعیت فروش
        $new_remaining = $sale['remaining_amount'] - $amount;
        $new_status = $new_remaining <= 0 ? 'paid' : 'partial';
        
        $update_query = "UPDATE sales SET remaining_amount = ?, payment_status = ? WHERE id = ?";
        $update_stmt = $db->prepare($update_query);
        $update_stmt->execute([$new_remaining, $new_status, $id]);
        
    } else { // purchase
        // بررسی وجود خرید
        $check_query = "SELECT remaining_amount, payment_status FROM purchases WHERE id = ?";
        $check_stmt = $db->prepare($check_query);
        $check_stmt->execute([$id]);
        $purchase = $check_stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$purchase) {
            throw new Exception('خرید یافت نشد');
        }
        
        if ($amount > $purchase['remaining_amount']) {
            throw new Exception('مبلغ پرداخت بیشتر از باقیمانده است');
        }
        
        // ثبت پرداخت
        $payment_query = "INSERT INTO purchase_payments (purchase_id, amount, payment_date, notes) VALUES (?, ?, ?, ?)";
        $payment_stmt = $db->prepare($payment_query);
        $payment_stmt->execute([$id, $amount, $payment_date, $notes]);
        
        // بروزرسانی وضعیت خرید
        $new_remaining = $purchase['remaining_amount'] - $amount;
        $new_status = $new_remaining <= 0 ? 'paid' : 'partial';
        
        $update_query = "UPDATE purchases SET remaining_amount = ?, payment_status = ? WHERE id = ?";
        $update_stmt = $db->prepare($update_query);
        $update_stmt->execute([$new_remaining, $new_status, $id]);
    }
    
    $db->commit();
    echo json_encode(['success' => true, 'message' => 'پرداخت با موفقیت ثبت شد']);
    
} catch (Exception $e) {
    $db->rollBack();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>