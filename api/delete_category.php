<?php
require_once '../init_security.php';
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'غیر مجاز']);
    exit();
}

require_once '../config/database.php';

header('Content-Type: application/json');

// CSRF protection
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf_token'])) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'درخواست نامعتبر - CSRF']);
        exit();
    }

    $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
    
    if ($id <= 0) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'شناسه دستهبندی نامعتبر']);
        exit();
    }
    
    try {
        $database = new Database();
        $db = $database->getConnection();
        
        // Check if category exists
        $check_exists = "SELECT id FROM categories WHERE id = ?";
        $exists_stmt = $db->prepare($check_exists);
        $exists_stmt->execute([$id]);
        
        if (!$exists_stmt->fetch()) {
            echo json_encode(['success' => false, 'message' => 'دستهبندی یافت نشد']);
            exit();
        }
        
        // Check if category has products
        $check_query = "SELECT COUNT(*) as count FROM products WHERE category_id = ?";
        $check_stmt = $db->prepare($check_query);
        $check_stmt->execute([$id]);
        $product_count = (int)($check_stmt->fetch(PDO::FETCH_ASSOC)['count'] ?? 0);
        
        if ($product_count > 0) {
            echo json_encode(['success' => false, 'message' => 'این دستهبندی دارای محصول است و قابل حذف نیست']);
            exit();
        }
        
        $query = "DELETE FROM categories WHERE id = ?";
        $stmt = $db->prepare($query);
        $result = $stmt->execute([$id]);
        
        if ($result && $stmt->rowCount() > 0) {
            echo json_encode(['success' => true, 'message' => 'دستهبندی با موفقیت حذف شد']);
        } else {
            echo json_encode(['success' => false, 'message' => 'خطا در حذف دستهبندی']);
        }
        
    } catch (PDOException $e) {
        error_log('delete_category error: ' . $e->getMessage());
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'خطا در پایگاه داده: ' . $e->getMessage()]);
    } catch (Exception $e) {
        error_log('delete_category general error: ' . $e->getMessage());
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'خطای سیستم: ' . $e->getMessage()]);
    }
} else {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'متد درخواست نامعتبر']);
}
?>