<?php
// Secure file inclusion with path validation
$allowed_files = [
    '../init_security.php' => realpath(__DIR__ . '/../init_security.php'),
    '../config/database.php' => realpath(__DIR__ . '/../config/database.php')
];

foreach ($allowed_files as $file => $real_path) {
    if ($real_path && file_exists($real_path)) {
        require_once $real_path;
    } else {
        http_response_code(500);
        exit('Security error: Invalid file path');
    }
}

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'غیر مجاز']);
    return;
}

$barcode = trim($_GET['barcode'] ?? '');
$scan_type = in_array($_GET['type'] ?? 'search', ['sale', 'inventory', 'search']) ? $_GET['type'] : 'search';

if (!$barcode) {
    echo json_encode(['success' => false, 'message' => 'بارکد الزامی است']);
    return;
}

try {
    $database = new Database();
    $db = $database->getConnection();
    
    // جستجوی محصول بر اساس بارکد
    $product_query = "SELECT p.*, c.name as category_name, pb.barcode_type
                      FROM products p
                      LEFT JOIN categories c ON p.category_id = c.id
                      LEFT JOIN product_barcodes pb ON p.id = pb.product_id
                      WHERE p.barcode = ? OR pb.barcode = ?
                      LIMIT 1";
    $product_stmt = $db->prepare($product_query);
    $product_stmt->execute([$barcode, $barcode]);
    $product = $product_stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$product) {
        echo json_encode(['success' => false, 'message' => 'محصول یافت نشد']);
        return;
    }
    
    // ثبت تاریخچه اسکن
    $scan_query = "INSERT INTO barcode_scans (barcode, product_id, scan_type, scanned_by) VALUES (?, ?, ?, ?)";
    $scan_stmt = $db->prepare($scan_query);
    if (!$scan_stmt->execute([$barcode, $product['id'], $scan_type, $_SESSION['user_id']])) {
        error_log('Failed to log barcode scan: ' . implode(', ', $scan_stmt->errorInfo()));
    }
    
    echo json_encode([
        'success' => true,
        'product' => [
            'id' => (int)$product['id'],
            'name' => htmlspecialchars($product['name'], ENT_QUOTES, 'UTF-8'),
            'code' => htmlspecialchars($product['code'], ENT_QUOTES, 'UTF-8'),
            'barcode' => htmlspecialchars($barcode, ENT_QUOTES, 'UTF-8'),
            'sell_price' => (float)$product['sell_price'],
            'stock_quantity' => (int)$product['stock_quantity'],
            'category_name' => htmlspecialchars($product['category_name'], ENT_QUOTES, 'UTF-8'),
            'brand' => htmlspecialchars($product['brand'], ENT_QUOTES, 'UTF-8'),
            'motor_model' => htmlspecialchars($product['motor_model'], ENT_QUOTES, 'UTF-8')
        ]
    ]);
    
} catch (Exception $e) {
    error_log('Barcode search error: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'خطا در جستجو']);
}
?>