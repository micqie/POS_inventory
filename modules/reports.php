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

// Fetch top products for the display
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
    <div class="page-header">
        <h1 class="page-title">Sales Reports</h1>
        <p class="page-subtitle">Analyze sales performance and export data</p>
    </div>

    <!-- Filter Form -->
    <div class="card mb-4">
        <div class="card-header">
            <i class="bi bi-funnel"></i> Filter Reports
        </div>
        <div class="card-body">
            <form method="GET" action="" id="reportFilterForm" style="display: flex; gap: var(--spacing-md); align-items: flex-end; flex-wrap: wrap;">
                <input type="hidden" name="page" value="reports">
                <div class="form-group" style="flex: 1; min-width: 200px; margin-bottom: 0;">
                    <label class="form-label">Start Date</label>
                    <input type="date" class="form-control" name="start_date" id="start_date" value="<?php echo htmlspecialchars($startDate); ?>" max="<?php echo date('Y-m-d'); ?>" required>
                </div>
                <div class="form-group" style="flex: 1; min-width: 200px; margin-bottom: 0;">
                    <label class="form-label">End Date</label>
                    <input type="date" class="form-control" name="end_date" id="end_date" value="<?php echo htmlspecialchars($endDate); ?>" max="<?php echo date('Y-m-d'); ?>" required>
                </div>
                <div style="display: flex; gap: var(--spacing-sm);">
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-filter"></i> Apply Filter
                    </button>
                 
                </div>
            </form>
            <div style="margin-top: var(--spacing-md); display: flex; gap: var(--spacing-sm); flex-wrap: wrap;">
                <button type="button" class="btn btn-sm btn-outline-secondary" onclick="setDateRange(7)">Last 7 Days</button>
                <button type="button" class="btn btn-sm btn-outline-secondary" onclick="setDateRange(30)">Last 30 Days</button>
                <button type="button" class="btn btn-sm btn-outline-secondary" onclick="setDateRange(90)">Last 90 Days</button>
                <button type="button" class="btn btn-sm btn-outline-secondary" onclick="setMonthToDate()">Month to Date</button>
                <button type="button" class="btn btn-sm btn-outline-secondary" onclick="setYearToDate()">Year to Date</button>
            </div>
        </div>
    </div>

    <!-- Summary Cards -->
    <div class="dashboard-cards" style="margin-bottom: var(--spacing-xl);">
        <div class="metric-card">
            <div>
                <p class="text-muted mb-1" style="font-size: var(--font-size-sm); font-weight: 500;">Total Sales</p>
                <h4 class="mb-0 fw-bold" style="font-size: var(--font-size-2xl);">$<?php echo number_format($totalSales, 2); ?></h4>
                <p class="text-muted mb-0" style="font-size: var(--font-size-xs); margin-top: var(--spacing-xs);"><?php echo date('M d, Y', strtotime($startDate)); ?> - <?php echo date('M d, Y', strtotime($endDate)); ?></p>
            </div>
            <span class="metric-indicator bg-primary-subtle text-primary">
                <i class="bi bi-currency-dollar"></i>
            </span>
        </div>

        <div class="metric-card">
            <div>
                <p class="text-muted mb-1" style="font-size: var(--font-size-sm); font-weight: 500;">Total Transactions</p>
                <h4 class="mb-0 fw-bold" style="font-size: var(--font-size-2xl);"><?php echo number_format($totalTransactions); ?></h4>
                <p class="text-muted mb-0" style="font-size: var(--font-size-xs); margin-top: var(--spacing-xs);">Avg: <?php echo number_format(count($salesData) > 0 ? $totalTransactions / count($salesData) : 0, 1); ?> per day</p>
            </div>
            <span class="metric-indicator bg-success-subtle text-success">
                <i class="bi bi-receipt"></i>
            </span>
        </div>

        <div class="metric-card">
            <div>
                <p class="text-muted mb-1" style="font-size: var(--font-size-sm); font-weight: 500;">Days with Sales</p>
                <h4 class="mb-0 fw-bold" style="font-size: var(--font-size-2xl);"><?php echo count($salesData); ?></h4>
                <p class="text-muted mb-0" style="font-size: var(--font-size-xs); margin-top: var(--spacing-xs);">Out of <?php echo max(1, round((strtotime($endDate) - strtotime($startDate)) / (60 * 60 * 24)) + 1); ?> days</p>
            </div>
            <span class="metric-indicator bg-info-subtle text-info">
                <i class="bi bi-calendar-check"></i>
            </span>
        </div>
    </div>

    <!-- Charts and Data Display -->
    <div class="reports-grid" style="display: grid; grid-template-columns: 2fr 1fr; gap: var(--spacing-lg); margin-bottom: var(--spacing-xl);">
        <!-- Sales Chart -->
        <div class="card">
            <div class="card-header" style="display: flex; justify-content: space-between; align-items: center;">
                <div>
                    <i class="bi bi-bar-chart"></i> Sales Trend
                    <span style="color: var(--text-muted); font-size: var(--font-size-sm); margin-left: var(--spacing-sm);"><?php echo date('M d, Y', strtotime($startDate)); ?> to <?php echo date('M d, Y', strtotime($endDate)); ?></span>
                </div>
                <div style="display: flex; gap: var(--spacing-xs);">
                    <button type="button" class="btn btn-sm btn-outline-secondary active" onclick="changeChartType('bar')">Bar</button>
                    <button type="button" class="btn btn-sm btn-outline-secondary" onclick="changeChartType('line')">Line</button>
                </div>
            </div>
            <div class="card-body">
                <div style="height: 300px;">
                    <canvas id="salesChart"></canvas>
                </div>
                <?php if (empty($chartValues)): ?>
                    <div class="text-center text-muted" style="padding: var(--spacing-2xl);">
                        <i class="bi bi-bar-chart" style="font-size: var(--font-size-3xl); display: block; margin-bottom: var(--spacing-md); opacity: 0.5;"></i>
                        No chart data available for this period
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Top Products -->
        <div class="card">
            <div class="card-header">
                <i class="bi bi-star"></i> Top 5 Products
            </div>
            <div class="card-body" style="padding: 0;">
                <?php if (!empty($topProducts)): ?>
                    <div class="table-container">
                        <table class="table table-hover align-middle">
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
                                        <td><?php echo $index + 1; ?></td>
                                        <td><strong><?php echo sanitize($product['product_name']); ?></strong></td>
                                        <td class="text-end">
                                            <span style="padding: 4px 10px; border-radius: var(--radius-sm); font-weight: 600; font-size: var(--font-size-xs); background: var(--primary-100); color: var(--primary-700);">
                                                <?php echo $product['qty']; ?> units
                                            </span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="text-center text-muted" style="padding: var(--spacing-2xl);">
                        <i class="bi bi-inbox" style="font-size: var(--font-size-3xl); display: block; margin-bottom: var(--spacing-md); opacity: 0.5;"></i>
                        No product data in this period
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Main Data Table -->
    <div class="card">
        <div class="card-header" style="display: flex; justify-content: space-between; align-items: center;">
            <div>
                <i class="bi bi-table"></i> Sales Report Summary
            </div>
            <button type="button" class="btn btn-sm btn-success" onclick="exportToExcel()">
                <i class="bi bi-file-earmark-excel"></i> Export to Excel
            </button>
        </div>
        <div class="card-body" style="padding: 0;">
            <div class="table-container">
                <table class="table table-hover align-middle">
                    <thead>
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
                                        <i class="bi bi-calendar" style="margin-right: var(--spacing-xs);"></i>
                                        <?php echo date('D, M d, Y', strtotime($sale['day'])); ?>
                                    </td>
                                    <td class="text-end">
                                        <strong style="color: var(--primary-600);">$<?php echo number_format($sale['total'], 2); ?></strong>
                                    </td>
                                    <td class="text-end">
                                        <span style="padding: 4px 10px; border-radius: var(--radius-sm); font-weight: 600; font-size: var(--font-size-xs); background: var(--bg-tertiary); color: var(--text-primary);">
                                            <?php echo $transactionCount; ?> trans
                                        </span>
                                    </td>
                                    <td class="text-end">
                                        <span style="color: var(--text-muted);">$<?php echo number_format($avgTransaction, 2); ?></span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="4" class="text-center text-muted" style="padding: var(--spacing-2xl);">
                                    <i class="bi bi-inbox" style="font-size: var(--font-size-3xl); display: block; margin-bottom: var(--spacing-md); opacity: 0.5;"></i>
                                    No sales data in this period
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                    <?php if (!empty($salesData)): ?>
                        <tfoot style="background: var(--bg-secondary);">
                            <tr>
                                <td><strong>Total / Average</strong></td>
                                <td class="text-end">
                                    <strong style="color: var(--success);">$<?php echo number_format($totalSales, 2); ?></strong>
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

    <!-- Hidden form for export -->
    <form id="excelExportForm" method="GET" action="export_sales_report.php" target="_blank" style="display: none;">
        <input type="hidden" name="start_date" id="export_start_date">
        <input type="hidden" name="end_date" id="export_end_date">
    </form>
</main>

<!-- Chart.js Library -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
// Global chart variable
let salesChart = null;

// Initialize Sales Chart
document.addEventListener('DOMContentLoaded', function() {
    const chartData = <?php echo json_encode($chartValues); ?>;
    const chartLabels = <?php echo json_encode($chartLabels); ?>;

    if (chartData.length > 0) {
        initChart('bar');
    }

    document.getElementById('start_date').addEventListener('change', updateExportForm);
    document.getElementById('end_date').addEventListener('change', updateExportForm);
});

function initChart(type) {
    const ctx = document.getElementById('salesChart').getContext('2d');
    const chartData = <?php echo json_encode($chartValues); ?>;
    const chartLabels = <?php echo json_encode($chartLabels); ?>;

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
                backgroundColor: type === 'bar' ? 'rgba(2, 132, 199, 0.7)' : 'transparent',
                borderColor: 'rgba(2, 132, 199, 1)',
                borderWidth: 2,
                fill: type === 'line',
                tension: 0.4,
                pointBackgroundColor: 'rgba(2, 132, 199, 1)',
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
    document.querySelectorAll('[onclick^="changeChartType"]').forEach(btn => {
        btn.classList.remove('active');
        if (btn.textContent.toLowerCase().includes(type)) {
            btn.classList.add('active');
        }
    });
    initChart(type);
}

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
    document.getElementById('export_start_date').value = startDate;
    document.getElementById('export_end_date').value = endDate;
}

function exportToExcel() {
    const startDate = document.getElementById('start_date').value;
    const endDate = document.getElementById('end_date').value;

    if (!startDate || !endDate) {
        alert('Please select both start and end dates.');
        return;
    }

    function getSessionId() {
        const cookies = document.cookie.split(';');
        for (let cookie of cookies) {
            const [name, value] = cookie.trim().split('=');
            if (name === 'PHPSESSID') {
                return value;
            }
        }
        return null;
    }

    const sessionId = getSessionId();
    if (sessionId) {
        window.open(`export_sales_report.php?start_date=${encodeURIComponent(startDate)}&end_date=${encodeURIComponent(endDate)}&session_id=${sessionId}`, '_blank');
    } else {
        alert('Session expired. Please log in again.');
    }
}
</script>
