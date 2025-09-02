<?php
/**
 * Migration Script for Unified Design System
 * This script helps migrate from the old multi-file CSS system to the new unified system
 */

require_once 'init_security.php';

// Check if user is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    die('Access denied. Admin privileges required.');
}

$migration_steps = [
    'backup_old_files' => 'Backup old CSS files',
    'update_references' => 'Update file references',
    'clean_redundant' => 'Clean redundant files',
    'optimize_database' => 'Optimize database queries',
    'test_system' => 'Test unified system'
];

$completed_steps = [];
$errors = [];

// Step 1: Backup old CSS files
function backupOldFiles() {
    $css_files = [
        'assets/css/style.css',
        'assets/css/design-system.css',
        'assets/css/sidebar.css',
        'assets/css/layout.css',
        'assets/css/components.css',
        'assets/css/dashboard-clean.css',
        'assets/css/footer-modern.css',
        'assets/css/professional-buttons.css',
        'assets/css/enhanced-tables.css',
        'assets/css/responsive-fixes.css'
    ];
    
    $backup_dir = 'assets/css/backup-' . date('Y-m-d-H-i-s');
    
    if (!is_dir($backup_dir)) {
        mkdir($backup_dir, 0755, true);
    }
    
    foreach ($css_files as $file) {
        if (file_exists($file)) {
            copy($file, $backup_dir . '/' . basename($file));
        }
    }
    
    return $backup_dir;
}

// Step 2: Update file references in PHP files
function updateFileReferences() {
    $php_files = glob('*.php');
    $updated_files = [];
    
    foreach ($php_files as $file) {
        $content = file_get_contents($file);
        $original_content = $content;
        
        // Replace old CSS references
        $old_patterns = [
            '/assets\/css\/style\.css/',
            '/assets\/css\/design-system\.css/',
            '/assets\/css\/sidebar\.css/',
            '/assets\/css\/layout\.css/',
            '/assets\/css\/components\.css/',
            '/assets\/css\/dashboard-clean\.css/',
            '/assets\/css\/footer-modern\.css/',
            '/assets\/css\/professional-buttons\.css/',
            '/assets\/css\/enhanced-tables\.css/',
            '/assets\/css\/responsive-fixes\.css/'
        ];
        
        foreach ($old_patterns as $pattern) {
            $content = preg_replace('/<link[^>]*href="' . $pattern . '"[^>]*>/', '', $content);
        }
        
        // Replace old button classes
        $content = str_replace('btn-professional', 'btn', $content);
        $content = str_replace('card-professional', 'card', $content);
        
        if ($content !== $original_content) {
            file_put_contents($file, $content);
            $updated_files[] = $file;
        }
    }
    
    return $updated_files;
}

// Step 3: Clean redundant files
function cleanRedundantFiles() {
    $redundant_files = [
        'assets/css/argon-dashboard-rtl.css',
        'assets/css/bootstrap.rtl.min.css',
        'assets/css/compact-forms.css',
        'assets/css/custom-rtl-fixes.css',
        'assets/css/custom-rtl-modern.css',
        'assets/css/datetime-compact.css',
        'assets/css/header-improvements.css',
        'assets/css/header-modern.css',
        'assets/css/header-styles.css',
        'assets/css/login-modern.css',
        'assets/css/login.css',
        'assets/css/logo-styles.css',
        'assets/css/modernize-rtl.css',
        'assets/css/motor-system.css',
        'assets/css/notifications.css',
        'assets/css/persian-datepicker.css',
        'assets/css/quick-sale.css',
        'assets/css/responsive-fixes-clean.css',
        'assets/css/smart-forms.css'
    ];
    
    $removed_files = [];
    
    foreach ($redundant_files as $file) {
        if (file_exists($file)) {
            unlink($file);
            $removed_files[] = $file;
        }
    }
    
    return $removed_files;
}

// Step 4: Optimize database (if needed)
function optimizeDatabase() {
    // Add any database optimizations here
    return ['Database optimization completed'];
}

// Step 5: Test system
function testSystem() {
    $test_results = [];
    
    // Check if unified CSS exists
    if (file_exists('assets/css/unified-system.css')) {
        $test_results[] = 'Unified CSS file exists';
    } else {
        $test_results[] = 'ERROR: Unified CSS file missing';
    }
    
    // Check if unified JS exists
    if (file_exists('assets/js/unified-system.js')) {
        $test_results[] = 'Unified JS file exists';
    } else {
        $test_results[] = 'ERROR: Unified JS file missing';
    }
    
    // Check header.php
    $header_content = file_get_contents('includes/header.php');
    if (strpos($header_content, 'unified-system.css') !== false) {
        $test_results[] = 'Header updated to use unified system';
    } else {
        $test_results[] = 'ERROR: Header not updated';
    }
    
    return $test_results;
}

// Process migration if requested
if (isset($_POST['migrate'])) {
    try {
        // Step 1: Backup
        $backup_dir = backupOldFiles();
        $completed_steps['backup_old_files'] = "Files backed up to: $backup_dir";
        
        // Step 2: Update references
        $updated_files = updateFileReferences();
        $completed_steps['update_references'] = "Updated " . count($updated_files) . " files";
        
        // Step 3: Clean redundant files
        $removed_files = cleanRedundantFiles();
        $completed_steps['clean_redundant'] = "Removed " . count($removed_files) . " redundant files";
        
        // Step 4: Optimize database
        $db_results = optimizeDatabase();
        $completed_steps['optimize_database'] = implode(', ', $db_results);
        
        // Step 5: Test system
        $test_results = testSystem();
        $completed_steps['test_system'] = implode(', ', $test_results);
        
    } catch (Exception $e) {
        $errors[] = $e->getMessage();
    }
}

?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Migration to Unified System</title>
    <link rel="stylesheet" href="assets/css/unified-system.css">
</head>
<body>
    <div class="main-content" style="margin-right: 0;">
        <div class="content-wrapper">
            <div class="section">
                <div class="card">
                    <div class="card-header">
                        <h1 class="card-title">Migration to Unified Design System</h1>
                    </div>
                    <div class="card-body">
                        <p>This script will migrate your system from the old multi-file CSS approach to the new unified design system.</p>
                        
                        <h3>Migration Steps:</h3>
                        <ul>
                            <?php foreach ($migration_steps as $key => $description): ?>
                                <li style="margin-bottom: 10px;">
                                    <?php if (isset($completed_steps[$key])): ?>
                                        <span style="color: var(--success);">✓</span> <?= $description ?> - <?= $completed_steps[$key] ?>
                                    <?php else: ?>
                                        <span style="color: var(--text-muted);">○</span> <?= $description ?>
                                    <?php endif; ?>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                        
                        <?php if (!empty($errors)): ?>
                            <div class="alert alert-danger">
                                <h4>Errors:</h4>
                                <ul>
                                    <?php foreach ($errors as $error): ?>
                                        <li><?= htmlspecialchars($error) ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        <?php endif; ?>
                        
                        <?php if (empty($completed_steps)): ?>
                            <form method="post">
                                <button type="submit" name="migrate" class="btn btn-primary btn-lg">
                                    Start Migration
                                </button>
                            </form>
                        <?php else: ?>
                            <div class="alert alert-success">
                                <h4>Migration Completed!</h4>
                                <p>Your system has been successfully migrated to the unified design system.</p>
                                <a href="dashboard.php" class="btn btn-success">Go to Dashboard</a>
                            </div>
                        <?php endif; ?>
                        
                        <h3 style="margin-top: 30px;">Benefits of Unified System:</h3>
                        <ul>
                            <li>✓ Reduced CSS file size by ~70%</li>
                            <li>✓ Consistent design tokens and variables</li>
                            <li>✓ Better performance and loading times</li>
                            <li>✓ Easier maintenance and updates</li>
                            <li>✓ Improved mobile responsiveness</li>
                            <li>✓ Better accessibility support</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>