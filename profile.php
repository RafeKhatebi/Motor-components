<?php
require_once 'init_security.php';
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

require_once 'config/database.php';
require_once 'includes/functions.php';
require_once 'includes/SettingsHelper.php';
$page_title = 'پروفایل کاربری';
$database = new Database();
$db = $database->getConnection();
SettingsHelper::loadSettings($db);

$user_query = "SELECT * FROM users WHERE id = ?";
$user_stmt = $db->prepare($user_query);
$user_stmt->execute([$_SESSION['user_id']]);
$user = $user_stmt->fetch(PDO::FETCH_ASSOC);

if ($_POST && isset($_POST['action'])) {
    if ($_POST['action'] === 'update_profile') {
        $update_query = "UPDATE users SET full_name = ?, username = ? WHERE id = ?";
        $update_stmt = $db->prepare($update_query);

        if ($update_stmt->execute([$_POST['full_name'], $_POST['username'], $_SESSION['user_id']])) {
            $_SESSION['full_name'] = $_POST['full_name'];
            $success_message = "پروفایل با موفقیت بروزرسانی شد";
            $user['full_name'] = $_POST['full_name'];
            $user['username'] = $_POST['username'];
        } else {
            $error_message = "خطا در بروزرسانی پروفایل";
        }
    }

    if ($_POST['action'] === 'change_password') {
        if (password_verify($_POST['current_password'], $user['password'])) {
            if ($_POST['new_password'] === $_POST['confirm_password']) {
                $hashed_password = password_hash($_POST['new_password'], PASSWORD_DEFAULT);
                $password_query = "UPDATE users SET password = ? WHERE id = ?";
                $password_stmt = $db->prepare($password_query);

                if ($password_stmt->execute([$hashed_password, $_SESSION['user_id']])) {
                    $success_message = "رمز عبور با موفقیت تغییر کرد";
                } else {
                    $error_message = "خطا در تغییر رمز عبور";
                }
            } else {
                $error_message = "رمز عبور جدید و تکرار آن یکسان نیست";
            }
        } else {
            $error_message = "رمز عبور فعلی اشتباه است";
        }
    }
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
        <!-- کارت پروفایل -->
        <div class="col-md-4 mb-4">
            <div class="card">
                <div class="card-body text-center">
                    <div class="mb-3">
                        <div class="bg-primary text-white rounded-circle d-inline-flex align-items-center justify-content-center" style="width: 80px; height: 80px;">
                            <i class="fas fa-user fa-2x"></i>
                        </div>
                    </div>
                    <h4 class="mb-1"><?= sanitizeOutput($user['full_name']) ?></h4>
                    <p class="text-muted mb-2">
                        <?php
                        $roles = ['admin' => 'مدیر سیستم', 'manager' => 'مدیر فروش', 'employee' => 'کارمند'];
                        echo sanitizeOutput($roles[$user['role']] ?? $user['role']);
                        ?>
                    </p>
                    <small class="text-muted">عضویت: <?= sanitizeOutput(SettingsHelper::formatDate(strtotime($user['created_at']), $db)) ?></small>
                </div>
            </div>
        </div>

        <!-- فرم بروزرسانی پروفایل -->
        <div class="col-md-8">
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-user-edit me-2"></i>
                        بروزرسانی پروفایل
                    </h5>
                </div>
                <div class="card-body">
                    <form method="POST">
                        <input type="hidden" name="action" value="update_profile">
                        <div class="d-flex gap-3 mb-3">
                            <div style="flex: 1;">
                                <label class="form-label">نام کاربری</label>
                                <input type="text" name="username" class="form-control" value="<?= sanitizeOutput($user['username']) ?>" required>
                            </div>
                            <div style="flex: 1;">
                                <label class="form-label">نام کامل</label>
                                <input type="text" name="full_name" class="form-control" value="<?= sanitizeOutput($user['full_name']) ?>" required>
                            </div>
                        </div>
                        <div class="d-flex gap-3 mb-3">
                            <div style="flex: 1;">
                                <label class="form-label">نقش</label>
                                <input type="text" class="form-control" value="<?= sanitizeOutput($roles[$user['role']] ?? $user['role']) ?>" disabled>
                            </div>
                            <div style="flex: 1;">
                                <label class="form-label">تاریخ عضویت</label>
                                <input type="text" class="form-control" value="<?= sanitizeOutput(SettingsHelper::formatDate(strtotime($user['created_at']), $db)) ?>" disabled>
                            </div>
                        </div>
                        <div class="text-center">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save me-2"></i>
                                بروزرسانی پروفایل
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- فرم تغییر رمز عبور -->
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-key me-2"></i>
                        تغییر رمز عبور
                    </h5>
                </div>
                <div class="card-body">
                    <form method="POST">
                        <input type="hidden" name="action" value="change_password">
                        <div class="mb-3">
                            <label class="form-label">رمز عبور فعلی</label>
                            <input type="password" name="current_password" class="form-control" required>
                        </div>
                        <div class="d-flex gap-3 mb-3">
                            <div style="flex: 1;">
                                <label class="form-label">رمز عبور جدید</label>
                                <input type="password" name="new_password" class="form-control" required>
                            </div>
                            <div style="flex: 1;">
                                <label class="form-label">تکرار رمز عبور</label>
                                <input type="password" name="confirm_password" class="form-control" required>
                            </div>
                        </div>
                        <div class="text-center">
                            <button type="submit" class="btn btn-warning">
                                <i class="fas fa-lock me-2"></i>
                                تغییر رمز عبور
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer-modern.php'; ?>