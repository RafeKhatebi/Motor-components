<?php
/**
 * Enhanced Error Handling
 */
class ErrorHandler
{

    public static function init()
    {
        // Set error reporting based on environment
        if (defined('ENVIRONMENT') && ENVIRONMENT === 'development') {
            error_reporting(E_ALL);
            ini_set('display_errors', 1);
        } else {
            error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);
            ini_set('display_errors', 0);
        }

        // Set custom error handlers
        set_error_handler([self::class, 'handleError']);
        set_exception_handler([self::class, 'handleException']);
        register_shutdown_function([self::class, 'handleFatalError']);
    }

    public static function handleError($severity, $message, $file, $line)
    {
        if (!(error_reporting() & $severity)) {
            return false;
        }

        $error = [
            'type' => 'Error',
            'severity' => $severity,
            'message' => $message,
            'file' => $file,
            'line' => $line,
            'time' => date('Y-m-d H:i:s')
        ];

        self::logError($error);

        if (defined('ENVIRONMENT') && ENVIRONMENT === 'development') {
            self::displayError($error);
        } else {
            self::displayUserFriendlyError();
        }

        return true;
    }

    public static function handleException($exception)
    {
        $error = [
            'type' => 'Exception',
            'message' => $exception->getMessage(),
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
            'trace' => $exception->getTraceAsString(),
            'time' => date('Y-m-d H:i:s')
        ];

        self::logError($error);

        if (defined('ENVIRONMENT') && ENVIRONMENT === 'development') {
            self::displayError($error);
        } else {
            self::displayUserFriendlyError();
        }
    }

    public static function handleFatalError()
    {
        $error = error_get_last();
        if ($error && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
            self::logError($error);
            if (!(defined('ENVIRONMENT') && ENVIRONMENT === 'development')) {
                self::displayUserFriendlyError();
            }
        }
    }

    private static function logError($error)
    {
        $logFile = 'logs/error_' . date('Y-m-d') . '.log';
        $logDir = dirname($logFile);

        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }

        $logMessage = sprintf(
            "[%s] %s: %s in %s on line %d\n",
            $error['time'],
            $error['type'] ?? 'Error',
            $error['message'],
            $error['file'] ?? 'unknown',
            $error['line'] ?? 0
        );

        error_log($logMessage, 3, $logFile);
    }

    private static function displayError($error)
    {
        echo "<div style='background: #f8d7da; color: #721c24; padding: 15px; margin: 10px; border: 1px solid #f5c6cb; border-radius: 5px;'>";
        echo "<strong>{$error['type']}:</strong> {$error['message']}<br>";
        echo "<strong>File:</strong> {$error['file']}<br>";
        echo "<strong>Line:</strong> {$error['line']}<br>";
        if (isset($error['trace'])) {
            echo "<details><summary>Stack Trace</summary><pre>{$error['trace']}</pre></details>";
        }
        echo "</div>";
    }

    private static function displayUserFriendlyError()
    {
        if (!headers_sent()) {
            http_response_code(500);
        }

        if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest') {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'خطای داخلی سرور. لطفاً دوباره تلاش کنید.']);
        } else {
            echo '<!DOCTYPE html><html><head><title>خطا</title><meta charset="utf-8"></head><body>';
            echo '<div style="text-align: center; padding: 50px; font-family: Tahoma;">';
            echo '<h2>متأسفانه خطایی رخ داده است</h2>';
            echo '<p>لطفاً چند لحظه دیگر دوباره تلاش کنید.</p>';
            echo '<a href="javascript:history.back()">بازگشت</a>';
            echo '</div></body></html>';
        }
        exit();
    }
}

// Initialize error handler
if (!defined('ENVIRONMENT')) {
    define('ENVIRONMENT', 'production'); // تغییر به development برای دیباگ
}
ErrorHandler::init();
?>