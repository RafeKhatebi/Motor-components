<?php
/**
 * ورود سوپر ادمین جداگانه
 */
require_once 'init_security.php';
require_once 'config/database.php';
require_once 'includes/LicenseManager.php';
require_once 'includes/SuperAdminManager.php';

$database = new Database();
$db = $database->getConnection();
$superAdminManager = new SuperAdminManager($db);
$licenseManager = new LicenseManager($db, $superAdminManager);

$error = '';
$hardwareId = $licenseManager->generateHardwareID();

// بررسی وجود سوپر ادمین
if (!$superAdminManager->superAdminExists()) {
    $result = $superAdminManager->createInitialSuperAdmin($hardwareId);
    if ($result['success']) {
        $initialCredentials = $result;
    }
}

if ($_POST) {
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf_token'])) {
        $error = 'درخواست نامعتبر';
    } else {
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';
        
        if ($username && $password) {
            $result = $superAdminManager->authenticate($username, $password, $hardwareId);
            
            if ($result['success']) {
                $superAdminManager->createSuperAdminSession($result['admin']);
                header('Location: super_admin_panel.php');
                exit();
            } else {
                $error = $result['message'];
            }
        } else {
            $error = 'لطفاً تمام فیلدها را پر کنید';
        }
    }
}

// تولید CSRF token
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="utf-8">
    <title>ورود سوپر ادمین</title>
    <link rel="stylesheet" href="assets/css/bootstrap.rtl.min.css">
    <style>
        body {
            font-family: Tahoma;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .super-admin-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.2);
            overflow: hidden;
            max-width: 450px;
            width: 100%;
        }
        .super-admin-header {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            padding: 30px;
            text-align: center;
        }
        .super-admin-icon {
            width: 80px;
            height: 80px;
            background: rgba(255, 255, 255, 0.2);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
            font-size: 2rem;
        }
        .super-admin-body {
            padding: 30px;
        }
        .form-control {
            border: 2px solid #e5e7eb;
            border-radius: 12px;
            padding: 12px 16px;
            transition: all 0.3s ease;
        }
        .form-control:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }
        .btn-super-admin {
            background: linear-gradient(135deg, #667eea, #764ba2);
            border: none;
            border-radius: 12px;
            padding: 14px;
            font-weight: 600;
            color: white;
            width: 100%;
            transition: all 0.3s ease;
        }
        .btn-super-admin:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(102, 126, 234, 0.4);
            color: white;
        }
        .initial-credentials {
            background: #d1ecf1;
            border: 1px solid #bee5eb;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 20px;
        }
        .credential-item {
            background: #f8f9fa;
            padding: 10px;
            border-radius: 8px;
            margin: 5px 0;
            font-family: monospace;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <div class="super-admin-card">
        <div class="super-admin-header">
            <div class="super-admin-icon">
                🔐
            </div>
            <h2>سوپر ادمین سیستم</h2>
            <p>دسترسی کامل به مدیریت لایسنس</p>
        </div>
        
        <div class="super-admin-body">
            <?php if (isset($initialCredentials)): ?>
                <div class="initial-credentials">
                    <h5 class="text-info">🎉 سوپر ادمین ایجاد شد!</h5>
                    <p><strong>نام کاربری:</strong></p>
                    <div class="credential-item"><?= $initialCredentials['username'] ?></div>
                    <p><strong>رمز عبور:</strong></p>
                    <div class="credential-item"><?= $initialCredentials['password'] ?></div>
                    <small class="text-warning">⚠️ لطفاً این اطلاعات را یادداشت کرده و پس از ورود رمز عبور را تغییر دهید.</small>
                </div>
            <?php endif; ?>
            
            <?php if ($error): ?>
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>
            
            <form method="POST">
                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                
                <div class="mb-3">
                    <label class="form-label">نام کاربری سوپر ادمین</label>
                    <input type="text" name="username" class="form-control" 
                           placeholder="نام کاربری" required 
                           value="<?= isset($initialCredentials) ? $initialCredentials['username'] : '' ?>">
                </div>
                
                <div class="mb-3">
                    <label class="form-label">رمز عبور</label>
                    <input type="password" name="password" class="form-control" 
                           placeholder="رمز عبور" required>
                </div>
                
                <button type="submit" class="btn-super-admin">
                    🔓 ورود به پنل سوپر ادمین
                </button>
            </form>
            
            <div class="text-center mt-4">
                <small class="text-muted">
                    شناسه سیستم: <code><?= substr($hardwareId, 0, 16) ?>...</code>
                </small>
            </div>
            
            <div class="text-center mt-3">
                <a href="login.php" class="btn btn-outline-secondary btn-sm">
                    ورود کاربر عادی
                </a>
            </div>
        </div>
    </div>
</body>
</html>