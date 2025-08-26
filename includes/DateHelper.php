<?php
class DateHelper
{

    public static function gregorianToJalali($gy, $gm, $gd)
    {
        $g_d_m = [0, 31, 59, 90, 120, 151, 181, 212, 243, 273, 304, 334];

        if ($gy <= 1600) {
            $jy = 0;
            $gy -= 621;
        } else {
            $jy = 979;
            $gy -= 1600;
        }

        if ($gm > 2) {
            $gy2 = $gy + 1;
        } else {
            $gy2 = $gy;
        }

        $days = (365 * $gy) + ((int) (($gy2 + 3) / 4)) - ((int) (($gy2 + 99) / 100)) + ((int) (($gy2 + 399) / 400)) - 80 + $gd + $g_d_m[$gm - 1];

        $jy += 33 * ((int) ($days / 12053));
        $days %= 12053;

        $jy += 4 * ((int) ($days / 1461));
        $days %= 1461;

        if ($days > 365) {
            $jy += (int) (($days - 1) / 365);
            $days = ($days - 1) % 365;
        }

        if ($days < 186) {
            $jm = 1 + (int) ($days / 31);
            $jd = 1 + ($days % 31);
        } else {
            $jm = 7 + (int) (($days - 186) / 30);
            $jd = 1 + (($days - 186) % 30);
        }

        return [$jy, $jm, $jd];
    }

    public static function formatJalaliDate($timestamp = null)
    {
        if ($timestamp === null) {
            $timestamp = time();
        }

        $date = date('Y-m-d', $timestamp);
        list($gy, $gm, $gd) = explode('-', $date);
        list($jy, $jm, $jd) = self::gregorianToJalali($gy, $gm, $gd);

        $months = [
            1 => 'حمل',
            2 => 'ثور',
            3 => 'جوزا',
            4 => 'سرطان',
            5 => 'اسد',
            6 => 'سنبله',
            7 => 'میزان',
            8 => 'عقرب',
            9 => 'قوس',
            10 => 'جدی',
            11 => 'دلو',
            12 => 'حوت'
        ];

        return $jd . ' ' . $months[$jm] . ' ' . $jy;
    }

    public static function formatJalaliDateTime($timestamp = null)
    {
        if ($timestamp === null) {
            $timestamp = time();
        }

        $time = date('H:i', $timestamp);
        return self::formatJalaliDate($timestamp) . ' - ' . $time;
    }

    public static function getDateFormat($setting = 'gregorian')
    {
        return $setting === 'jalali' ? 'jalali' : 'gregorian';
    }

    public static function jalaliToGregorian($jy, $jm, $jd)
    {
        $gy = $jy <= 979 ? 621 : 1600;
        $jy -= $jy <= 979 ? 0 : 979;

        $jp = $jm < 7 ? ($jm - 1) * 31 : ($jm - 7) * 30 + 186;
        $days = (365 * $jy) + ((int) ($jy / 33)) * 8 + ((int) ((($jy % 33) + 3) / 4)) + 78 + $jd + $jp;

        $gy += 400 * ((int) ($days / 146097));
        $days %= 146097;

        $leap = true;
        if ($days >= 36525) {
            $days--;
            $gy += 100 * ((int) ($days / 36524));
            $days %= 36524;
            if ($days >= 365)
                $days++;
        }

        $gy += 4 * ((int) ($days / 1461));
        $days %= 1461;

        if ($days >= 366) {
            $leap = false;
            $days--;
            $gy += (int) ($days / 365);
            $days = $days % 365;
        }

        $sal_a = [0, 31, ($leap ? 29 : 28), 31, 30, 31, 30, 31, 31, 30, 31, 30, 31];
        $gm = 0;
        while ($gm < 13 && $days >= $sal_a[$gm]) {
            $days -= $sal_a[$gm];
            $gm++;
        }

        return [$gy, $gm, $days + 1];
    }

    public static function convertJalaliToMysqlDate($jalaliDate)
    {
        if (empty($jalaliDate))
            return null;

        $parts = explode('/', $jalaliDate);
        if (count($parts) !== 3)
            return null;

        list($gy, $gm, $gd) = self::jalaliToGregorian($parts[0], $parts[1], $parts[2]);
        return sprintf('%04d-%02d-%02d', $gy, $gm, $gd);
    }

    public static function convertMysqlToJalaliDate($mysqlDate)
    {
        if (empty($mysqlDate))
            return '';

        $parts = explode('-', $mysqlDate);
        if (count($parts) !== 3)
            return $mysqlDate;

        list($jy, $jm, $jd) = self::gregorianToJalali($parts[0], $parts[1], $parts[2]);
        return sprintf('%04d/%02d/%02d', $jy, $jm, $jd);
    }
}
?>