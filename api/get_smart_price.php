<?php
require_once '../init_security.php';
require_once '../config/database.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'غیر مجاز']);
    exit();
}

$product_id = filter_input(INPUT_GET, 'product_id', FILTER_VALIDATE_INT);
$customer_id = filter_input(INPUT_GET, 'customer_id', FILTER_VALIDATE_INT);
$quantity = filter_input(INPUT_GET, 'quantity', FILTER_VALIDATE_INT) ?: 1;

if (!$product_id) {
    echo json_encode(['success' => false, 'message' => 'شناسه محصول الزامی است']);
    exit();
}

try {
    $database = new Database();
    $db = $database->getConnection();
    
    // دریافت قیمت پایه محصول
    $product_query = "SELECT sell_price FROM products WHERE id = ?";
    $product_stmt = $db->prepare($product_query);
    $product_stmt->execute([$product_id]);
    $product = $product_stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$product) {
        echo json_encode(['success' => false, 'message' => 'محصول یافت نشد']);
        exit();
    }
    
    $base_price = $product['sell_price'];
    $final_price = $base_price;
    $customer_type = 'retail';
    $customer_discount = 0;
    
    // اگر مشتری مشخص شده، نوع و تخفیف او را دریافت کن
    if ($customer_id) {
        $customer_query = "SELECT customer_type, discount_percentage FROM customers WHERE id = ?";
        $customer_stmt = $db->prepare($customer_query);
        $customer_stmt->execute([$customer_id]);
        $customer = $customer_stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($customer) {
            $customer_type = $customer['customer_type'];
            $customer_discount = $customer['discount_percentage'];
        }
    }
    
    // بررسی قیمت ویژه برای نوع مشتری
    $special_price_query = "SELECT price FROM product_prices WHERE product_id = ? AND customer_type = ? AND min_quantity <= ?";
    $special_price_stmt = $db->prepare($special_price_query);
    $special_price_stmt->execute([$product_id, $customer_type, $quantity]);
    $special_price = $special_price_stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($special_price) {
        $final_price = $special_price['price'];
    }
    
    // بررسی تخفیف حجمی
    $volume_discount_query = "SELECT discount_percentage FROM volume_discounts 
                             WHERE product_id = ? AND min_quantity <= ? 
                             AND (customer_type IS NULL OR customer_type = ?) 
                             ORDER BY min_quantity DESC LIMIT 1";
    $volume_stmt = $db->prepare($volume_discount_query);
    $volume_stmt->execute([$product_id, $quantity, $customer_type]);
    $volume_discount = $volume_stmt->fetch(PDO::FETCH_ASSOC);
    
    $volume_discount_percent = 0;
    if ($volume_discount) {
        $volume_discount_percent = $volume_discount['discount_percentage'];
    }
    
    // اعمال تخفیفات
    $total_discount = max($customer_discount, $volume_discount_percent);
    $discount_amount = ($final_price * $total_discount) / 100;
    $final_price = $final_price - $discount_amount;
    
    echo json_encode([
        'success' => true,
        'data' => [
            'base_price' => $base_price,
            'final_price' => $final_price,
            'customer_type' => $customer_type,
            'customer_discount' => $customer_discount,
            'volume_discount' => $volume_discount_percent,
            'applied_discount' => $total_discount,
            'discount_amount' => $discount_amount,
            'quantity' => $quantity
        ]
    ]);
    
} catch (Exception $e) {
    error_log('Smart pricing error: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'خطا در محاسبه قیمت']);
}
?>