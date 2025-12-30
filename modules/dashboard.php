<?php
// Basic overview counts.
$counts = [];
$tables = ['products', 'categories', 'suppliers', 'customers', 'sales'];
foreach ($tables as $tbl) {
    $res = $conn->query("SELECT COUNT(*) AS total FROM {$tbl}");
    $counts[$tbl] = $res ? (int)$res->fetch_assoc()['total'] : 0;
}

// Calculate total revenue
$resRevenue = $conn->query("SELECT SUM(total_amount) AS revenue FROM sales");
$totalRevenue = $resRevenue ? (float)$resRevenue->fetch_assoc()['revenue'] : 0;


$startDate = $_GET['start_date'] ?? date('Y-m-d', strtotime('-6 days'));
$endDate   = $_GET['end_date'] ?? date('Y-m-d');

// Ensure end date is not before start date
if (strtotime($endDate) < strtotime($startDate)) {
    $endDate = $startDate;
}

// Fetch top 5 products
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


?>

<main>
    <div class="page-header">
        <h1 class="page-title">Dashboard</h1>
        <p class="page-subtitle">Overview of your inventory system</p>
    </div>

    <div class="dashboard-cards">
        <div class="metric-card">
            <div>
                <p class="text-muted mb-1" style="font-size: var(--font-size-sm); font-weight: 500;">Products</p>
                <h4 class="mb-0 fw-bold" style="font-size: var(--font-size-2xl);"><?php echo number_format($counts['products']); ?></h4>
            </div>
            <span class="metric-indicator bg-primary-subtle text-primary">
                <i class="bi bi-box-seam"></i>
            </span>
        </div>

        <div class="metric-card">
            <div>
                <p class="text-muted mb-1" style="font-size: var(--font-size-sm); font-weight: 500;">Categories</p>
                <h4 class="mb-0 fw-bold" style="font-size: var(--font-size-2xl);"><?php echo number_format($counts['categories']); ?></h4>
            </div>
            <span class="metric-indicator bg-secondary-subtle text-secondary">
                <i class="bi bi-tags"></i>
            </span>
        </div>

        <div class="metric-card">
            <div>
                <p class="text-muted mb-1" style="font-size: var(--font-size-sm); font-weight: 500;">Suppliers</p>
                <h4 class="mb-0 fw-bold" style="font-size: var(--font-size-2xl);"><?php echo number_format($counts['suppliers']); ?></h4>
            </div>
            <span class="metric-indicator bg-info-subtle text-info">
                <i class="bi bi-truck"></i>
            </span>
        </div>

        <div class="metric-card">
            <div>
                <p class="text-muted mb-1" style="font-size: var(--font-size-sm); font-weight: 500;">Customers</p>
                <h4 class="mb-0 fw-bold" style="font-size: var(--font-size-2xl);"><?php echo number_format($counts['customers']); ?></h4>
            </div>
            <span class="metric-indicator bg-success-subtle text-success">
                <i class="bi bi-people"></i>
            </span>
        </div>

        <div class="metric-card">
            <div>
                <p class="text-muted mb-1" style="font-size: var(--font-size-sm); font-weight: 500;">Sales</p>
                <h4 class="mb-0 fw-bold" style="font-size: var(--font-size-2xl);"><?php echo number_format($counts['sales']); ?></h4>
            </div>
            <span class="metric-indicator bg-warning-subtle text-warning">
                <i class="bi bi-cart-check"></i>
            </span>
        </div>

        <!-- Total Revenue Card -->
        <div class="metric-card">
            <div>
                <p class="text-muted mb-1" style="font-size: var(--font-size-sm); font-weight: 500;">Total Revenue</p>
                <h4 class="mb-0 fw-bold" style="font-size: var(--font-size-2xl);">₱<?php echo number_format($totalRevenue, 2); ?></h4>
            </div>
            <span class="metric-indicator bg-danger-subtle text-danger">
                <i class="bi bi-currency-dollar"></i>
            </span>
        </div>
    </div>

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
</main>
