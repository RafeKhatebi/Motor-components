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
    $customer_id = !empty($_POST['customer_id']) ? intval($_POST['customer_id']) : null;
    $discount = floatval($_POST['discount'] ?? 0);
    $products = $_POST['products'] ?? [];
    $quantities = $_POST['quantities'] ?? [];
    $prices = $_POST['prices'] ?? [];

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

    $discount_amount = ($total_amount * $discount) / 100;
    $final_amount = $total_amount - $discount_amount;

    // بروزرسانی فاکتور
    $update_sale_query = "UPDATE sales SET 
                          customer_id = :customer_id,
                          total_amount = :total_amount,
                          discount = :discount,
                          final_amount = :final_amount
                          WHERE id = :id";
    
    $update_sale_stmt = $db->prepare($update_sale_query);
    $update_sale_stmt->bindParam(':customer_id', $customer_id);
    $update_sale_stmt->bindParam(':total_amount', $total_amount);
    $update_sale_stmt->bindParam(':discount', $discount);
    $update_sale_stmt->bindParam(':final_amount', $final_amount);
    $update_sale_stmt->bindParam(':id', $sale_id);
    $update_sale_stmt->execute();

    // حذف آیتمهای قبلی
    $delete_items_query = "DELETE FROM sale_items WHERE sale_id = :sale_id";
    $delete_items_stmt = $db->prepare($delete_items_query);
    $delete_items_stmt->bindParam(':sale_id', $sale_id);
    $delete_items_stmt->execute();

    // اضافه کردن آیتمهای جدید
    $insert_item_query = "INSERT INTO sale_items (sale_id, product_id, quantity, unit_price, total_price) 
                          VALUES (:sale_id, :product_id, :quantity, :unit_price, :total_price)";
    $insert_item_stmt = $db->prepare($insert_item_query);

    foreach ($valid_items as $item) {
        $insert_item_stmt->bindParam(':sale_id', $sale_id);
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