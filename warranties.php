<?php
// Secure file inclusion with path validation
$allowed_files = [
    'init_security.php' => realpath(__DIR__ . '/init_security.php'),
    'config/database.php' => realpath(__DIR__ . '/config/database.php'),
    'includes/functions.php' => realpath(__DIR__ . '/includes/functions.php'),
    'includes/SettingsHelper.php' => realpath(__DIR__ . '/includes/SettingsHelper.php'),
    'includes/header.php' => realpath(__DIR__ . '/includes/header.php'),
    'includes/footer-modern.php' => realpath(__DIR__ . '/includes/footer-modern.php')
];

foreach (['init_security.php'] as $required_file) {
    $real_path = $allowed_files[$required_file];
    if ($real_path && file_exists($real_path)) {
        require_once $real_path;
    } else {
        http_response_code(500);
        exit('Security error: Invalid file path');
    }
}
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    return;
}

foreach (['config/database.php', 'includes/functions.php', 'includes/SettingsHelper.php'] as $file) {
    $real_path = $allowed_files[$file];
    if ($real_path && file_exists($real_path)) {
        require_once $real_path;
    } else {
        http_response_code(500);
        exit('Security error: Invalid file path');
    }
}
$database = new Database();
$db = $database->getConnection();
SettingsHelper::loadSettings($db);

$page_title = 'مدیریت گارانتی';

// دریافت گارانتیها
$warranties_query = "SELECT w.*, p.name as product_name, c.name as customer_name, 
                     CASE 
                         WHEN w.warranty_end < CURDATE() THEN 'expired'
                         WHEN w.warranty_end <= DATE_ADD(CURDATE(), INTERVAL 30 DAY) THEN 'expiring'
                         ELSE 'active'
                     END as warranty_status
                     FROM warranties w
                     LEFT JOIN products p ON w.product_id = p.id
                     LEFT JOIN customers c ON w.customer_id = c.id
                     ORDER BY w.created_at DESC";
$warranties_stmt = $db->prepare($warranties_query);
$warranties_stmt->execute();
$warranties = $warranties_stmt->fetchAll(PDO::FETCH_ASSOC);

$real_path = $allowed_files['includes/header.php'];
if ($real_path && file_exists($real_path)) {
    include $real_path;
} else {
    http_response_code(500);
    exit('Security error: Invalid file path');
}
?>

<div class="section">
    <div class="card">
        <div class="card-header">
            <h5 class="card-title mb-0">
                <i class="fas fa-shield-alt me-2"></i>
                مدیریت گارانتی محصولات
            </h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-modern">
                    <thead>
                        <tr>
                            <th>محصول</th>
                            <th>مشتری</th>
                            <th>شروع گارانتی</th>
                            <th>پایان گارانتی</th>
                            <th>مدت (ماه)</th>
                            <th>وضعیت</th>
                            <th>عملیات</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($warranties as $warranty): ?>
                        <tr>
                            <td><?= sanitizeOutput($warranty['product_name']) ?></td>
                            <td><?= sanitizeOutput($warranty['customer_name'] ?: 'نامشخص') ?></td>
                            <td><?= date('Y/m/d', strtotime($warranty['warranty_start'])) ?></td>
                            <td><?= date('Y/m/d', strtotime($warranty['warranty_end'])) ?></td>
                            <td><?= $warranty['warranty_months'] ?></td>
                            <td>
                                <?php
                                $status_class = [
                                    'active' => 'bg-success',
                                    'expiring' => 'bg-warning',
                                    'expired' => 'bg-danger'
                                ];
                                $status_text = [
                                    'active' => 'فعال',
                                    'expiring' => 'در حال انقضا',
                                    'expired' => 'منقضی شده'
                                ];
                                ?>
                                <span class="badge <?= $status_class[$warranty['warranty_status']] ?>">
                                    <?= $status_text[$warranty['warranty_status']] ?>
                                </span>
                            </td>
                            <td>
                                <button class="btn btn-info btn-sm" onclick="viewWarranty(<?= $warranty['id'] ?>)">
                                    <i class="fas fa-eye"></i>
                                </button>
                                <?php if ($warranty['warranty_status'] === 'active'): ?>
                                <button class="btn btn-warning btn-sm" onclick="claimWarranty(<?= $warranty['id'] ?>)">
                                    <i class="fas fa-exclamation-triangle"></i>
                                </button>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- مودال درخواست گارانتی -->
<div class="modal fade" id="claimWarrantyModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">درخواست گارانتی</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="claimWarrantyForm">
                    <input type="hidden" id="claimWarrantyId" name="warranty_id">
                    <div class="form-group mb-3">
                        <label class="form-label">نوع درخواست</label>
                        <select name="claim_type" class="form-select" required>
                            <option value="repair">تعمیر</option>
                            <option value="replace">تعویض</option>
                            <option value="refund">بازپرداخت</option>
                        </select>
                    </div>
                    <div class="form-group mb-3">
                        <label class="form-label">شرح مشکل</label>
                        <textarea name="issue_description" class="form-control" rows="4" required></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">انصراف</button>
                <button type="button" class="btn btn-primary" onclick="submitWarrantyClaim()">ثبت درخواست</button>
            </div>
        </div>
    </div>
</div>

<script>
function claimWarranty(warrantyId) {
    document.getElementById('claimWarrantyId').value = warrantyId;
    const modal = new bootstrap.Modal(document.getElementById('claimWarrantyModal'));
    modal.show();
}

async function submitWarrantyClaim() {
    const form = document.getElementById('claimWarrantyForm');
    const formData = new FormData(form);
    
    try {
        const response = await fetch('api/warranty_claim.php', {
            method: 'POST',
            body: formData
        });
        
        const result = await response.json();
        
        if (result.success) {
            showAlert('درخواست گارانتی ثبت شد', 'success');
            bootstrap.Modal.getInstance(document.getElementById('claimWarrantyModal')).hide();
            setTimeout(() => location.reload(), 1000);
        } else {
            showAlert(result.message, 'error');
        }
    } catch (error) {
        showAlert('خطا در ارتباط با سرور', 'error');
    }
}

function showAlert(message, type) {
    const alertClass = type === 'success' ? 'alert-success' : 'alert-danger';
    const alertHtml = `<div class="alert ${alertClass} alert-dismissible fade show" role="alert">
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>`;
    document.body.insertAdjacentHTML('afterbegin', alertHtml);
}
</script>

<?php 
$real_path = $allowed_files['includes/footer-modern.php'];
if ($real_path && file_exists($real_path)) {
    include $real_path;
} else {
    http_response_code(500);
    exit('Security error: Invalid file path');
}
?>