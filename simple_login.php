<?php
session_start();
require_once 'config/database.php';

$error = '';

if ($_POST) {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    
    if ($username && $password) {
        try {
            $database = new Database();
            $db = $database->getConnection();
            
            $query = "SELECT id, username, password, full_name, role FROM users WHERE username = ?";
            $stmt = $db->prepare($query);
            $stmt->execute([$username]);
            
            if ($user = $stmt->fetch(PDO::FETCH_ASSOC)) {
                if (password_verify($password, $user['password'])) {
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['username'] = $user['username'];
                    $_SESSION['full_name'] = $user['full_name'];
                    $_SESSION['role'] = $user['role'];
                    
                    header('Location: dashboard.php');
                    exit();
                }
            }
            $error = 'نام کاربری یا رمز عبور اشتباه است';
        } catch (Exception $e) {
            $error = 'خطا: ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="utf-8">
    <title>ورود ساده</title>
    <style>
        body { font-family: Tahoma; padding: 50px; }
        .form { max-width: 400px; margin: 0 auto; }
        input { width: 100%; padding: 10px; margin: 10px 0; }
        button { width: 100%; padding: 15px; background: #007bff; color: white; border: none; }
        .error { color: red; padding: 10px; background: #ffe6e6; margin: 10px 0; }
    </style>
</head>
<body>
    <div class="form">
        <h2>ورود ساده به سیستم</h2>
        
        <?php if ($error): ?>
            <div class="error"><?= $error ?></div>
        <?php endif; ?>
        
        <form method="POST">
            <input type="text" name="username" placeholder="نام کاربری" required>
            <input type="password" name="password" placeholder="رمز عبور" required>
            <button type="submit">ورود</button>
        </form>
        
        <p><strong>اطلاعات ورود:</strong></p>
        <p>نام کاربری: admin</p>
        <p>رمز عبور: password</p>
    </div>
</body>
</html>