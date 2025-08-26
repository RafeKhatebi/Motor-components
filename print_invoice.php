<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

require_once 'config/database.php';
require_once 'includes/SettingsHelper.php';
$database = new Database();
$db = $database->getConnection();
SettingsHelper::loadSettings($db);

$sale_id = $_GET['id'] ?? 0;

if (!$sale_id) {
    die('شناسه فاکتور نامعتبر');
}

$sale_query = "SELECT s.*, c.name as customer_name, c.phone as customer_phone, c.address as customer_address 
               FROM sales s 
               LEFT JOIN customers c ON s.customer_id = c.id 
               WHERE s.id = ?";
$sale_stmt = $db->prepare($sale_query);
$sale_stmt->execute([$sale_id]);
$sale = $sale_stmt->fetch(PDO::FETCH_ASSOC);

if (!$sale) {
    die('فاکتور یافت نشد');
}

$items_query = "SELECT si.*, p.name as product_name, p.code as product_code 
                FROM sale_items si 
                JOIN products p ON si.product_id = p.id 
                WHERE si.sale_id = ?";
$items_stmt = $db->prepare($items_query);
$items_stmt->execute([$sale_id]);
$items = $items_stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="fa">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>فاکتور فروش - <?= $sale_id ?></title>
    <style>
        body {
            font-family: 'Tahoma', sans-serif;
            direction: rtl;
            margin: 20px;
        }

        .invoice-header {
            text-align: center;
            border-bottom: 2px solid #333;
            padding-bottom: 20px;
            margin-bottom: 20px;
        }

        .invoice-info {
            display: flex;
            justify-content: space-between;
            margin-bottom: 20px;
        }

        .customer-info {
            background: #f5f5f5;
            padding: 15px;
            border-radius: 5px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }

        th,
        td {
            border: 1px solid #ddd;
            padding: 10px;
            text-align: center;
        }

        th {
            background: #f5f5f5;
        }

        .total-section {
            margin-top: 20px;
            text-align: left;
        }

        .print-btn {
            background: #007bff;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            margin-left: 10px;
        }

        .back-btn {
            background: #6c757d;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
        }

        @media print {

            .print-btn,
            .back-btn {
                display: none;
            }
        }
    </style>
</head>

<body>
    <div class="invoice-header">
        <h1>فروشگاه قطعات موتورسیکلت</h1>
        <p>فاکتور فروش</p>
    </div>

    <div class="invoice-info">
        <div>
            <strong>شماره فاکتور:</strong> <?= $sale_id ?><br>
            <strong>تاریخ:</strong> <?= SettingsHelper::formatDateTime(strtotime($sale['created_at']), $db) ?>
        </div>
        <div class="customer-info">
            <strong>مشتری:</strong> <?= $sale['customer_name'] ?: 'مشتری نقدی' ?><br>
            <?php if ($sale['customer_phone']): ?>
                <strong>تلفن:</strong> <?= $sale['customer_phone'] ?><br>
            <?php endif; ?>
            <?php if ($sale['customer_address']): ?>
                <strong>آدرس:</strong> <?= $sale['customer_address'] ?>
            <?php endif; ?>
        </div>
    </div>

    <table>
        <thead>
            <tr>
                <th>ردیف</th>
                <th>کد کالا</th>
                <th>نام کالا</th>
                <th>تعداد</th>
                <th>قیمت واحد</th>
                <th>مبلغ کل</th>
            </tr>
        </thead>
        <tbody>
            <?php $row_num = 1;
            foreach ($items as $item): ?>
                <tr>
                    <td><?= $row_num++ ?></td>
                    <td><?= $item['product_code'] ?></td>
                    <td><?= $item['product_name'] ?></td>
                    <td><?= $item['quantity'] ?></td>
                    <td><?= number_format($item['unit_price']) ?> افغانی</td>
                    <td><?= number_format($item['total_price']) ?> افغانی</td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <div class="total-section">
        <p><strong>مبلغ کل: <?= number_format($sale['total_amount']) ?> افغانی</strong></p>
        <p><strong>تخفیف: <?= number_format($sale['discount']) ?> افغانی</strong></p>
        <p><strong>مبلغ قابل پرداخت: <?= number_format($sale['final_amount']) ?> افغانی</strong></p>
    </div>

    <a href="dashboard.php" class="back-btn">برگشت</a>
    <button class="print-btn" onclick="window.print()">چاپ فاکتور</button>
</body>

</html>