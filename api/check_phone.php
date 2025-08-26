<?php
require_once '../init_security.php';
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'غیر مجاز']);
    exit();
}

require_once '../config/database.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $phone = trim($_POST['phone'] ?? '');
    $table = trim($_POST['table'] ?? '');
    $exclude_id = filter_input(INPUT_POST, 'exclude_id', FILTER_VALIDATE_INT);
    
    if (empty($phone) || !in_array($table, ['customers', 'suppliers'])) {
        echo json_encode(['success' => false, 'message' => 'ورودی نامعتبر']);
        exit();
    }
    
    try {
        $database = new Database();
        $db = $database->getConnection();
        
        $query = "SELECT id FROM $table WHERE phone = ?";
        $params = [$phone];
        
        if ($exclude_id) {
            $query .= " AND id != ?";
            $params[] = $exclude_id;
        }
        
        $stmt = $db->prepare($query);
        $stmt->execute($params);
        
        $exists = $stmt->fetch() ? true : false;
        
        echo json_encode([
            'success' => true, 
            'exists' => $exists,
            'message' => $exists ? 'شماره تلفن قبلاً ثبت شده است' : 'شماره تلفن قابل استفاده است'
        ]);
        
    } catch (Exception $e) {
        error_log('Phone check error: ' . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'خطا در بررسی شماره تلفن']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'درخواست نامعتبر']);
}
?>