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
    <link rel="stylesheet" href="assets/webfonts/">

    <!-- Icons -->
    <link rel="stylesheet" href="assets/css/all.min.css">
    <!-- Unified Design System -->
    <link rel="stylesheet" href="assets/css/unified-system.css">
    <!-- Modern Login Styles -->
    <link rel="stylesheet" href="assets/css/modern-login.css">
</head>

<body class="modern-login-page">
    <!-- Background Animation -->
    <div class="login-background">
        <div class="floating-shapes">
            <div class="shape shape-1"></div>
            <div class="shape shape-2"></div>
            <div class="shape shape-3"></div>
            <div class="shape shape-4"></div>
            <div class="shape shape-5"></div>
        </div>
    </div>

    <div class="login-container">
        <div class="login-card-modern">
            <!-- Left Side - Branding -->
            <div class="login-brand-section">
                <div class="brand-content">
                    <div class="brand-logo">
                        <?php if (SettingsHelper::hasCustomLogo()): ?>
                            <img src="<?= htmlspecialchars(SettingsHelper::getShopLogo(), ENT_QUOTES, 'UTF-8') ?>" alt="<?= sanitizeOutput(SettingsHelper::getShopName()) ?>">
                        <?php else: ?>
                            <div class="default-logo">
                                <i class="fas fa-motorcycle"></i>
                            </div>
                        <?php endif; ?>
                    </div>
                    <h1 class="brand-title"><?= sanitizeOutput(SettingsHelper::getShopName()) ?></h1>
                    <p class="brand-subtitle">سیستم مدیریت فروشگاه قطعات موتورسیکلت</p>
                    <div class="brand-features">
                        <div class="feature-item">
                            <i class="fas fa-shield-alt"></i>
                            <span>امنیت بالا</span>
                        </div>
                        <div class="feature-item">
                            <i class="fas fa-chart-line"></i>
                            <span>گزارشگیری پیشرفته</span>
                        </div>
                        <div class="feature-item">
                            <i class="fas fa-users"></i>
                            <span>مدیریت کاربران</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right Side - Login Form -->
            <div class="login-form-section">
                <div class="form-header">
                    <h2 class="form-title">ورود به سیستم</h2>
                    <p class="form-subtitle">لطفاً اطلاعات خود را وارد کنید</p>
                </div>

                <div class="form-content">
                    <?php if ($error): ?>
                        <div class="error-message">
                            <i class="fas fa-exclamation-circle"></i>
                            <span><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></span>
                        </div>
                    <?php endif; ?>

                    <form method="POST" class="login-form">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8') ?>">

                        <div class="input-field">
                            <div class="input-wrapper">
                                <i class="fas fa-user input-icon"></i>
                                <input type="text" name="username" id="username" class="modern-input"
                                   value="<?= htmlspecialchars($_COOKIE['remember_username'] ?? '', ENT_QUOTES, 'UTF-8') ?>"placeholder="نام کاربری" required>
                                <!-- <label class="input-label">نام کاربری</label> -->
                            </div>
                        </div>

                        <div class="input-field">
                            <div class="input-wrapper">
                                <i class="fas fa-lock input-icon"></i>
                                <input type="password" id="password" name="password" class="modern-input"
                                   placeholder="رمز عبور" required>
                                <!-- <label class="input-label">رمز عبور</label> -->
                                <i class="fas fa-eye toggle-password" id="togglePassword"></i>
                            </div>
                        </div>

                        <div class="form-options">
                            <label class="checkbox-wrapper">
                                <input type="checkbox" name="remember_me" id="remember_me" <?= isset($_COOKIE['remember_username']) ? 'checked' : '' ?>>
                                <span class="checkmark"></span>
                                <span class="checkbox-text">مرا بخاطر بسپار</span>
                            </label>
                        </div>

                        <button type="submit" class="login-btn">
                            <span class="btn-text">ورود به سیستم</span>
                            <i class="fas fa-arrow-left btn-icon"></i>
                        </button>
                    </form>
                </div>

                <div class="form-footer">
                    <p>&copy; <?= date('Y') ?> <?= sanitizeOutput(SettingsHelper::getShopName()) ?>. تمامی حقوق محفوظ است.</p>
                </div>
            </div>
        </div>
    </div>
    <script>
        // Minimal login functionality
        document.addEventListener('DOMContentLoaded', function() {
            // Password toggle
            const togglePassword = document.getElementById('togglePassword');
            const passwordInput = document.getElementById('password');
            
            if (togglePassword && passwordInput) {
                togglePassword.addEventListener('click', function() {
                    const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
                    passwordInput.setAttribute('type', type);
                    this.classList.toggle('fa-eye');
                    this.classList.toggle('fa-eye-slash');
                });
            }
            
            // Input wrapper click focus
            document.querySelectorAll('.input-wrapper').forEach(wrapper => {
                wrapper.addEventListener('click', function() {
                    const input = this.querySelector('.modern-input');
                    if (input) input.focus();
                });
            });
            
            // Label animation
            document.querySelectorAll('.modern-input').forEach(input => {
                function updateLabel() {
                    const label = input.nextElementSibling;
                    if (label && label.classList.contains('input-label')) {
                        if (input.value.trim() !== '' || input === document.activeElement) {
                            label.style.transform = 'translateY(-35px) scale(0.85)';
                            label.style.color = '#4f46e5';
                            label.style.background = 'white';
                            label.style.padding = '0 8px';
                        } else {
                            label.style.transform = 'translateY(-50%) scale(1)';
                            label.style.color = '#6b7280';
                            label.style.background = 'transparent';
                            label.style.padding = '0';
                        }
                    }
                }
                
                input.addEventListener('input', updateLabel);
                input.addEventListener('focus', updateLabel);
                input.addEventListener('blur', updateLabel);
                updateLabel();
            });
        });
    </script>
</body>

</html>