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

include __DIR__ . '/includes/header.php';
?>

<!-- <div class="page-header">
    <h1 class="page-title">مدیریت فروش</h1>
    <p class="page-subtitle">ثبت و مدیریت فاکتورهای فروش</p>
</div> -->

<!-- فاکتور فروش -->
<div class="section">
    <div class="card">
        <div class="card-header">
            <h5 class="card-title mb-0">
                <i class="fas fa-file-invoice me-2"></i>
                فاکتور فروش جدید
            </h5>
        </div>
        <div class="card-body">
            <form id="saleForm" onsubmit="event.preventDefault(); submitSale();">
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

                <!-- بارکد اسکنر -->
                <div class="d-flex gap-3 align-items-end mb-3">
                    <div class="form-group" style="flex: 3;">
                        <label class="form-label">اسکن بارکد</label>
                        <div class="input-group">
                            <input type="text" id="barcodeInput" class="form-control" placeholder="بارکد را اسکن کنید یا وارد کنید" onkeypress="handleBarcodeInput(event)">
                            <button type="button" class="btn btn-primary" onclick="searchBarcode()">
                                <i class="fas fa-search"></i>
                            </button>
                        </div>
                    </div>
                </div>
                
                <div class="d-flex gap-3 align-items-end mb-3">
                    <div class="form-group" style="flex: 2;">
                        <label class="form-label">مشتری</label>
                        <div class="input-group">
                            <select id="customer_id" name="customer_id" class="form-select form-select-sm">
                                <option value="">مشتری نقدی</option>
                                <?php foreach ($customers as $customer): ?>
                                    <option value="<?= $customer['id'] ?>"><?= sanitizeOutput($customer['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                            <button type="button" class="btn btn-outline-primary btn-sm"
                                onclick="showNewCustomerForm()">
                                <i class="fas fa-plus"></i>
                            </button>
                        </div>

                        <!-- فرم مشتری جدید -->
                        <div id="newCustomerForm" style="display: none;" class="mt-3 p-3 border rounded">
                            <h6>مشتری جدید</h6>
                            <div class="row">
                                <div class="col-md-6">
                                    <input type="text" id="newCustomerName" class="form-control form-control-sm"
                                        placeholder="نام مشتری" required>
                                </div>
                                <div class="col-md-6">
                                    <input type="text" id="newCustomerPhone" class="form-control form-control-sm"
                                        placeholder="شماره تلفن">
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
                    <div class="form-group" style="flex: 1;">
                        <label class="form-label">تخفیف (%)</label>
                        <input type="number" id="discount" name="discount" class="form-control" placeholder="0"
                            value="0" min="0" max="100" step="0.01" onchange="calculateInvoice()">
                    </div>
                    <div class="form-group" style="flex: 1;">
                        <label class="form-label">نوع پرداخت</label>
                        <select id="payment_type" name="payment_type" class="form-select"
                            onchange="togglePaymentFields()">
                            <option value="cash">نقدی</option>
                            <option value="credit">قرضی</option>
                        </select>
                    </div>
                    <div class="form-group" id="paid_amount_field" style="display: none; flex: 1;">
                        <label class="form-label">مبلغ پرداختی</label>
                        <input type="number" id="paid_amount" name="paid_amount" class="form-control" placeholder="0"
                            value="0" min="0" step="0.01" onchange="calculateInvoice()">
                    </div>
                    <div class="form-group">
                        <button type="button" class="btn btn-success me-2" onclick="submitSale()">
                            <i class="fas fa-check me-1"></i>ثبت فاکتور
                        </button>
                        <button type="button" class="btn btn-secondary" onclick="resetSaleForm()">
                            <i class="fas fa-times me-1"></i>انصراف
                        </button>
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="table table-sm" id="invoiceItems">
                        <thead>
                            <tr>
                                <th>محصول</th>
                                <th>تعداد</th>
                                <th>قیمت فروش</th>
                                <th>جمع</th>
                                <th>عملیات</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>
                                    <select name="products[]" class="form-select form-select-sm"
                                        onchange="updatePrice(this)">
                                        <option value="">انتخاب محصول</option>
                                        <?php foreach ($products as $product): ?>
                                            <option value="<?= $product['id'] ?>" data-price="<?= $product['sell_price'] ?>"
                                                data-buy-price="<?= $product['buy_price'] ?>">
                                                <?= sanitizeOutput($product['name']) ?> (موجودی:
                                                <?= $product['stock_quantity'] ?>)
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </td>
                                <td><input type="number" name="quantities[]"
                                        class="form-control form-control-sm quantity" min="1" step="1"
                                        placeholder="تعداد" onchange="calculateInvoice()" required></td>
                                <td><input type="number" name="prices[]" class="form-control form-control-sm price"
                                        min="0" step="0.01" placeholder="قیمت واحد"
                                        onchange="validatePrice(this); calculateInvoice()" required></td>
                                <td><span class="subtotal">0</span> افغانی</td>
                                <td>
                                    <button type="button" class="btn btn-outline-success btn-sm me-1" onclick="addRow()"
                                        title="افزودن ردیف">
                                        <i class="fas fa-plus"></i>
                                    </button>
                                    <button type="button" class="btn btn-outline-danger btn-sm"
                                        onclick="removeRow(this)" title="حذف ردیف">
                                        <i class="fas fa-minus"></i>
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                        <tfoot>
                            <tr class="table-success">
                                <th colspan="2">مجموع: <span id="totalAmount">0</span> | تخفیف: <span id="discountAmount">0</span> | مبلغ نهایی: <span id="finalAmount">0 افغانی</span></th>
                                <th colspan="3"></th>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="section">
    <div class="table-card">
        <div class="table-header">
            <div class="action-bar">
                <div class="action-group">
                    <h3>فهرست فروشها</h3>
                </div>
                <div class="action-group">
                    <input type="text" class="form-control form-control-sm" placeholder="جستجو..." id="searchInput"
                        style="width: 200px;">
                    <!-- <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#newSaleModal">
                        <i class="fas fa-plus"></i> فاکتور جدید
                    </button> -->
                    <button class="btn btn-info" onclick="exportSales()">
                        <i class="fas fa-download"></i> دانلود
                    </button>
                </div>
            </div>
        </div>
        <div class="table-responsive">
            <table class="table table-modern" id="salesTable">
                <thead>
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
                                <span
                                    class="badge <?= ($sale['payment_type'] ?? 'cash') === 'cash' ? 'bg-success' : 'bg-info' ?>">
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
                                    <button onclick="printInvoice(<?= $sale['id'] ?>)" class="btn btn-outline-info btn-sm"
                                        title="چاپ فاکتور">
                                        <i class="fas fa-print"></i>
                                    </button>
                                    <!-- <button onclick="viewSale(<?= $sale['id'] ?>)"
                                                class="btn btn-outline-primary btn-sm" title="مشاهده جزئیات">
                                                <i class="fas fa-eye"></i>
                                            </button> -->
                                    <button onclick="editSale(<?= $sale['id'] ?>)" class="btn btn-outline-warning btn-sm"
                                        title="ویرایش فاکتور">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button onclick="deleteSale(<?= $sale['id'] ?>)"
                                        class="btn btn-outline-secondary btn-sm" title="حذف فاکتور">
                                        <i class="fas fa-trash"></i>
                                    </button>

                                    <?php if (!isset($sale['status']) || $sale['status'] !== 'returned'): ?>
                                        <button onclick="returnSale(<?= $sale['id'] ?>)" class="btn btn-outline-danger btn-sm"
                                            title="برگشت فاکتور">
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





</div>
</div>

<?php include __DIR__ . '/includes/footer-modern.php'; ?>





<script>
    function addRow() {
        const tbody = document.querySelector("#invoiceItems tbody");
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
            priceInput.addEventListener('change', function () {
                validatePrice(this);
                calculateInvoice();
            });
            priceInput.addEventListener('input', function () {
                validatePrice(this);
                calculateInvoice();
            });
        }

        tbody.appendChild(newRow);
    }

    function removeRow(btn) {
        const tbody = document.querySelector("#invoiceItems tbody");
        if (tbody.rows.length > 1) {
            btn.closest("tr").remove();
            calculateInvoice();
        }
    }

    async function updatePrice(select) {
        const productId = select.value;
        const customerId = document.getElementById('customer_id').value;
        const row = select.closest("tr");
        const priceInput = row.querySelector(".price");
        const quantityInput = row.querySelector(".quantity");
        
        if (!productId) {
            priceInput.value = '';
            return;
        }
        
        try {
            const quantity = quantityInput.value || 1;
            const url = `api/get_smart_price.php?product_id=${productId}&customer_id=${customerId}&quantity=${quantity}`;
            const response = await fetch(url);
            const result = await response.json();
            
            if (result.success) {
                priceInput.value = result.data.final_price;
                priceInput.dataset.buyPrice = result.data.base_price;
                
                // نمایش اطلاعات تخفیف
                if (result.data.applied_discount > 0) {
                    const discountInfo = `تخفیف ${result.data.applied_discount}% اعمال شد`;
                    priceInput.title = discountInfo;
                }
            } else {
                // fallback to original price
                const price = select.options[select.selectedIndex].dataset.price || 0;
                priceInput.value = price;
            }
        } catch (error) {
            // fallback to original price
            const price = select.options[select.selectedIndex].dataset.price || 0;
            priceInput.value = price;
        }
        
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
        const rows = document.querySelectorAll("#invoiceItems tbody tr");
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

        const form = document.getElementById("saleForm");
        const formData = new FormData();

        // افزودن CSRF token
        formData.append('csrf_token', form.querySelector('input[name="csrf_token"]').value);

        // افزودن اطلاعات مشتری، تخفیف و پرداخت
        const customerId = form.querySelector('#customer_id').value;
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
                resetSaleForm();
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

    async function submitSale() {
        const formData = new FormData();
        
        // Add CSRF token
        const csrfToken = document.querySelector('input[name="csrf_token"]').value;
        formData.append('csrf_token', csrfToken);
        
        // Add customer, discount, payment info
        const customerId = document.getElementById('customer_id').value;
        const discount = document.getElementById('discount').value || 0;
        const paymentType = document.getElementById('payment_type').value || 'cash';
        const paidAmount = document.getElementById('paid_amount').value || 0;
        
        if (customerId) formData.append('customer_id', customerId);
        formData.append('discount', discount);
        formData.append('payment_type', paymentType);
        formData.append('paid_amount', paidAmount);
        
        // Collect products
        const rows = document.querySelectorAll('#invoiceItems tbody tr');
        let hasValidItem = false;
        
        rows.forEach(row => {
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
                    hasValidItem = true;
                }
            }
        });
        
        if (!hasValidItem) {
            showAlert('لطفاً حداقل یک محصول معتبر انتخاب کنید', 'error');
            return;
        }
        
        try {
            const response = await fetch('api/add_sale.php', {
                method: 'POST',
                body: formData
            });
            
            const result = await response.json();
            
            if (result.success) {
                showAlert('فاکتور با موفقیت ثبت شد', 'success');
                resetSaleForm();
                setTimeout(() => location.reload(), 1000);
            } else {
                showAlert(result.message || 'خطا در ثبت فاکتور', 'error');
            }
        } catch (error) {
            console.error('Error:', error);
            showAlert('خطا در ارتباط با سرور', 'error');
        }
    }

    // Barcode functions
    function handleBarcodeInput(event) {
        if (event.key === 'Enter') {
            event.preventDefault();
            searchBarcode();
        }
    }
    
    async function searchBarcode() {
        const barcodeInput = document.getElementById('barcodeInput');
        const barcode = barcodeInput.value.trim();
        
        if (!barcode) {
            showAlert('بارکد را وارد کنید', 'error');
            return;
        }
        
        try {
            const response = await fetch(`api/barcode_search.php?barcode=${encodeURIComponent(barcode)}&type=sale`);
            const result = await response.json();
            
            if (result.success) {
                addProductToInvoice(result.product);
                barcodeInput.value = '';
                showAlert(`محصول اضافه شد: ${result.product.name}`, 'success');
            } else {
                showAlert(result.message, 'error');
            }
        } catch (error) {
            showAlert('خطا در جستجوی بارکد', 'error');
        }
    }
    
    function addProductToInvoice(product) {
        const tbody = document.querySelector('#invoiceItems tbody');
        let emptyRow = null;
        
        // پیدا کردن ردیف خالی
        const rows = tbody.querySelectorAll('tr');
        for (let row of rows) {
            const select = row.querySelector('select[name="products[]"]');
            if (!select.value) {
                emptyRow = row;
                break;
            }
        }
        
        // اگر ردیف خالی نیست، یکی اضافه کن
        if (!emptyRow) {
            addRow();
            emptyRow = tbody.lastElementChild;
        }
        
        // پر کردن اطلاعات
        const select = emptyRow.querySelector('select[name="products[]"]');
        const quantityInput = emptyRow.querySelector('input[name="quantities[]"]');
        const priceInput = emptyRow.querySelector('input[name="prices[]"]');
        
        // اضافه کردن محصول به لیست اگر وجود ندارد
        let optionExists = false;
        for (let option of select.options) {
            if (option.value == product.id) {
                optionExists = true;
                break;
            }
        }
        
        if (!optionExists) {
            const option = new Option(`${product.name} (موجودی: ${product.stock_quantity})`, product.id);
            option.dataset.price = product.sell_price;
            select.add(option);
        }
        
        select.value = product.id;
        quantityInput.value = 1;
        priceInput.value = product.sell_price;
        
        calculateInvoice();
    }
    
    // Initialize event listeners
    document.addEventListener('DOMContentLoaded', function() {
        // Add event listeners to existing row
        const firstRow = document.querySelector('#invoiceItems tbody tr');
        if (firstRow) {
            const productSelect = firstRow.querySelector('select[name="products[]"]');
            const quantityInput = firstRow.querySelector('input[name="quantities[]"]');
            const priceInput = firstRow.querySelector('input[name="prices[]"]');
            
            if (productSelect) {
                productSelect.addEventListener('change', function() {
                    updatePrice(this);
                });
            }
            
            if (quantityInput) {
                quantityInput.addEventListener('input', calculateInvoice);
            }
            
            if (priceInput) {
                priceInput.addEventListener('input', function() {
                    validatePrice(this);
                    calculateInvoice();
                });
            }
        }
    });

    function validateSaleForm() {
        const rows = document.querySelectorAll('#invoiceItems tbody tr');
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
        console.log('Alert:', message, type);
        const alertClass = type === "success" ? "alert-success" : "alert-danger";
        const alertHtml = `<div class="alert ${alertClass} alert-dismissible fade show" role="alert" style="position: fixed; top: 20px; right: 20px; z-index: 9999; max-width: 400px;">
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>`;
        document.body.insertAdjacentHTML("afterbegin", alertHtml);
    }

    // Debug function
    function debugSubmit() {
        console.log('Submit button clicked');
        const rows = document.querySelectorAll('#invoiceItems tbody tr');
        console.log('Found rows:', rows.length);
        rows.forEach((row, i) => {
            const product = row.querySelector('select[name="products[]"]')?.value;
            const quantity = row.querySelector('input[name="quantities[]"]')?.value;
            const price = row.querySelector('input[name="prices[]"]')?.value;
            console.log(`Row ${i}:`, {product, quantity, price});
        });
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
        const form = document.getElementById('saleForm');
        if (form) {
            form.reset();
            calculateInvoice();
            hideNewCustomerForm();
            document.getElementById('paid_amount_field').style.display = 'none';
        }
    }

    // مدیریت مشتری جدید
    function showNewCustomerForm() {
        const form = document.getElementById('newCustomerForm');
        if (form) form.style.display = 'block';
    }

    function hideNewCustomerForm() {
        const form = document.getElementById('newCustomerForm');
        if (form) form.style.display = 'none';

        const nameInput = document.getElementById('newCustomerName');
        const phoneInput = document.getElementById('newCustomerPhone');
        const addressInput = document.getElementById('newCustomerAddress');

        if (nameInput) nameInput.value = '';
        if (phoneInput) phoneInput.value = '';
        if (addressInput) addressInput.value = '';
    }

    async function addNewCustomer() {
        const nameInput = document.getElementById('newCustomerName');
        const phoneInput = document.getElementById('newCustomerPhone');
        const addressInput = document.getElementById('newCustomerAddress');

        if (!nameInput) {
            showAlert('فرم مشتری یافت نشد', 'error');
            return;
        }

        const name = nameInput.value.trim();
        const phone = phoneInput ? phoneInput.value.trim() : '';
        const address = addressInput ? addressInput.value.trim() : '';

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
                const select = document.getElementById('customer_id');
                if (select) {
                    const option = new Option(name, result.customer_id);
                    select.add(option);
                    select.value = result.customer_id;
                }

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
        const validId = parseInt(saleId);
        if (validId <= 0) return;

        const modalHtml = `
                <div class="modal-backdrop fade show" style="z-index: 1040;"></div>
                <div class="modal fade show d-block" id="returnSaleModalInline" tabindex="-1" style="z-index: 1050;">
                    <div class="modal-dialog modal-dialog-centered modal-sm">
                        <div class="modal-content shadow">
                            <div class="modal-header bg-danger text-white">
                                <h6 class="modal-title mb-0">
                                    <i class="fas fa-undo me-2"></i>
                                    برگشت فاکتور #${validId}
                                </h6>
                                <button type="button" class="btn-close btn-close-white" onclick="closeReturnModal()"></button>
                            </div>
                            <div class="modal-body p-4">
                                <div class="mb-3">
                                    <label class="form-label fw-bold">دلیل برگشت:</label>
                                    <textarea id="returnReasonInline" class="form-control" rows="3" 
                                        placeholder="لطفاً دلیل برگشت را بنویسید..." required></textarea>
                                </div>
                                <div class="alert alert-warning alert-sm mb-0">
                                    <i class="fas fa-exclamation-triangle me-2"></i>
                                    <small>پس از برگشت، کالاها به انبار بازمیگردند</small>
                                </div>
                            </div>
                            <div class="modal-footer p-3">
                                <button type="button" class="btn btn-light btn-sm" onclick="closeReturnModal()">
                                    <i class="fas fa-times me-1"></i>انصراف
                                </button>
                                <button type="button" class="btn btn-danger btn-sm" onclick="confirmReturnSaleInline(${validId})">
                                    <i class="fas fa-check me-1"></i>تأیید برگشت
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            `;

        document.body.insertAdjacentHTML('beforeend', modalHtml);
        setTimeout(() => document.getElementById('returnReasonInline').focus(), 100);
    }

    function closeReturnModal() {
        const modal = document.getElementById('returnSaleModalInline');
        const backdrop = document.querySelector('.modal-backdrop');
        if (modal) modal.remove();
        if (backdrop) backdrop.remove();
    }

    async function confirmReturnSaleInline(saleId) {
        const reason = document.getElementById('returnReasonInline').value.trim();

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
                closeReturnModal();
                updateSaleRowStatus(saleId, 'returned');
            } else {
                showAlert(result.message, 'error');
            }
        } catch (error) {
            showAlert('خطا در ارتباط با سرور', 'error');
        }
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



    // تابع ویرایش فاکتور فروش
    function editSale(id) {
        const validId = parseInt(id);
        if (validId > 0) {
            window.open(`edit_sale.php?id=${validId}`, '_blank');
        }
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




</script>