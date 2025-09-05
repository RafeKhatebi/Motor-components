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

    <!-- Favicon -->
    <link rel="icon" href="assets/img/brand/favicon.png" type="image/png">
    <!-- Icons -->
    <link rel="stylesheet" href="assets/css/all.min.css">
    
    <!-- Unified Design System -->
    <link rel="stylesheet" href="assets/css/unified-system.css">
    <!-- Theme Enhancements -->
    <link rel="stylesheet" href="assets/css/theme-enhancements.css">
    <?= $extra_css ?? '' ?>
</head>

<body data-date-format="<?= SettingsHelper::getSetting('date_format', 'gregorian') ?>"
    data-page="<?= basename($_SERVER['PHP_SELF'], '.php') ?>">
    <div class="app-layout">
        <!-- Sidebar -->
        <aside class="sidebar" id="sidebar">
            <div class="sidebar-header">
                <a class="sidebar-brand" href="dashboard.php">
                    <?php
                    $shop_name = SettingsHelper::getShopName();
                    if (SettingsHelper::hasCustomLogo()):
                        $shop_logo = SettingsHelper::getShopLogo();
                        ?>
                        <img src="<?= $shop_logo ?>" alt="<?= sanitizeOutput($shop_name) ?>">
                    <?php else: ?>
                        <i class="fas fa-motorcycle"></i>
                    <?php endif; ?>
                    <span><?= sanitizeOutput($shop_name) ?></span>
                </a>
            </div>
            
            <nav class="sidebar-nav">
                <div class="nav-section">
                    <div class="nav-item">
                        <a class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : '' ?>" href="dashboard.php">
                            <i class="fas fa-tachometer-alt"></i>
                            <span>داشبورد</span>
                        </a>
                    </div>
                </div>

                <div class="nav-section">
                    <div class="nav-item">
                        <div class="nav-link nav-parent" data-dropdown="inventory">
                            <div>
                                <i class="fas fa-warehouse"></i>
                                <span>گدام</span>
                            </div>
                            <i class="fas fa-chevron-left nav-arrow"></i>
                        </div>
                        <div class="nav-dropdown" id="inventory">
                            <a class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'products.php' ? 'active' : '' ?>" href="products.php">
                                <i class="fas fa-box"></i>
                                <span>اجناس</span>
                            </a>
                            <a class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'categories.php' ? 'active' : '' ?>" href="categories.php">
                                <i class="fas fa-tags"></i>
                                <span>دسته بندی</span>
                            </a>
                        </div>
                    </div>
                </div>

                <div class="nav-section">
                    <div class="nav-item">
                        <div class="nav-link nav-parent" data-dropdown="transactions">
                            <div>
                                <i class="fas fa-exchange-alt"></i>
                                <span>معاملات</span>
                            </div>
                            <i class="fas fa-chevron-left nav-arrow"></i>
                        </div>
                        <div class="nav-dropdown" id="transactions">
                            <a class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'sales.php' ? 'active' : '' ?>" href="sales.php">
                                <i class="fas fa-shopping-cart"></i>
                                <span>فروش</span>
                            </a>
                            <a class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'purchases.php' ? 'active' : '' ?>" href="purchases.php">
                                <i class="fas fa-shopping-bag"></i>
                                <span>خرید</span>
                            </a>
                            <a class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'transactions.php' ? 'active' : '' ?>" href="transactions.php">
                                <i class="fas fa-money-bill-wave"></i>
                                <span>مصارف</span>
                            </a>
                        </div>
                    </div>
                </div>

                <div class="nav-section">
                    <div class="nav-item">
                        <div class="nav-link nav-parent" data-dropdown="relations">
                            <div>
                                <i class="fas fa-users"></i>
                                <span>روابط</span>
                            </div>
                            <i class="fas fa-chevron-left nav-arrow"></i>
                        </div>
                        <div class="nav-dropdown" id="relations">
                            <a class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'customers.php' ? 'active' : '' ?>" href="customers.php">
                                <i class="fas fa-user-friends"></i>
                                <span>مشتریان</span>
                            </a>
                            <a class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'suppliers.php' ? 'active' : '' ?>" href="suppliers.php">
                                <i class="fas fa-building"></i>
                                <span>تأمین کنندگان</span>
                            </a>
                            <a class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'debts.php' ? 'active' : '' ?>" href="debts.php">
                                <i class="fas fa-money-bill-wave"></i>
                                <span>قرض ها</span>
                            </a>
                        </div>
                    </div>
                </div>

                <div class="nav-section">
                    <div class="nav-item">
                        <a class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'reports.php' ? 'active' : '' ?>" href="reports.php">
                            <i class="fas fa-chart-line"></i>
                            <span>گزارشات</span>
                        </a>
                    </div>
                </div>

                <?php if ($_SESSION['role'] === 'admin'): ?>
                <div class="nav-section">
                    <div class="nav-item">
                        <div class="nav-link nav-parent" data-dropdown="management">
                            <div>
                                <i class="fas fa-cog"></i>
                                <span>مدیریت</span>
                            </div>
                            <i class="fas fa-chevron-left nav-arrow"></i>
                        </div>
                        <div class="nav-dropdown" id="management">
                            <a class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'users.php' ? 'active' : '' ?>" href="users.php">
                                <i class="fas fa-users-cog"></i>
                                <span>کاربران</span>
                            </a>
                            <a class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'backup.php' ? 'active' : '' ?>" href="backup.php">
                                <i class="fas fa-database"></i>
                                <span>پشتیبان</span>
                            </a>
                            <a class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'settings.php' ? 'active' : '' ?>" href="settings.php">
                                <i class="fas fa-sliders-h"></i>
                                <span>تنظیمات</span>
                            </a>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
                <div class="nav-section">
                    <div class="nav-item">
                        <div class="nav-link nav-parent" data-dropdown="user">
                            <div>
                                <i class="fas fa-user-circle"></i>
                                <span><?= sanitizeOutput($_SESSION['username']) ?></span>
                            </div>
                            <i class="fas fa-chevron-left nav-arrow"></i>
                        </div>
                        <div class="nav-dropdown" id="user">
                            <a class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'profile.php' ? 'active' : '' ?>" href="profile.php">
                                <i class="fas fa-user"></i>
                                <span>پروفایل</span>
                            </a>
                            <a class="nav-link" href="#" onclick="confirmLogout()">
                                <i class="fas fa-sign-out-alt"></i>
                                <span>خروج</span>
                            </a>
                        </div>
                    </div>
                </div>
            </nav>
        </aside>
        
        <div class="sidebar-overlay" id="sidebarOverlay"></div>

    <!-- نمایش هشدار لایسنس -->
    <?php if (function_exists('showLicenseWarning'))
        showLicenseWarning(); ?>

        <!-- Main Content -->
        <main class="main-content">
            <div class="topbar">
                <button class="sidebar-toggle" id="sidebarToggle">
                    <i class="fas fa-bars"></i>
                </button>
                <div class="user-menu">
                    <div class="user-avatar">
                        <?= strtoupper(substr($_SESSION['username'], 0, 1)) ?>
                    </div>
                    <span><?= sanitizeOutput($_SESSION['username']) ?></span>
                </div>
            </div>
            <div class="content-wrapper">