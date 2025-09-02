<?php
require_once 'init_security.php';
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

require_once 'config/database.php';
require_once 'includes/functions.php';
require_once 'includes/SettingsHelper.php';

$database = new Database();
$db = $database->getConnection();
SettingsHelper::loadSettings($db);

$page_title = 'ویرایش فاکتور خرید';

// دریافت ID فاکتور
$purchase_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if (!$purchase_id) {
    header('Location: purchases.php');
    exit();
}

// دریافت اطلاعات فاکتور
$purchase_query = "SELECT p.*, s.name as supplier_name FROM purchases p 
                   LEFT JOIN suppliers s ON p.supplier_id = s.id 
                   WHERE p.id = :id";
$purchase_stmt = $db->prepare($purchase_query);
$purchase_stmt->bindParam(':id', $purchase_id);
$purchase_stmt->execute();
$purchase = $purchase_stmt->fetch(PDO::FETCH_ASSOC);

if (!$purchase) {
    header('Location: purchases.php');
    exit();
}

// دریافت آیتمهای فاکتور
$items_query = "SELECT pi.*, p.name as product_name FROM purchase_items pi 
                LEFT JOIN products p ON pi.product_id = p.id 
                WHERE pi.purchase_id = :purchase_id";
$items_stmt = $db->prepare($items_query);
$items_stmt->bindParam(':purchase_id', $purchase_id);
$items_stmt->execute();
$purchase_items = $items_stmt->fetchAll(PDO::FETCH_ASSOC);

// دریافت تأمینکنندگان
$suppliers_query = "SELECT * FROM suppliers ORDER BY name";
$suppliers_stmt = $db->prepare($suppliers_query);
$suppliers_stmt->execute();
$suppliers = $suppliers_stmt->fetchAll(PDO::FETCH_ASSOC);

// دریافت محصولات
$products_query = "SELECT * FROM products ORDER BY name";
$products_stmt = $db->prepare($products_query);
$products_stmt->execute();
$products = $products_stmt->fetchAll(PDO::FETCH_ASSOC);

include 'includes/header.php';
?>

<div class="section">
    <div class="card">
        <div class="card-header">
            <div class="d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0">
                    <i class="fas fa-edit me-2"></i>
                    ویرایش فاکتور خرید #<?= $purchase['id'] ?>
                </h5>
                <a href="purchases.php" class="btn btn-secondary btn-sm">
                    <i class="fas fa-arrow-right me-1"></i>بازگشت
                </a>
            </div>
        </div>
        <div class="card-body">
                    <form id="editPurchaseForm">
                        <input type="hidden" name="purchase_id" value="<?= $purchase['id'] ?>">
                        <div class="form-group mb-3">
                            <label class="form-label">تأمینکننده</label>
                            <select name="supplier_id" class="form-select" required>
                                <?php foreach ($suppliers as $supplier): ?>
                                    <option value="<?= $supplier['id'] ?>" <?= $purchase['supplier_id'] == $supplier['id'] ? 'selected' : '' ?>>
                                        <?= sanitizeOutput($supplier['name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <h6 class="mb-3">آیتمهای فاکتور</h6>
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>محصول</th>
                                        <th>تعداد</th>
                                        <th>قیمت واحد</th>
                                        <th>جمع</th>
                                        <th>عملیات</th>
                                    </tr>
                                </thead>
                                <tbody id="purchaseItems">
                                    <?php foreach ($purchase_items as $item): ?>
                                    <tr>
                                        <td>
                                            <select name="products[]" class="form-select form-select-sm">
                                                <?php foreach ($products as $product): ?>
                                                    <option value="<?= $product['id'] ?>" <?= $item['product_id'] == $product['id'] ? 'selected' : '' ?>>
                                                        <?= sanitizeOutput($product['name']) ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </td>
                                        <td>
                                            <input type="number" name="quantities[]" class="form-control form-control-sm quantity" 
                                                   value="<?= $item['quantity'] ?>" min="1" onchange="calculateTotal()">
                                        </td>
                                        <td>
                                            <input type="number" name="prices[]" class="form-control form-control-sm price" 
                                                   value="<?= $item['unit_price'] ?>" min="0" step="0.01" onchange="calculateTotal()">
                                        </td>
                                        <td class="subtotal"><?= number_format($item['total_price']) ?></td>
                                        <td>
                                            <button type="button" onclick="removeRow(this)" class="btn btn-outline-danger btn-sm">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>

                        <button type="button" onclick="addRow()" class="btn btn-outline-success btn-sm mb-3">
                            <i class="fas fa-plus me-1"></i>افزودن ردیف
                        </button>

                        <div class="d-flex justify-content-between align-items-center mt-3">
                            <h5>مبلغ کل: <span id="totalAmount" class="text-success"><?= number_format($purchase['total_amount']) ?></span> افغانی</h5>
                            <div>
                                <button type="button" onclick="updatePurchase()" class="btn btn-success me-2">
                                    <i class="fas fa-save me-1"></i>ذخیره تغییرات
                                </button>
                                <a href="purchases.php" class="btn btn-secondary">
                                    <i class="fas fa-times me-1"></i>انصراف
                                </a>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer-modern.php'; ?>

<script>
function addRow() {
    const tbody = document.getElementById('purchaseItems');
    const newRow = tbody.insertRow();
    newRow.innerHTML = `
        <td>
            <select name="products[]" class="form-select form-select-sm">
                <option value="">انتخاب محصول</option>
                <?php foreach ($products as $product): ?>
                    <option value="<?= $product['id'] ?>">
                        <?= sanitizeOutput($product['name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </td>
        <td><input type="number" name="quantities[]" class="form-control form-control-sm quantity" min="1" onchange="calculateTotal()"></td>
        <td><input type="number" name="prices[]" class="form-control form-control-sm price" min="0" step="0.01" onchange="calculateTotal()"></td>
        <td class="subtotal">0</td>
        <td><button type="button" onclick="removeRow(this)" class="btn btn-outline-danger btn-sm"><i class="fas fa-trash"></i></button></td>
    `;
}

function removeRow(btn) {
    btn.closest('tr').remove();
    calculateTotal();
}

function calculateTotal() {
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

    document.getElementById('totalAmount').textContent = total.toLocaleString();
}

async function updatePurchase() {
    const form = document.getElementById('editPurchaseForm');
    const formData = new FormData(form);

    try {
        const response = await fetch('api/edit_purchase.php', {
            method: 'POST',
            body: formData
        });

        const result = await response.json();

        if (result.success) {
            showAlert('فاکتور با موفقیت ویرایش شد', 'success');
            setTimeout(() => {
                if (window.opener) {
                    window.opener.location.reload();
                    window.close();
                } else {
                    window.location.href = 'purchases.php';
                }
            }, 1000);
        } else {
            showAlert(result.message || 'خطا در ویرایش فاکتور', 'error');
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

// محاسبه اولیه
calculateTotal();
</script>