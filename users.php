<?php
require_once 'init_security.php';
require_once 'includes/permissions.php';
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// Check page access permission
PermissionManager::requirePermission('users.view');

require_once 'config/database.php';
require_once 'includes/functions.php';
require_once 'includes/SettingsHelper.php';
$database = new Database();
$db = $database->getConnection();
SettingsHelper::loadSettings($db);
$page_title = 'مدیریت کاربران';

$query = "SELECT * FROM users ORDER BY created_at DESC";
$stmt = $db->prepare($query);
$stmt->execute();
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

include 'includes/header.php';
?>

<!-- Header -->
<div class="header bg-gradient-primary pb-8 pt-5 pt-md-8">
    <div class="container-fluid">
        <div class="header-body">
            <div class="row align-items-center py-4">
                <div class="col-lg-6 col-7">
                    <h6 class="h2 text-white d-inline-block mb-0"><?= __('user_management') ?></h6>
                </div>
                <div class="col-lg-6 col-5 text-left">
                    <button onclick="openModal('addUserModal')" class="btn btn-professional btn-sm">
                        <i class="fas fa-plus"></i> <?= __('add') ?> <?= __('new_user') ?>
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Page content -->
<div class="container-fluid mt--7">
    <div class="row">
        <div class="col-12">
            <div class="card card-professional">
                <div class="card-header border-0">
                    <div class="row align-items-center">
                        <div class="col">
                            <h3 class="mb-0">مدیریت کاربران</h3>
                        </div>
                        <div class="col text-left">
                            <input type="text" class="form-control form-control-sm" placeholder="جستجو..."
                                id="searchInput" style="width: 200px; display: inline-block;">
                        </div>
                    </div>
                </div>
                <div class="table-responsive">
                    <table class="table align-items-center table-flush" id="usersTable">
                        <thead class="thead-light">
                            <tr>
                                <th scope="col">#</th>
                                <th scope="col">نام کاربری</th>
                                <th scope="col">نام کامل</th>
                                <th scope="col">نقش</th>
                                <th scope="col">تاریخ ثبت</th>
                                <th scope="col">عملیات</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($users as $index => $user): ?>
                                <tr>
                                    <td><?= $index + 1 ?></td>
                                    <td>
                                        <div class="media align-items-center">
                                            <div class="avatar rounded-circle ml-3">
                                                <i class="fas fa-user"></i>
                                            </div>
                                            <div class="media-body">
                                                <span class="mb-0 text-sm"><?= sanitizeOutput($user['username']) ?></span>
                                            </div>
                                        </div>
                                    </td>
                                    <td><?= sanitizeOutput($user['full_name']) ?></td>
                                    <td>
                                        <?php
                                        $roles = ['admin' => 'مدیر', 'manager' => 'مدیر فروش', 'employee' => 'کارمند'];
                                        $roleClass = ['admin' => 'badge-success', 'manager' => 'badge-warning', 'employee' => 'badge-info'];
                                        ?>
                                        <span
                                            class="badge <?= sanitizeOutput($roleClass[$user['role']] ?? 'badge-secondary') ?>">
                                            <?= sanitizeOutput($roles[$user['role']] ?? $user['role']) ?>
                                        </span>
                                    </td>
                                    <td><?= sanitizeOutput(SettingsHelper::formatDate(strtotime($user['created_at']), $db)) ?></td>
                                    <td>
                                        <?php if ($user['id'] != $_SESSION['user_id']): ?>
                                            <button onclick="editUser(<?= sanitizeOutput($user['id']) ?>)"
                                                class="btn btn-professional btn-warning btn-sm">
                                                <i class="fas fa-edit"></i> ویرایش
                                            </button>
                                            <button
                                                onclick="confirmDelete(<?= sanitizeOutput($user['id']) ?>, 'api/delete_user.php', '<?= sanitizeOutput($user['username']) ?>')"
                                                class="btn btn-professional btn-danger btn-sm">
                                                <i class="fas fa-trash"></i> حذف
                                            </button>
                                        <?php endif; ?>
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

<!-- Modal افزودن کاربر -->
<div class="modal fade modal-professional" id="addUserModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">افزودن کاربر جدید</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="addUserForm"
                    onsubmit="event.preventDefault(); submitForm('addUserForm', 'api/add_user.php');">
                    <?php if (!isset($_SESSION['csrf_token'])) $_SESSION['csrf_token'] = bin2hex(random_bytes(32)); ?>
                    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                    <div class="form-group mb-3">
                        <label class="form-control-label">نام کاربری</label>
                        <input type="text" name="username" class="form-control form-control-professional" required>
                    </div>
                    <div class="form-group mb-3">
                        <label class="form-control-label">نام کامل</label>
                        <input type="text" name="full_name" class="form-control form-control-professional" required>
                    </div>
                    <div class="form-group mb-3">
                        <label class="form-control-label">رمز عبور</label>
                        <input type="password" name="password" class="form-control form-control-professional" required>
                    </div>
                    <div class="form-group mb-3">
                        <label class="form-control-label">نقش</label>
                        <select name="role" class="form-control form-control-professional" required>
                            <option value="employee">کارمند</option>
                            <option value="manager">مدیر فروش</option>
                            <option value="admin">مدیر</option>
                        </select>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-professional btn-secondary" data-bs-dismiss="modal">انصراف</button>
                <button type="button" onclick="submitForm('addUserForm', 'api/add_user.php')"
                    class="btn btn-professional btn-success">ذخیره</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal ویرایش کاربر -->
<div class="modal fade modal-professional" id="editUserModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">ویرایش کاربر</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="editUserForm"
                    onsubmit="event.preventDefault(); submitForm('editUserForm', 'api/edit_user.php');">
                    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                    <input type="hidden" name="id" id="editUserId">
                    <div class="form-group mb-3">
                        <label class="form-control-label">نام کاربری</label>
                        <input type="text" name="username" id="editUsername"
                            class="form-control form-control-professional" required>
                    </div>
                    <div class="form-group mb-3">
                        <label class="form-control-label">نام کامل</label>
                        <input type="text" name="full_name" id="editFullName"
                            class="form-control form-control-professional" required>
                    </div>
                    <div class="form-group mb-3">
                        <label class="form-control-label">رمز عبور جدید (اختیاری)</label>
                        <input type="password" name="password" class="form-control form-control-professional">
                    </div>
                    <div class="form-group mb-3">
                        <label class="form-control-label">نقش</label>
                        <select name="role" id="editRole" class="form-control form-control-professional" required>
                            <option value="employee">کارمند</option>
                            <option value="manager">مدیر فروش</option>
                            <option value="admin">مدیر</option>
                        </select>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-professional btn-secondary" data-bs-dismiss="modal">انصراف</button>
                <button type="button" onclick="submitForm('editUserForm', 'api/edit_user.php')"
                    class="btn btn-professional btn-success">بروزرسانی</button>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>

<script>
    function openModal(modalId) {
        const modal = new bootstrap.Modal(document.getElementById(modalId));
        modal.show();
    }

    async function submitForm(formId, apiUrl) {
        const form = document.getElementById(formId);
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

    function editUser(id) {
        // Find user data from the table
        const userRow = document.querySelector(`button[onclick="editUser(${id})"]`).closest('tr');
        const cells = userRow.querySelectorAll('td');

        document.getElementById('editUserId').value = id;
        document.getElementById('editUsername').value = cells[1].querySelector('.media-body span').textContent;
        document.getElementById('editFullName').value = cells[2].textContent;

        // Set role based on badge text
        const roleText = cells[3].querySelector('.badge').textContent.trim();
        const roleMap = { 'مدیر': 'admin', 'مدیر فروش': 'manager', 'کارمند': 'employee' };
        document.getElementById('editRole').value = roleMap[roleText] || 'employee';

        openModal('editUserModal');
    }

    function confirmDelete(id, apiUrl, name) {
        if (confirm(`آیا از حذف کاربر "${name}" اطمینان دارید؟`)) {
            deleteItem(id, apiUrl);
        }
    }

    async function deleteItem(id, apiUrl) {
        try {
            const formData = new FormData();
            formData.append('id', id);
            formData.append('csrf_token', '<?= $_SESSION['csrf_token'] ?? '' ?>');
            
            const response = await fetch(apiUrl, {
                method: 'POST',
                body: formData
            });

            const result = await response.json();

            if (result.success) {
                showAlert('کاربر با موفقیت حذف شد', 'success');
                location.reload();
            } else {
                showAlert(result.message || 'خطا در حذف کاربر', 'error');
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
        const rows = document.querySelectorAll('#usersTable tbody tr');

        rows.forEach(row => {
            const text = row.textContent.toLowerCase();
            row.style.display = text.includes(filter) ? '' : 'none';
        });
    });
</script>