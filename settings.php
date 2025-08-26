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

<!-- Header -->
<div class="header bg-gradient-primary pb-8 pt-5 pt-md-8">
    <div class="container-fluid">
        <div class="header-body">
            <div class="row align-items-center py-4">
                <div class="col-lg-6 col-7">
                    <h6 class="h2 text-white d-inline-block mb-0"><?= __('system_settings') ?></h6>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Page content -->
<div class="container-fluid mt--7">
    <?php if (isset($_GET['first_login'])): ?>
        <div class="alert alert-info alert-dismissible fade show" role="alert">
            <i class="fas fa-star ml-2"></i>
            <strong>خوش آمدید!</strong> این اولین ورود شما به سیستم است. لطفاً تنظیمات اولیه را تکمیل کنید و کاربران جدید ایجاد کنید.
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

    <!-- System Statistics -->
    <div class="row">
        <div class="col-xl-3 col-lg-6">
            <div class="card card-stats mb-4 mb-xl-0">
                <div class="card-body">
                    <div class="row">
                        <div class="col">
                            <h5 class="card-title text-uppercase text-muted mb-0"><?= __('total_products') ?></h5>
                            <span class="h2 font-weight-bold mb-0"><?= number_format($stats['total_products']) ?></span>
                        </div>
                        <div class="col-auto">
                            <div class="icon icon-shape bg-danger text-white rounded-circle shadow">
                                <i class="fas fa-box"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-lg-6">
            <div class="card card-stats mb-4 mb-xl-0">
                <div class="card-body">
                    <div class="row">
                        <div class="col">
                            <h5 class="card-title text-uppercase text-muted mb-0"><?= __('total_customers') ?></h5>
                            <span
                                class="h2 font-weight-bold mb-0"><?= number_format($stats['total_customers']) ?></span>
                        </div>
                        <div class="col-auto">
                            <div class="icon icon-shape bg-warning text-white rounded-circle shadow">
                                <i class="fas fa-users"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-lg-6">
            <div class="card card-stats mb-4 mb-xl-0">
                <div class="card-body">
                    <div class="row">
                        <div class="col">
                            <h5 class="card-title text-uppercase text-muted mb-0"><?= __('today_sales') ?></h5>
                            <span class="h2 font-weight-bold mb-0"><?= number_format($stats['today_sales']) ?></span>
                        </div>
                        <div class="col-auto">
                            <div class="icon icon-shape bg-yellow text-white rounded-circle shadow">
                                <i class="fas fa-chart-line"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-lg-6">
            <div class="card card-stats mb-4 mb-xl-0">
                <div class="card-body">
                    <div class="row">
                        <div class="col">
                            <h5 class="card-title text-uppercase text-muted mb-0"><?= __('today') ?>
                                <?= __('total_revenue') ?>
                            </h5>
                            <span
                                class="h2 font-weight-bold mb-0"><?= number_format($stats['today_revenue'] ?? 0) ?></span>
                        </div>
                        <div class="col-auto">
                            <div class="icon icon-shape bg-info text-white rounded-circle shadow">
                                <i class="fas fa-coins"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row mt-5">
        <div class="col-xl-8">
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

                        <h6 class="heading-small text-muted mb-4"><?= __('shop_information') ?></h6>
                        <div class="pl-lg-4">
                            <div class="row">
                                <div class="col-lg-6">
                                    <div class="form-group">
                                        <label class="form-control-label"><?= __('shop_name') ?></label>
                                        <input type="text" name="shop_name" required
                                            class="form-control form-control-alternative"
                                            value="<?= sanitizeOutput($current_settings['shop_name'] ?? 'فروشگاه قطعات موتور') ?>"
                                            placeholder="نام فروشگاه را وارد کنید">
                                    </div>
                                </div>
                                <div class="col-lg-6">
                                    <div class="form-group">
                                        <label class="form-control-label"><?= __('shop_phone') ?></label>
                                        <input type="text" name="shop_phone"
                                            class="form-control form-control-alternative"
                                            value="<?= sanitizeOutput($current_settings['shop_phone'] ?? '021-12345678') ?>"
                                            placeholder="شماره تماس">
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-lg-12">
                                    <div class="form-group">
                                        <label class="form-control-label"><?= __('shop_address') ?></label>
                                        <textarea name="shop_address" class="form-control form-control-alternative"
                                            rows="3"
                                            placeholder="آدرس کامل فروشگاه"><?= sanitizeOutput($current_settings['shop_address'] ?? 'تهران، خیابان اصلی، پلاک 123') ?></textarea>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-lg-6">
                                    <div class="form-group">
                                        <label class="form-control-label">لوگوی فروشگاه</label>
                                        <input type="file" name="shop_logo"
                                            class="form-control form-control-alternative"
                                            accept="image/jpeg,image/png,image/gif,image/webp">
                                        <small class="form-text text-muted">فرمتهای مجاز: JPG, PNG, GIF, WEBP</small>
                                    </div>
                                </div>
                                <div class="col-lg-6">
                                    <?php if (!empty($current_settings['shop_logo']) && file_exists($current_settings['shop_logo'])): ?>
                                        <div class="form-group">
                                            <label class="form-control-label">لوگوی فعلی</label>
                                            <div class="current-logo">
                                                <img src="<?= $current_settings['shop_logo'] ?>" alt="لوگوی فروشگاه"
                                                    style="max-width: 100px; max-height: 100px; border-radius: 8px; border: 2px solid #e5e7eb;">
                                            </div>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>

                        <hr class="my-4">

                        <h6 class="heading-small text-muted mb-4">تنظیمات ظاهری</h6>
                        <div class="pl-lg-4">
                            <div class="row">
                                <div class="col-lg-6">
                                    <div class="form-group">
                                        <label class="form-control-label">رنگ قالب</label>
                                        <select name="theme_color" class="form-control form-control-alternative">
                                            <option value="blue" <?= ($current_settings['theme_color'] ?? 'blue') == 'blue' ? 'selected' : '' ?>>آبی</option>
                                            <option value="green" <?= ($current_settings['theme_color'] ?? 'blue') == 'green' ? 'selected' : '' ?>>سبز</option>
                                            <option value="purple" <?= ($current_settings['theme_color'] ?? 'blue') == 'purple' ? 'selected' : '' ?>>بنفش</option>
                                            <option value="red" <?= ($current_settings['theme_color'] ?? 'blue') == 'red' ? 'selected' : '' ?>>قرمز</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-lg-6">
                                    <div class="form-group">
                                        <label class="form-control-label">چیدمان داشبورد</label>
                                        <select name="dashboard_layout" class="form-control form-control-alternative">
                                            <option value="modern" <?= ($current_settings['dashboard_layout'] ?? 'modern') == 'modern' ? 'selected' : '' ?>>مدرن</option>
                                            <option value="classic" <?= ($current_settings['dashboard_layout'] ?? 'modern') == 'classic' ? 'selected' : '' ?>>کلاسیک</option>
                                            <option value="compact" <?= ($current_settings['dashboard_layout'] ?? 'modern') == 'compact' ? 'selected' : '' ?>>فشرده</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-lg-6">
                                    <div class="form-group">
                                        <div class="form-check">
                                            <input class="form-check-input" name="show_animations" id="show_animations"
                                                type="checkbox" <?= ($current_settings['show_animations'] ?? '1') == '1' ? 'checked' : '' ?>>
                                            <label class="form-check-label" for="show_animations">
                                                <span class="text-muted">نمایش انیمیشنها</span>
                                            </label>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-lg-6">
                                    <div class="form-group">
                                        <div class="form-check">
                                            <input class="form-check-input" name="compact_mode" id="compact_mode"
                                                type="checkbox" <?= ($current_settings['compact_mode'] ?? '0') == '1' ? 'checked' : '' ?>>
                                            <label class="form-check-label" for="compact_mode">
                                                <span class="text-muted">حالت فشرده</span>
                                            </label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <hr class="my-4">

                        <h6 class="heading-small text-muted mb-4">تنظیمات کسب وکار</h6>
                        <div class="pl-lg-4">
                            <div class="row">
                                <div class="col-lg-6">
                                    <div class="form-group">
                                        <label class="form-control-label">حداقل درصد فایده (%)</label>
                                        <input type="number" name="min_profit_margin" class="form-control form-control-alternative"
                                            value="<?= sanitizeOutput($current_settings['min_profit_margin'] ?? '5') ?>"
                                            min="0" max="100" step="0.1" placeholder="5">
                                        <small class="form-text text-muted">حداقل درصد فایده نسبت به قیمت خرید</small>
                                    </div>
                                </div>
                                <div class="col-lg-6">
                                    <div class="form-group">
                                        <label class="form-control-label">حد نهایت موجودی کم</label>
                                        <input type="number" name="low_stock_threshold" class="form-control form-control-alternative"
                                            value="<?= sanitizeOutput($current_settings['low_stock_threshold'] ?? '5') ?>"
                                            min="1" placeholder="5">
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-lg-6">
                                    <div class="form-group">
                                        <label class="form-control-label">پیشوند فاکتور</label>
                                        <input type="text" name="invoice_prefix" class="form-control form-control-alternative"
                                            value="<?= sanitizeOutput($current_settings['invoice_prefix'] ?? 'INV') ?>"
                                            placeholder="INV">
                                    </div>
                                </div>
                                <div class="col-lg-6">
                                    <div class="form-group">
                                        <div class="form-check">
                                            <input class="form-check-input" name="low_stock_alerts" id="low_stock_alerts"
                                                type="checkbox" <?= ($current_settings['low_stock_alerts'] ?? '1') == '1' ? 'checked' : '' ?>>
                                            <label class="form-check-label" for="low_stock_alerts">
                                                <span class="text-muted">هشدار موجودی کم</span>
                                            </label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <hr class="my-4">

                        <h6 class="heading-small text-muted mb-4"><?= __('system_settings') ?></h6>
                        <div class="pl-lg-4">
                            <div class="row">
                                <div class="col-lg-6">
                                    <div class="form-group">
                                        <label class="form-control-label"><?= __('currency') ?></label>
                                        <select name="currency" class="form-control form-control-alternative">
                                            <option value="afghani" <?= ($current_settings['currency'] ?? 'afghani') == 'afghani' ? 'selected' : '' ?>><?= __('afghani') ?></option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-lg-6">
                                    <div class="form-group">
                                        <label class="form-control-label"><?= __('language') ?></label>
                                        <select name="language" id="languageSelect" class="form-control form-control-alternative" onchange="changeLanguage(this.value)">
                                            <option value="fa" <?= ($current_settings['language'] ?? 'fa') == 'fa' ? 'selected' : '' ?>><?= __('persian') ?></option>
                                            <option value="ps" <?= ($current_settings['language'] ?? 'fa') == 'ps' ? 'selected' : '' ?>><?= __('pashto') ?></option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-lg-6">
                                    <div class="form-group">
                                        <label class="form-control-label"><?= __('date_format_label') ?></label>
                                        <select name="date_format" class="form-control form-control-alternative">
                                            <option value="gregorian" <?= ($current_settings['date_format'] ?? 'gregorian') == 'gregorian' ? 'selected' : '' ?>><?= __('gregorian') ?>
                                            </option>
                                            <option value="jalali" <?= ($current_settings['date_format'] ?? 'gregorian') == 'jalali' ? 'selected' : '' ?>><?= __('solar_hijri') ?>
                                            </option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-lg-6">
                                    <div class="form-group">
                                        <div class="form-check">
                                            <input class="form-check-input" name="auto_backup" id="backup_auto"
                                                type="checkbox" <?= ($current_settings['auto_backup'] ?? '1') == '1' ? 'checked' : '' ?>>
                                            <label class="form-check-label" for="backup_auto">
                                                <span class="text-muted"><?= __('automatic_backup') ?></span>
                                            </label>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-lg-6">
                                    <div class="form-group">
                                        <div class="form-check">
                                            <input class="form-check-input" name="notifications" id="notifications"
                                                type="checkbox" <?= ($current_settings['notifications'] ?? '1') == '1' ? 'checked' : '' ?>>
                                            <label class="form-check-label" for="notifications">
                                                <span class="text-muted"><?= __('system_notifications') ?></span>
                                            </label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <hr class="my-4">
                        <div class="pl-lg-4">
                            <div class="row">
                                <div class="col text-center">
                                    <button type="submit" class="btn btn-primary"><?= __('save_settings') ?></button>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-xl-4">
            <div class="card shadow">
                <div class="card-header border-0">
                    <h3 class="mb-0"><?= __('system_operations') ?></h3>
                </div>
                <div class="card-body">
                    <div class="list-group list-group-flush">
                        <a href="backup.php" class="list-group-item list-group-item-action">
                            <div class="row align-items-center">
                                <div class="col-auto">
                                    <i class="fas fa-download text-primary"></i>
                                </div>
                                <div class="col ml--2">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <h4 class="mb-0 text-sm"><?= __('backup') ?></h4>
                                        </div>
                                    </div>
                                    <p class="text-sm text-muted mb-0"><?= __('create_backup') ?></p>
                                </div>
                            </div>
                        </a>
                        <a href="users.php" class="list-group-item list-group-item-action">
                            <div class="row align-items-center">
                                <div class="col-auto">
                                    <i class="fas fa-users text-warning"></i>
                                </div>
                                <div class="col ml--2">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <h4 class="mb-0 text-sm"><?= __('user_management') ?></h4>
                                        </div>
                                    </div>
                                    <p class="text-sm text-muted mb-0"><?= __('add') ?> <?= __('manage') ?>
                                        <?= __('users') ?>
                                    </p>
                                </div>
                            </div>
                        </a>
                        <a href="reports.php" class="list-group-item list-group-item-action">
                            <div class="row align-items-center">
                                <div class="col-auto">
                                    <i class="fas fa-chart-bar text-success"></i>
                                </div>
                                <div class="col ml--2">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <h4 class="mb-0 text-sm"><?= __('reports') ?></h4>
                                        </div>
                                    </div>
                                    <p class="text-sm text-muted mb-0"><?= __('view') ?> <?= __('sales_reports') ?></p>
                                </div>
                            </div>
                        </a>
                    </div>
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
<?php include 'includes/footer.php'; ?>