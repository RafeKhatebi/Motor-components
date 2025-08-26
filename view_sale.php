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

$sale_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$sale_id) {
    header('Location: sales.php');
    exit();
}

// Get sale details
$sale_query = "SELECT s.*, c.name as customer_name, c.phone as customer_phone, c.address as customer_address,
               COALESCE(s.status, 'completed') as status 
               FROM sales s 
               LEFT JOIN customers c ON s.customer_id = c.id 
               WHERE s.id = ?";
$sale_stmt = $db->prepare($sale_query);
$sale_stmt->execute([$sale_id]);
$sale = $sale_stmt->fetch(PDO::FETCH_ASSOC);

if (!$sale) {
    header('Location: sales.php');
    exit();
}

// Get sale items
$items_query = "SELECT si.*, p.name as product_name, p.code as product_code
                FROM sale_items si
                JOIN products p ON si.product_id = p.id
                WHERE si.sale_id = ?";
$items_stmt = $db->prepare($items_query);
$items_stmt->execute([$sale_id]);
$items = $items_stmt->fetchAll(PDO::FETCH_ASSOC);

$page_title = 'جزئیات فاکتور فروش #' . $sale_id;
include 'includes/header.php';
?>

<div class="container-fluid mt-4">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h3 class="mb-0">جزئیات فاکتور فروش #<?= $sale_id ?></h3>
                    <div>
                        <button onclick="window.print()" class="btn btn-primary btn-sm">
                            <i class="fas fa-print"></i> چاپ
                        </button>
                        <button onclick="window.close()" class="btn btn-secondary btn-sm">
                            <i class="fas fa-times"></i> بستن
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <h5>اطلاعات مشتری</h5>
                            <p><strong>نام:</strong> <?= $sale['customer_name'] ?: 'مشتری نقدی' ?></p>
                            <?php if ($sale['customer_phone']): ?>
                                <p><strong>تلفن:</strong> <?= sanitizeOutput($sale['customer_phone']) ?></p>
                            <?php endif; ?>
                            <?php if ($sale['customer_address']): ?>
                                <p><strong>آدرس:</strong> <?= sanitizeOutput($sale['customer_address']) ?></p>
                            <?php endif; ?>
                        </div>
                        <div class="col-md-6">
                            <h5>اطلاعات فاکتور</h5>
                            <p><strong>شماره فاکتور:</strong> #<?= $sale_id ?></p>
                            <p><strong>تاریخ:</strong>
                                <?= SettingsHelper::formatDateTime(strtotime($sale['created_at']), $db) ?></p>
                            <p><strong>وضعیت:</strong>
                                <?php if ($sale['status'] === 'returned'): ?>
                                    <span class="badge badge-danger">برگشت شده</span>
                                <?php else: ?>
                                    <span class="badge badge-success">تکمیل شده</span>
                                <?php endif; ?>
                            </p>
                        </div>
                    </div>

                    <h5>آیتمهای فاکتور</h5>
                    <div class="table-responsive">
                        <table class="table table-bordered">
                            <thead class="thead-light">
                                <tr>
                                    <th>کد محصول</th>
                                    <th>نام محصول</th>
                                    <th>تعداد</th>
                                    <th>قیمت واحد</th>
                                    <th>مبلغ کل</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($items as $item): ?>
                                    <tr>
                                        <td><?= sanitizeOutput($item['product_code']) ?></td>
                                        <td><?= sanitizeOutput($item['product_name']) ?></td>
                                        <td><?= number_format($item['quantity']) ?></td>
                                        <td><?= number_format($item['unit_price']) ?> افغانی</td>
                                        <td><?= number_format($item['total_price']) ?> افغانی</td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                            <tfoot>
                                <tr class="table-active">
                                    <th colspan="4" class="text-right">مبلغ کل:</th>
                                    <th><?= number_format($sale['total_amount']) ?> افغانی</th>
                                </tr>
                                <?php if ($sale['discount'] > 0): ?>
                                    <tr class="table-warning">
                                        <th colspan="4" class="text-right">تخفیف:</th>
                                        <th><?= number_format($sale['discount']) ?> افغانی</th>
                                    </tr>
                                <?php endif; ?>
                                <tr class="table-success">
                                    <th colspan="4" class="text-right">مبلغ نهایی:</th>
                                    <th><?= number_format($sale['final_amount']) ?> افغانی</th>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    @media print {

        .btn,
        .card-header .btn {
            display: none !important;
        }

        .container-fluid {
            padding: 0;
        }

        .card {
            border: none;
            box-shadow: none;
        }
    }
</style>

<?php include 'includes/footer.php'; ?>