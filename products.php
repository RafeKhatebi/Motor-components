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

.table-summary th:first-child {
    border-radius: 0 0 0 8px;
}

.table-summary th:last-child {
    border-radius: 0 0 8px 0;
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
    
    .modal-lg {
        max-width: 95%;
    }
    
    .card-header .row {
        flex-direction: column;
        gap: 15px;
    }
    
    #searchInput {
        width: 100% !important;
        max-width: 300px;
    }
}
</style>
';

include __DIR__ . '/includes/header.php';
?>

<!-- Header -->
<div class="header bg-gradient-primary pb-8 pt-5 pt-md-8">
    <div class="container-fluid">
        <div class="header-body">
            <div class="row align-items-center py-4">
                <div class="col-lg-6 col-7">
                    <h6 class="h2 text-white d-inline-block mb-0"><?= sanitizeOutput(__('product_management')) ?></h6>
                </div>
                <div class="col-lg-6 col-5 text-left">
                    <a href="#" class="btn btn-professional btn-sm" data-bs-toggle="modal"
                        data-bs-target="#addProductModal">
                        <i class="fas fa-plus"></i> <?= sanitizeOutput(__('new_product')) ?>
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Page content -->
<div class="container-fluid mt--7">
    <div class="row">
        <div class="col">
            <div class="card card-professional">
                <div class="card-header border-0">
                    <div class="row align-items-center">
                        <div class="col">
                            <h3 class="mb-0"><?= sanitizeOutput(__('list')) ?> <?= sanitizeOutput(__('products')) ?></h3>
                        </div>
                        <div class="col text-left">
                            <input type="text" class="form-control form-control-sm" placeholder="<?= sanitizeOutput(__('search')) ?>..."
                                id="searchInput" style="width: 200px; display: inline-block;">
                        </div>
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="table align-items-center table-flush" id="productsTable">
                        <thead class="thead-light">
                            <tr>
                                <th scope="col">#</th>
                                <th scope="col"><?= sanitizeOutput(__('products')) ?></th>
                                <th scope="col"><?= sanitizeOutput(__('category')) ?></th>
                                <th scope="col"><?= sanitizeOutput(__('sell_price')) ?></th>
                                <th scope="col"><?= sanitizeOutput(__('stock_quantity')) ?></th>
                                <th scope="col"><?= sanitizeOutput(__('status')) ?></th>
                                <th scope="col"><?= sanitizeOutput(__('actions')) ?></th>
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
                                            class="btn btn-professional btn-warning btn-sm">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button
                                            onclick="confirmDelete(<?= $product['id'] ?>, 'api/delete_product.php', '<?= sanitizeOutput($product['name']) ?>')"
                                            class="btn btn-professional btn-danger btn-sm">
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

    <?php include __DIR__ . '/includes/footer.php'; ?>

    <!-- Modal افزودن محصول -->
    <div class="modal fade modal-professional" id="addProductModal" tabindex="-1" role="dialog">
        <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">افزودن محصول جدید</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="addProductForm"
                        onsubmit="event.preventDefault(); submitForm('addProductForm', 'api/add_product.php');">
                        <?php if (!isset($_SESSION['csrf_token']))
                            $_SESSION['csrf_token'] = bin2hex(random_bytes(32)); ?>
                        <input type="hidden" name="csrf_token" value="<?= sanitizeOutput($_SESSION['csrf_token']) ?>">
                        <div class="row">
                            <div class="col-lg-6">
                                <div class="form-group">
                                    <label class="form-control-label">نام محصول</label>
                                    <input type="text" name="name" class="form-control form-control-professional"
                                        required>
                                </div>
                            </div>
                            <div class="col-lg-6">
                                <div class="form-group">
                                    <label class="form-control-label">کد محصول</label>
                                    <input type="text" name="code" id="productCode"
                                        class="form-control form-control-professional" readonly required>
                                    <small class="form-text text-muted">کد به صورت خودکار تولید میشود</small>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-lg-6">
                                <div class="form-group">
                                    <label class="form-control-label">دسته بندی</label>
                                    <select name="category_id" class="form-control form-control-professional" required>
                                        <option value="">انتخاب کنید</option>
                                        <?php foreach ($categories as $category): ?>
                                            <option value="<?= $category['id'] ?>"><?= sanitizeOutput($category['name']) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                            <div class="col-lg-6">
                                <div class="form-group">
                                    <label class="form-control-label">موجودی اولیه</label>
                                    <input type="number" name="stock_quantity"
                                        class="form-control form-control-professional" value="0">
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-lg-6">
                                <div class="form-group">
                                    <label class="form-control-label">قیمت خرید</label>
                                    <input type="number" name="buy_price" class="form-control form-control-professional"
                                        required>
                                </div>
                            </div>
                            <div class="col-lg-6">
                                <div class="form-group">
                                    <label class="form-control-label">قیمت فروش</label>
                                    <input type="number" name="sell_price"
                                        class="form-control form-control-professional" required>
                                </div>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="form-control-label">حداقل موجودی</label>
                            <input type="number" name="min_stock" class="form-control form-control-professional"
                                value="5">
                        </div>
                        <div class="form-group">
                            <label class="form-control-label">توضیحات</label>
                            <textarea name="description" class="form-control form-control-professional"
                                rows="3"></textarea>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-professional btn-secondary"
                        data-bs-dismiss="modal">انصراف</button>
                    <button type="submit" form="addProductForm" class="btn btn-professional btn-success">ذخیره</button>
                </div>
            </div>
        </div>
    </div>

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
                    showAlert('عملیات با موفقیت انجام شد', 'success');
                    bootstrap.Modal.getInstance(form.closest('.modal')).hide();
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

        // Load product code when modal opens
        document.getElementById('addProductModal').addEventListener('show.bs.modal', async function () {
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
        });

        // Calculate on page load
        document.addEventListener('DOMContentLoaded', calculateSummary);
    </script>