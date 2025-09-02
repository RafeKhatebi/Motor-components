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

$page_title = 'ویرایش محصول';

$id = $_GET['id'] ?? 0;
if (!$id) {
    header('Location: products.php');
    exit();
}

// Get product data
$query = "SELECT * FROM products WHERE id = ?";
$stmt = $db->prepare($query);
$stmt->execute([$id]);
$product = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$product) {
    header('Location: products.php');
    exit();
}

// Get categories
$categories_query = "SELECT * FROM categories ORDER BY name";
$categories_stmt = $db->prepare($categories_query);
$categories_stmt->execute();
$categories = $categories_stmt->fetchAll(PDO::FETCH_ASSOC);

// Handle form submission
if ($_POST) {
    // CSRF protection
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf_token'])) {
        $error_message = 'درخواست نامعتبر';
    } else {
        $update_query = "UPDATE products SET name = ?, code = ?, category_id = ?, buy_price = ?, sell_price = ?, stock_quantity = ?, min_stock = ?, description = ? WHERE id = ?";
        $update_stmt = $db->prepare($update_query);

    if (
        $update_stmt->execute([
            $_POST['name'],
            $_POST['code'],
            $_POST['category_id'],
            $_POST['buy_price'],
            $_POST['sell_price'],
            $_POST['stock_quantity'],
            $_POST['min_stock'],
            $_POST['description'],
            $id
        ])
    ) {
        $success_message = "محصول با موفقیت بروزرسانی شد";
        // Refresh product data
        $stmt->execute([$id]);
        $product = $stmt->fetch(PDO::FETCH_ASSOC);
        } else {
            $error_message = "خطا در بروزرسانی محصول";
        }
    }
}

// Generate CSRF token
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
?>
<?php include 'includes/header.php'; ?>

<?php if (isset($success_message)): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="fas fa-check-circle me-2"></i>
        <?= $success_message ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<?php if (isset($error_message)): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="fas fa-exclamation-circle me-2"></i>
        <?= $error_message ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<div class="section">
    <div class="card">
        <div class="card-header">
            <div class="d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0">
                    <i class="fas fa-edit me-2"></i>
                    ویرایش محصول
                </h5>
                <a href="products.php" class="btn btn-secondary btn-sm">
                    <i class="fas fa-arrow-right me-1"></i>بازگشت
                </a>
            </div>
        </div>
        <div class="card-body">
            <form method="POST">
                <input type="hidden" name="csrf_token" value="<?= sanitizeOutput($_SESSION['csrf_token']) ?>">
                <div class="row">
                    <div class="col-md-4">
                        <div class="form-group mb-2">
                            <label class="form-label">نام محصول</label>
                            <input type="text" name="name" class="form-control form-control-sm"
                                value="<?= sanitizeOutput($product['name']) ?>" required>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group mb-2">
                            <label class="form-label">کد محصول</label>
                            <input type="text" name="code" class="form-control form-control-sm"
                                value="<?= sanitizeOutput($product['code']) ?>" required>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group mb-2">
                            <label class="form-label">دسته بندی</label>
                            <select name="category_id" class="form-select form-select-sm" required>
                                <option value="">انتخاب کنید</option>
                                <?php foreach ($categories as $category): ?>
                                    <option value="<?= $category['id'] ?>"
                                        <?= $product['category_id'] == $category['id'] ? 'selected' : '' ?>>
                                        <?= sanitizeOutput($category['name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-3">
                        <div class="form-group mb-2">
                            <label class="form-label">قیمت خرید</label>
                            <input type="number" name="buy_price" class="form-control form-control-sm"
                                value="<?= $product['buy_price'] ?>" step="0.01" required>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group mb-2">
                            <label class="form-label">قیمت فروش</label>
                            <input type="number" name="sell_price" class="form-control form-control-sm"
                                value="<?= $product['sell_price'] ?>" step="0.01" required>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group mb-2">
                            <label class="form-label">موجودی</label>
                            <input type="number" name="stock_quantity" class="form-control form-control-sm"
                                value="<?= $product['stock_quantity'] ?>">
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group mb-2">
                            <label class="form-label">حداقل موجودی</label>
                            <input type="number" name="min_stock" class="form-control form-control-sm"
                                value="<?= $product['min_stock'] ?>">
                        </div>
                    </div>
                </div>
                <div class="form-group mb-2">
                    <label class="form-label">توضیحات</label>
                    <textarea name="description" class="form-control form-control-sm"
                        rows="2"><?= sanitizeOutput($product['description']) ?></textarea>
                </div>
                <div class="d-flex justify-content-center gap-2 mt-3">
                    <button type="submit" class="btn btn-success btn-sm">
                        <i class="fas fa-save me-1"></i>بروزرسانی
                    </button>
                    <a href="products.php" class="btn btn-secondary btn-sm">
                        <i class="fas fa-times me-1"></i>انصراف
                    </a>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include 'includes/footer-modern.php'; ?>