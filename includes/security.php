<?php
class SecurityHelper {
    
    private static $allowedIncludes = [
        'header.php',
        'footer.php',
        'auth.php',
        'functions.php',
        'SettingsHelper.php',
        'LanguageHelper.php',
        'permissions.php',
        'business_rules.php',
        'audit_logger.php'
    ];
    
    public static function validateIncludePath($path) {
        $filename = basename($path);
        
        if (!in_array($filename, self::$allowedIncludes)) {
            throw new Exception('Unauthorized file access attempt');
        }
        
        if (strpos($path, '..') !== false) {
            throw new Exception('Path traversal attempt detected');
        }
        
        return true;
    }
    
    public static function sanitizeFilename($filename) {
        return preg_replace('/[^a-zA-Z0-9_\-\.]/', '', $filename);
    }
}
?>