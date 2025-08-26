<?php
// Validate and sanitize file paths to prevent directory traversal attacks
$allowed_files = [
    'init_security.php' => 'init_security.php',
    'includes/functions.php' => 'includes/functions.php',
    'includes/license_check.php' => 'includes/license_check.php'
];

foreach ($allowed_files as $file) {
    if (!preg_match('/^[a-zA-Z0-9_\-\/]+\.php$/', $file) || strpos($file, '..') !== false) {
        die('Invalid file path');
    }
    require_once $file;
}
?>
<!DOCTYPE html>
<html lang="<?= LanguageHelper::getCurrentLanguage() ?>" dir="<?= LanguageHelper::getDirection() ?>">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title><?= sanitizeOutput($page_title ?? SettingsHelper::getShopName()) ?> -
        <?= sanitizeOutput(SettingsHelper::getShopName()) ?></title>

    <!-- Custom RTL Fixes -->
    <link rel="stylesheet" href="assets/css/custom-rtl-fixes.css">
    <!-- Favicon -->
    <link rel="icon" href="assets/img/brand/favicon.png" type="image/png">
    <!-- Icons -->
    <link rel="stylesheet" href="assets/css/all.min.css">
    <!-- Bootstrap RTL CSS -->
    <link rel="stylesheet" href="assets/css/bootstrap.rtl.min.css">
    <!-- Bootstrap RTL CSS -->
    <link rel="stylesheet" href="assets/css/argon-dashboard-rtl.css">

    <!-- Modernize theme: Persian-friendly font and visual overrides -->
    <link rel="stylesheet" href="assets/css/modernize-rtl.css">
    <link rel="stylesheet" href="assets/css/notifications.css">
    <link rel="stylesheet" href="assets/css/professional-buttons.css">

    <!-- Header Styles -->
    <link rel="stylesheet" href="assets/css/header-styles.css">
    <!-- Header Improvements -->
    <link rel="stylesheet" href="assets/css/header-improvements.css">
    <!-- Logo Styles -->
    <link rel="stylesheet" href="assets/css/logo-styles.css">
    <!-- Footer Minimal -->
    <link rel="stylesheet" href="assets/css/footer-minimal.css">
    <!-- Persian DatePicker -->
    <link rel="stylesheet" href="assets/css/persian-datepicker.css">
    <!-- Responsive Fixes Clean -->
    <link rel="stylesheet" href="assets/css/responsive-fixes-clean.css">
    <!-- Smart Forms and Settings -->
    <link rel="stylesheet" href="assets/css/smart-forms.css">
    <!-- Compact DateTime -->
    <link rel="stylesheet" href="assets/css/datetime-compact.css">
    <!-- Compact Forms -->
    <link rel="stylesheet" href="assets/css/compact-forms.css">
    <?= $extra_css ?? '' ?>
</head>

<body data-date-format="<?= SettingsHelper::getSetting('date_format', 'gregorian') ?>"
    data-page="<?= basename($_SERVER['PHP_SELF'], '.php') ?>">
    <!-- Top Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark navbar-modern">
        <div class="container-fluid">
            <!-- Brand -->
            <a class="navbar-brand" href="dashboard.php">
                <?php
                $shop_name = SettingsHelper::getShopName();
                if (SettingsHelper::hasCustomLogo()):
                    $shop_logo = SettingsHelper::getShopLogo();
                    ?>
                    <img src="<?= $shop_logo ?>" alt="<?= sanitizeOutput($shop_name) ?>"
                        style="height: 32px; width: auto; max-width: 40px; border-radius: 6px;">
                <?php else: ?>
                    <i class="fas fa-motorcycle" style="color: #fff;"></i>
                <?php endif; ?>
                <span class="ms-2" style="color: #fff !important;"><?= sanitizeOutput($shop_name) ?></span>
            </a>

            <!-- Toggle button for mobile -->
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav"
                aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>

            <!-- Navigation Links -->
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <!-- داشبورد -->
                    <li class="nav-item">
                        <a class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : '' ?>"
                            href="dashboard.php">
                            <i class="fas fa-tachometer-alt"></i>
                            <span>داشبورد</span>
                        </a>
                    </li>

                    <!-- مدیریت گدام -->
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                            <i class="fas fa-warehouse"></i>
                            <span>گدام</span>
                        </a>
                        <ul class="dropdown-menu nav-dropdown">
                            <li><a class="dropdown-item" href="products.php">
                                    <i class="fas fa-box"></i>
                                    <span>اجناس</span>
                                </a></li>
                            <li><a class="dropdown-item" href="categories.php">
                                    <i class="fas fa-tags"></i>
                                    <span>دسته بندی ها</span>
                                </a></li>
                        </ul>
                    </li>

                    <!-- فروش و خرید -->
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                            <i class="fas fa-exchange-alt"></i>
                            <span>معاملات</span>
                        </a>
                        <ul class="dropdown-menu nav-dropdown">
                            <li><a class="dropdown-item" href="sales.php">
                                    <i class="fas fa-shopping-cart"></i>
                                    <span>فروش</span>
                                </a></li>
                            <li><a class="dropdown-item" href="purchases.php">
                                    <i class="fas fa-shopping-bag"></i>
                                    <span>خرید</span>
                                </a></li>
                            <li><a class="dropdown-item" href="transactions.php">
                                    <i class="fas fa-money-bill-wave"></i>
                                    <span>مصارف و برداشت ها</span>
                                </a></li>
                        </ul>
                    </li>

                    <!-- مشتریان و تأمینک نندگان -->
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                            <i class="fas fa-users"></i>
                            <span>روابط</span>
                        </a>
                        <ul class="dropdown-menu nav-dropdown">
                            <li><a class="dropdown-item" href="customers.php">
                                    <i class="fas fa-user-friends"></i>
                                    <span>مشتریان</span>
                                </a></li>
                            <li><a class="dropdown-item" href="suppliers.php">
                                    <i class="fas fa-building"></i>
                                    <span>تأمین کنندگان</span>
                                </a></li>
                            <li><a class="dropdown-item" href="debts.php">
                                    <i class="fas fa-money-bill-wave"></i>
                                    <span>قرض ها و طلب ها</span>
                                </a></li>
                        </ul>
                    </li>

                    <!-- گزارشات -->
                    <li class="nav-item">
                        <a class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'reports.php' ? 'active' : '' ?>"
                            href="reports.php">
                            <i class="fas fa-chart-line"></i>
                            <span>گزارشات</span>
                        </a>
                    </li>

                    <!-- مدیریت سیستم (فقط برای ادمین) -->
                    <?php if ($_SESSION['role'] === 'admin'): ?>
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle admin-menu" href="#" role="button" data-bs-toggle="dropdown">
                                <i class="fas fa-cog"></i>
                                <span>مدیریت</span>
                                <span class="admin-badge">مدیر</span>
                            </a>
                            <ul class="dropdown-menu nav-dropdown">
                                <li><a class="dropdown-item" href="users.php">
                                        <i class="fas fa-users-cog"></i>
                                        <span>کاربران</span>
                                    </a></li>
                                <li><a class="dropdown-item" href="backup.php">
                                        <i class="fas fa-database"></i>
                                        <span>پشتیبان گیری</span>
                                    </a></li>
                                <li><a class="dropdown-item" href="settings.php">
                                        <i class="fas fa-sliders-h"></i>
                                        <span>تنظیمات</span>
                                    </a></li>
                            </ul>
                        </li>
                    <?php endif; ?>
                </ul>

                <!-- قسمت کاربر -->
                <ul class="navbar-nav">
                    <li class="nav-item dropdown nav-section">
                        <!-- <div class="nav-section-title d-none d-lg-block">کاربر</div> -->
                        <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                            <i class="fas fa-user-circle"></i>
                            <span><?= sanitizeOutput($_SESSION['username']) ?></span>
                        </a>
                        <ul class="dropdown-menu nav-dropdown dropdown-menu-end">
                            <li><a class="dropdown-item" href="profile.php">
                                    <i class="fas fa-user"></i>
                                    <span>پروفایل من</span>
                                </a></li>
                            <li><a class="dropdown-item" href="user_guide.php">
                                    <i class="fas fa-cog"></i>
                                    <span>راهنمای</span></span>
                                </a></li>
                            <li>
                                <hr class="dropdown-divider">
                            </li>
                            <li><a class="dropdown-item" href="logout.php">
                                    <i class="fas fa-sign-out-alt"></i>
                                    <span>خروج</span>
                                </a></li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- نمایش هشدار لایسنس -->
    <?php if (function_exists('showLicenseWarning'))
        showLicenseWarning(); ?>

    <!-- تاریخ و ساعت دری -->
    <div class="dari-datetime" id="dari-datetime">
        <!-- محتوا توسط JavaScript پر میشود -->
    </div>

    <!-- Main content -->
    <div class="main-content-navbar">

        <!-- JavaScript برای تاریخ و ساعت -->
        <script src="assets/js/datetime.js"></script>
        <!-- سیستم اعلانات -->
        <script src="assets/js/notifications.js"></script>
        <!-- Persian DatePicker -->
        <script src="assets/js/persian-datepicker.js"></script>
        <!-- Date Converter -->
        <script src="assets/js/date-converter.js"></script>
        <!-- Smart Forms and Settings -->
        <script src="assets/js/smart-forms.js"></script>