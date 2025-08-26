<?php
if (!class_exists('LanguageHelper')) {
    require_once __DIR__ . '/LanguageHelper.php';
}

function sanitizeOutput($text) {
    try {
        if ($text === null) {
            return '';
        }
        return htmlspecialchars((string)$text, ENT_QUOTES, 'UTF-8');
    } catch (Exception $e) {
        error_log('sanitizeOutput error: ' . $e->getMessage());
        return '';
    }
}

// Helper function for easy access to translations
if (!function_exists('__')) {
    function __($key, $default = null) {
        return LanguageHelper::get($key, $default);
    }
}

// تابع تبدیل تاریخ میلادی به شمسی
if (!function_exists('jdate')) {
    function jdate($format, $timestamp = null) {
        if ($timestamp === null) {
            $timestamp = time();
        }
        
        $gregorian_months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
        $persian_months = ['فرو', 'ارد', 'خرد', 'تیر', 'مرد', 'شهر', 'مهر', 'آبا', 'آذر', 'دی', 'بهم', 'اسف'];
        
        $date = date($format, $timestamp);
        
        // تبدیل نام ماهها
        for ($i = 0; $i < 12; $i++) {
            $date = str_replace($gregorian_months[$i], $persian_months[$i], $date);
        }
        
        return $date;
    }
}
?>