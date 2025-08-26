<?php
/**
 * اسکریپت تست جامع سیستم
 */
require_once 'init_security.php';

echo "<h2>🔍 تست جامع سیستم مدیریت فروشگاه</h2>";

$tests = [];
$passed = 0;
$failed = 0;

// تست 1: اتصال دیتابیس
try {
    require_once 'config/database.php';
    $db = new Database();
    $conn = $db->getConnection();
    $tests[] = ['name' => 'اتصال دیتابیس', 'status' => 'pass', 'message' => 'موفق'];
    $passed++;
} catch (Exception $e) {
    $tests[] = ['name' => 'اتصال دیتابیس', 'status' => 'fail', 'message' => $e->getMessage()];
    $failed++;
}

// تست 2: بررسی جداول اصلی
if (isset($conn)) {
    $required_tables = ['users', 'products', 'categories', 'customers', 'suppliers', 'sales', 'purchases'];
    foreach ($required_tables as $table) {
        try {
            $stmt = $conn->prepare("SELECT 1 FROM $table LIMIT 1");
            $stmt->execute();
            $tests[] = ['name' => "جدول $table", 'status' => 'pass', 'message' => 'موجود'];
            $passed++;
        } catch (Exception $e) {
            $tests[] = ['name' => "جدول $table", 'status' => 'fail', 'message' => 'ناموجود'];
            $failed++;
        }
    }
}

// تست 3: بررسی فایلهای اصلی
$required_files = [
    'login.php' => 'صفحه ورود',
    'dashboard.php' => 'داشبورد',
    'products.php' => 'مدیریت محصولات',
    'sales.php' => 'فروش',
    'includes/auth.php' => 'احراز هویت',
    'includes/functions.php' => 'توابع کمکی'
];

foreach ($required_files as $file => $desc) {
    if (file_exists($file)) {
        $tests[] = ['name' => $desc, 'status' => 'pass', 'message' => 'فایل موجود'];
        $passed++;
    } else {
        $tests[] = ['name' => $desc, 'status' => 'fail', 'message' => 'فایل ناموجود'];
        $failed++;
    }
}

// تست 4: بررسی مجوزهای فایل
$upload_dirs = ['uploads/logos/', 'backups/', 'logs/'];
foreach ($upload_dirs as $dir) {
    if (is_dir($dir) && is_writable($dir)) {
        $tests[] = ['name' => "مجوز نوشتن $dir", 'status' => 'pass', 'message' => 'قابل نوشتن'];
        $passed++;
    } else {
        $tests[] = ['name' => "مجوز نوشتن $dir", 'status' => 'fail', 'message' => 'غیرقابل نوشتن'];
        $failed++;
    }
}

// تست 5: بررسی PHP Extensions
$required_extensions = ['pdo', 'pdo_mysql', 'gd', 'mbstring', 'json'];
foreach ($required_extensions as $ext) {
    if (extension_loaded($ext)) {
        $tests[] = ['name' => "PHP Extension: $ext", 'status' => 'pass', 'message' => 'نصب شده'];
        $passed++;
    } else {
        $tests[] = ['name' => "PHP Extension: $ext", 'status' => 'fail', 'message' => 'نصب نشده'];
        $failed++;
    }
}

// نمایش نتایج
echo "<style>
.test-results { font-family: Tahoma; margin: 20px 0; }
.test-item { padding: 10px; margin: 5px 0; border-radius: 5px; }
.pass { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
.fail { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
.summary { padding: 15px; margin: 20px 0; border-radius: 5px; background: #e2e3e5; }
</style>";

echo "<div class='test-results'>";
echo "<div class='summary'>";
echo "<h3>📊 خلاصه نتایج:</h3>";
echo "<p><strong>موفق:</strong> $passed تست</p>";
echo "<p><strong>ناموفق:</strong> $failed تست</p>";
echo "<p><strong>درصد موفقیت:</strong> " . round(($passed / ($passed + $failed)) * 100, 1) . "%</p>";
echo "</div>";

foreach ($tests as $test) {
    $class = $test['status'] == 'pass' ? 'pass' : 'fail';
    $icon = $test['status'] == 'pass' ? '✅' : '❌';
    echo "<div class='test-item $class'>";
    echo "<strong>$icon {$test['name']}:</strong> {$test['message']}";
    echo "</div>";
}
echo "</div>";

if ($failed > 0) {
    echo "<div style='background: #fff3cd; color: #856404; padding: 15px; border-radius: 5px; margin: 20px 0;'>";
    echo "<h4>⚠️ توصیههای رفع مشکل:</h4>";
    echo "<ul>";
    echo "<li>برای رفع مشکلات دیتابیس، فایل <code>fix_database.php</code> را اجرا کنید</li>";
    echo "<li>مجوزهای فولدرها را بررسی کنید (chmod 755 یا 777)</li>";
    echo "<li>PHP Extensions مورد نیاز را نصب کنید</li>";
    echo "<li>فایلهای ناموجود را از پروژه اصلی کپی کنید</li>";
    echo "</ul>";
    echo "</div>";
} else {
    echo "<div style='background: #d4edda; color: #155724; padding: 15px; border-radius: 5px; margin: 20px 0;'>";
    echo "<h4>🎉 تبریک! سیستم شما کاملاً سالم است</h4>";
    echo "<p>تمام تستها با موفقیت پاس شدند. سیستم آماده استفاده است.</p>";
    echo "</div>";
}

echo "<p><a href='dashboard.php' style='background: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>🏠 بازگشت به داشبورد</a></p>";
?>