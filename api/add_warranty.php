<?php
// Secure file inclusion with path validation
$allowed_files = [
    '../init_security.php' => realpath(__DIR__ . '/../init_security.php'),
    '../config/database.php' => realpath(__DIR__ . '/../config/database.php')
];

foreach ($allowed_files as $file => $real_path) {
    if ($real_path && file_exists($real_path)) {
        require_once $real_path;
    } else {
        http_response_code(500);
        exit('Security error: Invalid file path');
    }
}

header('Content-Type: application/json');

function sendResponse($data, $code = 200) {
    http_response_code($code);
    echo json_encode($data);
    return;
}

if (!isset($_SESSION['user_id'])) {
    return sendResponse(['success' => false, 'message' => 'غیر مجاز'], 401);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    return sendResponse(['success' => false, 'message' => 'درخواست نامعتبر'], 405);
}

$sale_item_id = filter_input(INPUT_POST, 'sale_item_id', FILTER_VALIDATE_INT);
$warranty_months = filter_input(INPUT_POST, 'warranty_months', FILTER_VALIDATE_INT);
$serial_number = trim($_POST['serial_number'] ?? '');
$warranty_type = in_array($_POST['warranty_type'] ?? 'shop', ['manufacturer', 'shop', 'extended']) ? $_POST['warranty_type'] : 'shop';

if (!$sale_item_id || !$warranty_months) {
    return sendResponse(['success' => false, 'message' => 'اطلاعات ناکافی'], 400);
}

try {
    $database = new Database();
    $db = $database->getConnection();
    
    // دریافت اطلاعات فروش
    $sale_query = "SELECT si.*, s.customer_id, s.created_at as sale_date 
                   FROM sale_items si 
                   JOIN sales s ON si.sale_id = s.id 
                   WHERE si.id = ?";
    $sale_stmt = $db->prepare($sale_query);
    $sale_stmt->execute([$sale_item_id]);
    $sale_item = $sale_stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$sale_item) {
        return sendResponse(['success' => false, 'message' => 'آیتم فروش یافت نشد'], 404);
    }
    
    $warranty_start = date('Y-m-d', strtotime($sale_item['sale_date']));
    $warranty_end = date('Y-m-d', strtotime($warranty_start . " +{$warranty_months} months"));
    
    $db->beginTransaction();
    
    // ثبت گارانتی
    $warranty_query = "INSERT INTO warranties (sale_item_id, product_id, customer_id, warranty_start, warranty_end, warranty_months, serial_number, warranty_type) 
                       VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
    $warranty_stmt = $db->prepare($warranty_query);
    $warranty_stmt->execute([
        $sale_item_id,
        $sale_item['product_id'],
        $sale_item['customer_id'],
        $warranty_start,
        $warranty_end,
        $warranty_months,
        $serial_number,
        $warranty_type
    ]);
    
    $warranty_id = $db->lastInsertId();
    
    // ثبت تاریخچه
    $history_query = "INSERT INTO warranty_history (warranty_id, action, description, performed_by) 
                      VALUES (?, 'created', ?, ?)";
    $history_stmt = $db->prepare($history_query);
    $history_stmt->execute([
        $warranty_id,
        "گارانتی {$warranty_months} ماهه ثبت شد",
        $_SESSION['user_id']
    ]);
    
    $db->commit();
    
    echo json_encode([
        'success' => true,
        'message' => 'گارانتی با موفقیت ثبت شد',
        'warranty_id' => $warranty_id,
        'warranty_end' => $warranty_end
    ]);
    
} catch (Exception $e) {
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    error_log('Warranty creation error: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'خطا در ثبت گارانتی']);
}
?>