<?php
// Secure file inclusion with path validation
$allowed_files = [
    'init_security.php' => realpath(__DIR__ . '/init_security.php'),
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

foreach (['includes/BarcodeGenerator.php'] as $file) {
    $real_path = $allowed_files[$file];
    if ($real_path && file_exists($real_path)) {
        require_once $real_path;
    } else {
        http_response_code(500);
        exit('Security error: Invalid file path');
    }
}

$barcode = trim($_GET['barcode'] ?? '');

if (!$barcode) {
    exit('بارکد نامعتبر');
}
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>چاپ بارکد</title>
    <style>
        body {
            font-family: 'Tahoma', Arial, sans-serif;
            margin: 0;
            padding: 20px;
            text-align: center;
            background: white;
        }
        .barcode-container {
            border: 1px solid #ccc;
            padding: 20px;
            margin: 20px auto;
            max-width: 300px;
            background: white;
        }
        .barcode-title {
            font-size: 16px;
            margin-bottom: 15px;
            color: #333;
        }
        .barcode-svg {
            margin: 15px 0;
        }
        .barcode-text {
            font-size: 14px;
            font-weight: bold;
            margin-top: 10px;
            color: #333;
        }
        @media print {
            body { margin: 0; padding: 10px; }
            .barcode-container { border: 1px solid #000; }
        }
    </style>
</head>
<body>
    <div class="barcode-container">
        <div class="barcode-title">بارکد محصول</div>
        <div class="barcode-svg">
            <?= BarcodeGenerator::generateSVG($barcode, 3, 60) ?>
        </div>
        <div class="barcode-text"><?= htmlspecialchars($barcode, ENT_QUOTES, 'UTF-8') ?></div>
    </div>

    <script>
        window.onload = function() {
            window.print();
            setTimeout(() => window.close(), 1000);
        };
    </script>
</body>
</html>