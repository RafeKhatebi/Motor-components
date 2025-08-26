<?php
require_once '../init_security.php';
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'غیر مجاز']);
    return;
}

require_once '../config/database.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $database = new Database();
    $db = $database->getConnection();
    
    $name = $_POST['name'] ?? '';
    $description = $_POST['description'] ?? '';
    
    if (empty($name)) {
        echo json_encode(['success' => false, 'message' => 'نام دستهبندی الزامی است']);
        return;
    }
    
    try {
        $query = "INSERT INTO categories (name, description) VALUES (?, ?)";
        $stmt = $db->prepare($query);
        $stmt->execute([$name, $description]);
        
        echo json_encode(['success' => true, 'message' => 'دستهبندی با موفقیت اضافه شد']);
        
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'خطا در ذخیره اطلاعات']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'درخواست نامعتبر']);
}
?>