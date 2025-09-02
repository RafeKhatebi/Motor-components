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

$page_title = 'مدیریت مشتریان';

// Pagination
$items_per_page = 30;
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$offset = ($page - 1) * $items_per_page;

// Count total customers
$count_query = "SELECT COUNT(*) as total FROM customers";
$count_stmt = $db->prepare($count_query);
$count_stmt->execute();
$total_items = $count_stmt->fetch(PDO::FETCH_ASSOC)['total'];
$total_pages = ceil($total_items / $items_per_page);

$query = "SELECT * FROM customers ORDER BY created_at DESC LIMIT :limit OFFSET :offset";
$stmt = $db->prepare($query);
$stmt->bindValue(':limit', $items_per_page, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$customers = $stmt->fetchAll(PDO::FETCH_ASSOC);

$extra_css = '
<style>
.table-summary {
    background: linear-gradient(135deg, #f8fafc, #e2e8f0);
    border-top: 2px solid #1f2937;
    font-weight: 600;
    color: #1f2937;
}

.table-summary th {
    padding: 16px 12px;
    font-size: 0.95rem;
    border-top: 2px solid #1f2937;
}

/* Edit Modal Styles */
#editCustomerModal {
    display: none;
}

#editCustomerModal.show {
    display: block;
}

.modal-professional .modal-content {
    border-radius: 12px;
    border: none;
    box-shadow: 0 10px 40px rgba(0,0,0,0.1);
}

.modal-professional .modal-header {
    background: linear-gradient(135deg, #1f2937, #374151);
    color: white;
    border-radius: 12px 12px 0 0;
}

/* Pagination Styles */
.pagination {
    gap: 4px;
}

.page-link {
    border-radius: 8px;
    border: 1px solid #e5e7eb;
    color: #374151;
    padding: 8px 12px;
    font-weight: 500;
    transition: all 0.2s ease;
}

.page-link:hover {
    background: #1f2937;
    border-color: #1f2937;
    color: white;
    transform: translateY(-1px);
}

.page-item.active .page-link {
    background: #1f2937;
    border-color: #1f2937;
    color: white;
    box-shadow: 0 4px 12px rgba(31, 41, 55, 0.3);
}

.page-item.disabled .page-link {
    color: #9ca3af;
    background: #f9fafb;
    border-color: #e5e7eb;
}

/* Mobile responsive improvements */
@media (max-width: 768px) {
    .table th:nth-child(3),
    .table td:nth-child(3) {
        display: none;
    }
    
    .btn-group {
        flex-direction: column;
        width: 100%;
    }
    
    .btn-group .btn {
        width: 100%;
        margin-bottom: 5px;
        border-radius: 6px !important;
    }
    
    .card-header .row {
        flex-direction: column;
        gap: 15px;
    }
    
    #searchInput {
        width: 100% !important;
        max-width: 300px;
    }
    
    .modal-dialog {
        max-width: 95%;
        margin: 10px;
    }
}
</style>
';

include 'includes/header.php';
?>

<!-- فرم مشتری جدید -->
<div class="section">
    <div class="card">
        <div class="card-header">
            <h5 class="card-title mb-0">
                <i class="fas fa-user-plus me-2"></i>
                افزودن مشتری جدید
            </h5>
        </div>
        <div class="card-body">
            <form id="addCustomerForm" onsubmit="event.preventDefault(); submitForm('addCustomerForm', 'api/add_customer.php');">
                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">
                <div class="d-flex gap-3 align-items-end">
                    <div class="form-group" style="flex: 2;">
                        <label class="form-label">نام مشتری</label>
                        <input type="text" name="name" class="form-control" required>
                    </div>
                    <div class="form-group" style="flex: 2;">
                        <label class="form-label">شماره تلفن</label>
                        <input type="text" name="phone" id="customerPhone" class="form-control" 
                               placeholder="07XXXXXXXX" maxlength="10" onblur="checkPhoneUnique('customers', this.value)" 
                               oninput="validatePhoneFormat(this)">
                        <div id="phoneValidation" class="mt-1"></div>
                    </div>
                    <div class="form-group" style="flex: 3;">
                        <label class="form-label">آدرس</label>
                        <input type="text" name="address" class="form-control">
                    </div>
                    <div class="form-group">
                        <button type="submit" class="btn btn-success">
                            <i class="fas fa-check me-1"></i>ثبت
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>
<div class="section">
    <div class="table-card">
        <div class="table-header">
            <div class="action-bar">
                <div class="action-group">
                    <h3>فهرست مشتریان</h3>
                </div>
                <div class="action-group">
                    <input type="text" class="form-control form-control-sm" placeholder="جستجو..."
                        id="searchInput" style="width: 200px;">

                </div>
            </div>
        </div>
        <div class="table-responsive">
            <table class="table table-modern" id="customersTable">
                <thead>
                            <tr>
                                <th scope="col">#</th>
                                <th scope="col">نام</th>
                                <th scope="col">تلفن</th>
                                <th scope="col">آدرس</th>
                                <th scope="col">تاریخ ثبت</th>
                                <th scope="col">عملیات</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($customers as $index => $customer): ?>
                                <tr>
                                    <td><?= $offset + $index + 1 ?></td>
                                    <td><?= sanitizeOutput($customer['name']) ?></td>
                                    <td><?= sanitizeOutput($customer['phone']) ?></td>
                                    <td><?= sanitizeOutput($customer['address']) ?></td>
                                    <td><?= SettingsHelper::formatDate(strtotime($customer['created_at']), $db) ?></td>
                                    <td class="text-left">
                                        <button onclick="editCustomer(<?= $customer['id'] ?>)"
                                            class="btn btn-professional btn-warning btn-sm">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button
                                            onclick="confirmDelete(<?= $customer['id'] ?>, 'api/delete_customer.php', '<?= sanitizeOutput($customer['name']) ?>')"
                                            class="btn btn-professional btn-danger btn-sm">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                        <tfoot>
                            <tr class="table-summary">
                                <th colspan="4" class="text-end">جمع کل مشتریان:</th>
                                <th id="totalCustomers">0</th>
                            </tr>
                        </tfoot>
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
                                    <?= $total_items ?> مشتری
                                </small>
                            </div>
                        </nav>
                    </div>
                <?php endif; ?>
            </div>
    </div>
</div>



<!-- Modal ویرایش مشتری -->
<div class="modal fade modal-professional" id="editCustomerModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">ویرایش مشتری</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="editCustomerForm"
                    onsubmit="event.preventDefault(); submitForm('editCustomerForm', 'api/edit_customer.php');">
                    <input type="hidden" id="editCustomerId" name="id">
                    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                    <div class="form-group">
                        <label class="form-control-label">نام مشتری</label>
                        <input type="text" id="editCustomerName" name="name" class="form-control form-control-professional" required>
                    </div>
                    <div class="form-group">
                        <label class="form-control-label">شماره تلفن</label>
                        <input type="text" id="editCustomerPhone" name="phone" class="form-control form-control-professional" 
                               placeholder="07XXXXXXXX" maxlength="10" oninput="validatePhoneFormat(this)">
                    </div>
                    <div class="form-group">
                        <label class="form-control-label">آدرس</label>
                        <textarea id="editCustomerAddress" name="address" class="form-control form-control-professional" rows="3"></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-professional btn-secondary" data-bs-dismiss="modal">انصراف</button>
                <button type="submit" form="editCustomerForm" class="btn btn-professional btn-warning">بروزرسانی</button>
            </div>
        </div>
    </div>
</div>

<script>
    let phoneValidationPassed = true;

    function validatePhoneFormat(input) {
        let value = input.value.replace(/\D/g, '');
        if (value.length > 10) value = value.substring(0, 10);
        input.value = value;
    }

    async function checkPhoneUnique(table, phone) {
        const validationDiv = document.getElementById('phoneValidation');

        if (!phone.trim()) {
            validationDiv.innerHTML = '';
            phoneValidationPassed = true;
            return;
        }

        // Check format first
        if (!/^07\d{8}$/.test(phone)) {
            validationDiv.innerHTML = '<small class="text-danger"><i class="fas fa-times"></i> شماره تلفن باید با 07 شروع شود و 10 رقم باشد</small>';
            phoneValidationPassed = false;
            return;
        }

        try {
            const formData = new FormData();
            formData.append('phone', phone);
            formData.append('table', table);

            const response = await fetch('api/check_phone.php', {
                method: 'POST',
                body: formData
            });

            const result = await response.json();

            if (result.success) {
                if (result.exists) {
                    validationDiv.innerHTML = '<small class="text-danger"><i class="fas fa-times"></i> شماره تلفن قبلاً ثبت شده است</small>';
                    phoneValidationPassed = false;
                } else {
                    validationDiv.innerHTML = '<small class="text-success"><i class="fas fa-check"></i> شماره تلفن قابل استفاده است</small>';
                    phoneValidationPassed = true;
                }
            }
        } catch (error) {
            validationDiv.innerHTML = '<small class="text-warning"><i class="fas fa-exclamation-triangle"></i> خطا در بررسی شماره تلفن</small>';
            phoneValidationPassed = false;
        }
    }

    async function submitForm(formId, apiUrl) {
        const form = document.getElementById(formId);
        const phone = form.querySelector('[name="phone"]').value;

        // Check phone validation before submit
        if (phone && !phoneValidationPassed) {
            showAlert('لطفاً شماره تلفن معتبر وارد کنید', 'error');
            return;
        }

        const formData = new FormData(form);

        try {
            const response = await fetch(apiUrl, {
                method: 'POST',
                body: formData
            });

            const result = await response.json();

            if (result.success) {
                showAlert('مشتری با موفقیت اضافه شد', 'success');
                form.reset();
                document.getElementById('phoneValidation').innerHTML = '';
                phoneValidationPassed = true;
                setTimeout(() => location.reload(), 1000);
            } else {
                showAlert(result.message || 'خطا در انجام عملیات', 'error');
            }
        } catch (error) {
            showAlert('خطا در ارتباط با سرور', 'error');
        }
    }

    async function editCustomer(id) {
        try {
            const response = await fetch(`api/get_customer.php?id=${id}`);
            const result = await response.json();
            
            if (result.success) {
                // پر کردن فیلدهای فرم ویرایش
                document.getElementById('editCustomerId').value = id;
                document.getElementById('editCustomerName').value = result.data.name;
                document.getElementById('editCustomerPhone').value = result.data.phone;
                document.getElementById('editCustomerAddress').value = result.data.address;
                
                // نمایش مودال ویرایش
                const modalElement = document.getElementById('editCustomerModal');
                const modal = new bootstrap.Modal(modalElement);
                modal.show();
            } else {
                showAlert('خطا در دریافت اطلاعات مشتری', 'error');
            }
        } catch (error) {
            showAlert('خطا در ارتباط با سرور', 'error');
        }
    }

    function confirmDelete(id, apiUrl, name) {
        if (confirm(`آیا از حذف "${name}" اطمینان دارید؟`)) {
            deleteItem(id, apiUrl);
        }
    }

    async function deleteItem(id, apiUrl) {
        try {
            const formData = new FormData();
            formData.append('id', id);

            const response = await fetch(apiUrl, {
                method: 'POST',
                body: formData
            });

            const result = await response.json();

            if (result.success) {
                showAlert('مشتری با موفقیت حذف شد', 'success');
                setTimeout(() => location.reload(), 1000);
            } else {
                showAlert(result.message || 'خطا در حذف مشتری', 'error');
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

    // Calculate summary
    function calculateSummary() {
        const totalCustomers = <?= $total_items ?>;
        document.getElementById('totalCustomers').textContent = totalCustomers.toLocaleString();
    }

    // Search functionality
    document.getElementById('searchInput').addEventListener('keyup', function () {
        const filter = this.value.toLowerCase();
        const rows = document.querySelectorAll('#customersTable tbody tr');

        rows.forEach(row => {
            const text = row.textContent.toLowerCase();
            row.style.display = text.includes(filter) ? '' : 'none';
        });
    });

    // Calculate on page load
    document.addEventListener('DOMContentLoaded', calculateSummary);
</script>

<?php include 'includes/footer-modern.php'; ?>