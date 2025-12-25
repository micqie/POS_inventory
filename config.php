<?php
/**
 * Application Configuration
 */

// Define role-based permissions
$rolePermissions = [
    'admin' => [
        'dashboard', 'categories', 'products', 'suppliers',
        'customers', 'inventory', 'sales', 'reports', 'users'
    ],
    'cashier' => [
        'dashboard', 'products', 'customers', 'sales'
    ],
    'manager' => [
        'dashboard', 'categories', 'products', 'suppliers',
        'customers', 'inventory', 'sales', 'reports'
    ]
];

// Public pages that don't require authentication
$publicPages = ['login', 'seed_admin'];

// Get current page
$currentPage = isset($_GET['page']) ? $_GET['page'] : 'dashboard';

// Handle logout early
if (isset($_GET['page']) && $_GET['page'] === 'logout') {
    session_destroy();
    header('Location: index.php?page=login');
    exit;
}

// Show login without header/nav if not authenticated
if (!is_logged_in() && !in_array($currentPage, $publicPages, true)) {
    header('Location: index.php?page=login');
    exit;
}

// Load public pages
if ($currentPage === 'login') {
    include __DIR__ . '/modules/login.php';
    exit;
}

if ($currentPage === 'seed_admin') {
    include __DIR__ . '/seed_admin.php';
    exit;
}

// Check permissions for authenticated users
if (is_logged_in()) {
    $userRole = get_current_role();

    // If no role found, default to cashier for security
    if (!$userRole || !isset($rolePermissions[$userRole])) {
        $userRole = 'cashier';
    }

    // Redirect if user tries to access a page they don't have permission for
    if (!in_array($currentPage, $publicPages, true)) {
        if (!isset($rolePermissions[$userRole]) || !in_array($currentPage, $rolePermissions[$userRole], true)) {
            flash('error', 'You do not have permission to access that page.');
            header('Location: index.php?page=dashboard');
            exit;
        }
    }
}

// Include header for authenticated users
include __DIR__ . '/includes/header.php';
?>
<div class="app-content-wrapper">
    <?php include __DIR__ . '/includes/nav.php'; ?>
    <div style="flex: 1; display: flex; flex-direction: column;">
        <div style="flex: 1;">
            <?php
            // Display flash messages
            if ($msg = flash('success')) {
                echo '<div class="alert alert-success" role="alert">' . sanitize($msg) . '</div>';
            }
            if ($msg = flash('error')) {
                echo '<div class="alert alert-danger" role="alert">' . sanitize($msg) . '</div>';
            }

            // Load the requested module if user has permission
            $userRole = get_current_role() ?? 'cashier';
            $allowedModules = $rolePermissions[$userRole] ?? $rolePermissions['cashier'];

            if (in_array($currentPage, $allowedModules, true)) {
                include __DIR__ . '/modules/' . $currentPage . '.php';
            } else {
                echo '<main><div class="page-header"><h1 class="page-title">Page not found</h1></div></main>';
            }
            ?>
        </div>
    </div>
</div>
<?php
include __DIR__ . '/includes/footer.php';
