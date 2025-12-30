<?php
/**
 * Common helpers used across modules.
 */

function sanitize($value)
{
    return htmlspecialchars(trim((string)$value), ENT_QUOTES, 'UTF-8');
}

function is_post()
{
    return $_SERVER['REQUEST_METHOD'] === 'POST';
}

function flash($key, $message = null)
{
    if ($message !== null) {
        $_SESSION['flash'][$key] = $message;
        return;
    }
    if (!empty($_SESSION['flash'][$key])) {
        $msg = $_SESSION['flash'][$key];
        unset($_SESSION['flash'][$key]);
        return $msg;
    }
    return null;
}

function is_logged_in()
{
    // Check for new format (user_id) OR old format (user array)
    return !empty($_SESSION['user_id']) || !empty($_SESSION['user']);
}

function get_current_role()
{
    // Try new format first
    if (isset($_SESSION['user_role'])) {
        return $_SESSION['user_role'];
    }

    // Try old format
    if (isset($_SESSION['user']['role'])) {
        return $_SESSION['user']['role'];
    }

    return null;
}

function current_user()
{
    // Return data in consistent format
    if (isset($_SESSION['user_id'])) {
        return [
            'user_id' => $_SESSION['user_id'],
            'id' => $_SESSION['user_id'], // Also include 'id' for backward compatibility
            'username' => $_SESSION['username'] ?? '',
            'role' => $_SESSION['user_role'] ?? ''
        ];
    }

    // Fallback to old format
    if (isset($_SESSION['user'])) {
        $user = $_SESSION['user'];
        // Ensure user_id exists even if old format uses 'id'
        if (isset($user['id']) && !isset($user['user_id'])) {
            $user['user_id'] = $user['id'];
        }
        return $user;
    }

    return null;
}

function require_role($role)
{
    if (!is_logged_in()) {
        flash('error', 'Please login first.');
        header('Location: index.php?page=login');
        exit;
    }

    $currentRole = get_current_role();
    if ($currentRole !== $role) {
        flash('error', 'You do not have permission to perform this action.');
        header('Location: index.php?page=dashboard');
        exit;
    }
}

function has_permission($page)
{
    $rolePermissions = [
        'admin' => ['dashboard', 'categories', 'products', 'suppliers', 'customers',
                    'inventory', 'sales', 'reports', 'users'],
        'cashier' => ['dashboard', 'products', 'customers', 'sales'],
        'manager' => ['dashboard', 'categories', 'products', 'suppliers', 'customers',
                      'inventory', 'sales', 'reports']
    ];

    $userRole = get_current_role();

    if (!$userRole || !isset($rolePermissions[$userRole])) {
        return false;
    }

    return in_array($page, $rolePermissions[$userRole]);
}

function redirect_if_unauthorized($page)
{
    if (!has_permission($page)) {
        flash('error', 'You do not have permission to access that page.');
        header('Location: index.php?page=dashboard');
        exit;
    }
}
