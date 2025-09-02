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

<div class="container-fluid mt-4">
    <!-- ثبت پرداخت -->
    <div class="row mb-4">
        <div class="col">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-money-bill-wave me-2"></i>
                        ثبت پرداخت جدید
                    </h5>
                </div>
                <div class="card-body">
                    <form id="quickPaymentForm" onsubmit="event.preventDefault(); submitQuickPayment();">
                        <div class="d-flex gap-3 align-items-end mb-3">
                            <div class="form-group" style="flex: 1;">
                                <label class="form-label">نوع پرداخت</label>
                                <select name="payment_type" class="form-control" required>
                                    <option value="">انتخاب کنید</option>
                                    <option value="sale">پرداخت قرض مشتری</option>
                                    <option value="purchase">پرداخت به تأمین کننده</option>
                                </select>
                            </div>
                            <div class="form-group" style="flex: 1;">
                                <label class="form-label">شماره فاکتور</label>
                                <input type="number" name="invoice_id" class="form-control" min="1" required>
                            </div>
                            <div class="form-group" style="flex: 1;">
                                <label class="form-label">مبلغ پرداخت</label>
                                <input type="number" name="amount" class="form-control" min="1" step="0.01" required>
                                <small class="form-text text-muted">حداکثر: <span id="maxAmountDisplay">-</span> افغانی</small>
                            </div>
                            <div class="form-group" style="flex: 1;">
                                <label class="form-label">روش پرداخت</label>
                                <select name="payment_method" class="form-control">
                                    <option value="cash">نقدی</option>
                                    <option value="bank">بانکی</option>
                                    <option value="check">چک</option>
                                </select>
                            </div>
                            <div class="form-group" style="flex: 1;">
                                <label class="form-label">تاریخ پرداخت</label>
                                <input type="date" name="payment_date" class="form-control" value="<?= date('Y-m-d') ?>" required>
                            </div>
                            <div class="form-group" style="flex: 1;">
                                <label class="form-label">یادداشت</label>
                                <input type="text" name="notes" class="form-control" placeholder="یادداشت اختیاری...">
                            </div>
                            <div class="form-group">
                                <button type="submit" class="btn btn-success">
                                    <i class="fas fa-plus"></i> ثبت
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- آمار کلی -->
    <div class="d-flex w-100 gap-3 mb-4">
        <div style="flex: 1;">
            <div class="card bg-danger text-white">
                <div class="card-body text-center py-3">
                    <i class="fas fa-arrow-down fa-2x mb-2"></i>
                    <div class="h6">کل قرض مشتریان</div>
                    <h4 class="mb-0"><?= number_format($total_customer_debt) ?> افغانی</h4>
                </div>
            </div>
        </div>
        <div style="flex: 1;">
            <div class="card bg-success text-white">
                <div class="card-body text-center py-3">
                    <i class="fas fa-arrow-up fa-2x mb-2"></i>
                    <div class="h6">کل طلب از تأمین کنندگان</div>
                    <h4 class="mb-0"><?= number_format($total_supplier_credit) ?> افغانی</h4>
                </div>
            </div>
        </div>
    </div>
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
                                            onclick="fillPaymentForm('sale', <?= $debt['id'] ?>, <?= $debt['remaining_amount'] ?>)"
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
                                            onclick="fillPaymentForm('purchase', <?= $credit['id'] ?>, <?= $credit['remaining_amount'] ?>)"
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



<script>
    function fillPaymentForm(type, id, maxAmount) {
        const form = document.getElementById('quickPaymentForm');
        form.querySelector('select[name="payment_type"]').value = type;
        form.querySelector('input[name="invoice_id"]').value = id;
        form.querySelector('input[name="amount"]').max = maxAmount;
        form.querySelector('input[name="amount"]').value = maxAmount;
        document.getElementById('maxAmountDisplay').textContent = maxAmount.toLocaleString();
        
        // Scroll to form
        form.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }

    async function submitQuickPayment() {
        const form = document.getElementById('quickPaymentForm');
        const formData = new FormData(form);
        formData.append('id', formData.get('invoice_id'));
        formData.append('type', formData.get('payment_type'));
        formData.append('payment_date', new Date().toISOString().split('T')[0]);

        try {
            const response = await fetch('api/add_payment.php', {
                method: 'POST',
                body: formData
            });

            const result = await response.json();

            if (result.success) {
                showAlert(result.message, 'success');
                form.reset();
                setTimeout(() => location.reload(), 1000);
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

<?php include 'includes/footer-modern.php'; ?>