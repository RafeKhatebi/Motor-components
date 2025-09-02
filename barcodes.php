<?php
require_once 'init_security.php';
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

require_once 'config/database.php';
require_once 'includes/functions.php';
require_once 'includes/SettingsHelper.php';
$database = new Database();
$db = $database->getConnection();
SettingsHelper::loadSettings($db);

$page_title = 'مدیریت بارکد';

// دریافت محصولات و بارکدها
$products_query = "SELECT p.*, pb.barcode as custom_barcode, pb.barcode_type, c.name as category_name
                   FROM products p
                   LEFT JOIN product_barcodes pb ON p.id = pb.product_id AND pb.is_primary = TRUE
                   LEFT JOIN categories c ON p.category_id = c.id
                   ORDER BY p.name";
$products_stmt = $db->prepare($products_query);
$products_stmt->execute();
$products = $products_stmt->fetchAll(PDO::FETCH_ASSOC);

include 'includes/header.php';
?>

<div class="section">
    <div class="card">
        <div class="card-header">
            <h5 class="card-title mb-0">
                <i class="fas fa-qrcode me-2"></i>
                مدیریت بارکد محصولات
            </h5>
        </div>
        <div class="card-body">
            <!-- تست اسکنر بارکد -->
            <div class="alert alert-info mb-4">
                <h6><i class="fas fa-scanner me-2"></i>تست اسکنر بارکد</h6>
                <div class="input-group">
                    <input type="text" id="testBarcode" class="form-control"
                        placeholder="بارکد را اسکن کنید یا وارد کنید">
                    <button class="btn btn-primary" onclick="testScan()">
                        <i class="fas fa-search"></i> جستجو
                    </button>
                </div>
                <div id="scanResult" class="mt-2"></div>
            </div>

            <div class="table-responsive">
                <table class="table table-modern">
                    <thead>
                        <tr>
                            <th>محصول</th>
                            <th>دسته بندی</th>
                            <th>بارکد فعلی</th>
                            <th>نوع بارکد</th>
                            <th>عملیات</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($products as $product): ?>
                            <tr>
                                <td>
                                    <strong><?= sanitizeOutput($product['name']) ?></strong><br>
                                    <small class="text-muted">کد: <?= sanitizeOutput($product['code']) ?></small>
                                </td>
                                <td><?= sanitizeOutput($product['category_name']) ?></td>
                                <td>
                                    <code><?= sanitizeOutput($product['custom_barcode'] ?: $product['barcode']) ?></code>
                                </td>
                                <td>
                                    <span class="badge bg-info"><?= $product['barcode_type'] ?: 'CODE128' ?></span>
                                </td>
                                <td>
                                    <button class="btn btn-primary btn-sm" onclick="generateBarcode(<?= $product['id'] ?>)">
                                        <i class="fas fa-qrcode"></i> تولید
                                    </button>
                                    <button class="btn btn-success btn-sm"
                                        onclick="printBarcode('<?= $product['custom_barcode'] ?: $product['barcode'] ?>')">
                                        <i class="fas fa-print"></i> چاپ
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- آمار اسکن -->
<div class="section">
    <div class="card">
        <div class="card-header">
            <h5 class="card-title mb-0">
                <i class="fas fa-chart-bar me-2"></i>
                آمار اسکن بارکد
            </h5>
        </div>
        <div class="card-body">
            <?php
            $scan_stats = "SELECT 
                COUNT(*) as total_scans,
                COUNT(DISTINCT barcode) as unique_barcodes,
                COUNT(CASE WHEN DATE(created_at) = CURDATE() THEN 1 END) as today_scans
                FROM barcode_scans";
            $stats_stmt = $db->prepare($scan_stats);
            $stats_stmt->execute();
            $stats = $stats_stmt->fetch(PDO::FETCH_ASSOC);
            ?>
            <div class="row">
                <div class="col-md-4">
                    <div class="stat-card">
                        <h3><?= number_format($stats['total_scans']) ?></h3>
                        <p>کل اسکنها</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="stat-card">
                        <h3><?= number_format($stats['today_scans']) ?></h3>
                        <p>اسکن امروز</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="stat-card">
                        <h3><?= number_format($stats['unique_barcodes']) ?></h3>
                        <p>بارکدهای منحصر</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- <script>
    document.getElementById('testBarcode').addEventListener('keypress', function (event) {
        if (event.key === 'Enter') {
            event.preventDefault();
            testScan();
        }
    });

    async function testScan() {
        const barcode = document.getElementById('testBarcode').value.trim();
        const resultDiv = document.getElementById('scanResult');

        if (!barcode) {
            resultDiv.innerHTML = '<div class="alert alert-warning">بارکد را وارد کنید</div>';
            return;
        }

        try {
            const response = await fetch('api/barcode_search.php?barcode=' + encodeURIComponent(barcode) + '&type=search');
            const result = await response.json();

            if (result.success) {
                const product = result.product;
                resultDiv.innerHTML = '<div class="alert alert-success">' +
                    '<h6>محصول یافت شد:</h6>' +
                    '<p><strong>' + product.name + '</strong></p>' +
                    '<p>کد: ' + product.code + ' | قیمت: ' + parseInt(product.sell_price).toLocaleString() + ' افغانی | موجودی: ' + product.stock_quantity + '</p>' +
                    '</div>';
            } else {
                resultDiv.innerHTML = '<div class="alert alert-danger">' + result.message + '</div>';
            }
        } catch (error) {
            resultDiv.innerHTML = '<div class="alert alert-danger">خطا در جستجو</div>';
        }
    }

    function generateBarcode(productId) {
        const newBarcode = 'MP' + String(productId).padStart(8, '0') + String(Date.now()).slice(-4);

        if (confirm('بارکد جدید تولید شود؟\n' + newBarcode)) {
            showAlert('بارکد جدید تولید شد', 'success');
        }
    }

    function printBarcode(barcode) {
        const printWindow = window.open('', '_blank', 'width=400,height=300');
        const htmlContent = '<html>' +
            '<head><title>چاپ بارکد</title></head>' +
            '<body style="text-align: center; font-family: Arial;">' +
            '<h3>بارکد محصول</h3>' +
            '<div style="font-size: 24px; font-family: monospace; margin: 20px;">' + barcode + '</div>' +
            '<div style="margin: 20px;">' +
            '<svg width="200" height="50">' +
            '<rect width="2" height="50" x="10" fill="black"></rect>' +
            '<rect width="1" height="50" x="15" fill="black"></rect>' +
            '<rect width="3" height="50" x="20" fill="black"></rect>' +
            '<rect width="1" height="50" x="27" fill="black"></rect>' +
            '<rect width="2" height="50" x="32" fill="black"></rect>' +
            '</svg>' +
            '</div>' +
            '<script>window.print(); window.close();</script>' +
'</body>' +
'

</html>';

// printWindow.document.write(htmlContent);
}
</script> -->
<script>
    function printBarcode(barcode) {
        const printWindow = window.open('', '_blank', 'width=400,height=300');
        const htmlContent = `
        <html>
        <head>
            <title>چاپ بارکد</title>
            <script src="https://cdn.jsdelivr.net/npm/jsbarcode@3.11.6/dist/JsBarcode.all.min.js"><\/script>
        </head>
        <body style="text-align: center; font-family: Arial;">
            <h3>بارکد محصول</h3>
            <div style="margin: 20px;">
                <svg id="barcode"></svg>
            </div>
            <script>
                JsBarcode("#barcode", "${barcode}", {
                    format: "CODE128",
                    width: 2,
                    height: 50,
                    displayValue: true
                });
                window.print();
                window.close();
            <\/script>
        </body>
        </html>
    `;

        printWindow.document.open();
        printWindow.document.write(htmlContent);
        printWindow.document.close();
    }
</script>


<?php include 'includes/footer-modern.php'; ?>