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
try {
    $stmt = $db->query("SELECT * FROM transaction_types ORDER BY type, name");
    $transaction_types = $stmt->fetchAll();
} catch (PDOException $e) {
    // Create tables if they don't exist
    $db->exec("CREATE TABLE IF NOT EXISTS transaction_types (
        id INT AUTO_INCREMENT PRIMARY KEY,
        type ENUM('expense', 'withdrawal') NOT NULL,
        name VARCHAR(255) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");

    $db->exec("CREATE TABLE IF NOT EXISTS expense_transactions (
        id INT AUTO_INCREMENT PRIMARY KEY,
        transaction_code VARCHAR(50) UNIQUE,
        type_id INT,
        transaction_type ENUM('expense', 'withdrawal') NOT NULL,
        amount DECIMAL(10,2) NOT NULL,
        person_name VARCHAR(255) NOT NULL,
        transaction_date DATE NOT NULL,
        description TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (type_id) REFERENCES transaction_types(id)
    )");

    // Insert default transaction types
    $db->exec("INSERT IGNORE INTO transaction_types (type, name) VALUES 
        ('expense', 'کرایه'),
        ('expense', 'برق'),
        ('expense', 'آب'),
        ('expense', 'تلفن'),
        ('expense', 'حقوق'),
        ('expense', 'متفرقه'),
        ('withdrawal', 'برداشت شخصی'),
        ('withdrawal', 'سرمایهگذاری')");

    $stmt = $db->query("SELECT * FROM transaction_types ORDER BY type, name");
    $transaction_types = $stmt->fetchAll();
}

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
try {
    $query = "SELECT ft.*, tt.name as type_name 
              FROM expense_transactions ft 
              JOIN transaction_types tt ON ft.type_id = tt.id 
              $where_clause 
              ORDER BY ft.transaction_date DESC, ft.created_at DESC";

    $stmt = $db->prepare($query);
    $stmt->execute($params);
    $transactions = $stmt->fetchAll();
} catch (PDOException $e) {
    $transactions = [];
    error_log('Transaction query error: ' . $e->getMessage());
}

// Pagination
$items_per_page = 30;
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$offset = ($page - 1) * $items_per_page;

// Count total transactions
$count_query = "SELECT COUNT(*) as total FROM expense_transactions ft JOIN transaction_types tt ON ft.type_id = tt.id $where_clause";
$count_stmt = $db->prepare($count_query);
$count_stmt->execute($params);
$total_items = $count_stmt->fetch(PDO::FETCH_ASSOC)['total'];
$total_pages = ceil($total_items / $items_per_page);

$query = "SELECT ft.*, tt.name as type_name 
          FROM expense_transactions ft 
          JOIN transaction_types tt ON ft.type_id = tt.id 
          $where_clause 
          ORDER BY ft.transaction_date DESC, ft.created_at DESC
          LIMIT :limit OFFSET :offset";

$stmt = $db->prepare($query);
foreach ($params as $key => $value) {
    $stmt->bindValue($key + 1, $value);
}
$stmt->bindValue(':limit', $items_per_page, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
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

<!-- Page content -->
<div class="container-fluid mt-4">
    <div class="row">
        <div class="col">
            <div class="card card-professional">
                <!-- Add Transaction Form -->
                <div class="card-header border-0">
                    <h5 class="mb-3"><i class="fas fa-plus"></i> افزودن هزینه جدید</h5>
                    <form id="transactionForm" onsubmit="event.preventDefault(); submitTransaction();">
                        <!-- ردیف اول -->
                        <div class="d-flex gap-3 align-items-end mb-3">
                            <div class="form-group" style="flex: 1;">
                                <label class="form-label">نوع هزینه</label>
                                <select name="transaction_type" class="form-control" required>
                                    <option value="">انتخاب کنید</option>
                                    <option value="expense">مصرف</option>
                                    <option value="withdrawal">برداشت</option>
                                </select>
                            </div>
                            <div class="form-group" style="flex: 1;">
                                <label class="form-label">دسته</label>
                                <select name="type_id" class="form-control" required>
                                    <option value="">ابتدا نوع هزینه را انتخاب کنید</option>
                                </select>
                            </div>
                            <div class="form-group" style="flex: 1;">
                                <label class="form-label">مبلغ (افغانی)</label>
                                <input type="number" name="amount" class="form-control" min="1" required>
                            </div>
                        </div>
                        <!-- ردیف دوم -->
                        <div class="d-flex gap-3 align-items-end mb-3">
                            <div class="form-group" style="flex: 1;">
                                <label class="form-label">نام شخص</label>
                                <input type="text" name="person_name" class="form-control" required>
                            </div>
                            <div class="form-group" style="flex: 1;">
                                <label class="form-label">تاریخ</label>
                                <input type="date" name="transaction_date" class="form-control"
                                    value="<?= date('Y-m-d') ?>" required>
                            </div>
                            <div class="form-group" style="flex: 1;">
                                <label class="form-label">توضیحات</label>
                                <textarea name="description" class="form-control" rows="1"
                                    placeholder="توضیحات اختیاری..."></textarea>
                            </div>
                            <div class="form-group">
                                <button type="submit" class="btn btn-success">
                                    <i class="fas fa-plus"></i> افزودن
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="card-header border-0">
                    <h3 class="mb-0">لیست هزینه ها</h3>
                </div>

                <div class="card-body">
                    <!-- خلاصه مالی -->
                    <div class="d-flex w-100 gap-3 mb-4">
                        <div style="flex: 1;">
                            <div class="card bg-danger text-white">
                                <div class="card-body text-center py-3">
                                    <i class="fas fa-money-bill-wave fa-2x mb-2"></i>
                                    <div class="h6">کل مصارف</div>
                                    <h4 class="mb-0"><?= number_format($total_expenses) ?></h4>
                                </div>
                            </div>
                        </div>
                        <div style="flex: 1;">
                            <div class="card bg-warning text-white">
                                <div class="card-body text-center py-3">
                                    <i class="fas fa-hand-holding-usd fa-2x mb-2"></i>
                                    <div class="h6">کل برداشتها</div>
                                    <h4 class="mb-0"><?= number_format($total_withdrawals) ?></h4>
                                </div>
                            </div>
                        </div>
                        <div style="flex: 1;">
                            <div class="card bg-info text-white">
                                <div class="card-body text-center py-3">
                                    <i class="fas fa-arrow-down fa-2x mb-2"></i>
                                    <div class="h6">کل خروجی</div>
                                    <h4 class="mb-0"><?= number_format($total_expenses + $total_withdrawals) ?></h4>
                                </div>
                            </div>
                        </div>
                        <div style="flex: 1;">
                            <div class="card bg-success text-white">
                                <div class="card-body text-center py-3">
                                    <i class="fas fa-list-ol fa-2x mb-2"></i>
                                    <div class="h6">تعداد هزینه</div>
                                    <h4 class="mb-0"><?= count($transactions) ?></h4>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- فیلترها -->
                    <div class="row mb-4">
                        <div class="col-12">
                            <div class="card bg-light">
                                <div class="card-body">
                                    <form method="GET">
                                        <!-- سطر اول: نوع هزینه و نام شخص -->
                                        <div class="d-flex gap-3 mb-3">
                                            <div style="flex: 1;">
                                                <label class="form-label">نوع هزینه</label>
                                                <select name="filter_type" class="form-control">
                                                    <option value="">همه انواع</option>
                                                    <option value="expense" <?= $filter_type === 'expense' ? 'selected' : '' ?>>مصارف</option>
                                                    <option value="withdrawal" <?= $filter_type === 'withdrawal' ? 'selected' : '' ?>>برداشتها</option>
                                                </select>
                                            </div>
                                            <div style="flex: 1;">
                                                <label class="form-label">نام شخص</label>
                                                <input type="text" name="filter_person" class="form-control"
                                                    placeholder="جستجو..."
                                                    value="<?= sanitizeOutput($filter_person) ?>">
                                            </div>
                                        </div>
                                        <!-- سطر دوم: تاریخها و دکمه ها -->
                                        <div class="d-flex gap-3">
                                            <div style="flex: 1;">
                                                <label class="form-label">از تاریخ</label>
                                                <input type="date" name="filter_date_from" class="form-control"
                                                    value="<?= $filter_date_from ?>">
                                            </div>
                                            <div style="flex: 1;">
                                                <label class="form-label">تا تاریخ</label>
                                                <input type="date" name="filter_date_to" class="form-control"
                                                    value="<?= $filter_date_to ?>">
                                            </div>
                                            <div style="flex: 1;">
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
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- جدول  هزینه ها -->
                <div class="table-responsive">
                    <table class="table align-items-center table-flush table-sm">
                        <thead class="thead-light">
                            <tr>
                                <th class="text-center" style="width: 10%">کد</th>
                                <th class="text-center" style="width: 10%">نوع</th>
                                <th class="text-center" style="width: 15%">دسته</th>
                                <th class="text-center" style="width: 15%">مبلغ</th>
                                <th class="text-center" style="width: 20%">شخص</th>
                                <th class="text-center" style="width: 15%">تاریخ</th>
                                <th class="text-center" style="width: 15%">عملیات</th>
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
                                        <td class="text-center">
                                            <small><code><?= $transaction['transaction_code'] ?></code></small>
                                        </td>
                                        <td class="text-center">
                                            <span
                                                class="badge badge-<?= $transaction['transaction_type'] === 'expense' ? 'danger' : 'warning' ?> badge-sm">
                                                <?= $transaction['transaction_type'] === 'expense' ? 'مصرف' : 'برداشت' ?>
                                            </span>
                                        </td>
                                        <td class="text-center"><small><?= sanitizeOutput($transaction['type_name']) ?></small>
                                        </td>
                                        <td class="text-center"><small><?= number_format($transaction['amount']) ?></small></td>
                                        <td class="text-center">
                                            <small><?= sanitizeOutput($transaction['person_name']) ?></small>
                                        </td>
                                        <td class="text-center">
                                            <small><?= date('Y/m/d', strtotime($transaction['transaction_date'])) ?></small>
                                        </td>
                                        <td class="text-center">
                                            <button onclick="editTransaction(<?= $transaction['id'] ?>)"
                                                class="btn btn-warning btn-xs">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <button onclick="deleteTransaction(<?= $transaction['id'] ?>)"
                                                class="btn btn-danger btn-xs">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
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
                                        <a class="page-link"
                                            href="?page=<?= $page - 1 ?>&filter_type=<?= urlencode($filter_type) ?>&filter_person=<?= urlencode($filter_person) ?>&filter_date_from=<?= urlencode($filter_date_from) ?>&filter_date_to=<?= urlencode($filter_date_to) ?>">
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
                                        <a class="page-link"
                                            href="?page=<?= $i ?>&filter_type=<?= urlencode($filter_type) ?>&filter_person=<?= urlencode($filter_person) ?>&filter_date_from=<?= urlencode($filter_date_from) ?>&filter_date_to=<?= urlencode($filter_date_to) ?>"><?= $i ?></a>
                                    </li>
                                <?php endfor; ?>

                                <?php if ($page < $total_pages): ?>
                                    <li class="page-item">
                                        <a class="page-link"
                                            href="?page=<?= $page + 1 ?>&filter_type=<?= urlencode($filter_type) ?>&filter_person=<?= urlencode($filter_person) ?>&filter_date_from=<?= urlencode($filter_date_from) ?>&filter_date_to=<?= urlencode($filter_date_to) ?>">
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
                                    <?= $total_items ?> هزینه
                                </small>
                            </div>
                        </nav>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Edit Transaction Modal -->
    <div class="modal fade" id="editTransactionModal" tabindex="-1" style="display: none;">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">ویرایش هزینه</h5>
                    <button type="button" class="btn-close" onclick="closeEditModal()"></button>
                </div>
                <div class="modal-body">
                    <form id="editTransactionForm">
                        <input type="hidden" name="id" id="editTransactionId">
                        <div class="row">
                            <div class="col-md-6">
                                <label class="form-label">نوع هزینه</label>
                                <select name="transaction_type" id="editTransactionType" class="form-control" required>
                                    <option value="expense">مصرف</option>
                                    <option value="withdrawal">برداشت</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">دسته</label>
                                <select name="type_id" id="editTypeId" class="form-control" required>
                                    <option value="">انتخاب کنید</option>
                                </select>
                            </div>
                        </div>
                        <div class="row mt-3">
                            <div class="col-md-6">
                                <label class="form-label">مبلغ (افغانی)</label>
                                <input type="number" name="amount" id="editAmount" class="form-control" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">نام شخص</label>
                                <input type="text" name="person_name" id="editPersonName" class="form-control" required>
                            </div>
                        </div>
                        <div class="row mt-3">
                            <div class="col-md-6">
                                <label class="form-label">تاریخ</label>
                                <input type="date" name="transaction_date" id="editTransactionDate" class="form-control"
                                    required>
                            </div>
                        </div>
                        <div class="mt-3">
                            <label class="form-label">توضیحات</label>
                            <textarea name="description" id="editDescription" class="form-control" rows="2"></textarea>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="closeEditModal()">انصراف</button>
                    <button type="button" class="btn btn-success" onclick="updateTransaction()">بروزرسانی</button>
                </div>
            </div>
        </div>
    </div>

    <?php include __DIR__ . '/includes/footer-modern.php'; ?>



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
        document.addEventListener('change', function (e) {
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
                    form.reset();
                    form.querySelector('select[name="type_id"]').innerHTML = '<option value="">ابتدا نوع هزینه را انتخاب کنید</option>';
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

                    document.getElementById('editTransactionId').value = transaction.id;
                    document.getElementById('editTransactionType').value = transaction.transaction_type;
                    document.getElementById('editAmount').value = transaction.amount;
                    document.getElementById('editPersonName').value = transaction.person_name;
                    document.getElementById('editTransactionDate').value = transaction.transaction_date;
                    document.getElementById('editDescription').value = transaction.description || '';

                    updateEditTypeOptions(transaction.transaction_type, transaction.type_id);
                    openEditModal();
                } else {
                    showAlert(result.message || 'خطا در دریافت اطلاعات', 'error');
                }
            } catch (error) {
                showAlert('خطا در ارتباط با سرور', 'error');
            }
        }

        function updateEditTypeOptions(selectedType, selectedValue = null) {
            const typeSelect = document.getElementById('editTypeId');
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

        function openEditModal() {
            const modal = document.getElementById('editTransactionModal');
            modal.style.display = 'block';
            modal.classList.add('show');
        }

        function closeEditModal() {
            const modal = document.getElementById('editTransactionModal');
            modal.style.display = 'none';
            modal.classList.remove('show');
        }

        async function updateTransaction() {
            const form = document.getElementById('editTransactionForm');
            const formData = new FormData(form);

            try {
                const response = await fetch('api/update_transaction.php', {
                    method: 'POST',
                    body: formData
                });

                const result = await response.json();

                if (result.success) {
                    showAlert('هزینه با موفقیت بروزرسانی شد', 'success');
                    closeEditModal();
                    setTimeout(() => location.reload(), 1000);
                } else {
                    showAlert(result.message || 'خطا در بروزرسانی', 'error');
                }
            } catch (error) {
                showAlert('خطا در ارتباط با سرور', 'error');
            }
        }

        // Event listener for edit modal transaction type change
        document.addEventListener('change', function (e) {
            if (e.target.id === 'editTransactionType') {
                updateEditTypeOptions(e.target.value);
            }
        });

        // حذف هزینه
        async function deleteTransaction(id) {
            if (confirm('آیا از حذف این هزینه اطمینان دارید؟')) {
                try {
                    const response = await fetch('api/delete_transaction.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ id: id })
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
            const alertHtml = `<div class="alert ${alertClass} alert-dismissible fade show" role="alert" style="position: fixed; top: 20px; right: 20px; z-index: 9999; max-width: 400px;">
                ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>`;
            document.body.insertAdjacentHTML('afterbegin', alertHtml);
        }


    </script>