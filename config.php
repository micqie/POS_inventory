<?php
// Global bootstrap: session + DB connection + small helpers.
session_start();
require_once __DIR__ . '/connect_db.php';
require_once __DIR__ . '/functions.php';

// Redirect to login when not authenticated (except login and seed).
$publicPages = ['login', 'seed_admin'];
$currentPage = isset($_GET['page']) ? $_GET['page'] : 'dashboard';
if (!is_logged_in() && !in_array($currentPage, $publicPages, true)) {
    header('Location: index.php?page=login');
    exit;
}

