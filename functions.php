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
    return !empty($_SESSION['user']);
}

function current_user()
{
    return $_SESSION['user'] ?? null;
}

function require_role($role)
{
    if (!is_logged_in() || ($_SESSION['user']['role'] ?? '') !== $role) {
        flash('error', 'You are not allowed to perform this action.');
        header('Location: index.php');
        exit;
    }
}

