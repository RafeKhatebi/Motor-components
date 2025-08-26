<?php
require_once __DIR__ . '/init_security.php';
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/SettingsHelper.php';
$database = new Database();
$db = $database->getConnection();
SettingsHelper::loadSettings($db);

$page_title = 'مدیریت هزینه ها مالی';

// دریافت انواع  هزینه ها
$stmt = $db->query("SELECT * FROM transaction_types ORDER BY type, name");
$transaction_types = $stmt->fetchAll();

// دریافت فیلترها
$filter_type = $_GET['filter_type'] ?? '';
$filter_person = $_GET['filter_person'] ?? '';
$filter_date_from = $_GET['filter_date_from'] ?? '';
$filter_date_to = $_GET['filter_date_to'] ?? '';

// ساخت کوئری با فیلتر
$where_conditions = [];
$params = [];

if ($filter_type) {
    $where_conditions[] = "ft.transaction_type = ?";
    $params[] = $filter_type;
}
if ($filter_person) {
    $where_conditions[] = "ft.person_name LIKE ?";
    $params[] = "%$filter_person%";
}
if ($filter_date_from) {
    $where_conditions[] = "ft.transaction_date >= ?";
    $params[] = $filter_date_from;
}
if ($filter_date_to) {
    $where_conditions[] = "ft.transaction_date <= ?";
    $params[] = $filter_date_to;
}

$where_clause = $where_conditions ? 'WHERE ' . implode(' AND ', $where_conditions) : '';

$query = "SELECT ft.*, tt.name as type_name 
          FROM expense_transactions ft 
          JOIN transaction_types tt ON ft.type_id = tt.id 
          $where_clause 
          ORDER BY ft.transaction_date DESC, ft.created_at DESC";

$stmt = $db->prepare($query);
$stmt->execute($params);
$transactions = $stmt->fetchAll();

// محاسبه مجموع
$total_expenses = 0;
$total_withdrawals = 0;
foreach ($transactions as $transaction) {
    if ($transaction['transaction_type'] === 'expense') {
        $total_expenses += $transaction['amount'];
    } else {
        $total_withdrawals += $transaction['amount'];
    }
}

include __DIR__ . '/includes/header.php';
?>

<!-- Header -->
<div class="header bg-gradient-primary pb-8 pt-5 pt-md-8">
    <div class="container-fluid">
        <div class="header-body">
            <div class="row align-items-center py-4">
                <div class="col-lg-6 col-7">
                    <h6 class="h2 text-white d-inline-block mb-0">مدیریت هزینه ها مالی</h6>
                </div>
                <div class="col-lg-6 col-5 text-left">
                    <a href="#" class="btn btn-professional btn-sm" data-bs-toggle="modal" data-bs-target="#addTransactionModal">
                        <i class="fas fa-plus"></i> افزودن هزینه
                    </a>
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
                            <h3 class="mb-0">لیست هزینه ها</h3>
                        </div>
                    </div>
                </div>

                <!-- فیلترها -->
                <div class="card-body">
                    <div class="row mb-4">
                        <div class="col-12">
                            <div class="card bg-light">
                                <div class="card-body">
                                    <form method="GET" class="row g-3">
                                        <div class="col-md-3">
                                            <label class="form-label">نوع هزینه</label>
                                            <select name="filter_type" class="form-control">
                                                <option value="">همه انواع</option>
                                                <option value="expense" <?= $filter_type === 'expense' ? 'selected' : '' ?>>مصارف</option>
                                                <option value="withdrawal" <?= $filter_type === 'withdrawal' ? 'selected' : '' ?>>برداشتها</option>
                                            </select>
                                        </div>
                                        <div class="col-md-3">
                                            <label class="form-label">نام شخص</label>
                                            <input type="text" name="filter_person" class="form-control" placeholder="جستجو..." value="<?= sanitizeOutput($filter_person) ?>">
                                        </div>
                                        <div class="col-md-2">
                                            <label class="form-label">از تاریخ</label>
                                            <input type="date" name="filter_date_from" class="form-control" value="<?= $filter_date_from ?>">
                                        </div>
                                        <div class="col-md-2">
                                            <label class="form-label">تا تاریخ</label>
                                            <input type="date" name="filter_date_to" class="form-control" value="<?= $filter_date_to ?>">
                                        </div>
                                        <div class="col-md-2">
                                            <label class="form-label d-block">&nbsp;</label>
                                            <div class="d-flex gap-2">
                                                <button type="submit" class="btn btn-primary">
                                                    <i class="fas fa-search"></i> فیلتر
                                                </button>
                                                <a href="transactions.php" class="btn btn-secondary">
                                                    <i class="fas fa-times"></i> پاک
                                                </a>
                                            </div>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- خلاصه مالی -->
                    <div class="row mb-4">
                        <div class="col-lg-3 col-md-6 mb-3">
                            <div class="card bg-danger text-white">
                                <div class="card-body text-center">
                                    <i class="fas fa-shopping-cart fa-2x mb-2"></i>
                                    <h6>کل مصارف</h6>
                                    <h4><?= number_format($total_expenses) ?> افغانی</h4>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-3 col-md-6 mb-3">
                            <div class="card bg-warning text-white">
                                <div class="card-body text-center">
                                    <i class="fas fa-hand-holding-usd fa-2x mb-2"></i>
                                    <h6>کل برداشتها</h6>
                                    <h4><?= number_format($total_withdrawals) ?> افغانی</h4>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-3 col-md-6 mb-3">
                            <div class="card bg-info text-white">
                                <div class="card-body text-center">
                                    <i class="fas fa-calculator fa-2x mb-2"></i>
                                    <h6>کل خروجی</h6>
                                    <h4><?= number_format($total_expenses + $total_withdrawals) ?> افغانی</h4>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-3 col-md-6 mb-3">
                            <div class="card bg-success text-white">
                                <div class="card-body text-center">
                                    <i class="fas fa-list fa-2x mb-2"></i>
                                    <h6>تعداد هزینه</h6>
                                    <h4><?= count($transactions) ?> مورد</h4>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- جدول  هزینه ها -->
                <div class="table-responsive">
                    <table class="table align-items-center table-flush">
                        <thead class="thead-light">
                            <tr>
                                <th>کد هزینه</th>
                                <th>نوع</th>
                                <th>دسته</th>
                                <th>مبلغ</th>
                                <th>شخص</th>
                                <th>تاریخ</th>
                                <th>عملیات</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($transactions)): ?>
                            <tr>
                                <td colspan="7" class="text-center py-4">
                                    <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                                    <p class="text-muted">هیچ هزینهی یافت نشد</p>
                                </td>
                            </tr>
                            <?php else: ?>
                            <?php foreach ($transactions as $transaction): ?>
                            <tr>
                                <td><code><?= $transaction['transaction_code'] ?></code></td>
                                <td>
                                    <span class="badge badge-<?= $transaction['transaction_type'] === 'expense' ? 'danger' : 'warning' ?>">
                                        <?= $transaction['transaction_type'] === 'expense' ? 'مصرف' : 'برداشت' ?>
                                    </span>
                                </td>
                                <td><?= sanitizeOutput($transaction['type_name']) ?></td>
                                <td><?= number_format($transaction['amount']) ?> افغانی</td>
                                <td><?= sanitizeOutput($transaction['person_name']) ?></td>
                                <td><?= date('Y/m/d', strtotime($transaction['transaction_date'])) ?></td>
                                <td>
                                    <button onclick="editTransaction(<?= $transaction['id'] ?>)" class="btn btn-professional btn-warning btn-sm">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button onclick="deleteTransaction(<?= $transaction['id'] ?>)" class="btn btn-professional btn-danger btn-sm">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <?php include __DIR__ . '/includes/footer.php'; ?>

    <!-- Modal افزودن/ویرایش هزینه -->
    <div class="modal fade modal-professional" id="addTransactionModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">افزودن هزینه جدید</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="transactionForm" onsubmit="event.preventDefault(); submitTransaction();">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="form-control-label">نوع هزینه</label>
                                    <select name="transaction_type" class="form-control" required>
                                        <option value="">انتخاب کنید</option>
                                        <option value="expense">مصرف</option>
                                        <option value="withdrawal">برداشت</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="form-control-label">دسته</label>
                                    <select name="type_id" class="form-control" required>
                                        <option value="">ابتدا نوع هزینه را انتخاب کنید</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="form-control-label">مبلغ (افغانی)</label>
                                    <input type="number" name="amount" class="form-control" min="1" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="form-control-label">نام شخص</label>
                                    <input type="text" name="person_name" class="form-control" required>
                                </div>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="form-control-label">تاریخ</label>
                            <input type="date" name="transaction_date" class="form-control" value="<?= date('Y-m-d') ?>" required>
                        </div>
                        <div class="form-group">
                            <label class="form-control-label">توضیحات</label>
                            <textarea name="description" class="form-control" rows="3"></textarea>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-professional btn-secondary" data-bs-dismiss="modal">انصراف</button>
                    <button type="submit" form="transactionForm" class="btn btn-professional btn-success">ذخیره</button>
                </div>
            </div>
        </div>
    </div>

    <script>
        const transactionTypes = <?= json_encode($transaction_types) ?>;

        // تغییر دستهها بر اساس نوع هزینه
        function updateTypeOptions(selectedType, selectedValue = null) {
            const typeSelect = document.querySelector('select[name="type_id"]');
            typeSelect.innerHTML = '<option value="">انتخاب کنید</option>';
            
            transactionTypes.forEach(type => {
                if (type.type === selectedType) {
                    const option = document.createElement('option');
                    option.value = type.id;
                    option.textContent = type.name;
                    if (selectedValue && type.id == selectedValue) {
                        option.selected = true;
                    }
                    typeSelect.appendChild(option);
                }
            });
        }

        // رویداد تغییر نوع هزینه
        document.addEventListener('change', function(e) {
            if (e.target.name === 'transaction_type') {
                updateTypeOptions(e.target.value);
            }
        });

        // افزودن/ویرایش هزینه
        async function submitTransaction() {
            const form = document.getElementById('transactionForm');
            const formData = new FormData(form);
            
            const isEdit = formData.has('id');
            const url = isEdit ? 'api/update_transaction.php' : 'api/add_transaction.php';
            
            try {
                const response = await fetch(url, {
                    method: 'POST',
                    body: formData
                });
                
                const result = await response.json();
                
                if (result.success) {
                    showAlert('عملیات با موفقیت انجام شد', 'success');
                    bootstrap.Modal.getInstance(document.getElementById('addTransactionModal')).hide();
                    setTimeout(() => location.reload(), 1000);
                } else {
                    showAlert(result.message || 'خطا در انجام عملیات', 'error');
                }
            } catch (error) {
                showAlert('خطا در ارتباط با سرور', 'error');
            }
        }

        // ویرایش هزینه
        async function editTransaction(id) {
            try {
                const response = await fetch(`api/get_transaction.php?id=${id}`);
                const result = await response.json();
                
                if (result.success) {
                    const transaction = result.transaction;
                    const form = document.getElementById('transactionForm');
                    
                    form.querySelector('select[name="transaction_type"]').value = transaction.transaction_type;
                    updateTypeOptions(transaction.transaction_type, transaction.type_id);
                    form.querySelector('input[name="amount"]').value = transaction.amount;
                    form.querySelector('input[name="person_name"]').value = transaction.person_name;
                    form.querySelector('input[name="transaction_date"]').value = transaction.transaction_date;
                    form.querySelector('textarea[name="description"]').value = transaction.description || '';
                    
                    document.querySelector('#addTransactionModal .modal-title').textContent = 'ویرایش هزینه';
                    if (!form.querySelector('input[name="id"]')) {
                        const idInput = document.createElement('input');
                        idInput.type = 'hidden';
                        idInput.name = 'id';
                        form.appendChild(idInput);
                    }
                    form.querySelector('input[name="id"]').value = transaction.id;
                    
                    new bootstrap.Modal(document.getElementById('addTransactionModal')).show();
                }
            } catch (error) {
                showAlert('خطا در دریافت اطلاعات', 'error');
            }
        }

        // حذف هزینه
        async function deleteTransaction(id) {
            if (confirm('آیا از حذف این هزینه اطمینان دارید؟')) {
                try {
                    const response = await fetch('api/delete_transaction.php', {
                        method: 'POST',
                        headers: {'Content-Type': 'application/json'},
                        body: JSON.stringify({id: id})
                    });
                    
                    const result = await response.json();
                    
                    if (result.success) {
                        showAlert('هزینه با موفقیت حذف شد', 'success');
                        setTimeout(() => location.reload(), 1000);
                    } else {
                        showAlert(result.message || 'خطا در حذف هزینه', 'error');
                    }
                } catch (error) {
                    showAlert('خطا در ارتباط با سرور', 'error');
                }
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

        // ریست فرم هنگام بستن مودال
        document.getElementById('addTransactionModal').addEventListener('hidden.bs.modal', function() {
            const form = document.getElementById('transactionForm');
            form.reset();
            document.querySelector('#addTransactionModal .modal-title').textContent = 'افزودن هزینه جدید';
            const idInput = form.querySelector('input[name="id"]');
            if (idInput) idInput.remove();
            form.querySelector('select[name="type_id"]').innerHTML = '<option value="">ابتدا نوع هزینه را انتخاب کنید</option>';
        });
    </script>