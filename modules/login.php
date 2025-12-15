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
                header('Location: ../index.php');
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
    <style>
        body { display: flex; align-items: center; justify-content: center; min-height: 100vh; background: #111827; color: #fff; font-family: Arial, sans-serif; }
        .card { width: 340px; background: #1f2937; padding: 18px; border-radius: 8px; }
        input { width: 100%; padding: 10px; margin: 8px 0; border: 1px solid #374151; border-radius: 4px; background: #111827; color: #fff; }
        button { width: 100%; padding: 10px; background: #2563eb; color: #fff; border: 0; border-radius: 4px; cursor: pointer; font-weight: bold; }
        .error { background: #7f1d1d; padding: 10px; margin-bottom: 10px; border-radius: 4px; }
    </style>
</head>
<body>
    <div class="card">
        <h2>POS Inventory Login</h2>
        <?php if ($error): ?>
            <div class="error"><?php echo sanitize($error); ?></div>
        <?php endif; ?>
        <form method="post">
            <input type="text" name="username" placeholder="Username" required>
            <input type="password" name="password" placeholder="Password" required>
            <button type="submit">Login</button>
        </form>
        <p style="font-size:12px; margin-top:8px;">Need an account? Run <code>seed_admin.php</code> once to create admin.</p>
    </div>
</body>
</html>

