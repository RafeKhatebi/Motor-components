<?php
/**
 * تست سریع سیستم بکاپ
 */
require_once 'init_security.php';
require_once 'config/database.php';

// فقط ادمین
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    die('دسترسی غیرمجاز');
}

echo "<h2>🧪 تست سیستم بکاپ</h2>";

try {
    // بررسی فولدر backups
    if (!is_dir('backups')) {
        mkdir('backups', 0755, true);
        echo "<p>✅ فولدر backups ایجاد شد</p>";
    } else {
        echo "<p>✅ فولدر backups موجود است</p>";
    }
    
    // بررسی مجوز نوشتن
    if (is_writable('backups')) {
        echo "<p>✅ فولدر backups قابل نوشتن است</p>";
    } else {
        echo "<p>❌ فولدر backups قابل نوشتن نیست</p>";
        die();
    }
    
    // تست اتصال دیتابیس
    $database = new Database();
    $db = $database->getConnection();
    echo "<p>✅ اتصال دیتابیس موفق</p>";
    
    // تست ایجاد بکاپ
    $backup_file = 'test_backup_' . date('Y-m-d_H-i-s') . '.sql';
    $backup_path = 'backups/' . $backup_file;
    
    $tables = ['users', 'categories', 'products', 'customers'];
    $backup_content = "-- Test Backup created on " . date('Y-m-d H:i:s') . "\n\n";
    
    foreach ($tables as $table) {
        try {
            // بررسی وجود جدول
            $checkTable = $db->prepare("SHOW TABLES LIKE ?");
            $checkTable->execute([$table]);
            
            if ($checkTable->rowCount() == 0) {
                echo "<p>⚠️ جدول $table وجود ندارد</p>";
                continue;
            }
            
            $query = "SELECT * FROM $table LIMIT 5";
            $stmt = $db->prepare($query);
            $stmt->execute();
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $backup_content .= "-- Table: $table (" . count($rows) . " records)\n";
            
            foreach ($rows as $row) {
                $columns = array_keys($row);
                $values = array_map(function ($value) use ($db) {
                    return $value === null ? 'NULL' : $db->quote($value);
                }, array_values($row));
                
                $backup_content .= "INSERT INTO $table (" . implode(', ', $columns) . ") VALUES (" . implode(', ', $values) . ");\n";
            }
            $backup_content .= "\n";
            
            echo "<p>✅ جدول $table - " . count($rows) . " رکورد</p>";
            
        } catch (Exception $e) {
            echo "<p>⚠️ جدول $table - " . $e->getMessage() . "</p>";
            // ادامه به جدول بعدی
            continue;
        }
    }
    
    // نوشتن فایل
    $writeResult = file_put_contents($backup_path, $backup_content);
    if ($writeResult !== false) {
        echo "<p>✅ فایل بکاپ ایجاد شد: $backup_file</p>";
        echo "<p>📁 مسیر: $backup_path</p>";
        echo "<p>📊 حجم: " . number_format(filesize($backup_path) / 1024, 2) . " KB</p>";
        
        // لینک دانلود
        echo "<p><a href='backups/$backup_file' target='_blank' style='background:#28a745;color:white;padding:10px;text-decoration:none;border-radius:5px;'>📥 دانلود فایل تست</a></p>";
        
    } else {
        echo "<p>❌ خطا در ایجاد فایل بکاپ</p>";
    }
    
    echo "<div style='background:#d4edda;color:#155724;padding:15px;border-radius:5px;margin:20px 0;'>";
    echo "<h4>🎉 تست بکاپ موفق بود!</h4>";
    echo "<p>حالا میتوانید از صفحه backup.php استفاده کنید.</p>";
    echo "</div>";
    
} catch (Exception $e) {
    echo "<div style='background:#f8d7da;color:#721c24;padding:15px;border-radius:5px;margin:20px 0;'>";
    echo "<h4>❌ خطا در تست بکاپ</h4>";
    echo "<p>" . $e->getMessage() . "</p>";
    echo "</div>";
}

echo "<p><a href='backup.php' style='background:#007bff;color:white;padding:10px;text-decoration:none;border-radius:5px;'>🔙 بازگشت به صفحه بکاپ</a></p>";
?>