<?php
require_once 'init_security.php';
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: dashboard.php');
    exit();
}

require_once 'config/database.php';
require_once 'includes/functions.php';
require_once 'includes/SettingsHelper.php';
$page_title = 'بک آپگیری';

$database = new Database();
$db = $database->getConnection();
SettingsHelper::loadSettings($db);

if ($_POST && isset($_POST['action'])) {
    $database = new Database();
    $db = $database->getConnection();

    if ($_POST['action'] === 'backup') {
        $backup_file = 'backup_' . date('Y-m-d_H-i-s') . '.sql';
        $backup_path = 'backups/' . $backup_file;

        if (!is_dir('backups')) {
            mkdir('backups', 0755, true);
        }

        $tables = ['users', 'categories', 'products', 'customers', 'suppliers', 'sales', 'sale_items', 'purchases', 'purchase_items'];
        $backup_content = "-- Backup created on " . date('Y-m-d H:i:s') . "\n\n";

        foreach ($tables as $table) {
            try {
                // بررسی وجود جدول
                $checkTable = $db->prepare("SHOW TABLES LIKE ?");
                $checkTable->execute([$table]);

                if ($checkTable->rowCount() == 0) {
                    continue; // جدول وجود ندارد
                }

                $query = "SELECT * FROM $table";
                $stmt = $db->prepare($query);
                $stmt->execute();
                $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

                if (!empty($rows)) {
                    $backup_content .= "-- Table: $table\n";
                    $backup_content .= "DELETE FROM $table;\n";

                    foreach ($rows as $row) {
                        $columns = array_keys($row);
                        $values = array_map(function ($value) use ($db) {
                            return $value === null ? 'NULL' : $db->quote($value);
                        }, array_values($row));

                        $backup_content .= "INSERT INTO $table (" . implode(', ', $columns) . ") VALUES (" . implode(', ', $values) . ");\n";
                    }
                    $backup_content .= "\n";
                }
            } catch (Exception $e) {
                // لاگ خطا و ادامه
                error_log("Backup error for table $table: " . $e->getMessage());
                continue;
            }
        }

        if (file_put_contents($backup_path, $backup_content) !== false) {
            $success_message = "بک آپ گیری با موفقیت انجام شد: $backup_file";
        } else {
            $error_message = "خطا در ایجاد فایل بک آپ";
        }
    }
}

$backup_files = [];
if (is_dir('backups')) {
    $backup_files = array_diff(scandir('backups'), ['.', '..']);
    rsort($backup_files);
}

include 'includes/header.php';
?>
<!-- Page content -->
<div class="container-fluid mt--7">
    <?php if (isset($success_message)): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fas fa-check-circle ml-2"></i>
            <?= $success_message ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <?php if (isset($error_message)): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="fas fa-exclamation-triangle ml-2"></i>
            <?= $error_message ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <div class="row">
        <div class="col-12">
            <div class="card shadow mb-4">
                <div class="card-header border-0">
                    <h3 class="mb-0">ایجاد بک آپ جدید</h3>
                </div>
                <div class="card-body">
                    <form method="POST">
                        <input type="hidden" name="action" value="backup">
                        <button type="submit" class="btn btn-success">
                            <i class="fas fa-download ml-2"></i>
                            ایجاد بک آپ
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <div class="card shadow">
                <div class="card-header border-0">
                    <div class="row align-items-center">
                        <div class="col">
                            <h3 class="mb-0">فایلهای بک آپ</h3>
                        </div>
                        <div class="col text-left">
                            <input type="text" class="form-control form-control-sm" placeholder="جستجو..."
                                id="searchInput" style="width: 200px; display: inline-block;">
                        </div>
                    </div>
                </div>
                <div class="table-responsive">
                    <table class="table align-items-center table-flush" id="backupTable">
                        <thead class="thead-light">
                            <tr>
                                <th scope="col">نام فایل</th>
                                <th scope="col">تاریخ ایجاد</th>
                                <th scope="col">حجم</th>
                                <th scope="col">عملیات</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($backup_files as $file): ?>
                                <tr>
                                    <td>
                                        <i class="fas fa-file-archive text-primary ml-2"></i>
                                        <?= sanitizeOutput($file) ?>
                                    </td>
                                    <td><?= SettingsHelper::formatDateTime(filemtime('backups/' . $file), $db) ?></td>
                                    <td><?= number_format(filesize('backups/' . $file) / 1024, 2) ?> KB</td>
                                    <td>
                                        <a href="download_backup.php?file=<?= urlencode($file) ?>"
                                            class="btn btn-primary btn-sm">
                                            <i class="fas fa-download"></i> دانلود
                                        </a>
                                        <a href="export_excel.php?type=backup&file=<?= urlencode($file) ?>"
                                            class="btn btn-success btn-sm">
                                            <i class="fas fa-file-excel"></i> اکسل
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    // Search functionality
    document.getElementById('searchInput').addEventListener('keyup', function () {
        const filter = this.value.toLowerCase();
        const rows = document.querySelectorAll('#backupTable tbody tr');

        rows.forEach(row => {
            const text = row.textContent.toLowerCase();
            row.style.display = text.includes(filter) ? '' : 'none';
        });
    });
</script>

<?php include 'includes/footer-modern.php'; ?>