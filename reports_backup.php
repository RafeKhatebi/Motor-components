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

$page_title = 'گزارشات پیشرفته';

// گزارش فروش تفصیلی 30 روز گذشته
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

// تعداد کل تأمین کنندگان
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

$extra_css = '
<style>
.report-container {
    background: #f8fafc;
    min-height: 100vh;
    padding: 20px 0;
}

.reports-menu {
    background: white;
    border-radius: 15px;
    box-shadow: 0 8px 30px rgba(0, 0, 0, 0.1);
    margin-bottom: 30px;
    overflow: hidden;
}

.menu-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 25px;
    text-align: center;
    position: relative;
}

.menu-header::before {
    content: "";
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: linear-gradient(45deg, rgba(255,255,255,0.1) 0%, transparent 50%, rgba(255,255,255,0.1) 100%);
}

.menu-header h2 {
    margin: 0;
    font-size: 1.8rem;
    font-weight: 700;
    position: relative;
    z-index: 1;
}

.menu-header .subtitle {
    margin: 8px 0 0 0;
    opacity: 0.9;
    font-size: 1rem;
    position: relative;
    z-index: 1;
}

.menu-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 0;
    padding: 0;
}

.menu-item {
    padding: 25px;
    border: none;
    background: white;
    cursor: pointer;
    transition: all 0.3s ease;
    text-decoration: none;
    color: #374151;
    display: flex;
    align-items: center;
    gap: 15px;
    border-bottom: 1px solid #e5e7eb;
    border-right: 1px solid #e5e7eb;
    position: relative;
    overflow: hidden;
}

.menu-item:last-child,
.menu-item:nth-child(even) {
    border-right: none;
}

.menu-item:nth-last-child(-n+2) {
    border-bottom: none;
}

.menu-item::before {
    content: "";
    position: absolute;
    top: 0;
    left: -100%;
    width: 100%;
    height: 100%;
    background: linear-gradient(90deg, transparent, rgba(102, 126, 234, 0.1), transparent);
    transition: left 0.5s ease;
}

.menu-item:hover::before {
    left: 100%;
}

.menu-item:hover {
    background: #f8fafc;
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
    color: #1f2937;
}

.menu-icon {
    width: 50px;
    height: 50px;
    background: linear-gradient(135deg, #667eea, #764ba2);
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 1.3rem;
    flex-shrink: 0;
    position: relative;
    z-index: 1;
}

.menu-content {
    flex: 1;
    position: relative;
    z-index: 1;
}

.menu-title {
    font-size: 1.1rem;
    font-weight: 600;
    margin: 0 0 5px 0;
    color: #1f2937;
}

.menu-description {
    font-size: 0.9rem;
    color: #6b7280;
    margin: 0;
    line-height: 1.4;
}

.menu-arrow {
    font-size: 1.2rem;
    color: #9ca3af;
    transition: all 0.3s ease;
    position: relative;
    z-index: 1;
}

.menu-item:hover .menu-arrow {
    color: #667eea;
    transform: translateX(-5px);
}

.report-section {
    display: none;
    animation: fadeIn 0.5s ease;
}

.report-section.active {
    display: block;
}

@keyframes fadeIn {
    from { opacity: 0; transform: translateY(20px); }
    to { opacity: 1; transform: translateY(0); }
}

.back-button {
    background: linear-gradient(135deg, #6b7280, #4b5563);
    color: white;
    border: none;
    padding: 12px 20px;
    border-radius: 8px;
    cursor: pointer;
    font-size: 0.9rem;
    font-weight: 500;
    margin-bottom: 20px;
    transition: all 0.3s ease;
    display: inline-flex;
    align-items: center;
    gap: 8px;
}

.back-button:hover {
    background: linear-gradient(135deg, #4b5563, #374151);
    transform: translateY(-2px);
}

@media (max-width: 768px) {
    .menu-grid {
        grid-template-columns: 1fr;
    }
    
    .menu-item {
        border-right: none;
        border-bottom: 1px solid #e5e7eb;
    }
    
    .menu-header h2 {
        font-size: 1.5rem;
    }
}

.report-card {
    background: white;
    border-radius: 12px;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
    border: 1px solid #e5e7eb;
    margin-bottom: 20px;
}

.report-header {
    padding: 20px;
    border-bottom: 1px solid #e5e7eb;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.report-title {
    font-size: 1.25rem;
    font-weight: 700;
    color: #1f2937;
    margin: 0;
}

.report-actions {
    display: flex;
    gap: 8px;
}

.btn-report {
    padding: 8px 16px;
    border-radius: 8px;
    font-size: 0.875rem;
    font-weight: 500;
    border: 1px solid #d1d5db;
    background: white;
    color: #374151;
    cursor: pointer;
    transition: all 0.2s ease;
}

.btn-report:hover {
    background: #1f2937;
    color: white;
    border-color: #1f2937;
}

.btn-print { color: #059669; border-color: #059669; }
.btn-excel { color: #0891b2; border-color: #0891b2; }
.btn-pdf { color: #dc2626; border-color: #dc2626; }

.btn-print:hover { background: #059669; }
.btn-excel:hover { background: #0891b2; }
.btn-pdf:hover { background: #dc2626; }

.filter-section {
    padding: 16px 20px;
    background: #f9fafb;
    border-bottom: 1px solid #e5e7eb;
    display: flex;
    gap: 12px;
    align-items: center;
    flex-wrap: wrap;
}

.filter-input {
    padding: 8px 12px;
    border: 1px solid #d1d5db;
    border-radius: 6px;
    font-size: 0.875rem;
    min-width: 150px;
}

.date-range-form {
    background: white;
    padding: 20px;
    border-radius: 12px;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
    margin-bottom: 20px;
}

.form-row {
    display: flex;
    gap: 15px;
    align-items: end;
    flex-wrap: wrap;
}

.form-group {
    display: flex;
    flex-direction: column;
    gap: 5px;
}

.form-group label {
    font-weight: 600;
    color: #374151;
    font-size: 0.875rem;
}

.chart-container {
    background: white;
    padding: 20px;
    border-radius: 12px;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
    margin-bottom: 20px;
}

.summary-cards {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 20px;
    margin-bottom: 25px;
}

.summary-card {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 25px;
    border-radius: 15px;
    text-align: center;
    box-shadow: 0 8px 25px rgba(102, 126, 234, 0.3);
    border: 1px solid rgba(255, 255, 255, 0.1);
    transition: all 0.3s ease;
    position: relative;
    overflow: hidden;
}

.summary-card::before {
    content: "";
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: linear-gradient(45deg, rgba(255,255,255,0.1) 0%, transparent 50%, rgba(255,255,255,0.1) 100%);
    pointer-events: none;
}

.summary-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 12px 35px rgba(102, 126, 234, 0.4);
}

.summary-card:nth-child(1) {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    box-shadow: 0 8px 25px rgba(102, 126, 234, 0.3);
}

.summary-card:nth-child(2) {
    background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
    box-shadow: 0 8px 25px rgba(240, 147, 251, 0.3);
}

.summary-card:nth-child(3) {
    background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
    box-shadow: 0 8px 25px rgba(79, 172, 254, 0.3);
}

.summary-card:nth-child(4) {
    background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%);
    box-shadow: 0 8px 25px rgba(67, 233, 123, 0.3);
}

.summary-card h4 {
    margin: 0 0 15px 0;
    font-size: 1rem;
    font-weight: 600;
    opacity: 0.95;
    text-shadow: 0 1px 3px rgba(0,0,0,0.2);
    position: relative;
    z-index: 1;
}

.summary-card .value {
    font-size: 2rem;
    font-weight: 800;
    margin: 0;
    text-shadow: 0 2px 4px rgba(0,0,0,0.3);
    position: relative;
    z-index: 1;
    line-height: 1.2;
    word-break: break-word;
}

.summary-card .icon {
    position: absolute;
    top: 20px;
    right: 20px;
    width: 40px;
    height: 40px;
    background: rgba(255, 255, 255, 0.2);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.2rem;
    backdrop-filter: blur(10px);
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

@media print {
    .no-print { display: none !important; }
    .report-card { box-shadow: none; border: 1px solid #000; }
    body { background: white !important; }
}

@media (max-width: 768px) {
    .filter-section {
        flex-direction: column;
        align-items: stretch;
    }
    
    .form-row {
        flex-direction: column;
    }
    
    .filter-input {
        min-width: auto;
        width: 100%;
    }
    
    .summary-cards {
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 15px;
    }
    
    .summary-card {
        padding: 20px;
    }
    
    .summary-card .value {
        font-size: 1.5rem;
    }
    
    .summary-card .icon {
        width: 35px;
        height: 35px;
        font-size: 1rem;
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
                    <h6 class="h2 text-white d-inline-block mb-0">گزارشات پیشرفته</h6>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Page content -->
<div class="container-fluid mt--7 report-container">
    <!-- منوی اصلی گزارشات -->
    <div class="reports-menu" id="reportsMenu">
        <div class="menu-header">
            <h2><i class="fas fa-chart-line"></i> گزارشات پیشرفته</h2>
            <p class="subtitle">انتخاب نوع گزارش مورد نظر خود</p>
        </div>

        <div class="menu-grid">
            <a href="#" class="menu-item" onclick="showReport('custom')">
                <div class="menu-icon">
                    <i class="fas fa-calendar-alt"></i>
                </div>
                <div class="menu-content">
                    <h3 class="menu-title">گزارش سفارشی بین دو تاریخ</h3>
                    <p class="menu-description">انتخاب بازه زمانی دلخواه و دریافت گزارش اختصاصی</p>
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
                    <h3 class="menu-title">گزارش فروش تفصیلی ۳۰ روز گذشته</h3>
                    <p class="menu-description">نمایش جزئیات کامل فروش در یک ماه اخیر</p>
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
                    <h3 class="menu-title">محصولات پرفروش با موجودی کم</h3>
                    <p class="menu-description">لیست محصولات پرتقاضا که موجودی آنها کمتر از ۱۰ عدد است</p>
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
                    <p class="menu-description">آمار و دادههای جامع کل سیستم از شروع فعالیت تا امروز</p>
                </div>
                <div class="menu-arrow">
                    <i class="fas fa-chevron-left"></i>
                </div>
            </a>
        </div>
    </div>

    <!-- بخش گزارش سفارشی -->
    <div class="report-section" id="customReport">
        <button class="back-button" onclick="showMenu()">
            <i class="fas fa-arrow-right"></i> بازگشت به منو
        </button>

        <div class="date-range-form no-print">
            <h4 style="margin-bottom: 15px;"><i class="fas fa-calendar-alt"></i> گزارش سفارشی بین دو تاریخ</h4>
            <form id="customReportForm" onsubmit="generateCustomReport(event)">
                <div class="form-row">
                    <div class="form-group">
                        <label>تاریخ شروع:</label>
                        <input type="date" id="startDate" class="filter-input" required>
                    </div>
                    <div class="form-group">
                        <label>تاریخ پایان:</label>
                        <input type="date" id="endDate" class="filter-input" required>
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
                <p class="value" id="totalSales">0</p>
            </div>
            <div class="summary-card">
                <div class="icon"><i class="fas fa-file-invoice"></i></div>
                <h4>تعداد فاکتور</h4>
                <p class="value" id="totalInvoices">0</p>
            </div>
            <div class="summary-card">
                <div class="icon"><i class="fas fa-coins"></i></div>
                <h4>کل سود</h4>
                <p class="value" id="totalProfit">0</p>
            </div>
            <div class="summary-card">
                <div class="icon"><i class="fas fa-calculator"></i></div>
                <h4>میانگین فاکتور</h4>
                <p class="value" id="avgInvoice">0</p>
            </div>
        </div>

        <!-- نمودار -->
        <div class="chart-container no-print" id="chartContainer" style="display: none;">
            <canvas id="reportChart" width="400" height="200"></canvas>
        </div>

        <!-- گزارش فروش تفصیلی 30 روز گذشته -->
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
                <h3 class="report-title">گزارش فروش تفصیلی ۳۰ روز گذشته</h3>
                <div class="report-actions">
                    <button onclick="printReport('detailed-sales-report')" class="btn-report btn-print">
                        <i class="fas fa-print"></i> چاپ
                    </button>
                    <button onclick="exportToExcel('detailed-sales-report', 'گزارش-فروش-تفصیلی')"
                        class="btn-report btn-excel">
                        <i class="fas fa-file-excel"></i> اکسل
                    </button>
                    <button onclick="exportToPDF('detailed-sales-report', 'گزارش-فروش-تفصیلی')"
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
                                        <?= SettingsHelper::formatDate(strtotime($sale['sale_date']), $db) ?>
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

        <!-- محصولات پرفروش با موجودی کم -->
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

        <!-- گزارش کلی سیستم -->
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
                        <h4>کل تأمین کنندگان</h4>
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
                                <td><i class="fas fa-building text-info me-2"></i>تأمین کنندگان ثبت شده</td>
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
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <?php include 'includes/footer.php'; ?>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/3.9.1/chart.min.js"></script>

    <script>
        let reportChart = null;

        // مدیریت منو و بخشها
        function showMenu() {
            document.getElementById('reportsMenu').style.display = 'block';
            document.querySelectorAll('.report-section').forEach(section => {
                section.classList.remove('active');
                section.style.display = 'none';
            });
        }

        function showReport(reportType) {
            document.getElementById('reportsMenu').style.display = 'none';
            document.querySelectorAll('.report-section').forEach(section => {
                section.classList.remove('active');
                section.style.display = 'none';
            });

            const targetSection = document.getElementById(reportType + 'Report');
            if (targetSection) {
                targetSection.style.display = 'block';
                targetSection.classList.add('active');
            }
        }

        // Print function
        function printReport(reportId) {
            const printContent = document.getElementById(reportId).innerHTML;
            const originalContent = document.body.innerHTML;

            document.body.innerHTML = `
        <div style="direction: rtl; font-family: Tahoma, Arial, sans-serif; padding: 20px;">
            <h2 style="text-align: center; margin-bottom: 20px; border-bottom: 2px solid #000; padding-bottom: 10px;">گزارش فروشگاه قطعات موتورسیکلت</h2>
            <p style="text-align: center; margin-bottom: 30px;">تاریخ تولید گزارش: ${new Date().toLocaleDateString('fa-IR')}</p>
            ${printContent}
        </div>
    `;

            window.print();
            document.body.innerHTML = originalContent;
            location.reload();
        }

        // Export to Excel
        function exportToExcel(reportId, filename) {
            const table = document.querySelector(`#${reportId} table`);
            const wb = XLSX.utils.table_to_book(table, { sheet: "گزارش" });
            XLSX.writeFile(wb, `${filename}-${new Date().toISOString().split('T')[0]}.xlsx`);
        }

        // Export to PDF
        function exportToPDF(reportId, filename) {
            const element = document.getElementById(reportId);
            const opt = {
                margin: 0.5,
                filename: `${filename}-${new Date().toISOString().split('T')[0]}.pdf`,
                image: { type: 'jpeg', quality: 0.98 },
                html2canvas: { scale: 2, useCORS: true },
                jsPDF: { unit: 'in', format: 'a4', orientation: 'portrait' }
            };
            html2pdf().set(opt).from(element).save();
        }

        // Live Search
        function liveSearch(tableId, searchValue) {
            const table = document.getElementById(tableId);
            const rows = table.getElementsByTagName('tbody')[0].getElementsByTagName('tr');
            let visibleCount = 0;

            for (let i = 0; i < rows.length; i++) {
                const row = rows[i];
                const text = row.textContent.toLowerCase();
                const isVisible = text.includes(searchValue.toLowerCase());
                row.style.display = isVisible ? '' : 'none';
                if (isVisible) visibleCount++;
            }

            updateSummary(tableId);
        }

        // Filter by date
        function filterByDate(tableId, dateValue) {
            const table = document.getElementById(tableId);
            const rows = table.getElementsByTagName('tbody')[0].getElementsByTagName('tr');

            for (let i = 0; i < rows.length; i++) {
                const row = rows[i];
                const dateCell = row.querySelector('[data-date]');
                if (dateCell) {
                    const rowDate = new Date(dateCell.getAttribute('data-date')).toISOString().split('T')[0];
                    row.style.display = !dateValue || rowDate === dateValue ? '' : 'none';
                }
            }

            updateSummary(tableId);
        }

        // Filter by stock level
        function filterByStock(tableId, stockLevel) {
            const table = document.getElementById(tableId);
            const rows = table.getElementsByTagName('tbody')[0].getElementsByTagName('tr');

            for (let i = 0; i < rows.length; i++) {
                const row = rows[i];
                const stockCell = row.querySelector('[data-stock]');
                if (stockCell) {
                    const stock = parseInt(stockCell.getAttribute('data-stock'));
                    let show = true;

                    if (stockLevel === 'critical') show = stock <= 5;
                    else if (stockLevel === 'low') show = stock >= 6 && stock <= 10;

                    row.style.display = show ? '' : 'none';
                }
            }
        }

        // Sort table
        function sortTable(tableId, sortType) {
            const table = document.getElementById(tableId);
            const tbody = table.getElementsByTagName('tbody')[0];
            const rows = Array.from(tbody.getElementsByTagName('tr'));

            rows.sort((a, b) => {
                if (sortType.includes('date')) {
                    const dateA = new Date(a.querySelector('[data-date]').getAttribute('data-date'));
                    const dateB = new Date(b.querySelector('[data-date]').getAttribute('data-date'));
                    return sortType.includes('desc') ? dateB - dateA : dateA - dateB;
                } else if (sortType.includes('amount')) {
                    const amountA = parseFloat(a.querySelector('[data-amount]').getAttribute('data-amount'));
                    const amountB = parseFloat(b.querySelector('[data-amount]').getAttribute('data-amount'));
                    return sortType.includes('desc') ? amountB - amountA : amountA - amountB;
                } else if (sortType.includes('product')) {
                    const productA = a.querySelector('[data-product]').getAttribute('data-product');
                    const productB = b.querySelector('[data-product]').getAttribute('data-product');
                    return sortType.includes('desc') ? productB.localeCompare(productA) : productA.localeCompare(productB);
                }
            });

            rows.forEach(row => tbody.appendChild(row));
            updateSummary(tableId);
        }

        // Update summary based on visible rows
        function updateSummary(tableId) {
            const table = document.getElementById(tableId);
            const rows = table.getElementsByTagName('tbody')[0].getElementsByTagName('tr');
            let totalAmount = 0, totalProfit = 0, totalQuantity = 0, visibleRows = 0;

            for (let i = 0; i < rows.length; i++) {
                const row = rows[i];
                if (row.style.display !== 'none') {
                    visibleRows++;

                    const amountCell = row.querySelector('[data-amount]');
                    if (amountCell) totalAmount += parseFloat(amountCell.getAttribute('data-amount'));

                    const profitCell = row.querySelector('[data-profit]');
                    if (profitCell) totalProfit += parseFloat(profitCell.getAttribute('data-profit'));

                    const quantityCell = row.cells[2];
                    if (quantityCell && !isNaN(quantityCell.textContent)) {
                        totalQuantity += parseInt(quantityCell.textContent);
                    }
                }
            }

            // Update footer
            const totalAmountEl = document.getElementById('totalAmount');
            if (totalAmountEl) totalAmountEl.textContent = totalAmount.toLocaleString() + ' افغانی';

            const totalProfitEl = document.getElementById('totalProfitAmount');
            if (totalProfitEl) {
                totalProfitEl.textContent = totalProfit.toLocaleString() + ' افغانی';
                totalProfitEl.className = totalProfit > 0 ? 'text-success' : 'text-danger';
            }

            const totalQuantityEl = document.getElementById('totalQuantity');
            if (totalQuantityEl) totalQuantityEl.textContent = totalQuantity.toLocaleString();
        }

        // Save filters
        function saveFilters(reportType) {
            const filters = {
                search: document.getElementById(`${reportType}Search`)?.value || '',
                date: document.getElementById(`${reportType}DateFilter`)?.value || '',
                sort: document.getElementById(`${reportType}SortOrder`)?.value || ''
            };

            localStorage.setItem(`${reportType}_filters`, JSON.stringify(filters));
            alert('فیلترها ذخیره شد');
        }

        // Load filters
        function loadFilters(reportType) {
            const saved = localStorage.getItem(`${reportType}_filters`);
            if (saved) {
                const filters = JSON.parse(saved);

                const searchEl = document.getElementById(`${reportType}Search`);
                if (searchEl) {
                    searchEl.value = filters.search;
                    liveSearch(`${reportType}-table`, filters.search);
                }

                const dateEl = document.getElementById(`${reportType}DateFilter`);
                if (dateEl) {
                    dateEl.value = filters.date;
                    filterByDate(`${reportType}-table`, filters.date);
                }

                const sortEl = document.getElementById(`${reportType}SortOrder`);
                if (sortEl) {
                    sortEl.value = filters.sort;
                    sortTable(`${reportType}-table`, filters.sort);
                }

                alert('فیلترها بارگذاری شد');
            } else {
                alert('فیلتر ذخیره شده‌ای یافت نشد');
            }
        }

        // Generate custom report
        async function generateCustomReport(event) {
            event.preventDefault();

            const startDate = document.getElementById('startDate').value;
            const endDate = document.getElementById('endDate').value;
            const reportType = document.getElementById('reportType').value;
            const categoryFilter = document.getElementById('categoryFilter').value;

            if (!startDate || !endDate) {
                alert('لطفاً تاریخ شروع و پایان را انتخاب کنید');
                return;
            }

            try {
                const response = await fetch('api/custom_report.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        start_date: startDate,
                        end_date: endDate,
                        report_type: reportType,
                        category_id: categoryFilter
                    })
                });

                const data = await response.json();

                if (data.success) {
                    displayCustomReport(data.data, reportType, startDate, endDate);
                    updateSummaryCards(data.summary);
                    createChart(data.chart_data, reportType);
                } else {
                    alert('خطا در تولید گزارش: ' + data.message);
                }
            } catch (error) {
                alert('خطا در ارتباط با سرور');
            }
        }

        // Display custom report
        function displayCustomReport(data, reportType, startDate, endDate) {
            const reportTitle = {
                'sales': 'گزارش فروش کلی',
                'bestsellers': 'گزارش محصولات پرفروش',
                'inventory': 'گزارش موجودی کالاها',
                'profit': 'گزارش سود و زیان'
            };

            document.getElementById('customReportTitle').textContent =
                `${reportTitle[reportType]} از ${startDate} تا ${endDate}`;

            const headers = getReportHeaders(reportType);
            const headerHtml = '<tr>' + headers.map(h => `<th>${h}</th>`).join('') + '</tr>';
            document.getElementById('customReportHeader').innerHTML = headerHtml;

            const bodyHtml = data.map(row => {
                return '<tr>' + Object.values(row).map(val => `<td>${val}</td>`).join('') + '</tr>';
            }).join('');
            document.getElementById('customReportBody').innerHTML = bodyHtml;

            document.getElementById('customReportResult').style.display = 'block';
        }

        // Get report headers based on type
        function getReportHeaders(reportType) {
            const headers = {
                'sales': ['تاریخ', 'شماره فاکتور', 'مشتری', 'مبلغ کل', 'تخفیف', 'مبلغ نهایی'],
                'bestsellers': ['نام محصول', 'تعداد فروش', 'درآمد کل', 'سود'],
                'inventory': ['نام محصول', 'دسته بندی', 'موجودی فعلی', 'حداقل موجودی', 'وضعیت'],
                'profit': ['تاریخ', 'درآمد', 'هزینه', 'سود خالص', 'درصد سود']
            };
            return headers[reportType] || [];
        }

        // Update summary cards
        function updateSummaryCards(summary) {
            document.getElementById('totalSales').textContent = (summary.total_sales || 0).toLocaleString() + ' افغانی';
            document.getElementById('totalInvoices').textContent = (summary.total_invoices || 0).toLocaleString() + ' عدد';
            document.getElementById('totalProfit').textContent = (summary.total_profit || 0).toLocaleString() + ' افغانی';
            document.getElementById('avgInvoice').textContent = (summary.avg_invoice || 0).toLocaleString() + ' افغانی';

            document.getElementById('summaryCards').style.display = 'grid';
        }

        // Create chart
        function createChart(chartData, reportType) {
            const ctx = document.getElementById('reportChart').getContext('2d');

            if (reportChart) {
                reportChart.destroy();
            }

            const chartConfig = {
                'sales': { type: 'line', label: 'فروش روزانه' },
                'bestsellers': { type: 'bar', label: 'تعداد فروش' },
                'inventory': { type: 'doughnut', label: 'وضعیت موجودی' },
                'profit': { type: 'line', label: 'سود روزانه' }
            };

            const config = chartConfig[reportType];

            reportChart = new Chart(ctx, {
                type: config.type,
                data: {
                    labels: chartData.labels || [],
                    datasets: [{
                        label: config.label,
                        data: chartData.data || [],
                        backgroundColor: config.type === 'doughnut' ?
                            ['#1f2937', '#374151', '#4b5563', '#6b7280', '#9ca3af'] :
                            'rgba(31, 41, 55, 0.1)',
                        borderColor: '#1f2937',
                        borderWidth: 2,
                        fill: config.type === 'line'
                    }]
                },
                options: {
                    responsive: true,
                    plugins: {
                        legend: {
                            position: config.type === 'doughnut' ? 'bottom' : 'top'
                        }
                    },
                    scales: config.type !== 'doughnut' ? {
                        y: {
                            beginAtZero: true
                        }
                    } : {}
                }
            });

            document.getElementById('chartContainer').style.display = 'block';
        }

        // Initialize
        document.addEventListener('DOMContentLoaded', function () {
            // Set default dates
            const today = new Date();
            const thirtyDaysAgo = new Date(today.getTime() - (30 * 24 * 60 * 60 * 1000));

            document.getElementById('endDate').value = today.toISOString().split('T')[0];
            document.getElementById('startDate').value = thirtyDaysAgo.toISOString().split('T')[0];

            // نمایش منوی اصلی در ابتدا
            showMenu();

            // Update summaries on load
            updateSummary('detailed-sales-table');
        });
    </script>