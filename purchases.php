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

/* Mobile responsive improvements */
@media (max-width: 768px) {
    .table th:nth-child(3),
    .table td:nth-child(3) {
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
    
    .card-header .row {
        flex-direction: column;
        gap: 15px;
    }
    
    #searchInput {
        width: 100% !important;
        max-width: 300px;
    }
    
    .modal-lg {
        max-width: 95%;
    }
    
    .header .btn {
        width: 100%;
        max-width: 200px;
        margin: 5px 0;
    }
}
</style>
';

include 'includes/header.php';
?>

<!-- Header -->
<div class="header bg-gradient-primary pb-8 pt-5 pt-md-8">
    <div class="container-fluid">
        <div class="header-body">
            <div class="row align-items-center py-4">
                <div class="col-lg-6 col-7">
                    <h6 class="h2 text-white d-inline-block mb-0"><?= __('purchase_management') ?></h6>
                </div>
                <div class="col-lg-6 col-5 text-left">
                    <button onclick="openModal('newPurchaseModal')" class="btn btn-professional btn-sm">
                        <i class="fas fa-plus"></i> <?= __('new_purchase') ?>
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Page content -->
<div class="container-fluid mt--7">
    <div class="row">
        <div class="col-12">
            <div class="card card-professional">
                <div class="card-header border-0">
                    <div class="row align-items-center">
                        <div class="col">
                            <h3 class="mb-0">مدیریت خرید</h3>
                        </div>
                        <div class="col text-left">
                            <input type="text" class="form-control form-control-sm" placeholder="جستجو..."
                                id="searchInput" style="width: 200px; display: inline-block; margin-left: 10px;">
                            <button onclick="openModal('newPurchaseModal')" class="btn btn-professional btn-sm">
                                <i class="fas fa-plus"></i> فاکتور خرید جدید
                            </button>
                        </div>
                    </div>
                </div>
                <div class="table-responsive">
                    <table class="table align-items-center table-flush" id="purchasesTable">
                        <thead class="thead-light">
                            <tr>
                                <th scope="col" style="width: 40px;">#</th>
                                <th scope="col" style="width: 70px;">شماره فاکتور</th>
                                <th scope="col" style="width: 180px;">تأمین کننده</th>
                                <th scope="col" style="width: 100px;">مبلغ کل</th>
                                <th scope="col" style="width: 90px;">نوع پرداخت</th>
                                <th scope="col" style="width: 120px;">وضعیت پرداخت</th>
                                <th scope="col" style="width: 100px;">تاریخ</th>
                                <th scope="col" style="width: 90px;">وضعیت</th>
                                <th scope="col" style="width: 120px;">عملیات</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($purchases as $index => $purchase): ?>
                                <tr>
                                    <td><?= $index + 1 ?></td>
                                    <td><?= sanitizeOutput($purchase['id']) ?></td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <i class="fas fa-truck text-muted me-2"></i>
                                            <span style="font-size: 0.9rem; line-height: 1.2;">
                                                <?= sanitizeOutput($purchase['supplier_name']) ?>
                                            </span>
                                        </div>
                                    </td>
                                    <td><?= number_format($purchase['total_amount']) ?> افغانی</td>
                                    <td>
                                        <span class="badge <?= $purchase['payment_type'] === 'cash' ? 'badge-success' : 'badge-info' ?>">
                                            <?= $purchase['payment_type'] === 'cash' ? 'نقدی' : 'قرضی' ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php 
                                        $badge_class = $purchase['payment_status'] === 'paid' ? 'badge-success' : ($purchase['payment_status'] === 'partial' ? 'badge-warning' : 'badge-danger');
                                        $status_text = $purchase['payment_status'] === 'paid' ? 'پرداخت شده' : ($purchase['payment_status'] === 'partial' ? 'پرداخت جزئی' : 'پرداخت نشده');
                                        ?>
                                        <span class="badge <?= $badge_class ?>"><?= $status_text ?></span>
                                        <?php if ($purchase['payment_type'] === 'credit' && $purchase['remaining_amount'] > 0): ?>
                                            <br><small class="text-muted">باقیمانده: <?= number_format($purchase['remaining_amount']) ?> افغانی</small>
                                        <?php endif; ?>
                                    </td>
                                    <td><?= SettingsHelper::formatDate(strtotime($purchase['created_at']), $db) ?></td>
                                    <td>
                                        <?php if (isset($purchase['status']) && $purchase['status'] === 'returned'): ?>
                                            <span class="badge badge-danger">برگشت شده</span>
                                        <?php else: ?>
                                            <span class="badge badge-success">تکمیل شده</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="btn-group" role="group">
                                            <button onclick="viewPurchase(<?= $purchase['id'] ?>)"
                                                class="btn btn-professional btn-primary btn-sm" title="مشاهده جزئیات">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                            <button onclick="editPurchase(<?= $purchase['id'] ?>)"
                                                class="btn btn-professional btn-warning btn-sm" title="ویرایش فاکتور">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <button onclick="deletePurchase(<?= $purchase['id'] ?>)"
                                                class="btn btn-professional btn-secondary btn-sm" title="حذف فاکتور">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                            <?php if (!isset($purchase['status']) || $purchase['status'] !== 'returned'): ?>
                                            <button onclick="returnPurchase(<?= $purchase['id'] ?>)"
                                                class="btn btn-professional btn-danger btn-sm" title="برگشت فاکتور">
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
    </div>
</div>

<!-- Modal فاکتور خرید جدید -->
<div class="modal fade modal-professional" id="newPurchaseModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">فاکتور خرید جدید</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="newPurchaseForm" onsubmit="event.preventDefault(); submitPurchase();">
                    <?php if (!isset($_SESSION['csrf_token'])) $_SESSION['csrf_token'] = bin2hex(random_bytes(32)); ?>
                    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                    <div class="row">
                        <div class="col-lg-6">
                            <div class="form-group mb-3">
                                <label class="form-control-label">تأمینکننده</label>
                                <div class="input-group">
                                    <select id="input-supplier" name="supplier_id" class="form-control form-control-professional" required>
                                        <option value="">انتخاب کنید</option>
                                        <?php foreach ($suppliers as $supplier): ?>
                                            <option value="<?= $supplier['id'] ?>"><?= sanitizeOutput($supplier['name']) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                    <button type="button" class="btn btn-professional btn-sm" onclick="showNewSupplierForm()">
                                        <i class="fas fa-plus"></i>
                                    </button>
                                </div>
                            </div>

                            <!-- فرم تأمینکننده جدید -->
                            <div id="newSupplierForm" style="display: none;" class="mt-3 p-3 border rounded">
                                <h6>تأمینکننده جدید</h6>
                                <div class="row">
                                    <div class="col-md-6">
                                        <input type="text" id="newSupplierName" class="form-control form-control-sm" placeholder="نام تأمینکننده" required>
                                    </div>
                                    <div class="col-md-6">
                                        <input type="text" id="newSupplierPhone" class="form-control form-control-sm" placeholder="شماره تلفن">
                                    </div>
                                </div>
                                <div class="row mt-2">
                                    <div class="col-md-12">
                                        <textarea id="newSupplierAddress" class="form-control form-control-sm" placeholder="آدرس" rows="2"></textarea>
                                    </div>
                                </div>
                                <div class="mt-2">
                                    <button type="button" class="btn btn-success btn-sm" onclick="addNewSupplier()">ثبت</button>
                                    <button type="button" class="btn btn-secondary btn-sm" onclick="hideNewSupplierForm()">لغو</button>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-6">
                            <div class="form-group mb-3">
                                <label class="form-control-label">نوع پرداخت</label>
                                <select id="purchase_payment_type" name="payment_type" class="form-control form-control-professional" onchange="togglePurchasePaymentFields()">
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
                                <input type="number" id="purchase_paid_amount" name="paid_amount" class="form-control form-control-professional" 
                                       placeholder="0" value="0" min="0" step="0.01">
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
                                                        <?= sanitizeOutput($product['name']) ?></option>
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
                                                            <?= sanitizeOutput($category['name']) ?></option>
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

<!-- Modal برگشت فاکتور خرید -->
<div class="modal fade" id="returnPurchaseModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">برگشت فاکتور خرید</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="returnPurchaseForm">
                    <input type="hidden" id="returnPurchaseId">
                    <div class="form-group">
                        <label class="form-control-label">دلیل برگشت</label>
                        <textarea id="returnPurchaseReason" class="form-control" rows="3" placeholder="دلیل برگشت را بنویسید..." required></textarea>
                    </div>
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle"></i>
                        توجه: پس از برگشت، کالاها از انبار کسر و مبلغ به حساب بازگردانده میشود.
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">انصراف</button>
                <button type="button" class="btn btn-danger" onclick="confirmReturnPurchase()">تأیید برگشت</button>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>

<script>
    function openModal(modalId) {
        const modal = new bootstrap.Modal(document.getElementById(modalId));
        modal.show();
    }

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
    
    function togglePurchasePaymentFields() {
        const paymentType = document.getElementById('purchase_payment_type').value;
        const paidAmountField = document.getElementById('purchase_paid_amount_field');
        const paidAmountInput = document.getElementById('purchase_paid_amount');
        
        if (paymentType === 'credit') {
            paidAmountField.style.display = 'block';
            paidAmountInput.required = false;
        } else {
            paidAmountField.style.display = 'none';
            paidAmountInput.required = false;
            paidAmountInput.value = '0';
        }
    }

    async function submitPurchase() {
        const form = document.getElementById('newPurchaseForm');
        const formData = new FormData(form);
        
        // اضافه کردن اطلاعات پرداخت
        const paymentType = document.getElementById('purchase_payment_type').value || 'cash';
        const paidAmount = document.getElementById('purchase_paid_amount').value || 0;
        
        formData.append('payment_type', paymentType);
        formData.append('paid_amount', paidAmount);

        try {
            const response = await fetch('api/add_purchase.php', {
                method: 'POST',
                body: formData
            });

            const result = await response.json();

            if (result.success) {
                showAlert('فاکتور خرید با موفقیت ثبت شد', 'success');
                bootstrap.Modal.getInstance(document.getElementById('newPurchaseModal')).hide();
                location.reload();
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
        document.getElementById('returnPurchaseId').value = purchaseId;
        document.getElementById('returnPurchaseReason').value = '';
        new bootstrap.Modal(document.getElementById('returnPurchaseModal')).show();
    }
    
    async function confirmReturnPurchase() {
        const purchaseId = document.getElementById('returnPurchaseId').value;
        const reason = document.getElementById('returnPurchaseReason').value.trim();
        
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
                bootstrap.Modal.getInstance(document.getElementById('returnPurchaseModal')).hide();
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

    // مدیریت تأمینکننده جدید
    function showNewSupplierForm() {
        document.getElementById('newSupplierForm').style.display = 'block';
    }

    function hideNewSupplierForm() {
        document.getElementById('newSupplierForm').style.display = 'none';
        document.getElementById('newSupplierName').value = '';
        document.getElementById('newSupplierPhone').value = '';
        document.getElementById('newSupplierAddress').value = '';
    }

    async function addNewSupplier() {
        const name = document.getElementById('newSupplierName').value.trim();
        const phone = document.getElementById('newSupplierPhone').value.trim();
        const address = document.getElementById('newSupplierAddress').value.trim();

        if (!name) {
            showAlert('لطفاً نام تأمینکننده را وارد کنید', 'error');
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
                // اضافه کردن تأمینکننده جدید به لیست
                const select = document.getElementById('input-supplier');
                const option = new Option(name, result.supplier_id);
                select.add(option);
                select.value = result.supplier_id;

                hideNewSupplierForm();
                showAlert('تأمینکننده جدید با موفقیت اضافه شد', 'success');
            } else {
                showAlert(result.message || 'خطا در افزودن تأمینکننده', 'error');
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
</script>