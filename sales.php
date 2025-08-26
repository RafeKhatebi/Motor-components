<?php
require_once 'init_security.php';
if (!isset($_SESSION['user_id'])) {
    try {
        header('Location: login.php');
        exit();
    } catch (Exception $e) {
        error_log('Redirect error in sales.php: ' . $e->getMessage());
        throw new Exception('Authentication required');
    }
}

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/SettingsHelper.php';
require_once __DIR__ . '/includes/DateManager.php';
$database = new Database();
$db = $database->getConnection();
SettingsHelper::loadSettings($db);

$page_title = 'مدیریت فروش';

// محاسبه آمار یکجا برای بهبود عملکرد
$stats_query = "SELECT 
    COALESCE(SUM(CASE WHEN DATE(created_at) = CURDATE() THEN final_amount END), 0) as today_sales,
    COUNT(CASE WHEN DATE(created_at) = CURDATE() THEN 1 END) as today_count,
    COALESCE(AVG(CASE WHEN DATE(created_at) = CURDATE() THEN final_amount END), 0) as today_avg,
    COALESCE(SUM(CASE WHEN MONTH(created_at) = MONTH(CURDATE()) AND YEAR(created_at) = YEAR(CURDATE()) THEN final_amount END), 0) as month_total
    FROM sales WHERE (status IS NULL OR status != 'returned')";
$stats_stmt = $db->prepare($stats_query);
$stats_stmt->execute();
$stats = $stats_stmt->fetch(PDO::FETCH_ASSOC);

$customers_query = "SELECT * FROM customers ORDER BY name";
$customers_stmt = $db->prepare($customers_query);
$customers_stmt->execute();
$customers = $customers_stmt->fetchAll(PDO::FETCH_ASSOC);

$products_query = "SELECT * FROM products WHERE stock_quantity > 0 ORDER BY name";
$products_stmt = $db->prepare($products_query);
$products_stmt->execute();
$products = $products_stmt->fetchAll(PDO::FETCH_ASSOC);

// Pagination
$items_per_page = 30;
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$offset = ($page - 1) * $items_per_page;

// Count total sales
$count_query = "SELECT COUNT(*) as total FROM sales WHERE (status IS NULL OR status != 'returned')";
$count_stmt = $db->prepare($count_query);
$count_stmt->execute();
$total_items = $count_stmt->fetch(PDO::FETCH_ASSOC)['total'];
$total_pages = ceil($total_items / $items_per_page);

$sales_query = "SELECT s.*, c.name as customer_name, 
                COALESCE(s.status, 'completed') as status,
                COALESCE(s.payment_type, 'cash') as payment_type,
                COALESCE(s.paid_amount, s.final_amount) as paid_amount,
                COALESCE(s.remaining_amount, 0) as remaining_amount,
                COALESCE(s.payment_status, 'paid') as payment_status
                FROM sales s 
                LEFT JOIN customers c ON s.customer_id = c.id 
                ORDER BY s.created_at DESC LIMIT :limit OFFSET :offset";
$sales_stmt = $db->prepare($sales_query);
$sales_stmt->bindValue(':limit', $items_per_page, PDO::PARAM_INT);
$sales_stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$sales_stmt->execute();
$sales = $sales_stmt->fetchAll(PDO::FETCH_ASSOC);

$extra_css = '
<link rel="stylesheet" href="assets/css/quick-sale.css">
<link rel="stylesheet" href="assets/css/enhanced-tables.css">
<style>
/* بهبود جدول فروش */
#salesTable {
    font-size: 0.9rem;
}

#salesTable th {
    font-weight: 600;
    font-size: 0.85rem;
    padding: 12px 8px;
    vertical-align: middle;
    border-bottom: 2px solid #dee2e6;
}

#salesTable td {
    padding: 12px 8px;
    vertical-align: middle;
    border-bottom: 1px solid #f1f3f4;
}

#salesTable tbody tr:hover {
    background-color: #f8f9fa;
    transform: scale(1.01);
    transition: all 0.2s ease;
}

.btn-group-vertical .btn {
    font-size: 0.75rem;
    padding: 4px 8px;
    border-radius: 4px;
}

.text-truncate {
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

/* رسپانسیو */
@media (max-width: 1200px) {
    #salesTable {
        font-size: 0.8rem;
    }
    
    #salesTable th,
    #salesTable td {
        padding: 8px 4px;
    }
    
    .btn-group-vertical .btn {
        font-size: 0.7rem;
        padding: 2px 6px;
    }
}

@media (max-width: 768px) {
    #salesTable th:nth-child(4),
    #salesTable td:nth-child(4),
    #salesTable th:nth-child(5),
    #salesTable td:nth-child(5) {
        display: none;
    }
    
    .btn-group-vertical {
        flex-direction: row;
    }
    
    .btn-group-vertical .btn {
        margin: 0 2px;
        font-size: 0.6rem;
    }
}
.table-summary {
    background: linear-gradient(135deg, #f8fafc, #e2e8f0);
    border-top: 2px solid #1f2937;
    font-weight: 600;
    color: #1f2937;
}

.table-summary th {
    padding: 16px 12px;
    font-size: 0.95rem;
    border-top: 2px solid #1f2937;
}

/* Pagination Styles */
.pagination {
    gap: 4px;
}

.page-link {
    border-radius: 8px;
    border: 1px solid #e5e7eb;
    color: #374151;
    padding: 8px 12px;
    font-weight: 500;
    transition: all 0.2s ease;
}

.page-link:hover {
    background: #1f2937;
    border-color: #1f2937;
    color: white;
    transform: translateY(-1px);
}

.page-item.active .page-link {
    background: #1f2937;
    border-color: #1f2937;
    color: white;
    box-shadow: 0 4px 12px rgba(31, 41, 55, 0.3);
}

.page-item.disabled .page-link {
    color: #9ca3af;
    background: #f9fafb;
    border-color: #e5e7eb;
}

/* Button group styles for better desktop visibility */
.btn-group .btn {
    margin-right: 2px;
    min-width: 35px;
    padding: 6px 8px;
}

.btn-group .btn:last-child {
    margin-right: 0;
}

.btn-group .btn i {
    font-size: 14px;
}

/* Tooltip styles */
.btn[title]:hover::after {
    content: attr(title);
    position: absolute;
    bottom: 100%;
    left: 50%;
    transform: translateX(-50%);
    background: #333;
    color: white;
    padding: 4px 8px;
    border-radius: 4px;
    font-size: 12px;
    white-space: nowrap;
    z-index: 1000;
    margin-bottom: 5px;
}

.btn[title] {
    position: relative;
}
</style>
';

try {
    include __DIR__ . '/includes/header.php';
} catch (Exception $e) {
    error_log('Header include error in sales.php: ' . $e->getMessage());
    throw new Exception('Header could not be loaded');
}
?>

<!-- Header -->
<div class="header bg-gradient-primary pb-8 pt-5 pt-md-8">
    <div class="container-fluid">
        <div class="header-body">
            <div class="row align-items-center py-4">
                <div class="col-lg-6 col-7">
                    <h6 class="h2 text-white d-inline-block mb-0"><?= __('sales_management') ?></h6>
                    <nav aria-label="breadcrumb" class="d-none d-md-inline-block ms-md-4">
                        <ol class="breadcrumb breadcrumb-links breadcrumb-dark">
                            <li class="breadcrumb-item"><a href="dashboard.php"><i class="fas fa-home"></i></a></li>
                            <li class="breadcrumb-item"><a href="#">فروش</a></li>
                            <li class="breadcrumb-item active" aria-current="page">لیست فروشات</li>
                        </ol>
                    </nav>
                </div>
                <div class="col-lg-6 col-5 text-left">
                    <a href="#" class="btn btn-professional btn-sm" data-bs-toggle="modal"
                        data-bs-target="#newSaleModal">
                        <i class="fas fa-plus"></i> <?= __('new_invoice') ?>
                    </a>
                    <a href="#" onclick="exportSales()" class="btn btn-professional btn-sm btn-info">
                        <i class="fas fa-download"></i> دانلود
                    </a>
                </div>
            </div>


        </div>
    </div>
</div>

<!-- Page content -->
<div class="container-fluid mt--7">
    <!-- Quick Sale Bar -->
    <div class="quick-sale-bar">
        <div class="row align-items-center">
            <div class="col-md-4">
                <div style="position: relative;">
                    <input type="text" class="form-control" id="quickProductSearch" 
                           placeholder="جستجو محصول (F2)" autocomplete="off">
                    <div class="search-suggestions" id="productSuggestions"></div>
                </div>
            </div>
            <div class="col-md-2">
                <input type="number" class="form-control" id="quickQuantity" 
                       placeholder="تعداد" min="1" value="1">
            </div>
            <div class="col-md-2">
                <div class="price-display" id="quickPrice">0 افغانی</div>
            </div>
            <div class="col-md-2">
                <button class="btn btn-success btn-block" id="quickAddToCart" onclick="quickSale.addToCart()">
                    <i class="fas fa-plus"></i> افزودن
                </button>
            </div>
            <div class="col-md-2">
                <button class="btn btn-primary" id="quickCheckout" onclick="quickSale.quickCheckout()">
                    <i class="fas fa-shopping-cart"></i> تسویه 
                    <span class="shortcut-hint">F9</span>
                </button>
            </div>
        </div>
    </div>
    <!-- Table -->
    <div class="row">
        <div class="col">
            <div class="card card-professional">
                <div class="card-header border-0">
                    <div class="row align-items-center">
                        <div class="col">
                            <h3 class="mb-0">لیست فروشات</h3>
                        </div>
                        <div class="col text-left">
                            <input type="text" class="form-control form-control-sm" placeholder="جستجو..."
                                id="searchInput" style="width: 200px; display: inline-block; margin-left: 10px;">
                        </div>
                    </div>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover enhanced-table table-enhanced" id="salesTable">
                        <thead class="table-dark">
                            <tr>
                                <th scope="col" style="width: 40px;">#</th>
                                <th scope="col" style="width: 70px;">فاکتور</th>
                                <th scope="col" style="width: 180px;">مشتری</th>
                                <th scope="col" style="width: 100px;">مبلغ کل</th>
                                <th scope="col" style="width: 80px;">تخفیف</th>
                                <th scope="col" style="width: 100px;">مبلغ نهایی</th>
                                <th scope="col" style="width: 90px;">پرداخت</th>
                                <th scope="col" style="width: 120px;">وضعیت</th>
                                <th scope="col" style="width: 100px;">تاریخ</th>
                                <th scope="col" style="width: 150px;">عملیات</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($sales as $index => $sale): ?>
                                <tr>
                                    <td class="text-center"><?= $offset + $index + 1 ?></td>
                                    <td class="text-center">
                                        <span class="badge bg-primary">#<?= $sale['id'] ?></span>
                                    </td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <i class="fas fa-user-circle text-muted me-2"></i>
                                            <span style="font-size: 0.9rem; line-height: 1.2;">
                                                <?= sanitizeOutput($sale['customer_name'] ?: 'مشتری نقدی') ?>
                                            </span>
                                        </div>
                                    </td>
                                    <td class="text-end">
                                        <span class="fw-bold"><?= number_format($sale['total_amount']) ?></span>
                                        <small class="text-muted d-block">افغانی</small>
                                    </td>
                                    <td class="text-end">
                                        <?php if ($sale['discount'] > 0): ?>
                                            <span class="text-warning fw-bold"><?= number_format($sale['discount']) ?></span>
                                            <small class="text-muted d-block">افغانی</small>
                                        <?php else: ?>
                                            <span class="text-muted">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-end">
                                        <span class="fw-bold text-success"><?= number_format($sale['final_amount']) ?></span>
                                        <small class="text-muted d-block">افغانی</small>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge <?= ($sale['payment_type'] ?? 'cash') === 'cash' ? 'bg-success' : 'bg-info' ?>">
                                            <?= ($sale['payment_type'] ?? 'cash') === 'cash' ? 'نقدی' : 'قرضی' ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php 
                                        $payment_status = $sale['payment_status'] ?? 'paid';
                                        $badge_class = $payment_status === 'paid' ? 'bg-success' : ($payment_status === 'partial' ? 'bg-warning' : 'bg-danger');
                                        $status_text = $payment_status === 'paid' ? 'پرداخت شده' : ($payment_status === 'partial' ? 'جزئی' : 'بدهکار');
                                        ?>
                                        <span class="badge <?= $badge_class ?> mb-1"><?= $status_text ?></span>
                                        <?php if (($sale['payment_type'] ?? 'cash') === 'credit' && ($sale['remaining_amount'] ?? 0) > 0): ?>
                                            <div class="small text-danger">
                                                <i class="fas fa-exclamation-circle"></i>
                                                <?= number_format($sale['remaining_amount'] ?? 0) ?> افغانی
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center">
                                        <div class="small">
                                            <i class="fas fa-calendar text-muted"></i>
                                            <?= DateManager::formatDateForDisplay($sale['created_at'], false) ?>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="btn-group btn-group-sm" role="group">
                                            <button onclick="printInvoice(<?= $sale['id'] ?>)"
                                                class="btn btn-outline-info btn-sm" title="چاپ فاکتور">
                                                <i class="fas fa-print"></i>
                                            </button>
                                            <!-- <button onclick="viewSale(<?= $sale['id'] ?>)"
                                                class="btn btn-outline-primary btn-sm" title="مشاهده جزئیات">
                                                <i class="fas fa-eye"></i>
                                            </button> -->
                                            <button onclick="editSale(<?= $sale['id'] ?>)"
                                                class="btn btn-outline-warning btn-sm" title="ویرایش فاکتور">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <button onclick="deleteSale(<?= $sale['id'] ?>)"
                                                class="btn btn-outline-secondary btn-sm" title="حذف فاکتور">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                            <?php if (($sale['payment_type'] ?? 'cash') === 'credit' && ($sale['remaining_amount'] ?? 0) > 0): ?>
                                                <button onclick="showPaymentModal('sale', <?= $sale['id'] ?>, <?= $sale['remaining_amount'] ?? 0 ?>)"
                                                    class="btn btn-outline-success btn-sm" title="پرداخت">
                                                    <i class="fas fa-money-bill"></i>
                                                </button>
                                            <?php endif; ?>
                                            <?php if (!isset($sale['status']) || $sale['status'] !== 'returned'): ?>
                                                <button onclick="returnSale(<?= $sale['id'] ?>)"
                                                    class="btn btn-outline-danger btn-sm" title="برگشت فاکتور">
                                                    <i class="fas fa-undo"></i>
                                                </button>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                        <tfoot>
                            <tr class="table-dark">
                                <th colspan="3" class="text-end fw-bold">جمع کل:</th>
                                <th class="text-end" id="totalSalesAmount">0 افغانی</th>
                                <th class="text-end" id="totalDiscount">0 افغانی</th>
                                <th class="text-end" id="totalFinalAmount">0 افغانی</th>
                                <th colspan="4"></th>
                            </tr>
                        </tfoot>
                    </table>
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
                                    <?= $total_items ?> فروش
                                </small>
                            </div>
                        </nav>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Modal برگشت فاکتور -->
    <div class="modal fade" id="returnSaleModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">برگشت فاکتور فروش</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="returnSaleForm">
                        <input type="hidden" id="returnSaleId">
                        <div class="form-group">
                            <label class="form-control-label">دلیل برگشت</label>
                            <textarea id="returnReason" class="form-control" rows="3"
                                placeholder="دلیل برگشت را بنویسید..." required></textarea>
                        </div>
                        <div class="alert alert-warning">
                            <i class="fas fa-exclamation-triangle"></i>
                            توجه: پس از برگشت، کالاها به انبار بازگردانده و مبلغ از حساب کسر میشود.
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">انصراف</button>
                    <button type="button" class="btn btn-danger" onclick="confirmReturnSale()">تأیید برگشت</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal پرداخت -->
    <div class="modal fade" id="paymentModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">ثبت پرداخت</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="paymentForm">
                        <input type="hidden" id="paymentType">
                        <input type="hidden" id="paymentId">
                        <div class="form-group">
                            <label>مبلغ پرداخت</label>
                            <input type="number" id="paymentAmount" class="form-control" min="1" step="0.01" required>
                            <small class="form-text text-muted">حداکثر: <span id="maxAmount"></span> افغانی</small>
                        </div>
                        <div class="form-group">
                            <label>تاریخ پرداخت</label>
                            <input type="date" id="paymentDate" class="form-control" value="<?= date('Y-m-d') ?>" required>
                        </div>
                        <div class="form-group">
                            <label>روش پرداخت</label>
                            <select id="paymentMethod" class="form-control">
                                <option value="cash">نقدی</option>
                                <option value="bank">بانکی</option>
                                <option value="check">چک</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>یادداشت</label>
                            <textarea id="paymentNotes" class="form-control" rows="3"></textarea>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">انصراف</button>
                    <button type="button" class="btn btn-success" onclick="submitPayment()">ثبت پرداخت</button>
                </div>
            </div>
        </div>
    </div>

    <?php
    try {
        include __DIR__ . '/includes/footer.php';
    } catch (Exception $e) {
        error_log('Footer include error in sales.php: ' . $e->getMessage());
        echo '<!-- Footer could not be loaded -->';
    }
    ?>
    
    <script src="assets/js/quick-sale.js"></script>
    <script src="assets/js/enhanced-tables.js"></script>
    <script src="assets/js/notifications.js"></script>

    <!-- Modal فاکتور جدید -->
    <div class="modal fade modal-professional" id="newSaleModal" tabindex="-1" role="dialog"
        aria-labelledby="newSaleModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-xl modal-responsive" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="newSaleModalLabel">فاکتور فروش جدید</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <form id="newSaleForm" data-validate onsubmit="event.preventDefault(); submitSale();">
                        <?php
                        try {
                            if (!isset($_SESSION['csrf_token'])) {
                                $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
                            }
                        } catch (Exception $e) {
                            error_log('CSRF token generation error in sales.php: ' . $e->getMessage());
                            $_SESSION['csrf_token'] = 'fallback_token_' . time();
                        }
                        ?>
                        <input type="hidden" name="csrf_token" value="<?= sanitizeOutput($_SESSION['csrf_token']) ?>">
                        <div class="row">
                            <div class="col-lg-6">
                                <div class="form-group">
                                    <label class="form-control-label"
                                        for="input-customer"><?= __('customer_label') ?></label>
                                    <div class="input-group">
                                        <select id="input-customer" name="customer_id"
                                            class="form-control form-control-alternative"
                                            data-rules="">
                                            <option value=""><?= __('cash_customer') ?></option>
                                            <?php foreach ($customers as $customer): ?>
                                                <option value="<?= $customer['id'] ?>">
                                                    <?= sanitizeOutput($customer['name']) ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                        <button type="button" class="btn btn-professional btn-sm"
                                            onclick="showNewCustomerForm()">
                                            <i class="fas fa-plus"></i>
                                        </button>
                                    </div>
                                </div>

                                <!-- فرم مشتری جدید -->
                                <div id="newCustomerForm" style="display: none;" class="mt-3 p-3 border rounded">
                                    <h6>مشتری جدید</h6>
                                    <div class="row">
                                        <div class="col-md-6">
                                            <input type="text" id="newCustomerName" class="form-control form-control-sm"
                                                placeholder="نام مشتری" data-rules="required|min:2|persian" required>
                                            <div class="form-hint">
                                                <i class="fas fa-info-circle"></i>
                                                <span>نام کامل مشتری را وارد کنید</span>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <input type="text" id="newCustomerPhone"
                                                class="form-control form-control-sm" placeholder="شماره تلفن"
                                                data-rules="phone">
                                            <div class="form-hint">
                                                <i class="fas fa-phone"></i>
                                                <span>مثال: 09123456789</span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row mt-2">
                                        <div class="col-md-12">
                                            <textarea id="newCustomerAddress" class="form-control form-control-sm"
                                                placeholder="آدرس" rows="2"></textarea>
                                        </div>
                                    </div>
                                    <div class="mt-2">
                                        <button type="button" class="btn btn-success btn-sm"
                                            onclick="addNewCustomer()">ثبت</button>
                                        <button type="button" class="btn btn-secondary btn-sm"
                                            onclick="hideNewCustomerForm()">لغو</button>
                                    </div>
                                </div>
                            </div>
                            <div class="col-lg-6">
                                <div class="form-group">
                                    <label class="form-control-label" for="input-discount">تخفیف (%)</label>
                                    <div class="input-group">
                                        <input type="number" id="discount" name="discount"
                                            class="form-control form-control-alternative" placeholder="0" value="0"
                                            min="0" max="100" step="0.01" onchange="calculateInvoice()"
                                            data-rules="numeric">
                                        <div class="input-group-text">%</div>
                                    </div>
                                    <div class="form-hint">
                                        <i class="fas fa-percentage"></i>
                                        <span>درصد تخفیف (0 تا 100)</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-lg-6">
                                <div class="form-group">
                                    <label class="form-control-label">نوع پرداخت</label>
                                    <select id="payment_type" name="payment_type" class="form-control form-control-alternative" onchange="togglePaymentFields()">
                                        <option value="cash">نقدی</option>
                                        <option value="credit">قرضی</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-lg-6" id="paid_amount_field" style="display: none;">
                                <div class="form-group">
                                    <label class="form-control-label">مبلغ پرداختی</label>
                                    <input type="number" id="paid_amount" name="paid_amount" class="form-control form-control-alternative" 
                                           placeholder="0" value="0" min="0" step="0.01" onchange="calculateInvoice()"
                                           data-rules="numeric">
                                    <small class="form-text text-muted">مبلغی که مشتری پرداخت کرده</small>
                                </div>
                            </div>
                        </div>

                        <hr class="my-4">

                        <h6 class="heading-small text-muted mb-4"><?= __('invoice_items_label') ?></h6>

                        <div class="table-responsive">
                            <table class="table align-items-center table-flush table-mobile-friendly">
                                <thead class="thead-light">
                                    <tr>
                                        <th scope="col"><?= __('product_label') ?></th>
                                        <th scope="col"><?= __('quantity_label') ?></th>
                                        <th scope="col" class="mobile-hidden"><?= __('unit_price_label') ?></th>
                                        <th scope="col"><?= __('sum_label') ?></th>
                                        <th scope="col"><?= __('operations_label') ?></th>
                                    </tr>
                                </thead>
                                <tbody id="invoiceItems">
                                    <tr>
                                        <td>
                                            <select name="products[]" class="form-control form-control-alternative"
                                                onchange="updatePrice(this)">
                                                <option value=""><?= __('select_product') ?></option>
                                                <?php foreach ($products as $product): ?>
                                                    <option value="<?= $product['id'] ?>"
                                                        data-price="<?= $product['sell_price'] ?>"
                                                        data-buy-price="<?= $product['buy_price'] ?>"><?= $product['name'] ?>
                                                        (موجودی: <?= $product['stock_quantity'] ?>)</option>
                                                <?php endforeach; ?>
                                            </select>
                                        </td>
                                        <td>
                                            <input type="number" name="quantities[]"
                                                class="form-control form-control-alternative quantity" min="1" step="1"
                                                placeholder="تعداد" onchange="calculateInvoice()" required>
                                        </td>
                                        <td>
                                            <input type="number" name="prices[]"
                                                class="form-control form-control-alternative price" min="0" step="0.01"
                                                placeholder="قیمت واحد" onchange="validatePrice(this); calculateInvoice()" required>
                                        </td>
                                        <td>
                                            <span class="subtotal">0</span> افغانی
                                        </td>
                                        <td>
                                            <button type="button" onclick="removeRow(this)"
                                                class="btn btn-professional btn-danger btn-sm">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <button type="button" onclick="addRow()" class="btn btn-professional btn-sm mb-4">
                            <i class="fas fa-plus"></i> <?= __('add_row') ?>
                        </button>

                        <div class="row">
                            <div class="col-lg-4 col-md-4">
                                <div class="card bg-gradient-success shadow">
                                    <div class="card-body">
                                        <div class="row">
                                            <div class="col">
                                                <h5 class="card-title text-uppercase text-white mb-0">
                                                    <?= __('total_amount') ?>
                                                </h5>
                                                <span class="h2 font-weight-bold mb-0 text-white"
                                                    id="totalAmount">0</span>
                                            </div>
                                            <div class="col-auto">
                                                <div
                                                    class="icon icon-shape bg-white text-primary rounded-circle shadow">
                                                    <i class="fas fa-calculator"></i>
                                                </div>
                                            </div>
                                        </div>
                                        <p class="mt-3 mb-0 text-white text-sm">
                                            <span class="text-nowrap"><?= __('afghani_currency') ?></span>
                                        </p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-lg-4 col-md-4">
                                <div class="card bg-gradient-warning shadow">
                                    <div class="card-body">
                                        <div class="row">
                                            <div class="col">
                                                <h5 class="card-title text-uppercase text-white mb-0">
                                                    تخفیف</h5>
                                                <span class="h2 font-weight-bold mb-0 text-white"
                                                    id="discountAmount">0</span>
                                            </div>
                                            <div class="col-auto">
                                                <div
                                                    class="icon icon-shape bg-white text-primary rounded-circle shadow">
                                                    <i class="fas fa-percent"></i>
                                                </div>
                                            </div>
                                        </div>
                                        <p class="mt-3 mb-0 text-white text-sm">
                                            <span class="text-nowrap"><?= __('afghani_currency') ?></span>
                                        </p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-lg-4 col-md-4">
                                <div class="card bg-gradient-info shadow">
                                    <div class="card-body">
                                        <div class="row">
                                            <div class="col">
                                                <h5 class="card-title text-uppercase text-white mb-0">
                                                    <?= __('final_amount') ?>
                                                </h5>
                                                <span class="h2 font-weight-bold mb-0 text-white"
                                                    id="finalAmount">0</span>
                                            </div>
                                            <div class="col-auto">
                                                <div
                                                    class="icon icon-shape bg-white text-primary rounded-circle shadow">
                                                    <i class="fas fa-money-bill"></i>
                                                </div>
                                            </div>
                                        </div>
                                        <p class="mt-3 mb-0 text-white text-sm">
                                            <span class="text-nowrap"><?= __('afghani_currency') ?></span>
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-professional btn-secondary"
                        data-bs-dismiss="modal"><?= __('cancel') ?></button>
                    <button type="button" onclick="submitSale()" id="submitSaleBtn"
                        class="btn btn-professional btn-success">
                        <span class="btn-text"><?= __('submit_invoice') ?></span>
                        <span class="spinner-border spinner-border-sm d-none" role="status"></span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script>
        function addRow() {
            const tbody = document.getElementById("invoiceItems");
            const newRow = tbody.rows[0].cloneNode(true);

            // پاک کردن مقادیر
            newRow.querySelectorAll("input").forEach(input => {
                input.value = "";
                input.removeAttribute('readonly');
            });
            newRow.querySelectorAll("select").forEach(select => select.selectedIndex = 0);
            newRow.querySelector(".subtotal").textContent = "0";

            // اضافه کردن event listeners
            const productSelect = newRow.querySelector('select[name="products[]"]');
            const quantityInput = newRow.querySelector('input[name="quantities[]"]');
            const priceInput = newRow.querySelector('input[name="prices[]"]');

            if (productSelect) {
                productSelect.addEventListener('change', function () {
                    updatePrice(this);
                });
            }

            if (quantityInput) {
                quantityInput.addEventListener('change', calculateInvoice);
                quantityInput.addEventListener('input', calculateInvoice);
            }

            if (priceInput) {
                priceInput.addEventListener('change', function() {
                    validatePrice(this);
                    calculateInvoice();
                });
                priceInput.addEventListener('input', function() {
                    validatePrice(this);
                    calculateInvoice();
                });
            }

            tbody.appendChild(newRow);
        }

        function removeRow(btn) {
            const tbody = document.getElementById("invoiceItems");
            if (tbody.rows.length > 1) {
                btn.closest("tr").remove();
                calculateInvoice();
            }
        }

        function updatePrice(select) {
            const price = select.options[select.selectedIndex].dataset.price || 0;
            const buyPrice = select.options[select.selectedIndex].dataset.buyPrice || 0;
            const row = select.closest("tr");
            const priceInput = row.querySelector(".price");
            priceInput.value = price;
            priceInput.dataset.buyPrice = buyPrice;
            calculateInvoice();
        }

        // تابع اعتبارسنجی قیمت
        const minProfitMargin = <?= SettingsHelper::getSetting('min_profit_margin', '5') ?>;
        
        function validatePrice(priceInput) {
            const buyPrice = parseFloat(priceInput.dataset.buyPrice) || 0;
            const sellPrice = parseFloat(priceInput.value) || 0;
            
            if (buyPrice > 0 && sellPrice > 0) {
                const minAllowedPrice = buyPrice * (1 + minProfitMargin / 100);
                
                if (sellPrice < minAllowedPrice) {
                    priceInput.style.borderColor = '#ef4444';
                    priceInput.style.backgroundColor = '#fef2f2';
                    priceInput.title = `حداقل قیمت مجاز: ${Math.ceil(minAllowedPrice)} افغانی`;
                    
                    // جلوگیری از ورود قیمت کمتر
                    priceInput.value = Math.ceil(minAllowedPrice);
                    showAlert(`قیمت نمیتواند کمتر از ${Math.ceil(minAllowedPrice)} افغانی باشد`, 'error');
                    return false;
                } else {
                    priceInput.style.borderColor = '#10b981';
                    priceInput.style.backgroundColor = '#f0fdf4';
                    priceInput.title = '';
                    return true;
                }
            }
            return true;
        }

        function calculateInvoice() {
            const rows = document.querySelectorAll("#invoiceItems tr");
            let total = 0;

            rows.forEach(row => {
                const quantityInput = row.querySelector(".quantity");
                const priceInput = row.querySelector(".price");
                const subtotalElement = row.querySelector(".subtotal");

                if (quantityInput && priceInput && subtotalElement) {
                    const quantity = parseFloat(quantityInput.value) || 0;
                    const price = parseFloat(priceInput.value) || 0;
                    const subtotal = quantity * price;

                    subtotalElement.textContent = subtotal.toLocaleString('fa-IR');
                    total += subtotal;
                }
            });

            const discountInput = document.getElementById("discount");
            const discountPercent = parseFloat(discountInput?.value || 0);
            const discountAmount = (total * discountPercent) / 100;
            const finalAmount = Math.max(0, total - discountAmount);

            const totalAmountElement = document.getElementById("totalAmount");
            const discountAmountElement = document.getElementById("discountAmount");
            const finalAmountElement = document.getElementById("finalAmount");

            if (totalAmountElement) {
                totalAmountElement.textContent = total.toLocaleString('fa-IR');
            }

            if (discountAmountElement) {
                discountAmountElement.textContent = discountAmount.toLocaleString('fa-IR');
            }

            if (finalAmountElement) {
                finalAmountElement.textContent = finalAmount.toLocaleString('fa-IR');
            }
        }

        async function submitSale() {
            // اعتبارسنجی فرم
            if (!validateSaleForm()) {
                return;
            }

            // نمایش loading state
            const submitBtn = document.getElementById('submitSaleBtn');
            const btnText = submitBtn.querySelector('.btn-text');
            const spinner = submitBtn.querySelector('.spinner-border');

            submitBtn.disabled = true;
            btnText.textContent = 'در حال ثبت...';
            spinner.classList.remove('d-none');

            const form = document.getElementById("newSaleForm");
            const formData = new FormData();

            // افزودن CSRF token
            formData.append('csrf_token', form.querySelector('input[name="csrf_token"]').value);

            // افزودن اطلاعات مشتری، تخفیف و پرداخت
            const customerId = form.querySelector('#input-customer').value;
            const discount = form.querySelector('#discount').value || 0;
            const paymentType = form.querySelector('#payment_type').value || 'cash';
            const paidAmount = form.querySelector('#paid_amount').value || 0;

            if (customerId) {
                formData.append('customer_id', customerId);
            }
            formData.append('discount', discount);
            formData.append('payment_type', paymentType);
            formData.append('paid_amount', paidAmount);

            // جمع‌آوری محصولات معتبر
            const rows = document.querySelectorAll('#invoiceItems tr');
            let validItemsCount = 0;

            rows.forEach((row, index) => {
                const productSelect = row.querySelector('select[name="products[]"]');
                const quantityInput = row.querySelector('input[name="quantities[]"]');
                const priceInput = row.querySelector('input[name="prices[]"]');

                if (productSelect && quantityInput && priceInput) {
                    const productId = productSelect.value;
                    const quantity = quantityInput.value;
                    const price = priceInput.value;

                    if (productId && quantity > 0 && price > 0) {
                        formData.append('products[]', productId);
                        formData.append('quantities[]', quantity);
                        formData.append('prices[]', price);
                        validItemsCount++;
                    }
                }
            });

            if (validItemsCount === 0) {
                showAlert('لطفاً حداقل یک محصول معتبر انتخاب کنید', 'error');
                return;
            }

            try {
                const response = await fetch("api/add_sale.php", {
                    method: "POST",
                    body: formData
                });

                const result = await response.json();

                if (result.success) {
                    showAlert("فاکتور با موفقیت ثبت شد", "success");
                    bootstrap.Modal.getInstance(document.getElementById("newSaleModal")).hide();
                    setTimeout(() => location.reload(), 1000);
                } else {
                    showAlert(result.message || "خطا در ثبت فاکتور", "error");
                }
            } catch (error) {
                console.error('Error:', error);
                showAlert("خطا در ارتباط با سرور", "error");
            } finally {
                // برگرداندن حالت عادی دکمه
                submitBtn.disabled = false;
                btnText.textContent = '<?= __('submit_invoice') ?>';
                spinner.classList.add('d-none');
            }
        }

        function validateSaleForm() {
            const rows = document.querySelectorAll('#invoiceItems tr');
            let hasValidItem = false;
            let hasInvalidPrice = false;

            for (let row of rows) {
                const productSelect = row.querySelector('select[name="products[]"]');
                const quantityInput = row.querySelector('input[name="quantities[]"]');
                const priceInput = row.querySelector('input[name="prices[]"]');

                if (productSelect && quantityInput && priceInput) {
                    const productId = productSelect.value;
                    const quantity = parseFloat(quantityInput.value) || 0;
                    const price = parseFloat(priceInput.value) || 0;

                    if (productId && quantity > 0 && price > 0) {
                        hasValidItem = true;
                        
                        // بررسی قیمت مجاز
                        if (!validatePrice(priceInput)) {
                            hasInvalidPrice = true;
                        }
                    }
                }
            }

            if (!hasValidItem) {
                showAlert('لطفاً حداقل یک محصول با مقدار و قیمت معتبر انتخاب کنید', 'error');
                return false;
            }

            if (hasInvalidPrice) {
                showAlert('قیمت فروش نمیتواند کمتر از حداقل سود مجاز باشد', 'error');
                return false;
            }

            return true;
        }

        function showAlert(message, type) {
            const alertClass = type === "success" ? "alert-success" : "alert-danger";
            const alertHtml = `<div class="alert ${alertClass} alert-dismissible fade show" role="alert">
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>`;
            document.body.insertAdjacentHTML("afterbegin", alertHtml);
        }

        function viewSale(id) {
            window.open(`view_sale.php?id=${id}`, "_blank");
        }

        function printInvoice(id) {
            window.open(`print_invoice.php?id=${id}`, "_blank");
        }

        function exportSales() {
            // Validate and sanitize the export request
            const exportUrl = 'export_sales.php';
            if (exportUrl.match(/^[a-zA-Z0-9_\-\.]+\.php$/)) {
                window.open(exportUrl, '_blank');
            } else {
                showAlert('درخواست نامعتبر', 'error');
            }
        }

        // Calculate summary
        function calculateSalesSummary() {
            const rows = document.querySelectorAll('#salesTable tbody tr');
            let totalAmount = 0;
            let totalDiscount = 0;
            let totalFinal = 0;

            rows.forEach(row => {
                if (row.style.display !== 'none') {
                    const amountText = row.cells[2]?.textContent?.replace(/[^0-9]/g, '') || '0';
                    const discountText = row.cells[3]?.textContent?.replace(/[^0-9]/g, '') || '0';
                    const finalText = row.cells[4]?.textContent?.replace(/[^0-9]/g, '') || '0';

                    totalAmount += parseInt(amountText) || 0;
                    totalDiscount += parseInt(discountText) || 0;
                    totalFinal += parseInt(finalText) || 0;
                }
            });

            const totalAmountEl = document.getElementById('totalSalesAmount');
            const totalDiscountEl = document.getElementById('totalDiscount');
            const totalFinalEl = document.getElementById('totalFinalAmount');

            if (totalAmountEl) totalAmountEl.textContent = totalAmount.toLocaleString() + ' افغانی';
            if (totalDiscountEl) totalDiscountEl.textContent = totalDiscount.toLocaleString() + ' افغانی';
            if (totalFinalEl) totalFinalEl.textContent = totalFinal.toLocaleString() + ' افغانی';
        }

        // Search functionality
        document.getElementById('searchInput').addEventListener('keyup', function () {
            const filter = this.value.toLowerCase();
            const rows = document.querySelectorAll('#salesTable tbody tr');

            rows.forEach(row => {
                const text = row.textContent.toLowerCase();
                row.style.display = text.includes(filter) ? '' : 'none';
            });

            calculateSalesSummary();
        });

        // Calculate on page load
        document.addEventListener('DOMContentLoaded', function () {
            calculateSalesSummary();

            // اضافه کردن event listeners به فرم فروش
            const modal = document.getElementById('newSaleModal');
            if (modal) {
                modal.addEventListener('shown.bs.modal', function () {
                    // ریست کردن فرم هنگام باز شدن مودال
                    resetSaleForm();
                });
            }

            // اضافه کردن event listener به فیلد تخفیف
            const discountInput = document.getElementById('discount');
            if (discountInput) {
                discountInput.addEventListener('input', calculateInvoice);
                discountInput.addEventListener('change', calculateInvoice);
            }
        });

        function resetSaleForm() {
            const form = document.getElementById('newSaleForm');
            if (form) {
                form.reset();

                // پاک کردن جدول و اضافه کردن یک ردیف خالی
                const tbody = document.getElementById('invoiceItems');
                if (tbody) {
                    tbody.innerHTML = `
                        <tr>
                            <td>
                                <select name="products[]" class="form-control form-control-alternative"
                                    onchange="updatePrice(this)" required>
                                    <option value=""><?= __('select_product') ?></option>
                                    <?php foreach ($products as $product): ?>
                                        <option value="<?= $product['id'] ?>"
                                            data-price="<?= $product['sell_price'] ?>"><?= $product['name'] ?>
                                            (موجودی: <?= $product['stock_quantity'] ?>)</option>
                                    <?php endforeach; ?>
                                </select>
                            </td>
                            <td>
                                <input type="number" name="quantities[]"
                                    class="form-control form-control-alternative quantity" min="1" step="1"
                                    placeholder="تعداد" onchange="calculateInvoice()" required>
                            </td>
                            <td>
                                <input type="number" name="prices[]"
                                    class="form-control form-control-alternative price" min="0" step="0.01"
                                    placeholder="قیمت واحد" onchange="calculateInvoice()" required>
                            </td>
                            <td>
                                <span class="subtotal">0</span> افغانی
                            </td>
                            <td>
                                <button type="button" onclick="removeRow(this)"
                                    class="btn btn-professional btn-danger btn-sm">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </td>
                        </tr>
                    `;
                }

                // ریست محاسبات
                calculateInvoice();

                // مخفی کردن فرم مشتری جدید
                hideNewCustomerForm();
            }
        }

        // مدیریت مشتری جدید
        function showNewCustomerForm() {
            document.getElementById('newCustomerForm').style.display = 'block';
        }

        function hideNewCustomerForm() {
            document.getElementById('newCustomerForm').style.display = 'none';
            document.getElementById('newCustomerName').value = '';
            document.getElementById('newCustomerPhone').value = '';
            document.getElementById('newCustomerAddress').value = '';
        }

        async function addNewCustomer() {
            const name = document.getElementById('newCustomerName').value.trim();
            const phone = document.getElementById('newCustomerPhone').value.trim();
            const address = document.getElementById('newCustomerAddress').value.trim();

            if (!name) {
                showAlert('لطفاً نام مشتری را وارد کنید', 'error');
                return;
            }

            const formData = new FormData();
            formData.append('name', name);
            formData.append('phone', phone);
            formData.append('address', address);
            formData.append('csrf_token', document.querySelector('input[name="csrf_token"]').value);

            try {
                const response = await fetch('api/add_customer.php', {
                    method: 'POST',
                    body: formData
                });

                const result = await response.json();

                if (result.success) {
                    // اضافه کردن مشتری جدید به لیست
                    const select = document.getElementById('input-customer');
                    const option = new Option(name, result.customer_id);
                    select.add(option);
                    select.value = result.customer_id;

                    hideNewCustomerForm();
                    showAlert('مشتری جدید با موفقیت اضافه شد', 'success');
                } else {
                    showAlert(result.message || 'خطا در افزودن مشتری', 'error');
                }
            } catch (error) {
                showAlert('خطا در ارتباط با سرور', 'error');
            }
        }

        // مدیریت بخش های پرداخت
        function togglePaymentFields() {
            const paymentType = document.getElementById('payment_type').value;
            const paidAmountField = document.getElementById('paid_amount_field');
            const paidAmountInput = document.getElementById('paid_amount');
            
            if (paymentType === 'credit') {
                paidAmountField.style.display = 'block';
                paidAmountInput.required = false; // نه اجباری برای قرضی کامل
            } else {
                paidAmountField.style.display = 'none';
                paidAmountInput.required = false;
                paidAmountInput.value = '0';
            }
        }

        // Return sale functions
        function returnSale(saleId) {
            document.getElementById('returnSaleId').value = saleId;
            document.getElementById('returnReason').value = '';
            new bootstrap.Modal(document.getElementById('returnSaleModal')).show();
        }

        async function confirmReturnSale() {
            const saleId = document.getElementById('returnSaleId').value;
            const reason = document.getElementById('returnReason').value.trim();

            if (!reason) {
                showAlert('لطفاً دلیل برگشت را وارد کنید', 'error');
                return;
            }

            if (!confirm('آیا از برگشت این فاکتور اطمینان دارید؟')) {
                return;
            }

            try {
                const formData = new FormData();
                formData.append('sale_id', saleId);
                formData.append('reason', reason);

                const response = await fetch('api/return_sale.php', {
                    method: 'POST',
                    body: formData
                });

                const result = await response.json();

                if (result.success) {
                    showAlert(result.message, 'success');
                    bootstrap.Modal.getInstance(document.getElementById('returnSaleModal')).hide();

                    // Update the row status immediately without page reload
                    updateSaleRowStatus(saleId, 'returned');
                } else {
                    showAlert(result.message, 'error');
                }
            } catch (error) {
                showAlert('خطا در ارتباط با سرور', 'error');
            }
        }

        // Function to update sale row status without page reload
        function updateSaleRowStatus(saleId, status) {
            // Find the return button for this sale
            const returnButton = document.querySelector(`button[onclick="returnSale(${saleId})"]`);
            if (returnButton) {
                const row = returnButton.closest('tr');

                // Update status column (7th column, index 6)
                const statusCell = row.cells[6];
                if (status === 'returned') {
                    statusCell.innerHTML = '<span class="badge badge-danger">برگشت شده</span>';

                    // Hide the return button
                    returnButton.style.display = 'none';
                }
            }
        }
        
        // توابع پرداخت
        function showPaymentModal(type, id, maxAmount) {
            document.getElementById('paymentType').value = type;
            document.getElementById('paymentId').value = id;
            document.getElementById('paymentAmount').max = maxAmount;
            document.getElementById('maxAmount').textContent = maxAmount.toLocaleString();
            
            new bootstrap.Modal(document.getElementById('paymentModal')).show();
        }
        
        // تابع ویرایش فاکتور فروش
        function editSale(id) {
            window.open(`edit_sale.php?id=${id}`, '_blank');
        }
        
        // تابع حذف فاکتور فروش
        async function deleteSale(id) {
            if (!confirm('آیا از حذف این فاکتور اطمینان دارید؟\nتوجه: این عمل غیرقابل بازگشت است و موجودی محصولات نیز تنظیم خواهد شد.')) {
                return;
            }

            try {
                const formData = new FormData();
                formData.append('sale_id', id);

                const response = await fetch('api/delete_sale.php', {
                    method: 'POST',
                    body: formData
                });

                const result = await response.json();

                if (result.success) {
                    showAlert(result.message, 'success');
                    setTimeout(() => location.reload(), 1000);
                } else {
                    showAlert(result.message, 'error');
                }
            } catch (error) {
                showAlert('خطا در ارتباط با سرور', 'error');
            }
        }
        
        async function submitPayment() {
            const form = document.getElementById('paymentForm');
            const formData = new FormData();
            
            formData.append('type', document.getElementById('paymentType').value);
            formData.append('id', document.getElementById('paymentId').value);
            formData.append('amount', document.getElementById('paymentAmount').value);
            formData.append('payment_date', document.getElementById('paymentDate').value);
            formData.append('payment_method', document.getElementById('paymentMethod').value);
            formData.append('notes', document.getElementById('paymentNotes').value);
            
            try {
                const response = await fetch('api/add_payment.php', {
                    method: 'POST',
                    body: formData
                });
                
                const result = await response.json();
                
                if (result.success) {
                    showAlert(result.message, 'success');
                    bootstrap.Modal.getInstance(document.getElementById('paymentModal')).hide();
                    location.reload();
                } else {
                    showAlert(result.message, 'error');
                }
            } catch (error) {
                showAlert('خطا در ارتباط با سرور', 'error');
            }
        }
    </script>