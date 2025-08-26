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
        echo json_encode(['success' => false, 'message' => 'شناسه مشتری نامعتبر']);
        exit();
    }
    
    $database = new Database();
    $db = $database->getConnection();
    
    try {
        // Check if customer has sales
        $check_query = "SELECT COUNT(*) as count FROM sales WHERE customer_id = ?";
        $check_stmt = $db->prepare($check_query);
        $check_stmt->execute([$id]);
        $sales_count = $check_stmt->fetch(PDO::FETCH_ASSOC)['count'];
        
        if ($sales_count > 0) {
            http_response_code(409);
            echo json_encode(['success' => false, 'message' => 'این مشتری دارای فاکتور فروش است و قابل حذف نیست']);
            exit();
        }
        
        $query = "DELETE FROM customers WHERE id = ?";
        $stmt = $db->prepare($query);
        $stmt->execute([$id]);
        
        if ($stmt->rowCount() > 0) {
            echo json_encode(['success' => true, 'message' => 'مشتری با موفقیت حذف شد']);
        } else {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'مشتری یافت نشد']);
        }
        exit();
        
    } catch (PDOException $e) {
        error_log('Database error in delete_customer.php: ' . $e->getMessage());
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'خطا در حذف مشتری']);
        exit();
    } catch (Exception $e) {
        error_log('General error in delete_customer.php: ' . $e->getMessage());
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