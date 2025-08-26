<?php
/**
 * پنل مدیریت لایسنس - فقط سوپر ادمین
 */
require_once 'init_security.php';
require_once 'config/database.php';
require_once 'includes/LicenseManager.php';

// بررسی دسترسی سوپر ادمین
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    die('دسترسی غیرمجاز');
}

$database = new Database();
$db = $database->getConnection();
$licenseManager = new LicenseManager($db);

$message = '';
$hardwareId = $licenseManager->generateHardwareID();
$licenseInfo = $licenseManager->getLicenseInfo();

// پردازش درخواستها
if ($_POST) {
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf_token'])) {
        $message = '<div class="alert alert-danger">درخواست نامعتبر</div>';
    } else {
        $action = $_POST['action'] ?? '';
        
        switch ($action) {
            case 'generate':
                $days = (int)($_POST['days'] ?? 365);
                $maxUsers = (int)($_POST['max_users'] ?? 5);
                $features = array_filter($_POST['features'] ?? []);
                
                $licenseKey = $licenseManager->generateLicenseKey($hardwareId, $days, $maxUsers, $features);
                $message = '<div class="alert alert-success">کلید لایسنس تولید شد:<br><textarea class="form-control mt-2" rows="3" readonly>' . $licenseKey . '</textarea></div>';
                break;
                
            case 'activate':
                $licenseKey = trim($_POST['license_key'] ?? '');
                $result = $licenseManager->activateLicense($licenseKey);
                $class = $result['success'] ? 'success' : 'danger';
                $message = '<div class="alert alert-' . $class . '">' . $result['message'] . '</div>';
                break;
                
            case 'disable':
                $licenseManager->disableLicense();
                $message = '<div class="alert alert-warning">لایسنس غیرفعال شد</div>';
                break;
                
            case 'extend':
                $days = (int)($_POST['extend_days'] ?? 30);
                $licenseManager->extendLicense($hardwareId, $days);
                $message = '<div class="alert alert-success">لایسنس ' . $days . ' روز تمدید شد</div>';
                break;
                
            case 'reset':
                $licenseManager->resetLicense();
                $message = '<div class="alert alert-info">لایسنس بازنشانی شد</div>';
                break;
        }
        
        // بروزرسانی اطلاعات
        $licenseInfo = $licenseManager->getLicenseInfo();
    }
}

// تولید CSRF token
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$validation = $licenseManager->validateLicense();
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="utf-8">
    <title>مدیریت لایسنس سیستم</title>
    <link rel="stylesheet" href="assets/css/bootstrap.rtl.min.css">
    <style>
        body { font-family: Tahoma; background: #f8f9fa; }
        .container { max-width: 800px; margin: 50px auto; }
        .card { margin-bottom: 20px; }
        .hardware-id { font-family: monospace; background: #f1f1f1; padding: 10px; border-radius: 5px; }
        .status-active { color: #28a745; }
        .status-expired { color: #dc3545; }
        .status-disabled { color: #6c757d; }
    </style>
</head>
<body>
    <div class="container">
        <h2 class="text-center mb-4">🔐 مدیریت لایسنس سیستم</h2>
        
        <?= $message ?>
        
        <!-- اطلاعات سیستم -->
        <div class="card">
            <div class="card-header">
                <h5>📋 اطلاعات سیستم</h5>
            </div>
            <div class="card-body">
                <p><strong>شناسه سیستم:</strong></p>
                <div class="hardware-id"><?= $hardwareId ?></div>
                
                <?php if ($licenseInfo): ?>
                    <hr>
                    <div class="row">
                        <div class="col-md-6">
                            <p><strong>وضعیت:</strong> 
                                <span class="status-<?= $licenseInfo['status'] ?>">
                                    <?= $licenseInfo['status'] === 'active' ? '✅ فعال' : 
                                        ($licenseInfo['status'] === 'expired' ? '❌ منقضی' : '⏸️ غیرفعال') ?>
                                </span>
                            </p>
                            <p><strong>تاریخ صدور:</strong> <?= $licenseInfo['issued_date'] ?></p>
                            <p><strong>تاریخ انقضا:</strong> <?= $licenseInfo['expiry_date'] ?></p>
                        </div>
                        <div class="col-md-6">
                            <p><strong>حداکثر کاربران:</strong> <?= $licenseInfo['max_users'] ?></p>
                            <p><strong>ویژگیها:</strong> 
                                <?php 
                                $features = json_decode($licenseInfo['features'], true) ?: [];
                                echo empty($features) ? 'همه ویژگیها' : implode(', ', $features);
                                ?>
                            </p>
                            <?php if ($validation['valid']): ?>
                                <p><strong>روزهای باقیمانده:</strong> <?= $validation['days_remaining'] ?></p>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="alert alert-warning mt-3">هیچ لایسنسی فعال نیست</div>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- تولید لایسنس -->
        <div class="card">
            <div class="card-header">
                <h5>🔑 تولید لایسنس جدید</h5>
            </div>
            <div class="card-body">
                <form method="POST">
                    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                    <input type="hidden" name="action" value="generate">
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">مدت اعتبار (روز):</label>
                                <input type="number" name="days" class="form-control" value="365" min="1" max="3650">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">حداکثر کاربران:</label>
                                <input type="number" name="max_users" class="form-control" value="5" min="1" max="100">
                            </div>
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
                    
                    <button type="submit" class="btn btn-primary">تولید کلید لایسنس</button>
                </form>
            </div>
        </div>
        
        <!-- فعالسازی لایسنس -->
        <div class="card">
            <div class="card-header">
                <h5>✅ فعالسازی لایسنس</h5>
            </div>
            <div class="card-body">
                <form method="POST">
                    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                    <input type="hidden" name="action" value="activate">
                    
                    <div class="mb-3">
                        <label class="form-label">کلید لایسنس:</label>
                        <textarea name="license_key" class="form-control" rows="3" placeholder="کلید لایسنس را اینجا وارد کنید..." required></textarea>
                    </div>
                    
                    <button type="submit" class="btn btn-success">فعالسازی لایسنس</button>
                </form>
            </div>
        </div>
        
        <!-- مدیریت لایسنس فعلی -->
        <?php if ($licenseInfo): ?>
        <div class="card">
            <div class="card-header">
                <h5>⚙️ مدیریت لایسنس فعلی</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-3">
                        <form method="POST" class="d-inline">
                            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                            <input type="hidden" name="action" value="disable">
                            <button type="submit" class="btn btn-warning btn-sm w-100" onclick="return confirm('آیا مطمئن هستید؟')">غیرفعال کردن</button>
                        </form>
                    </div>
                    <div class="col-md-6">
                        <form method="POST" class="d-flex">
                            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                            <input type="hidden" name="action" value="extend">
                            <input type="number" name="extend_days" class="form-control form-control-sm me-2" value="30" min="1" max="365" style="width: 80px;">
                            <button type="submit" class="btn btn-info btn-sm">تمدید (روز)</button>
                        </form>
                    </div>
                    <div class="col-md-3">
                        <form method="POST" class="d-inline">
                            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                            <input type="hidden" name="action" value="reset">
                            <button type="submit" class="btn btn-danger btn-sm w-100" onclick="return confirm('تمام اطلاعات لایسنس حذف خواهد شد!')">بازنشانی</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>
        
        <div class="text-center mt-4">
            <a href="dashboard.php" class="btn btn-secondary">بازگشت به داشبورد</a>
        </div>
    </div>
</body>
</html>