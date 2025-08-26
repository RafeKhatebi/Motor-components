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

$page_title = 'مدیریت دسته‌ بندی‌ ها';

$query = "SELECT * FROM categories ORDER BY name";
$stmt = $db->prepare($query);
$stmt->execute();
$categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

// CSRF token for forms and deletion
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrf_token = $_SESSION['csrf_token'];

include 'includes/header.php';
?>

<!-- Header -->
<div class="header bg-gradient-primary pb-8 pt-5 pt-md-8">
    <div class="container-fluid">
        <div class="header-body">
            <div class="row align-items-center py-4">
                <div class="col-lg-6 col-7">
                    <h6 class="h2 text-white d-inline-block mb-0"><?= __('category_management') ?></h6>
                </div>
                <div class="col-lg-6 col-5 text-left">
                    <a href="#" class="btn btn-professional btn-sm" data-bs-toggle="modal"
                        data-bs-target="#addCategoryModal">
                        <i class="fas fa-plus"></i> <?= __('new_category') ?>
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
                            <h3 class="mb-0"><?= __('list_title') ?> <?= __('categories') ?></h3>
                        </div>
                    </div>
                </div>
                <div class="table-responsive">
                    <table class="table align-items-center table-flush">
                        <thead class="thead-light">
                            <tr>
                                <th scope="col"><?= __('category_name') ?></th>
                                <th scope="col"><?= __('description') ?></th>
                                <th scope="col"><?= __('creation_date') ?></th>
                                <th scope="col"><?= __('actions') ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($categories as $category): ?>
                                <tr>
                                    <td><?= sanitizeOutput($category['name']) ?></td>
                                    <td><?= sanitizeOutput($category['description']) ?></td>
                                    <td><?= SettingsHelper::formatDate(strtotime($category['created_at']), $db) ?></td>
                                    <td class="text-left">
                                        <button onclick="editCategory(<?= $category['id'] ?>)"
                                            class="btn btn-professional btn-warning btn-sm">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button
                                            onclick="confirmDelete(<?= $category['id'] ?>, 'api/delete_category.php', <?= json_encode($category['name']) ?>)"
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

<!-- Modal افزودن دسته‌بندی -->
<div class="modal fade modal-professional" id="addCategoryModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">افزودن دسته‌ بندی جدید</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="addCategoryForm"
                    onsubmit="event.preventDefault(); submitForm('addCategoryForm', 'api/add_category.php');">
                    <div class="form-group">
                        <label class="form-control-label">نام دسته‌ بندی</label>
                        <input type="text" name="name" class="form-control form-control-professional" required>
                    </div>
                    <div class="form-group">
                        <label class="form-control-label">توضیحات</label>
                        <textarea name="description" class="form-control form-control-professional" rows="3"></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-professional btn-secondary" data-bs-dismiss="modal">انصراف</button>
                <button type="submit" form="addCategoryForm" class="btn btn-professional btn-success">ذخیره</button>
            </div>
        </div>
    </div>
</div>

<script>
    const CSRF = '<?= $csrf_token ?>';
    async function submitForm(formId, apiUrl) {
        const form = document.getElementById(formId);
        const formData = new FormData(form);
        formData.append('csrf_token', CSRF);

        try {
            const response = await fetch(apiUrl, {
                method: 'POST',
                body: formData,
                credentials: 'same-origin'
            });

            const result = await response.json();

            if (result.success) {
                showAlert('عملیات با موفقیت انجام شد', 'success');
                const modalEl = form.closest('.modal');
                if (modalEl) {
                    const modalInstance = bootstrap.Modal.getInstance(modalEl) || new bootstrap.Modal(modalEl);
                    modalInstance.hide();
                }
                location.reload();
            } else {
                showAlert(result.message || 'خطا در انجام عملیات', 'error');
            }
        } catch (error) {
            showAlert('خطا در ارتباط با سرور', 'error');
        }
    }

    function editCategory(id) {
        window.location.href = `edit_category.php?id=${id}`;
    }

    async function confirmDelete(id, apiUrl, name) {
        if (confirm(`آیا از حذف "${name}" اطمینان دارید؟`)) {
            try {
                const formData = new FormData();
                formData.append('id', id);
                formData.append('csrf_token', CSRF);

                const response = await fetch(apiUrl, {
                    method: 'POST',
                    body: formData,
                    credentials: 'same-origin'
                });

                const result = await response.json();

                if (result.success) {
                    showAlert('دسته‌ بندی با موفقیت حذف شد', 'success');
                    setTimeout(() => location.reload(), 1000);
                } else {
                    showAlert(result.message || 'خطا در حذف دسته‌بندی', 'error');
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

<?php
$footer_path = 'includes/footer.php';
if (!preg_match('/^[a-zA-Z0-9_\-\/]+\.php$/', $footer_path) || strpos($footer_path, '..') !== false) {
    die('Invalid file path');
}
include $footer_path;
?>