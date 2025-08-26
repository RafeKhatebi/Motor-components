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
require_once '../includes/permissions.php';
require_once '../includes/business_rules.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $database = new Database();
    $db = $database->getConnection();
    
    $supplier_id = filter_input(INPUT_POST, 'supplier_id', FILTER_VALIDATE_INT);
    $payment_type = in_array($_POST['payment_type'] ?? 'cash', ['cash', 'credit']) ? $_POST['payment_type'] : 'cash';
    $paid_amount = (float)($_POST['paid_amount'] ?? 0);
    $products = $_POST['products'] ?? [];
    $quantities = $_POST['quantities'] ?? [];
    $prices = $_POST['prices'] ?? [];
    $new_product_names = $_POST['new_product_names'] ?? [];
    $new_product_codes = $_POST['new_product_codes'] ?? [];
    $new_product_categories = $_POST['new_product_categories'] ?? [];
    
    // Permission check
    PermissionManager::requirePermission('purchases.create');
    
    if (empty($supplier_id) || empty($products) || empty($quantities) || empty($prices)) {
        echo json_encode(['success' => false, 'message' => 'لطفا تمام فیلدها را پر کنید']);
        exit();
    }
    
    try {
        $db->beginTransaction();
        
        $total_amount = 0;
        $valid_items = [];
        
        for ($i = 0; $i < count($products); $i++) {
            if (!empty($quantities[$i]) && !empty($prices[$i])) {
                $product_id = null;
                
                // بررسی اینکه آیا محصول جدید است یا موجود
                if ($products[$i] === 'new' && !empty($new_product_names[$i]) && !empty($new_product_codes[$i])) {
                    // ایجاد محصول جدید
                    $new_product_query = "INSERT INTO products (name, code, category_id, buy_price, sell_price, stock_quantity, min_stock) VALUES (?, ?, ?, ?, ?, 0, 5)";
                    $new_product_stmt = $db->prepare($new_product_query);
                    $category_id = !empty($new_product_categories[$i]) ? $new_product_categories[$i] : null;
                    $new_product_stmt->execute([$new_product_names[$i], $new_product_codes[$i], $category_id, $prices[$i], $prices[$i]]);
                    $product_id = $db->lastInsertId();
                } elseif (!empty($products[$i]) && $products[$i] !== 'new') {
                    $product_id = $products[$i];
                }
                
                if ($product_id) {
                    $item_total = $quantities[$i] * $prices[$i];
                    $total_amount += $item_total;
                    
                    $valid_items[] = [
                        'product_id' => $product_id,
                        'quantity' => $quantities[$i],
                        'price' => $prices[$i],
                        'total' => $item_total
                    ];
                }
            }
        }
        
        if (empty($valid_items)) {
            throw new Exception('هیچ آیتم معتبری یافت نشد');
        }
        
        // محاسبه وضعیت پرداخت
        $remaining_amount = 0;
        $payment_status = 'paid';
        
        if ($payment_type === 'credit') {
            if ($paid_amount > $total_amount) {
                $paid_amount = $total_amount;
            }
            $remaining_amount = $total_amount - $paid_amount;
            
            if ($remaining_amount > 0) {
                $payment_status = $paid_amount > 0 ? 'partial' : 'unpaid';
            }
        } else {
            $paid_amount = $total_amount;
        }
        
        $purchase_query = "INSERT INTO purchases (supplier_id, total_amount, payment_type, paid_amount, remaining_amount, payment_status) VALUES (?, ?, ?, ?, ?, ?)";
        $purchase_stmt = $db->prepare($purchase_query);
        $purchase_stmt->execute([$supplier_id, $total_amount, $payment_type, $paid_amount, $remaining_amount, $payment_status]);
        
        $purchase_id = $db->lastInsertId();
        
        foreach ($valid_items as $item) {
            $item_query = "INSERT INTO purchase_items (purchase_id, product_id, quantity, unit_price, total_price) VALUES (?, ?, ?, ?, ?)";
            $item_stmt = $db->prepare($item_query);
            $item_stmt->execute([$purchase_id, $item['product_id'], $item['quantity'], $item['price'], $item['total']]);
            
            $update_stock_query = "UPDATE products SET stock_quantity = stock_quantity + ? WHERE id = ?";
            $update_stock_stmt = $db->prepare($update_stock_query);
            $update_stock_stmt->execute([$item['quantity'], $item['product_id']]);
        }
        
        $db->commit();
        echo json_encode(['success' => true, 'message' => 'فاکتور خرید با موفقیت ثبت شد']);
        
    } catch (Exception $e) {
        $db->rollBack();
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'درخواست نامعتبر']);
}
?>