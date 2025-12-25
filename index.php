
<?php
// index.php
session_start();
require_once __DIR__ . '/connect_db.php';
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/config.php';

// Redirect to login when not authenticated (except login and seed).
$publicPages = ['login', 'seed_admin'];
$currentPage = isset($_GET['page']) ? $_GET['page'] : 'dashboard';

if (!is_logged_in() && !in_array($currentPage, $publicPages, true)) {
    header('Location: index.php?page=login');
    exit;
}

// Define role-based permissions
$rolePermissions = [
    'admin' => ['dashboard', 'categories', 'products', 'suppliers', 'customers',
                'inventory', 'sales', 'reports', 'users'],
    'cashier' => ['dashboard', 'products', 'customers', 'sales'],
    'manager' => ['dashboard', 'categories', 'products', 'suppliers', 'customers',
                  'inventory', 'sales', 'reports']
];

// Get current user role (default to cashier if not set)
$userRole = $_SESSION['user_role'] ?? 'cashier';

// Check if user has permission to access the requested page
if (!in_array($currentPage, $publicPages, true)) {
    if (!isset($rolePermissions[$userRole]) || !in_array($currentPage, $rolePermissions[$userRole], true)) {
        // Redirect to dashboard if unauthorized
        $_SESSION['flash_error'] = 'You do not have permission to access that page.';
        header('Location: index.php?page=dashboard');
        exit;
    }
}

require_once __DIR__ . '/config.php';

// Handle logout early.
if (isset($_GET['page']) && $_GET['page'] === 'logout') {
    session_destroy();
    header('Location: index.php?page=login');
    exit;
}

// Show login without header/nav if not authenticated.
$page = isset($_GET['page']) ? $_GET['page'] : 'dashboard';

if (!is_logged_in() && $page !== 'login' && $page !== 'seed_admin') {
    header('Location: index.php?page=login');
    exit;
}

if ($page === 'login') {
    include __DIR__ . '/modules/login.php';
    exit;
}

if ($page === 'seed_admin') {
    include __DIR__ . '/seed_admin.php';
    exit;
}

include __DIR__ . '/includes/header.php';
?>
<div class="app-content-wrapper">
    <?php include __DIR__ . '/includes/nav.php'; ?>
    <div style="flex: 1; display: flex; flex-direction: column;">
        <div style="flex: 1;">
            <?php
            if ($msg = flash('success')) {
                echo '<div class="alert alert-success" role="alert">' . sanitize($msg) . '</div>';
            }
            if ($msg = flash('error')) {
                echo '<div class="alert alert-danger" role="alert">' . sanitize($msg) . '</div>';
            }

            $allowedModules = $rolePermissions[$userRole] ?? [];

            if (in_array($page, $allowedModules, true)) {
                include __DIR__ . '/modules/' . $page . '.php';
            } else {
                echo '<main><div class="page-header"><h1 class="page-title">Page not found</h1></div></main>';
            }
            ?>
        </div>
    </div>
</div>
<?php
include __DIR__ . '/includes/footer.php';
