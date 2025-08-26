<?php
require_once '../init_security.php';
require_once '../config/database.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit();
}

$database = new Database();
$db = $database->getConnection();

try {
    $sql = "SELECT COUNT(*) as count, 
                   GROUP_CONCAT(name SEPARATOR ', ') as product_names
            FROM products 
            WHERE stock_quantity <= min_stock 
            AND stock_quantity >= 0";
    
    $stmt = $db->prepare($sql);
    $stmt->execute();
    
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'count' => (int)$result['count'],
        'products' => $result['product_names'] ? explode(', ', $result['product_names']) : []
    ]);
    
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Database error'
    ]);
}
?>