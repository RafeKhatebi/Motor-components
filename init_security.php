<?php
// Load enhanced security
if (file_exists(__DIR__ . '/includes/security_enhanced.php')) {
    require_once __DIR__ . '/includes/security_enhanced.php';
    SecurityEnhanced::initSecureSession();
} else {
    // Fallback to basic session security
    if (session_status() == PHP_SESSION_NONE) {
        ini_set('session.cookie_httponly', '1');
        ini_set('session.cookie_secure', isset($_SERVER['HTTPS']) ? '1' : '0');
        ini_set('session.cookie_samesite', 'Strict');
        ini_set('session.use_strict_mode', '1');
        ini_set('session.use_only_cookies', '1');
        ini_set('session.gc_maxlifetime', 1800);
        session_start();
        
        if (!isset($_SESSION['last_regeneration'])) {
            $_SESSION['last_regeneration'] = time();
        } elseif (time() - $_SESSION['last_regeneration'] > 300) {
            session_regenerate_id(true);
            $_SESSION['last_regeneration'] = time();
        }
        
        if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > 1800)) {
            session_unset();
            session_destroy();
            if (isset($_SESSION['user_id'])) {
                header('Location: login.php?timeout=1');
                exit();
            }
        }
        $_SESSION['last_activity'] = time();
    }
}

// Other security settings
ini_set('display_errors', '0');
ini_set('log_errors', '1');

// Security headers function
function setSecurityHeaders()
{
    if (!headers_sent()) {
        header('X-Frame-Options: DENY');
        header('X-Content-Type-Options: nosniff');
        header('X-XSS-Protection: 1; mode=block');
        header('Referrer-Policy: strict-origin-when-cross-origin');
        header('Permissions-Policy: geolocation=(), microphone=(), camera=()');
    }
}

// Auto-call security headers
setSecurityHeaders();

// Load security helpers
if (file_exists(__DIR__ . '/includes/csrf_helper.php')) {
    require_once __DIR__ . '/includes/csrf_helper.php';
}
if (file_exists(__DIR__ . '/includes/validator.php')) {
    require_once __DIR__ . '/includes/validator.php';
}
if (file_exists(__DIR__ . '/includes/file_upload.php')) {
    require_once __DIR__ . '/includes/file_upload.php';
}
if (file_exists(__DIR__ . '/includes/error_handler.php')) {
    require_once __DIR__ . '/includes/error_handler.php';
}
?>