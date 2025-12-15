<?php
// Basic overview counts.
$counts = [];
$tables = ['products', 'categories', 'suppliers', 'customers', 'sales'];
foreach ($tables as $tbl) {
    $res = $conn->query("SELECT COUNT(*) AS total FROM {$tbl}");
    $counts[$tbl] = $res ? (int)$res->fetch_assoc()['total'] : 0;
}
?>
<main>
    <h3>Dashboard</h3>
    <div class="row g-3 dashboard-cards">
        <div class="col-12 col-md-6 col-lg-4">
            <div class="card metric-card border-0 shadow-sm">
                <div class="card-body d-flex justify-content-between align-items-center">
                    <div>
                        <p class="text-muted mb-1">Products</p>
                        <h4 class="mb-0 fw-bold"><?php echo number_format($counts['products']); ?></h4>
                    </div>
                    <span class="metric-indicator bg-primary-subtle text-primary">P</span>
                </div>
            </div>
        </div>
        <div class="col-12 col-md-6 col-lg-4">
            <div class="card metric-card border-0 shadow-sm">
                <div class="card-body d-flex justify-content-between align-items-center">
                    <div>
                        <p class="text-muted mb-1">Categories</p>
                        <h4 class="mb-0 fw-bold"><?php echo number_format($counts['categories']); ?></h4>
                    </div>
                    <span class="metric-indicator bg-secondary-subtle text-secondary">C</span>
                </div>
            </div>
        </div>
        <div class="col-12 col-md-6 col-lg-4">
            <div class="card metric-card border-0 shadow-sm">
                <div class="card-body d-flex justify-content-between align-items-center">
                    <div>
                        <p class="text-muted mb-1">Suppliers</p>
                        <h4 class="mb-0 fw-bold"><?php echo number_format($counts['suppliers']); ?></h4>
                    </div>
                    <span class="metric-indicator bg-info-subtle text-info">S</span>
                </div>
            </div>
        </div>
        <div class="col-12 col-md-6 col-lg-4">
            <div class="card metric-card border-0 shadow-sm">
                <div class="card-body d-flex justify-content-between align-items-center">
                    <div>
                        <p class="text-muted mb-1">Customers</p>
                        <h4 class="mb-0 fw-bold"><?php echo number_format($counts['customers']); ?></h4>
                    </div>
                    <span class="metric-indicator bg-success-subtle text-success">C</span>
                </div>
            </div>
        </div>
        <div class="col-12 col-md-6 col-lg-4">
            <div class="card metric-card border-0 shadow-sm">
                <div class="card-body d-flex justify-content-between align-items-center">
                    <div>
                        <p class="text-muted mb-1">Sales</p>
                        <h4 class="mb-0 fw-bold"><?php echo number_format($counts['sales']); ?></h4>
                    </div>
                    <span class="metric-indicator bg-warning-subtle text-warning">S</span>
                </div>
            </div>
        </div>
    </div>
</main>

