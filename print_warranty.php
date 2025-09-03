<?php
// Secure file inclusion with path validation
$allowed_files = [
    'init_security.php' => realpath(__DIR__ . '/init_security.php'),
    'config/database.php' => realpath(__DIR__ . '/config/database.php'),
    'includes/functions.php' => realpath(__DIR__ . '/includes/functions.php'),
    'includes/SettingsHelper.php' => realpath(__DIR__ . '/includes/SettingsHelper.php'),
    'includes/BarcodeGenerator.php' => realpath(__DIR__ . '/includes/BarcodeGenerator.php')
];

foreach (['init_security.php'] as $required_file) {
    $real_path = $allowed_files[$required_file];
    if ($real_path && file_exists($real_path)) {
        require_once $real_path;
    } else {
        http_response_code(500);
        exit('Security error: Invalid file path');
    }
}

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

foreach (['config/database.php', 'includes/functions.php', 'includes/SettingsHelper.php', 'includes/BarcodeGenerator.php'] as $file) {
    $real_path = $allowed_files[$file];
    if ($real_path && file_exists($real_path)) {
        require_once $real_path;
    } else {
        http_response_code(500);
        exit('Security error: Invalid file path');
    }
}

$database = new Database();
$db = $database->getConnection();
SettingsHelper::loadSettings($db);

$warranty_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$warranty_id) {
    exit('شناسه گارانتی نامعتبر');
}

// Get warranty details
$warranty_query = "SELECT w.*, p.name as product_name, p.code as product_code, p.barcode,
                   c.name as customer_name, c.phone as customer_phone, c.address as customer_address,
                   si.quantity, si.unit_price, s.created_at as sale_date, s.id as sale_id
                   FROM warranties w
                   LEFT JOIN products p ON w.product_id = p.id
                   LEFT JOIN customers c ON w.customer_id = c.id
                   LEFT JOIN sale_items si ON w.sale_item_id = si.id
                   LEFT JOIN sales s ON si.sale_id = s.id
                   WHERE w.id = ?";

$warranty_stmt = $db->prepare($warranty_query);
$warranty_stmt->execute([$warranty_id]);
$warranty = $warranty_stmt->fetch(PDO::FETCH_ASSOC);

if (!$warranty) {
    exit('گارانتی یافت نشد');
}
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>گارانتی محصول - <?= sanitizeOutput(SettingsHelper::getShopName()) ?></title>
    <style>
        body {
            font-family: 'Tahoma', Arial, sans-serif;
            margin: 0;
            padding: 20px;
            background: white;
            color: #333;
        }
        .warranty-card {
            max-width: 600px;
            margin: 0 auto;
            border: 2px solid #2563eb;
            border-radius: 10px;
            padding: 20px;
            background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
        }
        .header {
            text-align: center;
            border-bottom: 2px solid #2563eb;
            padding-bottom: 15px;
            margin-bottom: 20px;
        }
        .shop-name {
            font-size: 24px;
            font-weight: bold;
            color: #2563eb;
            margin-bottom: 5px;
        }
        .warranty-title {
            font-size: 18px;
            color: #1e40af;
            margin: 10px 0;
        }
        .info-section {
            margin: 15px 0;
        }
        .info-row {
            display: flex;
            justify-content: space-between;
            margin: 8px 0;
            padding: 5px 0;
            border-bottom: 1px dotted #cbd5e1;
        }
        .label {
            font-weight: bold;
            color: #374151;
        }
        .value {
            color: #1f2937;
        }
        .barcode-section {
            text-align: center;
            margin: 20px 0;
            padding: 15px;
            background: white;
            border-radius: 5px;
        }
        .terms {
            font-size: 12px;
            color: #6b7280;
            margin-top: 20px;
            padding: 15px;
            background: #f9fafb;
            border-radius: 5px;
            border-right: 4px solid #2563eb;
        }
        .footer {
            text-align: center;
            margin-top: 20px;
            padding-top: 15px;
            border-top: 1px solid #e5e7eb;
            font-size: 12px;
            color: #6b7280;
        }
        @media print {
            body { margin: 0; padding: 10px; }
            .warranty-card { border: 2px solid #000; }
        }
    </style>
</head>
<body>
    <div class="warranty-card">
        <div class="header">
            <div class="shop-name"><?= sanitizeOutput(SettingsHelper::getShopName()) ?></div>
            <div><?= sanitizeOutput(SettingsHelper::getSetting('shop_address', '')) ?></div>
            <div>تلفن: <?= sanitizeOutput(SettingsHelper::getSetting('shop_phone', '')) ?></div>
            <div class="warranty-title">گارانتی نامه محصول</div>
        </div>

        <div class="info-section">
            <div class="info-row">
                <span class="label">شماره گارانتی:</span>
                <span class="value"><?= str_pad($warranty['id'], 6, '0', STR_PAD_LEFT) ?></span>
            </div>
            <div class="info-row">
                <span class="label">نام محصول:</span>
                <span class="value"><?= sanitizeOutput($warranty['product_name']) ?></span>
            </div>
            <div class="info-row">
                <span class="label">کد محصول:</span>
                <span class="value"><?= sanitizeOutput($warranty['product_code']) ?></span>
            </div>
            <div class="info-row">
                <span class="label">نام مشتری:</span>
                <span class="value"><?= sanitizeOutput($warranty['customer_name'] ?: 'نامشخص') ?></span>
            </div>
            <?php if ($warranty['customer_phone']): ?>
            <div class="info-row">
                <span class="label">تلفن مشتری:</span>
                <span class="value"><?= sanitizeOutput($warranty['customer_phone']) ?></span>
            </div>
            <?php endif; ?>
            <div class="info-row">
                <span class="label">تاریخ خرید:</span>
                <span class="value"><?= date('Y/m/d', strtotime($warranty['sale_date'])) ?></span>
            </div>
            <div class="info-row">
                <span class="label">شروع گارانتی:</span>
                <span class="value"><?= date('Y/m/d', strtotime($warranty['warranty_start'])) ?></span>
            </div>
            <div class="info-row">
                <span class="label">پایان گارانتی:</span>
                <span class="value"><?= date('Y/m/d', strtotime($warranty['warranty_end'])) ?></span>
            </div>
            <div class="info-row">
                <span class="label">مدت گارانتی:</span>
                <span class="value"><?= $warranty['warranty_months'] ?> ماه</span>
            </div>
            <div class="info-row">
                <span class="label">نوع گارانتی:</span>
                <span class="value"><?= $warranty['warranty_type'] === 'shop' ? 'فروشگاه' : 'سازنده' ?></span>
            </div>
        </div>

        <?php if ($warranty['barcode']): ?>
        <div class="barcode-section">
            <div style="margin-bottom: 10px;">بارکد محصول:</div>
            <?= BarcodeGenerator::generateSVG($warranty['barcode'], 2, 40) ?>
            <div style="margin-top: 5px; font-size: 12px;"><?= sanitizeOutput($warranty['barcode']) ?></div>
        </div>
        <?php endif; ?>

        <div class="terms">
            <strong>شرایط گارانتی:</strong><br>
            • این گارانتی فقط برای نقص ساخت معتبر است<br>
            • خرابی ناشی از سوء استفاده شامل گارانتی نمی‌شود<br>
            • برای استفاده از گارانتی ارائه این برگه الزامی است<br>
            • گارانتی قابل انتقال نیست<br>
            • تعمیر یا تعویض بر اساس تشخیص فنی انجام می‌شود
        </div>

        <div class="footer">
            تاریخ صدور: <?= date('Y/m/d H:i') ?><br>
            این گارانتی نامه توسط سیستم مدیریت فروشگاه صادر شده است
        </div>
    </div>

    <script>
        window.onload = function() {
            window.print();
        };
    </script>
</body>
</html>