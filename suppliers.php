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

$page_title = 'مدیریت تأمین‌ کنندگان';

$query = "SELECT * FROM suppliers ORDER BY created_at DESC";
$stmt = $db->prepare($query);
$stmt->execute();
$suppliers = $stmt->fetchAll(PDO::FETCH_ASSOC);

include 'includes/header.php';
?>

<!-- Header -->
<div class="header bg-gradient-primary pb-8 pt-5 pt-md-8">
    <div class="container-fluid">
        <div class="header-body">
            <div class="row align-items-center py-4">
                <div class="col-lg-6 col-7">
                    <h6 class="h2 text-white d-inline-block mb-0"><?= __('supplier_management') ?></h6>
                </div>
                <div class="col-lg-6 col-5 text-left">
                    <a href="#" class="btn btn-professional btn-sm" data-bs-toggle="modal"
                        data-bs-target="#addSupplierModal">
                        <i class="fas fa-plus"></i> <?= __('new_supplier') ?>
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
                                    <td><?= $index + 1 ?></td>
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
            </div>
        </div>
    </div>
</div>

<!-- Modal افزودن تأمین‌کننده -->
<div class="modal fade modal-professional" id="addSupplierModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><?= __('add') ?> <?= __('new_supplier') ?></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="addSupplierForm"
                    onsubmit="event.preventDefault(); submitForm('addSupplierForm', 'api/add_supplier.php');">
                    <?php if (!isset($_SESSION['csrf_token']))
                        $_SESSION['csrf_token'] = bin2hex(random_bytes(32)); ?>
                    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                    <div class="form-group">
                        <label class="form-control-label"><?= __('name') ?> <?= __('supplier') ?></label>
                        <input type="text" name="name" class="form-control form-control-professional" required>
                    </div>
                    <div class="form-group">
                        <label class="form-control-label"><?= __('supplier_phone') ?></label>
                        <input type="text" name="phone" id="supplierPhone"
                            class="form-control form-control-professional" placeholder="07XXXXXXXX" maxlength="10"
                            onblur="checkPhoneUnique('suppliers', this.value)" oninput="validatePhoneFormat(this)">
                        <div id="phoneValidation" class="mt-1"></div>
                        <small class="form-text text-muted">شماره تلفن باید با 07 شروع شود و 10 رقم باشد</small>
                    </div>
                    <div class="form-group">
                        <label class="form-control-label"><?= __('supplier_address') ?></label>
                        <textarea name="address" class="form-control form-control-professional" rows="3"></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-professional btn-secondary"
                    data-bs-dismiss="modal"><?= __('cancel') ?></button>
                <button type="submit" form="addSupplierForm"
                    class="btn btn-professional btn-success"><?= __('save') ?></button>
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
                showAlert('عملیات با موفقیت انجام شد', 'success');
                bootstrap.Modal.getInstance(form.closest('.modal')).hide();
                location.reload();
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
                document.getElementById('editSupplierId').value = supplier.id;
                document.getElementById('editSupplierName').value = supplier.name;
                document.getElementById('editSupplierPhone').value = supplier.phone;
                document.getElementById('editSupplierAddress').value = supplier.address;

                originalPhone = supplier.phone;
                editPhoneValidationPassed = true;
                document.getElementById('editPhoneValidation').innerHTML = '';

                const modal = new bootstrap.Modal(document.getElementById('editSupplierModal'));
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
                showAlert('تأمینکننده با موفقیت به‌روزرسانی شد', 'success');
                bootstrap.Modal.getInstance(document.getElementById('editSupplierModal')).hide();
                location.reload();
            } else {
                showAlert(result.message || 'خطا در به‌روزرسانی تأمینکننده', 'error');
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
                showAlert('تأمین‌کننده با موفقیت حذف شد', 'success');
                setTimeout(() => location.reload(), 1000);
            } else {
                showAlert(result.message || 'خطا در حذف تأمین‌کننده', 'error');
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

<?php include 'includes/footer.php'; ?>