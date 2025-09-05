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
    exit;
}

$warranty_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$warranty_id) {
    echo json_encode(['success' => false, 'message' => 'شناسه گارانتی الزامی است']);
    exit;
}

try {
    $database = new Database();
    $db = $database->getConnection();
    
    $warranty_query = "SELECT w.*, p.name as product_name, p.code as product_code, 
                       c.name as customer_name, c.phone as customer_phone,
                       si.quantity, si.unit_price, s.created_at as sale_date
                       FROM warranties w
                       LEFT JOIN products p ON w.product_id = p.id
                       LEFT JOIN customers c ON w.customer_id = c.id
                       LEFT JOIN sale_items si ON w.sale_item_id = si.id
                       LEFT JOIN sales s ON si.sale_id = s.id
                       WHERE w.id = ?";
    
    $warranty_stmt = $db->prepare($warranty_query);
    $warranty_stmt->execute([$warranty_id]);
    $warranty = $warranty_stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$warranty) {
        echo json_encode(['success' => false, 'message' => 'گارانتی یافت نشد']);
        exit;
    }
    
    // Get warranty claims if any
    $claims_query = "SELECT * FROM warranty_claims WHERE warranty_id = ? ORDER BY claim_date DESC";
    $claims_stmt = $db->prepare($claims_query);
    $claims_stmt->execute([$warranty_id]);
    $claims = $claims_stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'warranty' => [
            'id' => (int)$warranty['id'],
            'product_name' => $warranty['product_name'],
            'product_code' => $warranty['product_code'],
            'customer_name' => $warranty['customer_name'],
            'customer_phone' => $warranty['customer_phone'],
            'warranty_start' => $warranty['warranty_start'],
            'warranty_end' => $warranty['warranty_end'],
            'warranty_months' => (int)$warranty['warranty_months'],
            'warranty_type' => $warranty['warranty_type'],
            'status' => $warranty['status'],
            'notes' => $warranty['notes'],
            'sale_date' => $warranty['sale_date'],
            'quantity' => (int)$warranty['quantity'],
            'unit_price' => (float)$warranty['unit_price']
        ],
        'claims' => $claims
    ]);
    
} catch (Exception $e) {
    error_log('Get warranty error: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'خطا در دریافت اطلاعات گارانتی']);
}
?>