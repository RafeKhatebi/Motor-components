<?php
/**
 * Environment Configuration
 */

// Define environment (development, production, testing)
define('ENVIRONMENT', 'development'); // Change to 'production' for live site

// Database configuration based on environment
switch (ENVIRONMENT) {
    case 'development':
        define('DB_HOST', 'localhost');
        define('DB_NAME', 'motor');
        define('DB_USER', 'root');
        define('DB_PASS', '');
        define('DB_CHARSET', 'utf8mb4');

        // Development settings
        define('DEBUG_MODE', true);
        define('LOG_ERRORS', true);
        define('DISPLAY_ERRORS', true);
        break;

    case 'production':
        define('DB_HOST', 'localhost');
        define('DB_NAME', 'motor_prod');
        define('DB_USER', 'motor_user');
        define('DB_PASS', 'secure_password_here');
        define('DB_CHARSET', 'utf8mb4');

        // Production settings
        define('DEBUG_MODE', false);
        define('LOG_ERRORS', true);
        define('DISPLAY_ERRORS', false);
        break;

    case 'testing':
        define('DB_HOST', 'localhost');
        define('DB_NAME', 'motor_test');
        define('DB_USER', 'root');
        define('DB_PASS', '');
        define('DB_CHARSET', 'utf8mb4');

        // Testing settings
        define('DEBUG_MODE', true);
        define('LOG_ERRORS', true);
        define('DISPLAY_ERRORS', true);
        break;
}

// Security settings
define('ENCRYPTION_KEY', 'your-32-character-secret-key-here'); // Change this!
define('SESSION_TIMEOUT', 1800); // 30 minutes
define('MAX_LOGIN_ATTEMPTS', 5);
define('LOGIN_LOCKOUT_TIME', 900); // 15 minutes

// File upload settings
define('MAX_UPLOAD_SIZE', 5 * 1024 * 1024); // 5MB
define('UPLOAD_PATH', 'uploads/');
define('ALLOWED_IMAGE_TYPES', ['image/jpeg', 'image/png', 'image/gif', 'image/webp']);

// Application settings
define('APP_NAME', 'سیستم مدیریت فروشگاه موتورسیکلت');
define('APP_VERSION', '2.0.0');
define('TIMEZONE', 'Asia/Kabul');

// Set timezone
date_default_timezone_set(TIMEZONE);
?>