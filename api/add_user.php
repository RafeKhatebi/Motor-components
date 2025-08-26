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
    PermissionManager::requirePermission('users.create');
} catch (Exception $e) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'دسترسی غیر مجاز']);
    exit();
}

require_once '../config/database.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $database = new Database();
    $db = $database->getConnection();

    $username = trim($_POST['username'] ?? '');
    $full_name = trim($_POST['full_name'] ?? '');
    $password = $_POST['password'] ?? '';
    $role = $_POST['role'] ?? 'employee';

    // Validate role
    $allowed_roles = ['admin', 'manager', 'employee'];
    if (!in_array($role, $allowed_roles)) {
        $role = 'employee';
    }

    if (empty($username) || empty($full_name) || empty($password)) {
        echo json_encode(['success' => false, 'message' => 'تمام فیلدها الزامی است']);
        exit();
    }

    try {
        $check_query = "SELECT id FROM users WHERE username = ?";
        $check_stmt = $db->prepare($check_query);
        $check_stmt->execute([$username]);

        if ($check_stmt->fetch()) {
            echo json_encode(['success' => false, 'message' => 'نام کاربری تکراری است']);
            exit();
        }

        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

        $query = "INSERT INTO users (username, full_name, password, role) VALUES (?, ?, ?, ?)";
        $stmt = $db->prepare($query);
        $result = $stmt->execute([$username, $full_name, $hashed_password, $role]);

        if ($result && $stmt->rowCount() > 0) {
            echo json_encode(['success' => true, 'message' => 'کاربر با موفقیت اضافه شد']);
        } else {
            echo json_encode(['success' => false, 'message' => 'خطا در ذخیره اطلاعات']);
        }

    } catch (PDOException $e) {
        error_log('User add error: ' . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'خطا در ذخیره اطلاعات: ' . $e->getMessage()]);
        exit();
    } catch (Exception $e) {
        error_log('General error: ' . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'خطای عمومی: ' . $e->getMessage()]);
        exit();
    }
} else {
    echo json_encode(['success' => false, 'message' => 'درخواست نامعتبر']);
}
?>