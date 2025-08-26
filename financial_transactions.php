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

$page_title = 'گزارش تراکنشهای مالی';

$query = "SELECT ft.*, u.full_name as user_name 
          FROM financial_transactions ft 
          LEFT JOIN users u ON ft.created_by = u.id 
          ORDER BY ft.created_at DESC";
$stmt = $db->prepare($query);
$stmt->execute();
$transactions = $stmt->fetchAll(PDO::FETCH_ASSOC);

include 'includes/header.php';
?>

<!-- Header -->
<div class="header bg-gradient-primary pb-8 pt-5 pt-md-8">
    <div class="container-fluid">
        <div class="header-body">
            <div class="row align-items-center py-4">
                <div class="col-lg-6 col-7">
                    <h6 class="h2 text-white d-inline-block mb-0">گزارش تراکنشهای مالی</h6>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Page content -->
<div class="container-fluid mt--7">
    <div class="row">
        <div class="col">
            <div class="card card-professional">
                <div class="card-header border-0">
                    <div class="row align-items-center">
                        <div class="col">
                            <h3 class="mb-0">تراکنشهای مالی</h3>
                        </div>
                        <div class="col text-left">
                            <input type="text" class="form-control form-control-sm" placeholder="جستجو..."
                                id="searchInput" style="width: 200px; display: inline-block;">
                        </div>
                    </div>
                </div>
                <div class="table-responsive">
                    <table class="table align-items-center table-flush" id="transactionsTable">
                        <thead class="thead-light">
                            <tr>
                                <th scope="col">نوع تراکنش</th>
                                <th scope="col">شماره مرجع</th>
                                <th scope="col">مبلغ</th>
                                <th scope="col">توضیحات</th>
                                <th scope="col">کاربر</th>
                                <th scope="col">تاریخ</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($transactions as $transaction): ?>
                                <tr>
                                    <td>
                                        <?php
                                        $types = [
                                            'sale' => 'فروش',
                                            'purchase' => 'خرید', 
                                            'sale_return' => 'برگشت فروش',
                                            'purchase_return' => 'برگشت خرید'
                                        ];
                                        $typeClass = [
                                            'sale' => 'badge-success',
                                            'purchase' => 'badge-info',
                                            'sale_return' => 'badge-danger', 
                                            'purchase_return' => 'badge-warning'
                                        ];
                                        ?>
                                        <span class="badge <?= $typeClass[$transaction['transaction_type']] ?? 'badge-secondary' ?>">
                                            <?= $types[$transaction['transaction_type']] ?? $transaction['transaction_type'] ?>
                                        </span>
                                    </td>
                                    <td>#<?= sanitizeOutput($transaction['reference_id']) ?></td>
                                    <td>
                                        <span class="<?= $transaction['amount'] >= 0 ? 'text-success' : 'text-danger' ?>">
                                            <?= number_format($transaction['amount']) ?> افغانی
                                        </span>
                                    </td>
                                    <td><?= sanitizeOutput($transaction['description']) ?></td>
                                    <td><?= sanitizeOutput($transaction['user_name']) ?></td>
                                    <td><?= SettingsHelper::formatDateTime(strtotime($transaction['created_at']), $db) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    // Search functionality
    document.getElementById('searchInput').addEventListener('keyup', function () {
        const filter = this.value.toLowerCase();
        const rows = document.querySelectorAll('#transactionsTable tbody tr');

        rows.forEach(row => {
            const text = row.textContent.toLowerCase();
            row.style.display = text.includes(filter) ? '' : 'none';
        });
    });
</script>

<?php include 'includes/footer.php'; ?>