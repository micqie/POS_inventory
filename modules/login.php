<?php
require_once __DIR__ . '/../connect_db.php';
require_once __DIR__ . '/../functions.php';

if (is_logged_in()) {
    header('Location: ../index.php');
    exit;
}

$error = null;

if (is_post()) {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($username === '' || $password === '') {
        $error = 'Please provide username and password.';
    } else {
        $stmt = $conn->prepare('SELECT user_id, username, password, role FROM users WHERE username = ? LIMIT 1');
        $stmt->bind_param('s', $username);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($row = $result->fetch_assoc()) {
            if (password_verify($password, $row['password'])) {
                $_SESSION['user'] = $row;
                header('Location: index.php');
                exit;
            }
        }
        $error = 'Invalid credentials.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - POS Inventory</title>
    <link rel="stylesheet" href="style.css">
</head>
<body class="login-body">
    <div class="login-card">
        <h2 class="text-center mb-3">POS Inventory Login</h2>
        <?php if ($error): ?>
            <div class="alert alert-danger"><?php echo sanitize($error); ?></div>
        <?php endif; ?>
        <form method="post" class="login-form">
            <input class="form-control mb-2" type="text" name="username" placeholder="Username" required>
            <input class="form-control mb-3" type="password" name="password" placeholder="Password" required>
            <button class="btn btn-primary w-100" type="submit">Login</button>
        </form>
        <p class="login-hint">Need an account? Run <code>seed_admin.php</code> once to create admin.</p>
    </div>
</body>
</html>

