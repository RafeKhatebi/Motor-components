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

// Pagination
$items_per_page = 30;
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$offset = ($page - 1) * $items_per_page;

// Count total warranties
$count_query = "SELECT COUNT(*) as total FROM warranties";
$count_stmt = $db->prepare($count_query);
$count_stmt->execute();
$total_items = $count_stmt->fetch(PDO::FETCH_ASSOC)['total'];
$total_pages = ceil($total_items / $items_per_page);

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
                     ORDER BY w.created_at DESC LIMIT :limit OFFSET :offset";
$warranties_stmt = $db->prepare($warranties_query);
$warranties_stmt->bindValue(':limit', $items_per_page, PDO::PARAM_INT);
$warranties_stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
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
                                <button class="btn btn-info btn-sm" onclick="viewWarranty(<?= $warranty['id'] ?>)" title="مشاهده جزئیات">
                                    <i class="fas fa-eye"></i>
                                </button>
                                <?php if ($warranty['warranty_status'] === 'active'): ?>
                                <button class="btn btn-warning btn-sm" onclick="claimWarranty(<?= $warranty['id'] ?>)" title="درخواست گارانتی">
                                    <i class="fas fa-exclamation-triangle"></i>
                                </button>
                                <?php endif; ?>
                                <button class="btn btn-success btn-sm" onclick="printWarranty(<?= $warranty['id'] ?>)" title="چاپ گارانتی">
                                    <i class="fas fa-print"></i>
                                </button>
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
<div id="claimWarrantyModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 9999; justify-content: center; align-items: center;">
    <div style="background: white; padding: 0; border-radius: 10px; max-width: 500px; margin: 20px; max-height: 90vh; overflow-y: auto;">
        <div style="padding: 20px; border-bottom: 1px solid #e5e7eb; display: flex; justify-content: space-between; align-items: center;">
            <h5 style="margin: 0; color: #1f2937;">درخواست گارانتی</h5>
            <button onclick="closeClaimModal()" style="background: none; border: none; font-size: 24px; cursor: pointer; color: #6b7280;">&times;</button>
        </div>
        <div style="padding: 20px;">
            <form id="claimWarrantyForm">
                <input type="hidden" id="claimWarrantyId" name="warranty_id">
                <div style="margin-bottom: 15px;">
                    <label style="display: block; margin-bottom: 5px; font-weight: 500;">نوع درخواست</label>
                    <select name="claim_type" style="width: 100%; padding: 8px; border: 1px solid #d1d5db; border-radius: 5px;" required>
                        <option value="repair">تعمیر</option>
                        <option value="replace">تعویض</option>
                        <option value="refund">بازپرداخت</option>
                    </select>
                </div>
                <div style="margin-bottom: 15px;">
                    <label style="display: block; margin-bottom: 5px; font-weight: 500;">شرح مشکل</label>
                    <textarea name="issue_description" style="width: 100%; padding: 8px; border: 1px solid #d1d5db; border-radius: 5px; resize: vertical;" rows="4" required></textarea>
                </div>
            </form>
        </div>
        <div style="padding: 20px; border-top: 1px solid #e5e7eb; display: flex; gap: 10px; justify-content: flex-end;">
            <button onclick="closeClaimModal()" style="background: #6b7280; color: white; border: none; padding: 8px 16px; border-radius: 5px; cursor: pointer;">انصراف</button>
            <button onclick="submitWarrantyClaim()" style="background: #2563eb; color: white; border: none; padding: 8px 16px; border-radius: 5px; cursor: pointer;">ثبت درخواست</button>
        </div>
    </div>
</div>

<!-- مودال مشاهده جزئیات گارانتی -->
<div id="viewWarrantyModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 9999; justify-content: center; align-items: center;">
    <div style="background: white; padding: 0; border-radius: 10px; max-width: 700px; margin: 20px; max-height: 90vh; overflow-y: auto;">
        <div style="padding: 20px; border-bottom: 1px solid #e5e7eb; display: flex; justify-content: space-between; align-items: center;">
            <h5 style="margin: 0; color: #1f2937;">جزئیات گارانتی</h5>
            <button onclick="closeViewModal()" style="background: none; border: none; font-size: 24px; cursor: pointer; color: #6b7280;">&times;</button>
        </div>
        <div id="warrantyDetails" style="padding: 20px;">
            <!-- جزئیات گارانتی اینجا نمایش داده میشود -->
        </div>
        <div style="padding: 20px; border-top: 1px solid #e5e7eb; text-align: center;">
            <button onclick="closeViewModal()" style="background: #6b7280; color: white; border: none; padding: 8px 16px; border-radius: 5px; cursor: pointer;">بستن</button>
        </div>
        
        <!-- Pagination -->
        <?php if ($total_pages > 1): ?>
            <div class="card-footer py-4">
                <nav aria-label="صفحهبندی">
                    <ul class="pagination justify-content-center mb-0">
                        <?php if ($page > 1): ?>
                            <li class="page-item">
                                <a class="page-link" href="?page=<?= $page - 1 ?>">
                                    <i class="fas fa-angle-right"></i>
                                </a>
                            </li>
                        <?php else: ?>
                            <li class="page-item disabled">
                                <span class="page-link"><i class="fas fa-angle-right"></i></span>
                            </li>
                        <?php endif; ?>

                        <?php
                        $start = max(1, $page - 2);
                        $end = min($total_pages, $page + 2);

                        for ($i = $start; $i <= $end; $i++): ?>
                            <li class="page-item <?= $i == $page ? 'active' : '' ?>">
                                <a class="page-link" href="?page=<?= $i ?>"><?= $i ?></a>
                            </li>
                        <?php endfor; ?>

                        <?php if ($page < $total_pages): ?>
                            <li class="page-item">
                                <a class="page-link" href="?page=<?= $page + 1 ?>">
                                    <i class="fas fa-angle-left"></i>
                                </a>
                            </li>
                        <?php else: ?>
                            <li class="page-item disabled">
                                <span class="page-link"><i class="fas fa-angle-left"></i></span>
                            </li>
                        <?php endif; ?>
                    </ul>

                    <div class="text-center mt-3">
                        <small class="text-muted">
                            نمایش <?= $offset + 1 ?> تا <?= min($offset + $items_per_page, $total_items) ?> از
                            <?= $total_items ?> گارانتی
                        </small>
                    </div>
                </nav>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
function claimWarranty(warrantyId) {
    document.getElementById('claimWarrantyId').value = warrantyId;
    document.getElementById('claimWarrantyModal').style.display = 'flex';
}

function closeClaimModal() {
    document.getElementById('claimWarrantyModal').style.display = 'none';
}

function closeViewModal() {
    document.getElementById('viewWarrantyModal').style.display = 'none';
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
            closeClaimModal();
            setTimeout(() => location.reload(), 1000);
        } else {
            showAlert(result.message, 'error');
        }
    } catch (error) {
        showAlert('خطا در ارتباط با سرور', 'error');
    }
}

async function viewWarranty(warrantyId) {
    try {
        const response = await fetch(`api/get_warranty.php?id=${warrantyId}`);
        const result = await response.json();
        
        if (result.success) {
            const warranty = result.warranty;
            const detailsHtml = `
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                    <div>
                        <h6 style="color: #374151; margin-bottom: 10px;">اطلاعات محصول</h6>
                        <p><strong>نام محصول:</strong> ${warranty.product_name || 'نامشخص'}</p>
                        <p><strong>مشتری:</strong> ${warranty.customer_name || 'نامشخص'}</p>
                        <p><strong>نوع گارانتی:</strong> ${warranty.warranty_type || 'فروشگاه'}</p>
                    </div>
                    <div>
                        <h6 style="color: #374151; margin-bottom: 10px;">اطلاعات گارانتی</h6>
                        <p><strong>شروع گارانتی:</strong> ${warranty.warranty_start || '-'}</p>
                        <p><strong>پایان گارانتی:</strong> ${warranty.warranty_end || '-'}</p>
                        <p><strong>مدت (ماه):</strong> ${warranty.warranty_months || '0'}</p>
                        <p><strong>وضعیت:</strong> <span style="background: ${warranty.status === 'active' ? '#10b981' : '#f59e0b'}; color: white; padding: 2px 8px; border-radius: 4px; font-size: 12px;">${warranty.status || 'نامشخص'}</span></p>
                    </div>
                </div>
                ${warranty.notes ? `<div style="margin-top: 15px; padding-top: 15px; border-top: 1px solid #e5e7eb;"><h6 style="color: #374151;">یادداشتها</h6><p>${warranty.notes}</p></div>` : ''}
            `;
            document.getElementById('warrantyDetails').innerHTML = detailsHtml;
            document.getElementById('viewWarrantyModal').style.display = 'flex';
        } else {
            showAlert(result.message || 'خطا در دریافت اطلاعات', 'error');
        }
    } catch (error) {
        showAlert('خطا در دریافت اطلاعات', 'error');
    }
}

function printWarranty(warrantyId) {
    window.open(`print_warranty.php?id=${warrantyId}`, '_blank', 'width=800,height=600');
}

function showAlert(message, type) {
    const alertColor = type === 'success' ? '#10b981' : '#ef4444';
    const alertHtml = `<div style="background: ${alertColor}; color: white; padding: 15px; margin-bottom: 20px; border-radius: 5px; position: relative;">
        ${message}
        <button onclick="this.parentElement.remove()" style="position: absolute; top: 10px; left: 10px; background: none; border: none; color: white; font-size: 18px; cursor: pointer;">&times;</button>
    </div>`;
    
    const contentWrapper = document.querySelector('.content-wrapper');
    if (contentWrapper) {
        contentWrapper.insertAdjacentHTML('afterbegin', alertHtml);
    }
    
    setTimeout(() => {
        const alert = document.querySelector('[style*="background: ' + alertColor + '"]');
        if (alert) alert.remove();
    }, 3000);
}

document.addEventListener('click', function(e) {
    if (e.target.id === 'claimWarrantyModal') {
        closeClaimModal();
    }
    if (e.target.id === 'viewWarrantyModal') {
        closeViewModal();
    }
});
</script>

<?php 
require_once 'includes/footer-modern.php';
?>