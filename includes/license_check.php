<?php
/**
 * بررسی خودکار لایسنس در هر صفحه
 */

// فقط در صورت وجود session اجرا شود (و نه سوپر ادمین)
if (session_status() === PHP_SESSION_ACTIVE && isset($_SESSION['user_id']) && !isset($_SESSION['is_super_admin'])) {

    // استثناء برای صفحات خاص
    $currentPage = basename($_SERVER['PHP_SELF']);
    $excludedPages = ['login.php', 'logout.php', 'license_admin.php', 'super_admin_login.php', 'super_admin_panel.php'];

    if (!in_array($currentPage, $excludedPages)) {

        try {
            require_once __DIR__ . '/../config/database.php';
            require_once __DIR__ . '/LicenseManager.php';

            $database = new Database();
            $db = $database->getConnection();
            $licenseManager = new LicenseManager($db);

            $validation = $licenseManager->validateLicense();

            if (!$validation['valid']) {
                // نمایش پیام خطا و هدایت به صفحه لایسنس
                $errorMessage = $validation['message'];

                // فقط ادمین میتواند به صفحه مدیریت لایسنس برود
                if ($_SESSION['role'] === 'admin') {
                    $redirectUrl = 'license_admin.php';
                    $adminMessage = '<br><a href="' . $redirectUrl . '" class="btn btn-primary mt-2">مدیریت لایسنس</a>';
                } else {
                    $redirectUrl = 'login.php';
                    $adminMessage = '<br><small>لطفاً با مدیر سیستم تماس بگیرید.</small>';
                }

                echo '<!DOCTYPE html>
                <html lang="fa" dir="rtl">
                <head>
                    <meta charset="utf-8">
                    <title>مشکل لایسنس</title>
                    <link rel="stylesheet" href="assets/css/bootstrap.rtl.min.css">
                    <style>
                        body { font-family: Tahoma; background: #f8f9fa; }
                        .container { max-width: 500px; margin: 100px auto; text-align: center; }
                        .error-icon { font-size: 4rem; color: #dc3545; margin-bottom: 20px; }
                    </style>
                </head>
                <body>
                    <div class="container">
                        <div class="card">
                            <div class="card-body">
                                <div class="error-icon">🔒</div>
                                <h4 class="text-danger">مشکل در لایسنس سیستم</h4>
                                <p class="mt-3">' . $errorMessage . '</p>
                                ' . $adminMessage . '
                            </div>
                        </div>
                    </div>
                </body>
                </html>';
                exit();
            }

            // بررسی محدودیت کاربران
            if (!$licenseManager->checkUserLimit()) {
                if ($_SESSION['role'] !== 'admin') {
                    echo '<!DOCTYPE html>
                    <html lang="fa" dir="rtl">
                    <head>
                        <meta charset="utf-8">
                        <title>محدودیت کاربران</title>
                        <link rel="stylesheet" href="assets/css/bootstrap.rtl.min.css">
                        <style>body { font-family: Tahoma; background: #f8f9fa; }</style>
                    </head>
                    <body>
                        <div class="container" style="max-width: 500px; margin: 100px auto; text-align: center;">
                            <div class="card">
                                <div class="card-body">
                                    <div style="font-size: 4rem; color: #ffc107; margin-bottom: 20px;">⚠️</div>
                                    <h4 class="text-warning">محدودیت تعداد کاربران</h4>
                                    <p class="mt-3">حداکثر تعداد کاربران مجاز در لایسنس فعلی به پایان رسیده است.</p>
                                    <small>لطفاً با مدیر سیستم تماس بگیرید.</small>
                                </div>
                            </div>
                        </div>
                    </body>
                    </html>';
                    exit();
                }
            }

            // نمایش هشدار انقضای نزدیک (فقط برای ادمین)
            if ($_SESSION['role'] === 'admin' && $validation['days_remaining'] <= 7) {
                $GLOBALS['license_warning'] = $validation['days_remaining'];
            }

        } catch (Exception $e) {
            // در صورت خطا، فقط لاگ کن و ادامه بده
            error_log('License check error: ' . $e->getMessage());
        }
    }
}

/**
 * نمایش هشدار لایسنس در header
 */
function showLicenseWarning()
{
    if (isset($GLOBALS['license_warning'])) {
        $days = $GLOBALS['license_warning'];
        echo '<div class="alert alert-warning alert-dismissible fade show" role="alert" style="margin: 0; border-radius: 0;">
            <strong>⚠️ هشدار:</strong> لایسنس سیستم ' . $days . ' روز دیگر منقضی میشود.
            <a href="license_admin.php" class="btn btn-sm btn-outline-warning ms-2">مدیریت لایسنس</a>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>';
    }
}
?>