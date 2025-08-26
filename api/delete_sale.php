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

    $sale_id = intval($_POST['sale_id']);

    if (!$sale_id) {
        throw new Exception('شناسه فاکتور نامعتبر');
    }

    // بررسی وجود فاکتور
    $check_query = "SELECT id FROM sales WHERE id = :id";
    $check_stmt = $db->prepare($check_query);
    $check_stmt->bindParam(':id', $sale_id);
    $check_stmt->execute();
    
    if (!$check_stmt->fetch()) {
        throw new Exception('فاکتور یافت نشد');
    }

    // دریافت آیتمهای فاکتور برای بازگرداندن موجودی
    $items_query = "SELECT product_id, quantity FROM sale_items WHERE sale_id = :sale_id";
    $items_stmt = $db->prepare($items_query);
    $items_stmt->bindParam(':sale_id', $sale_id);
    $items_stmt->execute();
    $items = $items_stmt->fetchAll(PDO::FETCH_ASSOC);

    // بازگرداندن موجودی محصولات
    foreach ($items as $item) {
        $update_stock_query = "UPDATE products SET stock_quantity = stock_quantity + :quantity WHERE id = :product_id";
        $update_stock_stmt = $db->prepare($update_stock_query);
        $update_stock_stmt->bindParam(':quantity', $item['quantity']);
        $update_stock_stmt->bindParam(':product_id', $item['product_id']);
        $update_stock_stmt->execute();
    }

    // حذف آیتمهای فاکتور
    $delete_items_query = "DELETE FROM sale_items WHERE sale_id = :sale_id";
    $delete_items_stmt = $db->prepare($delete_items_query);
    $delete_items_stmt->bindParam(':sale_id', $sale_id);
    $delete_items_stmt->execute();

    // حذف فاکتور
    $delete_sale_query = "DELETE FROM sales WHERE id = :id";
    $delete_sale_stmt = $db->prepare($delete_sale_query);
    $delete_sale_stmt->bindParam(':id', $sale_id);
    $delete_sale_stmt->execute();

    $db->commit();
    echo json_encode(['success' => true, 'message' => 'فاکتور با موفقیت حذف شد']);

} catch (Exception $e) {
    if (isset($db)) {
        $db->rollback();
    }
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>