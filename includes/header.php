<?php
// Layout header with bootstrap + top bar
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>POS Inventory</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link rel="stylesheet" href="style.css">
</head>
<body class="bg-light">
<header class="navbar navbar-dark bg-dark px-3">
    <span class="navbar-brand mb-0 h4">POS Inventory</span>
    <?php if (is_logged_in()): ?>
        <div class="d-flex align-items-center ms-auto">
            <span class="text-white me-3 small">
                <?php echo sanitize(current_user()['username']); ?> (<?php echo sanitize(current_user()['role']); ?>)
            </span>
            <a class="btn btn-outline-light btn-sm" href="index.php?page=logout">Logout</a>
        </div>
    <?php endif; ?>
</header>

