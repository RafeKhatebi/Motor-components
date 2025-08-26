<?php
/**
 * راهاندازی کامل سیستم با لایسنس و سوپر ادمین
 */
require_once 'init_security.php';
require_once 'config/database.php';
require_once 'includes/LicenseManager.php';
require_once 'includes/SuperAdminManager.php';

set_time_limit(300);

class SystemSetup {
    private $db;
    private $licenseManager;
    private $superAdminManager;
    private $steps = [];
    private $errors = [];
    
    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
        $this->superAdminManager = new SuperAdminManager($this->db);
        $this->licenseManager = new LicenseManager($this->db, $this->superAdminManager);
    }
    
    public function run() {
        echo "<h2>🚀 راهاندازی کامل سیستم با لایسنس</h2>";
        
        $this->step1_checkSystem();
        $this->step2_setupDatabase();
        $this->step3_createSuperAdmin();
        $this->step4_generateInitialLicense();
        $this->step5_testSystem();
        
        $this->displayResults();
    }
    
    private function step1_checkSystem() {
        $this->logStep("🔍 بررسی سیستم...");
        
        // بررسی PHP version
        if (version_compare(PHP_VERSION, '7.4.0') >= 0) {
            $this->logStep("✅ PHP " . PHP_VERSION . " - مناسب");
        } else {
            $this->logError("❌ PHP version باید 7.4+ باشد");
        }
        
        // بررسی extensions
        $required = ['pdo', 'pdo_mysql', 'openssl', 'json'];
        foreach ($required as $ext) {
            if (extension_loaded($ext)) {
                $this->logStep("✅ Extension $ext - موجود");
            } else {
                $this->logError("❌ Extension $ext - ناموجود");
            }
        }
        
        // بررسی مجوزهای فولدر
        $dirs = ['uploads/logos', 'logs', 'backups', 'cache'];
        foreach ($dirs as $dir) {
            if (!is_dir($dir)) {
                mkdir($dir, 0755, true);
            }
            if (is_writable($dir)) {
                $this->logStep("✅ فولدر $dir - قابل نوشتن");
            } else {
                $this->logError("❌ فولدر $dir - غیرقابل نوشتن");
            }
        }
    }
    
    private function step2_setupDatabase() {
        $this->logStep("🗄️ راهاندازی دیتابیس...");
        
        try {
            // بررسی اتصال
            $this->db->query("SELECT 1");
            $this->logStep("✅ اتصال دیتابیس موفق");
            
            // ایجاد جداول اصلی (اگر وجود ندارند)
            $tables = [
                'users' => "CREATE TABLE IF NOT EXISTS users (
                    id INT PRIMARY KEY AUTO_INCREMENT,
                    username VARCHAR(50) UNIQUE NOT NULL,
                    password VARCHAR(255) NOT NULL,
                    full_name VARCHAR(100) NOT NULL,
                    role ENUM('admin','manager','employee') DEFAULT 'employee',
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                )",
                'settings' => "CREATE TABLE IF NOT EXISTS settings (
                    id INT PRIMARY KEY AUTO_INCREMENT,
                    setting_key VARCHAR(255) UNIQUE,
                    setting_value TEXT,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
                )"
            ];
            
            foreach ($tables as $name => $sql) {
                $this->db->exec($sql);
                $this->logStep("✅ جدول $name آماده");
            }
            
            // ایجاد کاربر admin پیشفرض (اگر وجود ندارد)
            $stmt = $this->db->prepare("SELECT COUNT(*) FROM users WHERE role = 'admin'");
            $stmt->execute();
            
            if ($stmt->fetchColumn() == 0) {
                $hashedPassword = password_hash('admin123', PASSWORD_DEFAULT);
                $stmt = $this->db->prepare("INSERT INTO users (username, password, full_name, role) VALUES (?, ?, ?, ?)");
                $stmt->execute(['admin', $hashedPassword, 'مدیر سیستم', 'admin']);
                $this->logStep("✅ کاربر admin ایجاد شد");
            }
            
        } catch (Exception $e) {
            $this->logError("❌ خطای دیتابیس: " . $e->getMessage());
        }
    }
    
    private function step3_createSuperAdmin() {
        $this->logStep("👑 ایجاد سوپر ادمین...");
        
        try {
            $hardwareId = $this->licenseManager->generateHardwareID();
            
            if (!$this->superAdminManager->superAdminExists()) {
                $result = $this->superAdminManager->createInitialSuperAdmin($hardwareId);
                
                if ($result['success']) {
                    $this->logStep("✅ سوپر ادمین ایجاد شد");
                    $this->logStep("📝 نام کاربری: " . $result['username']);
                    $this->logStep("🔑 رمز عبور: " . $result['password']);
                    
                    // ذخیره اطلاعات برای نمایش نهایی
                    $GLOBALS['super_admin_credentials'] = $result;
                } else {
                    $this->logError("❌ " . $result['message']);
                }
            } else {
                $this->logStep("ℹ️ سوپر ادمین قبلاً وجود دارد");
            }
            
        } catch (Exception $e) {
            $this->logError("❌ خطا در ایجاد سوپر ادمین: " . $e->getMessage());
        }
    }
    
    private function step4_generateInitialLicense() {
        $this->logStep("🔐 تولید لایسنس اولیه...");
        
        try {
            $hardwareId = $this->licenseManager->generateHardwareID();
            
            // بررسی وجود لایسنس فعال
            $validation = $this->licenseManager->validateLicense();
            
            if (!$validation['valid']) {
                // تولید لایسنس 1 ساله با 10 کاربر
                $licenseKey = $this->licenseManager->generateLicenseKey(
                    $hardwareId, 
                    365, // 1 سال
                    10,  // 10 کاربر
                    ['advanced_reports', 'multi_branch', 'api_access'] // تمام ویژگیها
                );
                
                // فعالسازی خودکار
                $result = $this->licenseManager->activateLicense($licenseKey);
                
                if ($result['success']) {
                    $this->logStep("✅ لایسنس اولیه فعال شد (1 سال، 10 کاربر)");
                    $GLOBALS['initial_license'] = $licenseKey;
                } else {
                    $this->logError("❌ " . $result['message']);
                }
            } else {
                $this->logStep("ℹ️ لایسنس فعال موجود است");
            }
            
        } catch (Exception $e) {
            $this->logError("❌ خطا در تولید لایسنس: " . $e->getMessage());
        }
    }
    
    private function step5_testSystem() {
        $this->logStep("🧪 تست سیستم...");
        
        try {
            // تست لایسنس
            $validation = $this->licenseManager->validateLicense();
            if ($validation['valid']) {
                $this->logStep("✅ لایسنس معتبر - " . $validation['days_remaining'] . " روز باقیمانده");
            } else {
                $this->logError("❌ لایسنس نامعتبر");
            }
            
            // تست سوپر ادمین
            if ($this->superAdminManager->superAdminExists()) {
                $this->logStep("✅ سوپر ادمین آماده");
            } else {
                $this->logError("❌ سوپر ادمین ناموجود");
            }
            
            // تست فایلهای ضروری
            $files = ['login.php', 'dashboard.php', 'super_admin_login.php', 'super_admin_panel.php'];
            foreach ($files as $file) {
                if (file_exists($file)) {
                    $this->logStep("✅ فایل $file موجود");
                } else {
                    $this->logError("❌ فایل $file ناموجود");
                }
            }
            
        } catch (Exception $e) {
            $this->logError("❌ خطا در تست: " . $e->getMessage());
        }
    }
    
    private function logStep($message) {
        $this->steps[] = $message;
        echo "<p>$message</p>";
        flush();
        ob_flush();
        usleep(200000); // 0.2 ثانیه تاخیر
    }
    
    private function logError($message) {
        $this->errors[] = $message;
        echo "<p style='color:red;'>$message</p>";
        flush();
        ob_flush();
    }
    
    private function displayResults() {
        $hardwareId = $this->licenseManager->generateHardwareID();
        
        echo "<div style='background:#e8f5e8;padding:20px;border-radius:8px;margin:20px 0;'>";
        echo "<h3>🎉 راهاندازی کامل شد!</h3>";
        
        if (empty($this->errors)) {
            echo "<p><strong>✅ سیستم آماده استفاده است</strong></p>";
            
            echo "<div style='background:#d1ecf1;padding:15px;border-radius:8px;margin:15px 0;'>";
            echo "<h4>📋 اطلاعات مهم:</h4>";
            echo "<p><strong>شناسه سیستم:</strong><br><code>$hardwareId</code></p>";
            
            if (isset($GLOBALS['super_admin_credentials'])) {
                $creds = $GLOBALS['super_admin_credentials'];
                echo "<p><strong>🔐 اطلاعات سوپر ادمین:</strong><br>";
                echo "نام کاربری: <code>{$creds['username']}</code><br>";
                echo "رمز عبور: <code>{$creds['password']}</code></p>";
            }
            
            if (isset($GLOBALS['initial_license'])) {
                echo "<p><strong>🎫 لایسنس اولیه:</strong><br>";
                echo "<textarea style='width:100%;height:60px;font-family:monospace;font-size:10px;'>" . $GLOBALS['initial_license'] . "</textarea></p>";
            }
            echo "</div>";
            
            echo "<h4>🚀 مراحل بعدی:</h4>";
            echo "<ol>";
            echo "<li><a href='super_admin_login.php' target='_blank'>ورود سوپر ادمین</a></li>";
            echo "<li><a href='login.php' target='_blank'>ورود کاربر عادی</a> (admin / admin123)</li>";
            echo "<li>تغییر رمزهای عبور پیشفرض</li>";
            echo "<li>تنظیم اطلاعات فروشگاه</li>";
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
        echo "<h4>📋 خلاصه مراحل:</h4>";
        echo "<ul>";
        foreach ($this->steps as $step) {
            echo "<li>$step</li>";
        }
        echo "</ul>";
        echo "</div>";
    }
}

// اجرای راهاندازی
$setup = new SystemSetup();
$setup->run();
?>