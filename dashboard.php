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

$extra_css = '';

include 'includes/header.php';
?>

<div class="section">
    <div class="stats-grid">
        <div class="stat-card primary">
            <div class="stat-header">
                <div>
                    <div class="stat-title">کل محصولات</div>
                    <div class="stat-value"><?= number_format($stats['products']) ?></div>
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
                </div>
                <div class="stat-icon info">
                    <i class="fas fa-money-bill-wave"></i>
                </div>
            </div>
        </div>

        <div class="stat-card <?= $stats['today_profit'] >= 0 ? 'success' : 'danger' ?>">
            <div class="stat-header">
                <div>
                    <div class="stat-title">فایده امروز</div>
                    <div class="stat-value"><?= number_format($stats['today_profit']) ?></div>

                </div>
                <div class="stat-icon <?= $stats['today_profit'] >= 0 ? 'success' : 'danger' ?>">
                    <i class="fas fa-<?= $stats['today_profit'] >= 0 ? 'chart-line' : 'chart-line-down' ?>"></i>
                </div>
            </div>
        </div>


    </div>
</div>



<div class="section">
    <div class="charts-grid">
        <div class="chart-card">
            <div class="chart-header">
                <h3 class="chart-title">روند فروش هفتگی</h3>
                <p class="chart-subtitle">فروش روزانه در 7 روز گذشته</p>
            </div>
            <div class="chart">
                <canvas id="chart-sales"></canvas>
            </div>
        </div>

        <div class="chart-card">
            <div class="chart-header">
                <h3 class="chart-title">محصولات پرفروش</h3>
                <p class="chart-subtitle">5 محصول برتر</p>
            </div>
            <div class="chart">
                <canvas id="chart-orders"></canvas>
            </div>
        </div>

        <div class="chart-card">
            <div class="chart-header">
                <h3 class="chart-title">روند موجودی</h3>
                <p class="chart-subtitle">وضعیت موجودی کالاها</p>
            </div>
            <div class="chart">
                <canvas id="chart-inventory"></canvas>
            </div>
        </div>


    </div>
</div>

<div class="section">
    <div class="table-card">
        <div class="table-header">
            <div class="action-bar">
                <div class="action-group">
                    <h3>آخرین فروشها</h3>
                </div>
                <div class="action-group">
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
            <table class="table" id="recentSalesTable">
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
                                <div class="btn-group" role="group">
                                    <a href="print_invoice.php?id=<?= $row['id'] ?>" class="btn btn-info btn-sm"
                                        target="_blank" title="چاپ فاکتور">
                                        <i class="fas fa-print"></i>
                                    </a>
                                    <button onclick="viewSale(<?= $row['id'] ?>)" class="btn btn-primary btn-sm"
                                        title="مشاهده جزئیات">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                    <button onclick="editSale(<?= $row['id'] ?>)" class="btn btn-warning btn-sm"
                                        title="ویرایش فاکتور">
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

<!-- کارتهای جدید -->
<div class="section">
    <div class="charts-grid">
        <!-- کارت تقویم -->
        <div class="chart-card">
            <div class="chart-header">
                <h3 class="chart-title">تقویم</h3>
                <p class="chart-subtitle">تاریخ و ساعت جاری</p>
            </div>
            <div class="chart" style="text-align: center; padding: 20px;">
                <div id="currentDate" style="font-size: 18px; font-weight: bold; margin-bottom: 10px;"></div>
                <div id="currentTime" style="font-size: 24px; color: #4f46e5; margin-bottom: 15px;"></div>
                <button onclick="toggleCalendar()" style="padding: 8px 15px; background: #4f46e5; color: white; border: none; border-radius: 4px;">نمایش تقویم</button>
                <div id="calendarView" style="display: none; margin-top: 15px; font-size: 14px;"></div>
            </div>
        </div>

        <!-- کارت اوقات شرعی -->
        <div class="chart-card">
            <div class="chart-header">
                <h3 class="chart-title">اوقات شرعی</h3>
                <p class="chart-subtitle">کابل، افغانستان</p>
            </div>
            <div class="chart" style="padding: 15px;">
                <div class="prayer-times">
                    <div class="prayer-item"><span>فجر:</span> <input type="time" id="fajr" value="05:30" onchange="savePrayerTimes()"></div>
                    <div class="prayer-item"><span>طلوع:</span> <input type="time" id="sunrise" value="06:45" onchange="savePrayerTimes()"></div>
                    <div class="prayer-item"><span>ظهر:</span> <input type="time" id="dhuhr" value="12:15" onchange="savePrayerTimes()"></div>
                    <div class="prayer-item"><span>عصر:</span> <input type="time" id="asr" value="15:30" onchange="savePrayerTimes()"></div>
                    <div class="prayer-item"><span>مغرب:</span> <input type="time" id="maghrib" value="18:00" onchange="savePrayerTimes()"></div>
                    <div class="prayer-item"><span>عشاء:</span> <input type="time" id="isha" value="19:30" onchange="savePrayerTimes()"></div>
                </div>
                <div style="text-align: center; margin-top: 15px;">
                    <button id="alertToggle" onclick="togglePrayerAlert()" style="padding: 8px 15px; background: #10b981; color: white; border: none; border-radius: 4px;">🔔 فعال کردن هشدار</button>
                </div>
            </div>
        </div>

        <!-- کارت To-Do List -->
        <div class="chart-card">
            <div class="chart-header">
                <h3 class="chart-title">کارهای ضروری</h3>
                <p class="chart-subtitle">To-Do List</p>
            </div>
            <div class="chart" style="padding: 15px;">
                <div class="todo-input" style="margin-bottom: 15px;">
                    <input type="text" id="todoInput" placeholder="کار ضروری جدید..." style="width: 65%; padding: 8px; border: 1px solid #ddd; border-radius: 4px;">
                    <button onclick="addTodo()" style="padding: 8px 12px; background: #10b981; color: white; border: none; border-radius: 4px; margin-right: 5px;">+ افزودن</button>
                </div>
                <div style="margin-bottom: 10px; font-size: 12px; color: #666;">کلیک روی متن برای خط زدن</div>
                <ul id="todoList" style="list-style: none; padding: 0; max-height: 180px; overflow-y: auto;"></ul>
            </div>
        </div>
    </div>
</div>
</div>

<?php include 'includes/footer-modern.php'; ?>

<style>
.prayer-times {
    display: flex;
    flex-direction: column;
    gap: 8px;
}
.prayer-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 8px 0;
    border-bottom: 1px solid #eee;
}
.prayer-item input {
    padding: 4px 8px;
    border: 1px solid #ddd;
    border-radius: 4px;
    width: 80px;
    text-align: center;
}
.todo-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 8px;
    margin: 5px 0;
    background: #f8f9fa;
    border-radius: 4px;
}
.todo-item.completed {
    text-decoration: line-through;
    opacity: 0.6;
    background: #e8f5e8;
}
.todo-item.urgent {
    border-left: 4px solid #dc3545;
    background: #fff5f5;
}
.todo-text {
    flex: 1;
    cursor: pointer;
}
.todo-delete {
    background: #dc3545;
    color: white;
    border: none;
    border-radius: 3px;
    padding: 4px 8px;
    font-size: 12px;
    cursor: pointer;
    margin-right: 5px;
}
.calendar-grid {
    display: grid;
    grid-template-columns: repeat(7, 1fr);
    gap: 2px;
    margin-top: 10px;
}
.calendar-day {
    padding: 5px;
    text-align: center;
    border: 1px solid #eee;
    font-size: 12px;
}
.calendar-day.today {
    background: #4f46e5;
    color: white;
    font-weight: bold;
}
</style>

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
                    borderWidth: 2,
                    fill: true,
                    tension: 0.2
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                animation: {
                    duration: 0
                },
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
                maintainAspectRatio: true,
                animation: {
                    duration: 0
                },
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
                maintainAspectRatio: true,
                animation: {
                    duration: 0
                },
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

    // تاریخ و ساعت با ماههای افغانی
    const afghanMonths = ['حمل', 'ثور', 'جوزا', 'سرطان', 'اسد', 'سنبله', 'میزان', 'عقرب', 'قوس', 'جدی', 'دلو', 'حوت'];
    const weekDays = ['یکشنبه', 'دوشنبه', 'سه‌شنبه', 'چهارشنبه', 'پنجشنبه', 'جمعه', 'شنبه'];
    
    function updateDateTime() {
        const now = new Date();
        const afghanDate = toAfghanDate(now);
        document.getElementById('currentDate').textContent = `${weekDays[now.getDay()]} ${afghanDate.day} ${afghanMonths[afghanDate.month-1]} ${afghanDate.year}`;
        document.getElementById('currentTime').textContent = now.toLocaleTimeString('en-US', {hour12: true, hour: '2-digit', minute: '2-digit', second: '2-digit'});
    }
    
    function toAfghanDate(date) {
        const year = date.getFullYear();
        const month = date.getMonth() + 1;
        const day = date.getDate();
        return {year: year - 621, month: month, day: day};
    }
    
    function toggleCalendar() {
        const cal = document.getElementById('calendarView');
        if (cal.style.display === 'none') {
            generateCalendar();
            cal.style.display = 'block';
        } else {
            cal.style.display = 'none';
        }
    }
    
    function generateCalendar() {
        const now = new Date();
        const year = now.getFullYear();
        const month = now.getMonth();
        const today = now.getDate();
        const firstDay = new Date(year, month, 1).getDay();
        const daysInMonth = new Date(year, month + 1, 0).getDate();
        
        let html = '<div class="calendar-grid">';
        ['ش', 'ی', 'د', 'س', 'چ', 'پ', 'ج'].forEach(day => {
            html += `<div class="calendar-day" style="font-weight: bold;">${day}</div>`;
        });
        
        for (let i = 0; i < firstDay; i++) {
            html += '<div class="calendar-day"></div>';
        }
        
        for (let day = 1; day <= daysInMonth; day++) {
            const isToday = day === today ? ' today' : '';
            html += `<div class="calendar-day${isToday}">${day}</div>`;
        }
        
        html += '</div>';
        document.getElementById('calendarView').innerHTML = html;
    }
    
    updateDateTime();
    setInterval(updateDateTime, 1000);

    // Prayer Times
    let prayerAlertEnabled = localStorage.getItem('prayerAlert') === 'true';
    let lastAlertTime = '';
    
    function savePrayerTimes() {
        const times = {
            fajr: document.getElementById('fajr').value,
            sunrise: document.getElementById('sunrise').value,
            dhuhr: document.getElementById('dhuhr').value,
            asr: document.getElementById('asr').value,
            maghrib: document.getElementById('maghrib').value,
            isha: document.getElementById('isha').value
        };
        localStorage.setItem('prayerTimes', JSON.stringify(times));
    }
    
    function loadPrayerTimes() {
        const saved = localStorage.getItem('prayerTimes');
        if (saved) {
            const times = JSON.parse(saved);
            Object.keys(times).forEach(key => {
                const element = document.getElementById(key);
                if (element) element.value = times[key];
            });
        }
        updateAlertButton();
    }
    
    function togglePrayerAlert() {
        prayerAlertEnabled = !prayerAlertEnabled;
        localStorage.setItem('prayerAlert', prayerAlertEnabled);
        updateAlertButton();
    }
    
    function updateAlertButton() {
        const btn = document.getElementById('alertToggle');
        if (prayerAlertEnabled) {
            btn.innerHTML = '🔕 غیرفعال کردن هشدار';
            btn.style.background = '#dc3545';
        } else {
            btn.innerHTML = '🔔 فعال کردن هشدار';
            btn.style.background = '#10b981';
        }
    }
    
    function checkPrayerTime() {
        if (!prayerAlertEnabled) return;
        
        const now = new Date();
        const currentTime = now.getHours().toString().padStart(2, '0') + ':' + now.getMinutes().toString().padStart(2, '0');
        
        const prayerNames = {fajr: 'فجر', sunrise: 'طلوع', dhuhr: 'ظهر', asr: 'عصر', maghrib: 'مغرب', isha: 'عشاء'};
        
        Object.keys(prayerNames).forEach(key => {
            const element = document.getElementById(key);
            if (element && element.value === currentTime && lastAlertTime !== currentTime) {
                playPrayerAlert(prayerNames[key]);
                lastAlertTime = currentTime;
            }
        });
    }
    
    function playPrayerAlert(prayerName) {
        // ایجاد صدای هشدار
        const audioContext = new (window.AudioContext || window.webkitAudioContext)();
        const oscillator = audioContext.createOscillator();
        const gainNode = audioContext.createGain();
        
        oscillator.connect(gainNode);
        gainNode.connect(audioContext.destination);
        
        oscillator.frequency.setValueAtTime(800, audioContext.currentTime);
        oscillator.type = 'sine';
        
        gainNode.gain.setValueAtTime(0.3, audioContext.currentTime);
        gainNode.gain.exponentialRampToValueAtTime(0.01, audioContext.currentTime + 2);
        
        oscillator.start(audioContext.currentTime);
        oscillator.stop(audioContext.currentTime + 2);
        
        // نمایش پیام
        alert(`وقت ${prayerName} فرا رسیده است!`);
    }
    
    // بررسی هر دقیقه
    setInterval(checkPrayerTime, 60000);
    
    // Todo List با قابلیت کارهای ضروری
    let todos = JSON.parse(localStorage.getItem('todos') || '[]');
    
    function renderTodos() {
        const list = document.getElementById('todoList');
        list.innerHTML = '';
        todos.forEach((todo, index) => {
            const li = document.createElement('li');
            li.className = 'todo-item' + (todo.completed ? ' completed' : '') + (todo.urgent ? ' urgent' : '');
            li.innerHTML = `
                <span class="todo-text" onclick="toggleTodo(${index})" title="کلیک برای خط زدن">${todo.text}</span>
                <div>
                    <button onclick="toggleUrgent(${index})" style="background: ${todo.urgent ? '#dc3545' : '#6c757d'}; color: white; border: none; border-radius: 3px; padding: 2px 6px; font-size: 10px; margin-left: 3px;">ضروری</button>
                    <button class="todo-delete" onclick="deleteTodo(${index})">×</button>
                </div>
            `;
            list.appendChild(li);
        });
    }
    
    function addTodo() {
        const input = document.getElementById('todoInput');
        const text = input.value.trim();
        if (text) {
            todos.push({ text, completed: false, urgent: false, date: new Date().toLocaleDateString('fa-IR') });
            localStorage.setItem('todos', JSON.stringify(todos));
            input.value = '';
            renderTodos();
        }
    }
    
    function toggleTodo(index) {
        todos[index].completed = !todos[index].completed;
        localStorage.setItem('todos', JSON.stringify(todos));
        renderTodos();
    }
    
    function toggleUrgent(index) {
        todos[index].urgent = !todos[index].urgent;
        localStorage.setItem('todos', JSON.stringify(todos));
        renderTodos();
    }
    
    function deleteTodo(index) {
        todos.splice(index, 1);
        localStorage.setItem('todos', JSON.stringify(todos));
        renderTodos();
    }
    
    document.getElementById('todoInput').addEventListener('keypress', function(e) {
        if (e.key === 'Enter') addTodo();
    });
    
    loadPrayerTimes();
    renderTodos();
    checkPrayerTime();
</script>