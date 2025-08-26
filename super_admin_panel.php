<?php
/**
 * پنل سوپر ادمین - مدیریت کامل سیستم و لایسنس
 */
require_once 'init_security.php';
require_once 'config/database.php';
require_once 'includes/LicenseManager.php';
require_once 'includes/SuperAdminManager.php';

$database = new Database();
$db = $database->getConnection();
$superAdminManager = new SuperAdminManager($db);
$licenseManager = new LicenseManager($db, $superAdminManager);

// بررسی دسترسی سوپر ادمین
if (!$superAdminManager->isSuperAdmin()) {
    header('Location: super_admin_login.php');
    exit();
}

$message = '';
$hardwareId = $licenseManager->generateHardwareID();
$licenseInfo = $licenseManager->getLicenseInfo();
$superAdminInfo = $superAdminManager->getSuperAdminInfo($_SESSION['super_admin_id']);

// پردازش درخواستها
if ($_POST) {
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf_token'])) {
        $message = '<div class="alert alert-danger">درخواست نامعتبر</div>';
    } else {
        $action = $_POST['action'] ?? '';
        
        try {
            switch ($action) {
                case 'generate_license':
                    $days = (int)($_POST['days'] ?? 365);
                    $maxUsers = (int)($_POST['max_users'] ?? 5);
                    $features = array_filter($_POST['features'] ?? []);
                    
                    $licenseKey = $licenseManager->generateLicenseKey($hardwareId, $days, $maxUsers, $features);
                    $message = '<div class="alert alert-success">
                        <h5>🔑 کلید لایسنس تولید شد:</h5>
                        <textarea class="form-control mt-2" rows="4" readonly onclick="this.select()">' . $licenseKey . '</textarea>
                        <small class="text-muted mt-1 d-block">کلیک کنید تا کپی شود</small>
                    </div>';
                    break;
                    
                case 'activate_license':
                    $licenseKey = trim($_POST['license_key'] ?? '');
                    $result = $licenseManager->activateLicense($licenseKey);
                    $class = $result['success'] ? 'success' : 'danger';
                    $message = '<div class="alert alert-' . $class . '">' . $result['message'] . '</div>';
                    break;
                    
                case 'disable_license':
                    $licenseManager->disableLicense();
                    $message = '<div class="alert alert-warning">🔒 لایسنس غیرفعال شد</div>';
                    break;
                    
                case 'extend_license':
                    $days = (int)($_POST['extend_days'] ?? 30);
                    $licenseManager->extendLicense($hardwareId, $days);
                    $message = '<div class="alert alert-success">📅 لایسنس ' . $days . ' روز تمدید شد</div>';
                    break;
                    
                case 'reset_license':
                    $licenseManager->resetLicense();
                    $message = '<div class="alert alert-info">🔄 لایسنس بازنشانی شد</div>';
                    break;
                    
                case 'change_password':
                    $oldPassword = $_POST['old_password'] ?? '';
                    $newPassword = $_POST['new_password'] ?? '';
                    $confirmPassword = $_POST['confirm_password'] ?? '';
                    
                    if ($newPassword !== $confirmPassword) {
                        $message = '<div class="alert alert-danger">رمزهای عبور جدید مطابقت ندارند</div>';
                    } else {
                        $result = $superAdminManager->changePassword($_SESSION['super_admin_id'], $oldPassword, $newPassword);
                        $class = $result['success'] ? 'success' : 'danger';
                        $message = '<div class="alert alert-' . $class . '">' . $result['message'] . '</div>';
                    }
                    break;
                    
                case 'logout':
                    $superAdminManager->destroySuperAdminSession();
                    header('Location: super_admin_login.php');
                    exit();
                    break;
            }
            
            // بروزرسانی اطلاعات
            $licenseInfo = $licenseManager->getLicenseInfo();
            
        } catch (Exception $e) {
            $message = '<div class="alert alert-danger">خطا: ' . $e->getMessage() . '</div>';
        }
    }
}

// تولید CSRF token
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$validation = $licenseManager->validateLicense();

// آمار سیستم
$systemStats = [
    'total_users' => $db->query("SELECT COUNT(*) FROM users")->fetchColumn(),
    'total_products' => $db->query("SELECT COUNT(*) FROM products")->fetchColumn(),
    'total_sales' => $db->query("SELECT COUNT(*) FROM sales")->fetchColumn(),
    'today_sales' => $db->query("SELECT COALESCE(SUM(final_amount), 0) FROM sales WHERE DATE(created_at) = CURDATE()")->fetchColumn()
];
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="utf-8">
    <title>پنل سوپر ادمین - مدیریت سیستم</title>
    <link rel="stylesheet" href="assets/css/bootstrap.rtl.min.css">
    <link rel="stylesheet" href="assets/css/all.min.css">
    <style>
        body { 
            font-family: Tahoma; 
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            min-height: 100vh;
        }
        .super-admin-header {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            padding: 20px 0;
            margin-bottom: 30px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
        }
        .card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
            margin-bottom: 20px;
        }
        .card-header {
            background: linear-gradient(135deg, #f8f9fa, #e9ecef);
            border-bottom: 1px solid #dee2e6;
            border-radius: 15px 15px 0 0 !important;
            font-weight: 600;
        }
        .btn-super {
            background: linear-gradient(135deg, #667eea, #764ba2);
            border: none;
            color: white;
            border-radius: 8px;
            padding: 8px 16px;
            transition: all 0.3s ease;
        }
        .btn-super:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
            color: white;
        }
        .hardware-id {
            font-family: monospace;
            background: #f1f3f4;
            padding: 10px;
            border-radius: 8px;
            border-left: 4px solid #667eea;
        }
        .status-badge {
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
        }
        .status-active { background: #d4edda; color: #155724; }
        .status-expired { background: #f8d7da; color: #721c24; }
        .status-disabled { background: #e2e3e5; color: #6c757d; }
        .stat-card {
            background: white;
            border-radius: 12px;
            padding: 20px;
            text-align: center;
            box-shadow: 0 3px 10px rgba(0,0,0,0.1);
            transition: transform 0.3s ease;
        }
        .stat-card:hover {
            transform: translateY(-5px);
        }
        .stat-number {
            font-size: 2rem;
            font-weight: bold;
            color: #667eea;
        }
        .logout-btn {
            position: fixed;
            top: 20px;
            left: 20px;
            z-index: 1000;
        }
    </style>
</head>
<body>
    <!-- Header -->
    <div class="super-admin-header">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h2><i class="fas fa-crown me-2"></i>پنل سوپر ادمین</h2>
                    <p class="mb-0">مدیریت کامل سیستم و لایسنس - خوش آمدید <?= htmlspecialchars($_SESSION['super_admin_name']) ?></p>
                </div>
                <div class="col-md-4 text-end">
                    <form method="POST" class="d-inline">
                        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                        <input type="hidden" name="action" value="logout">
                        <button type="submit" class="btn btn-outline-light">
                            <i class="fas fa-sign-out-alt me-1"></i>خروج
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <div class="container">
        <?= $message ?>
        
        <!-- آمار سیستم -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="stat-card">
                    <div class="stat-number"><?= number_format($systemStats['total_users']) ?></div>
                    <div>کاربران سیستم</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card">
                    <div class="stat-number"><?= number_format($systemStats['total_products']) ?></div>
                    <div>محصولات</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card">
                    <div class="stat-number"><?= number_format($systemStats['total_sales']) ?></div>
                    <div>کل فروش</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card">
                    <div class="stat-number"><?= number_format($systemStats['today_sales']) ?></div>
                    <div>فروش امروز</div>
                </div>
            </div>
        </div>
        
        <!-- اطلاعات سیستم و لایسنس -->
        <div class="row">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h5><i class="fas fa-server me-2"></i>اطلاعات سیستم</h5>
                    </div>
                    <div class="card-body">
                        <p><strong>شناسه سیستم:</strong></p>
                        <div class="hardware-id mb-3"><?= $hardwareId ?></div>
                        
                        <p><strong>سوپر ادمین:</strong> <?= htmlspecialchars($superAdminInfo['full_name']) ?></p>
                        <p><strong>آخرین ورود:</strong> <?= $superAdminInfo['last_login'] ?: 'هرگز' ?></p>
                        <p><strong>تاریخ ایجاد:</strong> <?= $superAdminInfo['created_at'] ?></p>
                    </div>
                </div>
            </div>
            
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h5><i class="fas fa-certificate me-2"></i>وضعیت لایسنس</h5>
                    </div>
                    <div class="card-body">
                        <?php if ($licenseInfo): ?>
                            <div class="mb-3">
                                <span class="status-badge status-<?= $licenseInfo['status'] ?>">
                                    <?= $licenseInfo['status'] === 'active' ? '✅ فعال' : 
                                        ($licenseInfo['status'] === 'expired' ? '❌ منقضی' : '⏸️ غیرفعال') ?>
                                </span>
                            </div>
                            <p><strong>تاریخ انقضا:</strong> <?= $licenseInfo['expiry_date'] ?></p>
                            <p><strong>حداکثر کاربران:</strong> <?= $licenseInfo['max_users'] ?></p>
                            <?php if ($validation['valid']): ?>
                                <p><strong>روزهای باقیمانده:</strong> 
                                    <span class="badge bg-<?= $validation['days_remaining'] <= 7 ? 'warning' : 'success' ?>">
                                        <?= $validation['days_remaining'] ?> روز
                                    </span>
                                </p>
                            <?php endif; ?>
                        <?php else: ?>
                            <div class="alert alert-warning">هیچ لایسنسی فعال نیست</div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- مدیریت لایسنس -->
        <div class="row">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h5><i class="fas fa-key me-2"></i>تولید لایسنس جدید</h5>
                    </div>
                    <div class="card-body">
                        <form method="POST">
                            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                            <input type="hidden" name="action" value="generate_license">
                            
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">مدت اعتبار (روز):</label>
                                    <input type="number" name="days" class="form-control" value="365" min="1" max="3650">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">حداکثر کاربران:</label>
                                    <input type="number" name="max_users" class="form-control" value="5" min="1" max="100">
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">ویژگیهای مجاز:</label>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="features[]" value="advanced_reports" id="f1">
                                    <label class="form-check-label" for="f1">گزارشات پیشرفته</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="features[]" value="multi_branch" id="f2">
                                    <label class="form-check-label" for="f2">چند شعبه</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="features[]" value="api_access" id="f3">
                                    <label class="form-check-label" for="f3">دسترسی API</label>
                                </div>
                            </div>
                            
                            <button type="submit" class="btn btn-super">
                                <i class="fas fa-magic me-1"></i>تولید کلید لایسنس
                            </button>
                        </form>
                    </div>
                </div>
            </div>
            
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h5><i class="fas fa-unlock me-2"></i>فعالسازی لایسنس</h5>
                    </div>
                    <div class="card-body">
                        <form method="POST">
                            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                            <input type="hidden" name="action" value="activate_license">
                            
                            <div class="mb-3">
                                <label class="form-label">کلید لایسنس:</label>
                                <textarea name="license_key" class="form-control" rows="4" 
                                          placeholder="کلید لایسنس را اینجا وارد کنید..." required></textarea>
                            </div>
                            
                            <button type="submit" class="btn btn-success">
                                <i class="fas fa-check me-1"></i>فعالسازی لایسنس
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- مدیریت لایسنس فعلی -->
        <?php if ($licenseInfo): ?>
        <div class="card">
            <div class="card-header">
                <h5><i class="fas fa-cogs me-2"></i>مدیریت لایسنس فعلی</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-3">
                        <form method="POST" class="d-inline">
                            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                            <input type="hidden" name="action" value="disable_license">
                            <button type="submit" class="btn btn-warning w-100" onclick="return confirm('آیا مطمئن هستید؟')">
                                <i class="fas fa-pause me-1"></i>غیرفعال کردن
                            </button>
                        </form>
                    </div>
                    <div class="col-md-6">
                        <form method="POST" class="d-flex">
                            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                            <input type="hidden" name="action" value="extend_license">
                            <input type="number" name="extend_days" class="form-control me-2" value="30" min="1" max="365">
                            <button type="submit" class="btn btn-info">
                                <i class="fas fa-calendar-plus me-1"></i>تمدید (روز)
                            </button>
                        </form>
                    </div>
                    <div class="col-md-3">
                        <form method="POST" class="d-inline">
                            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                            <input type="hidden" name="action" value="reset_license">
                            <button type="submit" class="btn btn-danger w-100" onclick="return confirm('تمام اطلاعات لایسنس حذف خواهد شد!')">
                                <i class="fas fa-redo me-1"></i>بازنشانی
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>
        
        <!-- تغییر رمز عبور -->
        <div class="card">
            <div class="card-header">
                <h5><i class="fas fa-lock me-2"></i>تغییر رمز عبور سوپر ادمین</h5>
            </div>
            <div class="card-body">
                <form method="POST" class="row">
                    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                    <input type="hidden" name="action" value="change_password">
                    
                    <div class="col-md-4">
                        <input type="password" name="old_password" class="form-control" placeholder="رمز عبور فعلی" required>
                    </div>
                    <div class="col-md-4">
                        <input type="password" name="new_password" class="form-control" placeholder="رمز عبور جدید" required>
                    </div>
                    <div class="col-md-4">
                        <div class="input-group">
                            <input type="password" name="confirm_password" class="form-control" placeholder="تکرار رمز جدید" required>
                            <button type="submit" class="btn btn-super">
                                <i class="fas fa-save me-1"></i>تغییر رمز
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
        
        <!-- دسترسی سریع -->
        <div class="card">
            <div class="card-header">
                <h5><i class="fas fa-tachometer-alt me-2"></i>دسترسی سریع</h5>
            </div>
            <div class="card-body text-center">
                <a href="dashboard.php" class="btn btn-outline-primary me-2">
                    <i class="fas fa-home me-1"></i>داشبورد اصلی
                </a>
                <a href="users.php" class="btn btn-outline-info me-2">
                    <i class="fas fa-users me-1"></i>مدیریت کاربران
                </a>
                <a href="settings.php" class="btn btn-outline-success me-2">
                    <i class="fas fa-cog me-1"></i>تنظیمات سیستم
                </a>
                <a href="backup.php" class="btn btn-outline-warning">
                    <i class="fas fa-database me-1"></i>پشتیبانگیری
                </a>
            </div>
        </div>
    </div>
</body>
</html>