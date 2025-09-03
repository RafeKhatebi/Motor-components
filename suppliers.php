<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

require_once 'config/database.php';
require_once 'includes/SettingsHelper.php';
$database = new Database();
$db = $database->getConnection();
SettingsHelper::loadSettings($db);

$page_title = 'مدیریت تأمین کنندگان';

$extra_css = '
<style>
#editSupplierModal {
    display: none;
}

#editSupplierModal.show {
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
</style>
';

// Pagination
$items_per_page = 30;
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$offset = ($page - 1) * $items_per_page;

// Count total suppliers
$count_query = "SELECT COUNT(*) as total FROM suppliers";
$count_stmt = $db->prepare($count_query);
$count_stmt->execute();
$total_items = $count_stmt->fetch(PDO::FETCH_ASSOC)['total'];
$total_pages = ceil($total_items / $items_per_page);

$query = "SELECT * FROM suppliers ORDER BY created_at DESC LIMIT :limit OFFSET :offset";
$stmt = $db->prepare($query);
$stmt->bindValue(':limit', $items_per_page, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$suppliers = $stmt->fetchAll(PDO::FETCH_ASSOC);

include 'includes/header.php';
?>

<!-- فرم تأمینکننده جدید -->
<div class="section">
    <div class="card">
        <div class="card-header">
            <h5 class="card-title mb-0">
                <i class="fas fa-truck me-2"></i>
                افزودن تأمینکننده جدید
            </h5>
        </div>
        <div class="card-body">
            <form id="addSupplierForm" onsubmit="event.preventDefault(); submitForm('addSupplierForm', 'api/add_supplier.php');">
                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">
                <div class="d-flex gap-3 align-items-end">
                    <div class="form-group" style="flex: 2;">
                        <label class="form-label">نام تأمینکننده</label>
                        <input type="text" name="name" class="form-control" required>
                    </div>
                    <div class="form-group" style="flex: 2;">
                        <label class="form-label">شماره تلفن</label>
                        <input type="text" name="phone" id="supplierPhone" class="form-control" 
                               placeholder="07XXXXXXXX" maxlength="10" onblur="checkPhoneUnique('suppliers', this.value)" 
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

<div class="container-fluid">
    <div class="row">
        <div class="col">
            <div class="card card-professional">
                <div class="card-header border-0">
                    <div class="row align-items-center">
                        <div class="col">
                            <h3 class="mb-0"><?= __('list_title') ?> <?= __('suppliers') ?></h3>
                        </div>
                        <div class="col text-left">
                            <input type="text" class="form-control form-control-sm" placeholder="جستجو..."
                                id="searchInput" style="width: 200px; display: inline-block;">
                        </div>
                    </div>
                </div>
                <div class="table-responsive">
                    <table class="table align-items-center table-flush" id="suppliersTable">
                        <thead class="thead-light">
                            <tr>
                                <th scope="col">#</th>
                                <th scope="col"><?= __('name') ?></th>
                                <th scope="col"><?= __('phone') ?></th>
                                <th scope="col"><?= __('address') ?></th>
                                <th scope="col"><?= __('registration_date') ?></th>
                                <th scope="col"><?= __('actions') ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($suppliers as $index => $supplier): ?>
                                <tr>
                                    <td><?= $offset + $index + 1 ?></td>
                                    <td><?= sanitizeOutput($supplier['name']) ?></td>
                                    <td><?= sanitizeOutput($supplier['phone']) ?></td>
                                    <td><?= sanitizeOutput($supplier['address']) ?></td>
                                    <td><?= sanitizeOutput(SettingsHelper::formatDate(strtotime($supplier['created_at']), $db)) ?>
                                    </td>
                                    <td class="text-left">
                                        <button onclick="editSupplier(<?= $supplier['id'] ?>)"
                                            class="btn btn-professional btn-warning btn-sm">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button
                                            onclick="confirmDelete(<?= $supplier['id'] ?>, 'api/delete_supplier.php', '<?= sanitizeOutput($supplier['name']) ?>')"
                                            class="btn btn-professional btn-danger btn-sm">
                                            <i class="fas fa-trash"></i>
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
                                    <?= $total_items ?> تأمینکننده
                                </small>
                            </div>
                        </nav>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Modal ویرایش تأمینکننده -->
<div class="modal fade modal-professional" id="editSupplierModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">ویرایش تأمینکننده</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="editSupplierForm" onsubmit="event.preventDefault(); submitEditForm();">
                    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                    <input type="hidden" id="editSupplierId" name="id">
                    <div class="form-group">
                        <label class="form-control-label">نام تأمینکننده</label>
                        <input type="text" id="editSupplierName" name="name"
                            class="form-control form-control-professional" required>
                    </div>
                    <div class="form-group">
                        <label class="form-control-label">شماره تلفن</label>
                        <input type="text" id="editSupplierPhone" name="phone"
                            class="form-control form-control-professional" placeholder="07XXXXXXXX" maxlength="10"
                            onblur="checkEditPhoneUnique(this.value)" oninput="validatePhoneFormat(this)">
                        <div id="editPhoneValidation" class="mt-1"></div>
                        <small class="form-text text-muted">شماره تلفن باید با 07 شروع شود و 10 رقم باشد</small>
                    </div>
                    <div class="form-group">
                        <label class="form-control-label">آدرس</label>
                        <textarea id="editSupplierAddress" name="address" class="form-control form-control-professional"
                            rows="3"></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-professional btn-secondary" data-bs-dismiss="modal">انصراف</button>
                <button type="submit" form="editSupplierForm" class="btn btn-professional btn-success">ذخیره
                    تغییرات</button>
            </div>
        </div>
    </div>
</div>

<script>
    let phoneValidationPassed = true;
    let editPhoneValidationPassed = true;
    let originalPhone = '';

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
                showAlert('تأمینکننده با موفقیت اضافه شد', 'success');
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

    async function editSupplier(id) {
        try {
            const response = await fetch(`api/get_supplier.php?id=${id}`);
            const result = await response.json();

            if (result.success) {
                const supplier = result.data;
                // پر کردن فیلدهای فرم ویرایش
                document.getElementById('editSupplierId').value = supplier.id;
                document.getElementById('editSupplierName').value = supplier.name;
                document.getElementById('editSupplierPhone').value = supplier.phone;
                document.getElementById('editSupplierAddress').value = supplier.address;

                originalPhone = supplier.phone;
                editPhoneValidationPassed = true;
                document.getElementById('editPhoneValidation').innerHTML = '';

                // نمایش مودال ویرایش
                const modalElement = document.getElementById('editSupplierModal');
                const modal = new bootstrap.Modal(modalElement);
                modal.show();
            } else {
                showAlert('خطا در دریافت اطلاعات تأمینکننده', 'error');
            }
        } catch (error) {
            showAlert('خطا در ارتباط با سرور', 'error');
        }
    }

    async function checkEditPhoneUnique(phone) {
        const validationDiv = document.getElementById('editPhoneValidation');

        if (!phone.trim()) {
            validationDiv.innerHTML = '';
            editPhoneValidationPassed = true;
            return;
        }

        // اگر شماره تغییر نکرده
        if (phone === originalPhone) {
            validationDiv.innerHTML = '';
            editPhoneValidationPassed = true;
            return;
        }

        // بررسی فرمت
        if (!/^07\d{8}$/.test(phone)) {
            validationDiv.innerHTML = '<small class="text-danger"><i class="fas fa-times"></i> شماره تلفن باید با 07 شروع شود و 10 رقم باشد</small>';
            editPhoneValidationPassed = false;
            return;
        }

        try {
            const formData = new FormData();
            formData.append('phone', phone);
            formData.append('table', 'suppliers');

            const response = await fetch('api/check_phone.php', {
                method: 'POST',
                body: formData
            });

            const result = await response.json();

            if (result.success) {
                if (result.exists) {
                    validationDiv.innerHTML = '<small class="text-danger"><i class="fas fa-times"></i> شماره تلفن قبلاً ثبت شده است</small>';
                    editPhoneValidationPassed = false;
                } else {
                    validationDiv.innerHTML = '<small class="text-success"><i class="fas fa-check"></i> شماره تلفن قابل استفاده است</small>';
                    editPhoneValidationPassed = true;
                }
            }
        } catch (error) {
            validationDiv.innerHTML = '<small class="text-warning"><i class="fas fa-exclamation-triangle"></i> خطا در بررسی شماره تلفن</small>';
            editPhoneValidationPassed = false;
        }
    }

    async function submitEditForm() {
        const form = document.getElementById('editSupplierForm');
        const phone = form.querySelector('[name="phone"]').value;

        if (phone && !editPhoneValidationPassed) {
            showAlert('لطفاً شماره تلفن معتبر وارد کنید', 'error');
            return;
        }

        const formData = new FormData(form);

        try {
            const response = await fetch('api/edit_supplier.php', {
                method: 'POST',
                body: formData
            });

            const result = await response.json();

            if (result.success) {
                showAlert('تأمینکننده با موفقیت بهروزرسانی شد', 'success');
                bootstrap.Modal.getInstance(document.getElementById('editSupplierModal')).hide();
                location.reload();
            } else {
                showAlert(result.message || 'خطا در بهروزرسانی تأمینکننده', 'error');
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
        console.log('Deleting item with id:', id, 'url:', apiUrl);
        try {
            const formData = new FormData();
            formData.append('id', id);

            const response = await fetch(apiUrl, {
                method: 'POST',
                body: formData
            });

            const result = await response.json();

            if (result.success) {
                showAlert('تأمینکننده با موفقیت حذف شد', 'success');
                setTimeout(() => location.reload(), 1000);
            } else {
                showAlert(result.message || 'خطا در حذف تأمینکننده', 'error');
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

    // Search functionality
    document.getElementById('searchInput').addEventListener('keyup', function () {
        const filter = this.value.toLowerCase();
        const rows = document.querySelectorAll('#suppliersTable tbody tr');

        rows.forEach(row => {
            const text = row.textContent.toLowerCase();
            row.style.display = text.includes(filter) ? '' : 'none';
        });
    });
</script>

<?php include 'includes/footer-modern.php'; ?>