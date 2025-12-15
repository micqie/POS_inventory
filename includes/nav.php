<?php if (is_logged_in()): ?>
<aside class="col-12 col-md-3 col-lg-2 px-0 sidebar bg-white border-end min-vh-100">
    <?php
    $pages = [
        'dashboard' => 'Dashboard',
        'categories' => 'Categories',
        'products' => 'Products',
        'suppliers' => 'Suppliers',
        'customers' => 'Customers',
        'inventory' => 'Inventory',
        'sales' => 'Sales',
        'reports' => 'Reports',
        'users' => 'Users',
    ];
    $current = isset($_GET['page']) ? $_GET['page'] : 'dashboard';
    ?>
    <div class="list-group list-group-flush">
        <?php foreach ($pages as $key => $label): ?>
            <a href="index.php?page=<?php echo $key; ?>"
               class="list-group-item list-group-item-action <?php echo $current === $key ? 'active' : ''; ?>">
                <?php echo $label; ?>
            </a>
        <?php endforeach; ?>
    </div>
</aside>
<?php endif; ?>

