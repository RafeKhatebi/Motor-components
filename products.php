<?php
require_once __DIR__ . '/init_security.php';
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/SettingsHelper.php';
$database = new Database();
$db = $database->getConnection();
SettingsHelper::loadSettings($db);

$page_title = 'مدیریت اجناس';

$page_title = 'مدیریت اجناس';

$categories_query = "SELECT * FROM categories ORDER BY name";
$categories_stmt = $db->prepare($categories_query);
$categories_stmt->execute();
$categories = $categories_stmt->fetchAll(PDO::FETCH_ASSOC);

// Pagination
$items_per_page = 30;
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$offset = ($page - 1) * $items_per_page;

// Count total products
$count_query = "SELECT COUNT(*) as total FROM products";
$count_stmt = $db->prepare($count_query);
$count_stmt->execute();
$total_items = $count_stmt->fetch(PDO::FETCH_ASSOC)['total'];
$total_pages = ceil($total_items / $items_per_page);

// Add min_stock column if not exists
try {
    $db->exec("ALTER TABLE products ADD COLUMN min_stock INT DEFAULT 5");
} catch (PDOException $e) {
    // Column already exists
}

$query = "SELECT p.*, c.name as category_name FROM products p 
          LEFT JOIN categories c ON p.category_id = c.id 
          ORDER BY p.created_at DESC LIMIT :limit OFFSET :offset";
$stmt = $db->prepare($query);
$stmt->bindValue(':limit', $items_per_page, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);

$extra_css = '
<style>
.table-summary {
    background: var(--bg-tertiary);
    border-top: 2px solid var(--primary);
    font-weight: 600;
    color: var(--text-primary);
}

.table-summary th {
    padding: var(--space-4) var(--space-3);
    font-size: var(--font-sm);
    border-top: 2px solid var(--primary);
}
</style>
';

include __DIR__ . '/includes/header.php';
?>

<!-- فرم محصول جدید -->
<div class="section">
    <div class="card">
        <div class="card-header">
            <h5 class="card-title mb-0">
                <i class="fas fa-plus me-2"></i>
                افزودن محصول جدید
            </h5>
        </div>
        <div class="card-body">
            <form id="addProductForm" onsubmit="event.preventDefault(); submitForm('addProductForm', 'api/add_product.php');">
                <input type="hidden" name="csrf_token" value="<?= sanitizeOutput($_SESSION['csrf_token'] ?? '') ?>">
                
                <!-- ردیف اول: اطلاعات پایه -->
                <div class="d-flex gap-2 align-items-end mb-3">
                    <div class="form-group" style="flex: 2;">
                        <label class="form-label">نام قطعه</label>
                        <input type="text" name="name" class="form-control" required>
                    </div>
                    <div class="form-group" style="flex: 1;">
                        <label class="form-label">کد محصول</label>
                        <input type="text" name="code" id="productCode" class="form-control" readonly required>
                    </div>
                    <div class="form-group" style="flex: 1;">
                        <label class="form-label">شماره OEM</label>
                        <input type="text" name="oem_number" class="form-control" placeholder="شماره اصلی">
                    </div>
                    <div class="form-group" style="flex: 1;">
                        <label class="form-label">دستهبندی</label>
                        <select name="category_id" class="form-select" required>
                            <option value="">انتخاب کنید</option>
                            <?php foreach ($categories as $category): ?>
                                <option value="<?= $category['id'] ?>"><?= sanitizeOutput($category['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                
                <!-- ردیف دوم: مشخصات موتور -->
                <div class="d-flex gap-2 align-items-end mb-3">
                    <div class="form-group" style="flex: 1;">
                        <label class="form-label">برند موتور</label>
                        <select name="brand" class="form-select">
                            <option value="">انتخاب کنید</option>
                            <option value="Honda">Honda</option>
                            <option value="Yamaha">Yamaha</option>
                            <option value="Suzuki">Suzuki</option>
                            <option value="Kawasaki">Kawasaki</option>
                            <option value="Bajaj">Bajaj</option>
                            <option value="TVS">TVS</option>
                            <option value="Hero">Hero</option>
                            <option value="Royal Enfield">Royal Enfield</option>
                        </select>
                    </div>
                    <div class="form-group" style="flex: 1;">
                        <label class="form-label">مدل موتور</label>
                        <input type="text" name="motor_model" class="form-control" placeholder="مثال: CBR150">
                    </div>
                    <div class="form-group" style="flex: 1;">
                        <label class="form-label">سال از</label>
                        <input type="number" name="year_from" class="form-control" min="1990" max="2030">
                    </div>
                    <div class="form-group" style="flex: 1;">
                        <label class="form-label">سال تا</label>
                        <input type="number" name="year_to" class="form-control" min="1990" max="2030">
                    </div>
                </div>
                
                <!-- ردیف سوم: قیمت و موجودی -->
                <div class="d-flex gap-2 align-items-end mb-3">
                    <div class="form-group" style="flex: 1;">
                        <label class="form-label">قیمت خرید</label>
                        <input type="number" name="buy_price" class="form-control" required>
                    </div>
                    <div class="form-group" style="flex: 1;">
                        <label class="form-label">قیمت فروش</label>
                        <input type="number" name="sell_price" class="form-control" required>
                    </div>
                    <div class="form-group" style="flex: 1;">
                        <label class="form-label">موجودی</label>
                        <input type="number" name="stock_quantity" class="form-control" value="0">
                    </div>
                    <div class="form-group" style="flex: 1;">
                        <label class="form-label">حداقل موجودی</label>
                        <input type="number" name="min_stock" class="form-control" value="5">
                    </div>
                </div>
                
                <!-- ردیف چهارم: مشخصات فیزیکی -->
                <div class="d-flex gap-2 align-items-end mb-3">
                    <div class="form-group" style="flex: 1;">
                        <label class="form-label">نوع قطعه</label>
                        <select name="part_type" class="form-select">
                            <option value="aftermarket">جایگزین</option>
                            <option value="original">اصلی</option>
                            <option value="used">دست دوم</option>
                        </select>
                    </div>
                    <div class="form-group" style="flex: 1;">
                        <label class="form-label">وزن (گرم)</label>
                        <input type="number" name="weight" class="form-control" step="0.01">
                    </div>
                    <div class="form-group" style="flex: 1;">
                        <label class="form-label">گارانتی (ماه)</label>
                        <input type="number" name="warranty_months" class="form-control" value="0">
                    </div>
                    <div class="form-group" style="flex: 1;">
                        <label class="form-label">موقعیت انبار</label>
                        <input type="text" name="shelf_location" class="form-control" placeholder="قفسه A-1">
                    </div>
                </div>
                
                <!-- ردیف پنجم: توضیحات و ثبت -->
                <div class="d-flex gap-2 align-items-end">
                    <div class="form-group" style="flex: 4;">
                        <label class="form-label">توضیحات</label>
                        <input type="text" name="description" class="form-control" placeholder="توضیحات اضافی">
                    </div>
                    <div class="form-group">
                        <button type="submit" class="btn btn-success">
                            <i class="fas fa-check me-1"></i>ثبت قطعه
                        </button>
                    </div>
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
                    <h3>فهرست محصولات</h3>
                </div>
                <div class="action-group">
                    <input type="text" class="form-control form-control-sm" placeholder="جستجو..."
                        id="searchInput" style="width: 200px;">

                </div>
            </div>
        </div>

        <div class="table-responsive">
            <table class="table table-modern" id="productsTable">
                <thead>
                            <tr>
                                <th>#</th>
                                <th>محصول</th>
                                <th>دسته بندی</th>
                                <th>قیمت فروش</th>
                                <th>موجودی</th>
                                <th>وضعیت</th>
                                <th>عملیات</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($products as $index => $product): ?>
                                <tr>
                                    <td><?= $offset + $index + 1 ?></td>
                                    <th scope="row">
                                        <div class="media align-items-center">
                                            <div class="media-body">
                                                <span
                                                    class="mb-0 text-sm"><?= sanitizeOutput($product['name']) ?></span><br>
                                                <small class="text-muted"><?= sanitizeOutput(__('code')) ?>:
                                                    <?= sanitizeOutput($product['code']) ?></small>
                                            </div>
                                        </div>
                                    </th>
                                    <td>
                                        <span class="badge badge-dot me-4">
                                            <i class="bg-warning"></i>
                                            <?= sanitizeOutput($product['category_name']) ?>
                                        </span>
                                    </td>
                                    <td><?= number_format($product['sell_price']) ?>     <?= sanitizeOutput(__('afghani_currency')) ?></td>
                                    <td>
                                        <span class="badge badge-dot me-4">
                                            <i
                                                class="<?= $product['stock_quantity'] <= $product['min_stock'] ? 'bg-danger' : 'bg-success' ?>"></i>
                                            <?= $product['stock_quantity'] ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if ($product['stock_quantity'] > $product['min_stock']): ?>
                                            <span class="badge badge-success"><?= sanitizeOutput(__('available')) ?></span>
                                        <?php elseif ($product['stock_quantity'] > 0): ?>
                                            <span class="badge badge-warning"><?= sanitizeOutput(__('low_stock_item')) ?></span>
                                        <?php else: ?>
                                            <span class="badge badge-danger"><?= sanitizeOutput(__('out_of_stock')) ?></span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-left">
                                        <button onclick="editProduct(<?= $product['id'] ?>)"
                                            class="btn btn-warning btn-sm">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button
                                            onclick="confirmDelete(<?= $product['id'] ?>, 'api/delete_product.php', '<?= sanitizeOutput($product['name']) ?>')"
                                            class="btn btn-danger btn-sm">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                        <tfoot>
                            <tr class="table-summary">
                                <th colspan="2" class="text-end">جمع کل:</th>
                                <th id="totalSellPrice">0 افغانی</th>
                                <th id="totalStock">0</th>
                                <th colspan="2"></th>
                            </tr>
                        </tfoot>
                    </table>
                </div>

                <!-- Pagination -->
                <?php if ($total_pages > 1): ?>
                    <div class="card-footer py-4">
                        <nav aria-label="صفحه بندی">
                            <ul class="pagination justify-content-center mb-0">
                                <?php if ($page > 1): ?>
                                    <li class="page-item">
                                        <a class="page-link" href="?page=<?= $page - 1 ?>">
                                            <i class="fas fa-angle-right"></i>
                                            <span class="sr-only">قبلی</span>
                                        </a>
                                    </li>
                                <?php else: ?>
                                    <li class="page-item disabled">
                                        <span class="page-link">
                                            <i class="fas fa-angle-right"></i>
                                        </span>
                                    </li>
                                <?php endif; ?>

                                <?php
                                $start = max(1, $page - 2);
                                $end = min($total_pages, $page + 2);

                                if ($start > 1): ?>
                                    <li class="page-item"><a class="page-link" href="?page=1">1</a></li>
                                    <?php if ($start > 2): ?>
                                        <li class="page-item disabled"><span class="page-link">...</span></li><?php endif; ?>
                                <?php endif; ?>

                                <?php for ($i = $start; $i <= $end; $i++): ?>
                                    <li class="page-item <?= $i == $page ? 'active' : '' ?>">
                                        <a class="page-link" href="?page=<?= $i ?>"><?= $i ?></a>
                                    </li>
                                <?php endfor; ?>

                                <?php if ($end < $total_pages): ?>
                                    <?php if ($end < $total_pages - 1): ?>
                                        <li class="page-item disabled"><span class="page-link">...</span></li><?php endif; ?>
                                    <li class="page-item"><a class="page-link"
                                            href="?page=<?= $total_pages ?>"><?= $total_pages ?></a></li>
                                <?php endif; ?>

                                <?php if ($page < $total_pages): ?>
                                    <li class="page-item">
                                        <a class="page-link" href="?page=<?= $page + 1 ?>">
                                            <i class="fas fa-angle-left"></i>
                                            <span class="sr-only">بعدی</span>
                                        </a>
                                    </li>
                                <?php else: ?>
                                    <li class="page-item disabled">
                                        <span class="page-link">
                                            <i class="fas fa-angle-left"></i>
                                        </span>
                                    </li>
                                <?php endif; ?>
                            </ul>

                            <div class="text-center mt-3">
                                <small class="text-muted">
                                    نمایش <?= sanitizeOutput($offset + 1) ?> تا <?= sanitizeOutput(min($offset + $items_per_page, $total_items)) ?> از
                                    <?= sanitizeOutput($total_items) ?> محصول
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
        function editProduct(id) {
            window.location.href = `edit_product.php?id=${id}`;
        }

        async function submitForm(formId, apiUrl) {
            const form = document.getElementById(formId);
            const formData = new FormData(form);

            try {
                const response = await fetch(apiUrl, {
                    method: 'POST',
                    body: formData
                });

                const result = await response.json();

                if (result.success) {
                    showAlert('محصول با موفقیت اضافه شد', 'success');
                    form.reset();
                    generateProductCode();
                    setTimeout(() => location.reload(), 1000);
                } else {
                    showAlert(result.message || 'خطا در انجام عملیات', 'error');
                }
            } catch (error) {
                showAlert('خطا در ارتباط با سرور', 'error');
            }
        }

        function confirmDelete(id, apiUrl, name) {
            if (confirm(`آیا از حذف "${name}" اطمینان دارید؟`)) {
                deleteItem(id, apiUrl);
            }
        }

        async function deleteItem(id, apiUrl) {
            try {
                const formData = new FormData();
                formData.append('id', id);

                const response = await fetch(apiUrl, {
                    method: 'POST',
                    body: formData
                });

                const result = await response.json();

                if (result.success) {
                    showAlert('محصول با موفقیت حذف شد', 'success');
                    setTimeout(() => location.reload(), 1000);
                } else {
                    showAlert(result.message || 'خطا در حذف محصول', 'error');
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

        // Search functionality
        document.getElementById('searchInput').addEventListener('keyup', function () {
            const filter = this.value.toLowerCase();
            const rows = document.querySelectorAll('#productsTable tbody tr');

            rows.forEach(row => {
                const text = row.textContent.toLowerCase();
                row.style.display = text.includes(filter) ? '' : 'none';
            });

            calculateSummary();
        });

        // Calculate summary
        function calculateSummary() {
            const rows = document.querySelectorAll('#productsTable tbody tr');
            let totalValue = 0;
            let totalStock = 0;

            rows.forEach(row => {
                if (row.style.display !== 'none') {
                    const priceText = row.cells[2].textContent.replace(/[^0-9]/g, '');
                    const stockText = row.cells[3].textContent.replace(/[^0-9]/g, '');

                    const price = parseInt(priceText) || 0;
                    const stock = parseInt(stockText) || 0;

                    totalValue += price * stock;
                    totalStock += stock;
                }
            });

            document.getElementById('totalSellPrice').textContent = totalValue.toLocaleString() + ' افغانی';
            document.getElementById('totalStock').textContent = totalStock.toLocaleString();
        }

        // Generate product code function
        async function generateProductCode() {
            try {
                const response = await fetch('api/get_next_product_code.php');
                const result = await response.json();

                if (result.success) {
                    document.getElementById('productCode').value = result.code;
                } else {
                    showAlert('خطا در تولید کد محصول', 'error');
                }
            } catch (error) {
                showAlert('خطا در ارتباط با سرور', 'error');
            }
        }

        // Calculate on page load and generate product code
        document.addEventListener('DOMContentLoaded', function() {
            calculateSummary();
            generateProductCode();
        });
    </script>