<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'غیر مجاز']);
    exit();
}

require_once '../config/database.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['id'] ?? 0;
    
    if (!$id) {
        echo json_encode(['success' => false, 'message' => 'شناسه محصول نامعتبر']);
        exit();
    }
    
    $database = new Database();
    $db = $database->getConnection();
    
    try {
        // Check if product is used in sales or purchases
        $check_query = "SELECT 
            (SELECT COUNT(*) FROM sale_items WHERE product_id = ?) as sale_count,
            (SELECT COUNT(*) FROM purchase_items WHERE product_id = ?) as purchase_count";
        $check_stmt = $db->prepare($check_query);
        $check_stmt->execute([$id, $id]);
        $counts = $check_stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($counts['sale_count'] > 0 || $counts['purchase_count'] > 0) {
            echo json_encode(['success' => false, 'message' => 'این محصول در فاکتورها استفاده شده و قابل حذف نیست']);
            exit();
        }
        
        $query = "DELETE FROM products WHERE id = ?";
        $stmt = $db->prepare($query);
        $stmt->execute([$id]);
        
        echo json_encode(['success' => true, 'message' => 'محصول با موفقیت حذف شد']);
        
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'خطا در حذف محصول']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'درخواست نامعتبر']);
}
?>