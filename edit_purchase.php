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

<div class="header bg-gradient-primary pb-8 pt-5 pt-md-8">
    <div class="container-fluid">
        <div class="header-body">
            <div class="row align-items-center py-4">
                <div class="col-lg-6 col-7">
                    <h6 class="h2 text-white d-inline-block mb-0">ویرایش فاکتور خرید #<?= $purchase['id'] ?></h6>
                </div>
                <div class="col-lg-6 col-5 text-left">
                    <a href="purchases.php" class="btn btn-professional btn-sm">
                        <i class="fas fa-arrow-right"></i> بازگشت
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="container-fluid mt--7">
    <div class="row">
        <div class="col-12">
            <div class="card card-professional">
                <div class="card-header">
                    <h3 class="mb-0">ویرایش فاکتور خرید</h3>
                </div>
                <div class="card-body">
                    <form id="editPurchaseForm">
                        <input type="hidden" name="purchase_id" value="<?= $purchase['id'] ?>">
                        <div class="row">
                            <div class="col-lg-6">
                                <div class="form-group">
                                    <label>تأمینکننده</label>
                                    <select name="supplier_id" class="form-control" required>
                                        <?php foreach ($suppliers as $supplier): ?>
                                            <option value="<?= $supplier['id'] ?>" <?= $purchase['supplier_id'] == $supplier['id'] ? 'selected' : '' ?>>
                                                <?= sanitizeOutput($supplier['name']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <h6 class="heading-small text-muted mb-4">آیتمهای فاکتور</h6>
                        <div class="table-responsive">
                            <table class="table">
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
                                            <select name="products[]" class="form-control">
                                                <?php foreach ($products as $product): ?>
                                                    <option value="<?= $product['id'] ?>" <?= $item['product_id'] == $product['id'] ? 'selected' : '' ?>>
                                                        <?= sanitizeOutput($product['name']) ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </td>
                                        <td>
                                            <input type="number" name="quantities[]" class="form-control quantity" 
                                                   value="<?= $item['quantity'] ?>" min="1" onchange="calculateTotal()">
                                        </td>
                                        <td>
                                            <input type="number" name="prices[]" class="form-control price" 
                                                   value="<?= $item['unit_price'] ?>" min="0" step="0.01" onchange="calculateTotal()">
                                        </td>
                                        <td class="subtotal"><?= number_format($item['total_price']) ?></td>
                                        <td>
                                            <button type="button" onclick="removeRow(this)" class="btn btn-danger btn-sm">حذف</button>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>

                        <button type="button" onclick="addRow()" class="btn btn-professional btn-sm mb-3">
                            <i class="fas fa-plus"></i> افزودن ردیف
                        </button>

                        <div class="text-left">
                            <h4>مبلغ کل: <span id="totalAmount" class="text-success"><?= number_format($purchase['total_amount']) ?></span> افغانی</h4>
                        </div>

                        <div class="text-center mt-4">
                            <button type="button" onclick="updatePurchase()" class="btn btn-professional btn-success">
                                <i class="fas fa-save"></i> ذخیره تغییرات
                            </button>
                            <a href="purchases.php" class="btn btn-secondary">انصراف</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>

<script>
function addRow() {
    const tbody = document.getElementById('purchaseItems');
    const newRow = tbody.insertRow();
    newRow.innerHTML = `
        <td>
            <select name="products[]" class="form-control">
                <option value="">انتخاب محصول</option>
                <?php foreach ($products as $product): ?>
                    <option value="<?= $product['id'] ?>">
                        <?= sanitizeOutput($product['name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </td>
        <td><input type="number" name="quantities[]" class="form-control quantity" min="1" onchange="calculateTotal()"></td>
        <td><input type="number" name="prices[]" class="form-control price" min="0" step="0.01" onchange="calculateTotal()"></td>
        <td class="subtotal">0</td>
        <td><button type="button" onclick="removeRow(this)" class="btn btn-danger btn-sm">حذف</button></td>
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
            alert('فاکتور با موفقیت ویرایش شد');
            window.close();
        } else {
            alert(result.message || 'خطا در ویرایش فاکتور');
        }
    } catch (error) {
        alert('خطا در ارتباط با سرور');
    }
}

// محاسبه اولیه
calculateTotal();
</script>