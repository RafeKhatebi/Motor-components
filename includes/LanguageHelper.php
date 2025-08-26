<?php
class LanguageHelper {
    private static $lang = null;
    private static $current_language = 'fa';
    
    public static function loadLanguage($language = null) {
        // اگر زبان مشخص نشده، از تنظیمات بخوان
        if ($language === null) {
            $language = self::getSystemLanguage();
        }
        
        self::$current_language = $language;
        
        $lang_file = __DIR__ . "/lang_{$language}.php";
        if (file_exists($lang_file)) {
            include $lang_file;
            self::$lang = isset($lang) ? $lang : [];
        } else {
            // Default to Persian if language file not found
            $lang_file = __DIR__ . "/lang_fa.php";
            if (file_exists($lang_file)) {
                include $lang_file;
                self::$lang = isset($lang) ? $lang : [];
            } else {
                self::$lang = [];
            }
        }
    }
    
    private static function getSystemLanguage() {
        try {
            if (class_exists('SettingsHelper')) {
                return SettingsHelper::getSetting('language', 'fa');
            }
        } catch (Exception $e) {
            // در صورت خطا، زبان پیشفرض
        }
        return 'fa';
    }
    
    public static function get($key, $default = null) {
        if (self::$lang === null) {
            self::loadLanguage(self::$current_language);
        }
        
        return self::$lang[$key] ?? $default ?? $key;
    }
    
    public static function getCurrentLanguage() {
        return self::$current_language;
    }
    
    public static function getDirection() {
        return in_array(self::$current_language, ['fa', 'ps']) ? 'rtl' : 'ltr';
    }
}

// Helper function for easy access (moved to functions.php)
?>