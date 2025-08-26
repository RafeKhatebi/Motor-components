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
    // Check for credit sales that are overdue (more than 30 days old)
    $sql = "SELECT COUNT(*) as count
            FROM sales 
            WHERE payment_type = 'credit' 
            AND payment_status != 'paid'
            AND created_at < DATE_SUB(NOW(), INTERVAL 30 DAY)
            AND (status IS NULL OR status != 'returned')";
    
    $stmt = $db->prepare($sql);
    $stmt->execute();
    
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'count' => (int)$result['count']
    ]);
    
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Database error'
    ]);
}
?>