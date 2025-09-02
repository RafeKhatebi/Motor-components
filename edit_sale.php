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

$page_title = 'ویرایش فاکتور فروش';

// دریافت ID فاکتور
$sale_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if (!$sale_id) {
    header('Location: sales.php');
    exit();
}

// دریافت اطلاعات فاکتور
$sale_query = "SELECT s.*, c.name as customer_name FROM sales s 
               LEFT JOIN customers c ON s.customer_id = c.id 
               WHERE s.id = :id";
$sale_stmt = $db->prepare($sale_query);
$sale_stmt->bindParam(':id', $sale_id);
$sale_stmt->execute();
$sale = $sale_stmt->fetch(PDO::FETCH_ASSOC);

if (!$sale) {
    header('Location: sales.php');
    exit();
}

// دریافت آیتمهای فاکتور
$items_query = "SELECT si.*, p.name as product_name FROM sale_items si 
                LEFT JOIN products p ON si.product_id = p.id 
                WHERE si.sale_id = :sale_id";
$items_stmt = $db->prepare($items_query);
$items_stmt->bindParam(':sale_id', $sale_id);
$items_stmt->execute();
$sale_items = $items_stmt->fetchAll(PDO::FETCH_ASSOC);

// دریافت مشتریان
$customers_query = "SELECT * FROM customers ORDER BY name";
$customers_stmt = $db->prepare($customers_query);
$customers_stmt->execute();
$customers = $customers_stmt->fetchAll(PDO::FETCH_ASSOC);

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
                    ویرایش فاکتور فروش #<?= $sale['id'] ?>
                </h5>
                <a href="sales.php" class="btn btn-secondary btn-sm">
                    <i class="fas fa-arrow-right me-1"></i>بازگشت
                </a>
            </div>
        </div>
        <div class="card-body">
                    <form id="editSaleForm">
                        <input type="hidden" name="sale_id" value="<?= $sale['id'] ?>">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group mb-3">
                                    <label class="form-label">مشتری</label>
                                    <select name="customer_id" class="form-select">
                                        <option value="">مشتری نقدی</option>
                                        <?php foreach ($customers as $customer): ?>
                                            <option value="<?= $customer['id'] ?>" <?= $sale['customer_id'] == $customer['id'] ? 'selected' : '' ?>>
                                                <?= sanitizeOutput($customer['name']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group mb-3">
                                    <label class="form-label">تخفیف (%)</label>
                                    <input type="number" name="discount" class="form-control" value="<?= $sale['discount'] ?>" min="0" max="100" step="0.01" onchange="calculateTotal()">
                                </div>
                            </div>
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
                                <tbody id="saleItems">
                                    <?php foreach ($sale_items as $item): ?>
                                    <tr>
                                        <td>
                                            <select name="products[]" class="form-select form-select-sm" onchange="updatePrice(this)">
                                                <?php foreach ($products as $product): ?>
                                                    <option value="<?= $product['id'] ?>" data-price="<?= $product['sell_price'] ?>" 
                                                            <?= $item['product_id'] == $product['id'] ? 'selected' : '' ?>>
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
                            <h5>مبلغ کل: <span id="totalAmount" class="text-success"><?= number_format($sale['final_amount']) ?></span> افغانی</h5>
                            <div>
                                <button type="button" onclick="updateSale()" class="btn btn-success me-2">
                                    <i class="fas fa-save me-1"></i>ذخیره تغییرات
                                </button>
                                <a href="sales.php" class="btn btn-secondary">
                                    <i class="fas fa-times me-1"></i>انصراف
                                </a>
                            </div>
                        </div>
                    </form>
        </div>
    </div>
</div>

<?php include 'includes/footer-modern.php'; ?>

<script>
function addRow() {
    const tbody = document.getElementById('saleItems');
    const newRow = tbody.insertRow();
    newRow.innerHTML = `
        <td>
            <select name="products[]" class="form-select form-select-sm" onchange="updatePrice(this)">
                <option value="">انتخاب محصول</option>
                <?php foreach ($products as $product): ?>
                    <option value="<?= $product['id'] ?>" data-price="<?= $product['sell_price'] ?>">
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

function updatePrice(select) {
    const price = select.options[select.selectedIndex].dataset.price || 0;
    const row = select.closest('tr');
    row.querySelector('.price').value = price;
    calculateTotal();
}

function calculateTotal() {
    const rows = document.querySelectorAll('#saleItems tr');
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

    const discount = parseFloat(document.querySelector('input[name="discount"]').value || 0);
    const discountAmount = (total * discount) / 100;
    const finalAmount = total - discountAmount;

    document.getElementById('totalAmount').textContent = finalAmount.toLocaleString();
}

async function updateSale() {
    const form = document.getElementById('editSaleForm');
    const formData = new FormData(form);

    try {
        const response = await fetch('api/edit_sale.php', {
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
                    window.location.href = 'sales.php';
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