<?php
require_once 'init_security.php';
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

require_once 'config/database.php';
require_once 'includes/functions.php';
require_once 'includes/SettingsHelper.php';
$page_title = 'مدیریت خرید';
$database = new Database();
$db = $database->getConnection();
SettingsHelper::loadSettings($db);

$suppliers_query = "SELECT * FROM suppliers ORDER BY name";
$suppliers_stmt = $db->prepare($suppliers_query);
$suppliers_stmt->execute();
$suppliers = $suppliers_stmt->fetchAll(PDO::FETCH_ASSOC);

$products_query = "SELECT * FROM products ORDER BY name";
$products_stmt = $db->prepare($products_query);
$products_stmt->execute();
$products = $products_stmt->fetchAll(PDO::FETCH_ASSOC);

$purchases_query = "SELECT p.*, s.name as supplier_name, 
                    COALESCE(p.status, 'completed') as status,
                    COALESCE(p.payment_type, 'cash') as payment_type,
                    COALESCE(p.paid_amount, p.total_amount) as paid_amount,
                    COALESCE(p.remaining_amount, 0) as remaining_amount,
                    COALESCE(p.payment_status, 'paid') as payment_status
                    FROM purchases p 
                    LEFT JOIN suppliers s ON p.supplier_id = s.id 
                    ORDER BY p.created_at DESC";
$purchases_stmt = $db->prepare($purchases_query);
$purchases_stmt->execute();
$purchases = $purchases_stmt->fetchAll(PDO::FETCH_ASSOC);

$extra_css = '
<style>
/* بهبود جدول خرید */
#purchasesTable {
    font-size: 0.9rem;
}

#purchasesTable th {
    font-weight: 600;
    font-size: 0.85rem;
    padding: 12px 8px;
    vertical-align: middle;
    border-bottom: 2px solid #dee2e6;
}

#purchasesTable td {
    padding: 12px 8px;
    vertical-align: middle;
    border-bottom: 1px solid #f1f3f4;
}

#purchasesTable tbody tr:hover {
    background-color: #f8f9fa;
    transform: scale(1.01);
    transition: all 0.2s ease;
}

.btn-group-sm .btn {
    font-size: 0.75rem;
    padding: 4px 8px;
    border-radius: 4px;
}

/* فرم خرید سریع */
#quickPurchaseForm .form-label {
    font-size: 0.85rem;
    font-weight: 600;
    margin-bottom: 4px;
}

#quickPurchaseForm .form-control,
#quickPurchaseForm .form-select {
    font-size: 0.85rem;
    padding: 6px 10px;
}

/* Button group styles */
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

/* رسپانسیو */
@media (max-width: 1200px) {
    #purchasesTable {
        font-size: 0.8rem;
    }
    
    #purchasesTable th,
    #purchasesTable td {
        padding: 8px 4px;
    }
    
    .btn-group-sm .btn {
        font-size: 0.7rem;
        padding: 2px 6px;
    }
}

@media (max-width: 768px) {
    #purchasesTable th:nth-child(4),
    #purchasesTable td:nth-child(4),
    #purchasesTable th:nth-child(5),
    #purchasesTable td:nth-child(5) {
        display: none;
    }
    
    .btn-group {
        flex-direction: column;
        width: 100%;
    }
    
    .btn-group .btn {
        width: 100%;
        margin-bottom: 5px;
        border-radius: 6px !important;
    }
    
    #quickPurchaseForm {
        flex-direction: column;
    }
    
    #quickPurchaseForm .col-md-2,
    #quickPurchaseForm .col-md-3 {
        width: 100%;
        margin-bottom: 10px;
    }
}
</style>
';

include 'includes/header.php';
?>
<!-- 
<div class="page-header">
    <h1 class="page-title">مدیریت خرید</h1>
    <p class="page-subtitle">ثبت و مدیریت فاکتورهای خرید</p>
</div> -->

<!-- فاکتور خرید -->
<div class="section">
    <div class="card">
        <div class="card-header">
            <h5 class="card-title mb-0">
                <i class="fas fa-file-invoice me-2"></i>
                فاکتور خرید جدید
            </h5>
        </div>
        <div class="card-body">
            <form id="purchaseForm" onsubmit="event.preventDefault(); submitPurchase();">
                <?php if (!isset($_SESSION['csrf_token']))
                    $_SESSION['csrf_token'] = bin2hex(random_bytes(32)); ?>
                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">

                <div class="d-flex gap-3 align-items-end mb-3">
                    <div class="form-group" style="flex: 2;">
                        <label class="form-label">تأمین کننده</label>
                        <div class="input-group">
                            <select id="supplier_id" name="supplier_id" class="form-select form-select-sm" required>
                                <option value="">انتخاب کنید</option>
                                <?php foreach ($suppliers as $supplier): ?>
                                    <option value="<?= $supplier['id'] ?>"><?= sanitizeOutput($supplier['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                            <button type="button" class="btn btn-outline-primary btn-sm"
                                onclick="showNewSupplierForm()">
                                <i class="fas fa-plus"></i>
                            </button>
                        </div>

                        <!-- فرم تأمین کننده جدید -->
                        <div id="newSupplierForm" style="display: none;" class="mt-3 p-3 border rounded">
                            <h6>تأمین کننده جدید</h6>
                            <div class="row">
                                <div class="col-md-6">
                                    <input type="text" id="newSupplierName" class="form-control form-control-sm"
                                        placeholder="نام تأمین کننده" required>
                                </div>
                                <div class="col-md-6">
                                    <input type="text" id="newSupplierPhone" class="form-control form-control-sm"
                                        placeholder="شماره تلفن">
                                </div>
                            </div>
                            <div class="row mt-2">
                                <div class="col-md-12">
                                    <textarea id="newSupplierAddress" class="form-control form-control-sm"
                                        placeholder="آدرس" rows="2"></textarea>
                                </div>
                            </div>
                            <div class="mt-2">
                                <button type="button" class="btn btn-success btn-sm"
                                    onclick="addNewSupplier()">ثبت</button>
                                <button type="button" class="btn btn-secondary btn-sm"
                                    onclick="hideNewSupplierForm()">لغو</button>
                            </div>
                        </div>
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
                        <input type="number" id="paid_amount" name="paid_amount" class="form-control"
                            placeholder="0" value="0" min="0" step="0.01">
                    </div>
                    <div class="form-group">
                        <button type="submit" class="btn btn-success">
                            <i class="fas fa-check me-1"></i>ثبت فاکتور
                        </button>
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="table table-sm" id="purchaseItems">
                        <thead>
                            <tr>
                                <th>محصول</th>
                                <th>تعداد</th>
                                <th>قیمت خرید</th>
                                <th>جمع</th>
                                <th>عملیات</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>
                                    <select name="products[]" class="form-select form-select-sm"
                                        onchange="toggleNewProduct(this)" required>
                                        <option value="">انتخاب محصول</option>
                                        <?php foreach ($products as $product): ?>
                                            <option value="<?= $product['id'] ?>">
                                                <?= sanitizeOutput($product['name']) ?> (موجودی:
                                                <?= $product['stock_quantity'] ?>)
                                            </option>
                                        <?php endforeach; ?>
                                        <option value="new">+ محصول جدید</option>
                                    </select>
                                    <div class="new-product-fields" style="display: none; margin-top: 10px;">
                                        <input type="text" name="new_product_names[]"
                                            class="form-control form-control-sm mb-2" placeholder="نام محصول جدید">
                                        <input type="text" name="new_product_codes[]"
                                            class="form-control form-control-sm mb-2" placeholder="کد محصول">
                                        <select name="new_product_categories[]" class="form-select form-select-sm">
                                            <option value="">انتخاب دسته بندی</option>
                                            <?php
                                            $categories_query = "SELECT * FROM categories ORDER BY name";
                                            $categories_stmt = $db->prepare($categories_query);
                                            $categories_stmt->execute();
                                            $categories = $categories_stmt->fetchAll(PDO::FETCH_ASSOC);
                                            foreach ($categories as $category): ?>
                                                <option value="<?= $category['id'] ?>">
                                                    <?= sanitizeOutput($category['name']) ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </td>
                                <td><input type="number" name="quantities[]"
                                        class="form-control form-control-sm quantity" min="1"
                                        onchange="calculatePurchase()" required></td>
                                <td><input type="number" name="prices[]" class="form-control form-control-sm price"
                                        min="0" step="0.01" onchange="calculatePurchase()" required></td>
                                <td class="subtotal">0</td>
                                <td>
                                    <button type="button" class="btn btn-outline-success btn-sm"
                                        onclick="addPurchaseRow()" title="افزودن ردیف">
                                        <i class="fas fa-plus"></i>
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                        <tfoot>
                            <tr class="table-dark">
                                <th colspan="3">مجموع کل:</th>
                                <th id="purchaseTotalAmount">0 افغانی</th>
                                <th></th>
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
                    <h3>فهرست خریدها</h3>
                </div>
                <div class="action-group">
                    <input type="text" class="form-control form-control-sm" placeholder="جستجو..." id="searchInput"
                        style="width: 200px;">
                    <!-- <button onclick="openModal('newPurchaseModal')" class="btn btn-primary">
                        <i class="fas fa-plus"></i> فاکتور جدید
                    </button> -->
                </div>
            </div>
        </div>
        <div class="table-responsive">
            <table class="table table-modern" id="purchasesTable">
                <thead>
                    <tr>
                        <th scope="col" style="width: 40px;">#</th>
                        <th scope="col" style="width: 70px;">فاکتور</th>
                        <th scope="col" style="width: 180px;">تأمین کننده</th>
                        <th scope="col" style="width: 100px;">مبلغ کل</th>
                        <th scope="col" style="width: 90px;">پرداخت</th>
                        <th scope="col" style="width: 120px;">وضعیت</th>
                        <th scope="col" style="width: 100px;">تاریخ</th>
                        <th scope="col" style="width: 150px;">عملیات</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($purchases as $index => $purchase): ?>
                        <tr>
                            <td class="text-center"><?= $index + 1 ?></td>
                            <td class="text-center">
                                <span class="badge bg-primary">#<?= $purchase['id'] ?></span>
                            </td>
                            <td>
                                <div class="d-flex align-items-center">
                                    <i class="fas fa-truck text-muted me-2"></i>
                                    <span style="font-size: 0.9rem; line-height: 1.2;">
                                        <?= sanitizeOutput($purchase['supplier_name']) ?>
                                    </span>
                                </div>
                            </td>
                            <td class="text-end">
                                <span class="fw-bold text-success"><?= number_format($purchase['total_amount']) ?></span>
                                <small class="text-muted d-block">افغانی</small>
                            </td>
                            <td class="text-center">
                                <span class="badge <?= $purchase['payment_type'] === 'cash' ? 'bg-success' : 'bg-info' ?>">
                                    <?= $purchase['payment_type'] === 'cash' ? 'نقدی' : 'قرضی' ?>
                                </span>
                            </td>
                            <td>
                                <?php
                                $badge_class = $purchase['payment_status'] === 'paid' ? 'bg-success' : ($purchase['payment_status'] === 'partial' ? 'bg-warning' : 'bg-danger');
                                $status_text = $purchase['payment_status'] === 'paid' ? 'پرداخت شده' : ($purchase['payment_status'] === 'partial' ? 'جزئی' : 'بدهکار');
                                ?>
                                <span class="badge <?= $badge_class ?> mb-1"><?= $status_text ?></span>
                                <?php if ($purchase['payment_type'] === 'credit' && $purchase['remaining_amount'] > 0): ?>
                                    <div class="small text-danger">
                                        <i class="fas fa-exclamation-circle"></i>
                                        <?= number_format($purchase['remaining_amount']) ?> افغانی
                                    </div>
                                <?php endif; ?>
                            </td>
                            <td class="text-center">
                                <div class="small">
                                    <i class="fas fa-calendar text-muted"></i>
                                    <?= SettingsHelper::formatDate(strtotime($purchase['created_at']), $db) ?>
                                </div>
                            </td>
                            <td>
                                <div class="btn-group btn-group-sm" role="group">
                                    <button onclick="viewPurchase(<?= $purchase['id'] ?>)"
                                        class="btn btn-outline-primary btn-sm" title="مشاهده جزئیات">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                    <button onclick="editPurchase(<?= $purchase['id'] ?>)"
                                        class="btn btn-outline-warning btn-sm" title="ویرایش فاکتور">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button onclick="deletePurchase(<?= $purchase['id'] ?>)"
                                        class="btn btn-outline-secondary btn-sm" title="حذف فاکتور">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                    <?php if (!isset($purchase['status']) || $purchase['status'] !== 'returned'): ?>
                                        <button onclick="returnPurchase(<?= $purchase['id'] ?>)"
                                            class="btn btn-outline-danger btn-sm" title="برگشت فاکتور">
                                            <i class="fas fa-undo"></i>
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>


<div class="modal fade modal-professional" id="newPurchaseModal" tabindex="-1">
    <div class="modal-dialog modal-xl modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">فاکتور خرید جدید</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="newPurchaseForm" onsubmit="event.preventDefault(); submitPurchase();">
                    <?php if (!isset($_SESSION['csrf_token']))
                        $_SESSION['csrf_token'] = bin2hex(random_bytes(32)); ?>
                    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                    <div class="row">
                        <div class="col-lg-6">
                            <div class="form-group mb-3">
                                <label class="form-control-label">تأمین کننده</label>
                                <div class="input-group">
                                    <select id="input-supplier" name="supplier_id"
                                        class="form-control form-control-professional" required>
                                        <option value="">انتخاب کنید</option>
                                        <?php foreach ($suppliers as $supplier): ?>
                                            <option value="<?= $supplier['id'] ?>"><?= sanitizeOutput($supplier['name']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <button type="button" class="btn btn-professional btn-sm"
                                        onclick="showNewSupplierForm()">
                                        <i class="fas fa-plus"></i>
                                    </button>
                                </div>
                            </div>

                            <!-- فرم تأمین کننده جدید -->
                            <div id="newSupplierForm" style="display: none;" class="mt-3 p-3 border rounded">
                                <h6>تأمین کننده جدید</h6>
                                <div class="row">
                                    <div class="col-md-6">
                                        <input type="text" id="newSupplierName" class="form-control form-control-sm"
                                            placeholder="نام تأمین کننده" required>
                                    </div>
                                    <div class="col-md-6">
                                        <input type="text" id="newSupplierPhone" class="form-control form-control-sm"
                                            placeholder="شماره تلفن">
                                    </div>
                                </div>
                                <div class="row mt-2">
                                    <div class="col-md-12">
                                        <textarea id="newSupplierAddress" class="form-control form-control-sm"
                                            placeholder="آدرس" rows="2"></textarea>
                                    </div>
                                </div>
                                <div class="mt-2">
                                    <button type="button" class="btn btn-success btn-sm"
                                        onclick="addNewSupplier()">ثبت</button>
                                    <button type="button" class="btn btn-secondary btn-sm"
                                        onclick="hideNewSupplierForm()">لغو</button>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-6">
                            <div class="form-group mb-3">
                                <label class="form-control-label">نوع پرداخت</label>
                                <select id="purchase_payment_type" name="payment_type"
                                    class="form-control form-control-professional"
                                    onchange="togglePurchasePaymentFields()">
                                    <option value="cash">نقدی</option>
                                    <option value="credit">قرضی</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="row" id="purchase_paid_amount_field" style="display: none;">
                        <div class="col-lg-6">
                            <div class="form-group mb-3">
                                <label class="form-control-label">مبلغ پرداختی</label>
                                <input type="number" id="purchase_paid_amount" name="paid_amount"
                                    class="form-control form-control-professional" placeholder="0" value="0" min="0"
                                    step="0.01">
                                <small class="form-text text-muted">مبلغی که پرداخت کرده‌اید</small>
                            </div>
                        </div>
                    </div>

                    <h6 class="heading-small text-muted mb-4">آیتمهای فاکتور</h6>
                    <div class="table-responsive">
                        <table class="table align-items-center">
                            <thead class="thead-light">
                                <tr>
                                    <th>محصول</th>
                                    <th>تعداد</th>
                                    <th>قیمت واحد</th>
                                    <th>جمع</th>
                                    <th>عملیات</th>
                                </tr>
                            </thead>
                            <tbody id="purchaseItems">
                                <tr>
                                    <td>
                                        <div class="product-selection">
                                            <select name="products[]"
                                                class="form-control form-control-professional product-select"
                                                onchange="toggleNewProduct(this)">
                                                <option value="">انتخاب محصول</option>
                                                <?php foreach ($products as $product): ?>
                                                    <option value="<?= $product['id'] ?>">
                                                        <?= sanitizeOutput($product['name']) ?>
                                                    </option>
                                                <?php endforeach; ?>
                                                <option value="new">+ محصول جدید</option>
                                            </select>
                                            <div class="new-product-fields" style="display: none; margin-top: 10px;">
                                                <input type="text" name="new_product_names[]"
                                                    class="form-control form-control-professional mb-2"
                                                    placeholder="نام محصول جدید">
                                                <input type="text" name="new_product_codes[]"
                                                    class="form-control form-control-professional mb-2"
                                                    placeholder="کد محصول">
                                                <select name="new_product_categories[]"
                                                    class="form-control form-control-professional">
                                                    <option value="">انتخاب دسته بندی</option>
                                                    <?php
                                                    $categories_query = "SELECT * FROM categories ORDER BY name";
                                                    $categories_stmt = $db->prepare($categories_query);
                                                    $categories_stmt->execute();
                                                    $categories = $categories_stmt->fetchAll(PDO::FETCH_ASSOC);
                                                    foreach ($categories as $category): ?>
                                                        <option value="<?= $category['id'] ?>">
                                                            <?= sanitizeOutput($category['name']) ?>
                                                        </option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>
                                        </div>
                                    </td>
                                    <td><input type="number" name="quantities[]"
                                            class="form-control form-control-professional quantity" min="1"
                                            onchange="calculatePurchase()"></td>
                                    <td><input type="number" name="prices[]"
                                            class="form-control form-control-professional price"
                                            onchange="calculatePurchase()"></td>
                                    <td class="subtotal">0</td>
                                    <td><button type="button" onclick="removePurchaseRow(this)"
                                            class="btn btn-professional btn-danger btn-sm">حذف</button></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <button type="button" onclick="addPurchaseRow()" class="btn btn-professional btn-sm mb-3">
                        <i class="fas fa-plus"></i> افزودن ردیف
                    </button>

                    <div class="text-left">
                        <h4>مبلغ کل: <span id="purchaseTotalAmount" class="text-success">0</span> افغانی</h4>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-professional btn-secondary" data-bs-dismiss="modal">انصراف</button>
                <button type="button" onclick="submitPurchase()" class="btn btn-professional btn-success">ثبت
                    فاکتور</button>
            </div>
        </div>
    </div>
</div>



<?php include 'includes/footer-modern.php'; ?>

<script>
    // حذف شده - ارتباطی به مودال ندارد

    async function toggleNewProduct(select) {
        const row = select.closest('tr');
        const newProductFields = row.querySelector('.new-product-fields');

        if (select.value === 'new') {
            newProductFields.style.display = 'block';

            // تولید خودکار کد محصول
            try {
                const response = await fetch('api/get_next_product_code.php');
                const result = await response.json();

                if (result.success) {
                    const codeInput = row.querySelector('input[name="new_product_codes[]"]');
                    if (codeInput) {
                        codeInput.value = result.code;
                        console.log('کد محصول تولید شد:', result.code);
                    } else {
                        console.log('فیلد کد محصول یافت نشد');
                    }
                } else {
                    console.log('خطا در API:', result.message);
                    showAlert('خطا در تولید کد محصول', 'error');
                }
            } catch (error) {
                console.log('خطا در تولید کد محصول:', error);
                showAlert('خطا در ارتباط با سرور', 'error');
            }
        } else {
            newProductFields.style.display = 'none';
            // پاک کردن فیلدها هنگام مخفی کردن
            const nameInput = row.querySelector('input[name="new_product_names[]"]');
            const codeInput = row.querySelector('input[name="new_product_codes[]"]');
            const categorySelect = row.querySelector('select[name="new_product_categories[]"]');

            if (nameInput) nameInput.value = '';
            if (codeInput) codeInput.value = '';
            if (categorySelect) categorySelect.selectedIndex = 0;
        }
    }

    function addPurchaseRow() {
        const tbody = document.getElementById('purchaseItems');
        const newRow = tbody.rows[0].cloneNode(true);
        newRow.querySelectorAll('input').forEach(input => input.value = '');
        newRow.querySelectorAll('select').forEach(select => select.selectedIndex = 0);
        newRow.querySelector('.subtotal').textContent = '0';
        newRow.querySelector('.new-product-fields').style.display = 'none';
        tbody.appendChild(newRow);
    }

    function removePurchaseRow(btn) {
        const tbody = document.getElementById('purchaseItems');
        if (tbody.rows.length > 1) {
            btn.closest('tr').remove();
            calculatePurchase();
        }
    }

    function calculatePurchase() {
        const rows = document.querySelectorAll('#purchaseItems tr');
        let total = 0;

        rows.forEach(row => {
            const quantity = parseFloat(row.querySelector('.quantity')?.value || 0);
            const price = parseFloat(row.querySelector('.price')?.value || 0);
            const subtotal = quantity * price;

            if (row.querySelector('.subtotal')) {
                row.querySelector('.subtotal').textContent = subtotal.toLocaleString();
            }

            total += subtotal;
        });

        document.getElementById('purchaseTotalAmount').textContent = total.toLocaleString();
    }



    async function submitPurchase() {
        const form = document.getElementById('purchaseForm');
        const formData = new FormData(form);

        try {
            const response = await fetch('api/add_purchase.php', {
                method: 'POST',
                body: formData
            });

            const result = await response.json();

            if (result.success) {
                showAlert('فاکتور خرید با موفقیت ثبت شد', 'success');
                form.reset();
                document.getElementById('purchaseTotalAmount').textContent = '0 افغانی';
                setTimeout(() => location.reload(), 1000);
            } else {
                showAlert(result.message || 'خطا در ثبت فاکتور', 'error');
            }
        } catch (error) {
            showAlert('خطا در ارتباط با سرور', 'error');
        }
    }

    function viewPurchase(id) {
        window.open(`view_purchase.php?id=${id}`, '_blank');
    }

    function showAlert(message, type) {
        const alertClass = type === 'success' ? 'alert-success' : 'alert-danger';
        const alertHtml = `<div class="alert ${alertClass} alert-dismissible fade show" role="alert">
                ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>`;
        document.body.insertAdjacentHTML('afterbegin', alertHtml);
    }

    // Return purchase functions
    function returnPurchase(purchaseId) {
        const modalHtml = `
            <div class="modal-backdrop fade show" style="z-index: 1040;"></div>
            <div class="modal fade show d-block" id="returnPurchaseModalInline" tabindex="-1" style="z-index: 1050;">
                <div class="modal-dialog modal-dialog-centered modal-sm">
                    <div class="modal-content shadow">
                        <div class="modal-header bg-danger text-white">
                            <h6 class="modal-title mb-0">
                                <i class="fas fa-undo me-2"></i>
                                برگشت فاکتور #${purchaseId}
                            </h6>
                            <button type="button" class="btn-close btn-close-white" onclick="closeReturnPurchaseModal()"></button>
                        </div>
                        <div class="modal-body p-4">
                            <div class="mb-3">
                                <label class="form-label fw-bold">دلیل برگشت:</label>
                                <textarea id="returnPurchaseReasonInline" class="form-control" rows="3" 
                                    placeholder="لطفاً دلیل برگشت را بنویسید..." required></textarea>
                            </div>
                            <div class="alert alert-warning alert-sm mb-0">
                                <i class="fas fa-exclamation-triangle me-2"></i>
                                <small>پس از برگشت، کالاها از انبار کسر میشوند</small>
                            </div>
                        </div>
                        <div class="modal-footer p-3">
                            <button type="button" class="btn btn-light btn-sm" onclick="closeReturnPurchaseModal()">
                                <i class="fas fa-times me-1"></i>انصراف
                            </button>
                            <button type="button" class="btn btn-danger btn-sm" onclick="confirmReturnPurchaseInline(${purchaseId})">
                                <i class="fas fa-check me-1"></i>تأیید برگشت
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        `;

        document.body.insertAdjacentHTML('beforeend', modalHtml);
        setTimeout(() => document.getElementById('returnPurchaseReasonInline').focus(), 100);
    }

    function closeReturnPurchaseModal() {
        const modal = document.getElementById('returnPurchaseModalInline');
        const backdrop = document.querySelector('.modal-backdrop');
        if (modal) modal.remove();
        if (backdrop) backdrop.remove();
    }

    async function confirmReturnPurchaseInline(purchaseId) {
        const reason = document.getElementById('returnPurchaseReasonInline').value.trim();

        if (!reason) {
            showAlert('لطفاً دلیل برگشت را وارد کنید', 'error');
            return;
        }

        if (!confirm('آیا از برگشت این فاکتور اطمینان دارید؟')) {
            return;
        }

        try {
            const formData = new FormData();
            formData.append('purchase_id', purchaseId);
            formData.append('reason', reason);

            const response = await fetch('api/return_purchase.php', {
                method: 'POST',
                body: formData
            });

            const result = await response.json();

            if (result.success) {
                showAlert(result.message, 'success');
                closeReturnPurchaseModal();
                setTimeout(() => location.reload(), 1000);
            } else {
                showAlert(result.message, 'error');
            }
        } catch (error) {
            showAlert('خطا در ارتباط با سرور', 'error');
        }
    }

    // تابع ویرایش فاکتور خرید
    function editPurchase(id) {
        window.open(`edit_purchase.php?id=${id}`, '_blank');
    }

    // تابع حذف فاکتور خرید
    async function deletePurchase(id) {
        if (!confirm('آیا از حذف این فاکتور اطمینان دارید؟\nتوجه: این عمل غیرقابل بازگشت است و موجودی محصولات نیز تنظیم خواهد شد.')) {
            return;
        }

        try {
            const formData = new FormData();
            formData.append('purchase_id', id);

            const response = await fetch('api/delete_purchase.php', {
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

    // مدیریت تأمین کننده جدید
    function showNewSupplierForm() {
        const form = document.getElementById('newSupplierForm');
        if (form) form.style.display = 'block';
    }

    function hideNewSupplierForm() {
        const form = document.getElementById('newSupplierForm');
        if (form) form.style.display = 'none';

        const nameInput = document.getElementById('newSupplierName');
        const phoneInput = document.getElementById('newSupplierPhone');
        const addressInput = document.getElementById('newSupplierAddress');

        if (nameInput) nameInput.value = '';
        if (phoneInput) phoneInput.value = '';
        if (addressInput) addressInput.value = '';
    }

    async function addNewSupplier() {
        const nameInput = document.getElementById('newSupplierName');
        const phoneInput = document.getElementById('newSupplierPhone');
        const addressInput = document.getElementById('newSupplierAddress');

        if (!nameInput) {
            showAlert('فرم تأمین کننده یافت نشد', 'error');
            return;
        }

        const name = nameInput.value.trim();
        const phone = phoneInput ? phoneInput.value.trim() : '';
        const address = addressInput ? addressInput.value.trim() : '';

        if (!name) {
            showAlert('لطفاً نام تأمین کننده را وارد کنید', 'error');
            return;
        }

        const formData = new FormData();
        formData.append('name', name);
        formData.append('phone', phone);
        formData.append('address', address);

        try {
            const response = await fetch('api/add_supplier.php', {
                method: 'POST',
                body: formData
            });

            const result = await response.json();

            if (result.success) {
                // اضافه کردن تأمین کننده جدید به لیست
                const select = document.getElementById('supplier_id');
                if (select) {
                    const option = new Option(name, result.supplier_id);
                    select.add(option);
                    select.value = result.supplier_id;
                }

                hideNewSupplierForm();
                showAlert('تأمین کننده جدید با موفقیت اضافه شد', 'success');
            } else {
                showAlert(result.message || 'خطا در افزودن تأمین کننده', 'error');
            }
        } catch (error) {
            showAlert('خطا در ارتباط با سرور', 'error');
        }
    }

    // Search functionality
    document.getElementById('searchInput').addEventListener('keyup', function () {
        const filter = this.value.toLowerCase();
        const rows = document.querySelectorAll('#purchasesTable tbody tr');

        rows.forEach(row => {
            const text = row.textContent.toLowerCase();
            row.style.display = text.includes(filter) ? '' : 'none';
        });
    });

    // توابع فاکتور خرید
    function togglePaymentFields() {
        const paymentType = document.getElementById('payment_type').value;
        const paidAmountField = document.getElementById('paid_amount_field');
        const paidAmountInput = document.getElementById('paid_amount');

        if (paymentType === 'credit') {
            paidAmountField.style.display = 'block';
            paidAmountInput.required = false;
        } else {
            paidAmountField.style.display = 'none';
            paidAmountInput.required = false;
            paidAmountInput.value = '0';
        }
    }
</script>