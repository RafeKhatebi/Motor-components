<?php
require_once 'init_security.php';
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

require_once 'config/database.php';
require_once 'includes/SettingsHelper.php';
$database = new Database();
$db = $database->getConnection();
SettingsHelper::loadSettings($db);

$page_title = 'داشبورد';

// آمار کلی
$stats = [];

$query = "SELECT COUNT(*) as count FROM products";
$stmt = $db->prepare($query);
$stmt->execute();
$stats['products'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'];

$query = "SELECT COALESCE(SUM(final_amount), 0) as total FROM sales WHERE DATE(created_at) = CURDATE() AND (status IS NULL OR status != 'returned')";
$stmt = $db->prepare($query);
$stmt->execute();
$stats['today_sales'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

// محاسبه کل تراکنشات مالی امروز
try {
    $query = "SELECT COALESCE(SUM(amount), 0) as total FROM expense_transactions WHERE DATE(transaction_date) = CURDATE()";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $stats['today_transactions'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
} catch (PDOException $e) {
    $stats['today_transactions'] = 0;
}

// محاسبه فایده خالص امروز (فروش منهای تراکنشات)
$stats['today_profit'] = $stats['today_sales'] - $stats['today_transactions'];

// دادههای نمودار فروش روزانه (7 روز گذشته)
$sales_chart_query = "SELECT DATE(created_at) as date, COALESCE(SUM(final_amount), 0) as total 
                      FROM sales 
                      WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY) 
                      AND (status IS NULL OR status != 'returned')
                      GROUP BY DATE(created_at) 
                      ORDER BY date ASC";
$sales_chart_stmt = $db->prepare($sales_chart_query);
$sales_chart_stmt->execute();
$sales_chart_data = $sales_chart_stmt->fetchAll(PDO::FETCH_ASSOC);

// محصولات پرفروش (5 محصول برتر)
$top_products_query = "SELECT p.name, SUM(si.quantity) as total_sold 
                       FROM sale_items si 
                       JOIN products p ON si.product_id = p.id 
                       GROUP BY si.product_id 
                       ORDER BY total_sold DESC 
                       LIMIT 5";
$top_products_stmt = $db->prepare($top_products_query);
$top_products_stmt->execute();
$top_products_data = $top_products_stmt->fetchAll(PDO::FETCH_ASSOC);

$extra_css = '
<link rel="stylesheet" href="assets/css/quick-sale.css">
<style>
.dashboard-container {
    background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
    min-height: 100vh;
    padding: 0;
    margin-top: -8px;
}

.dashboard-header {
    background: linear-gradient(135deg, #f8fafc, #e2e8f0);
    border-radius: 0 0 20px 20px;
    padding: 20px 30px;
    margin-bottom: 30px;
    color: #1f2937;
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
    border: 1px solid #e5e7eb;
}

.dashboard-title {
    font-size: 2rem;
    font-weight: 700;
    margin-bottom: 8px;
}

.dashboard-subtitle {
    opacity: 0.9;
    font-size: 1.1rem;
}

.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
    gap: 20px;
    margin-bottom: 30px;
}

.stat-card {
    background: white;
    border-radius: 16px;
    padding: 16px;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
    border: 1px solid rgba(0, 0, 0, 0.05);
    transition: all 0.3s ease;
    position: relative;
    overflow: hidden;
    min-height: 120px;
}

.stat-card::before {
    content: "";
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 4px;
    background: var(--gradient);
}

.stat-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 8px 30px rgba(0, 0, 0, 0.12);
}

.stat-card.primary::before { background: linear-gradient(90deg, #4f46e5, #7c3aed); }
.stat-card.success::before { background: linear-gradient(90deg, #10b981, #059669); }
.stat-card.warning::before { background: linear-gradient(90deg, #f59e0b, #d97706); }
.stat-card.danger::before { background: linear-gradient(90deg, #ef4444, #dc2626); }
.stat-card.info::before { background: linear-gradient(90deg, #06b6d4, #0891b2); }

.stat-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 8px;
}

.stat-icon {
    width: 48px;
    height: 48px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.3rem;
    color: white;
}

.stat-icon.primary { background: linear-gradient(135deg, #4f46e5, #7c3aed); }
.stat-icon.success { background: linear-gradient(135deg, #10b981, #059669); }
.stat-icon.warning { background: linear-gradient(135deg, #f59e0b, #d97706); }
.stat-icon.danger { background: linear-gradient(135deg, #ef4444, #dc2626); }
.stat-icon.info { background: linear-gradient(135deg, #06b6d4, #0891b2); }

.stat-title {
    font-size: 0.8rem;
    font-weight: 600;
    color: #6b7280;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    margin-bottom: 4px;
}

.stat-value {
    font-size: 1.6rem;
    font-weight: 800;
    color: #1f2937;
    margin-bottom: 6px;
    line-height: 1.2;
}

.stat-change {
    font-size: 0.875rem;
    display: flex;
    align-items: center;
    gap: 4px;
}

.stat-change.positive { color: #059669; }
.stat-change.negative { color: #dc2626; }

.charts-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 15px;
    margin-bottom: 30px;
}

@media (max-width: 1200px) {
    .charts-grid {
        grid-template-columns: repeat(2, 1fr);
    }
}

.chart-card {
    background: white;
    border-radius: 16px;
    padding: 24px;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
    border: 1px solid rgba(0, 0, 0, 0.05);
}

.chart-header {
    margin-bottom: 20px;
    padding-bottom: 16px;
    border-bottom: 1px solid #e5e7eb;
}

.chart-title {
    font-size: 1.25rem;
    font-weight: 700;
    color: #1f2937;
    margin-bottom: 4px;
}

.chart-subtitle {
    font-size: 0.875rem;
    color: #6b7280;
}

.recent-sales {
    background: white;
    border-radius: 16px;
    padding: 24px;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
    border: 1px solid rgba(0, 0, 0, 0.05);
}

.table-modern {
    border-collapse: separate;
    border-spacing: 0;
}

.table-modern thead th {
    background: #f8fafc;
    border: none;
    padding: 16px;
    font-weight: 600;
    color: #374151;
    font-size: 0.875rem;
}

.table-modern tbody td {
    padding: 16px;
    border-top: 1px solid #e5e7eb;
    vertical-align: middle;
}

.table-modern tbody tr:hover {
    background: #f9fafb;
}

@media (max-width: 768px) {
    .charts-grid {
        grid-template-columns: 1fr;
    }
    
    .stats-grid {
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    }
}

@media (max-width: 768px) {
    .dashboard-header {
        padding: 20px;
        margin-bottom: 20px;
    }
    
    .dashboard-title {
        font-size: 1.5rem;
    }
    
    .stats-grid {
        grid-template-columns: 1fr;
        gap: 16px;
    }
    
    .stat-card {
        padding: 14px;
        min-height: 100px;
    }
    
    .stat-value {
        font-size: 1.4rem;
    }
    
    .stat-icon {
        width: 40px;
        height: 40px;
        font-size: 1.1rem;
    }
    
    .chart-card, .recent-sales {
        padding: 20px;
    }
    
    .recent-sales .d-flex {
        flex-direction: column;
        gap: 15px;
    }
    
    .recent-sales .d-flex .btn {
        width: 100%;
        max-width: 200px;
    }
    
    #recentSalesSearch {
        width: 100% !important;
        max-width: none !important;
    }
}
</style>
';

include 'includes/header.php';
?>

<div class="dashboard-container">
    <div class="container-fluid">
        <!-- Stats Grid -->
        <div class="stats-grid">
            <div class="stat-card primary">
                <div class="stat-header">
                    <div>
                        <div class="stat-title">کل محصولات</div>
                        <div class="stat-value"><?= number_format($stats['products']) ?></div>
                        <div class="stat-change positive">
                            <i class="fas fa-arrow-up"></i>
                            <span>3.48% نسبت به ماه گذشته</span>
                        </div>
                    </div>
                    <div class="stat-icon primary">
                        <i class="fas fa-box"></i>
                    </div>
                </div>
            </div>



            <div class="stat-card warning">
                <div class="stat-header">
                    <div>
                        <div class="stat-title">فروش امروز</div>
                        <div class="stat-value"><?= number_format($stats['today_sales']) ?></div>
                        <div class="stat-change positive">
                            <i class="fas fa-arrow-up"></i>
                            <span>12.5% نسبت به دیروز</span>
                        </div>
                    </div>
                    <div class="stat-icon warning">
                        <i class="fas fa-chart-line"></i>
                    </div>
                </div>
            </div>

            <div class="stat-card info">
                <div class="stat-header">
                    <div>
                        <div class="stat-title">مصارف امروز</div>
                        <div class="stat-value"><?= number_format($stats['today_transactions']) ?></div>
                        <div class="stat-change negative">
                            <i class="fas fa-arrow-down"></i>
                            <span>خروجی مالی</span>
                        </div>
                    </div>
                    <div class="stat-icon info">
                        <i class="fas fa-money-bill-wave"></i>
                    </div>
                </div>
            </div>

            <div class="stat-card <?= $stats['today_profit'] >= 0 ? 'success' : 'danger' ?>">
                <div class="stat-header">
                    <div>
                        <div class="stat-title">فایده خالص امروز</div>
                        <div class="stat-value"><?= number_format($stats['today_profit']) ?></div>
                        <div class="stat-change <?= $stats['today_profit'] >= 0 ? 'positive' : 'negative' ?>">
                            <i class="fas fa-<?= $stats['today_profit'] >= 0 ? 'arrow-up' : 'arrow-down' ?>"></i>
                            <span><?= $stats['today_profit'] >= 0 ? 'فایده آور' : 'زیانده' ?></span>
                        </div>
                    </div>
                    <div class="stat-icon <?= $stats['today_profit'] >= 0 ? 'success' : 'danger' ?>">
                        <i class="fas fa-<?= $stats['today_profit'] >= 0 ? 'chart-line' : 'chart-line-down' ?>"></i>
                    </div>
                </div>
            </div>


        </div>

        <!-- Quick Actions Card -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card quick-actions-card">
                    <div class="card-header">
                        <h3>عملیات سریع</h3>
                    </div>
                    <div class="card-body">
                        <div style="display: grid; grid-template-columns: repeat(8, 1fr); gap: 10px;">
                            <a href="sales.php" style="background: linear-gradient(135deg, #f8fafc, #e2e8f0); border: 1px solid #e5e7eb; border-radius: 8px; padding: 12px 8px; text-align: center; cursor: pointer; transition: all 0.3s ease; text-decoration: none; color: #374151; display: flex; flex-direction: column; align-items: center; gap: 4px; min-height: 80px;" onmouseover="this.style.background='linear-gradient(135deg, #667eea, #764ba2)'; this.style.color='white';" onmouseout="this.style.background='linear-gradient(135deg, #f8fafc, #e2e8f0)'; this.style.color='#374151';">
                                <i class="fas fa-shopping-cart" style="font-size: 16px; margin-bottom: 4px;"></i>
                                <span style="font-weight: 600; font-size: 11px; line-height: 1.2;">فروش سریع</span>
                                <kbd style="background: rgba(0, 0, 0, 0.1); padding: 1px 4px; border-radius: 3px; font-size: 8px; margin-top: 2px;">F2</kbd>
                            </a>
                            <a href="products.php#add" style="background: linear-gradient(135deg, #f8fafc, #e2e8f0); border: 1px solid #e5e7eb; border-radius: 8px; padding: 12px 8px; text-align: center; cursor: pointer; transition: all 0.3s ease; text-decoration: none; color: #374151; display: flex; flex-direction: column; align-items: center; gap: 4px; min-height: 80px;" onmouseover="this.style.background='linear-gradient(135deg, #667eea, #764ba2)'; this.style.color='white';" onmouseout="this.style.background='linear-gradient(135deg, #f8fafc, #e2e8f0)'; this.style.color='#374151';">
                                <i class="fas fa-plus" style="font-size: 16px; margin-bottom: 4px;"></i>
                                <span style="font-weight: 600; font-size: 11px; line-height: 1.2;">محصول جدید</span>
                                <kbd style="background: rgba(0, 0, 0, 0.1); padding: 1px 4px; border-radius: 3px; font-size: 8px; margin-top: 2px;">Ctrl+P</kbd>
                            </a>
                            <a href="customers.php" style="background: linear-gradient(135deg, #f8fafc, #e2e8f0); border: 1px solid #e5e7eb; border-radius: 8px; padding: 12px 8px; text-align: center; cursor: pointer; transition: all 0.3s ease; text-decoration: none; color: #374151; display: flex; flex-direction: column; align-items: center; gap: 4px; min-height: 80px;" onmouseover="this.style.background='linear-gradient(135deg, #667eea, #764ba2)'; this.style.color='white';" onmouseout="this.style.background='linear-gradient(135deg, #f8fafc, #e2e8f0)'; this.style.color='#374151';">
                                <i class="fas fa-user-plus" style="font-size: 16px; margin-bottom: 4px;"></i>
                                <span style="font-weight: 600; font-size: 11px; line-height: 1.2;">مشتری جدید</span>
                                <kbd style="background: rgba(0, 0, 0, 0.1); padding: 1px 4px; border-radius: 3px; font-size: 8px; margin-top: 2px;">Ctrl+C</kbd>
                            </a>
                            <a href="purchases.php" style="background: linear-gradient(135deg, #f8fafc, #e2e8f0); border: 1px solid #e5e7eb; border-radius: 8px; padding: 12px 8px; text-align: center; cursor: pointer; transition: all 0.3s ease; text-decoration: none; color: #374151; display: flex; flex-direction: column; align-items: center; gap: 4px; min-height: 80px;" onmouseover="this.style.background='linear-gradient(135deg, #667eea, #764ba2)'; this.style.color='white';" onmouseout="this.style.background='linear-gradient(135deg, #f8fafc, #e2e8f0)'; this.style.color='#374151';">
                                <i class="fas fa-shopping-bag" style="font-size: 16px; margin-bottom: 4px;"></i>
                                <span style="font-weight: 600; font-size: 11px; line-height: 1.2;">خرید جدید</span>
                                <kbd style="background: rgba(0, 0, 0, 0.1); padding: 1px 4px; border-radius: 3px; font-size: 8px; margin-top: 2px;">Ctrl+B</kbd>
                            </a>
                            <a href="transactions.php" style="background: linear-gradient(135deg, #f8fafc, #e2e8f0); border: 1px solid #e5e7eb; border-radius: 8px; padding: 12px 8px; text-align: center; cursor: pointer; transition: all 0.3s ease; text-decoration: none; color: #374151; display: flex; flex-direction: column; align-items: center; gap: 4px; min-height: 80px;" onmouseover="this.style.background='linear-gradient(135deg, #667eea, #764ba2)'; this.style.color='white';" onmouseout="this.style.background='linear-gradient(135deg, #f8fafc, #e2e8f0)'; this.style.color='#374151';">
                                <i class="fas fa-money-bill-wave" style="font-size: 16px; margin-bottom: 4px;"></i>
                                <span style="font-weight: 600; font-size: 11px; line-height: 1.2;">مصارف</span>
                                <kbd style="background: rgba(0, 0, 0, 0.1); padding: 1px 4px; border-radius: 3px; font-size: 8px; margin-top: 2px;">Ctrl+E</kbd>
                            </a>
                            <a href="reports.php#lowstock" onclick="setTimeout(() => showReport('lowstock'), 100)" style="background: linear-gradient(135deg, #f8fafc, #e2e8f0); border: 1px solid #e5e7eb; border-radius: 8px; padding: 12px 8px; text-align: center; cursor: pointer; transition: all 0.3s ease; text-decoration: none; color: #374151; display: flex; flex-direction: column; align-items: center; gap: 4px; min-height: 80px;" onmouseover="this.style.background='linear-gradient(135deg, #667eea, #764ba2)'; this.style.color='white';" onmouseout="this.style.background='linear-gradient(135deg, #f8fafc, #e2e8f0)'; this.style.color='#374151';">
                                <i class="fas fa-warehouse" style="font-size: 16px; margin-bottom: 4px;"></i>
                                <span style="font-weight: 600; font-size: 11px; line-height: 1.2;">موجودی کم</span>
                                <kbd style="background: rgba(0, 0, 0, 0.1); padding: 1px 4px; border-radius: 3px; font-size: 8px; margin-top: 2px;">Ctrl+I</kbd>
                            </a>
                            <a href="reports.php" style="background: linear-gradient(135deg, #f8fafc, #e2e8f0); border: 1px solid #e5e7eb; border-radius: 8px; padding: 12px 8px; text-align: center; cursor: pointer; transition: all 0.3s ease; text-decoration: none; color: #374151; display: flex; flex-direction: column; align-items: center; gap: 4px; min-height: 80px;" onmouseover="this.style.background='linear-gradient(135deg, #667eea, #764ba2)'; this.style.color='white';" onmouseout="this.style.background='linear-gradient(135deg, #f8fafc, #e2e8f0)'; this.style.color='#374151';">
                                <i class="fas fa-chart-line" style="font-size: 16px; margin-bottom: 4px;"></i>
                                <span style="font-weight: 600; font-size: 11px; line-height: 1.2;">گزارشات</span>
                                <kbd style="background: rgba(0, 0, 0, 0.1); padding: 1px 4px; border-radius: 3px; font-size: 8px; margin-top: 2px;">Ctrl+R</kbd>
                            </a>
                            <a href="backup.php" style="background: linear-gradient(135deg, #f8fafc, #e2e8f0); border: 1px solid #e5e7eb; border-radius: 8px; padding: 12px 8px; text-align: center; cursor: pointer; transition: all 0.3s ease; text-decoration: none; color: #374151; display: flex; flex-direction: column; align-items: center; gap: 4px; min-height: 80px;" onmouseover="this.style.background='linear-gradient(135deg, #667eea, #764ba2)'; this.style.color='white';" onmouseout="this.style.background='linear-gradient(135deg, #f8fafc, #e2e8f0)'; this.style.color='#374151';">
                                <i class="fas fa-database" style="font-size: 16px; margin-bottom: 4px;"></i>
                                <span style="font-weight: 600; font-size: 11px; line-height: 1.2;">پشتیبان</span>
                                <kbd style="background: rgba(0, 0, 0, 0.1); padding: 1px 4px; border-radius: 3px; font-size: 8px; margin-top: 2px;">Ctrl+S</kbd>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Charts Grid -->
        <div class="charts-grid" style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 15px;">
            <div class="chart-card" style="padding: 16px;">
                <div class="chart-header" style="margin-bottom: 12px;">
                    <h3 class="chart-title" style="font-size: 1rem; margin-bottom: 2px;">روند فروش هفتگی</h3>
                    <p class="chart-subtitle" style="font-size: 0.75rem;">فروش روزانه در 7 روز گذشته</p>
                </div>
                <div class="chart">
                    <canvas id="chart-sales" style="max-height: 180px;"></canvas>
                </div>
            </div>

            <div class="chart-card" style="padding: 16px;">
                <div class="chart-header" style="margin-bottom: 12px;">
                    <h3 class="chart-title" style="font-size: 1rem; margin-bottom: 2px;">محصولات پرفروش</h3>
                    <p class="chart-subtitle" style="font-size: 0.75rem;">5 محصول برتر</p>
                </div>
                <div class="chart">
                    <canvas id="chart-orders" style="max-height: 180px;"></canvas>
                </div>
            </div>
            
            <div class="chart-card" style="padding: 16px;">
                <div class="chart-header" style="margin-bottom: 12px;">
                    <h3 class="chart-title" style="font-size: 1rem; margin-bottom: 2px;">روند موجودی</h3>
                    <p class="chart-subtitle" style="font-size: 0.75rem;">وضعیت موجودی کالاها</p>
                </div>
                <div class="chart">
                    <canvas id="chart-inventory" style="max-height: 180px;"></canvas>
                </div>
            </div>
            
            <div class="chart-card" style="padding: 16px;">
                <div class="chart-header" style="margin-bottom: 12px;">
                    <h3 class="chart-title" style="font-size: 1rem; margin-bottom: 2px;">روند فایده</h3>
                    <p class="chart-subtitle" style="font-size: 0.75rem;">فایده روزانه 7 روز گذشته</p>
                </div>
                <div class="chart">
                    <canvas id="chart-profit" style="max-height: 180px;"></canvas>
                </div>
            </div>
        </div>

        <!-- Recent Sales -->
        <div class="recent-sales">
            <div class="chart-header">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h3 class="chart-title">آخرین فروشها</h3>
                        <p class="chart-subtitle">فهرست 5 فاکتور آخر</p>
                    </div>
                    <div class="d-flex align-items-center gap-2">
                        <input type="text" class="form-control form-control-sm" placeholder="جستجو..."
                            id="recentSalesSearch" style="width: 150px;">
                        <a href="sales.php" class="btn btn-primary btn-sm">
                            <i class="fas fa-eye me-1"></i>
                            مشاهده همه
                        </a>
                    </div>
                </div>
            </div>
            <div class="table-responsive">
                <table class="table table-modern" id="recentSalesTable">
                    <thead>
                        <tr>
                            <th>فاکتور</th>
                            <th>مشتری</th>
                            <th>مبلغ</th>
                            <th>تاریخ</th>
                            <th>عملیات</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $query = "SELECT s.id, c.name as customer_name, s.final_amount, s.created_at 
                                 FROM sales s 
                                 LEFT JOIN customers c ON s.customer_id = c.id 
                                 WHERE (s.status IS NULL OR s.status != 'returned')
                                 ORDER BY s.created_at DESC LIMIT 5";
                        $stmt = $db->prepare($query);
                        $stmt->execute();

                        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)): ?>
                            <tr>
                                <td><strong>#<?= sanitizeOutput($row['id']) ?></strong></td>
                                <td><?= sanitizeOutput($row['customer_name'] ?: 'مشتری نقدی') ?></td>
                                <td><span class="badge bg-success"><?= number_format($row['final_amount']) ?> افغانی</span>
                                </td>
                                <td><?= SettingsHelper::formatDateTime(strtotime($row['created_at']), $db) ?></td>
                                <td>
                                    <div class="btn-group btn-group-sm" role="group">
                                        <a href="print_invoice.php?id=<?= $row['id'] ?>" class="btn btn-outline-info btn-sm"
                                            target="_blank" title="چاپ فاکتور">
                                            <i class="fas fa-print"></i>
                                        </a>
                                        <button onclick="viewSale(<?= $row['id'] ?>)" class="btn btn-outline-primary btn-sm" title="مشاهده جزئیات">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                        <button onclick="editSale(<?= $row['id'] ?>)" class="btn btn-outline-warning btn-sm" title="ویرایش فاکتور">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>

<script src="assets/js/chart.js"></script>
<script src="assets/js/quick-sale.js"></script>
<script>
    // نمودار فروش روزانه
    if (document.getElementById("chart-sales")) {
        const ctx = document.getElementById("chart-sales").getContext("2d");
        new Chart(ctx, {
            type: "line",
            data: {
                labels: [<?php foreach ($sales_chart_data as $data)
                    echo "'" . date('m/d', strtotime($data['date'])) . "',"; ?>],
                datasets: [{
                    label: "فروش روزانه",
                    data: [<?php foreach ($sales_chart_data as $data)
                        echo $data['total'] . ','; ?>],
                    borderColor: "#4f46e5",
                    backgroundColor: "rgba(79, 70, 229, 0.1)",
                    borderWidth: 3,
                    fill: true,
                    tension: 0.4
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: {
                            color: 'rgba(0,0,0,0.1)'
                        }
                    },
                    x: {
                        grid: {
                            display: false
                        }
                    }
                }
            }
        });
    }

    // نمودار محصولات پرفروش
    if (document.getElementById("chart-orders")) {
        const ctx = document.getElementById("chart-orders").getContext("2d");
        new Chart(ctx, {
            type: "doughnut",
            data: {
                labels: [<?php foreach ($top_products_data as $product)
                    echo "'" . $product['name'] . "',"; ?>],
                datasets: [{
                    data: [<?php foreach ($top_products_data as $product)
                        echo $product['total_sold'] . ','; ?>],
                    backgroundColor: ["#4f46e5", "#06b6d4", "#10b981", "#f59e0b", "#ef4444"]
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: "bottom",
                        labels: {
                            font: {
                                size: 10
                            }
                        }
                    }
                }
            }
        });
    }
    
    // نمودار وضعیت موجودی
    if (document.getElementById("chart-inventory")) {
        const ctx = document.getElementById("chart-inventory").getContext("2d");
        new Chart(ctx, {
            type: "bar",
            data: {
                labels: ["موجود", "کم", "بحرانی"],
                datasets: [{
                    label: "تعداد محصول",
                    data: [80, 15, 5],
                    backgroundColor: ["#10b981", "#f59e0b", "#ef4444"]
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });
    }
    
    // نمودار روند فایده
    if (document.getElementById("chart-profit")) {
        const ctx = document.getElementById("chart-profit").getContext("2d");
        new Chart(ctx, {
            type: "line",
            data: {
                labels: [<?php foreach ($sales_chart_data as $data)
                    echo "'" . date('m/d', strtotime($data['date'])) . "',"; ?>],
                datasets: [{
                    label: "فایده روزانه",
                    data: [<?php foreach ($sales_chart_data as $data)
                        echo ($data['total'] * 0.2) . ','; ?>],
                    borderColor: "#10b981",
                    backgroundColor: "rgba(16, 185, 129, 0.1)",
                    borderWidth: 2,
                    fill: true,
                    tension: 0.4
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true
                    },
                    x: {
                        grid: {
                            display: false
                        }
                    }
                }
            }
        });
    }

    // Search functionality for recent sales
    document.getElementById('recentSalesSearch').addEventListener('keyup', function () {
        const filter = this.value.toLowerCase();
        const rows = document.querySelectorAll('#recentSalesTable tbody tr');

        rows.forEach(row => {
            const text = row.textContent.toLowerCase();
            row.style.display = text.includes(filter) ? '' : 'none';
        });
    });
    
    // Functions for recent sales actions
    function viewSale(id) {
        window.open(`view_sale.php?id=${id}`, '_blank');
    }
    
    function editSale(id) {
        window.open(`edit_sale.php?id=${id}`, '_blank');
    }
</script>