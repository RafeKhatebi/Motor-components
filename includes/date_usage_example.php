<?php
/*
نحوه استفاده از سیستم تاریخ در صفحات مختلف:

1. در ابتدای هر صفحه که نیاز به نمایش تاریخ دارد:
*/

require_once 'includes/SettingsHelper.php';
// بارگذاری تنظیمات
SettingsHelper::loadSettings($db);

/*
2. برای نمایش تاریخ فعلی:
*/
echo SettingsHelper::formatDate(null, $db); // تاریخ امروز
echo SettingsHelper::formatDateTime(null, $db); // تاریخ و ساعت فعلی

/*
3. برای نمایش تاریخ از دیتابیس:
*/
$timestamp = strtotime($row['created_at']);
echo SettingsHelper::formatDate($timestamp, $db);
echo SettingsHelper::formatDateTime($timestamp, $db);

/*
4. در کوئری SQL برای فیلتر کردن بر اساس تاریخ:
*/
$query = "SELECT * FROM sales WHERE DATE(created_at) = CURDATE()";

/*
5. برای نمایش در جدول:
*/
?>
<td><?= SettingsHelper::formatDateTime(strtotime($row['created_at']), $db) ?></td>

<?php
/*
6. برای استفاده در JavaScript (در صورت نیاز):
*/
?>
<script>
const dateFormat = '<?= SettingsHelper::getSetting('date_format', 'gregorian') ?>';
</script>

<?php
/*
نکات مهم:
- همیشه $db را به عنوان پارامتر دوم ارسال کنید
- برای timestamp از strtotime() استفاده کنید
- برای تاریخ فعلی null ارسال کنید
- تنظیمات به صورت خودکار از دیتابیس بارگذاری میشود
*/
?>