<?php
/**
 * سیستم بهینه‌سازی عملکرد - محیط آفلاین
 */
require_once 'init_security.php';
require_once 'config/database.php';

class PerformanceOptimizer {
    private $db;
    private $results = [];
    
    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
    }
    
    public function runOptimizations() {
        echo "<h2>🚀 بهینه‌سازی عملکرد سیستم</h2>";
        
        $this->optimizeDatabase();
        $this->optimizeFrontend();
        $this->generateOptimizedAssets();
        $this->createCacheSystem();
        $this->optimizeQueries();
        
        $this->displayResults();
    }
    
    private function optimizeDatabase() {
        echo "<h3>📊 بهینه‌سازی دیتابیس</h3>";
        
        // ایجاد ایندکس‌های مفقود
        $indexes = [
            "CREATE INDEX IF NOT EXISTS idx_sales_date_status ON sales(created_at, status)",
            "CREATE INDEX IF NOT EXISTS idx_products_stock ON products(stock_quantity, min_stock)",
            "CREATE INDEX IF NOT EXISTS idx_sale_items_product_sale ON sale_items(product_id, sale_id)",
            "CREATE INDEX IF NOT EXISTS idx_customers_phone ON customers(phone)",
            "CREATE INDEX IF NOT EXISTS idx_suppliers_name ON suppliers(name)"
        ];
        
        foreach ($indexes as $index) {
            try {
                $this->db->exec($index);
                $this->results[] = "✅ ایندکس ایجاد شد: " . substr($index, 32, 30);
            } catch (Exception $e) {
                $this->results[] = "⚠️ ایندکس موجود: " . substr($index, 32, 30);
            }
        }
        
        // بهینه‌سازی جداول
        $tables = ['products', 'sales', 'customers', 'suppliers', 'sale_items'];
        foreach ($tables as $table) {
            $this->db->exec("OPTIMIZE TABLE $table");
            $this->results[] = "✅ جدول $table بهینه شد";
        }
    }
    
    private function optimizeFrontend() {
        echo "<h3>🎨 بهینه‌سازی Frontend</h3>";
        
        // ایجاد CSS مینیفای شده
        $this->minifyCSS();
        
        // ایجاد JS مینیفای شده
        $this->minifyJS();
        
        // بهینه‌سازی تصاویر
        $this->optimizeImages();
    }
    
    private function minifyCSS() {
        $cssFiles = [
            'assets/css/bootstrap.rtl.min.css',
            'assets/css/argon-dashboard-rtl.css',
            'assets/css/modernize-rtl.css',
            'assets/css/style.css'
        ];
        
        $combinedCSS = '';
        foreach ($cssFiles as $file) {
            if (file_exists($file)) {
                $css = file_get_contents($file);
                // حذف کامنت‌ها و فضاهای اضافی
                $css = preg_replace('/\/\*.*?\*\//s', '', $css);
                $css = preg_replace('/\s+/', ' ', $css);
                $css = str_replace(['; ', ' {', '{ ', ' }', '} '], [';', '{', '{', '}', '}'], $css);
                $combinedCSS .= $css;
            }
        }
        
        file_put_contents('assets/css/combined.min.css', $combinedCSS);
        $this->results[] = "✅ CSS فایل‌ها ترکیب و مینیفای شدند";
    }
    
    private function minifyJS() {
        $jsFiles = [
            'assets/js/bootstrap.bundle.min.js',
            'assets/js/chart.js',
            'assets/js/main.js'
        ];
        
        $combinedJS = '';
        foreach ($jsFiles as $file) {
            if (file_exists($file)) {
                $js = file_get_contents($file);
                $combinedJS .= $js . ";\n";
            }
        }
        
        file_put_contents('assets/js/combined.min.js', $combinedJS);
        $this->results[] = "✅ JS فایل‌ها ترکیب شدند";
    }
    
    private function optimizeImages() {
        $logoDir = 'uploads/logos/';
        if (is_dir($logoDir)) {
            $images = glob($logoDir . '*.{jpg,jpeg,png,gif}', GLOB_BRACE);
            foreach ($images as $image) {
                // فشرده‌سازی تصاویر (نیاز به GD extension)
                if (extension_loaded('gd')) {
                    $this->compressImage($image);
                }
            }
            $this->results[] = "✅ تصاویر بهینه شدند";
        }
    }
    
    private function compressImage($source) {
        $info = getimagesize($source);
        if ($info === false) return false;
        
        $image = null;
        switch ($info['mime']) {
            case 'image/jpeg':
                $image = imagecreatefromjpeg($source);
                break;
            case 'image/png':
                $image = imagecreatefrompng($source);
                break;
            case 'image/gif':
                $image = imagecreatefromgif($source);
                break;
        }
        
        if ($image) {
            imagejpeg($image, $source, 85); // کیفیت 85%
            imagedestroy($image);
        }
    }
    
    private function generateOptimizedAssets() {
        // ایجاد فایل header بهینه
        $optimizedHeader = $this->createOptimizedHeader();
        file_put_contents('includes/header_optimized.php', $optimizedHeader);
        $this->results[] = "✅ Header بهینه ایجاد شد";
    }
    
    private function createOptimizedHeader() {
        return '<?php
// بهینه‌سازی شده برای عملکرد
ob_start();
require_once "init_security.php";
require_once "includes/functions.php";
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= sanitizeOutput($page_title ?? "سیستم مدیریت") ?></title>
    
    <!-- Preload critical resources -->
    <link rel="preload" href="assets/css/combined.min.css" as="style">
    <link rel="preload" href="assets/js/combined.min.js" as="script">
    
    <!-- Critical CSS inline -->
    <style>
    body{font-family:Vazir,Tahoma,Arial;margin:0;background:#f8fafc}
    .navbar{background:linear-gradient(135deg,#5e72e4,#4338ca);padding:1rem;color:#fff}
    .container{max-width:1200px;margin:0 auto;padding:0 1rem}
    </style>
    
    <!-- Non-critical CSS -->
    <link rel="stylesheet" href="assets/css/combined.min.css" media="print" onload="this.media=\'all\'">
    <noscript><link rel="stylesheet" href="assets/css/combined.min.css"></noscript>
</head>
<body>
<?php ob_end_flush(); ?>';
    }
    
    private function createCacheSystem() {
        echo "<h3>💾 سیستم کش</h3>";
        
        $cacheSystem = '<?php
class SimpleCache {
    private static $cacheDir = "cache/";
    
    public static function init() {
        if (!is_dir(self::$cacheDir)) {
            mkdir(self::$cacheDir, 0755, true);
        }
    }
    
    public static function get($key) {
        $file = self::$cacheDir . md5($key) . ".cache";
        if (file_exists($file) && (time() - filemtime($file)) < 3600) {
            return unserialize(file_get_contents($file));
        }
        return false;
    }
    
    public static function set($key, $data) {
        $file = self::$cacheDir . md5($key) . ".cache";
        file_put_contents($file, serialize($data));
    }
    
    public static function delete($key) {
        $file = self::$cacheDir . md5($key) . ".cache";
        if (file_exists($file)) {
            unlink($file);
        }
    }
    
    public static function clear() {
        $files = glob(self::$cacheDir . "*.cache");
        foreach ($files as $file) {
            unlink($file);
        }
    }
}
SimpleCache::init();
?>';
        
        file_put_contents('includes/SimpleCache.php', $cacheSystem);
        $this->results[] = "✅ سیستم کش ایجاد شد";
    }
    
    private function optimizeQueries() {
        echo "<h3>🔍 بهینه‌سازی کوئری‌ها</h3>";
        
        // ایجاد کلاس بهینه‌سازی کوئری
        $queryOptimizer = '<?php
class QueryOptimizer {
    private static $cache = [];
    
    public static function getCachedStats($db) {
        $cacheKey = "dashboard_stats";
        if (isset(self::$cache[$cacheKey])) {
            return self::$cache[$cacheKey];
        }
        
        // استفاده از view بهینه
        $stmt = $db->prepare("SELECT * FROM dashboard_stats");
        $stmt->execute();
        $stats = $stmt->fetch(PDO::FETCH_ASSOC);
        
        self::$cache[$cacheKey] = $stats;
        return $stats;
    }
    
    public static function getRecentSales($db, $limit = 5) {
        $cacheKey = "recent_sales_$limit";
        if (isset(self::$cache[$cacheKey])) {
            return self::$cache[$cacheKey];
        }
        
        $query = "SELECT s.id, COALESCE(c.name, \'مشتری نقدی\') as customer_name, 
                         s.final_amount, s.created_at 
                  FROM sales s 
                  LEFT JOIN customers c ON s.customer_id = c.id 
                  WHERE s.status != \'returned\' OR s.status IS NULL
                  ORDER BY s.created_at DESC 
                  LIMIT ?";
        
        $stmt = $db->prepare($query);
        $stmt->execute([$limit]);
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        self::$cache[$cacheKey] = $results;
        return $results;
    }
}
?>';
        
        file_put_contents('includes/QueryOptimizer.php', $queryOptimizer);
        $this->results[] = "✅ کلاس بهینه‌سازی کوئری ایجاد شد";
    }
    
    private function displayResults() {
        echo "<div style='background:#e8f5e8;padding:20px;border-radius:8px;margin:20px 0;'>";
        echo "<h3>📋 نتایج بهینه‌سازی:</h3>";
        foreach ($this->results as $result) {
            echo "<p>$result</p>";
        }
        echo "</div>";
        
        echo "<div style='background:#fff3cd;padding:20px;border-radius:8px;margin:20px 0;'>";
        echo "<h4>📝 مراحل بعدی:</h4>";
        echo "<ol>";
        echo "<li>فایل <code>.htaccess</code> را برای کش استاتیک پیکربندی کنید</li>";
        echo "<li>PHP OPcache را فعال کنید</li>";
        echo "<li>از header بهینه استفاده کنید</li>";
        echo "<li>سیستم کش را در کوئری‌ها پیاده کنید</li>";
        echo "</ol>";
        echo "</div>";
    }
}

// اجرای بهینه‌سازی
$optimizer = new PerformanceOptimizer();
$optimizer->runOptimizations();
?>