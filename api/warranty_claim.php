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

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'غیر مجاز']);
    return;
}

$warranty_id = filter_input(INPUT_POST, 'warranty_id', FILTER_VALIDATE_INT);
$claim_type = in_array($_POST['claim_type'] ?? '', ['repair', 'replace', 'refund']) ? $_POST['claim_type'] : null;
$issue_description = trim($_POST['issue_description'] ?? '');

if (!$warranty_id || !$claim_type || !$issue_description) {
    echo json_encode(['success' => false, 'message' => 'اطلاعات ناکافی']);
    return;
}

try {
    $database = new Database();
    $db = $database->getConnection();
    
    // بررسی وضعیت گارانتی
    $warranty_check = "SELECT * FROM warranties WHERE id = ? AND status = 'active' AND warranty_end >= CURDATE()";
    $warranty_stmt = $db->prepare($warranty_check);
    $warranty_stmt->execute([$warranty_id]);
    $warranty = $warranty_stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$warranty) {
        echo json_encode(['success' => false, 'message' => 'گارانتی معتبر نیست یا منقضی شده']);
        return;
    }
    
    $db->beginTransaction();
    
    // ثبت درخواست گارانتی
    $claim_query = "INSERT INTO warranty_claims (warranty_id, claim_date, issue_description, claim_type) 
                    VALUES (?, CURDATE(), ?, ?)";
    $claim_stmt = $db->prepare($claim_query);
    $claim_stmt->execute([$warranty_id, $issue_description, $claim_type]);
    
    $claim_id = $db->lastInsertId();
    
    // ثبت تاریخچه
    $history_query = "INSERT INTO warranty_history (warranty_id, action, description, performed_by) 
                      VALUES (?, 'claimed', ?, ?)";
    $history_stmt = $db->prepare($history_query);
    $history_stmt->execute([
        $warranty_id,
        "درخواست گارانتی ثبت شد - نوع: {$claim_type}",
        $_SESSION['user_id']
    ]);
    
    $db->commit();
    
    echo json_encode([
        'success' => true,
        'message' => 'درخواست گارانتی با موفقیت ثبت شد',
        'claim_id' => $claim_id
    ]);
    
} catch (Exception $e) {
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    error_log('Warranty claim error: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'خطا در ثبت درخواست']);
}
?>