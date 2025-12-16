<?php
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

            $allowedModules = [
                'dashboard',
                'categories',
                'products',
                'suppliers',
                'customers',
                'inventory',
                'sales',
                'reports',
                'users',
            ];

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
