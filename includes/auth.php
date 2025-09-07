<?php
require_once __DIR__ . '/../init_security.php';
require_once '../config/database.php';

function isLoggedIn()
{
    return isset($_SESSION['user_id']);
}

function requireLogin()
{
    if (!isLoggedIn()) {
        header('Location: ../login.php');
        exit();
    }
}

function hasRole($role)
{
    return isset($_SESSION['role']) && $_SESSION['role'] === $role;
}

function requireRole($role)
{
    requireLogin();
    if (!hasRole($role)) {
        header('Location: ../dashboard.php');
        exit();
    }
}

function login($username, $password)
{
    $db = new Database();
    $conn = $db->getConnection();

    $stmt = $conn->prepare("SELECT id, username, password, full_name, role FROM users WHERE username = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user && password_verify($password, $user['password'])) {
        session_regenerate_id(true);
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['full_name'] = $user['full_name'];
        $_SESSION['role'] = $user['role'];
        return true;
    }
    return false;
}

function logout()
{
    $_SESSION = array();
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(
            session_name(),
            '',
            time() - 42000,
            $params["path"],
            $params["domain"],
            $params["secure"],
            $params["httponly"]
        );
    }
    session_destroy();
    header('Location: ../login.php');
    exit();
}

function generateCSRFToken()
{
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verifyCSRFToken($token)
{
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

function sanitizeOutput($data)
{
    return htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
}
?>