# سیستم استایل حرفه‌ای موتور

## نمای کلی

سیستم استایل جدید موتور یک راه‌حل مدرن، قابل نگهداری و کاملاً سازگار با RTL/LTR برای پروژه مدیریت فروشگاه قطعات موتورسیکلت است.

## ویژگی‌های کلیدی

### 🎨 سیستم طراحی مدرن
- **Design Tokens**: متغیرهای CSS سازمان‌یافته برای رنگ‌ها، فاصله‌ها، تایپوگرافی و سایه‌ها
- **پالت رنگ حرفه‌ای**: رنگ‌های اصلی، ثانویه و معنایی با درجات مختلف
- **تم روشن/تاریک**: پشتیبانی کامل از تم‌های مختلف با `[data-theme="dark"]`

### 🌍 پشتیبانی کامل RTL/LTR
- **Logical Properties**: استفاده از `margin-inline-*`, `padding-inline-*`, `inset-inline-*`
- **Direction Agnostic**: بدون direction ثابت در body
- **Text Alignment**: استفاده از `text-align: start/end`

### 📱 طراحی ریسپانسیو
- **Mobile First**: بهینه‌سازی برای موبایل
- **Breakpoints**: نقاط شکست استاندارد Bootstrap 5
- **Touch Targets**: حداقل اندازه 44px برای عناصر لمسی

### ♿ دسترسی‌پذیری
- **WCAG 2.1 AA**: کنتراست رنگ‌ها
- **Focus Management**: حلقه‌های focus واضح
- **Screen Reader**: پشتیبانی از خوانندگان صفحه
- **Reduced Motion**: احترام به تنظیمات کاربر

## ساختار فایل‌ها

```
assets/css/
├── style.css              # فایل اصلی سیستم استایل
├── motor-system.css       # نسخه کامل سیستم
├── demo.html             # نمونه استفاده
└── README.md             # این فایل
```

## نحوه استفاده

### 1. اضافه کردن فونت Vazir

```html
<!-- در head -->
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Vazir:wght@300;400;500;600;700&display=swap" rel="stylesheet">
```

### 2. اضافه کردن CSS

```html
<link rel="stylesheet" href="assets/css/style.css?v=2.0.0">
```

### 3. تنظیم HTML

```html
<html lang="fa" dir="rtl">
<!-- یا برای انگلیسی -->
<html lang="en" dir="ltr">
```

## Design Tokens

### رنگ‌ها

```css
/* رنگ‌های اصلی */
--color-primary: #5e72e4;
--color-secondary: #64748b;
--color-success: #10b981;
--color-danger: #ef4444;
--color-warning: #f59e0b;
--color-info: #06b6d4;

/* رنگ‌های سطح */
--surface-primary: #ffffff;
--surface-secondary: #f9fafb;
--surface-tertiary: #f3f4f6;

/* رنگ‌های متن */
--text-primary: #111827;
--text-secondary: #4b5563;
--text-tertiary: #6b7280;
```

### تایپوگرافی

```css
/* مقیاس فونت (نسبت 1.125) */
--font-size-xs: 0.75rem;    /* 12px */
--font-size-sm: 0.875rem;   /* 14px */
--font-size-base: 1rem;     /* 16px */
--font-size-lg: 1.125rem;   /* 18px */
--font-size-xl: 1.25rem;    /* 20px */

/* وزن‌های فونت */
--font-weight-light: 300;
--font-weight-normal: 400;
--font-weight-medium: 500;
--font-weight-semibold: 600;
--font-weight-bold: 700;
```

### فاصله‌ها

```css
/* مقیاس فاصله (پایه 4px) */
--space-1: 0.25rem;   /* 4px */
--space-2: 0.5rem;    /* 8px */
--space-3: 0.75rem;   /* 12px */
--space-4: 1rem;      /* 16px */
--space-5: 1.25rem;   /* 20px */
--space-6: 1.5rem;    /* 24px */
```

### شعاع مرز

```css
--radius-sm: 0.25rem;   /* 4px */
--radius-base: 0.5rem;  /* 8px */
--radius-lg: 0.75rem;   /* 12px */
--radius-xl: 1rem;      /* 16px */
--radius-full: 9999px;
```

### سایه‌ها

```css
--shadow-sm: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
--shadow-base: 0 1px 3px 0 rgba(0, 0, 0, 0.1), 0 1px 2px 0 rgba(0, 0, 0, 0.06);
--shadow-md: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
--shadow-lg: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
```

## اجزای اصلی

### Navbar

```html
<nav class="navbar">
    <a class="navbar-brand" href="#">
        <i class="fas fa-motorcycle"></i>
        سیستم مدیریت فروشگاه
    </a>
    <ul class="navbar-nav">
        <li class="nav-item">
            <a class="nav-link active" href="#">
                <i class="fas fa-tachometer-alt"></i>
                داشبورد
            </a>
        </li>
    </ul>
</nav>
```

### Cards

```html
<div class="card">
    <div class="card-header">
        <h5 class="mb-0">عنوان کارت</h5>
    </div>
    <div class="card-body">
        محتوای کارت
    </div>
</div>
```

### Buttons

```html
<button class="btn btn-primary">دکمه اصلی</button>
<button class="btn btn-secondary">دکمه ثانویه</button>
<button class="btn btn-success">دکمه موفقیت</button>
<button class="btn btn-danger">دکمه خطر</button>
```

### Forms

```html
<div class="form-group">
    <label for="input" class="form-label">برچسب</label>
    <input type="text" class="form-control" id="input" placeholder="متن نمونه">
    <div class="form-text">متن راهنما</div>
</div>
```

### Tables

```html
<div class="table-responsive">
    <table class="table">
        <thead>
            <tr>
                <th>ستون ۱</th>
                <th class="text-number">قیمت</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>داده</td>
                <td class="text-number">۱۲۳,۴۵۶</td>
            </tr>
        </tbody>
    </table>
</div>
```

## تم روشن/تاریک

### فعال‌سازی تم تاریک

```javascript
// تغییر به تم تاریک
document.documentElement.setAttribute('data-theme', 'dark');

// تغییر به تم روشن
document.documentElement.setAttribute('data-theme', 'light');
```

### ذخیره تنظیمات

```javascript
// ذخیره در localStorage
localStorage.setItem('theme', 'dark');

// بارگذاری تنظیمات
const savedTheme = localStorage.getItem('theme') || 'light';
document.documentElement.setAttribute('data-theme', savedTheme);
```

## کلاس‌های Utility

### متن

```html
<p class="text-primary">متن اصلی</p>
<p class="text-secondary">متن ثانویه</p>
<p class="text-muted">متن خاکستری</p>
<p class="text-center">متن وسط‌چین</p>
<p class="text-start">متن شروع</p>
<p class="text-end">متن پایان</p>
```

### فاصله‌ها

```html
<div class="mb-3">فاصله پایین</div>
<div class="mt-4">فاصله بالا</div>
<div class="gap-2">فاصله بین عناصر</div>
```

### نمایش

```html
<div class="d-flex">فلکس</div>
<div class="d-none">مخفی</div>
<div class="d-block">بلوک</div>
```

### تراز

```html
<div class="d-flex align-items-center">تراز وسط</div>
<div class="d-flex justify-content-between">توزیع فضا</div>
```

## بهینه‌سازی عملکرد

### Critical CSS

برای بهبود عملکرد، CSS بحرانی را در head قرار دهید:

```html
<style>
/* Critical CSS for above-the-fold content */
:root { /* design tokens */ }
body { /* base styles */ }
.navbar { /* navbar styles */ }
</style>
```

### Cache Busting

```html
<link rel="stylesheet" href="assets/css/style.css?v=2.0.0">
```

### Preload

```html
<link rel="preload" href="assets/css/style.css" as="style">
```

## سازگاری با Bootstrap

این سیستم کاملاً سازگار با Bootstrap 5 است و کلاس‌های موجود را override نمی‌کند، بلکه آن‌ها را تکمیل می‌کند.

## مثال‌های کاربردی

### صفحه داشبورد

```html
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>داشبورد</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Vazir:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css?v=2.0.0">
</head>
<body>
    <!-- محتوا -->
</body>
</html>
```

### کارت آمار

```html
<div class="card card-stats">
    <div class="card-body">
        <div class="icon bg-primary text-white rounded-full">
            <i class="fas fa-boxes"></i>
        </div>
        <div>
            <h3 class="h2 font-bold mb-0 text-number">۱,۲۳۴</h3>
            <p class="text-muted mb-0">کل محصولات</p>
        </div>
    </div>
</div>
```

## رفع مشکلات

### مشکلات رایج

1. **فونت نمایش داده نمی‌شود**: بررسی کنید که لینک فونت Vazir اضافه شده باشد
2. **RTL کار نمی‌کند**: `dir="rtl"` را به تگ html اضافه کنید
3. **تم تاریک فعال نمی‌شود**: `data-theme="dark"` را به html اضافه کنید

### دیباگ

```javascript
// بررسی تم فعلی
console.log(document.documentElement.getAttribute('data-theme'));

// بررسی direction
console.log(document.documentElement.dir);

// بررسی CSS variables
console.log(getComputedStyle(document.documentElement).getPropertyValue('--color-primary'));
```

## مشارکت

برای بهبود سیستم استایل:

1. کد را تست کنید
2. از design tokens استفاده کنید
3. سازگاری RTL/LTR را حفظ کنید
4. دسترسی‌پذیری را در نظر بگیرید

## نسخه‌ها

- **v2.0.0**: سیستم استایل جدید با design tokens
- **v1.0.0**: نسخه اولیه

## پشتیبانی

برای سوالات و مشکلات، با تیم توسعه تماس بگیرید.

---

**توسعه‌دهنده**: تیم توسعه سیستم مدیریت فروشگاه  
**آخرین بروزرسانی**: 2024  
**مجوز**: MIT