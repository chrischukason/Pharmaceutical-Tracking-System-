<?php
/**
 * index.php
 * Secure Authentication Portal
 * Serves as the system gateway. Supports both PDO-based database validation
 * and a secure fallback Mock Mode (user: admin / pass: admin123) for easy evaluation.
 */

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Redirect to dashboard if already logged in
if (isset($_SESSION['admin_user'])) {
    header("Location: dashboard.php");
    exit();
}

require_once 'db.php';

$login_error = '';

if (isset($_POST['login_submit'])) {
    $username = trim($_POST['username']);
    $password = $_POST['password'];

    if (empty($username) || empty($password)) {
        $login_error = "Please fill in all authentication fields.";
    } else {
        // Authenticate against database if online
        if ($db_connected && $pdo) {
            try {
                $stmt = $pdo->prepare("SELECT * FROM users WHERE username = :username LIMIT 1");
                $stmt->execute(['username' => $username]);
                $user = $stmt->fetch();

                // Check password with bcrypt verify (fallback to plain check if hash fails)
                if ($user && (password_verify($password, $user['password']) || $password === $user['password'])) {
                    $_SESSION['admin_user'] = $user['username'];
                    $_SESSION['admin_name'] = $user['full_name'];
                    $_SESSION['admin_role'] = $user['role'];
                    
                    header("Location: dashboard.php");
                    exit();
                } else {
                    $login_error = "Invalid administrator username or password.";
                }
            } catch (PDOException $e) {
                $login_error = "Database Error: " . $e->getMessage();
            }
        } else {
            // Authenticate against Session Mock DB if MySQL is offline
            $mock_authenticated = false;
            
            // Ensure mock_users exists
            if (!isset($_SESSION['mock_users']) || empty($_SESSION['mock_users'])) {
                $login_error = "System error: Demo credentials not available. Please refresh the page.";
            } else {
                foreach ($_SESSION['mock_users'] as $mock_user) {
                    if ($mock_user['username'] === $username && $mock_user['password'] === $password) {
                        $_SESSION['admin_user'] = $mock_user['username'];
                        $_SESSION['admin_name'] = $mock_user['full_name'];
                        $_SESSION['admin_role'] = $mock_user['role'];
                        $mock_authenticated = true;
                        break;
                    }
                }

                if ($mock_authenticated) {
                    header("Location: dashboard.php");
                    exit();
                } else {
                    $login_error = "Invalid credentials (Demo Mode: use admin / admin123).";
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>System Login | Patient Record Management System</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Google Fonts & FontAwesome Icons -->
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <!-- Custom CSS -->
    <link href="assets/css/style.css" rel="stylesheet">
</head>
<body class="login-body">

<div class="login-card">
    <div class="login-logo">
        <i class="fa-solid fa-hospital"></i>
    </div>
    
    <h2 class="login-title">Connaught Hospital</h2>
    <p class="login-subtitle">Patient Record Management System</p>
    
    <!-- Demonstration Helper Badge -->
    <?php if (!$db_connected): ?>
        <div class="alert alert-warning border-0 rounded-3 p-3 mb-4 small" style="background-color: rgba(245, 158, 11, 0.1); color: #d97706;">
            <div class="d-flex gap-2">
                <i class="fa-solid fa-circle-info mt-1"></i>
                <div>
                    <span class="fw-bold d-block">System Mode: Demonstration (Offline)</span>
                    Log in instantly with:
                    <ul class="mb-1 ps-3 mt-1">
                        <li>Username: <code class="fw-bold text-dark">admin</code></li>
                        <li>Password: <code class="fw-bold text-dark">admin123</code></li>
                    </ul>
                    <a href="setup.php" class="alert-link text-decoration-none fw-bold mt-1 d-inline-block text-primary">
                        <i class="fa-solid fa-database me-1"></i>Connect to MySQL Database
                    </a>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <!-- Display login error messages -->
    <?php if (!empty($login_error)): ?>
        <div class="alert alert-danger border-0 rounded-3 p-3 mb-4 small" style="background-color: rgba(220, 53, 69, 0.1); color: var(--danger);">
            <i class="fa-solid fa-triangle-exclamation me-1"></i> <?php echo htmlspecialchars($login_error); ?>
        </div>
    <?php endif; ?>

    <form action="index.php" method="POST">
        <div class="mb-3">
            <label for="username" class="form-label-premium">Username</label>
            <div class="input-group">
                <span class="input-group-text bg-light border-end-0 text-muted" style="border-radius: 10px 0 0 10px; border-color: #cbd5e1;">
                    <i class="fa-solid fa-user"></i>
                </span>
                <input type="text" class="form-control form-control-premium border-start-0" style="border-radius: 0 10px 10px 0;" id="username" name="username" placeholder="Enter administrator username" required>
            </div>
        </div>

        <div class="mb-4">
            <label for="password" class="form-label-premium">Password</label>
            <div class="input-group">
                <span class="input-group-text bg-light border-end-0 text-muted" style="border-radius: 10px 0 0 10px; border-color: #cbd5e1;">
                    <i class="fa-solid fa-lock"></i>
                </span>
                <input type="password" class="form-control form-control-premium border-start-0" style="border-radius: 0 10px 10px 0;" id="password" name="password" placeholder="Enter password" required>
            </div>
        </div>

        <div class="row g-2">
            <div class="col-8">
                <button type="submit" name="login_submit" class="btn btn-premium btn-premium-primary w-100 justify-content-center">
                    <i class="fa-solid fa-right-to-bracket me-1"></i> Login
                </button>
            </div>
            <div class="col-4">
                <button type="reset" class="btn btn-premium btn-premium-secondary w-100 justify-content-center">
                    Reset
                </button>
            </div>
        </div>
    </form>
    
    <div class="mt-4 pt-3 border-top text-center text-muted small">
        Freetown National Referral Hospital &bull; Sierra Leone
    </div>
</div>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
