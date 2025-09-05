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

$page_title = 'مدیریت دسته بندی ها';

// Pagination
$items_per_page = 30;
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$offset = ($page - 1) * $items_per_page;

// Count total categories
$count_query = "SELECT COUNT(*) as total FROM categories";
$count_stmt = $db->prepare($count_query);
$count_stmt->execute();
$total_items = $count_stmt->fetch(PDO::FETCH_ASSOC)['total'];
$total_pages = ceil($total_items / $items_per_page);

$query = "SELECT * FROM categories ORDER BY name LIMIT :limit OFFSET :offset";
$stmt = $db->prepare($query);
$stmt->bindValue(':limit', $items_per_page, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

// CSRF token for forms and deletion
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrf_token = $_SESSION['csrf_token'];

$extra_css = '';

include 'includes/header.php';
?>

<!-- فرم دسته بندی جدید -->
<div class="section">
    <div class="card">
        <div class="card-header">
            <h5 class="card-title mb-0">
                <i class="fas fa-plus me-2"></i>
                افزودن دسته بندی جدید
            </h5>
        </div>
        <div class="card-body">
            <form id="addCategoryForm"
                onsubmit="event.preventDefault(); submitForm('addCategoryForm', 'api/add_category.php');">
                <div class="d-flex gap-3 align-items-end">
                    <div class="form-group" style="flex: 2;">
                        <label class="form-label">نام دسته بندی</label>
                        <input type="text" name="name" class="form-control" required>
                    </div>
                    <div class="form-group" style="flex: 3;">
                        <label class="form-label">توضیحات</label>
                        <input type="text" name="description" class="form-control">
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
                    <h3><?= __('list_title') ?> <?= __('categories') ?></h3>
                </div>
                <div class="action-group">
                    <input type="text" class="form-control form-control-sm" placeholder="جستجو..." id="searchInput"
                        style="width: 200px;">
                </div>
            </div>
        </div>
        <div class="table-responsive">
            <table class="table" id="categoriesTable">
                <thead>
                    <tr>
                        <th>#</th>
                        <th><?= __('category_name') ?></th>
                        <th><?= __('description') ?></th>
                        <th><?= __('creation_date') ?></th>
                        <th><?= __('actions') ?></th>
                    </tr>
                </thead>
                        <tbody>
                            <?php foreach ($categories as $index => $category): ?>
                                <tr>
                                    <td><?= $index + 1 ?></td>
                                    <td><?= sanitizeOutput($category['name']) ?></td>
                                    <td><?= sanitizeOutput($category['description']) ?></td>
                                    <td><?= SettingsHelper::formatDate(strtotime($category['created_at']), $db) ?></td>
                                    <td class="text-left">
                                        <div class="btn-group" role="group">
                                            <button onclick="editCategory(<?= $category['id'] ?>)"
                                                class="btn btn-warning btn-sm" title="ویرایش">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <button
                                                onclick="confirmDelete(<?= $category['id'] ?>, 'api/delete_category.php', <?= htmlspecialchars(json_encode($category['name']), ENT_QUOTES, 'UTF-8') ?>)"
                                                class="btn btn-danger btn-sm" title="حذف">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
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
                showAlert('دسته بندی با موفقیت اضافه شد', 'success');
                form.reset();
                setTimeout(() => location.reload(), 1000);
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

                const responseText = await response.text();
                console.log('Response:', responseText);

                let result;
                try {
                    result = JSON.parse(responseText);
                } catch (e) {
                    console.error('JSON Parse Error:', e);
                    showAlert('خطا در پردازش پاسخ سرور', 'error');
                    return;
                }

                if (result.success) {
                    showAlert('دسته بندی با موفقیت حذف شد', 'success');
                    setTimeout(() => location.reload(), 1000);
                } else {
                    showAlert(result.message || 'خطا در حذف دسته بندی', 'error');
                }
            } catch (error) {
                console.error('Fetch Error:', error);
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

    // Search functionality
    document.getElementById('searchInput').addEventListener('keyup', function () {
        const filter = this.value.toLowerCase();
        const rows = document.querySelectorAll('#categoriesTable tbody tr');

        rows.forEach(row => {
            const text = row.textContent.toLowerCase();
            row.style.display = text.includes(filter) ? '' : 'none';
        });
    });
</script>

<?php include 'includes/footer-modern.php'; ?>