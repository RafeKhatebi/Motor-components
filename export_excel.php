<?php
require_once 'init_security.php';
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: dashboard.php');
    exit();
}

require_once 'config/database.php';

$type = $_GET['type'] ?? '';
$file = $_GET['file'] ?? '';

if ($type !== 'backup' || empty($file)) {
    die('درخواست نامعتبر');
}

$database = new Database();
$db = $database->getConnection();

// تنظیم header برای دانلود اکسل
header('Content-Type: application/vnd.ms-excel; charset=utf-8');
header('Content-Disposition: attachment; filename="backup_' . date('Y-m-d_H-i-s') . '.xls"');
header('Pragma: no-cache');
header('Expires: 0');

// شروع خروجی HTML برای اکسل
echo "\xEF\xBB\xBF"; // UTF-8 BOM
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        table { border-collapse: collapse; width: 100%; }
        th, td { border: 1px solid #000; padding: 8px; text-align: right; }
        th { background-color: #f2f2f2; font-weight: bold; }
        .sheet-title { font-size: 18px; font-weight: bold; margin: 20px 0; }
    </style>
</head>
<body>

<?php
$tables = [
    'users' => 'کاربران',
    'categories' => 'دسته بندیها', 
    'products' => 'محصولات',
    'customers' => 'مشتریان',
    'suppliers' => 'تأمین کنندگان',
    'sales' => 'فروش ها',
    'sale_items' => 'آیتم های فروش',
    'purchases' => 'خریدها',
    'purchase_items' => 'آیتم های خرید'
];

foreach ($tables as $table => $title) {
    try {
        $query = "SELECT * FROM $table";
        $stmt = $db->prepare($query);
        $stmt->execute();
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (!empty($rows)) {
            echo "<div class='sheet-title'>$title</div>";
            echo "<table>";
            
            // سرتیتر
            echo "<tr>";
            foreach (array_keys($rows[0]) as $column) {
                echo "<th>" . htmlspecialchars($column) . "</th>";
            }
            echo "</tr>";
            
            // داده‌ها
            foreach ($rows as $row) {
                echo "<tr>";
                foreach ($row as $value) {
                    echo "<td>" . htmlspecialchars($value ?? '') . "</td>";
                }
                echo "</tr>";
            }
            
            echo "</table><br><br>";
        }
    } catch (Exception $e) {
        // در صورت خطا، جدول را رد کن
        continue;
    }
}
?>

</body>
</html>