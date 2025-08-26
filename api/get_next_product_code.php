<?php
require_once '../init_security.php';
require_once '../config/database.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'غیر مجاز']);
    exit();
}

try {
    $database = new Database();
    $db = $database->getConnection();
    
    // شروع transaction برای جلوگیری از تداخل
    $db->beginTransaction();

    // دریافت آخرین کد محصول با قفل کردن جدول
    $query = "SELECT code FROM products WHERE code REGEXP '^[0-9]+$' ORDER BY CAST(code AS UNSIGNED) DESC LIMIT 1 FOR UPDATE";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($result) {
        $lastCode = intval($result['code']);
        $nextCode = $lastCode + 1;
    } else {
        $nextCode = 1;
    }
    
    // بررسی تکراری نبودن کد جدید
    do {
        $formattedCode = str_pad($nextCode, 4, '0', STR_PAD_LEFT);
        $check_query = "SELECT id FROM products WHERE code = :code";
        $check_stmt = $db->prepare($check_query);
        $check_stmt->bindParam(':code', $formattedCode);
        $check_stmt->execute();
        
        if ($check_stmt->fetch()) {
            $nextCode++; // اگر کد تکراری بود، یکی اضافه کن
        } else {
            break; // کد منحصر به فرد پیدا شد
        }
    } while ($nextCode < 9999); // حداکثر 9999 کد
    
    if ($nextCode >= 9999) {
        throw new Exception('حداکثر تعداد کد محصول به پایان رسیده');
    }

    $db->commit();
    echo json_encode(['success' => true, 'code' => $formattedCode]);

} catch (Exception $e) {
    if (isset($db) && $db->inTransaction()) {
        $db->rollback();
    }
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>