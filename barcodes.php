<?php
// Secure file inclusion with path validation
$allowed_files = [
    'init_security.php' => realpath(__DIR__ . '/init_security.php'),
    'config/database.php' => realpath(__DIR__ . '/config/database.php'),
    'includes/functions.php' => realpath(__DIR__ . '/includes/functions.php'),
    'includes/SettingsHelper.php' => realpath(__DIR__ . '/includes/SettingsHelper.php'),
    'includes/BarcodeGenerator.php' => realpath(__DIR__ . '/includes/BarcodeGenerator.php'),
    'includes/header.php' => realpath(__DIR__ . '/includes/header.php')
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
    return;
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

$page_title = 'مدیریت بارکد';

// Pagination
$items_per_page = 30;
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$offset = ($page - 1) * $items_per_page;

// Count total products
$count_query = "SELECT COUNT(*) as total FROM products";
$count_stmt = $db->prepare($count_query);
$count_stmt->execute();
$total_items = $count_stmt->fetch(PDO::FETCH_ASSOC)['total'];
$total_pages = ceil($total_items / $items_per_page);

// دریافت محصولات و بارکدها
$products_query = "SELECT p.*, pb.barcode as custom_barcode, pb.barcode_type, c.name as category_name
                   FROM products p
                   LEFT JOIN product_barcodes pb ON p.id = pb.product_id AND pb.is_primary = TRUE
                   LEFT JOIN categories c ON p.category_id = c.id
                   ORDER BY p.name LIMIT :limit OFFSET :offset";
$products_stmt = $db->prepare($products_query);
$products_stmt->bindValue(':limit', $items_per_page, PDO::PARAM_INT);
$products_stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$products_stmt->execute();
$products = $products_stmt->fetchAll(PDO::FETCH_ASSOC);

$real_path = $allowed_files['includes/header.php'];
if ($real_path && file_exists($real_path)) {
    include $real_path;
} else {
    http_response_code(500);
    exit('Security error: Invalid file path');
}
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
                                    <div class="d-flex align-items-center gap-2">
                                        <div>
                                            <code class="d-block"><?= sanitizeOutput($product['custom_barcode'] ?: $product['barcode']) ?></code>
                                            <?php if ($product['custom_barcode'] ?: $product['barcode']): ?>
                                                <div class="barcode-preview mt-1" style="max-width: 100px; font-size: 0;">
                                                    <?= BarcodeGenerator::generateSVG($product['custom_barcode'] ?: $product['barcode'], 1, 15) ?>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
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
            
            <!-- Pagination -->
            <?php if ($total_pages > 1): ?>
                <div class="card-footer py-4">
                    <nav aria-label="صفحهبندی">
                        <ul class="pagination justify-content-center mb-0">
                            <?php if ($page > 1): ?>
                                <li class="page-item">
                                    <a class="page-link" href="?page=<?= $page - 1 ?>">
                                        <i class="fas fa-angle-right"></i>
                                    </a>
                                </li>
                            <?php else: ?>
                                <li class="page-item disabled">
                                    <span class="page-link"><i class="fas fa-angle-right"></i></span>
                                </li>
                            <?php endif; ?>

                            <?php
                            $start = max(1, $page - 2);
                            $end = min($total_pages, $page + 2);

                            for ($i = $start; $i <= $end; $i++): ?>
                                <li class="page-item <?= $i == $page ? 'active' : '' ?>">
                                    <a class="page-link" href="?page=<?= $i ?>"><?= $i ?></a>
                                </li>
                            <?php endfor; ?>

                            <?php if ($page < $total_pages): ?>
                                <li class="page-item">
                                    <a class="page-link" href="?page=<?= $page + 1 ?>">
                                        <i class="fas fa-angle-left"></i>
                                    </a>
                                </li>
                            <?php else: ?>
                                <li class="page-item disabled">
                                    <span class="page-link"><i class="fas fa-angle-left"></i></span>
                                </li>
                            <?php endif; ?>
                        </ul>

                        <div class="text-center mt-3">
                            <small class="text-muted">
                                نمایش <?= $offset + 1 ?> تا <?= min($offset + $items_per_page, $total_items) ?> از
                                <?= $total_items ?> محصول
                            </small>
                        </div>
                    </nav>
                </div>
            <?php endif; ?>
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


<script>
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

    async function generateBarcode(productId) {
        if (!confirm('بارکد جدید تولید شود؟')) return;
        
        try {
            const formData = new FormData();
            formData.append('product_id', productId);
            formData.append('barcode_type', 'CODE128');
            
            const response = await fetch('api/generate_barcode.php', {
                method: 'POST',
                body: formData
            });
            
            const result = await response.json();
            
            if (result.success) {
                showAlert('بارکد جدید تولید شد: ' + result.barcode, 'success');
                setTimeout(() => location.reload(), 1500);
            } else {
                showAlert(result.message, 'error');
            }
        } catch (error) {
            showAlert('خطا در تولید بارکد', 'error');
        }
    }

    function printBarcode(barcode) {
        window.open(`print_barcode.php?barcode=${encodeURIComponent(barcode)}`, '_blank', 'width=400,height=300');
    }
</script>


<?php 
$footer_path = $allowed_files['includes/footer-modern.php'] ?? realpath(__DIR__ . '/includes/footer-modern.php');
if ($footer_path && file_exists($footer_path)) {
    include $footer_path;
} else {
    http_response_code(500);
    exit('Security error: Invalid file path');
}
?>