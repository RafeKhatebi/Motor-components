<?php
// Validate and sanitize file paths
$allowed_files = [
    'init_security.php',
    'config/database.php',
    'includes/functions.php',
    'includes/SettingsHelper.php',
    'includes/DateManager.php'
];

foreach ($allowed_files as $file) {
    if (!preg_match('/^[a-zA-Z0-9_\-\/]+\.php$/', $file) || strpos($file, '..') !== false) {
        throw new InvalidArgumentException('Invalid file path');
    }
    require_once $file;
}

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}
$database = new Database();
$db = $database->getConnection();
SettingsHelper::loadSettings($db);

$page_title = 'گزارشات جامع';

// گزارش فروش کامل 30 روز گذشته
$detailed_sales_query = "SELECT DATE(s.created_at) as sale_date, p.name as product_name, 
                                si.quantity, si.unit_price, si.total_price,
                                (si.unit_price - p.buy_price) * si.quantity as profit
                         FROM sale_items si
                         JOIN products p ON si.product_id = p.id
                         JOIN sales s ON si.sale_id = s.id
                         WHERE s.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
                         AND (s.status IS NULL OR s.status != 'returned')
                         ORDER BY s.created_at DESC";
$detailed_sales_stmt = $db->prepare($detailed_sales_query);
$detailed_sales_stmt->execute();
$detailed_sales = $detailed_sales_stmt->fetchAll(PDO::FETCH_ASSOC);

// محاسبه خلاصه 30 روز گذشته
$summary_30days = [
    'total_sales' => array_sum(array_column($detailed_sales, 'total_price')),
    'total_invoices' => 0,
    'total_profit' => array_sum(array_column($detailed_sales, 'profit')),
    'avg_invoice' => 0
];

// تعداد فاکتورهای 30 روز گذشته
$invoices_30days_query = "SELECT COUNT(*) as total, AVG(final_amount) as avg_amount
                          FROM sales 
                          WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
                          AND (status IS NULL OR status != 'returned')";
$invoices_30days_stmt = $db->prepare($invoices_30days_query);
$invoices_30days_stmt->execute();
$invoices_30days = $invoices_30days_stmt->fetch(PDO::FETCH_ASSOC);

$summary_30days['total_invoices'] = $invoices_30days['total'];
$summary_30days['avg_invoice'] = $invoices_30days['avg_amount'] ?: 0;

// محصولات پرفروش با موجودی کم
$low_stock_bestsellers_query = "SELECT p.name, SUM(si.quantity) as total_sold, p.stock_quantity
                                FROM sale_items si
                                JOIN products p ON si.product_id = p.id
                                JOIN sales s ON si.sale_id = s.id
                                WHERE s.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
                                  AND p.stock_quantity <= 10
                                  AND (s.status IS NULL OR s.status != 'returned')
                                GROUP BY si.product_id
                                ORDER BY total_sold DESC";
$low_stock_bestsellers_stmt = $db->prepare($low_stock_bestsellers_query);
$low_stock_bestsellers_stmt->execute();
$low_stock_bestsellers = $low_stock_bestsellers_stmt->fetchAll(PDO::FETCH_ASSOC);

// گزارش کلی سیستم
$general_stats = [];

// تعداد کل مشتریان
$customers_count_query = "SELECT COUNT(*) as total FROM customers";
$customers_count_stmt = $db->prepare($customers_count_query);
$customers_count_stmt->execute();
$general_stats['customers'] = $customers_count_stmt->fetch(PDO::FETCH_ASSOC)['total'];

// تعداد کل تأمین کننده گان
$suppliers_count_query = "SELECT COUNT(*) as total FROM suppliers";
$suppliers_count_stmt = $db->prepare($suppliers_count_query);
$suppliers_count_stmt->execute();
$general_stats['suppliers'] = $suppliers_count_stmt->fetch(PDO::FETCH_ASSOC)['total'];

// تعداد کل محصولات
$products_count_query = "SELECT COUNT(*) as total FROM products";
$products_count_stmt = $db->prepare($products_count_query);
$products_count_stmt->execute();
$general_stats['products'] = $products_count_stmt->fetch(PDO::FETCH_ASSOC)['total'];

// تعداد کل دسته بندی ها
$categories_count_query = "SELECT COUNT(*) as total FROM categories";
$categories_count_stmt = $db->prepare($categories_count_query);
$categories_count_stmt->execute();
$general_stats['categories'] = $categories_count_stmt->fetch(PDO::FETCH_ASSOC)['total'];

// کل فروش از ابتدا (بر اساس مبلغ نهایی فاکتورها)
$total_sales_query = "SELECT COALESCE(SUM(final_amount), 0) as total FROM sales WHERE (status IS NULL OR status != 'returned')";
$total_sales_stmt = $db->prepare($total_sales_query);
$total_sales_stmt->execute();
$general_stats['total_sales'] = $total_sales_stmt->fetch(PDO::FETCH_ASSOC)['total'];

// کل سود از ابتدا
$total_profit_query = "SELECT COALESCE(SUM((si.unit_price - p.buy_price) * si.quantity), 0) as total_profit
                       FROM sale_items si
                       JOIN products p ON si.product_id = p.id
                       JOIN sales s ON si.sale_id = s.id
                       WHERE (s.status IS NULL OR s.status != 'returned')";
$total_profit_stmt = $db->prepare($total_profit_query);
$total_profit_stmt->execute();
$general_stats['total_profit'] = $total_profit_stmt->fetch(PDO::FETCH_ASSOC)['total_profit'];

// کل هزینه ها مالی (خریدها)
$total_transactions_query = "SELECT COALESCE(SUM(total_amount), 0) as total_transactions FROM purchases WHERE status != 'returned'";
$total_transactions_stmt = $db->prepare($total_transactions_query);
$total_transactions_stmt->execute();
$general_stats['total_transactions'] = $total_transactions_stmt->fetch(PDO::FETCH_ASSOC)['total_transactions'];

// سود خالص (سود - هزینه ها مالی)
$general_stats['net_profit'] = $general_stats['total_profit'] - $general_stats['total_transactions'];

// تعداد کل فاکتورها
$total_invoices_query = "SELECT COUNT(*) as total FROM sales WHERE (status IS NULL OR status != 'returned')";
$total_invoices_stmt = $db->prepare($total_invoices_query);
$total_invoices_stmt->execute();
$general_stats['total_invoices'] = $total_invoices_stmt->fetch(PDO::FETCH_ASSOC)['total'];

// میانگین فاکتور
$general_stats['avg_invoice'] = $general_stats['total_invoices'] > 0 ?
    $general_stats['total_sales'] / $general_stats['total_invoices'] : 0;

// کل موجودی کالاها
$total_inventory_query = "SELECT COALESCE(SUM(stock_quantity), 0) as total FROM products";
$total_inventory_stmt = $db->prepare($total_inventory_query);
$total_inventory_stmt->execute();
$general_stats['total_inventory'] = $total_inventory_stmt->fetch(PDO::FETCH_ASSOC)['total'];

// دریافت دسته بندیها برای فیلتر
$categories_query = "SELECT * FROM categories ORDER BY name";
$categories_stmt = $db->prepare($categories_query);
$categories_stmt->execute();
$categories = $categories_stmt->fetchAll(PDO::FETCH_ASSOC);

$extra_css = '';

// Validate header file path
$header_file = 'includes/header.php';
if (!preg_match('/^[a-zA-Z0-9_\-\/]+\.php$/', $header_file) || strpos($header_file, '..') !== false) {
    throw new InvalidArgumentException('Invalid header file path');
}
include $header_file;
?>

<head>
    <link rel="stylesheet" href="./assets/css/reports.css">
</head>

<div class="section">
    <!-- منوی اصلی گزارشات -->
    <div class="reports-menu" id="reportsMenu">
        <div class="menu-header">
            <h2><i class="fas fa-chart-line"></i> گزارشات جامع</h2>
            <p class="subtitle">انتخاب نوع گزارش مورد نظر خود</p>
        </div>

        <div class="menu-grid">
            <a href="#" class="menu-item" onclick="showReport('custom')">
                <div class="menu-icon">
                    <i class="fas fa-calendar-alt"></i>
                </div>
                <div class="menu-content">
                    <h3 class="menu-title">گزارش دستی</h3>
                    <p class="menu-description">انتخاب بین دو تاریخ</p>
                </div>
                <div class="menu-arrow">
                    <i class="fas fa-chevron-left"></i>
                </div>
            </a>

            <a href="#" class="menu-item" onclick="showReport('detailed30')">
                <div class="menu-icon">
                    <i class="fas fa-chart-bar"></i>
                </div>
                <div class="menu-content">
                    <h3 class="menu-title">فروش کامل ۳۰ روز</h3>
                    <p class="menu-description">جزئیات کامل فروش ماه اخیر</p>
                </div>
                <div class="menu-arrow">
                    <i class="fas fa-chevron-left"></i>
                </div>
            </a>

            <a href="#" class="menu-item" onclick="showReport('lowstock')">
                <div class="menu-icon">
                    <i class="fas fa-exclamation-triangle"></i>
                </div>
                <div class="menu-content">
                    <h3 class="menu-title">محصولات کم موجود</h3>
                    <p class="menu-description">محصولات پرفروش با موجودی کم</p>
                </div>
                <div class="menu-arrow">
                    <i class="fas fa-chevron-left"></i>
                </div>
            </a>

            <a href="#" class="menu-item" onclick="showReport('debts')">
                <div class="menu-icon">
                    <i class="fas fa-money-bill-wave"></i>
                </div>
                <div class="menu-content">
                    <h3 class="menu-title">قرض ها و طلبها</h3>
                    <p class="menu-description">وضعیت مالی مشتریان وتأمین کنندگان</p>
                </div>
                <div class="menu-arrow">
                    <i class="fas fa-chevron-left"></i>
                </div>
            </a>

            <a href="#" class="menu-item" onclick="showReport('transactions')">
                <div class="menu-icon">
                    <i class="fas fa-exchange-alt"></i>
                </div>
                <div class="menu-content">
                    <h3 class="menu-title">هزینه ها مالی</h3>
                    <p class="menu-description">مصارف و برداشتها</p>
                </div>
                <div class="menu-arrow">
                    <i class="fas fa-chevron-left"></i>
                </div>
            </a>

            <a href="#" class="menu-item" onclick="showReport('general')">
                <div class="menu-icon">
                    <i class="fas fa-chart-pie"></i>
                </div>
                <div class="menu-content">
                    <h3 class="menu-title">گزارش کلی سیستم</h3>
                    <p class="menu-description">آمار جامع از ابتدای فعالیت</p>
                </div>
                <div class="menu-arrow">
                    <i class="fas fa-chevron-left"></i>
                </div>
            </a>
        </div>
    </div>

    <!-- بخش گزارش دستی -->
    <div class="report-section" id="customReport">
        <button class="back-button" onclick="showMenu()">
            <i class="fas fa-arrow-right"></i> بازگشت به منو
        </button>

        <div class="date-range-form no-print">
            <h4 style="margin-bottom: 15px;"><i class="fas fa-calendar-alt"></i> گزارش دستی بین دو تاریخ</h4>
            <form id="customReportForm" onsubmit="generateCustomReport(event)">
                <div class="form-row">
                    <div class="form-group">
                        <label>تاریخ شروع:</label>
                        <input type="text" id="startDate" class="filter-input jalali-date" placeholder="1404/01/01"
                            maxlength="10" required>
                    </div>
                    <div class="form-group">
                        <label>تاریخ پایان:</label>
                        <input type="text" id="endDate" class="filter-input jalali-date" placeholder="1404/12/29"
                            maxlength="10" required>
                    </div>
                    <div class="form-group">
                        <label>نوع گزارش:</label>
                        <select id="reportType" class="filter-input">
                            <option value="sales">فروش کلی</option>
                            <option value="bestsellers">محصولات پرفروش</option>
                            <option value="inventory">موجودی کالاها</option>
                            <option value="profit">سود و زیان</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>دسته بندی:</label>
                        <select id="categoryFilter" class="filter-input">
                            <option value="">همه دسته ها</option>
                            <?php foreach ($categories as $category): ?>
                                <option value="<?= $category['id'] ?>"><?= sanitizeOutput($category['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <button type="submit" class="btn-report"
                            style="background: #1f2937; color: white; border: none;">
                            <i class="fas fa-chart-bar"></i> تولید گزارش
                        </button>
                    </div>
                </div>
            </form>
        </div>

        <!-- کارتهای خلاصه -->
        <div class="summary-cards no-print" id="summaryCards" style="display: none;">
            <div class="summary-card">
                <div class="icon"><i class="fas fa-chart-line"></i></div>
                <h4>کل فروش</h4>
                <p class="value" id="totalSales"><?= number_format($general_stats['total_sales']) ?> افغانی</p>
            </div>
            <div class="summary-card">
                <div class="icon"><i class="fas fa-file-invoice"></i></div>
                <h4>تعداد فاکتور</h4>
                <p class="value" id="totalInvoices"><?= number_format($general_stats['total_invoices']) ?> عدد</p>
            </div>
            <div class="summary-card">
                <div class="icon"><i class="fas fa-coins"></i></div>
                <h4>کل سود</h4>
                <p class="value" id="totalProfit"><?= number_format($general_stats['total_profit']) ?> افغانی</p>
            </div>
            <div class="summary-card">
                <div class="icon"><i class="fas fa-calculator"></i></div>
                <h4>میانگین فاکتور</h4>
                <p class="value" id="avgInvoice"><?= number_format($general_stats['avg_invoice']) ?> افغانی</p>
            </div>
        </div>

        <!-- کارتهای آماری تفصیلی -->
        <div class="detailed-stats-cards no-print" id="detailedStatsCards" style="display: none;">
            <div class="stat-card primary">
                <div class="stat-header">
                    <div>
                        <div class="stat-title">بیشترین فروش روزانه</div>
                        <div class="stat-value" id="maxDailySales">0</div>
                        <div class="stat-change positive">
                            <i class="fas fa-arrow-up"></i>
                            <span>بهترین روز</span>
                        </div>
                    </div>
                    <div class="stat-icon primary">
                        <i class="fas fa-trophy"></i>
                    </div>
                </div>
            </div>

            <div class="stat-card success">
                <div class="stat-header">
                    <div>
                        <div class="stat-title">میانگین فروش روزانه</div>
                        <div class="stat-value" id="avgDailySales">0</div>
                        <div class="stat-change positive">
                            <i class="fas fa-chart-line"></i>
                            <span>روزانه</span>
                        </div>
                    </div>
                    <div class="stat-icon success">
                        <i class="fas fa-calendar-day"></i>
                    </div>
                </div>
            </div>

            <div class="stat-card warning">
                <div class="stat-header">
                    <div>
                        <div class="stat-title">پرفروش‌ترین محصول</div>
                        <div class="stat-value" id="topProduct">-</div>
                        <div class="stat-change positive">
                            <i class="fas fa-star"></i>
                            <span id="topProductQty">0 عدد</span>
                        </div>
                    </div>
                    <div class="stat-icon warning">
                        <i class="fas fa-medal"></i>
                    </div>
                </div>
            </div>

            <div class="stat-card info">
                <div class="stat-header">
                    <div>
                        <div class="stat-title">بهترین مشتری</div>
                        <div class="stat-value" id="topCustomer">-</div>
                        <div class="stat-change positive">
                            <i class="fas fa-crown"></i>
                            <span id="topCustomerAmount">0 افغانی</span>
                        </div>
                    </div>
                    <div class="stat-icon info">
                        <i class="fas fa-user-crown"></i>
                    </div>
                </div>
            </div>

            <div class="stat-card danger">
                <div class="stat-header">
                    <div>
                        <div class="stat-title">کمترین فروش روزانه</div>
                        <div class="stat-value" id="minDailySales">0</div>
                        <div class="stat-change negative">
                            <i class="fas fa-arrow-down"></i>
                            <span>ضعیف‌ ترین روز</span>
                        </div>
                    </div>
                    <div class="stat-icon danger">
                        <i class="fas fa-chart-line-down"></i>
                    </div>
                </div>
            </div>

            <div class="stat-card secondary">
                <div class="stat-header">
                    <div>
                        <div class="stat-title">روزهای فروش</div>
                        <div class="stat-value" id="activeDays">0</div>
                        <div class="stat-change positive">
                            <i class="fas fa-calendar-check"></i>
                            <span>روز فعال</span>
                        </div>
                    </div>
                    <div class="stat-icon secondary">
                        <i class="fas fa-calendar-alt"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- نتایج گزارش دستی -->
        <div class="report-card" id="customReportResult" style="display: none;">
            <div class="report-header no-print">
                <h3 class="report-title" id="customReportTitle">گزارش دستی</h3>
                <div class="report-actions">
                    <button onclick="printReport('custom-report')" class="btn-report btn-print">
                        <i class="fas fa-print"></i> چاپ
                    </button>
                    <button onclick="exportToExcel('custom-report', 'گزارش-دستی')" class="btn-report btn-excel">
                        <i class="fas fa-file-excel"></i> اکسل
                    </button>
                    <button onclick="exportToPDF('custom-report', 'گزارش-دستی')" class="btn-report btn-pdf">
                        <i class="fas fa-file-pdf"></i> PDF
                    </button>
                </div>
            </div>

            <div id="custom-report">
                <div class="table-responsive">
                    <table class="table align-items-center table-flush" id="custom-report-table">
                        <thead class="thead-light" id="customReportHeader">
                        </thead>
                        <tbody id="customReportBody">
                        </tbody>
                        <tfoot id="customReportFooter">
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- بخش گزارش فروش کامل 30 روز -->
    <div class="report-section" id="detailed30Report">
        <button class="back-button" onclick="showMenu()">
            <i class="fas fa-arrow-right"></i> بازگشت به منو
        </button>
        <div class="report-card">
            <!-- کارتهای خلاصه 30 روز -->
            <div class="summary-cards" style="margin: 20px;">
                <div class="summary-card">
                    <div class="icon"><i class="fas fa-chart-line"></i></div>
                    <h4>کل فروش</h4>
                    <p class="value"><?= number_format($summary_30days['total_sales']) ?> افغانی</p>
                </div>
                <div class="summary-card">
                    <div class="icon"><i class="fas fa-file-invoice"></i></div>
                    <h4>تعداد فاکتور</h4>
                    <p class="value"><?= number_format($summary_30days['total_invoices']) ?> عدد</p>
                </div>
                <div class="summary-card">
                    <div class="icon"><i class="fas fa-coins"></i></div>
                    <h4>کل سود</h4>
                    <p class="value"><?= number_format($summary_30days['total_profit']) ?> افغانی</p>
                </div>
                <div class="summary-card">
                    <div class="icon"><i class="fas fa-calculator"></i></div>
                    <h4>میانگین فاکتور</h4>
                    <p class="value"><?= number_format($summary_30days['avg_invoice']) ?> افغانی</p>
                </div>
            </div>

            <div class="report-header no-print">
                <h3 class="report-title">گزارش فروش کامل ۳۰ روز گذشته</h3>
                <div class="report-actions">
                    <button onclick="printReport('detailed-sales-report')" class="btn-report btn-print">
                        <i class="fas fa-print"></i> چاپ
                    </button>
                    <button onclick="exportToExcel('detailed-sales-report', 'گزارش-فروش-کامل')"
                        class="btn-report btn-excel">
                        <i class="fas fa-file-excel"></i> اکسل
                    </button>
                    <button onclick="exportToPDF('detailed-sales-report', 'گزارش-فروش-کامل')"
                        class="btn-report btn-pdf">
                        <i class="fas fa-file-pdf"></i> PDF
                    </button>
                    <button onclick="saveFilters('detailed-sales')" class="btn-report"
                        style="background: #6366f1; color: white;">
                        <i class="fas fa-save"></i> ذخیره فیلتر
                    </button>
                </div>
            </div>

            <div class="filter-section no-print">
                <input type="text" id="detailedSalesSearch" class="filter-input" placeholder="جستجو در محصولات..."
                    onkeyup="liveSearch('detailed-sales-table', this.value)">
                <input type="date" id="detailedDateFilter" class="filter-input"
                    onchange="filterByDate('detailed-sales-table', this.value)">
                <select id="detailedSortOrder" class="filter-input"
                    onchange="sortTable('detailed-sales-table', this.value)">
                    <option value="date-desc">تاریخ (جدید به قدیم)</option>
                    <option value="date-asc">تاریخ (قدیم به جدید)</option>
                    <option value="amount-desc">مبلغ (زیاد به کم)</option>
                    <option value="amount-asc">مبلغ (کم به زیاد)</option>
                    <option value="product-asc">محصول (الف-ی)</option>
                    <option value="product-desc">محصول (ی-الف)</option>
                </select>
                <button onclick="loadFilters('detailed-sales')" class="btn-report">
                    <i class="fas fa-upload"></i> بارگذاری فیلتر
                </button>
            </div>

            <div id="detailed-sales-report">
                <div class="table-responsive">
                    <table class="table align-items-center table-flush" id="detailed-sales-table">
                        <thead class="thead-light">
                            <tr>
                                <th>تاریخ فروش</th>
                                <th>نام محصول</th>
                                <th>تعداد</th>
                                <th>قیمت واحد</th>
                                <th>مبلغ کل</th>
                                <th>سود</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($detailed_sales as $sale): ?>
                                <tr>
                                    <td data-date="<?= $sale['sale_date'] ?>">
                                        <?= DateManager::formatDateForDisplay($sale['sale_date']) ?>
                                    </td>
                                    <td data-product="<?= $sale['product_name'] ?>">
                                        <?= sanitizeOutput($sale['product_name']) ?>
                                    </td>
                                    <td><?= $sale['quantity'] ?></td>
                                    <td><?= number_format($sale['unit_price']) ?> افغانی</td>
                                    <td data-amount="<?= $sale['total_price'] ?>"><?= number_format($sale['total_price']) ?>
                                        افغانی</td>
                                    <td data-profit="<?= $sale['profit'] ?>"
                                        class="<?= $sale['profit'] > 0 ? 'text-success' : 'text-danger' ?>">
                                        <?= number_format($sale['profit']) ?> افغانی
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                        <tfoot>
                            <tr class="table-summary">
                                <th colspan="2" class="text-end">جمع کل:</th>
                                <th id="totalQuantity"><?= array_sum(array_column($detailed_sales, 'quantity')) ?></th>
                                <th></th>
                                <th id="totalAmount">
                                    <?= number_format(array_sum(array_column($detailed_sales, 'total_price'))) ?> افغانی
                                </th>
                                <th id="totalProfitAmount"
                                    class="<?= array_sum(array_column($detailed_sales, 'profit')) > 0 ? 'text-success' : 'text-danger' ?>">
                                    <?= number_format(array_sum(array_column($detailed_sales, 'profit'))) ?> افغانی
                                </th>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- بخش محصولات پرفروش با موجودی کم -->
    <div class="report-section" id="lowstockReport">
        <button class="back-button" onclick="showMenu()">
            <i class="fas fa-arrow-right"></i> بازگشت به منو
        </button>
        <div class="report-card">
            <div class="report-header no-print">
                <h3 class="report-title">محصولات پرفروش با موجودی کم (کمتر از ۱۰ عدد)</h3>
                <div class="report-actions">
                    <button onclick="printReport('bestsellers-report')" class="btn-report btn-print">
                        <i class="fas fa-print"></i> چاپ
                    </button>
                    <button onclick="exportToExcel('bestsellers-report', 'محصولات-پرفروش-کم-موجود')"
                        class="btn-report btn-excel">
                        <i class="fas fa-file-excel"></i> اکسل
                    </button>
                    <button onclick="exportToPDF('bestsellers-report', 'محصولات-پرفروش-کم-موجود')"
                        class="btn-report btn-pdf">
                        <i class="fas fa-file-pdf"></i> PDF
                    </button>
                </div>
            </div>

            <div class="filter-section no-print">
                <input type="text" id="productsSearch" class="filter-input" placeholder="جستجو در محصولات..."
                    onkeyup="liveSearch('bestsellers-table', this.value)">
                <select id="stockFilter" class="filter-input" onchange="filterByStock('bestsellers-table', this.value)">
                    <option value="">همه موجودیها</option>
                    <option value="critical">بحرانی (≤5)</option>
                    <option value="low">کم (6-10)</option>
                </select>
            </div>

            <div id="bestsellers-report">
                <div class="table-responsive">
                    <table class="table align-items-center table-flush" id="bestsellers-table">
                        <thead class="thead-light">
                            <tr>
                                <th>نام محصول</th>
                                <th>تعداد فروش</th>
                                <th>موجودی فعلی</th>
                                <th>وضعیت</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($low_stock_bestsellers as $product): ?>
                                <tr>
                                    <td><?= sanitizeOutput($product['name']) ?></td>
                                    <td><?= $product['total_sold'] ?></td>
                                    <td data-stock="<?= $product['stock_quantity'] ?>"><?= $product['stock_quantity'] ?>
                                    </td>
                                    <td>
                                        <span
                                            class="badge badge-<?= $product['stock_quantity'] <= 5 ? 'danger' : 'warning' ?>">
                                            <?= $product['stock_quantity'] <= 5 ? 'بحرانی' : 'کم' ?>
                                        </span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                        <tfoot>
                            <tr class="table-summary">
                                <th>تعداد محصولات:</th>
                                <th><?= count($low_stock_bestsellers) ?></th>
                                <th colspan="2"></th>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- بخش گزارش کلی سیستم -->
    <div class="report-section" id="generalReport">
        <button class="back-button" onclick="showMenu()">
            <i class="fas fa-arrow-right"></i> بازگشت به منو
        </button>
        <div class="report-card">
            <div class="report-header no-print">
                <h3 class="report-title">گزارش کلی سیستم (از روز اول تا کنون)</h3>
                <div class="report-actions">
                    <button onclick="printReport('general-stats-report')" class="btn-report btn-print">
                        <i class="fas fa-print"></i> چاپ
                    </button>
                    <button onclick="exportToExcel('general-stats-report', 'گزارش-کلی-سیستم')"
                        class="btn-report btn-excel">
                        <i class="fas fa-file-excel"></i> اکسل
                    </button>
                    <button onclick="exportToPDF('general-stats-report', 'گزارش-کلی-سیستم')" class="btn-report btn-pdf">
                        <i class="fas fa-file-pdf"></i> PDF
                    </button>
                </div>
            </div>

            <div id="general-stats-report">
                <div class="summary-cards" style="margin: 20px;">
                    <div class="summary-card">
                        <div class="icon"><i class="fas fa-users"></i></div>
                        <h4>کل مشتریان</h4>
                        <p class="value"><?= number_format($general_stats['customers']) ?> نفر</p>
                    </div>
                    <div class="summary-card">
                        <div class="icon"><i class="fas fa-building"></i></div>
                        <h4>کل تأمین کننده گان</h4>
                        <p class="value"><?= number_format($general_stats['suppliers']) ?> نفر</p>
                    </div>
                    <div class="summary-card">
                        <div class="icon"><i class="fas fa-box"></i></div>
                        <h4>کل محصولات</h4>
                        <p class="value"><?= number_format($general_stats['products']) ?> عدد</p>
                    </div>
                    <div class="summary-card">
                        <div class="icon"><i class="fas fa-tags"></i></div>
                        <h4>کل دسته بندی ها</h4>
                        <p class="value"><?= number_format($general_stats['categories']) ?> عدد</p>
                    </div>
                    <div class="summary-card">
                        <div class="icon"><i class="fas fa-chart-line"></i></div>
                        <h4>کل فروش</h4>
                        <p class="value"><?= number_format($general_stats['total_sales']) ?> افغانی</p>
                    </div>
                    <div class="summary-card">
                        <div class="icon"><i class="fas fa-file-invoice"></i></div>
                        <h4>کل فاکتورها</h4>
                        <p class="value"><?= number_format($general_stats['total_invoices']) ?> عدد</p>
                    </div>
                    <div class="summary-card">
                        <div class="icon"><i class="fas fa-calculator"></i></div>
                        <h4>میانگین فاکتور</h4>
                        <p class="value"><?= number_format($general_stats['avg_invoice']) ?> افغانی</p>
                    </div>
                    <div class="summary-card">
                        <div class="icon"><i class="fas fa-warehouse"></i></div>
                        <h4>کل موجودی</h4>
                        <p class="value"><?= number_format($general_stats['total_inventory']) ?> عدد</p>
                    </div>
                    <div class="summary-card">
                        <div class="icon"><i class="fas fa-coins"></i></div>
                        <h4>کل سود</h4>
                        <p class="value"><?= number_format($general_stats['total_profit']) ?> افغانی</p>
                    </div>
                    <div class="summary-card">
                        <div class="icon"><i class="fas fa-money-bill-wave"></i></div>
                        <h4>کل هزینه ها مالی</h4>
                        <p class="value"><?= number_format($general_stats['total_transactions']) ?> افغانی</p>
                    </div>
                    <div class="summary-card">
                        <div class="icon"><i class="fas fa-chart-pie"></i></div>
                        <h4>سود خالص</h4>
                        <p class="value <?= $general_stats['net_profit'] >= 0 ? 'text-success' : 'text-danger' ?>">
                            <?= number_format($general_stats['net_profit']) ?> افغانی
                        </p>
                    </div>
                </div>

                <div class="table-responsive" style="margin: 20px;">
                    <table class="table align-items-center table-flush">
                        <thead class="thead-light">
                            <tr>
                                <th>شرح</th>
                                <th>تعداد/مقدار</th>
                                <th>واحد</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td><i class="fas fa-users text-primary me-2"></i>مشتریان ثبت شده</td>
                                <td><?= number_format($general_stats['customers']) ?></td>
                                <td>نفر</td>
                            </tr>
                            <tr>
                                <td><i class="fas fa-building text-info me-2"></i>تأمین کننده گان ثبت شده</td>
                                <td><?= number_format($general_stats['suppliers']) ?></td>
                                <td>نفر</td>
                            </tr>
                            <tr>
                                <td><i class="fas fa-box text-warning me-2"></i>محصولات ثبت شده</td>
                                <td><?= number_format($general_stats['products']) ?></td>
                                <td>عدد</td>
                            </tr>
                            <tr>
                                <td><i class="fas fa-tags text-success me-2"></i>دسته بندی ها</td>
                                <td><?= number_format($general_stats['categories']) ?></td>
                                <td>عدد</td>
                            </tr>
                            <tr class="table-summary">
                                <td><strong><i class="fas fa-chart-line text-primary me-2"></i>کل فروش از ابتدا</strong>
                                </td>
                                <td><strong><?= number_format($general_stats['total_sales']) ?></strong></td>
                                <td><strong>افغانی</strong></td>
                            </tr>
                            <tr>
                                <td><i class="fas fa-file-invoice text-secondary me-2"></i>تعداد کل فاکتورها</td>
                                <td><?= number_format($general_stats['total_invoices']) ?></td>
                                <td>عدد</td>
                            </tr>
                            <tr>
                                <td><i class="fas fa-calculator text-info me-2"></i>میانگین فاکتور</td>
                                <td><?= number_format($general_stats['avg_invoice']) ?></td>
                                <td>افغانی</td>
                            </tr>
                            <tr>
                                <td><i class="fas fa-warehouse text-warning me-2"></i>کل موجودی کالاها</td>
                                <td><?= number_format($general_stats['total_inventory']) ?></td>
                                <td>عدد</td>
                            </tr>
                            <tr class="table-summary">
                                <td><strong><i class="fas fa-coins text-success me-2"></i>کل سود از ابتدا</strong></td>
                                <td><strong><?= number_format($general_stats['total_profit']) ?></strong></td>
                                <td><strong>افغانی</strong></td>
                            </tr>
                            <tr>
                                <td><i class="fas fa-money-bill-wave text-danger me-2"></i>کل هزینه ها مالی</td>
                                <td><?= number_format($general_stats['total_transactions']) ?></td>
                                <td>افغانی</td>
                            </tr>
                            <tr class="table-summary">
                                <td><strong><i
                                            class="fas fa-chart-pie text-<?= $general_stats['net_profit'] >= 0 ? 'success' : 'danger' ?> me-2"></i>سود
                                        خالص</strong></td>
                                <td><strong
                                        class="<?= $general_stats['net_profit'] >= 0 ? 'text-success' : 'text-danger' ?>"><?= number_format($general_stats['net_profit']) ?></strong>
                                </td>
                                <td><strong>افغانی</strong></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- بخش گزارش هزینه ها مالی -->
    <div class="report-section" id="transactionsReport">
        <button class="back-button" onclick="showMenu()">
            <i class="fas fa-arrow-right"></i> بازگشت به منو
        </button>

        <div class="report-card">
            <div class="report-header no-print">
                <h3 class="report-title">گزارش هزینه ها مالی</h3>
                <div class="report-actions">
                    <button onclick="printReport('transactions-report')" class="btn-report btn-print">
                        <i class="fas fa-print"></i> چاپ
                    </button>
                    <button onclick="exportToExcel('transactions-report', 'گزارش-هزینه ها')"
                        class="btn-report btn-excel">
                        <i class="fas fa-file-excel"></i> اکسل
                    </button>
                    <button onclick="exportToPDF('transactions-report', 'گزارش-هزینه ها')" class="btn-report btn-pdf">
                        <i class="fas fa-file-pdf"></i> PDF
                    </button>
                </div>
            </div>

            <div id="transactions-report">
                <div class="summary-cards" style="margin: 20px;" id="transactionsSummary">
                    <div class="summary-card">
                        <div class="icon"><i class="fas fa-shopping-cart"></i></div>
                        <h4>کل مصارف</h4>
                        <p class="value" id="totalExpenses">0 افغانی</p>
                    </div>
                    <div class="summary-card">
                        <div class="icon"><i class="fas fa-hand-holding-usd"></i></div>
                        <h4>کل برداشتها</h4>
                        <p class="value" id="totalWithdrawals">0 افغانی</p>
                    </div>
                    <div class="summary-card">
                        <div class="icon"><i class="fas fa-calculator"></i></div>
                        <h4>کل خروجی</h4>
                        <p class="value" id="totalTransactions">0 افغانی</p>
                    </div>
                    <div class="summary-card">
                        <div class="icon"><i class="fas fa-list"></i></div>
                        <h4>تعداد تراکنش</h4>
                        <p class="value" id="transactionCount">0 مورد</p>
                    </div>
                </div>

                <div class="filter-section no-print">
                    <select id="transactionTypeFilter" class="filter-input" onchange="filterTransactionsByType()">
                        <option value="">همه انواع</option>
                        <option value="expense">مصارف</option>
                        <option value="withdrawal">برداشتها</option>
                    </select>
                    <input type="text" id="transactionPersonFilter" class="filter-input"
                        placeholder="جستجو بر اساس نام شخص..." onkeyup="filterTransactionsByPerson()">
                    <input type="date" id="transactionDateFrom" class="filter-input"
                        onchange="filterTransactionsByDateRange()">
                    <input type="date" id="transactionDateTo" class="filter-input"
                        onchange="filterTransactionsByDateRange()">
                    <button onclick="clearTransactionFilters()" class="btn-report">
                        <i class="fas fa-times"></i> پاک کردن فیلترها
                    </button>
                </div>

                <div class="table-responsive" style="margin: 20px;">
                    <table class="table align-items-center table-flush" id="transactionsTable">
                        <thead class="thead-light">
                            <tr>
                                <th>کد تراکنش</th>
                                <th>نوع</th>
                                <th>دسته</th>
                                <th>مبلغ</th>
                                <th>شخص</th>
                                <th>تاریخ</th>
                                <th>توضیحات</th>
                            </tr>
                        </thead>
                        <tbody id="transactionsTableBody">
                        </tbody>
                        <tfoot>
                            <tr class="table-summary">
                                <th colspan="3" class="text-end">جمع کل:</th>
                                <th id="footerTotalAmount">0 افغانی</th>
                                <th colspan="3"></th>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- بخش گزارش قرض ها -->
    <div class="report-section" id="debtsReport">
        <button class="back-button" onclick="showMenu()">
            <i class="fas fa-arrow-right"></i> بازگشت به منو
        </button>

        <div class="report-card">
            <div class="report-header no-print">
                <h3 class="report-title">گزارش قرض ها و طلبها</h3>
                <div class="report-actions">
                    <button onclick="printReport('debts-report')" class="btn-report btn-print">
                        <i class="fas fa-print"></i> چاپ
                    </button>
                    <button onclick="exportToExcel('debts-report', 'گزارش-قرض ها')" class="btn-report btn-excel">
                        <i class="fas fa-file-excel"></i> اکسل
                    </button>
                    <button onclick="exportToPDF('debts-report', 'گزارش-قرض ها')" class="btn-report btn-pdf">
                        <i class="fas fa-file-pdf"></i> PDF
                    </button>
                </div>
            </div>

            <div id="debts-report">
                <div class="summary-cards" style="margin: 20px;">
                    <div class="summary-card">
                        <div class="icon"><i class="fas fa-arrow-down"></i></div>
                        <h4>کل قرض مشتریان</h4>
                        <p class="value" id="totalCustomerDebt">0 افغانی</p>
                    </div>
                    <div class="summary-card">
                        <div class="icon"><i class="fas fa-arrow-up"></i></div>
                        <h4>کل طلب ازتأمین کنندگان</h4>
                        <p class="value" id="totalSupplierCredit">0 افغانی</p>
                    </div>
                    <div class="summary-card">
                        <div class="icon"><i class="fas fa-balance-scale"></i></div>
                        <h4>تراز مالی</h4>
                        <p class="value" id="financialBalance">0 افغانی</p>
                    </div>
                </div>

                <div class="row" style="margin: 20px;">
                    <div class="col-md-6">
                        <h5>قرض مشتریان</h5>
                        <div class="table-responsive">
                            <table class="table table-sm" id="customerDebtsTable">
                                <thead class="table-dark">
                                    <tr>
                                        <th>مشتری</th>
                                        <th>قرض</th>
                                        <th>وضعیت</th>
                                    </tr>
                                </thead>
                                <tbody id="customerDebtsBody">
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <h5>طلب ازتأمین کنندگان</h5>
                        <div class="table-responsive">
                            <table class="table table-sm" id="supplierCreditsTable">
                                <thead class="table-dark">
                                    <tr>
                                        <th>تأمین کننده</th>
                                        <th>طلب</th>
                                        <th>وضعیت</th>
                                    </tr>
                                </thead>
                                <tbody id="supplierCreditsBody">
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

</div>

<?php
// Validate footer file path
$footer_file = 'includes/footer-modern.php';
if (!preg_match('/^[a-zA-Z0-9_\-\/]+\.php$/', $footer_file) || strpos($footer_file, '..') !== false) {
    throw new InvalidArgumentException('Invalid footer file path');
}
include $footer_file;
?>

<script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/3.9.1/chart.min.js"></script>

<script src="./assets/js/reports.js"></script>