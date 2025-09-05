let reportChart = null;

// مدیریت منو و بخشها
function showMenu() {
  document.getElementById("reportsMenu").style.display = "block";
  document.querySelectorAll(".report-section").forEach((section) => {
    section.classList.remove("active");
    section.style.display = "none";
  });
}

function showReport(reportType) {
  document.getElementById("reportsMenu").style.display = "none";
  document.querySelectorAll(".report-section").forEach((section) => {
    section.classList.remove("active");
    section.style.display = "none";
  });

  const targetSection = document.getElementById(reportType + "Report");
  if (targetSection) {
    targetSection.style.display = "block";
    targetSection.classList.add("active");

    // بارگذاری گزارش قرض ها
    if (reportType === "debts") {
      loadDebtsReport();
    }

    // بارگذاری گزارش هزینه ها
    if (reportType === "transactions") {
      loadTransactionsReport();
    }
  }
}

// Print function
function printReport(reportId) {
  const printContent = document.getElementById(reportId).innerHTML;
  const originalContent = document.body.innerHTML;

  document.body.innerHTML = `
        <div style="direction: rtl; font-family: Tahoma, Arial, sans-serif; padding: 20px;">
            <h2 style="text-align: center; margin-bottom: 20px; border-bottom: 2px solid #000; padding-bottom: 10px;">گزارش فروشگاه قطعات موتورسیکلت</h2>
            <p style="text-align: center; margin-bottom: 30px;">تاریخ تولید گزارش: ${new Date().toLocaleDateString(
              "fa-IR"
            )}</p>
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
  XLSX.writeFile(
    wb,
    `${filename}-${new Date().toISOString().split("T")[0]}.xlsx`
  );
}

// Export to PDF
function exportToPDF(reportId, filename) {
  const element = document.getElementById(reportId);
  const opt = {
    margin: 0.5,
    filename: `${filename}-${new Date().toISOString().split("T")[0]}.pdf`,
    image: { type: "jpeg", quality: 0.98 },
    html2canvas: { scale: 2, useCORS: true },
    jsPDF: { unit: "in", format: "a4", orientation: "portrait" },
  };
  html2pdf().set(opt).from(element).save();
}

// Live Search
function liveSearch(tableId, searchValue) {
  const table = document.getElementById(tableId);
  const rows = table
    .getElementsByTagName("tbody")[0]
    .getElementsByTagName("tr");
  let visibleCount = 0;

  for (let i = 0; i < rows.length; i++) {
    const row = rows[i];
    const text = row.textContent.toLowerCase();
    const isVisible = text.includes(searchValue.toLowerCase());
    row.style.display = isVisible ? "" : "none";
    if (isVisible) visibleCount++;
  }

  updateSummary(tableId);
}

// Filter by date
function filterByDate(tableId, dateValue) {
  const table = document.getElementById(tableId);
  const rows = table
    .getElementsByTagName("tbody")[0]
    .getElementsByTagName("tr");

  for (let i = 0; i < rows.length; i++) {
    const row = rows[i];
    const dateCell = row.querySelector("[data-date]");
    if (dateCell) {
      const rowDate = new Date(dateCell.getAttribute("data-date"))
        .toISOString()
        .split("T")[0];
      row.style.display = !dateValue || rowDate === dateValue ? "" : "none";
    }
  }

  updateSummary(tableId);
}

// Filter by stock level
function filterByStock(tableId, stockLevel) {
  const table = document.getElementById(tableId);
  const rows = table
    .getElementsByTagName("tbody")[0]
    .getElementsByTagName("tr");

  for (let i = 0; i < rows.length; i++) {
    const row = rows[i];
    const stockCell = row.querySelector("[data-stock]");
    if (stockCell) {
      const stock = parseInt(stockCell.getAttribute("data-stock"));
      let show = true;

      if (stockLevel === "critical") show = stock <= 5;
      else if (stockLevel === "low") show = stock >= 6 && stock <= 10;

      row.style.display = show ? "" : "none";
    }
  }
}

// Sort table
function sortTable(tableId, sortType) {
  const table = document.getElementById(tableId);
  const tbody = table.getElementsByTagName("tbody")[0];
  const rows = Array.from(tbody.getElementsByTagName("tr"));

  rows.sort((a, b) => {
    if (sortType.includes("date")) {
      const dateA = new Date(
        a.querySelector("[data-date]").getAttribute("data-date")
      );
      const dateB = new Date(
        b.querySelector("[data-date]").getAttribute("data-date")
      );
      return sortType.includes("desc") ? dateB - dateA : dateA - dateB;
    } else if (sortType.includes("amount")) {
      const amountA = parseFloat(
        a.querySelector("[data-amount]").getAttribute("data-amount")
      );
      const amountB = parseFloat(
        b.querySelector("[data-amount]").getAttribute("data-amount")
      );
      return sortType.includes("desc") ? amountB - amountA : amountA - amountB;
    } else if (sortType.includes("product")) {
      const productA = a
        .querySelector("[data-product]")
        .getAttribute("data-product");
      const productB = b
        .querySelector("[data-product]")
        .getAttribute("data-product");
      return sortType.includes("desc")
        ? productB.localeCompare(productA)
        : productA.localeCompare(productB);
    }
  });

  rows.forEach((row) => tbody.appendChild(row));
  updateSummary(tableId);
}

// Update summary based on visible rows
function updateSummary(tableId) {
  const table = document.getElementById(tableId);
  const rows = table
    .getElementsByTagName("tbody")[0]
    .getElementsByTagName("tr");
  let totalAmount = 0,
    totalProfit = 0,
    totalQuantity = 0,
    visibleRows = 0;

  for (let i = 0; i < rows.length; i++) {
    const row = rows[i];
    if (row.style.display !== "none") {
      visibleRows++;

      const amountCell = row.querySelector("[data-amount]");
      if (amountCell)
        totalAmount += parseFloat(amountCell.getAttribute("data-amount"));

      const profitCell = row.querySelector("[data-profit]");
      if (profitCell)
        totalProfit += parseFloat(profitCell.getAttribute("data-profit"));

      const quantityCell = row.cells[2];
      if (quantityCell && !isNaN(quantityCell.textContent)) {
        totalQuantity += parseInt(quantityCell.textContent);
      }
    }
  }

  // Update footer
  const totalAmountEl = document.getElementById("totalAmount");
  if (totalAmountEl)
    totalAmountEl.textContent = totalAmount.toLocaleString() + " افغانی";

  const totalProfitEl = document.getElementById("totalProfitAmount");
  if (totalProfitEl) {
    totalProfitEl.textContent = totalProfit.toLocaleString() + " افغانی";
    totalProfitEl.className = totalProfit > 0 ? "text-success" : "text-danger";
  }

  const totalQuantityEl = document.getElementById("totalQuantity");
  if (totalQuantityEl)
    totalQuantityEl.textContent = totalQuantity.toLocaleString();
}

// Save filters
function saveFilters(reportType) {
  const filters = {
    search: document.getElementById(`${reportType}Search`)?.value || "",
    date: document.getElementById(`${reportType}DateFilter`)?.value || "",
    sort: document.getElementById(`${reportType}SortOrder`)?.value || "",
  };

  localStorage.setItem(`${reportType}_filters`, JSON.stringify(filters));
  alert("فیلترها ذخیره شد");
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

    alert("فیلترها بارگذاری شد");
  } else {
    alert("فیلتر ذخیره شده‌ای یافت نشد");
  }
}

// Generate custom report
async function generateCustomReport(event) {
  event.preventDefault();

  const startDate = document.getElementById("startDate").value;
  const endDate = document.getElementById("endDate").value;
  const reportType = document.getElementById("reportType").value;
  const categoryFilter = document.getElementById("categoryFilter").value;

  if (!startDate || !endDate) {
    alert("لطفاً تاریخ شروع و پایان را انتخاب کنید");
    return;
  }

  // Convert Jalali dates to Gregorian
  const startDateGregorian = convertJalaliToGregorian(startDate);
  const endDateGregorian = convertJalaliToGregorian(endDate);

  if (!startDateGregorian || !endDateGregorian) {
    alert("فرمت تاریخ نادرست است. لطفاً به فرمت 1404/01/01 وارد کنید");
    return;
  }

  try {
    const response = await fetch("api/custom_report.php", {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({
        start_date: startDateGregorian,
        end_date: endDateGregorian,
        report_type: reportType,
        category_id: categoryFilter,
      }),
    });

    const data = await response.json();

    if (data.success) {
      displayCustomReport(data.data, reportType, startDate, endDate);
      updateSummaryCards(data.summary);
      if (data.detailed_stats && reportType === "sales") {
        updateDetailedStats(data.detailed_stats);
      } else {
        document.getElementById("detailedStatsCards").style.display = "none";
      }
    } else {
      alert("خطا در تولید گزارش: " + data.message);
    }
  } catch (error) {
    alert("خطا در ارتباط با سرور");
  }
}

// Display custom report
function displayCustomReport(data, reportType, startDate, endDate) {
  const reportTitle = {
    sales: "گزارش فروش کلی",
    bestsellers: "گزارش محصولات پرفروش",
    inventory: "گزارش موجودی کالاها",
    profit: "گزارش سود و زیان",
  };

  document.getElementById(
    "customReportTitle"
  ).textContent = `${reportTitle[reportType]} از ${startDate} تا ${endDate}`;

  const headers = getReportHeaders(reportType);
  const headerHtml =
    "<tr>" + headers.map((h) => `<th>${h}</th>`).join("") + "</tr>";
  document.getElementById("customReportHeader").innerHTML = headerHtml;

  const bodyHtml = data
    .map((row) => {
      return (
        "<tr>" +
        Object.values(row)
          .map((val) => `<td>${val}</td>`)
          .join("") +
        "</tr>"
      );
    })
    .join("");
  document.getElementById("customReportBody").innerHTML = bodyHtml;

  document.getElementById("customReportResult").style.display = "block";
}

// Get report headers based on type
function getReportHeaders(reportType) {
  const headers = {
    sales: ["تاریخ", "شماره فاکتور", "مشتری", "مبلغ کل", "تخفیف", "مبلغ نهایی"],
    bestsellers: ["نام محصول", "تعداد فروش", "درآمد کل", "سود"],
    inventory: [
      "نام محصول",
      "دسته بندی",
      "موجودی فعلی",
      "حداقل موجودی",
      "وضعیت",
    ],
    profit: ["تاریخ", "درآمد", "هزینه", "سود خالص", "درصد سود"],
  };
  return headers[reportType] || [];
}

// Update summary cards
function updateSummaryCards(summary) {
  document.getElementById("totalSales").textContent =
    (summary.total_sales || 0).toLocaleString() + " افغانی";
  document.getElementById("totalInvoices").textContent =
    (summary.total_invoices || 0).toLocaleString() + " عدد";
  document.getElementById("totalProfit").textContent =
    (summary.total_profit || 0).toLocaleString() + " افغانی";
  document.getElementById("avgInvoice").textContent =
    (summary.avg_invoice || 0).toLocaleString() + " افغانی";

  document.getElementById("summaryCards").style.display = "grid";
}

// Update detailed statistics cards
function updateDetailedStats(stats) {
  if (!stats || Object.keys(stats).length === 0) {
    document.getElementById("detailedStatsCards").style.display = "none";
    return;
  }

  const maxDailySalesEl = document.getElementById("maxDailySales");
  const avgDailySalesEl = document.getElementById("avgDailySales");
  const topProductEl = document.getElementById("topProduct");
  const topProductQtyEl = document.getElementById("topProductQty");
  const topCustomerEl = document.getElementById("topCustomer");
  const topCustomerAmountEl = document.getElementById("topCustomerAmount");
  const minDailySalesEl = document.getElementById("minDailySales");
  const activeDaysEl = document.getElementById("activeDays");

  if (maxDailySalesEl)
    maxDailySalesEl.textContent =
      (stats.max_daily_sales || 0).toLocaleString() + " افغانی";
  if (avgDailySalesEl)
    avgDailySalesEl.textContent =
      (stats.avg_daily_sales || 0).toLocaleString() + " افغانی";
  if (topProductEl) topProductEl.textContent = stats.top_product || "-";
  if (topProductQtyEl)
    topProductQtyEl.textContent = (stats.top_product_qty || 0) + " عدد";
  if (topCustomerEl) topCustomerEl.textContent = stats.top_customer || "-";
  if (topCustomerAmountEl)
    topCustomerAmountEl.textContent =
      (stats.top_customer_amount || 0).toLocaleString() + " افغانی";
  if (minDailySalesEl)
    minDailySalesEl.textContent =
      (stats.min_daily_sales || 0).toLocaleString() + " افغانی";
  if (activeDaysEl)
    activeDaysEl.textContent = (stats.active_days || 0) + " روز";

  document.getElementById("detailedStatsCards").style.display = "grid";
}

// Create chart
function createChart(chartData, reportType) {
  const ctx = document.getElementById("reportChart").getContext("2d");

  if (reportChart) {
    reportChart.destroy();
  }

  const chartConfig = {
    sales: { type: "line", label: "فروش روزانه" },
    bestsellers: { type: "bar", label: "تعداد فروش" },
    inventory: { type: "doughnut", label: "وضعیت موجودی" },
    profit: { type: "line", label: "سود روزانه" },
  };

  const config = chartConfig[reportType];

  reportChart = new Chart(ctx, {
    type: config.type,
    data: {
      labels: chartData.labels || [],
      datasets: [
        {
          label: config.label,
          data: chartData.data || [],
          backgroundColor:
            config.type === "doughnut"
              ? ["#1f2937", "#374151", "#4b5563", "#6b7280", "#9ca3af"]
              : "rgba(31, 41, 55, 0.1)",
          borderColor: "#1f2937",
          borderWidth: 2,
          fill: config.type === "line",
        },
      ],
    },
    options: {
      responsive: true,
      plugins: {
        legend: {
          position: config.type === "doughnut" ? "bottom" : "top",
        },
      },
      scales:
        config.type !== "doughnut"
          ? {
              y: {
                beginAtZero: true,
              },
            }
          : {},
    },
  });

  document.getElementById("chartContainer").style.display = "block";
}

// Initialize
document.addEventListener("DOMContentLoaded", function () {
  // Set default Jalali dates
  const today = new Date();
  const thirtyDaysAgo = new Date(today.getTime() - 30 * 24 * 60 * 60 * 1000);

  const todayJalali = convertToJalali(today.toISOString().split("T")[0]);
  const thirtyDaysAgoJalali = convertToJalali(
    thirtyDaysAgo.toISOString().split("T")[0]
  );

  document.getElementById("endDate").value = todayJalali;
  document.getElementById("startDate").value = thirtyDaysAgoJalali;

  // نمایش منوی اصلی در ابتدا
  showMenu();

  // Update summaries on load
  updateSummary("detailed-sales-table");

  // Initialize responsive layout
  initializeResponsiveLayout();

  // Initialize Jalali date inputs
  initializeJalaliDateInputs();
});

// Initialize Jalali date inputs
function initializeJalaliDateInputs() {
  const jalaliInputs = document.querySelectorAll(".jalali-date");

  jalaliInputs.forEach((input) => {
    input.addEventListener("input", function (e) {
      let value = e.target.value.replace(/[^0-9]/g, "");

      if (value.length >= 4) {
        value = value.substring(0, 4) + "/" + value.substring(4);
      }
      if (value.length >= 7) {
        value = value.substring(0, 7) + "/" + value.substring(7, 9);
      }

      e.target.value = value;
    });

    input.addEventListener("blur", function (e) {
      const value = e.target.value;
      if (value && !value.match(/^\d{4}\/\d{2}\/\d{2}$/)) {
        alert("فرمت تاریخ باید به صورت 1404/01/01 باشد");
        e.target.focus();
      }
    });
  });
}

// Responsive layout initialization
function initializeResponsiveLayout() {
  const sidebar = document.getElementById("sidebar");
  const sidebarToggle = document.getElementById("sidebarToggle");
  const sidebarOverlay = document.getElementById("sidebarOverlay");

  if (sidebarToggle) {
    sidebarToggle.addEventListener("click", function () {
      if (sidebar) {
        sidebar.classList.toggle("open");
        if (sidebarOverlay) {
          sidebarOverlay.classList.toggle("show");
        }
      }
    });
  }

  if (sidebarOverlay) {
    sidebarOverlay.addEventListener("click", function () {
      if (sidebar) {
        sidebar.classList.remove("open");
        sidebarOverlay.classList.remove("show");
      }
    });
  }

  // Handle window resize
  window.addEventListener("resize", function () {
    if (window.innerWidth > 768) {
      if (sidebar) {
        sidebar.classList.remove("open");
      }
      if (sidebarOverlay) {
        sidebarOverlay.classList.remove("show");
      }
    }
  });
}

// توابع تبدیل تاریخ
function convertToJalali(gregorianDate) {
  if (!gregorianDate) return "";
  const parts = gregorianDate.split("-");
  if (parts.length !== 3) return gregorianDate;

  const jalali = gregorianToJalali(
    parseInt(parts[0]),
    parseInt(parts[1]),
    parseInt(parts[2])
  );
  return `${jalali[0]}/${jalali[1].toString().padStart(2, "0")}/${jalali[2]
    .toString()
    .padStart(2, "0")}`;
}

function gregorianToJalali(gy, gm, gd) {
  const g_d_m = [0, 31, 59, 90, 120, 151, 181, 212, 243, 273, 304, 334];

  let jy = gy <= 1600 ? 0 : 979;
  gy -= gy <= 1600 ? 621 : 1600;

  const gy2 = gm > 2 ? gy + 1 : gy;
  let days =
    365 * gy +
    Math.floor((gy2 + 3) / 4) -
    Math.floor((gy2 + 99) / 100) +
    Math.floor((gy2 + 399) / 400) -
    80 +
    gd +
    g_d_m[gm - 1];

  jy += 33 * Math.floor(days / 12053);
  days %= 12053;

  jy += 4 * Math.floor(days / 1461);
  days %= 1461;

  if (days > 365) {
    jy += Math.floor((days - 1) / 365);
    days = (days - 1) % 365;
  }

  const jm =
    days < 186 ? 1 + Math.floor(days / 31) : 7 + Math.floor((days - 186) / 30);
  const jd = days < 186 ? 1 + (days % 31) : 1 + ((days - 186) % 30);

  return [jy, jm, jd];
}

function jalaliToGregorian(jy, jm, jd) {
  const jy0 = jy - 979;
  const jp =
    33 * Math.floor(jy0 / 1029) +
    4 * Math.floor((jy0 % 1029) / 33) +
    Math.floor((((jy0 % 1029) % 33) + 3) / 4);

  let j_day_no =
    365 * jy + jp + (jm < 7 ? (jm - 1) * 31 : (jm - 7) * 30 + 186) + jd - 1;

  const g_day_no = j_day_no + 79;

  const gy = 1600 + 400 * Math.floor(g_day_no / 146097);
  let gd_no = g_day_no % 146097;

  let leap = true;
  if (gd_no >= 36525) {
    gd_no--;
    const gy2 = gy + 100 * Math.floor(gd_no / 36524);
    gd_no = gd_no % 36524;
    if (gd_no >= 365) gd_no++;
    leap = false;
  }

  const gy3 = gy + 4 * Math.floor(gd_no / 1461);
  gd_no %= 1461;

  if (gd_no >= 366) {
    leap = false;
    gd_no--;
    const gy4 = gy3 + Math.floor(gd_no / 365);
    gd_no = gd_no % 365;
  }

  let gm, gd;
  const sal_a = [
    0,
    31,
    leap ? 60 : 59,
    leap ? 91 : 90,
    leap ? 121 : 120,
    leap ? 152 : 151,
    leap ? 182 : 181,
    leap ? 213 : 212,
    leap ? 244 : 243,
    leap ? 274 : 273,
    leap ? 305 : 304,
    leap ? 335 : 334,
    leap ? 366 : 365,
  ];

  for (gm = 0; gm < 13; gm++) {
    if (gd_no < sal_a[gm]) break;
  }

  gd = gd_no - (gm > 0 ? sal_a[gm - 1] : 0) + 1;

  return [gy3 + (gy4 || 0), gm, gd];
}

function convertJalaliToGregorian(jalaliDate) {
  if (!jalaliDate || !jalaliDate.match(/^\d{4}\/\d{2}\/\d{2}$/)) {
    return null;
  }

  const parts = jalaliDate.split("/");
  const jy = parseInt(parts[0]);
  const jm = parseInt(parts[1]);
  const jd = parseInt(parts[2]);

  const gregorian = jalaliToGregorian(jy, jm, jd);
  return `${gregorian[0]}-${gregorian[1]
    .toString()
    .padStart(2, "0")}-${gregorian[2].toString().padStart(2, "0")}`;
}

// بارگذاری گزارش هزینه ها مالی
async function loadTransactionsReport() {
  try {
    const response = await fetch("api/transactions_report.php");
    const data = await response.json();

    if (data.success) {
      // بهروزرسانی کارتهای خلاصه
      document.getElementById("totalExpenses").textContent =
        data.summary.total_expenses.toLocaleString() + " افغانی";
      document.getElementById("totalWithdrawals").textContent =
        data.summary.total_withdrawals.toLocaleString() + " افغانی";
      document.getElementById("totalTransactions").textContent =
        (
          data.summary.total_expenses + data.summary.total_withdrawals
        ).toLocaleString() + " افغانی";
      document.getElementById("transactionCount").textContent =
        data.transactions.length.toLocaleString() + " مورد";

      // بهروزرسانی جدول هزینه ها
      const transactionsTableBody = document.getElementById(
        "transactionsTableBody"
      );
      transactionsTableBody.innerHTML = data.transactions
        .map(
          (transaction) => `
                        <tr data-type="${
                          transaction.transaction_type
                        }" data-person="${
            transaction.person_name
          }" data-date="${transaction.transaction_date}" data-amount="${
            transaction.amount
          }">
                            <td><code>${
                              transaction.transaction_code
                            }</code></td>
                            <td><span class="badge badge-${
                              transaction.transaction_type === "expense"
                                ? "danger"
                                : "warning"
                            }">${
            transaction.transaction_type === "expense" ? "مصرف" : "برداشت"
          }</span></td>
                            <td>${transaction.type_name}</td>
                            <td class="fw-bold">${transaction.amount.toLocaleString()} افغانی</td>
                            <td>${transaction.person_name}</td>
                            <td>${new Date(
                              transaction.transaction_date
                            ).toLocaleDateString("fa-IR")}</td>
                            <td>${transaction.description || "-"}</td>
                        </tr>
                    `
        )
        .join("");

      updateTransactionsSummary();
    } else {
      alert("خطا در بارگذاری گزارش هزینه ها");
    }
  } catch (error) {
    alert("خطا در ارتباط با سرور");
  }
}

// فیلتر بر اساس نوع تراکنش
function filterTransactionsByType() {
  const filterValue = document.getElementById("transactionTypeFilter").value;
  const rows = document.querySelectorAll("#transactionsTable tbody tr");

  rows.forEach((row) => {
    const type = row.dataset.type;
    row.style.display = !filterValue || type === filterValue ? "" : "none";
  });

  updateTransactionsSummary();
}

// فیلتر بر اساس نام شخص
function filterTransactionsByPerson() {
  const filterValue = document
    .getElementById("transactionPersonFilter")
    .value.toLowerCase();
  const rows = document.querySelectorAll("#transactionsTable tbody tr");

  rows.forEach((row) => {
    const person = row.dataset.person.toLowerCase();
    row.style.display =
      !filterValue || person.includes(filterValue) ? "" : "none";
  });

  updateTransactionsSummary();
}

// فیلتر بر اساس بازه تاریخ
function filterTransactionsByDateRange() {
  const dateFrom = document.getElementById("transactionDateFrom").value;
  const dateTo = document.getElementById("transactionDateTo").value;
  const rows = document.querySelectorAll("#transactionsTable tbody tr");

  rows.forEach((row) => {
    const rowDate = row.dataset.date;
    let show = true;

    if (dateFrom && rowDate < dateFrom) show = false;
    if (dateTo && rowDate > dateTo) show = false;

    row.style.display = show ? "" : "none";
  });

  updateTransactionsSummary();
}

// پاک کردن فیلترها
function clearTransactionFilters() {
  document.getElementById("transactionTypeFilter").value = "";
  document.getElementById("transactionPersonFilter").value = "";
  document.getElementById("transactionDateFrom").value = "";
  document.getElementById("transactionDateTo").value = "";

  const rows = document.querySelectorAll("#transactionsTable tbody tr");
  rows.forEach((row) => {
    row.style.display = "";
  });

  updateTransactionsSummary();
}

// بهروزرسانی خلاصه هزینه ها
function updateTransactionsSummary() {
  const visibleRows = document.querySelectorAll(
    '#transactionsTable tbody tr:not([style*="display: none"])'
  );
  let totalAmount = 0;

  visibleRows.forEach((row) => {
    totalAmount += parseFloat(row.dataset.amount);
  });

  document.getElementById("footerTotalAmount").textContent =
    totalAmount.toLocaleString() + " افغانی";
}

// بارگذاری گزارش قرض ها
async function loadDebtsReport() {
  try {
    const response = await fetch("api/debts_report.php");
    const data = await response.json();

    if (data.success) {
      // بهروزرسانی کارتهای خلاصه
      document.getElementById("totalCustomerDebt").textContent =
        data.summary.total_customer_debt.toLocaleString() + " افغانی";
      document.getElementById("totalSupplierCredit").textContent =
        data.summary.total_supplier_credit.toLocaleString() + " افغانی";

      const balance =
        data.summary.total_supplier_credit - data.summary.total_customer_debt;
      document.getElementById("financialBalance").textContent =
        balance.toLocaleString() + " افغانی";
      document.getElementById("financialBalance").className =
        "value " + (balance >= 0 ? "text-success" : "text-danger");

      // بهروزرسانی جدول قرض مشتریان
      const customerDebtsBody = document.getElementById("customerDebtsBody");
      customerDebtsBody.innerHTML = data.customer_debts
        .map(
          (debt) => `
                        <tr>
                            <td>${debt.customer_name}</td>
                            <td class="text-danger fw-bold">${debt.remaining_amount.toLocaleString()} افغانی</td>
                            <td><span class="badge bg-${
                              debt.payment_status === "partial"
                                ? "warning"
                                : "danger"
                            }">${
            debt.payment_status === "partial" ? "جزئی" : "بدهکار"
          }</span></td>
                        </tr>
                    `
        )
        .join("");

      // بهروزرسانی جدول طلبتأمین کنندگان
      const supplierCreditsBody = document.getElementById(
        "supplierCreditsBody"
      );
      supplierCreditsBody.innerHTML = data.supplier_credits
        .map(
          (credit) => `
                        <tr>
                            <td>${credit.supplier_name}</td>
                            <td class="text-success fw-bold">${credit.remaining_amount.toLocaleString()} افغانی</td>
                            <td><span class="badge bg-${
                              credit.payment_status === "partial"
                                ? "warning"
                                : "danger"
                            }">${
            credit.payment_status === "partial" ? "جزئی" : "بدهکار"
          }</span></td>
                        </tr>
                    `
        )
        .join("");
    } else {
      alert("خطا در بارگذاری گزارش قرض ها");
    }
  } catch (error) {
    alert("خطا در ارتباط با سرور");
  }
}
