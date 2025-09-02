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

try {
    require_once '../config/database.php';
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'خطا در بارگیری سیستم']);
    exit();
}

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $database = new Database();
        $db = $database->getConnection();
        
        if (!$db) {
            throw new Exception('Database connection failed');
        }
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'خطا در اتصال به پایگاه داده']);
        exit();
    }

    // Input validation and sanitization
    $customer_id = !empty($_POST['customer_id']) ? (int)$_POST['customer_id'] : null;
    $discount = filter_input(INPUT_POST, 'discount', FILTER_VALIDATE_FLOAT, FILTER_FLAG_ALLOW_FRACTION) ?: 0;
    $payment_type = in_array($_POST['payment_type'] ?? 'cash', ['cash', 'credit']) ? $_POST['payment_type'] : 'cash';
    $paid_amount = (float)($_POST['paid_amount'] ?? 0);
    $products = $_POST['products'] ?? [];
    $quantities = $_POST['quantities'] ?? [];
    $prices = $_POST['prices'] ?? [];
    
    $discount = $discount ?: 0;

    // بررسی وجود آرایهها
    if (!is_array($products) || !is_array($quantities) || !is_array($prices)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'داده‌های ارسالی نامعتبر است']);
        exit();
    }
    
    if (empty($products) || empty($quantities) || empty($prices)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'لطفا حداقل یک محصول انتخاب کنید']);
        exit();
    }
    
    // بررسی تعداد عناصر آرایهها
    if (count($products) !== count($quantities) || count($products) !== count($prices)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'تعداد محصولات، مقادیر و قیمت‌ها باید برابر باشد']);
        exit();
    }

    try {
        $db->beginTransaction();

        $total_amount = 0;
        $valid_items = [];

        // بررسی و محاسبه آیتمها
        for ($i = 0; $i < count($products); $i++) {
            $product_id = (int) $products[$i];
            $quantity = (int) $quantities[$i];
            $price = (float) $prices[$i];

            if ($product_id > 0 && $quantity > 0 && $price > 0) {
                // بررسی موجودی با row locking
                $stock_query = "SELECT stock_quantity FROM products WHERE id = ? AND id > 0 FOR UPDATE";
                $stock_stmt = $db->prepare($stock_query);
                $stock_stmt->execute([$product_id]);
                $stock = $stock_stmt->fetch(PDO::FETCH_ASSOC);

                if (!$stock || $stock['stock_quantity'] < $quantity) {
                    $db->rollBack();
                    http_response_code(409);
                    echo json_encode(['success' => false, 'message' => 'موجودی کافی نیست']);
                    exit();
                }

                $item_total = $quantity * $price;
                $total_amount += $item_total;

                $valid_items[] = [
                    'product_id' => $product_id,
                    'quantity' => $quantity,
                    'price' => $price,
                    'total' => $item_total
                ];
            }
        }

        if (empty($valid_items)) {
            $db->rollBack();
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'هیچ آیتم معتبری یافت نشد']);
            exit();
        }

        // بررسی و محاسبه تخفیف درصدی
        if ($discount < 0 || $discount > 100) {
            $db->rollBack();
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'درصد تخفیف باید بین 0 تا 100 باشد']);
            exit();
        }
        
        $discount_amount = ($total_amount * $discount) / 100;
        $final_amount = $total_amount - $discount_amount;
        
        if ($final_amount < 0) {
            $db->rollBack();
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'مبلغ نهایی نمیتواند منفی باشد']);
            exit();
        }

        // محاسبه وضعیت پرداخت
        $remaining_amount = 0;
        $payment_status = 'paid';
        
        if ($payment_type === 'credit') {
            if ($paid_amount > $final_amount) {
                $paid_amount = $final_amount;
            }
            $remaining_amount = $final_amount - $paid_amount;
            
            if ($remaining_amount > 0) {
                $payment_status = $paid_amount > 0 ? 'partial' : 'unpaid';
            }
        } else {
            $paid_amount = $final_amount;
        }
        
        // ثبت فاکتور با اطلاعات پرداخت
        $sale_query = "INSERT INTO sales (customer_id, total_amount, discount, final_amount, payment_type, paid_amount, remaining_amount, payment_status) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        $sale_stmt = $db->prepare($sale_query);
        $sale_stmt->execute([$customer_id, $total_amount, $discount_amount, $final_amount, $payment_type, $paid_amount, $remaining_amount, $payment_status]);

        $sale_id = $db->lastInsertId();

        // ثبت آیتمها، کاهش موجودی و ایجاد گارانتی
        foreach ($valid_items as $item) {
            // ثبت آیتم
            $item_query = "INSERT INTO sale_items (sale_id, product_id, quantity, unit_price, total_price) VALUES (?, ?, ?, ?, ?)";
            $item_stmt = $db->prepare($item_query);
            $item_stmt->execute([$sale_id, $item['product_id'], $item['quantity'], $item['price'], $item['total']]);
            $sale_item_id = $db->lastInsertId();

            // کاهش موجودی
            $update_stock_query = "UPDATE products SET stock_quantity = stock_quantity - ? WHERE id = ?";
            $update_stock_stmt = $db->prepare($update_stock_query);
            $update_stock_stmt->execute([$item['quantity'], $item['product_id']]);
            
            // ایجاد گارانتی خودکار اگر محصول گارانتی دارد
            $warranty_check = "SELECT warranty_months FROM products WHERE id = ? AND warranty_months > 0";
            $warranty_stmt = $db->prepare($warranty_check);
            $warranty_stmt->execute([$item['product_id']]);
            $product_warranty = $warranty_stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($product_warranty && $product_warranty['warranty_months'] > 0) {
                $warranty_months = $product_warranty['warranty_months'];
                $warranty_start = date('Y-m-d');
                $warranty_end = date('Y-m-d', strtotime("+{$warranty_months} months"));
                
                $warranty_query = "INSERT INTO warranties (sale_item_id, product_id, customer_id, warranty_start, warranty_end, warranty_months, warranty_type) 
                                  VALUES (?, ?, ?, ?, ?, ?, 'shop')";
                $warranty_insert = $db->prepare($warranty_query);
                $warranty_insert->execute([$sale_item_id, $item['product_id'], $customer_id, $warranty_start, $warranty_end, $warranty_months]);
            }
        }

        // Skip audit logging for now
        
        $db->commit();
        echo json_encode(['success' => true, 'message' => 'فاکتور با موفقیت ثبت شد', 'sale_id' => $sale_id]);
        exit();

    } catch (PDOException $e) {
        if ($db->inTransaction()) {
            $db->rollBack();
        }
        error_log('Database error in add_sale.php: ' . $e->getMessage());
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'خطا در ذخیره اطلاعات: ' . $e->getMessage()]);
        exit();
    } catch (Exception $e) {
        if ($db->inTransaction()) {
            $db->rollBack();
        }
        error_log('General error in add_sale.php: ' . $e->getMessage());
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'خطای سیستمی']);
        exit();
    }
} else {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'درخواست نامعتبر']);
    exit();
}
?>