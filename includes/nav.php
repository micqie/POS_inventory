<?php if (is_logged_in()): ?>
<?php
$userRole = get_current_role();
$current = isset($_GET['page']) ? $_GET['page'] : 'dashboard';

// Define menu items with roles that can access them
$pages = [
    'dashboard' => ['label' => 'Dashboard', 'icon' => 'bi-speedometer2', 'roles' => ['admin', 'cashier', 'manager']],
    'categories' => ['label' => 'Categories', 'icon' => 'bi-tags', 'roles' => ['admin', 'manager']],
    'products' => ['label' => 'Products', 'icon' => 'bi-box-seam', 'roles' => ['admin', 'cashier', 'manager']],
    'suppliers' => ['label' => 'Suppliers', 'icon' => 'bi-truck', 'roles' => ['admin', 'manager']],
    'customers' => ['label' => 'Customers', 'icon' => 'bi-people', 'roles' => ['admin', 'cashier', 'manager']],
    'inventory' => ['label' => 'Inventory', 'icon' => 'bi-archive', 'roles' => ['admin', 'manager']],
    'sales' => ['label' => 'Sales', 'icon' => 'bi-cart-check', 'roles' => ['admin', 'cashier', 'manager']],
    'reports' => ['label' => 'Reports', 'icon' => 'bi-graph-up', 'roles' => ['admin', 'manager']],
    'users' => ['label' => 'Users', 'icon' => 'bi-person-gear', 'roles' => ['admin']],
];
?>
<aside class="sidebar">
    <ul class="sidebar-list">
        <?php foreach ($pages as $key => $meta):
            // Only show menu item if user has permission
            if (in_array($userRole, $meta['roles'])): ?>
                <li>
                    <a href="index.php?page=<?php echo $key; ?>"
                       class="list-group-item <?php echo $current === $key ? 'active' : ''; ?>">
                        <i class="bi <?php echo $meta['icon']; ?>"></i>
                        <span><?php echo $meta['label']; ?></span>
                    </a>
                </li>
            <?php endif; ?>
        <?php endforeach; ?>

        <!-- Logout link -->
        <li>
            <a href="index.php?page=logout" class="list-group-item">
                <i class="bi bi-box-arrow-right"></i>
                <span>Logout</span>
            </a>
        </li>
    </ul>
</aside>
<?php endif; ?>
