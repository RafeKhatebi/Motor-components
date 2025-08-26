<?php
require_once '../init_security.php';
require_once '../config/database.php';
require_once '../includes/functions.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'غیر مجاز']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'روش نامعتبر']);
    exit();
}

try {
    $database = new Database();
    $db = $database->getConnection();
    $db->beginTransaction();

    $purchase_id = intval($_POST['purchase_id']);

    if (!$purchase_id) {
        throw new Exception('شناسه فاکتور نامعتبر');
    }

    // بررسی وجود فاکتور
    $check_query = "SELECT id FROM purchases WHERE id = :id";
    $check_stmt = $db->prepare($check_query);
    $check_stmt->bindParam(':id', $purchase_id);
    $check_stmt->execute();
    
    if (!$check_stmt->fetch()) {
        throw new Exception('فاکتور یافت نشد');
    }

    // دریافت آیتمهای فاکتور برای کسر موجودی
    $items_query = "SELECT product_id, quantity FROM purchase_items WHERE purchase_id = :purchase_id";
    $items_stmt = $db->prepare($items_query);
    $items_stmt->bindParam(':purchase_id', $purchase_id);
    $items_stmt->execute();
    $items = $items_stmt->fetchAll(PDO::FETCH_ASSOC);

    // کسر موجودی محصولات
    foreach ($items as $item) {
        $update_stock_query = "UPDATE products SET stock_quantity = stock_quantity - :quantity WHERE id = :product_id";
        $update_stock_stmt = $db->prepare($update_stock_query);
        $update_stock_stmt->bindParam(':quantity', $item['quantity']);
        $update_stock_stmt->bindParam(':product_id', $item['product_id']);
        $update_stock_stmt->execute();
    }

    // حذف آیتمهای فاکتور
    $delete_items_query = "DELETE FROM purchase_items WHERE purchase_id = :purchase_id";
    $delete_items_stmt = $db->prepare($delete_items_query);
    $delete_items_stmt->bindParam(':purchase_id', $purchase_id);
    $delete_items_stmt->execute();

    // حذف فاکتور
    $delete_purchase_query = "DELETE FROM purchases WHERE id = :id";
    $delete_purchase_stmt = $db->prepare($delete_purchase_query);
    $delete_purchase_stmt->bindParam(':id', $purchase_id);
    $delete_purchase_stmt->execute();

    $db->commit();
    echo json_encode(['success' => true, 'message' => 'فاکتور با موفقیت حذف شد']);

} catch (Exception $e) {
    if (isset($db)) {
        $db->rollback();
    }
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>