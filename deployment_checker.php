<?php
/**
 * چکر نهایی تحویل سیستم
 */
require_once 'init_security.php';

class DeploymentChecker {
    private $results = [];
    private $score = 0;
    private $maxScore = 0;
    
    public function runChecks() {
        echo "<h2>🔍 بررسی نهایی آمادگی تحویل</h2>";
        
        $this->checkDatabase();
        $this->checkSecurity();
        $this->checkAPI();
        $this->checkPerformance();
        $this->checkBackup();
        $this->checkFiles();
        
        $this->displayResults();
        $this->generateReport();
    }
    
    private function checkDatabase() {
        echo "<h3>🗄️ بررسی دیتابیس</h3>";
        
        try {
            require_once 'config/database.php';
            $db = new Database();
            $conn = $db->getConnection();
            $this->addResult('DB Connection', true, 'اتصال موفق');
            
            // بررسی جداول
            $tables = ['users', 'products', 'categories', 'customers', 'suppliers', 'sales', 'purchases'];
            foreach ($tables as $table) {
                $stmt = $conn->prepare("SELECT 1 FROM $table LIMIT 1");
                $stmt->execute();
                $this->addResult("Table $table", true, 'موجود');
            }
            
            // بررسی کاربر admin
            $stmt = $conn->prepare("SELECT COUNT(*) FROM users WHERE username = 'admin'");
            $stmt->execute();
            $adminExists = $stmt->fetchColumn() > 0;
            $this->addResult('Admin User', $adminExists, $adminExists ? 'موجود' : 'ناموجود');
            
            // بررسی ایندکسها
            $stmt = $conn->prepare("SHOW INDEX FROM sales WHERE Key_name = 'idx_sales_date_status'");
            $stmt->execute();
            $indexExists = $stmt->rowCount() > 0;
            $this->addResult('DB Indexes', $indexExists, $indexExists ? 'بهینه' : 'نیاز به بهینهسازی');
            
        } catch (Exception $e) {
            $this->addResult('DB Connection', false, $e->getMessage());
        }
    }
    
    private function checkSecurity() {
        echo "<h3>🔒 بررسی امنیت</h3>";
        
        // بررسی CSRF
        session_start();
        $csrfExists = isset($_SESSION['csrf_token']);
        $this->addResult('CSRF Protection', $csrfExists, $csrfExists ? 'فعال' : 'غیرفعال');
        
        // بررسی .htaccess
        $htaccessExists = file_exists('.htaccess');
        $this->addResult('.htaccess File', $htaccessExists, $htaccessExists ? 'موجود' : 'ناموجود');
        
        // بررسی محافظت فولدرها
        $protectedDirs = ['config/', 'includes/', 'logs/'];
        foreach ($protectedDirs as $dir) {
            $protected = $this->checkDirectoryProtection($dir);
            $this->addResult("Protection $dir", $protected, $protected ? 'محافظت شده' : 'نامحافظ');
        }
        
        // بررسی PHP settings
        $displayErrors = ini_get('display_errors') == '0';
        $this->addResult('Display Errors Off', $displayErrors, $displayErrors ? 'امن' : 'ناامن');
    }
    
    private function checkAPI() {
        echo "<h3>🔌 بررسی API</h3>";
        
        $apiFiles = [
            'api/add_product.php',
            'api/add_customer.php',
            'api/add_sale.php',
            'api/add_purchase.php'
        ];
        
        foreach ($apiFiles as $api) {
            $exists = file_exists($api);
            $this->addResult("API " . basename($api), $exists, $exists ? 'موجود' : 'ناموجود');
        }
        
        // تست API ساده
        $this->testAPIResponse();
    }
    
    private function testAPIResponse() {
        // شبیهسازی درخواست API
        $_POST['csrf_token'] = $_SESSION['csrf_token'] ?? '';
        $_POST['name'] = 'تست';
        $_POST['code'] = 'TEST001';
        $_POST['category_id'] = 1;
        $_POST['buy_price'] = 100;
        $_POST['sell_price'] = 150;
        
        ob_start();
        $response = @include 'api/add_product.php';
        $output = ob_get_clean();
        
        $apiWorks = !empty($output) || $response !== false;
        $this->addResult('API Response', $apiWorks, $apiWorks ? 'پاسخ میدهد' : 'خطا دارد');
    }
    
    private function checkPerformance() {
        echo "<h3>⚡ بررسی عملکرد</h3>";
        
        // بررسی OPcache
        $opcacheEnabled = function_exists('opcache_get_status') && opcache_get_status();
        $this->addResult('OPcache', $opcacheEnabled, $opcacheEnabled ? 'فعال' : 'غیرفعال');
        
        // بررسی فایلهای مینیفای
        $minifiedCSS = file_exists('assets/css/combined.min.css');
        $this->addResult('Minified CSS', $minifiedCSS, $minifiedCSS ? 'موجود' : 'ناموجود');
        
        $minifiedJS = file_exists('assets/js/combined.min.js');
        $this->addResult('Minified JS', $minifiedJS, $minifiedJS ? 'موجود' : 'ناموجود');
        
        // تست سرعت پایه
        $start = microtime(true);
        require_once 'includes/functions.php';
        $loadTime = (microtime(true) - $start) * 1000;
        $fastLoad = $loadTime < 50;
        $this->addResult('Load Speed', $fastLoad, number_format($loadTime, 2) . ' ms');
        
        // بررسی حافظه
        $memoryUsage = memory_get_usage(true) / 1024 / 1024;
        $lowMemory = $memoryUsage < 32;
        $this->addResult('Memory Usage', $lowMemory, number_format($memoryUsage, 1) . ' MB');
    }
    
    private function checkBackup() {
        echo "<h3>💾 بررسی بکاپ</h3>";
        
        // بررسی فولدر بکاپ
        $backupDir = 'backups/';
        $backupExists = is_dir($backupDir) && is_writable($backupDir);
        $this->addResult('Backup Directory', $backupExists, $backupExists ? 'آماده' : 'مشکل دار');
        
        // بررسی فایل بکاپ
        $backupFile = file_exists('backup.php');
        $this->addResult('Backup Script', $backupFile, $backupFile ? 'موجود' : 'ناموجود');
        
        // تست ایجاد بکاپ
        if ($backupExists) {
            $testBackup = $this->createTestBackup();
            $this->addResult('Backup Creation', $testBackup, $testBackup ? 'کار میکند' : 'خطا دارد');
        }
    }
    
    private function checkFiles() {
        echo "<h3>📁 بررسی فایلها</h3>";
        
        // فایلهای ضروری
        $requiredFiles = [
            'index.php',
            'login.php',
            'dashboard.php',
            'config/database.php',
            'includes/auth.php',
            'includes/functions.php'
        ];
        
        foreach ($requiredFiles as $file) {
            $exists = file_exists($file);
            $this->addResult("File " . basename($file), $exists, $exists ? 'موجود' : 'ناموجود');
        }
        
        // بررسی مجوزهای فولدر
        $writableDirs = ['uploads/', 'logs/', 'backups/'];
        foreach ($writableDirs as $dir) {
            if (is_dir($dir)) {
                $writable = is_writable($dir);
                $this->addResult("Writable $dir", $writable, $writable ? 'قابل نوشتن' : 'فقط خواندنی');
            }
        }
    }
    
    private function checkDirectoryProtection($dir) {
        if (!is_dir($dir)) return false;
        
        // بررسی وجود .htaccess در فولدر
        $htaccess = $dir . '.htaccess';
        if (file_exists($htaccess)) {
            $content = file_get_contents($htaccess);
            return strpos($content, 'Deny from all') !== false;
        }
        
        return false;
    }
    
    private function createTestBackup() {
        try {
            $testFile = 'backups/test_' . date('Y-m-d_H-i-s') . '.sql';
            $content = "-- Test backup file\nSELECT 1;";
            $result = file_put_contents($testFile, $content);
            
            if ($result && file_exists($testFile)) {
                unlink($testFile); // حذف فایل تست
                return true;
            }
        } catch (Exception $e) {
            return false;
        }
        
        return false;
    }
    
    private function addResult($test, $passed, $message) {
        $this->results[] = [
            'test' => $test,
            'passed' => $passed,
            'message' => $message
        ];
        
        if ($passed) $this->score++;
        $this->maxScore++;
    }
    
    private function displayResults() {
        $percentage = round(($this->score / $this->maxScore) * 100);
        $grade = $this->getGrade($percentage);
        
        echo "<div style='background:#e8f5e8;padding:20px;border-radius:8px;margin:20px 0;'>";
        echo "<h3>📊 نتیجه نهایی:</h3>";
        echo "<p><strong>امتیاز:</strong> {$this->score}/{$this->maxScore} ($percentage%)</p>";
        echo "<p><strong>درجه:</strong> <span style='font-size:1.2em;'>$grade</span></p>";
        echo "</div>";
        
        echo "<div style='background:#f8f9fa;padding:20px;border-radius:8px;margin:20px 0;'>";
        echo "<h3>📋 جزئیات بررسی:</h3>";
        echo "<table border='1' style='width:100%;border-collapse:collapse;'>";
        echo "<tr><th>تست</th><th>وضعیت</th><th>توضیحات</th></tr>";
        
        foreach ($this->results as $result) {
            $status = $result['passed'] ? '✅ موفق' : '❌ ناموفق';
            $rowColor = $result['passed'] ? '#d4edda' : '#f8d7da';
            echo "<tr style='background:$rowColor;'>";
            echo "<td>{$result['test']}</td>";
            echo "<td>$status</td>";
            echo "<td>{$result['message']}</td>";
            echo "</tr>";
        }
        
        echo "</table>";
        echo "</div>";
    }
    
    private function generateReport() {
        $failedTests = array_filter($this->results, function($r) { return !$r['passed']; });
        
        if (!empty($failedTests)) {
            echo "<div style='background:#fff3cd;padding:20px;border-radius:8px;margin:20px 0;'>";
            echo "<h3>⚠️ موارد نیازمند رفع:</h3>";
            echo "<ul>";
            foreach ($failedTests as $test) {
                echo "<li><strong>{$test['test']}:</strong> {$test['message']}</li>";
            }
            echo "</ul>";
            echo "</div>";
        }
        
        echo "<div style='background:#d1ecf1;padding:20px;border-radius:8px;margin:20px 0;'>";
        echo "<h3>📋 چکلیست تحویل:</h3>";
        
        $readyForDeployment = $this->score >= ($this->maxScore * 0.9);
        
        if ($readyForDeployment) {
            echo "<p style='color:#155724;font-weight:bold;'>🎉 سیستم آماده تحویل است!</p>";
            echo "<h4>مراحل نهایی:</h4>";
            echo "<ol>";
            echo "<li>تغییر رمز عبور admin</li>";
            echo "<li>تنظیم اطلاعات فروشگاه در settings</li>";
            echo "<li>ایجاد بکاپ اولیه</li>";
            echo "<li>تست نهایی توسط مشتری</li>";
            echo "</ol>";
        } else {
            echo "<p style='color:#721c24;font-weight:bold;'>⚠️ سیستم نیاز به رفع مشکلات دارد</p>";
            echo "<p>لطفاً موارد ناموفق را برطرف کنید و مجدداً تست کنید.</p>";
        }
        
        echo "</div>";
    }
    
    private function getGrade($percentage) {
        if ($percentage >= 95) return "🟢 عالی (A+)";
        if ($percentage >= 90) return "🔵 خوب (A)";
        if ($percentage >= 80) return "🟡 قابل قبول (B)";
        if ($percentage >= 70) return "🟠 ضعیف (C)";
        return "🔴 نیاز به کار بیشتر (D)";
    }
}

// اجرای بررسی
$checker = new DeploymentChecker();
$checker->runChecks();
?>