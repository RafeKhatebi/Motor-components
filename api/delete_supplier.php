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
        echo json_encode(['success' => false, 'message' => 'شناسه تأمینکننده نامعتبر']);
        exit();
    }
    
    $database = new Database();
    $db = $database->getConnection();
    
    try {
        // First check if supplier exists
        $exists_query = "SELECT COUNT(*) as count FROM suppliers WHERE id = ?";
        $exists_stmt = $db->prepare($exists_query);
        $exists_stmt->execute([$id]);
        $supplier_exists = $exists_stmt->fetch(PDO::FETCH_ASSOC)['count'];
        
        if ($supplier_exists == 0) {
            echo json_encode(['success' => false, 'message' => 'تأمینکننده یافت نشد']);
            exit();
        }
        
        // Check if supplier has purchases
        $check_query = "SELECT COUNT(*) as count FROM purchases WHERE supplier_id = ?";
        $check_stmt = $db->prepare($check_query);
        $check_stmt->execute([$id]);
        $purchases_count = $check_stmt->fetch(PDO::FETCH_ASSOC)['count'];
        
        if ($purchases_count > 0) {
            echo json_encode(['success' => false, 'message' => 'این تأمینکننده دارای فاکتور خرید است و قابل حذف نیست']);
            exit();
        }
        
        $query = "DELETE FROM suppliers WHERE id = ?";
        $stmt = $db->prepare($query);
        $stmt->execute([$id]);
        
        echo json_encode(['success' => true, 'message' => 'تأمینکننده با موفقیت حذف شد']);
        
    } catch (PDOException $e) {
        error_log("Delete supplier error: " . $e->getMessage());
        
        // Check if it's a foreign key constraint error
        if ($e->getCode() == '23000') {
            echo json_encode(['success' => false, 'message' => 'این تأمینکننده دارای سوابق خرید است و قابل حذف نیست']);
        } else {
            echo json_encode(['success' => false, 'message' => 'خطا در حذف تأمینکننده: ' . $e->getMessage()]);
        }
    }
} else {
    echo json_encode(['success' => false, 'message' => 'درخواست نامعتبر']);
}
?>