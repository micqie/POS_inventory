<?php if (is_logged_in()): ?>
<aside class="col-12 col-md-3 col-lg-2 px-0 sidebar bg-white border-end min-vh-100">
    <?php
    $pages = [
        'dashboard' => ['label' => 'Dashboard', 'icon' => 'bi-speedometer2'],
        'categories' => ['label' => 'Categories', 'icon' => 'bi-tags'],
        'products' => ['label' => 'Products', 'icon' => 'bi-box-seam'],
        'suppliers' => ['label' => 'Suppliers', 'icon' => 'bi-truck'],
        'customers' => ['label' => 'Customers', 'icon' => 'bi-people'],
        'inventory' => ['label' => 'Inventory', 'icon' => 'bi-archive'],
        'sales' => ['label' => 'Sales', 'icon' => 'bi-cart-check'],
        'reports' => ['label' => 'Reports', 'icon' => 'bi-graph-up'],
        'users' => ['label' => 'Users', 'icon' => 'bi-person-gear'],
    ];
    $current = isset($_GET['page']) ? $_GET['page'] : 'dashboard';
    ?>
    <div class="list-group list-group-flush sidebar-list">
        <?php foreach ($pages as $key => $meta): ?>
            <a href="index.php?page=<?php echo $key; ?>"
               class="list-group-item list-group-item-action d-flex align-items-center gap-2 <?php echo $current === $key ? 'active' : ''; ?>">
                <i class="bi <?php echo $meta['icon']; ?>"></i>
                <span><?php echo $meta['label']; ?></span>
            </a>
        <?php endforeach; ?>
    </div>
</aside>
<?php endif; ?>

