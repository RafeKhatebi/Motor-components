<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'غیر مجاز']);
    exit();
}

require_once '../config/database.php';
require_once '../includes/functions.php';

header('Content-Type: application/json');

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    echo json_encode(['success' => false, 'message' => 'شناسه نامعتبر']);
    exit();
}

$database = new Database();
$db = $database->getConnection();

$query = "SELECT * FROM suppliers WHERE id = ?";
$stmt = $db->prepare($query);
$stmt->execute([$_GET['id']]);
$supplier = $stmt->fetch(PDO::FETCH_ASSOC);

if ($supplier) {
    echo json_encode(['success' => true, 'data' => $supplier]);
} else {
    echo json_encode(['success' => false, 'message' => 'تأمینکننده یافت نشد']);
}
?>