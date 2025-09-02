<?php
require_once '../init_security.php';
require_once '../config/database.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'غیر مجاز']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'درخواست نامعتبر']);
    exit();
}

$sale_item_id = filter_input(INPUT_POST, 'sale_item_id', FILTER_VALIDATE_INT);
$warranty_months = filter_input(INPUT_POST, 'warranty_months', FILTER_VALIDATE_INT);
$serial_number = trim($_POST['serial_number'] ?? '');
$warranty_type = in_array($_POST['warranty_type'] ?? 'shop', ['manufacturer', 'shop', 'extended']) ? $_POST['warranty_type'] : 'shop';

if (!$sale_item_id || !$warranty_months) {
    echo json_encode(['success' => false, 'message' => 'اطلاعات ناکافی']);
    exit();
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
        echo json_encode(['success' => false, 'message' => 'آیتم فروش یافت نشد']);
        exit();
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