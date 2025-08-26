<?php
/**
 * Enhanced Security Helper
 */
class SecurityEnhanced {
    
    // Safe input sanitization replacement for deprecated FILTER_SANITIZE_STRING
    public static function sanitizeString($input) {
        if ($input === null) return '';
        return htmlspecialchars(strip_tags(trim($input)), ENT_QUOTES, 'UTF-8');
    }
    
    // Enhanced CSRF protection
    public static function generateCSRFToken() {
        if (!isset($_SESSION['csrf_token']) || !isset($_SESSION['csrf_token_time']) || 
            (time() - $_SESSION['csrf_token_time']) > 3600) { // 1 hour expiry
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
            $_SESSION['csrf_token_time'] = time();
        }
        return $_SESSION['csrf_token'];
    }
    
    public static function verifyCSRFToken($token) {
        return isset($_SESSION['csrf_token']) && 
               isset($_SESSION['csrf_token_time']) &&
               (time() - $_SESSION['csrf_token_time']) <= 3600 &&
               hash_equals($_SESSION['csrf_token'], $token);
    }
    
    // Rate limiting
    private static $rateLimits = [];
    
    public static function checkRateLimit($action, $limit = 10, $window = 60) {
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $key = $action . '_' . $ip;
        $now = time();
        
        if (!isset(self::$rateLimits[$key])) {
            self::$rateLimits[$key] = ['count' => 1, 'start' => $now];
            return true;
        }
        
        $data = self::$rateLimits[$key];
        
        if (($now - $data['start']) > $window) {
            self::$rateLimits[$key] = ['count' => 1, 'start' => $now];
            return true;
        }
        
        if ($data['count'] >= $limit) {
            return false;
        }
        
        self::$rateLimits[$key]['count']++;
        return true;
    }
    
    // Enhanced session security
    public static function initSecureSession() {
        if (session_status() == PHP_SESSION_NONE) {
            // Enhanced security settings
            ini_set('session.cookie_httponly', '1');
            ini_set('session.cookie_secure', isset($_SERVER['HTTPS']) ? '1' : '0');
            ini_set('session.cookie_samesite', 'Strict');
            ini_set('session.use_strict_mode', '1');
            ini_set('session.use_only_cookies', '1');
            ini_set('session.entropy_length', '32');
            ini_set('session.hash_function', 'sha256');
            ini_set('session.gc_maxlifetime', 1800); // 30 minutes
            
            session_start();
            
            // Session hijacking protection
            if (!isset($_SESSION['user_agent'])) {
                $_SESSION['user_agent'] = $_SERVER['HTTP_USER_AGENT'] ?? '';
            } elseif ($_SESSION['user_agent'] !== ($_SERVER['HTTP_USER_AGENT'] ?? '')) {
                session_destroy();
                header('Location: login.php?error=session_invalid');
                exit();
            }
            
            // Session regeneration
            if (!isset($_SESSION['last_regeneration'])) {
                $_SESSION['last_regeneration'] = time();
            } elseif (time() - $_SESSION['last_regeneration'] > 300) {
                session_regenerate_id(true);
                $_SESSION['last_regeneration'] = time();
            }
        }
    }
    
    // Input validation
    public static function validateInput($data, $rules) {
        $errors = [];
        
        foreach ($rules as $field => $rule) {
            $value = $data[$field] ?? null;
            
            if (isset($rule['required']) && $rule['required'] && empty($value)) {
                $errors[$field] = "فیلد {$field} الزامی است";
                continue;
            }
            
            if (!empty($value)) {
                if (isset($rule['type'])) {
                    switch ($rule['type']) {
                        case 'email':
                            if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
                                $errors[$field] = "فرمت ایمیل نامعتبر است";
                            }
                            break;
                        case 'numeric':
                            if (!is_numeric($value)) {
                                $errors[$field] = "مقدار باید عددی باشد";
                            }
                            break;
                        case 'phone':
                            if (!preg_match('/^[0-9+\-\s()]+$/', $value)) {
                                $errors[$field] = "فرمت شماره تلفن نامعتبر است";
                            }
                            break;
                    }
                }
                
                if (isset($rule['min_length']) && strlen($value) < $rule['min_length']) {
                    $errors[$field] = "حداقل طول {$rule['min_length']} کاراکتر";
                }
                
                if (isset($rule['max_length']) && strlen($value) > $rule['max_length']) {
                    $errors[$field] = "حداکثر طول {$rule['max_length']} کاراکتر";
                }
            }
        }
        
        return $errors;
    }
}
?>