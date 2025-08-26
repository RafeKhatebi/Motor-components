<?php
/**
 * PHP OPcache Preload Script
 * بارگذاری پیشین فایلهای پرکاربرد
 */

// فایلهای اصلی سیستم
$preloadFiles = [
    __DIR__ . '/../config/database.php',
    __DIR__ . '/../includes/functions.php',
    __DIR__ . '/../includes/SettingsHelper.php',
    __DIR__ . '/../includes/LanguageHelper.php',
    __DIR__ . '/../includes/DateHelper.php',
    __DIR__ . '/../includes/auth.php',
    __DIR__ . '/../includes/permissions.php',
    __DIR__ . '/../includes/security_enhanced.php',
    __DIR__ . '/../includes/SimpleCache.php',
    __DIR__ . '/../includes/QueryOptimizer.php'
];

foreach ($preloadFiles as $file) {
    if (file_exists($file)) {
        opcache_compile_file($file);
    }
}
?>