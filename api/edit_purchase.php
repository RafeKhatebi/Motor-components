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
    $supplier_id = intval($_POST['supplier_id']);
    $products = $_POST['products'] ?? [];
    $quantities = $_POST['quantities'] ?? [];
    $prices = $_POST['prices'] ?? [];

    if (!$purchase_id || !$supplier_id) {
        throw new Exception('اطلاعات ناقص');
    }

    // بررسی وجود فاکتور
    $check_query = "SELECT id FROM purchases WHERE id = :id";
    $check_stmt = $db->prepare($check_query);
    $check_stmt->bindParam(':id', $purchase_id);
    $check_stmt->execute();
    
    if (!$check_stmt->fetch()) {
        throw new Exception('فاکتور یافت نشد');
    }

    // محاسبه مبالغ
    $total_amount = 0;
    $valid_items = [];

    for ($i = 0; $i < count($products); $i++) {
        if (!empty($products[$i]) && !empty($quantities[$i]) && !empty($prices[$i])) {
            $product_id = intval($products[$i]);
            $quantity = floatval($quantities[$i]);
            $price = floatval($prices[$i]);
            $subtotal = $quantity * $price;
            
            $valid_items[] = [
                'product_id' => $product_id,
                'quantity' => $quantity,
                'unit_price' => $price,
                'subtotal' => $subtotal
            ];
            
            $total_amount += $subtotal;
        }
    }

    if (empty($valid_items)) {
        throw new Exception('حداقل یک محصول معتبر وارد کنید');
    }

    // بروزرسانی فاکتور
    $update_purchase_query = "UPDATE purchases SET 
                              supplier_id = :supplier_id,
                              total_amount = :total_amount
                              WHERE id = :id";
    
    $update_purchase_stmt = $db->prepare($update_purchase_query);
    $update_purchase_stmt->bindParam(':supplier_id', $supplier_id);
    $update_purchase_stmt->bindParam(':total_amount', $total_amount);
    $update_purchase_stmt->bindParam(':id', $purchase_id);
    $update_purchase_stmt->execute();

    // حذف آیتمهای قبلی
    $delete_items_query = "DELETE FROM purchase_items WHERE purchase_id = :purchase_id";
    $delete_items_stmt = $db->prepare($delete_items_query);
    $delete_items_stmt->bindParam(':purchase_id', $purchase_id);
    $delete_items_stmt->execute();

    // اضافه کردن آیتمهای جدید
    $insert_item_query = "INSERT INTO purchase_items (purchase_id, product_id, quantity, unit_price, total_price) 
                          VALUES (:purchase_id, :product_id, :quantity, :unit_price, :total_price)";
    $insert_item_stmt = $db->prepare($insert_item_query);

    foreach ($valid_items as $item) {
        $insert_item_stmt->bindParam(':purchase_id', $purchase_id);
        $insert_item_stmt->bindParam(':product_id', $item['product_id']);
        $insert_item_stmt->bindParam(':quantity', $item['quantity']);
        $insert_item_stmt->bindParam(':unit_price', $item['unit_price']);
        $insert_item_stmt->bindParam(':total_price', $item['subtotal']);
        $insert_item_stmt->execute();
    }

    $db->commit();
    echo json_encode(['success' => true, 'message' => 'فاکتور با موفقیت ویرایش شد']);

} catch (Exception $e) {
    if (isset($db)) {
        $db->rollback();
    }
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>