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

// Pagination
$items_per_page = 30;
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$offset = ($page - 1) * $items_per_page;

// Count total users
$count_query = "SELECT COUNT(*) as total FROM users";
$count_stmt = $db->prepare($count_query);
$count_stmt->execute();
$total_items = $count_stmt->fetch(PDO::FETCH_ASSOC)['total'];
$total_pages = ceil($total_items / $items_per_page);

$query = "SELECT * FROM users ORDER BY created_at DESC LIMIT :limit OFFSET :offset";
$stmt = $db->prepare($query);
$stmt->bindValue(':limit', $items_per_page, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

include 'includes/header.php';
?>

<!-- Page content -->
<div class="container-fluid mt-4">
    <div class="row">
        <div class="col-12">
            <div class="card card-professional">
                <!-- Add User Form -->
                <div class="card-header border-0">
                    <h5 class="mb-3"><i class="fas fa-user-plus"></i> افزودن کاربر جدید</h5>
                    <form id="addUserForm" onsubmit="event.preventDefault(); submitForm('addUserForm', 'api/add_user.php');">
                        <?php if (!isset($_SESSION['csrf_token'])) $_SESSION['csrf_token'] = bin2hex(random_bytes(32)); ?>
                        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                        <div class="d-flex gap-2 align-items-end mb-3">
                            <div class="form-group" style="flex: 1;">
                                <label class="form-label">نام کاربری</label>
                                <input type="text" name="username" class="form-control" required>
                            </div>
                            <div class="form-group" style="flex: 2;">
                                <label class="form-label">نام کامل</label>
                                <input type="text" name="full_name" class="form-control" required>
                            </div>
                            <div class="form-group" style="flex: 1;">
                                <label class="form-label">رمز عبور</label>
                                <input type="password" name="password" class="form-control" required>
                            </div>
                            <div class="form-group" style="flex: 1;">
                                <label class="form-label">نقش</label>
                                <select name="role" class="form-control" required>
                                    <option value="employee">کارمند</option>
                                    <option value="manager">مدیر فروش</option>
                                    <option value="admin">مدیر</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <button type="submit" class="btn btn-success">
                                    <i class="fas fa-plus"></i> افزودن
                                </button>
                            </div>
                            <div class="form-group" style="flex: 1;">
                                <input type="text" class="form-control" placeholder="جستجو..." id="searchInput">
                            </div>
                        </div>
                    </form>
                </div>
                <div class="card-header border-0">
                    <h3 class="mb-0">لیست کاربران</h3>
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
                                    <td><?= $offset + $index + 1 ?></td>
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
                                    <?= $total_items ?> کاربر
                                </small>
                            </div>
                        </nav>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>



<!-- Modal ویرایش کاربر -->
<div class="modal fade modal-professional" id="editUserModal" tabindex="-1" style="display: none;">
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

<?php include 'includes/footer-modern.php'; ?>

<script>
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
                form.reset();
                location.reload();
            } else {
                showAlert(result.message || 'خطا در انجام عملیات', 'error');
            }
        } catch (error) {
            showAlert('خطا در ارتباط با سرور', 'error');
        }
    }

    function openModal(modalId) {
        const modalElement = document.getElementById(modalId);
        modalElement.style.display = 'block';
        const modal = new bootstrap.Modal(modalElement);
        modal.show();
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