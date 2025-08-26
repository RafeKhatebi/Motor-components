<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'غیر مجاز']);
    exit();
}

require_once '../config/database.php';

// Security headers
header('X-Frame-Options: DENY');
header('X-Content-Type-Options: nosniff');
header('X-XSS-Protection: 1; mode=block');
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['id'] ?? 0;
    $username = $_POST['username'] ?? '';
    $full_name = $_POST['full_name'] ?? '';
    $role = $_POST['role'] ?? '';
    $password = $_POST['password'] ?? null;
    
    if (!$id || !$username || !$full_name || !$role) {
        echo json_encode(['success' => false, 'message' => 'لطفاً تمام فیلدهای الزامی را پر کنید']);
        exit();
    }
    
    $database = new Database();
    $db = $database->getConnection();
    
    try {
        // Check if username exists for other users
        $check_query = "SELECT id FROM users WHERE username = ? AND id != ?";
        $check_stmt = $db->prepare($check_query);
        $check_stmt->execute([$username, $id]);
        
        if ($check_stmt->fetch()) {
            echo json_encode(['success' => false, 'message' => 'این نام کاربری قبلاً استفاده شده است']);
            exit();
        }
        
        if ($password) {
            // Update with new password
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $query = "UPDATE users SET username = ?, full_name = ?, role = ?, password = ? WHERE id = ?";
            $stmt = $db->prepare($query);
            $result = $stmt->execute([$username, $full_name, $role, $hashed_password, $id]);
        } else {
            // Update without changing password
            $query = "UPDATE users SET username = ?, full_name = ?, role = ? WHERE id = ?";
            $stmt = $db->prepare($query);
            $result = $stmt->execute([$username, $full_name, $role, $id]);
        }
        
        if ($result) {
            echo json_encode(['success' => true, 'message' => 'کاربر با موفقیت بروزرسانی شد']);
        } else {
            echo json_encode(['success' => false, 'message' => 'خطا در بروزرسانی کاربر']);
        }
        
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'خطا در بروزرسانی کاربر']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'درخواست نامعتبر']);
}
?>