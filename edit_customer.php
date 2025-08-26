<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

require_once 'config/database.php';
$database = new Database();
$db = $database->getConnection();

$id = $_GET['id'] ?? 0;
if (!$id) {
    header('Location: customers.php');
    exit();
}

// Get customer data
$query = "SELECT * FROM customers WHERE id = ?";
$stmt = $db->prepare($query);
$stmt->execute([$id]);
$customer = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$customer) {
    header('Location: customers.php');
    exit();
}

// Handle form submission
if ($_POST) {
    $update_query = "UPDATE customers SET name = ?, phone = ?, address = ? WHERE id = ?";
    $update_stmt = $db->prepare($update_query);

    if ($update_stmt->execute([$_POST['name'], $_POST['phone'], $_POST['address'], $id])) {
        $success_message = "مشتری با موفقیت بروزرسانی شد";
        // Refresh customer data
        $stmt->execute([$id]);
        $customer = $stmt->fetch(PDO::FETCH_ASSOC);
    } else {
        $error_message = "خطا در بروزرسانی مشتری";
    }
}
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>ویرایش مشتری - مدیریت فروشگاه موتور</title>
    <link rel="stylesheet" href="assets/css/bootstrap.rtl.min.css">
    <link rel="stylesheet" href="assets/css/all.min.css">
    <link rel="stylesheet" href="assets/css/argon-dashboard-rtl.css">
</head>

<body>
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
                                <h3 class="mb-0">ویرایش مشتری</h3>
                            </div>
                            <div class="col text-left">
                                <a href="customers.php" class="btn btn-secondary btn-sm">
                                    <i class="fas fa-arrow-right"></i> بازگشت
                                </a>
                            </div>
                        </div>
                    </div>
                    <div class="card-body">
                        <form method="POST">
                            <div class="form-group mb-3">
                                <label class="form-control-label">نام مشتری</label>
                                <input type="text" name="name" class="form-control" value="<?= $customer['name'] ?>"
                                    required>
                            </div>
                            <div class="form-group mb-3">
                                <label class="form-control-label">شماره تلفن</label>
                                <input type="text" name="phone" class="form-control" value="<?= $customer['phone'] ?>">
                            </div>
                            <div class="form-group mb-3">
                                <label class="form-control-label">آدرس</label>
                                <textarea name="address" class="form-control"
                                    rows="3"><?= $customer['address'] ?></textarea>
                            </div>
                            <div class="text-center">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save"></i> بروزرسانی مشتری
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php include 'includes/footer.php'; ?>
</body>

</html>