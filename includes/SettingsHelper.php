<?php
require_once __DIR__ . '/DateHelper.php';
if (!class_exists('LanguageHelper')) {
    require_once __DIR__ . '/LanguageHelper.php';
}

class SettingsHelper {
    private static $settings = null;
    
    public static function loadSettings($db) {
        if (self::$settings === null) {
            self::$settings = [];
            try {
                $query = "SELECT setting_key, setting_value FROM settings";
                $stmt = $db->prepare($query);
                $stmt->execute();
                while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                    self::$settings[$row['setting_key']] = $row['setting_value'];
                }
                
                // Load language based on settings
                $language = self::$settings['language'] ?? 'fa';
                LanguageHelper::loadLanguage($language);
                
            } catch (Exception $e) {
                // Settings table doesn't exist yet
                LanguageHelper::loadLanguage('fa');
            }
        }
        return self::$settings;
    }
    
    public static function getSetting($key, $default = null) {
        global $db;
        if (self::$settings === null) {
            self::loadSettings($db);
        }
        return self::$settings[$key] ?? $default;
    }
    
    public static function formatDate($timestamp = null, $db = null) {
        if ($db) {
            $dateFormat = self::getSetting('date_format', 'gregorian');
        } else {
            $dateFormat = 'gregorian';
        }
        
        if ($dateFormat === 'jalali') {
            return DateHelper::formatJalaliDate($timestamp);
        } else {
            if ($timestamp === null) {
                return date('Y-m-d');
            }
            return date('Y-m-d', $timestamp);
        }
    }
    
    public static function formatDateTime($timestamp = null, $db = null) {
        if ($db) {
            $dateFormat = self::getSetting('date_format', 'gregorian');
        } else {
            $dateFormat = 'gregorian';
        }
        
        if ($dateFormat === 'jalali') {
            return DateHelper::formatJalaliDateTime($timestamp);
        } else {
            if ($timestamp === null) {
                return date('Y-m-d H:i');
            }
            return date('Y-m-d H:i', $timestamp);
        }
    }
    
    public static function getShopName() {
        return self::getSetting('shop_name', 'فروشگاه قطعات موتور');
    }
    
    public static function getShopLogo() {
        $logo = self::getSetting('shop_logo', '');
        if ($logo && file_exists($logo)) {
            return $logo;
        }
        return null;
    }
    
    public static function getShopLogoOrDefault() {
        $logo = self::getShopLogo();
        return $logo ?: 'assets/img/default-logo.svg';
    }
    
    public static function hasCustomLogo() {
        $logo = self::getSetting('shop_logo', '');
        return !empty($logo) && file_exists($logo);
    }
}
?>