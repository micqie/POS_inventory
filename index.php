<?php
// index.php
// Start output buffering to allow headers after output
ob_start();
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
    'cashier' => ['dashboard', 'products', 'customers', 'sales']

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

// Handle AJAX requests BEFORE including header to avoid HTML output
if (isset($_GET['ajax']) && $_GET['ajax'] === 'sale_details' && isset($_GET['sale_id']) && $page === 'sales') {
    header('Content-Type: application/json');

    $saleId = (int)$_GET['sale_id'];

    if ($saleId <= 0) {
        http_response_code(400);
        echo json_encode([
            'error' => true,
            'message' => 'Invalid sale ID'
        ]);
        exit;
    }

    try {
        // Get sale header info first
        $stmt = $conn->prepare("
            SELECT s.sale_id, s.total_amount, s.sale_date,
                   c.customer_name, u.username
            FROM sales s
            LEFT JOIN customers c ON c.customer_id = s.customer_id
            JOIN users u ON u.user_id = s.user_id
            WHERE s.sale_id = ?
        ");

        if (!$stmt) {
            throw new Exception('Database error: ' . $conn->error);
        }

        $stmt->bind_param('i', $saleId);

        if (!$stmt->execute()) {
            throw new Exception('Database error: ' . $stmt->error);
        }

        $result = $stmt->get_result();
        $saleInfo = $result->fetch_assoc();
        $stmt->close();

        if (!$saleInfo) {
            throw new Exception('Sale not found');
        }

        // Get sale details (products)
        $detailStmt = $conn->prepare('
            SELECT sd.quantity, sd.price, p.product_name
            FROM sale_details sd
            JOIN products p ON p.product_id = sd.product_id
            WHERE sd.sale_id = ?
            ORDER BY p.product_name
        ');

        if (!$detailStmt) {
            throw new Exception('Database error: ' . $conn->error);
        }

        $detailStmt->bind_param('i', $saleId);

        if (!$detailStmt->execute()) {
            $detailStmt->close();
            throw new Exception('Database error: ' . $detailStmt->error);
        }

        $detailResult = $detailStmt->get_result();
        $saleDetails = $detailResult->fetch_all(MYSQLI_ASSOC);
        $detailStmt->close();

        echo json_encode([
            'error' => false,
            'saleInfo' => $saleInfo,
            'details' => $saleDetails ? $saleDetails : []
        ]);

    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode([
            'error' => true,
            'message' => $e->getMessage()
        ]);
    } catch (Error $e) {
        http_response_code(500);
        echo json_encode([
            'error' => true,
            'message' => 'Server error: ' . $e->getMessage()
        ]);
    }
    exit;
}

include __DIR__ . '/includes/header.php';
?>
<div class="app-content-wrapper">
    <?php include __DIR__ . '/includes/nav.php'; ?>
    <div class="main-content-wrapper">
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
<?php
include __DIR__ . '/includes/footer.php';
// Flush output buffer
ob_end_flush();
