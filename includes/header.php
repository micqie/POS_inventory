<?php
// Layout header with modern design
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>POS Inventory System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
</head>
<body>
<div class="app-wrapper">
<header class="navbar">
    <div class="navbar-brand">
        <i class="bi bi-box-seam-fill"></i>
        <span>POS Inventory</span>
    </div>
    <?php if (is_logged_in()): ?>
        <div class="navbar-user">
            <div class="user-info">
                <span class="user-name"><?php echo sanitize(current_user()['username']); ?></span>
                <span class="user-role"><?php echo sanitize(current_user()['role']); ?></span>
            </div>
            <a class="btn btn-outline-secondary btn-sm" href="index.php?page=logout">
                <i class="bi bi-box-arrow-right"></i>
                <span>Logout</span>
            </a>
        </div>
    <?php endif; ?>
</header>
