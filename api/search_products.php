<?php
require_once '../init_security.php';
require_once '../config/database.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

$database = new Database();
$db = $database->getConnection();

$query = $_GET['q'] ?? '';
$query = trim($query);

if (strlen($query) < 2) {
    echo json_encode([]);
    exit();
}

try {
    $sql = "SELECT id, name, sell_price, stock_quantity 
            FROM products 
            WHERE (name LIKE :query OR id LIKE :query) 
            AND stock_quantity > 0 
            ORDER BY 
                CASE WHEN name LIKE :exact_query THEN 1 ELSE 2 END,
                stock_quantity DESC,
                name ASC 
            LIMIT 10";
    
    $stmt = $db->prepare($sql);
    $searchTerm = '%' . $query . '%';
    $exactTerm = $query . '%';
    
    $stmt->bindParam(':query', $searchTerm);
    $stmt->bindParam(':exact_query', $exactTerm);
    $stmt->execute();
    
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Format the results
    $results = array_map(function($product) {
        return [
            'id' => (int)$product['id'],
            'name' => $product['name'],
            'sell_price' => (float)$product['sell_price'],
            'stock_quantity' => (int)$product['stock_quantity']
        ];
    }, $products);
    
    echo json_encode($results);
    
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error']);
}
?>