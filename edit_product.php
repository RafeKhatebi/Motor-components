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

    <div class="container-fluid mt-4">
        <?php if (isset($success_message)): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fas fa-check-circle ml-2"></i>
                <?= $success_message ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if (isset($error_message)): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="fas fa-exclamation-circle ml-2"></i>
                <?= $error_message ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <div class="row">
            <div class="col-12">
                <div class="card shadow">
                    <div class="card-header border-0">
                        <div class="row align-items-center">
                            <div class="col">
                                <h3 class="mb-0">ویرایش محصول</h3>
                            </div>
                            <div class="col text-left">
                                <a href="products.php" class="btn btn-secondary btn-sm">
                                    <i class="fas fa-arrow-right"></i> بازگشت
                                </a>
                            </div>
                        </div>
                    </div>
                    <div class="card-body">
                        <form method="POST">
                            <input type="hidden" name="csrf_token" value="<?= sanitizeOutput($_SESSION['csrf_token']) ?>">
                            <div class="row">
                                <div class="col-lg-6">
                                    <div class="form-group mb-3">
                                        <label class="form-control-label">نام محصول</label>
                                        <input type="text" name="name" class="form-control"
                                            value="<?= sanitizeOutput($product['name']) ?>" required>
                                    </div>
                                </div>
                                <div class="col-lg-6">
                                    <div class="form-group mb-3">
                                        <label class="form-control-label">کد محصول</label>
                                        <input type="text" name="code" class="form-control"
                                            value="<?= sanitizeOutput($product['code']) ?>" required>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-lg-6">
                                    <div class="form-group mb-3">
                                        <label class="form-control-label">دسته بندی</label>
                                        <select name="category_id" class="form-control" required>
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
                                <div class="col-lg-6">
                                    <div class="form-group mb-3">
                                        <label class="form-control-label">موجودی</label>
                                        <input type="number" name="stock_quantity" class="form-control"
                                            value="<?= $product['stock_quantity'] ?>">
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-lg-6">
                                    <div class="form-group mb-3">
                                        <label class="form-control-label">قیمت خرید</label>
                                        <input type="number" name="buy_price" class="form-control"
                                            value="<?= $product['buy_price'] ?>" required>
                                    </div>
                                </div>
                                <div class="col-lg-6">
                                    <div class="form-group mb-3">
                                        <label class="form-control-label">قیمت فروش</label>
                                        <input type="number" name="sell_price" class="form-control"
                                            value="<?= $product['sell_price'] ?>" required>
                                    </div>
                                </div>
                            </div>
                            <div class="form-group mb-3">
                                <label class="form-control-label">حداقل موجودی</label>
                                <input type="number" name="min_stock" class="form-control"
                                    value="<?= $product['min_stock'] ?>">
                            </div>
                            <div class="form-group mb-3">
                                <label class="form-control-label">توضیحات</label>
                                <textarea name="description" class="form-control"
                                    rows="3"><?= sanitizeOutput($product['description']) ?></textarea>
                            </div>
                            <div class="text-center">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save"></i> بروزرسانی محصول
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

<?php include 'includes/footer.php'; ?>