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
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="./style.css">
</head>
<body>
    <div class="auth-container">
        <div class="auth-card lift-hover">
            <div class="auth-header">
                <h2>POS Inventory</h2>
                <p>Secure staff sign-in</p>
            </div>

            <?php if ($error): ?>
                <div class="alert alert-danger"><?php echo sanitize($error); ?></div>
            <?php endif; ?>

            <form method="post" class="login-form">
                <div class="form-group">
                    <label class="form-label" for="username">Username</label>
                    <input
                        class="form-control"
                        id="username"
                        type="text"
                        name="username"
                        placeholder="Enter your username"
                        required
                    >
                </div>

                <div class="form-group">
                    <label class="form-label" for="password">Password</label>
                    <input
                        class="form-control"
                        id="password"
                        type="password"
                        name="password"
                        placeholder="Enter your password"
                        required
                    >
                </div>

                <button class="btn btn-primary w-100" type="submit">Sign in</button>
            </form>

            <p class="auth-hint">
                First-time setup? Run <code>seed_admin.php</code> once to create the initial administrator account.
            </p>
        </div>
    </div>
</body>
</html>
