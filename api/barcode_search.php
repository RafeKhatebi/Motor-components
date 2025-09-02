<?php
require_once '../init_security.php';
require_once '../config/database.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'غیر مجاز']);
    exit();
}

$barcode = trim($_GET['barcode'] ?? '');
$scan_type = in_array($_GET['type'] ?? 'search', ['sale', 'inventory', 'search']) ? $_GET['type'] : 'search';

if (!$barcode) {
    echo json_encode(['success' => false, 'message' => 'بارکد الزامی است']);
    exit();
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
        exit();
    }
    
    // ثبت تاریخچه اسکن
    $scan_query = "INSERT INTO barcode_scans (barcode, product_id, scan_type, scanned_by) VALUES (?, ?, ?, ?)";
    $scan_stmt = $db->prepare($scan_query);
    $scan_stmt->execute([$barcode, $product['id'], $scan_type, $_SESSION['user_id']]);
    
    echo json_encode([
        'success' => true,
        'product' => [
            'id' => $product['id'],
            'name' => $product['name'],
            'code' => $product['code'],
            'barcode' => $barcode,
            'sell_price' => $product['sell_price'],
            'stock_quantity' => $product['stock_quantity'],
            'category_name' => $product['category_name'],
            'brand' => $product['brand'],
            'motor_model' => $product['motor_model']
        ]
    ]);
    
} catch (Exception $e) {
    error_log('Barcode search error: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'خطا در جستجو']);
}
?>