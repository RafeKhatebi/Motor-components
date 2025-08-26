<?php
/**
 * تست عملکرد سیستم - سنجش آفلاین
 */
require_once 'init_security.php';
require_once 'config/database.php';

class PerformanceTester {
    private $results = [];
    private $startTime;
    
    public function __construct() {
        $this->startTime = microtime(true);
    }
    
    public function runTests() {
        echo "<h2>⚡ تست عملکرد سیستم</h2>";
        
        $this->testDatabasePerformance();
        $this->testPageLoadTimes();
        $this->testMemoryUsage();
        $this->testFileSystemPerformance();
        
        $this->displayResults();
        $this->generateReport();
    }
    
    private function testDatabasePerformance() {
        echo "<h3>🗄️ تست عملکرد دیتابیس</h3>";
        
        $database = new Database();
        $db = $database->getConnection();
        
        // تست اتصال
        $start = microtime(true);
        $stmt = $db->prepare("SELECT 1");
        $stmt->execute();
        $connectionTime = (microtime(true) - $start) * 1000;
        
        $this->results['db_connection'] = $connectionTime;
        echo "<p>⏱️ زمان اتصال: " . number_format($connectionTime, 2) . " ms</p>";
        
        // تست کوئری ساده
        $start = microtime(true);
        $stmt = $db->prepare("SELECT COUNT(*) FROM products");
        $stmt->execute();
        $simpleQueryTime = (microtime(true) - $start) * 1000;
        
        $this->results['simple_query'] = $simpleQueryTime;
        echo "<p>⏱️ کوئری ساده: " . number_format($simpleQueryTime, 2) . " ms</p>";
        
        // تست کوئری پیچیده
        $start = microtime(true);
        $stmt = $db->prepare("
            SELECT s.id, c.name, s.final_amount, COUNT(si.id) as items
            FROM sales s 
            LEFT JOIN customers c ON s.customer_id = c.id 
            LEFT JOIN sale_items si ON s.id = si.sale_id 
            WHERE s.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
            GROUP BY s.id 
            ORDER BY s.created_at DESC 
            LIMIT 10
        ");
        $stmt->execute();
        $complexQueryTime = (microtime(true) - $start) * 1000;
        
        $this->results['complex_query'] = $complexQueryTime;
        echo "<p>⏱️ کوئری پیچیده: " . number_format($complexQueryTime, 2) . " ms</p>";
    }
    
    private function testPageLoadTimes() {
        echo "<h3>📄 تست زمان بارگذاری صفحات</h3>";
        
        $pages = [
            'dashboard.php' => 'داشبورد',
            'products.php' => 'محصولات',
            'sales.php' => 'فروش',
            'customers.php' => 'مشتریان'
        ];
        
        foreach ($pages as $page => $title) {
            if (file_exists($page)) {
                $start = microtime(true);
                
                // شبیهسازی بارگذاری صفحه
                ob_start();
                $fileSize = filesize($page);
                $content = file_get_contents($page);
                ob_end_clean();
                
                $loadTime = (microtime(true) - $start) * 1000;
                
                $this->results["page_$page"] = $loadTime;
                echo "<p>📄 $title: " . number_format($loadTime, 2) . " ms (اندازه: " . number_format($fileSize/1024, 1) . " KB)</p>";
            }
        }
    }
    
    private function testMemoryUsage() {
        echo "<h3>💾 تست استفاده از حافظه</h3>";
        
        $memoryStart = memory_get_usage();
        $memoryPeakStart = memory_get_peak_usage();
        
        // شبیهسازی عملیات سنگین
        $data = [];
        for ($i = 0; $i < 10000; $i++) {
            $data[] = [
                'id' => $i,
                'name' => 'محصول ' . $i,
                'price' => rand(100, 1000),
                'description' => str_repeat('توضیحات ', 10)
            ];
        }
        
        $memoryEnd = memory_get_usage();
        $memoryPeakEnd = memory_get_peak_usage();
        
        $memoryUsed = ($memoryEnd - $memoryStart) / 1024 / 1024;
        $memoryPeak = ($memoryPeakEnd - $memoryPeakStart) / 1024 / 1024;
        
        $this->results['memory_used'] = $memoryUsed;
        $this->results['memory_peak'] = $memoryPeak;
        
        echo "<p>💾 حافظه استفاده شده: " . number_format($memoryUsed, 2) . " MB</p>";
        echo "<p>💾 حداکثر حافظه: " . number_format($memoryPeak, 2) . " MB</p>";
        echo "<p>💾 حافظه کل سیستم: " . number_format(memory_get_usage(true) / 1024 / 1024, 2) . " MB</p>";
        
        unset($data); // آزادسازی حافظه
    }
    
    private function testFileSystemPerformance() {
        echo "<h3>📁 تست عملکرد فایل سیستم</h3>";
        
        // تست نوشتن فایل
        $testData = str_repeat('تست عملکرد ', 1000);
        $testFile = 'temp_performance_test.txt';
        
        $start = microtime(true);
        file_put_contents($testFile, $testData);
        $writeTime = (microtime(true) - $start) * 1000;
        
        // تست خواندن فایل
        $start = microtime(true);
        $readData = file_get_contents($testFile);
        $readTime = (microtime(true) - $start) * 1000;
        
        // پاک کردن فایل تست
        unlink($testFile);
        
        $this->results['file_write'] = $writeTime;
        $this->results['file_read'] = $readTime;
        
        echo "<p>✍️ نوشتن فایل: " . number_format($writeTime, 2) . " ms</p>";
        echo "<p>📖 خواندن فایل: " . number_format($readTime, 2) . " ms</p>";
    }
    
    private function displayResults() {
        $totalTime = (microtime(true) - $this->startTime) * 1000;
        
        echo "<div style='background:#e8f5e8;padding:20px;border-radius:8px;margin:20px 0;'>";
        echo "<h3>📊 خلاصه نتایج:</h3>";
        echo "<p><strong>⏱️ زمان کل تست:</strong> " . number_format($totalTime, 2) . " ms</p>";
        
        // ارزیابی عملکرد
        $score = $this->calculatePerformanceScore();
        $grade = $this->getPerformanceGrade($score);
        
        echo "<p><strong>🎯 امتیاز عملکرد:</strong> $score/100</p>";
        echo "<p><strong>📈 درجه:</strong> <span style='font-size:1.2em;'>$grade</span></p>";
        echo "</div>";
    }
    
    private function calculatePerformanceScore() {
        $score = 100;
        
        // کسر امتیاز بر اساس زمان پاسخ دیتابیس
        if ($this->results['db_connection'] > 10) $score -= 10;
        if ($this->results['simple_query'] > 5) $score -= 10;
        if ($this->results['complex_query'] > 50) $score -= 15;
        
        // کسر امتیاز بر اساس استفاده از حافظه
        if ($this->results['memory_used'] > 10) $score -= 10;
        if ($this->results['memory_peak'] > 20) $score -= 10;
        
        // کسر امتیاز بر اساس عملکرد فایل سیستم
        if ($this->results['file_write'] > 10) $score -= 5;
        if ($this->results['file_read'] > 5) $score -= 5;
        
        return max(0, $score);
    }
    
    private function getPerformanceGrade($score) {
        if ($score >= 90) return "🟢 عالی (A+)";
        if ($score >= 80) return "🔵 خوب (A)";
        if ($score >= 70) return "🟡 متوسط (B)";
        if ($score >= 60) return "🟠 ضعیف (C)";
        return "🔴 بسیار ضعیف (D)";
    }
    
    private function generateReport() {
        echo "<div style='background:#f8f9fa;padding:20px;border-radius:8px;margin:20px 0;'>";
        echo "<h3>📋 گزارش تفصیلی:</h3>";
        
        echo "<h4>🔧 توصیههای بهینهسازی:</h4>";
        echo "<ul>";
        
        if ($this->results['db_connection'] > 10) {
            echo "<li>⚠️ زمان اتصال دیتابیس بالا - بررسی تنظیمات MySQL</li>";
        }
        
        if ($this->results['complex_query'] > 50) {
            echo "<li>⚠️ کوئریهای پیچیده کند - نیاز به ایندکسگذاری</li>";
        }
        
        if ($this->results['memory_used'] > 10) {
            echo "<li>⚠️ استفاده بالا از حافظه - بهینهسازی کد</li>";
        }
        
        echo "<li>✅ فعالسازی OPcache برای بهبود عملکرد</li>";
        echo "<li>✅ استفاده از سیستم کش برای کوئریها</li>";
        echo "<li>✅ مینیفای کردن CSS/JS</li>";
        echo "<li>✅ فشردهسازی تصاویر</li>";
        echo "</ul>";
        
        echo "<h4>📈 مقایسه با استانداردها:</h4>";
        echo "<table border='1' style='width:100%;border-collapse:collapse;'>";
        echo "<tr><th>متریک</th><th>مقدار فعلی</th><th>استاندارد</th><th>وضعیت</th></tr>";
        
        $standards = [
            ['اتصال DB', $this->results['db_connection'], 5, 'ms'],
            ['کوئری ساده', $this->results['simple_query'], 3, 'ms'],
            ['کوئری پیچیده', $this->results['complex_query'], 30, 'ms'],
            ['استفاده حافظه', $this->results['memory_used'], 5, 'MB']
        ];
        
        foreach ($standards as $std) {
            $status = $std[1] <= $std[2] ? '✅ خوب' : '⚠️ نیاز به بهبود';
            echo "<tr>";
            echo "<td>{$std[0]}</td>";
            echo "<td>" . number_format($std[1], 2) . " {$std[3]}</td>";
            echo "<td>< {$std[2]} {$std[3]}</td>";
            echo "<td>$status</td>";
            echo "</tr>";
        }
        
        echo "</table>";
        echo "</div>";
    }
}

// اجرای تست
$tester = new PerformanceTester();
$tester->runTests();
?>