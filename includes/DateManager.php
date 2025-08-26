<?php
require_once 'DateHelper.php';
require_once 'SettingsHelper.php';

class DateManager {
    
    public static function processFormDates($postData) {
        $dateFormat = SettingsHelper::getSetting('date_format', 'gregorian');
        
        if ($dateFormat !== 'jalali') {
            return $postData;
        }
        
        $processedData = $postData;
        
        // پردازش فیلدهای تاریخ
        foreach ($postData as $key => $value) {
            if (self::isDateField($key) && !empty($value)) {
                if (preg_match('/^\d{4}\/\d{2}\/\d{2}$/', $value)) {
                    // تبدیل تاریخ شمسی به میلادی
                    $gregorianDate = DateHelper::convertJalaliToMysqlDate($value);
                    if ($gregorianDate) {
                        $processedData[$key] = $gregorianDate;
                    }
                }
            }
        }
        
        return $processedData;
    }
    
    public static function formatDateForDisplay($date, $includeTime = false) {
        $dateFormat = SettingsHelper::getSetting('date_format', 'gregorian');
        
        if ($dateFormat === 'jalali' && !empty($date)) {
            if ($includeTime) {
                return DateHelper::formatJalaliDateTime(strtotime($date));
            } else {
                return DateHelper::convertMysqlToJalaliDate($date);
            }
        }
        
        return $date;
    }
    
    public static function formatDateForInput($date) {
        $dateFormat = SettingsHelper::getSetting('date_format', 'gregorian');
        
        if ($dateFormat === 'jalali' && !empty($date)) {
            return DateHelper::convertMysqlToJalaliDate($date);
        }
        
        return $date;
    }
    
    private static function isDateField($fieldName) {
        $dateFields = [
            'date', 'created_at', 'updated_at', 'birth_date', 
            'start_date', 'end_date', 'invoice_date', 'due_date',
            'from_date', 'to_date', 'sale_date', 'purchase_date'
        ];
        
        foreach ($dateFields as $dateField) {
            if (strpos($fieldName, $dateField) !== false) {
                return true;
            }
        }
        
        return false;
    }
    
    public static function getCurrentJalaliDate() {
        $jalali = DateHelper::gregorianToJalali(date('Y'), date('m'), date('d'));
        return sprintf('%04d/%02d/%02d', $jalali[0], $jalali[1], $jalali[2]);
    }
    
    public static function getCurrentGregorianDate() {
        return date('Y-m-d');
    }
    
    public static function getDateRangeForQuery($fromDate, $toDate) {
        $dateFormat = SettingsHelper::getSetting('date_format', 'gregorian');
        
        if ($dateFormat === 'jalali') {
            $fromDate = DateHelper::convertJalaliToMysqlDate($fromDate);
            $toDate = DateHelper::convertJalaliToMysqlDate($toDate);
        }
        
        return [$fromDate, $toDate];
    }
}
?>