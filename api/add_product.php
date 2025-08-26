<?php
require_once '../init_security.php';
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'غیر مجاز']);
    exit();
}

// CSRF protection
if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf_token'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'درخواست نامعتبر']);
    exit();
}

require_once '../config/database.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $database = new Database();
    $db = $database->getConnection();
    
    // Input validation
    $name = trim($_POST['name'] ?? '');
    $code = trim($_POST['code'] ?? '');
    $category_id = filter_input(INPUT_POST, 'category_id', FILTER_VALIDATE_INT);
    $buy_price = filter_input(INPUT_POST, 'buy_price', FILTER_VALIDATE_FLOAT);
    $sell_price = filter_input(INPUT_POST, 'sell_price', FILTER_VALIDATE_FLOAT);
    $stock_quantity = filter_input(INPUT_POST, 'stock_quantity', FILTER_VALIDATE_INT) ?: 0;
    $min_stock = filter_input(INPUT_POST, 'min_stock', FILTER_VALIDATE_INT) ?: 5;
    $description = trim($_POST['description'] ?? '');
    
    // Validation
    if (empty($name) || empty($code) || !$category_id || $buy_price === false || $sell_price === false) {
        echo json_encode(['success' => false, 'message' => 'لطفا تمام فیلدهای ضروری را پر کنید']);
        exit();
    }
    
    // Business logic validation
    if ($sell_price <= $buy_price) {
        echo json_encode(['success' => false, 'message' => 'قیمت فروش باید بیشتر از قیمت خرید باشد']);
        exit();
    }
    
    if ($buy_price <= 0 || $sell_price <= 0) {
        echo json_encode(['success' => false, 'message' => 'قیمتها باید مثبت باشند']);
        exit();
    }
    
    if ($stock_quantity < 0 || $min_stock < 0) {
        echo json_encode(['success' => false, 'message' => 'موجودی نمیتواند منفی باشد']);
        exit();
    }
    
    try {
        // Check for duplicate code
        $check_query = "SELECT id FROM products WHERE code = ?";
        $check_stmt = $db->prepare($check_query);
        $check_stmt->execute([$code]);
        
        if ($check_stmt->fetch()) {
            echo json_encode(['success' => false, 'message' => 'کد محصول قبلاً استفاده شده است']);
            exit();
        }
        
        $db->beginTransaction();
        
        $query = "INSERT INTO products (name, code, category_id, buy_price, sell_price, stock_quantity, min_stock, description) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $db->prepare($query);
        $stmt->execute([$name, $code, $category_id, $buy_price, $sell_price, $stock_quantity, $min_stock, $description]);
        
        // Update sequence number if code follows PRD-XXXX pattern
        if (preg_match('/^PRD-(\d+)$/', $code, $matches)) {
            $sequence_number = intval($matches[1]);
            $update_sequence = "UPDATE product_sequence SET next_value = ? WHERE id = 1 AND next_value <= ?";
            $update_stmt = $db->prepare($update_sequence);
            $update_stmt->execute([$sequence_number + 1, $sequence_number]);
        }
        
        $db->commit();
        echo json_encode(['success' => true, 'message' => 'محصول با موفقیت اضافه شد']);
        
    } catch (PDOException $e) {
        if ($db->inTransaction()) {
            $db->rollBack();
        }
        error_log('Database error in add_product.php: ' . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'خطا در ذخیره اطلاعات']);
    }
} else {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'درخواست نامعتبر']);
}
?>