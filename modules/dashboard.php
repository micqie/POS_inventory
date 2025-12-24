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
</main>
