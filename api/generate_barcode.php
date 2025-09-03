<?php
// Secure file inclusion with path validation
$allowed_files = [
    '../init_security.php' => realpath(__DIR__ . '/../init_security.php'),
    '../config/database.php' => realpath(__DIR__ . '/../config/database.php'),
    '../includes/BarcodeGenerator.php' => realpath(__DIR__ . '/../includes/BarcodeGenerator.php')
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
    exit;
}

$product_id = filter_input(INPUT_POST, 'product_id', FILTER_VALIDATE_INT);
$barcode_type = in_array($_POST['barcode_type'] ?? 'CODE128', ['CODE128', 'EAN13', 'QR']) ? $_POST['barcode_type'] : 'CODE128';

if (!$product_id) {
    echo json_encode(['success' => false, 'message' => 'شناسه محصول الزامی است']);
    exit;
}

try {
    $database = new Database();
    $db = $database->getConnection();
    
    // Get product info
    $product_query = "SELECT * FROM products WHERE id = ?";
    $product_stmt = $db->prepare($product_query);
    $product_stmt->execute([$product_id]);
    $product = $product_stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$product) {
        echo json_encode(['success' => false, 'message' => 'محصول یافت نشد']);
        exit;
    }
    
    // Generate new barcode if needed
    $new_barcode = 'MP' . str_pad($product_id, 8, '0', STR_PAD_LEFT) . substr(time(), -4);
    
    $db->beginTransaction();
    
    // Update or insert barcode
    $barcode_check = "SELECT id FROM product_barcodes WHERE product_id = ? AND is_primary = 1";
    $check_stmt = $db->prepare($barcode_check);
    $check_stmt->execute([$product_id]);
    
    if ($check_stmt->fetch()) {
        $update_query = "UPDATE product_barcodes SET barcode = ?, barcode_type = ? WHERE product_id = ? AND is_primary = 1";
        $update_stmt = $db->prepare($update_query);
        $update_stmt->execute([$new_barcode, $barcode_type, $product_id]);
    } else {
        $insert_query = "INSERT INTO product_barcodes (product_id, barcode, barcode_type, is_primary) VALUES (?, ?, ?, 1)";
        $insert_stmt = $db->prepare($insert_query);
        $insert_stmt->execute([$product_id, $new_barcode, $barcode_type]);
    }
    
    // Update product table
    $product_update = "UPDATE products SET barcode = ? WHERE id = ?";
    $product_stmt = $db->prepare($product_update);
    $product_stmt->execute([$new_barcode, $product_id]);
    
    $db->commit();
    
    // Generate barcode image
    $barcode_svg = BarcodeGenerator::generateSVG($new_barcode, 2, 50);
    $barcode_base64 = BarcodeGenerator::generateBase64($new_barcode, 2, 50);
    
    echo json_encode([
        'success' => true,
        'message' => 'بارکد جدید تولید شد',
        'barcode' => $new_barcode,
        'barcode_type' => $barcode_type,
        'barcode_svg' => $barcode_svg,
        'barcode_base64' => $barcode_base64
    ]);
    
} catch (Exception $e) {
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    error_log('Barcode generation error: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'خطا در تولید بارکد']);
}
?>