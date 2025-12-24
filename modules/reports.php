<?php
// reports.php

// Set default date range (last 7 days)
$startDate = $_GET['start_date'] ?? date('Y-m-d', strtotime('-6 days'));
$endDate = $_GET['end_date'] ?? date('Y-m-d');

// Validate dates
if (!empty($_GET['start_date']) && !empty($_GET['end_date'])) {
    $startDate = $_GET['start_date'];
    $endDate = $_GET['end_date'];
}

// Ensure end date is not before start date
if (strtotime($endDate) < strtotime($startDate)) {
    $endDate = $startDate;
}

// Fetch sales data for the chart
$salesStmt = $conn->prepare("
    SELECT DATE(sale_date) AS day, SUM(total_amount) AS total
    FROM sales
    WHERE DATE(sale_date) BETWEEN ? AND ?
    GROUP BY DATE(sale_date)
    ORDER BY day ASC
");
$salesStmt->bind_param("ss", $startDate, $endDate);
$salesStmt->execute();
$salesResult = $salesStmt->get_result();

$salesData = [];
$chartLabels = [];
$chartValues = [];

while ($row = $salesResult->fetch_assoc()) {
    $salesData[] = $row;
    $chartLabels[] = date('M d', strtotime($row['day']));
    $chartValues[] = (float)$row['total'];
}

// Fetch top products for the display (without revenue)
$topProductsStmt = $conn->prepare("
    SELECT p.product_name, SUM(sd.quantity) AS qty
    FROM sale_details sd
    JOIN products p ON p.product_id = sd.product_id
    JOIN sales s ON s.sale_id = sd.sale_id
    WHERE DATE(s.sale_date) BETWEEN ? AND ?
    GROUP BY p.product_id, p.product_name
    ORDER BY qty DESC
    LIMIT 5
");
$topProductsStmt->bind_param("ss", $startDate, $endDate);
$topProductsStmt->execute();
$topProductsResult = $topProductsStmt->get_result();

$topProducts = [];
while ($row = $topProductsResult->fetch_assoc()) {
    $topProducts[] = $row;
}

// Get transaction counts for table
$countStmt = $conn->prepare("
    SELECT DATE(sale_date) AS day, COUNT(*) as count
    FROM sales
    WHERE DATE(sale_date) BETWEEN ? AND ?
    GROUP BY DATE(sale_date)
    ORDER BY day ASC
");
$countStmt->bind_param("ss", $startDate, $endDate);
$countStmt->execute();
$countResult = $countStmt->get_result();
$counts = [];
$totalTransactions = 0;
while ($row = $countResult->fetch_assoc()) {
    $counts[$row['day']] = $row['count'];
    $totalTransactions += $row['count'];
}

// Calculate totals for display
$totalSales = array_sum($chartValues);
?>

<main>
    <div class="container-fluid">
        <div class="page-header my-4">
            <h1 class="page-title">Sales Reports</h1>
            <p class="page-subtitle">Analyze sales performance and export data</p>
        </div>

        <!-- Filter Form -->
        <div class="card mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <div>
                    <i class="bi bi-funnel"></i> Filter Reports
                </div>
                <div>
                    <form method="GET" action="export_sales.php" id="exportForm" class="d-inline-block">
                        <input type="hidden" name="start_date" value="<?php echo htmlspecialchars($startDate); ?>">
                        <input type="hidden" name="end_date" value="<?php echo htmlspecialchars($endDate); ?>">
                        <button type="submit" class="btn btn-success">
                            <i class="bi bi-file-earmark-excel"></i> Export to Excel
                        </button>
                    </form>
                </div>
            </div>
            <div class="card-body">
                <form method="GET" action="" id="reportFilterForm">
                    <input type="hidden" name="page" value="reports">

                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group mb-3">
                                <label class="form-label">Start Date</label>
                                <input type="date"
                                       class="form-control"
                                       name="start_date"
                                       id="start_date"
                                       value="<?php echo htmlspecialchars($startDate); ?>"
                                       max="<?php echo date('Y-m-d'); ?>"
                                       required>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group mb-3">
                                <label class="form-label">End Date</label>
                                <input type="date"
                                       class="form-control"
                                       name="end_date"
                                       id="end_date"
                                       value="<?php echo htmlspecialchars($endDate); ?>"
                                       max="<?php echo date('Y-m-d'); ?>"
                                       required>
                            </div>
                        </div>
                        <div class="col-md-2 d-flex align-items-end">
                            <button type="submit" class="btn btn-primary w-100 mb-3">
                                <i class="bi bi-filter"></i> Apply Filter
                            </button>
                        </div>
                        <div class="col-md-2 d-flex align-items-end">
                            <button type="button" class="btn btn-outline-secondary w-100 mb-3" onclick="resetDates()">
                                <i class="bi bi-arrow-clockwise"></i> Reset
                            </button>
                        </div>
                    </div>

                    <!-- Quick Date Filters -->
                    <div class="row mt-2">
                        <div class="col-12">
                            <div class="btn-group" role="group">
                                <button type="button" class="btn btn-outline-primary btn-sm" onclick="setDateRange(7)">Last 7 Days</button>
                                <button type="button" class="btn btn-outline-primary btn-sm" onclick="setDateRange(30)">Last 30 Days</button>
                                <button type="button" class="btn btn-outline-primary btn-sm" onclick="setDateRange(90)">Last 90 Days</button>
                                <button type="button" class="btn btn-outline-primary btn-sm" onclick="setMonthToDate()">Month to Date</button>
                                <button type="button" class="btn btn-outline-primary btn-sm" onclick="setYearToDate()">Year to Date</button>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- Summary Cards -->
        <div class="row mb-4">
            <div class="col-md-4">
                <div class="card bg-primary text-white">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="card-subtitle mb-2">Total Sales</h6>
                                <h3 class="card-title">$<?php echo number_format($totalSales, 2); ?></h3>
                            </div>
                            <i class="bi bi-currency-dollar" style="font-size: 48px; opacity: 0.7;"></i>
                        </div>
                        <p class="card-text mb-0"><?php echo date('M d, Y', strtotime($startDate)); ?> - <?php echo date('M d, Y', strtotime($endDate)); ?></p>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card bg-success text-white">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="card-subtitle mb-2">Total Transactions</h6>
                                <h3 class="card-title"><?php echo $totalTransactions; ?></h3>
                            </div>
                            <i class="bi bi-receipt" style="font-size: 48px; opacity: 0.7;"></i>
                        </div>
                        <p class="card-text mb-0">Average: <?php echo number_format(count($salesData) > 0 ? $totalTransactions / count($salesData) : 0, 1); ?> per day</p>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card bg-info text-white">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="card-subtitle mb-2">Days with Sales</h6>
                                <h3 class="card-title"><?php echo count($salesData); ?></h3>
                            </div>
                            <i class="bi bi-calendar-check" style="font-size: 48px; opacity: 0.7;"></i>
                        </div>
                        <p class="card-text mb-0">Out of <?php echo max(1, round((strtotime($endDate) - strtotime($startDate)) / (60 * 60 * 24)) + 1); ?> days</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Charts and Data Display -->
        <div class="row mb-4">
            <!-- Sales Chart -->
            <div class="col-md-8">
                <div class="card h-100">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <div>
                            <i class="bi bi-bar-chart"></i> Sales Trend
                            <small class="text-muted ms-2"><?php echo date('M d, Y', strtotime($startDate)); ?> to <?php echo date('M d, Y', strtotime($endDate)); ?></small>
                        </div>
                        <div class="btn-group" role="group">
                            <button type="button" class="btn btn-sm btn-outline-secondary active" onclick="changeChartType('bar')">Bar</button>
                            <button type="button" class="btn btn-sm btn-outline-secondary" onclick="changeChartType('line')">Line</button>
                        </div>
                    </div>
                    <div class="card-body">
                        <div style="height: 300px;">
                            <canvas id="salesChart"></canvas>
                        </div>
                        <?php if (empty($chartValues)): ?>
                            <div class="text-center text-muted py-4">
                                <i class="bi bi-bar-chart" style="font-size: 48px; opacity: 0.3;"></i>
                                <p class="mt-2">No chart data available for this period</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Top Products -->
            <div class="col-md-4">
                <div class="card h-100">
                    <div class="card-header">
                        <i class="bi bi-star"></i> Top 5 Products
                    </div>
                    <div class="card-body">
                        <?php if (!empty($topProducts)): ?>
                            <div class="table-responsive">
                                <table class="table table-sm table-hover">
                                    <thead>
                                        <tr>
                                            <th>#</th>
                                            <th>Product</th>
                                            <th class="text-end">Qty Sold</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($topProducts as $index => $product): ?>
                                            <tr>
                                                <td class="text-muted"><?php echo $index + 1; ?></td>
                                                <td>
                                                    <div class="d-flex align-items-center">
                                                        <div class="flex-shrink-0">
                                                            <span class="badge bg-primary rounded-pill" style="width: 24px; height: 24px; display: inline-flex; align-items: center; justify-content: center;">
                                                                <?php echo $index + 1; ?>
                                                            </span>
                                                        </div>
                                                        <div class="flex-grow-1 ms-2">
                                                            <?php echo htmlspecialchars($product['product_name']); ?>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td class="text-end">
                                                    <span class="badge bg-primary rounded-pill"><?php echo $product['qty']; ?> units</span>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <div class="text-center text-muted py-4">
                                <i class="bi bi-inbox" style="font-size: 48px; opacity: 0.3;"></i>
                                <p class="mt-2">No product data in this period</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Main Data Table (Like your survey example) -->
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <div>
                    <i class="bi bi-table"></i> Sales Report Summary
                </div>
                <div>
                    <form method="GET" action="export_sales.php" class="d-inline-block">
                        <input type="hidden" name="start_date" value="<?php echo htmlspecialchars($startDate); ?>">
                        <input type="hidden" name="end_date" value="<?php echo htmlspecialchars($endDate); ?>">
                        <button type="submit" class="btn btn-success">
                            <i class="bi bi-file-earmark-excel"></i> Export to Excel
                        </button>
                    </form>
                </div>
            </div>
            <div class="card-body" style="padding: 0;">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Date</th>
                                <th class="text-end">Total Sales</th>
                                <th class="text-end">Transactions</th>
                                <th class="text-end">Avg. Transaction</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($salesData)): ?>
                                <?php foreach ($salesData as $sale):
                                    $transactionCount = $counts[$sale['day']] ?? 0;
                                    $avgTransaction = $transactionCount > 0 ? $sale['total'] / $transactionCount : 0;
                                ?>
                                    <tr>
                                        <td>
                                            <i class="bi bi-calendar me-1"></i>
                                            <?php echo date('D, M d, Y', strtotime($sale['day'])); ?>
                                        </td>
                                        <td class="text-end">
                                            <strong class="text-primary">$<?php echo number_format($sale['total'], 2); ?></strong>
                                        </td>
                                        <td class="text-end">
                                            <span class="badge bg-secondary"><?php echo $transactionCount; ?> trans</span>
                                        </td>
                                        <td class="text-end">
                                            <small class="text-muted">$<?php echo number_format($avgTransaction, 2); ?></small>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="4" class="text-center text-muted py-5">
                                        <i class="bi bi-inbox" style="font-size: 48px; opacity: 0.3;"></i>
                                        <p class="mt-2">No sales data in this period</p>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                        <?php if (!empty($salesData)): ?>
                            <tfoot class="table-light">
                                <tr>
                                    <td><strong>Total / Average</strong></td>
                                    <td class="text-end">
                                        <strong class="text-success">$<?php echo number_format($totalSales, 2); ?></strong>
                                    </td>
                                    <td class="text-end">
                                        <strong><?php echo $totalTransactions; ?> transactions</strong>
                                    </td>
                                    <td class="text-end">
                                        <strong>$<?php echo number_format($totalTransactions > 0 ? $totalSales / $totalTransactions : 0, 2); ?></strong>
                                    </td>
                                </tr>
                            </tfoot>
                        <?php endif; ?>
                    </table>
                </div>
            </div>
        </div>
    </div>
</main>

<!-- Chart.js Library -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
// Global chart variable
let salesChart = null;

// Initialize Sales Chart
document.addEventListener('DOMContentLoaded', function() {
    // Initialize chart if we have data
    const chartData = <?php echo json_encode($chartValues); ?>;
    const chartLabels = <?php echo json_encode($chartLabels); ?>;

    if (chartData.length > 0) {
        initChart('bar');
    }

    // Update export form when dates change
    document.getElementById('start_date').addEventListener('change', updateExportForm);
    document.getElementById('end_date').addEventListener('change', updateExportForm);
});

function initChart(type) {
    const ctx = document.getElementById('salesChart').getContext('2d');
    const chartData = <?php echo json_encode($chartValues); ?>;
    const chartLabels = <?php echo json_encode($chartLabels); ?>;

    // Destroy existing chart if it exists
    if (salesChart) {
        salesChart.destroy();
    }

    salesChart = new Chart(ctx, {
        type: type,
        data: {
            labels: chartLabels,
            datasets: [{
                label: 'Daily Sales ($)',
                data: chartData,
                backgroundColor: type === 'bar' ? 'rgba(54, 162, 235, 0.7)' : 'transparent',
                borderColor: 'rgba(54, 162, 235, 1)',
                borderWidth: 2,
                fill: type === 'line',
                tension: 0.4,
                pointBackgroundColor: 'rgba(54, 162, 235, 1)',
                pointBorderColor: '#fff',
                pointBorderWidth: 2,
                pointRadius: 4
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        callback: function(value) {
                            return '$' + value.toLocaleString();
                        }
                    },
                    grid: {
                        drawBorder: false
                    }
                },
                x: {
                    grid: {
                        display: false
                    }
                }
            },
            plugins: {
                legend: {
                    display: false
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            return 'Sales: $' + context.parsed.y.toLocaleString();
                        }
                    }
                }
            },
            interaction: {
                intersect: false,
                mode: 'index'
            }
        }
    });
}

function changeChartType(type) {
    // Update button states
    document.querySelectorAll('[onclick^="changeChartType"]').forEach(btn => {
        btn.classList.remove('active');
        if (btn.textContent.toLowerCase() === type) {
            btn.classList.add('active');
        }
    });

    // Reinitialize chart with new type
    initChart(type);
}

// Quick date range functions
function setDateRange(days) {
    const endDate = new Date();
    const startDate = new Date();
    startDate.setDate(endDate.getDate() - (days - 1));

    document.getElementById('start_date').value = formatDate(startDate);
    document.getElementById('end_date').value = formatDate(endDate);
    updateExportForm();
}

function setMonthToDate() {
    const now = new Date();
    const startDate = new Date(now.getFullYear(), now.getMonth(), 1);

    document.getElementById('start_date').value = formatDate(startDate);
    document.getElementById('end_date').value = formatDate(now);
    updateExportForm();
}

function setYearToDate() {
    const now = new Date();
    const startDate = new Date(now.getFullYear(), 0, 1);

    document.getElementById('start_date').value = formatDate(startDate);
    document.getElementById('end_date').value = formatDate(now);
    updateExportForm();
}

function resetDates() {
    const endDate = new Date();
    const startDate = new Date();
    startDate.setDate(endDate.getDate() - 6);

    document.getElementById('start_date').value = formatDate(startDate);
    document.getElementById('end_date').value = formatDate(endDate);
    updateExportForm();
}

function formatDate(date) {
    const year = date.getFullYear();
    const month = String(date.getMonth() + 1).padStart(2, '0');
    const day = String(date.getDate()).padStart(2, '0');
    return `${year}-${month}-${day}`;
}

function updateExportForm() {
    const startDate = document.getElementById('start_date').value;
    const endDate = document.getElementById('end_date').value;

    // Update all export forms on the page
    document.querySelectorAll('#exportForm input[name="start_date"]').forEach(input => {
        input.value = startDate;
    });
    document.querySelectorAll('#exportForm input[name="end_date"]').forEach(input => {
        input.value = endDate;
    });

    // Also update other export forms (if you have multiple)
    document.querySelectorAll('form[action="export_sales_report.php"] input[name="start_date"]').forEach(input => {
        input.value = startDate;
    });
    document.querySelectorAll('form[action="export_sales_report.php"] input[name="end_date"]').forEach(input => {
        input.value = endDate;
    });
}

// Alternative export function using window.open
function exportToExcel() {
    const startDate = document.getElementById('start_date').value;
    const endDate = document.getElementById('end_date').value;

    if (!startDate || !endDate) {
        alert('Please select both start and end dates.');
        return;
    }

    window.open(`export_sales_report.php?start_date=${encodeURIComponent(startDate)}&end_date=${encodeURIComponent(endDate)}`, '_blank');
}
</script>
