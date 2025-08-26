<?php
// Validate and sanitize file paths to prevent directory traversal
$allowed_files = [
    'init_security.php' => __DIR__ . '/init_security.php',
    'config/database.php' => __DIR__ . '/config/database.php',
    'includes/functions.php' => __DIR__ . '/includes/functions.php',
    'includes/SettingsHelper.php' => __DIR__ . '/includes/SettingsHelper.php',
    'includes/setup_helper.php' => __DIR__ . '/includes/setup_helper.php'
];

foreach ($allowed_files as $file_path) {
    if (file_exists($file_path) && is_readable($file_path)) {
        require_once $file_path;
    }
}

try {
    $database = new Database();
    $db = $database->getConnection();
    SettingsHelper::loadSettings($db);
} catch (Exception $e) {
    error_log('Database connection failed: ' . $e->getMessage());
    $error = 'خطا در اتصال به پایگاه داده';
    $db = null;
}

$error = '';

if ($_POST) {
    // CSRF protection
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf_token'])) {
        $error = 'درخواست نامعتبر';
    } else {
        $username = $_POST['username'] ?? '';
        $password = $_POST['password'] ?? '';

        if ($username && $password && $db) {
            try {
                $query = "SELECT id, username, password, full_name, role FROM users WHERE username = ?";
                $stmt = $db->prepare($query);
                $stmt->execute([$username]);
            } catch (PDOException $e) {
                error_log('Database query failed: ' . $e->getMessage());
                $error = 'خطا در بررسی اطلاعات کاربری';
                $stmt = null;
            }

            if ($stmt && ($user = $stmt->fetch(PDO::FETCH_ASSOC))) {
                if (password_verify($password, $user['password'])) {
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['username'] = $user['username'];
                    $_SESSION['full_name'] = $user['full_name'];
                    $_SESSION['role'] = $user['role'];
                    
                    // Handle Remember Me
                    if (isset($_POST['remember_me'])) {
                        setcookie('remember_username', $username, time() + (30 * 24 * 60 * 60), '/', '', false, true);
                    } else {
                        setcookie('remember_username', '', time() - 3600, '/', '', false, true);
                    }
                    
                    // Check if this is first login (only super admin exists)
                    if ($user['role'] === 'admin' && SetupHelper::isFirstLogin($db)) {
                        header('Location: settings.php?first_login=1');
                        return;
                    }
                    
                    header('Location: dashboard.php');
                    return;
                }
            }
            // Log failed login attempt
            error_log(sprintf('[%s] Failed login attempt - Username: %s, IP: %s', 
                date('Y-m-d H:i:s'), 
                $username, 
                $_SERVER['REMOTE_ADDR'] ?? 'unknown'
            ));
            $error = 'نام کاربری یا رمز عبور اشتباه است';
        }
    }
}

// Generate CSRF token
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>ورود به سیستم - <?= sanitizeOutput(SettingsHelper::getShopName()) ?></title>

    <!-- Favicon -->
    <link rel="icon" href="assets/img/brand/favicon.png" type="image/png">

    <!-- Fonts -->
    <!-- <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Vazirmatn:wght@300;400;500;600;700&display=swap" rel="stylesheet"> -->
    <link rel="stylesheet" href="assets/webfonts/">

    <!-- Icons -->
    <link rel="stylesheet" href="assets/css/all.min.css">
    <!-- Bootstrap RTL CSS -->
    <link rel="stylesheet" href="assets/css/bootstrap.rtl.min.css">
    <!-- Login Styles -->
    <link rel="stylesheet" href="assets/css/login.css">
</head>

<body>
    <div class="login-container">
        <div class="login-card">
            <!-- Header -->
            <div class="login-header">
                <div class="login-logo">
                    <?php if (SettingsHelper::hasCustomLogo()): ?>
                        <img src="<?= htmlspecialchars(SettingsHelper::getShopLogo(), ENT_QUOTES, 'UTF-8') ?>" alt="<?= sanitizeOutput(SettingsHelper::getShopName()) ?>" 
                             style="width: 60px; height: 60px; object-fit: contain; border-radius: 50%;">
                    <?php else: ?>
                        <i class="fas fa-motorcycle"></i>
                    <?php endif; ?>
                </div>
                <h1 class="login-title">خوش آمدید</h1>
                <p class="login-subtitle"><?= sanitizeOutput(SettingsHelper::getShopName()) ?></p>
            </div>

            <!-- Body -->
            <div class="login-body">
                <?php if ($error): ?>
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?>
                    </div>
                <?php endif; ?>

                <form method="POST">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8') ?>">

                    <div class="form-group">
                        <label class="form-label">نام کاربری</label>
                        <div class="input-group">
                            <input type="text" name="username" id="username" class="form-control with-icon"
                                placeholder="نام کاربری خود را وارد کنید" value="<?= htmlspecialchars($_COOKIE['remember_username'] ?? '', ENT_QUOTES, 'UTF-8') ?>" required>
                            <i class="fas fa-user input-icon"></i>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label">رمز عبور</label>
                        <div class="input-group">
                            <input type="password" id="password" name="password" class="form-control with-icons"
                                placeholder="رمز عبور خود را وارد کنید" required>
                            <i class="fas fa-lock input-icon-left"></i>
                            <i class="fas fa-eye input-icon-right" id="togglePassword" style="cursor: pointer;"></i>
                        </div>
                    </div>

                    <div class="form-group">
                        <div class="form-check">
                            <input type="checkbox" name="remember_me" id="remember_me" class="form-check-input" <?= isset($_COOKIE['remember_username']) ? 'checked' : '' ?>>
                            <label class="form-check-label" for="remember_me">
                                <i class="fas fa-heart me-1"></i>
                                بخاطر بسپار من را
                            </label>
                        </div>
                    </div>

                    <button type="submit" class="btn-login">
                        <i class="fas fa-sign-in-alt me-2"></i>
                        ورود به سیستم
                    </button>
                </form>
            </div>

            <!-- Footer -->
            <div class="login-footer">
                <p class="text-muted mb-0">
                    &copy; <?= date('Y') ?> <?= sanitizeOutput(SettingsHelper::getShopName()) ?>. تمامی حقوق محفوظ است.
                </p>
            </div>
        </div>
    </div>
    <script>
        document.getElementById('togglePassword').addEventListener('click', function() {
            const password = document.getElementById('password');
            const type = password.getAttribute('type') === 'password' ? 'text' : 'password';
            password.setAttribute('type', type);
            this.classList.toggle('fa-eye');
            this.classList.toggle('fa-eye-slash');
        });
    </script>
</body>

</html>