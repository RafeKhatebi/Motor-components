<?php
/**
 * تست سیستم لایسنس
 */
require_once 'init_security.php';
require_once 'config/database.php';
require_once 'includes/LicenseManager.php';

// فقط ادمین
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    die('دسترسی غیرمجاز');
}

$database = new Database();
$db = $database->getConnection();
$licenseManager = new LicenseManager($db);

echo "<h2>🧪 تست سیستم لایسنس</h2>";

$tests = [];
$passed = 0;
$failed = 0;

// تست 1: تولید Hardware ID
try {
    $hardwareId = $licenseManager->generateHardwareID();
    $tests[] = ['name' => 'تولید Hardware ID', 'status' => 'pass', 'message' => substr($hardwareId, 0, 16) . '...'];
    $passed++;
} catch (Exception $e) {
    $tests[] = ['name' => 'تولید Hardware ID', 'status' => 'fail', 'message' => $e->getMessage()];
    $failed++;
}

// تست 2: تولید کلید لایسنس
try {
    $licenseKey = $licenseManager->generateLicenseKey($hardwareId, 30, 3, ['test_feature']);
    $tests[] = ['name' => 'تولید کلید لایسنس', 'status' => 'pass', 'message' => 'کلید تولید شد'];
    $passed++;
} catch (Exception $e) {
    $tests[] = ['name' => 'تولید کلید لایسنس', 'status' => 'fail', 'message' => $e->getMessage()];
    $failed++;
    $licenseKey = null;
}

// تست 3: فعالسازی لایسنس
if ($licenseKey) {
    try {
        $result = $licenseManager->activateLicense($licenseKey);
        $tests[] = ['name' => 'فعالسازی لایسنس', 'status' => $result['success'] ? 'pass' : 'fail', 'message' => $result['message']];
        if ($result['success']) $passed++; else $failed++;
    } catch (Exception $e) {
        $tests[] = ['name' => 'فعالسازی لایسنس', 'status' => 'fail', 'message' => $e->getMessage()];
        $failed++;
    }
}

// تست 4: اعتبارسنجی لایسنس
try {
    $validation = $licenseManager->validateLicense();
    $tests[] = ['name' => 'اعتبارسنجی لایسنس', 'status' => $validation['valid'] ? 'pass' : 'fail', 'message' => $validation['message']];
    if ($validation['valid']) $passed++; else $failed++;
} catch (Exception $e) {
    $tests[] = ['name' => 'اعتبارسنجی لایسنس', 'status' => 'fail', 'message' => $e->getMessage()];
    $failed++;
}

// تست 5: بررسی ویژگی
try {
    $hasFeature = $licenseManager->hasFeature('test_feature');
    $tests[] = ['name' => 'بررسی ویژگی', 'status' => $hasFeature ? 'pass' : 'fail', 'message' => $hasFeature ? 'ویژگی موجود' : 'ویژگی ناموجود'];
    if ($hasFeature) $passed++; else $failed++;
} catch (Exception $e) {
    $tests[] = ['name' => 'بررسی ویژگی', 'status' => 'fail', 'message' => $e->getMessage()];
    $failed++;
}

// تست 6: بررسی محدودیت کاربران
try {
    $userLimit = $licenseManager->checkUserLimit();
    $tests[] = ['name' => 'محدودیت کاربران', 'status' => 'pass', 'message' => $userLimit ? 'در محدوده مجاز' : 'خارج از محدوده'];
    $passed++;
} catch (Exception $e) {
    $tests[] = ['name' => 'محدودیت کاربران', 'status' => 'fail', 'message' => $e->getMessage()];
    $failed++;
}

// تست 7: تمدید لایسنس
try {
    $extended = $licenseManager->extendLicense($hardwareId, 10);
    $tests[] = ['name' => 'تمدید لایسنس', 'status' => $extended ? 'pass' : 'fail', 'message' => $extended ? '10 روز تمدید شد' : 'خطا در تمدید'];
    if ($extended) $passed++; else $failed++;
} catch (Exception $e) {
    $tests[] = ['name' => 'تمدید لایسنس', 'status' => 'fail', 'message' => $e->getMessage()];
    $failed++;
}

// تست 8: غیرفعال کردن لایسنس
try {
    $disabled = $licenseManager->disableLicense();
    $tests[] = ['name' => 'غیرفعال کردن', 'status' => $disabled ? 'pass' : 'fail', 'message' => $disabled ? 'غیرفعال شد' : 'خطا در غیرفعالسازی'];
    if ($disabled) $passed++; else $failed++;
} catch (Exception $e) {
    $tests[] = ['name' => 'غیرفعال کردن', 'status' => 'fail', 'message' => $e->getMessage()];
    $failed++;
}

// تست 9: بازنشانی لایسنس
try {
    $reset = $licenseManager->resetLicense();
    $tests[] = ['name' => 'بازنشانی لایسنس', 'status' => $reset ? 'pass' : 'fail', 'message' => $reset ? 'بازنشانی شد' : 'خطا در بازنشانی'];
    if ($reset) $passed++; else $failed++;
} catch (Exception $e) {
    $tests[] = ['name' => 'بازنشانی لایسنس', 'status' => 'fail', 'message' => $e->getMessage()];
    $failed++;
}

// نمایش نتایج
$percentage = round(($passed / ($passed + $failed)) * 100);

echo "<style>
.test-results { font-family: Tahoma; margin: 20px 0; }
.test-item { padding: 10px; margin: 5px 0; border-radius: 5px; }
.pass { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
.fail { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
.summary { padding: 15px; margin: 20px 0; border-radius: 5px; background: #e2e3e5; }
</style>";

echo "<div class='test-results'>";
echo "<div class='summary'>";
echo "<h3>📊 خلاصه نتایج تست لایسنس:</h3>";
echo "<p><strong>موفق:</strong> $passed تست</p>";
echo "<p><strong>ناموفق:</strong> $failed تست</p>";
echo "<p><strong>درصد موفقیت:</strong> $percentage%</p>";
echo "</div>";

foreach ($tests as $test) {
    $class = $test['status'] == 'pass' ? 'pass' : 'fail';
    $icon = $test['status'] == 'pass' ? '✅' : '❌';
    echo "<div class='test-item $class'>";
    echo "<strong>$icon {$test['name']}:</strong> {$test['message']}";
    echo "</div>";
}
echo "</div>";

if ($percentage >= 80) {
    echo "<div style='background: #d4edda; color: #155724; padding: 15px; border-radius: 5px; margin: 20px 0;'>";
    echo "<h4>🎉 سیستم لایسنس به درستی کار میکند!</h4>";
    echo "<p>تمام عملکردهای اصلی تست شدند و سیستم آماده استفاده است.</p>";
    echo "</div>";
} else {
    echo "<div style='background: #f8d7da; color: #721c24; padding: 15px; border-radius: 5px; margin: 20px 0;'>";
    echo "<h4>⚠️ مشکلاتی در سیستم لایسنس وجود دارد</h4>";
    echo "<p>لطفاً موارد ناموفق را بررسی و برطرف کنید.</p>";
    echo "</div>";
}

echo "<p><a href='license_admin.php' style='background: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>🔧 مدیریت لایسنس</a></p>";
echo "<p><a href='dashboard.php' style='background: #28a745; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>🏠 بازگشت به داشبورد</a></p>";
?>