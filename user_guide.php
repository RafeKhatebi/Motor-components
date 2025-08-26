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
$page_title = 'راهنمای کاربری';
include 'includes/header.php';
?>

<div class="header bg-gradient-primary pb-8 pt-5 pt-md-8">
    <div class="container-fluid">
        <div class="header-body">
            <div class="row align-items-center py-4">
                <div class="col-lg-6 col-7">
                    <h6 class="h2 text-white d-inline-block mb-0">راهنمای کاربری</h6>
                    <nav aria-label="breadcrumb" class="d-none d-md-inline-block ml-md-4">
                        <ol class="breadcrumb breadcrumb-links breadcrumb-dark">
                            <li class="breadcrumb-item"><a href="dashboard.php">داشبورد</a></li>
                            <li class="breadcrumb-item active">راهنمای کاربری</li>
                        </ol>
                    </nav>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="container-fluid mt--7">
    <!-- فهرست مطالب -->
    <div class="row">
        <div class="col-12">
            <div class="card shadow">
                <div class="card-header">
                    <h3 class="mb-0">فهرست مطالب</h3>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <ul class="list-unstyled">
                                <li><a href="#getting-started">شروع کار</a></li>
                                <li><a href="#dashboard">داشبورد</a></li>
                                <li><a href="#products">مدیریت اجناس</a></li>
                                <li><a href="#categories">مدیریت دسته بندی ها</a></li>
                                <li><a href="#customers">مدیریت مشتریان</a></li>
                            </ul>
                        </div>
                        <div class="col-md-6">
                            <ul class="list-unstyled">
                                <li><a href="#suppliers">مدیریت تأمین کنندگان</a></li>
                                <li><a href="#sales">سیستم فروش</a></li>
                                <li><a href="#purchases">سیستم خرید</a></li>
                                <li><a href="#reports">گزارشات</a></li>
                                <li><a href="#backup">پشتیبان گیری</a></li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- شروع کار -->
    <div class="row mt-4" id="getting-started">
        <div class="col-12">
            <div class="card shadow">
                <div class="card-header">
                    <h3 class="mb-0">1. شروع کار</h3>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered">
                            <thead class="thead-light">
                                <tr>
                                    <th>مرحله</th>
                                    <th>توضیحات</th>
                                    <th>نکات مهم</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td><strong>ورود به سیستم</strong></td>
                                    <td>با نام کاربری و رمز عبور خود وارد شوید</td>
                                    <td>رمز عبور پیشفرض: password</td>
                                </tr>
                                <tr>
                                    <td><strong>تغییر رمز عبور</strong></td>
                                    <td>از منوی کاربری > پروفایل من</td>
                                    <td>برای امنیت حتماً رمز را تغییر دهید</td>
                                </tr>
                                <tr>
                                    <td><strong>آشنایی با داشبورد</strong></td>
                                    <td>مشاهده آمار کلی و وضعیت سیستم</td>
                                    <td>نقطه شروع همه فعالیت ها</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- داشبورد -->
    <div class="row mt-4" id="dashboard">
        <div class="col-12">
            <div class="card shadow">
                <div class="card-header">
                    <h3 class="mb-0">2. داشبورد</h3>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <h5>کارتهایگزارشات</h5>
                            <ul>
                                <li><strong>تعداد اجناس:</strong> نمایش کل اجناس موجود</li>
                                <li><strong>تعداد مشتریان:</strong> تعداد مشتریان ثبت شده</li>
                                <li><strong>فروش امروز:</strong> مجموع فروش روز جاری</li>
                                <li><strong>موجودی کم:</strong> اجناس با موجودی پایین</li>
                            </ul>
                        </div>
                        <div class="col-md-6">
                            <h5>نمودارها</h5>
                            <ul>
                                <li><strong>نمودار فروش:</strong> روند فروش 7 روز گذشته</li>
                                <li><strong>اجناس پرفروش:</strong> 5 محصول برتر</li>
                            </ul>
                            <h5>آخرین فعالیت ها</h5>
                            <ul>
                                <li>لیست آخرین فروشات</li>
                                <li>دسترسی سریع به چاپ فاکتور</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- مدیریت اجناس -->
    <div class="row mt-4" id="products">
        <div class="col-12">
            <div class="card shadow">
                <div class="card-header">
                    <h3 class="mb-0">3. مدیریت اجناس</h3>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered">
                            <thead class="thead-light">
                                <tr>
                                    <th>عملیات</th>
                                    <th>نحوه انجام</th>
                                    <th>فیلدهای مورد نیاز</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td><strong>افزودن محصول</strong></td>
                                    <td>کلیک روی "افزودن محصول" > پر کردن فرم</td>
                                    <td>نام، دسته بندی، قیمت خرید، قیمت فروش، موجودی</td>
                                </tr>
                                <tr>
                                    <td><strong>ویرایش محصول</strong></td>
                                    <td>کلیک روی آیکون ویرایش در جدول</td>
                                    <td>تغییر هر یک از فیلدهای موجود</td>
                                </tr>
                                <tr>
                                    <td><strong>حذف محصول</strong></td>
                                    <td>کلیک روی آیکون حذف > تأیید</td>
                                    <td>توجه: اجناس با فروش قابل حذف نیستند</td>
                                </tr>
                                <tr>
                                    <td><strong>جستجو و فیلتر</strong></td>
                                    <td>استفاده از کادر جستجو و فیلتر دسته بندی</td>
                                    <td>جستجو بر اساس نام محصول</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- سیستم فروش -->
    <div class="row mt-4" id="sales">
        <div class="col-12">
            <div class="card shadow">
                <div class="card-header">
                    <h3 class="mb-0">4. سیستم فروش</h3>
                </div>
                <div class="card-body">
                    <div class="alert alert-info">
                        <strong>مراحل ثبت فاکتور فروش:</strong>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-bordered">
                            <thead class="thead-light">
                                <tr>
                                    <th>مرحله</th>
                                    <th>شرح</th>
                                    <th>نکات</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>1</td>
                                    <td>انتخاب مشتری (اختیاری)</td>
                                    <td>برای فروش نقدی خالی بگذارید</td>
                                </tr>
                                <tr>
                                    <td>2</td>
                                    <td>افزودن اجناس</td>
                                    <td>انتخاب محصول، تعداد و قیمت</td>
                                </tr>
                                <tr>
                                    <td>3</td>
                                    <td>اعمال تخفیف (اختیاری)</td>
                                    <td>درصد یا مبلغ ثابت</td>
                                </tr>
                                <tr>
                                    <td>4</td>
                                    <td>ثبت فاکتور</td>
                                    <td>موجودی خودکار کم میشود</td>
                                </tr>
                                <tr>
                                    <td>5</td>
                                    <td>چاپ فاکتور</td>
                                    <td>امکان چاپ مجدد از لیست فروشات</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- گزارشات -->
    <div class="row mt-4" id="reports">
        <div class="col-12">
            <div class="card shadow">
                <div class="card-header">
                    <h3 class="mb-0">5. گزارشات</h3>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <h5>انواع گزارشات</h5>
                            <ul class="list-unstyled">
                                <li><i class="fas fa-chart-line text-primary me-2"></i>گزارش فروش روزانه</li>
                                <li><i class="fas fa-box text-success me-2"></i>گزارش موجودی گدام</li>
                                <li><i class="fas fa-star text-warning me-2"></i>اجناس پرفروش</li>
                                <li><i class="fas fa-users text-info me-2"></i>گزارش مشتریان</li>
                            </ul>
                        </div>
                        <div class="col-md-6">
                            <h5>نحوه استفاده</h5>
                            <ol>
                                <li>انتخاب نوع گزارش</li>
                                <li>تعیین بازه زمانی</li>
                                <li>اعمال فیلترها</li>
                                <li>مشاهده یا دانلود گزارش</li>
                            </ol>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- نکات امنیتی -->
    <div class="row mt-4">
        <div class="col-12">
            <div class="card shadow border-warning">
                <div class="card-header bg-warning">
                    <h3 class="mb-0 text-white">نکات امنیتی مهم</h3>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <ul class="list-unstyled">
                                <li><i class="fas fa-shield-alt text-success me-2"></i>رمز عبور قوی انتخاب کنید</li>
                                <li><i class="fas fa-clock text-info me-2"></i>به طور منظم پشتیبان تهیه کنید</li>
                                <li><i class="fas fa-user-lock text-warning me-2"></i>دسترسی کاربران را کنترل کنید</li>
                            </ul>
                        </div>
                        <div class="col-md-6">
                            <ul class="list-unstyled">
                                <li><i class="fas fa-sign-out-alt text-danger me-2"></i>پس از کار خروج کنید</li>
                                <li><i class="fas fa-database text-primary me-2"></i>اطلاعات مهم را محافظت کنید</li>
                                <li><i class="fas fa-update text-secondary me-2"></i>سیستم را آپدیت نگه دارید</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>

<style>
    .card-header h3::after {
        content: "";
        position: absolute;
        right: 0;
        bottom: -6px;
        width: 60px;
        height: 3px;
        background: linear-gradient(90deg, var(--mdz-primary), var(--mdz-accent));
        border-radius: 3px;
    }

    .list-unstyled li {
        margin-bottom: 8px;
        padding: 4px 0;
    }

    .table th {
        background-color: #f8f9fa;
        font-weight: 600;
    }

    .alert-info {
        border-left: 4px solid #17a2b8;
    }

    a[href^="#"] {
        color: var(--mdz-primary);
        text-decoration: none;
        font-weight: 500;
    }

    a[href^="#"]:hover {
        text-decoration: underline;
    }
</style>

<script>
    // اسکرول نرم برای لینکهای داخلی
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function (e) {
            e.preventDefault();
            const target = document.querySelector(this.getAttribute('href'));
            if (target) {
                target.scrollIntoView({
                    behavior: 'smooth',
                    block: 'start'
                });
            }
        });
    });
</script>