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
                // Set session variables in the new format expected by config.php
                $_SESSION['user_id'] = $row['user_id'];
                $_SESSION['username'] = $row['username'];
                $_SESSION['user_role'] = $row['role'];

                // Also keep the old format for backward compatibility if needed
                $_SESSION['user'] = $row;

                header('Location: index.php');
                exit;
            }
        }
        $error = 'Invalid credentials. Please check your username and password.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - POS Inventory</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="./style.css">

    <style>
        :root {
            --pos-blue-dark: #1e3a8a;
            --pos-blue-light: #eff6ff;
            --pos-blue-accent: #3b82f6;
        }

        /* Modern Bluish Modal Styling */
        .modal-content {
            border: none;
            border-radius: 1.25rem;
            overflow: hidden;
            box-shadow: 0 25px 50px -12px rgba(30, 58, 138, 0.25);
            background: #ffffff;
        }

        .modal-header {
            background: linear-gradient(135deg, #1e3a8a 0%, #3b82f6 100%);
            color: white;
            border-bottom: none;
            padding: 1.5rem;
        }

        .modal-header .btn-close {
            filter: brightness(0) invert(1); /* Makes close button white */
            opacity: 0.8;
        }

        .modal-title {
            font-weight: 700;
            letter-spacing: -0.025em;
        }

        .modal-body {
            padding: 2.5rem 1.5rem;
            background-color: var(--pos-blue-light);
        }

        .error-icon-wrapper {
            width: 60px;
            height: 60px;
            background: #fff;
            color: #ef4444;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2rem;
            margin: 0 auto 1rem;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
        }

        .modal-footer {
            border-top: 1px solid #e5e7eb;
            padding: 1.25rem;
            background: white;
        }

        .btn-modal-close {
            background: var(--pos-blue-dark);
            color: white;
            border-radius: 0.75rem;
            padding: 0.6rem 1.5rem;
            font-weight: 600;
            transition: all 0.2s;
            border: none;
        }

        .btn-modal-close:hover {
            background: #1e40af;
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(30, 58, 138, 0.2);
        }
    </style>
</head>

<body>
    <div class="auth-container">
        <div class="auth-card lift-hover">
            <div class="split-card-content">
                <div class="auth-visual-left">
                    <div class="auth-visual-overlay"></div>
                </div>

                <div class="auth-form-right">
                    <div class="auth-header">
                        <i class="bi bi-shop-window" style="font-size: 2.5rem; color: var(--primary-600); margin-bottom: 0.5rem; display: block;"></i>
                        <h2>POS Inventory</h2>
                        <p>Secure staff sign-in</p>
                    </div>

                    <form method="post" class="login-form">
                        <div class="form-group mb-3">
                            <label class="form-label" for="username">Username</label>
                            <input class="form-control" id="username" type="text" name="username" placeholder="Enter your username" required>
                        </div>

                        <div class="form-group mb-4">
                            <label class="form-label" for="password">Password</label>
                            <input class="form-control" id="password" type="password" name="password" placeholder="Enter your password" required>
                        </div>

                        <button class="btn btn-primary w-100 py-2" type="submit" style="font-weight: 600;">Sign in</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <?php if ($error): ?>
    <div class="modal fade" id="errorModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-sm">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title d-flex align-items-center">
                        <i class="bi bi-shield-lock-fill me-2"></i> Auth System
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body text-center">
                    <div class="error-icon-wrapper">
                        <i class="bi bi-x-circle-fill"></i>
                    </div>
                    <h5 style="color: #1e3a8a; font-weight: 700; margin-bottom: 0.5rem;">Login Failed</h5>
                    <p class="text-muted mb-0" style="font-size: 0.95rem; line-height: 1.5;">
                        <?php echo sanitize($error); ?>
                    </p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-modal-close w-100" data-bs-dismiss="modal">Try Again</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener("DOMContentLoaded", function() {
            var myModal = new bootstrap.Modal(document.getElementById('errorModal'));
            myModal.show();
        });
    </script>
    <?php else: ?>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <?php endif; ?>

</body>
</html>
