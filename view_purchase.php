<?php
require_once 'init_security.php';
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

require_once 'config/database.php';
require_once 'includes/SettingsHelper.php';
$database = new Database();
$db = $database->getConnection();
SettingsHelper::loadSettings($db);

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$id) {
    header('Location: purchases.php');
    return;
}

try {
    // Get purchase data with items in single query for better performance
    $query = "SELECT p.*, s.name as supplier_name FROM purchases p 
              LEFT JOIN suppliers s ON p.supplier_id = s.id 
              WHERE p.id = ?";
    $stmt = $db->prepare($query);
    $stmt->execute([$id]);
    $purchase = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$purchase) {
        header('Location: purchases.php');
        exit();
    }

    // Get purchase items
    $items_query = "SELECT pi.*, pr.name as product_name FROM purchase_items pi 
                    JOIN products pr ON pi.product_id = pr.id 
                    WHERE pi.purchase_id = ?";
    $items_stmt = $db->prepare($items_query);
    $items_stmt->execute([$id]);
    $items = $items_stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log('Purchase view error: ' . $e->getMessage());
    header('Location: purchases.php');
    exit();
}
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>مشاهده فاکتور خرید - مدیریت فروشگاه موتور</title>
    <link rel="stylesheet" href="assets/css/bootstrap.rtl.min.css">
    <link rel="stylesheet" href="assets/css/all.min.css">
    <link rel="stylesheet" href="assets/css/argon-dashboard-rtl.css">
</head>

<body>
    <?php include 'includes/header.php'; ?>

    <div class="container-fluid mt-4">
        <div class="row">
            <div class="col-12">
                <div class="card shadow">
                    <div class="card-header border-0">
                        <div class="row align-items-center">
                            <div class="col">
                                <h3 class="mb-0">فاکتور خرید #<?= $purchase['id'] ?></h3>
                            </div>
                            <div class="col text-left">
                                <a href="purchases.php" class="btn btn-secondary btn-sm">
                                    <i class="fas fa-arrow-right"></i> بازگشت
                                </a>
                                <button onclick="window.print()" class="btn btn-primary btn-sm">
                                    <i class="fas fa-print"></i> چاپ
                                </button>
                            </div>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="row mb-4">
                            <div class="col-md-6">
                                <h6 class="heading-small text-muted mb-4">اطلاعات فاکتور</h6>
                                <div class="pl-lg-4">
                                    <div class="row">
                                        <div class="col-lg-12">
                                            <div class="form-group">
                                                <label class="form-control-label">شماره فاکتور</label>
                                                <input type="text" class="form-control" value="#<?= $purchase['id'] ?>"
                                                    readonly>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-lg-12">
                                            <div class="form-group">
                                                <label class="form-control-label">تأمینکننده</label>
                                                <input type="text" class="form-control"
                                                    value="<?= sanitizeOutput($purchase['supplier_name']) ?>" readonly>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-lg-12">
                                            <div class="form-group">
                                                <label class="form-control-label">تاریخ</label>
                                                <input type="text" class="form-control"
                                                    value="<?= SettingsHelper::formatDateTime(strtotime($purchase['created_at']), $db) ?>"
                                                    readonly>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <h6 class="heading-small text-muted mb-4">خلاصه مالی</h6>
                                <div class="pl-lg-4">
                                    <div class="row">
                                        <div class="col-lg-12">
                                            <div class="form-group">
                                                <label class="form-control-label">مبلغ کل</label>
                                                <input type="text" class="form-control"
                                                    value="<?= number_format($purchase['total_amount']) ?> افغانی"
                                                    readonly>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <h6 class="heading-small text-muted mb-4">آیتمهای فاکتور</h6>
                        <div class="table-responsive">
                            <table class="table align-items-center table-flush">
                                <thead class="thead-light">
                                    <tr>
                                        <th scope="col">محصول</th>
                                        <th scope="col">تعداد</th>
                                        <th scope="col">قیمت واحد</th>
                                        <th scope="col">جمع</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($items as $item): ?>
                                        <tr>
                                            <td><?= sanitizeOutput($item['product_name']) ?></td>
                                            <td><?= $item['quantity'] ?></td>
                                            <td><?= number_format($item['unit_price']) ?> افغانی</td>
                                            <td><?= number_format($item['total_price']) ?> افغانی</td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                                <tfoot>
                                    <tr>
                                        <th colspan="3" class="text-left">مجموع کل:</th>
                                        <th><?= number_format($purchase['total_amount']) ?> افغانی</th>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php include 'includes/footer.php'; ?>
</body>

</html>