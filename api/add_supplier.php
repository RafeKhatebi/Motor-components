<?php
require_once '../init_security.php';
require_once '../includes/permissions.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'غیر مجاز']);
    exit();
}

try {
    PermissionManager::requirePermission('suppliers.create');
} catch (Exception $e) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'دسترسی غیر مجاز']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'درخواست نامعتبر']);
    exit();
}

require_once '../config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    
    $name = trim($_POST['name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $address = trim($_POST['address'] ?? '');
    
    if (empty($name)) {
        echo json_encode(['success' => false, 'message' => 'نام تأمینکننده الزامی است']);
        exit();
    }
    
    $query = "INSERT INTO suppliers (name, phone, address) VALUES (?, ?, ?)";
    $stmt = $db->prepare($query);
    $result = $stmt->execute([$name, $phone, $address]);
    
    if ($result && $stmt->rowCount() > 0) {
        echo json_encode(['success' => true, 'message' => 'تأمینکننده با موفقیت اضافه شد']);
    } else {
        echo json_encode(['success' => false, 'message' => 'خطا در ذخیره اطلاعات']);
    }
    
} catch (PDOException $e) {
    error_log('Supplier add error: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'خطا در ذخیره اطلاعات: ' . $e->getMessage()]);
} catch (Exception $e) {
    error_log('General error: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'خطای عمومی: ' . $e->getMessage()]);
}
?>