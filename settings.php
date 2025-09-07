<?php
require_once 'init_security.php';
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: dashboard.php');
    exit();
}

require_once 'config/database.php';
require_once 'includes/functions.php';
require_once 'includes/SettingsHelper.php';
$page_title = 'تنظیمات سیستم';
$database = new Database();
$db = $database->getConnection();
SettingsHelper::loadSettings($db);

// Handle settings update
if ($_POST && isset($_POST['action'])) {
    if ($_POST['action'] === 'update_settings') {
        try {
            // Create settings table if not exists
            $create_table = "CREATE TABLE IF NOT EXISTS settings (
                id INT PRIMARY KEY AUTO_INCREMENT,
                setting_key VARCHAR(255) UNIQUE,
                setting_value TEXT,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            )";
            $db->exec($create_table);

            // Handle logo upload
            $logo_path = null;
            if (isset($_FILES['shop_logo']) && $_FILES['shop_logo']['error'] === UPLOAD_ERR_OK) {
                $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
                $file_type = $_FILES['shop_logo']['type'];

                if (in_array($file_type, $allowed_types)) {
                    $file_extension = pathinfo($_FILES['shop_logo']['name'], PATHINFO_EXTENSION);
                    $logo_filename = 'logo_' . time() . '.' . $file_extension;
                    $logo_path = 'uploads/logos/' . $logo_filename;

                    if (move_uploaded_file($_FILES['shop_logo']['tmp_name'], $logo_path)) {
                        // Delete old logo if exists
                        $old_logo = $current_settings['shop_logo'] ?? '';
                        if ($old_logo && file_exists($old_logo)) {
                            unlink($old_logo);
                        }
                    } else {
                        $logo_path = null;
                    }
                }
            }

            // Update settings
            $settings = [
                'shop_name' => $_POST['shop_name'] ?? 'فروشگاه قطعات موتور',
                'shop_phone' => $_POST['shop_phone'] ?? '',
                'shop_address' => $_POST['shop_address'] ?? '',
                'currency' => $_POST['currency'] ?? 'afghani',
                'language' => $_POST['language'] ?? 'fa',
                'date_format' => $_POST['date_format'] ?? 'gregorian',
                'auto_backup' => isset($_POST['auto_backup']) ? '1' : '0',
                'notifications' => isset($_POST['notifications']) ? '1' : '0',
                'min_profit_margin' => $_POST['min_profit_margin'] ?? '5'
            ];

            // Add logo path if uploaded
            if ($logo_path) {
                $settings['shop_logo'] = $logo_path;
            }

            foreach ($settings as $key => $value) {
                $query = "INSERT INTO settings (setting_key, setting_value) VALUES (?, ?) 
                         ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)";
                $stmt = $db->prepare($query);
                $stmt->execute([$key, $value]);
            }

            $success_message = "تنظیمات با موفقیت بروزرسانی شد";
        } catch (Exception $e) {
            $error_message = "خطا در بروزرسانی تنظیمات: " . $e->getMessage();
        }
    }
}

// Get current settings
$current_settings = [];
try {
    $settings_query = "SELECT setting_key, setting_value FROM settings";
    $settings_stmt = $db->prepare($settings_query);
    $settings_stmt->execute();
    while ($row = $settings_stmt->fetch(PDO::FETCH_ASSOC)) {
        $current_settings[$row['setting_key']] = $row['setting_value'];
    }
} catch (Exception $e) {
    // Settings table doesn't exist yet
}

// Get system statistics
$stats_query = "SELECT 
    (SELECT COUNT(*) FROM products) as total_products,
    (SELECT COUNT(*) FROM customers) as total_customers,
    (SELECT COUNT(*) FROM suppliers) as total_suppliers,
    (SELECT COUNT(*) FROM users) as total_users,
    (SELECT COUNT(*) FROM sales WHERE DATE(created_at) = CURDATE() AND (status IS NULL OR status != 'returned')) as today_sales,
    (SELECT COALESCE(SUM(final_amount), 0) FROM sales WHERE DATE(created_at) = CURDATE() AND (status IS NULL OR status != 'returned')) as today_revenue";
$stats_stmt = $db->prepare($stats_query);
$stats_stmt->execute();
$stats = $stats_stmt->fetch(PDO::FETCH_ASSOC);

include 'includes/header.php';
?>
<!-- Page content -->
<div class="container-fluid mt--7">
    <?php if (isset($_GET['first_login'])): ?>
        <div class="alert alert-info alert-dismissible fade show" role="alert">
            <i class="fas fa-star ml-2"></i>
            <strong>خوش آمدید!</strong> این اولین ورود شما به سیستم است. لطفاً تنظیمات اولیه را تکمیل کنید و کاربران جدید
            ایجاد کنید.
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <?php if (isset($success_message)): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fas fa-check-circle ml-2"></i>
            <?= $success_message ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>
    <div class="row mt-5">
        <div class="col-12">
            <div class="card shadow">
                <div class="card-header border-0">
                    <div class="row align-items-center">
                        <div class="col">
                            <h3 class="mb-0"><?= __('general_settings') ?></h3>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <form method="POST" enctype="multipart/form-data">
                        <input type="hidden" name="action" value="update_settings">

                        <!-- تنظیمات عمومی -->
                        <div class="mb-4">
                            <h5 class="text-primary mb-3">اطلاعات فروشگاه</h5>
                            <div class="d-flex gap-3 mb-3">
                                <div style="flex: 1;">
                                    <label class="form-label">نام فروشگاه</label>
                                    <input type="text" name="shop_name" class="form-control" required
                                        value="<?= sanitizeOutput($current_settings['shop_name'] ?? 'فروشگاه قطعات موتور') ?>">
                                </div>
                                <div style="flex: 1;">
                                    <label class="form-label">شماره تماس</label>
                                    <input type="text" name="shop_phone" class="form-control"
                                        value="<?= sanitizeOutput($current_settings['shop_phone'] ?? '021-12345678') ?>">
                                </div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">آدرس فروشگاه</label>
                                <textarea name="shop_address" class="form-control"
                                    rows="2"><?= sanitizeOutput($current_settings['shop_address'] ?? 'تهران، خیابان اصلی، پلاک 123') ?></textarea>
                            </div>
                            <div class="d-flex gap-3">
                                <div style="flex: 1;">
                                    <label class="form-label">لوگوی فروشگاه</label>
                                    <input type="file" name="shop_logo" class="form-control" accept="image/*">
                                </div>
                                <?php if (!empty($current_settings['shop_logo']) && file_exists($current_settings['shop_logo'])): ?>
                                    <div style="flex: 1;">
                                        <label class="form-label">لوگوی فعلی</label>
                                        <div><img src="<?= $current_settings['shop_logo'] ?>"
                                                style="max-width: 80px; border-radius: 4px;"></div>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- تنظیمات ظاهری -->
                        <div class="mb-4">
                            <h5 class="text-success mb-3">تنظیمات ظاهری</h5>
                            <div class="d-flex gap-3 mb-3">
                                <div style="flex: 1;">
                                    <label class="form-label">رنگ قالب</label>
                                    <select name="theme_color" class="form-control">
                                        <option value="blue" <?= ($current_settings['theme_color'] ?? 'blue') == 'blue' ? 'selected' : '' ?>>آبی</option>
                                        <option value="green" <?= ($current_settings['theme_color'] ?? 'blue') == 'green' ? 'selected' : '' ?>>سبز</option>
                                        <option value="purple" <?= ($current_settings['theme_color'] ?? 'blue') == 'purple' ? 'selected' : '' ?>>بنفش</option>
                                        <option value="red" <?= ($current_settings['theme_color'] ?? 'blue') == 'red' ? 'selected' : '' ?>>قرمز</option>
                                    </select>
                                </div>
                                <div style="flex: 1;">
                                    <label class="form-label">چیدمان داشبورد</label>
                                    <select name="dashboard_layout" class="form-control">
                                        <option value="modern" <?= ($current_settings['dashboard_layout'] ?? 'modern') == 'modern' ? 'selected' : '' ?>>مدرن</option>
                                        <option value="classic" <?= ($current_settings['dashboard_layout'] ?? 'modern') == 'classic' ? 'selected' : '' ?>>کلاسیک</option>
                                        <option value="compact" <?= ($current_settings['dashboard_layout'] ?? 'modern') == 'compact' ? 'selected' : '' ?>>فشرده</option>
                                    </select>
                                </div>
                            </div>
                            <div class="d-flex gap-3">
                                <div style="flex: 1;">
                                    <div class="form-check">
                                        <input class="form-check-input" name="show_animations" id="show_animations"
                                            type="checkbox" <?= ($current_settings['show_animations'] ?? '1') == '1' ? 'checked' : '' ?>>
                                        <label class="form-check-label" for="show_animations">نمایش انیمیشنها</label>
                                    </div>
                                </div>
                                <div style="flex: 1;">
                                    <div class="form-check">
                                        <input class="form-check-input" name="compact_mode" id="compact_mode"
                                            type="checkbox" <?= ($current_settings['compact_mode'] ?? '0') == '1' ? 'checked' : '' ?>>
                                        <label class="form-check-label" for="compact_mode">حالت فشرده</label>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- تنظیمات کسب وکار -->
                        <div class="mb-4">
                            <h5 class="text-warning mb-3">تنظیمات کسب وکار</h5>
                            <div class="d-flex gap-3 mb-3">
                                <div style="flex: 1;">
                                    <label class="form-label">حداقل درصد فایده (%)</label>
                                    <input type="number" name="min_profit_margin" class="form-control" min="0" max="100"
                                        step="0.1"
                                        value="<?= sanitizeOutput($current_settings['min_profit_margin'] ?? '5') ?>">
                                </div>
                                <div style="flex: 1;">
                                    <label class="form-label">حد نهایت موجودی کم</label>
                                    <input type="number" name="low_stock_threshold" class="form-control" min="1"
                                        value="<?= sanitizeOutput($current_settings['low_stock_threshold'] ?? '5') ?>">
                                </div>
                            </div>
                            <div class="d-flex gap-3">
                                <div style="flex: 1;">
                                    <label class="form-label">پیشوند فاکتور</label>
                                    <input type="text" name="invoice_prefix" class="form-control"
                                        value="<?= sanitizeOutput($current_settings['invoice_prefix'] ?? 'INV') ?>">
                                </div>
                                <div style="flex: 1;">
                                    <div class="form-check mt-4">
                                        <input class="form-check-input" name="low_stock_alerts" id="low_stock_alerts"
                                            type="checkbox" <?= ($current_settings['low_stock_alerts'] ?? '1') == '1' ? 'checked' : '' ?>>
                                        <label class="form-check-label" for="low_stock_alerts">هشدار موجودی کم</label>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- تنظیمات سیستم -->
                        <div class="mb-4">
                            <h5 class="text-info mb-3">تنظیمات سیستم</h5>
                            <div class="d-flex gap-3 mb-3">
                                <div style="flex: 1;">
                                    <label class="form-label">واحد پول</label>
                                    <select name="currency" class="form-control">
                                        <option value="afghani" <?= ($current_settings['currency'] ?? 'afghani') == 'afghani' ? 'selected' : '' ?>>افغانی</option>
                                    </select>
                                </div>
                                <div style="flex: 1;">
                                    <label class="form-label">زبان</label>
                                    <select name="language" class="form-control" onchange="changeLanguage(this.value)">
                                        <option value="fa" <?= ($current_settings['language'] ?? 'fa') == 'fa' ? 'selected' : '' ?>>فارسی</option>
                                        <option value="ps" <?= ($current_settings['language'] ?? 'fa') == 'ps' ? 'selected' : '' ?>>پشتو</option>
                                    </select>
                                </div>
                                <div style="flex: 1;">
                                    <label class="form-label">فرمت تاریخ</label>
                                    <select name="date_format" class="form-control">
                                        <option value="gregorian" <?= ($current_settings['date_format'] ?? 'gregorian') == 'gregorian' ? 'selected' : '' ?>>میلادی</option>
                                        <option value="jalali" <?= ($current_settings['date_format'] ?? 'gregorian') == 'jalali' ? 'selected' : '' ?>>شمسی</option>
                                    </select>
                                </div>
                            </div>
                            <div class="d-flex gap-3">
                                <div style="flex: 1;">
                                    <div class="form-check">
                                        <input class="form-check-input" name="auto_backup" id="backup_auto"
                                            type="checkbox" <?= ($current_settings['auto_backup'] ?? '1') == '1' ? 'checked' : '' ?>>
                                        <label class="form-check-label" for="backup_auto">پشتیبان خودکار</label>
                                    </div>
                                </div>
                                <div style="flex: 1;">
                                    <div class="form-check">
                                        <input class="form-check-input" name="notifications" id="notifications"
                                            type="checkbox" <?= ($current_settings['notifications'] ?? '1') == '1' ? 'checked' : '' ?>>
                                        <label class="form-check-label" for="notifications">اعلانات سیستم</label>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="text-center">
                            <button type="submit" class="btn btn-primary btn-lg px-5">ذخیره تنظیمات</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="assets/js/logo-upload.js"></script>
<script>
    function changeLanguage(language) {
        fetch('api/update_language.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ language: language })
        })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                } else {
                    alert('خطا در تغییر زبان');
                }
            })
            .catch(error => {
                alert('خطا در ارتباط با سرور');
            });
    }
</script>
<?php include 'includes/footer-modern.php'; ?>