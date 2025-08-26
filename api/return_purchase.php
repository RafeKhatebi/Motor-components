<?php
require_once '../init_security.php';
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'غیر مجاز']);
    exit();
}

require_once '../config/database.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $database = new Database();
    $db = $database->getConnection();
    
    $purchase_id = filter_input(INPUT_POST, 'purchase_id', FILTER_VALIDATE_INT);
    $reason = trim($_POST['reason'] ?? '');
    $user_id = $_SESSION['user_id'];
    
    if (!$purchase_id || empty($reason)) {
        echo json_encode(['success' => false, 'message' => 'اطلاعات ناقص']);
        exit();
    }
    
    try {
        $db->beginTransaction();
        
        // بررسی وضعیت فاکتور
        $check_query = "SELECT status, total_amount FROM purchases WHERE id = ?";
        $check_stmt = $db->prepare($check_query);
        $check_stmt->execute([$purchase_id]);
        $purchase = $check_stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$purchase || $purchase['status'] === 'returned') {
            $db->rollBack();
            echo json_encode(['success' => false, 'message' => 'فاکتور قابل برگشت نیست']);
            exit();
        }
        
        // کسر موجودی کالاها
        $items_query = "SELECT product_id, quantity FROM purchase_items WHERE purchase_id = ?";
        $items_stmt = $db->prepare($items_query);
        $items_stmt->execute([$purchase_id]);
        
        while ($item = $items_stmt->fetch(PDO::FETCH_ASSOC)) {
            // بررسی موجودی کافی با row locking
            $stock_query = "SELECT stock_quantity FROM products WHERE id = ? FOR UPDATE";
            $stock_stmt = $db->prepare($stock_query);
            $stock_stmt->execute([$item['product_id']]);
            $stock = $stock_stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($stock['stock_quantity'] < $item['quantity']) {
                $db->rollBack();
                echo json_encode(['success' => false, 'message' => 'موجودی کافی برای برگشت وجود ندارد']);
                exit();
            }
            
            $update_stock = "UPDATE products SET stock_quantity = stock_quantity - ? WHERE id = ?";
            $update_stmt = $db->prepare($update_stock);
            $update_stmt->execute([$item['quantity'], $item['product_id']]);
        }
        
        // بهروزرسانی وضعیت فاکتور
        $update_purchase = "UPDATE purchases SET status = 'returned', return_reason = ?, returned_at = NOW(), returned_by = ? WHERE id = ?";
        $update_stmt = $db->prepare($update_purchase);
        $update_stmt->execute([$reason, $user_id, $purchase_id]);
        
        // ثبت تراکنش مالی
        $transaction_query = "INSERT INTO financial_transactions (transaction_type, reference_id, amount, description, created_by) VALUES ('purchase_return', ?, ?, ?, ?)";
        $transaction_stmt = $db->prepare($transaction_query);
        $transaction_stmt->execute([$purchase_id, $purchase['total_amount'], "برگشت فاکتور خرید #$purchase_id - $reason", $user_id]);
        
        $db->commit();
        echo json_encode(['success' => true, 'message' => 'فاکتور با موفقیت برگشت داده شد']);
        
    } catch (Exception $e) {
        $db->rollBack();
        error_log('Return purchase error: ' . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'خطا در برگشت فاکتور']);
    }
} else {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'درخواست نامعتبر']);
}
?>