<?php
/**
 * setup.php
 * Web-based Database Auto-Installer and Seeder
 * Assists users in creating the MySQL schema and seeding mock records.
 */

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

$install_status = '';
$error_detail = '';

// Check if setup is triggered
if (isset($_POST['run_setup'])) {
    $db_host = $_POST['db_host'];
    $db_user = $_POST['db_user'];
    $db_pass = $_POST['db_pass'];
    $db_name = $_POST['db_name'];

    try {
        // Connect to MySQL server without selecting DB (it might not exist yet)
        $dsn = "mysql:host=$db_host;charset=utf8mb4";
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];
        
        $pdo = new PDO($dsn, $db_user, $db_pass, $options);
        
        // 1. Create Database
        $pdo->exec("CREATE DATABASE IF NOT EXISTS `$db_name` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;");
        $pdo->exec("USE `$db_name`;");
        
        // 2. Create Tables and Seed (Read database.sql content)
        $sql_file = __DIR__ . '/database.sql';
        if (file_exists($sql_file)) {
            $sql = file_get_contents($sql_file);
            
            // Execute SQL commands in batch
            // Note: Since database.sql uses CREATE DATABASE IF NOT EXISTS hospital_db; USE hospital_db; 
            // it will perfectly conform. Let's run it directly.
            $pdo->exec($sql);
            
            // Clean up session mock if real DB is successfully created
            unset($_SESSION['mock_db_initialized']);
            unset($_SESSION['mock_patients']);
            unset($_SESSION['mock_doctors']);
            unset($_SESSION['mock_visits']);
            unset($_SESSION['mock_diagnoses']);
            unset($_SESSION['mock_prescriptions']);
            
            $install_status = 'success';
        } else {
            $install_status = 'error';
            $error_detail = "Could not find 'database.sql' schema file in the project directory.";
        }
        
    } catch (PDOException $e) {
        $install_status = 'error';
        $error_detail = "MySQL Error: " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Database Setup Wizard | Patient Record Management System</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Google Fonts & FontAwesome Icons -->
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --primary-color: #0b5ed7;
            --primary-hover: #0a58ca;
            --success-color: #0f8a5f;
            --text-dark: #1e293b;
            --bg-light: #f4f7fc;
        }
        
        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background: linear-gradient(135deg, #e0ebf8 0%, #f4f7fc 100%);
            min-height: 100vh;
            color: var(--text-dark);
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .setup-container {
            width: 100%;
            max-width: 580px;
        }

        .setup-card {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 20px;
            box-shadow: 0 15px 35px rgba(15, 76, 129, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.7);
            padding: 40px;
            position: relative;
            overflow: hidden;
        }

        .setup-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 5px;
            background: linear-gradient(to right, var(--primary-color), var(--success-color));
        }

        .logo-box {
            width: 70px;
            height: 70px;
            background: #eef5fc;
            color: var(--primary-color);
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 15px;
            margin: 0 auto 20px;
            font-size: 32px;
            box-shadow: 0 8px 16px rgba(11, 94, 215, 0.1);
        }

        .setup-title {
            font-weight: 700;
            color: #0f4c81;
            text-align: center;
            margin-bottom: 8px;
        }

        .setup-subtitle {
            text-align: center;
            color: #64748b;
            font-size: 14px;
            margin-bottom: 30px;
        }

        .form-label {
            font-weight: 600;
            font-size: 14px;
            color: #475569;
        }

        .form-control {
            border-radius: 10px;
            border: 1px solid #cbd5e1;
            padding: 10px 14px;
            font-size: 14px;
            transition: all 0.3s;
        }

        .form-control:focus {
            box-shadow: 0 0 0 3px rgba(11, 94, 215, 0.15);
            border-color: var(--primary-color);
        }

        .btn-setup {
            background-color: var(--primary-color);
            color: white;
            border: none;
            border-radius: 10px;
            padding: 12px 20px;
            font-weight: 600;
            font-size: 15px;
            width: 100%;
            transition: all 0.3s;
            box-shadow: 0 8px 16px rgba(11, 94, 215, 0.15);
        }

        .btn-setup:hover {
            background-color: var(--primary-hover);
            transform: translateY(-1px);
        }

        .status-alert {
            border-radius: 12px;
            padding: 16px;
            margin-bottom: 25px;
            font-size: 14px;
        }

        .status-success {
            background-color: #d1e7dd;
            border: 1px solid #badbcc;
            color: #0f5132;
        }

        .status-error {
            background-color: #f8d7da;
            border: 1px solid #f5c2c7;
            color: #842029;
        }

        .bullet-point {
            font-size: 13px;
            color: #64748b;
            margin-bottom: 5px;
        }
        
        .bullet-point i {
            color: var(--success-color);
            margin-right: 6px;
        }
    </style>
</head>
<body>

<div class="setup-container">
    <div class="setup-card">
        <div class="logo-box">
            <i class="fa-solid fa-database"></i>
        </div>
        <h3 class="setup-title">Database Setup Wizard</h3>
        <p class="setup-subtitle">Connaught Government Hospital Patient Record Management System</p>

        <?php if ($install_status === 'success'): ?>
            <div class="status-alert status-success">
                <h5 class="fw-bold mb-2"><i class="fa-solid fa-circle-check me-2"></i>Installation Complete!</h5>
                <p class="mb-3">The MySQL tables have been created and successfully seeded with hospital records for Freetown clinics.</p>
                <div class="mb-4">
                    <div class="bullet-point"><i class="fa-solid fa-check"></i> Database <strong>hospital_db</strong> Created</div>
                    <div class="bullet-point"><i class="fa-solid fa-check"></i> Administrator Account (username: <code>admin</code>, password: <code>admin123</code>)</div>
                    <div class="bullet-point"><i class="fa-solid fa-check"></i> Doctor, Patient, Visit, and Diagnosis Mock Datasets Saved</div>
                </div>
                <a href="index.php" class="btn btn-setup" style="background-color: var(--success-color);"><i class="fa-solid fa-right-to-bracket me-2"></i>Go to Login Screen</a>
            </div>
        <?php else: ?>
            
            <?php if ($install_status === 'error'): ?>
                <div class="status-alert status-error">
                    <h5 class="fw-bold mb-1"><i class="fa-solid fa-triangle-exclamation me-2"></i>Setup Failed</h5>
                    <p class="mb-0 small"><?php echo htmlspecialchars($error_detail); ?></p>
                </div>
            <?php endif; ?>

            <form action="setup.php" method="POST">
                <div class="mb-3">
                    <label for="db_host" class="form-label">Database Host</label>
                    <input type="text" class="form-control" id="db_host" name="db_host" value="localhost" required>
                    <div class="form-text small text-muted">Usually "localhost" or "127.0.0.1".</div>
                </div>

                <div class="mb-3">
                    <label for="db_user" class="form-label">Database Username</label>
                    <input type="text" class="form-control" id="db_user" name="db_user" value="root" required>
                    <div class="form-text small text-muted">Default user on local XAMPP/WAMP servers is "root".</div>
                </div>

                <div class="mb-3">
                    <label for="db_pass" class="form-label">Database Password</label>
                    <input type="password" class="form-control" id="db_pass" name="db_pass" placeholder="Leave empty for local default (no password)">
                    <div class="form-text small text-muted">Usually empty on local XAMPP servers.</div>
                </div>

                <div class="mb-4">
                    <label for="db_name" class="form-label">Database Name</label>
                    <input type="text" class="form-control" id="db_name" name="db_name" value="hospital_db" required>
                    <div class="form-text small text-muted">The setup will automatically create this schema.</div>
                </div>

                <button type="submit" name="run_setup" class="btn-setup">
                    <i class="fa-solid fa-gears me-2"></i>Run Database Setup
                </button>
            </form>
            
            <div class="mt-4 pt-3 border-top text-center">
                <span class="text-muted small">Prefer a quick frontend preview?</span><br>
                <a href="index.php" class="text-decoration-none small fw-bold" style="color: var(--primary-color);">
                    <i class="fa-solid fa-circle-play me-1"></i>Launch Application in Demo/Mock Mode
                </a>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Bootstrap JS Bundle -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
