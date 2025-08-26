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

$page_title = 'ویرایش دسته بندی';

$id = $_GET['id'] ?? 0;
if (!$id) {
    header('Location: categories.php');
    exit();
}

// Get category data
$query = "SELECT * FROM categories WHERE id = ?";
$stmt = $db->prepare($query);
$stmt->execute([$id]);
$category = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$category) {
    header('Location: categories.php');
    exit();
}

// Handle form submission
if ($_POST) {
    // CSRF protection
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf_token'])) {
        $error_message = 'درخواست نامعتبر';
    } else {
        $update_query = "UPDATE categories SET name = ?, description = ? WHERE id = ?";
        $update_stmt = $db->prepare($update_query);

        if ($update_stmt->execute([$_POST['name'], $_POST['description'], $id])) {
            $success_message = "دسته بندی با موفقیت بروزرسانی شد";
            // Refresh category data
            $stmt->execute([$id]);
            $category = $stmt->fetch(PDO::FETCH_ASSOC);
        } else {
            $error_message = "خطا در بروزرسانی دسته بندی";
        }
    }
}

// Generate CSRF token
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

include 'includes/header.php';
?>

<div class="container-fluid mt-4">
    <?php if (isset($success_message)): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fas fa-check-circle ml-2"></i>
            <?= sanitizeOutput($success_message) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <?php if (isset($error_message)): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="fas fa-exclamation-circle ml-2"></i>
            <?= sanitizeOutput($error_message) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <div class="row">
        <div class="col-12">
            <div class="card shadow">
                <div class="card-header border-0">
                    <div class="row align-items-center">
                        <div class="col">
                            <h3 class="mb-0">ویرایش دسته بندی</h3>
                        </div>
                        <div class="col text-left">
                            <a href="categories.php" class="btn btn-secondary btn-sm">
                                <i class="fas fa-arrow-right"></i> بازگشت
                            </a>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <form method="POST">
                        <input type="hidden" name="csrf_token" value="<?= sanitizeOutput($_SESSION['csrf_token']) ?>">
                        <div class="form-group mb-3">
                            <label class="form-control-label">نام دسته بندی</label>
                            <input type="text" name="name" class="form-control" value="<?= sanitizeOutput($category['name']) ?>" required>
                        </div>
                        <div class="form-group mb-3">
                            <label class="form-control-label">توضیحات</label>
                            <textarea name="description" class="form-control" rows="3"><?= sanitizeOutput($category['description']) ?></textarea>
                        </div>
                        <div class="text-center">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> بروزرسانی دسته بندی
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>