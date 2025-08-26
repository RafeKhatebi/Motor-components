<?php
/**
 * راهاندازی سریع سیستم - یک کلیک
 */
set_time_limit(300); // 5 دقیقه

class QuickSetup {
    private $steps = [];
    private $errors = [];
    
    public function run() {
        echo "<h2>🚀 راهاندازی سریع سیستم</h2>";
        echo "<div id='progress'></div>";
        
        $this->step1_checkRequirements();
        $this->step2_setupDatabase();
        $this->step3_createDirectories();
        $this->step4_optimizeSystem();
        $this->step5_finalizeSetup();
        
        $this->displayResults();
    }
    
    private function step1_checkRequirements() {
        $this->logStep("بررسی پیشنیازها...");
        
        // بررسی PHP version
        if (version_compare(PHP_VERSION, '7.4.0') >= 0) {
            $this->logStep("✅ PHP " . PHP_VERSION . " - مناسب");
        } else {
            $this->logError("❌ PHP version باید 7.4+ باشد");
        }
        
        // بررسی extensions
        $required = ['pdo', 'pdo_mysql', 'gd', 'mbstring', 'json'];
        foreach ($required as $ext) {
            if (extension_loaded($ext)) {
                $this->logStep("✅ Extension $ext - موجود");
            } else {
                $this->logError("❌ Extension $ext - ناموجود");
            }
        }
    }
    
    private function step2_setupDatabase() {
        $this->logStep("راهاندازی دیتابیس...");
        
        try {
            // اتصال به MySQL
            $pdo = new PDO("mysql:host=localhost", "root", "");
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            
            // ایجاد دیتابیس
            $pdo->exec("CREATE DATABASE IF NOT EXISTS motor_shop CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
            $this->logStep("✅ دیتابیس motor_shop ایجاد شد");
            
            // انتخاب دیتابیس
            $pdo->exec("USE motor_shop");
            
            // اجرای فایل SQL
            if (file_exists('database.sql')) {
                $sql = file_get_contents('database.sql');
                $pdo->exec($sql);
                $this->logStep("✅ جداول ایجاد شدند");
            }
            
            // ایجاد کاربر admin
            $hashedPassword = password_hash('admin123', PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT IGNORE INTO users (username, password, full_name, role) VALUES (?, ?, ?, ?)");
            $stmt->execute(['admin', $hashedPassword, 'مدیر سیستم', 'admin']);
            $this->logStep("✅ کاربر admin ایجاد شد");
            
            // تنظیمات پایه
            $settings = [
                ['shop_name', 'فروشگاه قطعات موتورسیکلت'],
                ['currency', 'afghani'],
                ['language', 'fa'],
                ['date_format', 'jalali']
            ];
            
            foreach ($settings as $setting) {
                $stmt = $pdo->prepare("INSERT IGNORE INTO settings (setting_key, setting_value) VALUES (?, ?)");
                $stmt->execute($setting);
            }
            $this->logStep("✅ تنظیمات پایه اضافه شد");
            
        } catch (Exception $e) {
            $this->logError("❌ خطای دیتابیس: " . $e->getMessage());
        }
    }
    
    private function step3_createDirectories() {
        $this->logStep("ایجاد فولدرهای ضروری...");
        
        $dirs = ['uploads/logos', 'logs', 'backups', 'cache'];
        
        foreach ($dirs as $dir) {
            if (!is_dir($dir)) {
                if (mkdir($dir, 0755, true)) {
                    $this->logStep("✅ فولدر $dir ایجاد شد");
                } else {
                    $this->logError("❌ خطا در ایجاد فولدر $dir");
                }
            } else {
                $this->logStep("✅ فولدر $dir موجود است");
            }
            
            // تست نوشتن
            $testFile = $dir . '/test.txt';
            if (file_put_contents($testFile, 'test')) {
                unlink($testFile);
                $this->logStep("✅ فولدر $dir قابل نوشتن است");
            } else {
                $this->logError("❌ فولدر $dir قابل نوشتن نیست");
            }
        }
    }
    
    private function step4_optimizeSystem() {
        $this->logStep("بهینهسازی سیستم...");
        
        // ایجاد .htaccess
        if (!file_exists('.htaccess')) {
            $htaccess = '# Performance & Security
<IfModule mod_deflate.c>
    AddOutputFilterByType DEFLATE text/css application/javascript text/html
</IfModule>

<IfModule mod_expires.c>
    ExpiresActive On
    ExpiresByType text/css "access plus 1 week"
    ExpiresByType application/javascript "access plus 1 week"
    ExpiresByType image/png "access plus 1 month"
    ExpiresByType image/jpg "access plus 1 month"
</IfModule>

<Files "*.php">
    Header set Cache-Control "no-cache"
</Files>';
            
            if (file_put_contents('.htaccess', $htaccess)) {
                $this->logStep("✅ فایل .htaccess ایجاد شد");
            }
        }
        
        // ایجاد کش ساده
        $cacheClass = '<?php
class SimpleCache {
    private static $dir = "cache/";
    
    public static function get($key) {
        $file = self::$dir . md5($key) . ".cache";
        if (file_exists($file) && (time() - filemtime($file)) < 3600) {
            return unserialize(file_get_contents($file));
        }
        return false;
    }
    
    public static function set($key, $data) {
        if (!is_dir(self::$dir)) mkdir(self::$dir, 0755, true);
        $file = self::$dir . md5($key) . ".cache";
        file_put_contents($file, serialize($data));
    }
}';
        
        if (file_put_contents('includes/SimpleCache.php', $cacheClass)) {
            $this->logStep("✅ سیستم کش ایجاد شد");
        }
        
        // بهینهسازی دیتابیس
        try {
            require_once 'config/database.php';
            $db = new Database();
            $conn = $db->getConnection();
            
            $indexes = [
                "CREATE INDEX IF NOT EXISTS idx_sales_date ON sales(created_at)",
                "CREATE INDEX IF NOT EXISTS idx_products_stock ON products(stock_quantity)",
                "CREATE INDEX IF NOT EXISTS idx_customers_phone ON customers(phone)"
            ];
            
            foreach ($indexes as $index) {
                $conn->exec($index);
            }
            $this->logStep("✅ ایندکسهای دیتابیس ایجاد شدند");
            
        } catch (Exception $e) {
            $this->logError("❌ خطا در بهینهسازی دیتابیس");
        }
    }
    
    private function step5_finalizeSetup() {
        $this->logStep("نهایی کردن راهاندازی...");
        
        // ایجاد فایل تست
        $testContent = '<?php
// تست سریع سیستم
require_once "init_security.php";
require_once "config/database.php";

try {
    $db = new Database();
    $conn = $db->getConnection();
    echo "✅ سیستم آماده است!";
} catch (Exception $e) {
    echo "❌ خطا: " . $e->getMessage();
}
?>';
        
        file_put_contents('test_system.php', $testContent);
        $this->logStep("✅ فایل تست ایجاد شد");
        
        // پاکسازی فایلهای موقت
        $tempFiles = glob('temp_*');
        foreach ($tempFiles as $file) {
            unlink($file);
        }
        $this->logStep("✅ فایلهای موقت پاک شدند");
    }
    
    private function logStep($message) {
        $this->steps[] = $message;
        echo "<p>$message</p>";
        flush();
        ob_flush();
        usleep(100000); // 0.1 ثانیه تاخیر برای نمایش
    }
    
    private function logError($message) {
        $this->errors[] = $message;
        echo "<p style='color:red;'>$message</p>";
        flush();
        ob_flush();
    }
    
    private function displayResults() {
        echo "<div style='background:#e8f5e8;padding:20px;border-radius:8px;margin:20px 0;'>";
        echo "<h3>🎉 راهاندازی کامل شد!</h3>";
        
        if (empty($this->errors)) {
            echo "<p><strong>✅ تمام مراحل با موفقیت انجام شد</strong></p>";
            echo "<h4>مراحل بعدی:</h4>";
            echo "<ol>";
            echo "<li><a href='login.php'>ورود به سیستم</a> (admin / admin123)</li>";
            echo "<li><a href='settings.php'>تنظیم اطلاعات فروشگاه</a></li>";
            echo "<li><a href='deployment_checker.php'>بررسی نهایی سیستم</a></li>";
            echo "</ol>";
        } else {
            echo "<p><strong>⚠️ برخی مشکلات وجود دارد:</strong></p>";
            echo "<ul>";
            foreach ($this->errors as $error) {
                echo "<li>$error</li>";
            }
            echo "</ul>";
        }
        
        echo "</div>";
        
        echo "<div style='background:#f8f9fa;padding:20px;border-radius:8px;margin:20px 0;'>";
        echo "<h4>📋 خلاصه مراحل انجام شده:</h4>";
        echo "<ul>";
        foreach ($this->steps as $step) {
            echo "<li>$step</li>";
        }
        echo "</ul>";
        echo "</div>";
    }
}

// اجرای راهاندازی
$setup = new QuickSetup();
$setup->run();
?>