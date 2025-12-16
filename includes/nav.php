<?php if (is_logged_in()): ?>
<aside class="sidebar">
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
    <ul class="sidebar-list">
        <?php foreach ($pages as $key => $meta): ?>
            <li>
                <a href="index.php?page=<?php echo $key; ?>"
                   class="list-group-item <?php echo $current === $key ? 'active' : ''; ?>">
                    <i class="bi <?php echo $meta['icon']; ?>"></i>
                    <span><?php echo $meta['label']; ?></span>
                </a>
            </li>
        <?php endforeach; ?>
    </ul>
</aside>
<?php endif; ?>
