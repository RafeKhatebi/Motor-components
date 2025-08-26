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

$query = "SELECT COUNT(*) as count FROM customers";
$stmt = $db->prepare($query);
$stmt->execute();
$stats['customers'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'];

$query = "SELECT COALESCE(SUM(final_amount), 0) as total FROM sales WHERE DATE(created_at) = CURDATE() AND (status IS NULL OR status != 'returned')";
$stmt = $db->prepare($query);
$stmt->execute();
$stats['today_sales'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

$query = "SELECT COUNT(*) as count FROM products WHERE stock_quantity <= min_stock";
$stmt = $db->prepare($query);
$stmt->execute();
$stats['low_stock'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'];

// داده‌های نمودار فروش روزانه (7 روز گذشته)
$sales_chart_query = "SELECT DATE(created_at) as date, COALESCE(SUM(final_amount), 0) as total 
                      FROM sales 
                      WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY) 
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

include 'includes/header.php';
?>

<!-- Header -->
<div class="header bg-gradient-primary pb-8 pt-5 pt-md-8">
    <div class="container-fluid">
        <div class="header-body">
            <!-- Card stats -->
            <div class="row">
                <div class="col-xl-3 col-lg-6">
                    <div class="card card-stats mb-4 mb-xl-0">
                        <div class="card-body">
                            <div class="row">
                                <div class="col">
                                    <h5 class="card-title text-uppercase text-muted mb-0"><?= __('total_products') ?>
                                    </h5>
                                    <span
                                        class="h2 font-weight-bold mb-0"><?= number_format($stats['products']) ?></span>
                                </div>
                                <div class="col-auto">
                                    <div class="icon icon-shape bg-danger text-white rounded-circle shadow">
                                        <i class="fas fa-chart-bar"></i>
                                    </div>
                                </div>
                            </div>
                            <p class="mt-3 mb-0 text-muted text-sm">
                                <span class="text-success me-2"><i class="fa fa-arrow-up"></i> 3.48%</span>
                                <span class="text-nowrap"><?= __('from_last_month') ?></span>
                            </p>
                        </div>
                    </div>
                </div>
                <div class="col-xl-3 col-lg-6">
                    <div class="card card-stats mb-4 mb-xl-0">
                        <div class="card-body">
                            <div class="row">
                                <div class="col">
                                    <h5 class="card-title text-uppercase text-muted mb-0"><?= __('customers') ?></h5>
                                    <span
                                        class="h2 font-weight-bold mb-0"><?= number_format($stats['customers']) ?></span>
                                </div>
                                <div class="col-auto">
                                    <div class="icon icon-shape bg-warning text-white rounded-circle shadow">
                                        <i class="fas fa-chart-pie"></i>
                                    </div>
                                </div>
                            </div>
                            <p class="mt-3 mb-0 text-muted text-sm">
                                <span class="text-danger me-2"><i class="fas fa-arrow-down"></i> 3.48%</span>
                                <span class="text-nowrap"><?= __('from_last_week') ?></span>
                            </p>
                        </div>
                    </div>
                </div>
                <div class="col-xl-3 col-lg-6">
                    <div class="card card-stats mb-4 mb-xl-0">
                        <div class="card-body">
                            <div class="row">
                                <div class="col">
                                    <h5 class="card-title text-uppercase text-muted mb-0"><?= __('today_sales') ?></h5>
                                    <span
                                        class="h2 font-weight-bold mb-0"><?= number_format($stats['today_sales']) ?></span>
                                </div>
                                <div class="col-auto">
                                    <div class="icon icon-shape bg-yellow text-white rounded-circle shadow">
                                        <i class="fas fa-users"></i>
                                    </div>
                                </div>
                            </div>
                            <p class="mt-3 mb-0 text-muted text-sm">
                                <span class="text-warning me-2"><i class="fas fa-arrow-down"></i> 1.10%</span>
                                <span class="text-nowrap"><?= __('afghani') ?></span>
                            </p>
                        </div>
                    </div>
                </div>
                <div class="col-xl-3 col-lg-6">
                    <div class="card card-stats mb-4 mb-xl-0">
                        <div class="card-body">
                            <div class="row">
                                <div class="col">
                                    <h5 class="card-title text-uppercase text-muted mb-0"><?= __('low_stock') ?></h5>
                                    <span
                                        class="h2 font-weight-bold mb-0"><?= number_format($stats['low_stock']) ?></span>
                                </div>
                                <div class="col-auto">
                                    <div class="icon icon-shape bg-info text-white rounded-circle shadow">
                                        <i class="fas fa-percent"></i>
                                    </div>
                                </div>
                            </div>
                            <p class="mt-3 mb-0 text-muted text-sm">
                                <span class="text-success me-2"><i class="fas fa-arrow-up"></i> 12%</span>
                                <span class="text-nowrap"><?= __('from_yesterday') ?></span>
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Page content -->
<div class="container-fluid mt--7">
    <div class="row">
        <div class="col-xl-8 mb-5 mb-xl-0">
            <div class="card bg-gradient-default shadow">
                <div class="card-header bg-transparent">
                    <div class="row align-items-center">
                        <div class="col">
                            <h6 class="text-uppercase text-light ls-1 mb-1"><?= __('overview') ?></h6>
                            <h2 class="text-white mb-0"><?= __('sales_chart') ?></h2>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <!-- Chart -->
                    <div class="chart">
                        <canvas id="chart-sales" class="chart-canvas"></canvas>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-4">
            <div class="card shadow">
                <div class="card-header bg-transparent">
                    <div class="row align-items-center">
                        <div class="col">
                            <h6 class="text-uppercase text-muted ls-1 mb-1"><?= __('performance') ?></h6>
                            <h2 class="mb-0"><?= __('top_products') ?></h2>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <!-- Chart -->
                    <div class="chart">
                        <canvas id="chart-orders" class="chart-canvas"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="row mt-5">
        <div class="col">
            <div class="card shadow">
                <div class="card-header border-0">
                    <div class="row align-items-center">
                        <div class="col">
                            <h3 class="mb-0">آخرین فعالیت‌ها</h3>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table align-items-center table-flush">
                            <thead class="thead-light">
                                <tr>
                                    <th scope="col">فعالیت</th>
                                    <th scope="col">تاریخ</th>
                                    <th scope="col">مبلغ</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>فروش جدید</td>
                                    <td>امروز</td>
                                    <td>۱۲۰,۰۰۰ افغانی</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>


<script src="assets/js/bootstrap.bundle.min.js"></script>

<script src="assets/js/chart.js"></script>
<script>
    // نمودار فروش
    const salesData = <?= json_encode($sales_chart_data) ?>;
    const salesLabels = salesData.map(item => item.date);
    const salesValues = salesData.map(item => parseFloat(item.total));

    const salesChart = new Chart(document.getElementById('chart-sales'), {
        type: 'line',
        data: {
            labels: salesLabels,
            datasets: [{
                label: 'فروش روزانه',
                data: salesValues,
                borderColor: 'rgb(75, 192, 192)',
                backgroundColor: 'rgba(75, 192, 192, 0.2)',
                tension: 0.1
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: {
                    labels: {
                        color: 'white'
                    }
                }
            },
            scales: {
                y: {
                    ticks: {
                        color: 'white'
                    },
                    grid: {
                        color: 'rgba(255,255,255,0.1)'
                    }
                },
                x: {
                    ticks: {
                        color: 'white'
                    },
                    grid: {
                        color: 'rgba(255,255,255,0.1)'
                    }
                }
            }
        }
    });

    // نمودار محصولات پرفروش
    const topProductsData = <?= json_encode($top_products_data) ?>;
    const productLabels = topProductsData.map(item => item.name);
    const productValues = topProductsData.map(item => parseInt(item.total_sold));

    const productsChart = new Chart(document.getElementById('chart-orders'), {
        type: 'doughnut',
        data: {
            labels: productLabels,
            datasets: [{
                data: productValues,
                backgroundColor: [
                    '#FF6384',
                    '#36A2EB',
                    '#FFCE56',
                    '#4BC0C0',
                    '#9966FF'
                ]
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: {
                    position: 'bottom'
                }
            }
        }
    });
</script>
</body>

</html>
<div>
    <div>
        <div class="card shadow">
            <div class="card-header border-0">
                <div class="row align-items-center">
                    <div class="col">
                        <h3 class="mb-0">آخرین فروش‌ها</h3>
                    </div>
                    <div class="col text-left">
                        <a href="sales.php" class="btn btn-sm btn-primary">مشاهده همه</a>
                    </div>
                </div>
            </div>
            <div class="table-responsive">
                <table class="table align-items-center table-flush">
                    <thead class="thead-light">
                        <tr>
                            <th scope="col">فاکتور</th>
                            <th scope="col">مشتری</th>
                            <th scope="col">مبلغ</th>
                            <th scope="col">تاریخ</th>
                            <th scope="col">عملیات</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $query = "SELECT s.id, c.name as customer_name, s.final_amount, s.created_at 
                                     FROM sales s 
                                     LEFT JOIN customers c ON s.customer_id = c.id 
                                     ORDER BY s.created_at DESC LIMIT 5";
                        $stmt = $db->prepare($query);
                        $stmt->execute();

                        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)): ?>
                            <tr>
                                <th scope="row">#<?= sanitizeOutput($row['id']) ?></th>
                                <td><?= sanitizeOutput($row['customer_name'] ?: 'مشتری نقدی') ?></td>
                                <td><?= number_format($row['final_amount']) ?> افغانی</td>
                                <td><?= SettingsHelper::formatDateTime(strtotime($row['created_at']), $db) ?></td>
                                <td class="text-left">
                                    <a href="print_invoice.php?id=<?= $row['id'] ?>" class="btn btn-sm btn-primary"
                                        target="_blank">
                                        <i class="fas fa-print"></i>
                                    </a>
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
<script>
    // نمودار فروش روزانه با داده‌های واقعی
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
                    borderColor: "#5e72e4",
                    backgroundColor: "rgba(94, 114, 228, 0.1)",
                    borderWidth: 2,
                    fill: true
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

    // نمودار محصولات پرفروش با داده‌های واقعی
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
                    backgroundColor: ["#5e72e4", "#11cdef", "#2dce89", "#fb6340", "#f5365c"]
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: "bottom"
                    }
                }
            }
        });
    }
</script>