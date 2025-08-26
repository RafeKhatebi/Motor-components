<?php
require_once 'init_security.php';
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

require_once 'config/database.php';
require_once 'includes/functions.php';
require_once 'includes/SettingsHelper.php';
require_once 'includes/DateManager.php';

$database = new Database();
$db = $database->getConnection();
SettingsHelper::loadSettings($db);

$page_title = 'مدیریت قرض ها و طلب ها';

// قرض مشتریان (فروش قرضی)
$customer_debts_query = "SELECT s.id, s.customer_id, COALESCE(c.name, 'مشتری نقدی') as customer_name, 
                                s.final_amount, COALESCE(s.paid_amount, 0) as paid_amount, 
                                COALESCE(s.remaining_amount, 0) as remaining_amount, 
                                COALESCE(s.payment_status, 'paid') as payment_status, s.created_at
                         FROM sales s
                         LEFT JOIN customers c ON s.customer_id = c.id
                         WHERE COALESCE(s.payment_type, 'cash') = 'credit' AND COALESCE(s.remaining_amount, 0) > 0
                         ORDER BY s.created_at DESC";
$customer_debts_stmt = $db->prepare($customer_debts_query);
$customer_debts_stmt->execute();
$customer_debts = $customer_debts_stmt->fetchAll(PDO::FETCH_ASSOC);

// طلب از تأمین کنندگان (خرید قرضی)
$supplier_credits_query = "SELECT p.id, p.supplier_id, s.name as supplier_name,
                                  p.total_amount, COALESCE(p.paid_amount, 0) as paid_amount, 
                                  COALESCE(p.remaining_amount, 0) as remaining_amount, 
                                  COALESCE(p.payment_status, 'paid') as payment_status, p.created_at
                           FROM purchases p
                           LEFT JOIN suppliers s ON p.supplier_id = s.id
                           WHERE COALESCE(p.payment_type, 'cash') = 'credit' AND COALESCE(p.remaining_amount, 0) > 0
                           ORDER BY p.created_at DESC";
$supplier_credits_stmt = $db->prepare($supplier_credits_query);
$supplier_credits_stmt->execute();
$supplier_credits = $supplier_credits_stmt->fetchAll(PDO::FETCH_ASSOC);

// خلاصه آمار
$total_customer_debt = array_sum(array_column($customer_debts, 'remaining_amount'));
$total_supplier_credit = array_sum(array_column($supplier_credits, 'remaining_amount'));

include 'includes/header.php';
?>

<div class="header bg-gradient-primary pb-8 pt-5 pt-md-8">
    <div class="container-fluid">
        <div class="header-body">
            <div class="row align-items-center py-4">
                <div class="col-lg-6 col-7">
                    <h6 class="h2 text-white d-inline-block mb-0">مدیریت قرض ها و طلب ها</h6>
                </div>
            </div>

            <!-- آمار کلی -->
            <div class="row">
                <div class="col-xl-6 col-lg-6">
                    <div class="card card-stats mb-4 mb-xl-0">
                        <div class="card-body">
                            <div class="row">
                                <div class="col">
                                    <h5 class="card-title text-uppercase text-muted mb-0">کل قرض مشتریان</h5>
                                    <span class="h2 font-weight-bold mb-0 text-danger">
                                        <?= number_format($total_customer_debt) ?> افغانی
                                    </span>
                                </div>
                                <div class="col-auto">
                                    <div class="icon icon-shape bg-danger text-white rounded-circle shadow">
                                        <i class="fas fa-arrow-down"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-xl-6 col-lg-6">
                    <div class="card card-stats mb-4 mb-xl-0">
                        <div class="card-body">
                            <div class="row">
                                <div class="col">
                                    <h5 class="card-title text-uppercase text-muted mb-0">کل طلب از تأمین کنندگان</h5>
                                    <span class="h2 font-weight-bold mb-0 text-success">
                                        <?= number_format($total_supplier_credit) ?> افغانی
                                    </span>
                                </div>
                                <div class="col-auto">
                                    <div class="icon icon-shape bg-success text-white rounded-circle shadow">
                                        <i class="fas fa-arrow-up"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="container-fluid mt--7">
    <!-- قرض مشتریان -->
    <div class="row mb-4">
        <div class="col">
            <div class="card">
                <div class="card-header">
                    <h3 class="mb-0">قرض مشتریان</h3>
                </div>
                <div class="table-responsive">
                    <table class="table align-items-center table-flush">
                        <thead class="thead-light">
                            <tr>
                                <th>شماره فاکتور</th>
                                <th>مشتری</th>
                                <th>مبلغ کل</th>
                                <th>پرداخت شده</th>
                                <th>باقیمانده</th>
                                <th>تاریخ</th>
                                <th>عملیات</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($customer_debts as $debt): ?>
                                <tr>
                                    <td>#<?= $debt['id'] ?></td>
                                    <td><?= sanitizeOutput($debt['customer_name']) ?></td>
                                    <td><?= number_format($debt['final_amount']) ?> افغانی</td>
                                    <td><?= number_format($debt['paid_amount']) ?> افغانی</td>
                                    <td class="text-danger"><?= number_format($debt['remaining_amount']) ?> افغانی</td>
                                    <td><?= DateManager::formatDateForDisplay($debt['created_at']) ?></td>
                                    <td>
                                        <button
                                            onclick="showPaymentModal('sale', <?= $debt['id'] ?>, <?= $debt['remaining_amount'] ?>)"
                                            class="btn btn-success btn-sm">
                                            <i class="fas fa-money-bill"></i> پرداخت
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

    <!-- طلب از تأمین کنندگان -->
    <div class="row">
        <div class="col">
            <div class="card">
                <div class="card-header">
                    <h3 class="mb-0">طلب از تأمین کنندگان</h3>
                </div>
                <div class="table-responsive">
                    <table class="table align-items-center table-flush">
                        <thead class="thead-light">
                            <tr>
                                <th>شماره خرید</th>
                                <th>تأمین کننده</th>
                                <th>مبلغ کل</th>
                                <th>پرداخت شده</th>
                                <th>باقیمانده</th>
                                <th>تاریخ</th>
                                <th>عملیات</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($supplier_credits as $credit): ?>
                                <tr>
                                    <td>#<?= $credit['id'] ?></td>
                                    <td><?= sanitizeOutput($credit['supplier_name']) ?></td>
                                    <td><?= number_format($credit['total_amount']) ?> افغانی</td>
                                    <td><?= number_format($credit['paid_amount']) ?> افغانی</td>
                                    <td class="text-success"><?= number_format($credit['remaining_amount']) ?> افغانی</td>
                                    <td><?= DateManager::formatDateForDisplay($credit['created_at']) ?></td>
                                    <td>
                                        <button
                                            onclick="showPaymentModal('purchase', <?= $credit['id'] ?>, <?= $credit['remaining_amount'] ?>)"
                                            class="btn btn-primary btn-sm">
                                            <i class="fas fa-money-bill"></i> پرداخت
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
</div>

<!-- Modal پرداخت -->
<div class="modal fade" id="paymentModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">ثبت پرداخت</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="paymentForm">
                    <input type="hidden" id="paymentType">
                    <input type="hidden" id="paymentId">
                    <div class="form-group">
                        <label>مبلغ پرداخت</label>
                        <input type="number" id="paymentAmount" class="form-control" min="1" step="0.01" required>
                        <small class="form-text text-muted">حداکثر: <span id="maxAmount"></span> افغانی</small>
                    </div>
                    <div class="form-group">
                        <label>تاریخ پرداخت</label>
                        <input type="date" id="paymentDate" class="form-control" value="<?= date('Y-m-d') ?>" required>
                    </div>
                    <div class="form-group">
                        <label>روش پرداخت</label>
                        <select id="paymentMethod" class="form-control">
                            <option value="cash">نقدی</option>
                            <option value="bank">بانکی</option>
                            <option value="check">چک</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>یادداشت</label>
                        <textarea id="paymentNotes" class="form-control" rows="3"></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">انصراف</button>
                <button type="button" class="btn btn-success" onclick="submitPayment()">ثبت پرداخت</button>
            </div>
        </div>
    </div>
</div>

<script>
    function showPaymentModal(type, id, maxAmount) {
        document.getElementById('paymentType').value = type;
        document.getElementById('paymentId').value = id;
        document.getElementById('paymentAmount').max = maxAmount;
        document.getElementById('maxAmount').textContent = maxAmount.toLocaleString();

        new bootstrap.Modal(document.getElementById('paymentModal')).show();
    }

    async function submitPayment() {
        const form = document.getElementById('paymentForm');
        const formData = new FormData();

        formData.append('type', document.getElementById('paymentType').value);
        formData.append('id', document.getElementById('paymentId').value);
        formData.append('amount', document.getElementById('paymentAmount').value);
        formData.append('payment_date', document.getElementById('paymentDate').value);
        formData.append('payment_method', document.getElementById('paymentMethod').value);
        formData.append('notes', document.getElementById('paymentNotes').value);

        try {
            const response = await fetch('api/add_payment.php', {
                method: 'POST',
                body: formData
            });

            const result = await response.json();

            if (result.success) {
                showAlert(result.message, 'success');
                bootstrap.Modal.getInstance(document.getElementById('paymentModal')).hide();
                location.reload();
            } else {
                showAlert(result.message, 'error');
            }
        } catch (error) {
            showAlert('خطا در ارتباط با سرور', 'error');
        }
    }

    function showAlert(message, type) {
        const alertClass = type === 'success' ? 'alert-success' : 'alert-danger';
        const alertHtml = `<div class="alert ${alertClass} alert-dismissible fade show" role="alert">
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>`;
        document.body.insertAdjacentHTML('afterbegin', alertHtml);
    }
</script>

<?php include 'includes/footer.php'; ?>