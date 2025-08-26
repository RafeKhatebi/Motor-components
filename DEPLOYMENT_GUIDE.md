# راهنمای نصب و راهاندازی سیستم مدیریت فروشگاه قطعات موتورسیکلت

## 📋 پیشنیازهای سیستم

### نرمافزارهای مورد نیاز:
- **PHP**: 7.4+ (توصیه: 8.1)
- **MySQL**: 5.7+ یا MariaDB 10.4+
- **Apache**: 2.4+
- **سیستمعامل**: Windows 10+, Linux, macOS

### ماژولهای PHP ضروری:
```
php-pdo
php-pdo-mysql
php-gd
php-mbstring
php-json
php-curl
php-zip
php-xml
```

### تنظیمات سیستمعامل:
- حداقل RAM: 2GB
- فضای دیسک: 1GB
- دسترسی نوشتن به فولدرهای: `uploads/`, `logs/`, `backups/`, `cache/`

## 🚀 مراحل نصب

### مرحله 1: نصب XAMPP
1. دانلود XAMPP از [apachefriends.org](https://www.apachefriends.org)
2. نصب در مسیر `C:\xampp\`
3. اجرای XAMPP Control Panel
4. Start کردن Apache و MySQL

### مرحله 2: کپی فایلهای سیستم
```bash
# کپی فایلها به htdocs
cp -r motor/ C:\xampp\htdocs\motor\

# تنظیم مجوزها (Linux/macOS)
chmod -R 755 /opt/lampp/htdocs/motor/
chmod -R 777 /opt/lampp/htdocs/motor/uploads/
chmod -R 777 /opt/lampp/htdocs/motor/logs/
chmod -R 777 /opt/lampp/htdocs/motor/backups/
```

### مرحله 3: ایجاد دیتابیس
```sql
-- اجرا در phpMyAdmin یا MySQL CLI
CREATE DATABASE motor_shop CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE motor_shop;
SOURCE C:\xampp\htdocs\motor\database.sql;
```

### مرحله 4: تنظیم پیکربندی
1. ویرایش `config/database.php`:
```php
private $host = 'localhost';
private $db_name = 'motor_shop';
private $username = 'root';
private $password = ''; // رمز MySQL
```

2. اجرای اسکریپت راهاندازی:
```
http://localhost/motor/fix_database_safe.php
```

### مرحله 5: تست اولیه
```
http://localhost/motor/system_test.php
```

## ⚡ بهینهسازی عملکرد

### مرحله 1: بهینهسازی خودکار
```
http://localhost/motor/performance_optimizer.php
```

### مرحله 2: تنظیم PHP (php.ini)
```ini
; OPcache
opcache.enable=1
opcache.memory_consumption=256
opcache.max_accelerated_files=10000

; Memory
memory_limit=512M
max_execution_time=60

; Upload
upload_max_filesize=10M
post_max_size=10M
```

### مرحله 3: تنظیم Apache (httpd.conf)
```apache
# Compression
LoadModule deflate_module modules/mod_deflate.so

# Headers
LoadModule headers_module modules/mod_headers.so

# Expires
LoadModule expires_module modules/mod_expires.so

# Rewrite
LoadModule rewrite_module modules/mod_rewrite.so
```

### مرحله 4: تنظیم MySQL (my.ini)
```ini
[mysqld]
innodb_buffer_pool_size = 256M
query_cache_type = 1
query_cache_size = 32M
max_connections = 100
```

## 🧪 تست سیستم

### تست اتصال دیتابیس:
```
http://localhost/motor/check_db.php
```

### تست API:
```bash
# تست ورود
curl -X POST http://localhost/motor/login.php \
  -d "username=admin&password=admin123"

# تست افزودن محصول
curl -X POST http://localhost/motor/api/add_product.php \
  -d "name=تست&code=TEST001&buy_price=100&sell_price=150"
```

### تست سرعت:
```
http://localhost/motor/performance_test.php
```

### بررسی لاگها:
- `logs/error_*.log`
- `api/logs/error_*.log`
- Apache error.log
- MySQL error.log

## 📦 چکلیست تحویل نهایی

### ✅ دیتابیس
- [ ] دیتابیس motor_shop ایجاد شده
- [ ] تمام جداول موجود
- [ ] کاربر admin ایجاد شده
- [ ] ایندکسها بهینه شدهاند
- [ ] دادههای نمونه (اختیاری)

### ✅ امنیت
- [ ] رمزهای عبور hash شدهاند
- [ ] CSRF protection فعال
- [ ] Session security تنظیم شده
- [ ] فایلهای حساس محافظت شدهاند
- [ ] SQL Injection prevention

### ✅ API و عملکرد
- [ ] تمام APIها کار میکنند
- [ ] زمان پاسخ < 200ms
- [ ] OPcache فعال
- [ ] Gzip compression فعال
- [ ] Static file caching

### ✅ سرعت و بهینهسازی
- [ ] CSS/JS minified
- [ ] تصاویر بهینه شدهاند
- [ ] Database queries optimized
- [ ] Memory usage < 64MB
- [ ] Page load time < 2s

### ✅ بکاپ و نگهداری
- [ ] سیستم بکاپ خودکار
- [ ] فولدر backups/ قابل نوشتن
- [ ] Log rotation تنظیم شده
- [ ] راهنمای کاربر موجود

## 🔧 عیبیابی رایج

### مشکل اتصال دیتابیس:
```php
// بررسی در config/database.php
try {
    $pdo = new PDO("mysql:host=localhost;dbname=motor_shop", "root", "");
    echo "اتصال موفق";
} catch(PDOException $e) {
    echo "خطا: " . $e->getMessage();
}
```

### مشکل مجوزهای فایل:
```bash
# Windows
icacls "C:\xampp\htdocs\motor\uploads" /grant Everyone:F

# Linux
chmod -R 777 /var/www/html/motor/uploads/
```

### مشکل PHP Extensions:
```bash
# بررسی ماژولهای نصب شده
php -m | grep -E "(pdo|gd|mbstring)"
```

## 📞 پشتیبانی

### اطلاعات ورود پیشفرض:
- **نام کاربری**: admin
- **رمز عبور**: admin123

### فایلهای مهم:
- `config/database.php` - تنظیمات دیتابیس
- `includes/auth.php` - احراز هویت
- `.htaccess` - تنظیمات Apache
- `logs/` - فایلهای لاگ

### لینکهای مفید:
- داشبورد: `http://localhost/motor/dashboard.php`
- تست سیستم: `http://localhost/motor/system_test.php`
- تست عملکرد: `http://localhost/motor/performance_test.php`

---
**نسخه**: 1.0.0  
**تاریخ**: 2024  
**پشتیبانی**: تیم توسعه سیستم مدیریت فروشگاه