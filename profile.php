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
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>پروفایل من - مدیریت فروشگاه موتور</title>
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
            <div class="col-xl-4 order-xl-2 mb-5 mb-xl-0">
                <div class="card card-profile shadow">
                    <div class="card-header text-center border-0 pt-8 pt-md-4 pb-0 pb-md-4">
                        <div class="d-flex justify-content-between">
                            <a href="#" class="btn btn-sm btn-info mr-4">اتصال</a>
                            <a href="#" class="btn btn-sm btn-default float-right">پیام</a>
                        </div>
                    </div>
                    <div class="card-body pt-0 pt-md-4">
                        <div class="row">
                            <div class="col">
                                <div class="card-profile-stats d-flex justify-content-center mt-md-5">
                                    <div>
                                        <span class="heading">22</span>
                                        <span class="description">دوستان</span>
                                    </div>
                                    <div>
                                        <span class="heading">10</span>
                                        <span class="description">عکس</span>
                                    </div>
                                    <div>
                                        <span class="heading">89</span>
                                        <span class="description">نظرات</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="text-center">
                            <h3><?= sanitizeOutput($user['full_name']) ?></h3>
                            <div class="h5 font-weight-300">
                                <i class="ni location_pin mr-2"></i>
                                <?php
                                $roles = ['admin' => 'مدیر سیستم', 'manager' => 'مدیر فروش', 'employee' => 'کارمند'];
                                echo sanitizeOutput($roles[$user['role']] ?? $user['role']);
                                ?>
                            </div>
                            <div class="h5 mt-4">
                                <i class="ni business_briefcase-24 mr-2"></i>
                                فروشگاه قطعات موتورسیکلت
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-xl-8 order-xl-1">
                <div class="card bg-secondary shadow">
                    <div class="card-header bg-white border-0">
                        <div class="row align-items-center">
                            <div class="col-8">
                                <h3 class="mb-0">حساب کاربری من</h3>
                            </div>
                        </div>
                    </div>
                    <div class="card-body">
                        <form method="POST">
                            <input type="hidden" name="action" value="update_profile">
                            <h6 class="heading-small text-muted mb-4">اطلاعات کاربر</h6>
                            <div class="pl-lg-4">
                                <div class="row">
                                    <div class="col-lg-6">
                                        <div class="form-group">
                                            <label class="form-control-label">نام کاربری</label>
                                            <input type="text" name="username"
                                                class="form-control form-control-alternative"
                                                value="<?= sanitizeOutput($user['username']) ?>" required>
                                        </div>
                                    </div>
                                    <div class="col-lg-6">
                                        <div class="form-group">
                                            <label class="form-control-label">نام کامل</label>
                                            <input type="text" name="full_name"
                                                class="form-control form-control-alternative"
                                                value="<?= sanitizeOutput($user['full_name']) ?>" required>
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-lg-6">
                                        <div class="form-group">
                                            <label class="form-control-label">نقش</label>
                                            <input type="text" class="form-control form-control-alternative"
                                                value="<?= sanitizeOutput($roles[$user['role']] ?? $user['role']) ?>"
                                                disabled>
                                        </div>
                                    </div>
                                    <div class="col-lg-6">
                                        <div class="form-group">
                                            <label class="form-control-label">تاریخ عضویت</label>
                                            <input type="text" class="form-control form-control-alternative"
                                                value="<?= sanitizeOutput(SettingsHelper::formatDate(strtotime($user['created_at']), $db)) ?>"
                                                disabled>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <hr class="my-4">
                            <div class="pl-lg-4">
                                <div class="row">
                                    <div class="col text-center">
                                        <button type="submit" class="btn btn-primary">بروزرسانی پروفایل</button>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

                <div class="card bg-secondary shadow mt-4">
                    <div class="card-header bg-white border-0">
                        <div class="row align-items-center">
                            <div class="col-8">
                                <h3 class="mb-0">تغییر رمز عبور</h3>
                            </div>
                        </div>
                    </div>
                    <div class="card-body">
                        <form method="POST">
                            <input type="hidden" name="action" value="change_password">
                            <div class="pl-lg-4">
                                <div class="row">
                                    <div class="col-lg-12">
                                        <div class="form-group">
                                            <label class="form-control-label">رمز عبور فعلی</label>
                                            <input type="password" name="current_password"
                                                class="form-control form-control-alternative" required>
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-lg-6">
                                        <div class="form-group">
                                            <label class="form-control-label">رمز عبور جدید</label>
                                            <input type="password" name="new_password"
                                                class="form-control form-control-alternative" required>
                                        </div>
                                    </div>
                                    <div class="col-lg-6">
                                        <div class="form-group">
                                            <label class="form-control-label">تکرار رمز عبور جدید</label>
                                            <input type="password" name="confirm_password"
                                                class="form-control form-control-alternative" required>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <hr class="my-4">
                            <div class="pl-lg-4">
                                <div class="row">
                                    <div class="col text-center">
                                        <button type="submit" class="btn btn-warning">تغییر رمز عبور</button>
                                    </div>
                                </div>
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