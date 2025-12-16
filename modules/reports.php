<?php
// Reports & analytics using Chart.js

// Sales last 7 days
$salesData = [];
$res = $conn->query("
    SELECT DATE(sale_date) AS day, SUM(total_amount) AS total
    FROM sales
    WHERE sale_date >= DATE_SUB(CURDATE(), INTERVAL 6 DAY)
    GROUP BY DATE(sale_date)
    ORDER BY day ASC
");
if ($res) {
    while ($row = $res->fetch_assoc()) {
        $salesData[] = $row;
    }
}

// Top 5 products by quantity sold
$topProducts = [];
$resTop = $conn->query("
    SELECT p.product_name, SUM(sd.quantity) AS qty
    FROM sale_details sd
    JOIN products p ON p.product_id = sd.product_id
    GROUP BY p.product_id, p.product_name
    ORDER BY qty DESC
    LIMIT 5
");
if ($resTop) {
    while ($row = $resTop->fetch_assoc()) {
        $topProducts[] = $row;
    }
}

$labels = array_map(fn($r) => $r['day'], $salesData);
$salesTotals = array_map(fn($r) => (float)$r['total'], $salesData);
$productLabels = array_map(fn($r) => $r['product_name'], $topProducts);
$productQty = array_map(fn($r) => (int)$r['qty'], $topProducts);
?>
<main>
    <div class="page-header">
        <h1 class="page-title">Reports & Analytics</h1>
        <p class="page-subtitle">View sales trends and product performance</p>
    </div>

    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(500px, 1fr)); gap: var(--spacing-xl);">
        <div class="card">
            <div class="card-header">
                <i class="bi bi-graph-up"></i> Sales (Last 7 Days)
            </div>
            <div class="card-body">
                <canvas id="salesChart" style="max-height: 300px;"></canvas>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <i class="bi bi-bar-chart"></i> Top Products (Quantity Sold)
            </div>
            <div class="card-body">
                <canvas id="productsChart" style="max-height: 300px;"></canvas>
            </div>
        </div>
    </div>
</main>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js" integrity="sha384-+sMkRUYw35vjpsIadB1iKsFcfoTmyaKOA1NVuMcZV8IO4D4ew3Efr2E1VlzDq+W8" crossorigin="anonymous"></script>
<script>
    const salesCtx = document.getElementById('salesChart');
    const salesLabels = <?php echo json_encode($labels); ?>;
    const salesTotals = <?php echo json_encode($salesTotals); ?>;

    new Chart(salesCtx, {
        type: 'line',
        data: {
            labels: salesLabels,
            datasets: [{
                label: 'Total Sales',
                data: salesTotals,
                borderColor: '#007bff',
                backgroundColor: 'rgba(0, 123, 255, 0.15)',
                tension: 0.3,
                fill: true,
                borderWidth: 2,
            }]
        },
        options: {
            scales: {
                y: { beginAtZero: true }
            }
        }
    });

    const productCtx = document.getElementById('productsChart');
    const productLabels = <?php echo json_encode($productLabels); ?>;
    const productQty = <?php echo json_encode($productQty); ?>;

    new Chart(productCtx, {
        type: 'bar',
        data: {
            labels: productLabels,
            datasets: [{
                label: 'Quantity Sold',
                data: productQty,
                backgroundColor: '#28a745',
                borderColor: '#1e7e34',
                borderWidth: 1
            }]
        },
        options: {
            indexAxis: 'y',
            scales: {
                x: { beginAtZero: true }
            }
        }
    });
</script>
