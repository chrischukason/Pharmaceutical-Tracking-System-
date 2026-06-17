<?php
/**
 * settings.php
 * Administrative Settings & Configuration
 * Provides controls for administrative credentials, custom hospital branding details,
 * database connectivity reports, and session mock database resets.
 */

require_once 'header.php';

$alert_message = '';
$alert_type = '';

// Handle Settings Submissions
if (isset($_POST['save_hospital_settings'])) {
    $_SESSION['hospital_name'] = trim($_POST['hosp_name']);
    $_SESSION['hospital_address'] = trim($_POST['hosp_address']);
    $_SESSION['hospital_phone'] = trim($_POST['hosp_phone']);
    
    $alert_message = "Hospital branding configurations updated successfully.";
    $alert_type = "success";
}

if (isset($_POST['update_admin_profile'])) {
    $admin_name = trim($_POST['admin_name']);
    $username = trim($_POST['admin_username']);
    $new_pass = $_POST['admin_password'];
    
    if (empty($admin_name) || empty($username)) {
        $alert_message = "Administrator name and username are required.";
        $alert_type = "danger";
    } else {
        $_SESSION['admin_name'] = $admin_name;
        $_SESSION['admin_user'] = $username;
        
        // Update database profile if connected
        if ($db_connected && $pdo) {
            try {
                if (!empty($new_pass)) {
                    $hashed_pass = password_hash($new_pass, PASSWORD_DEFAULT);
                    $stmt = $pdo->prepare("UPDATE users SET full_name = :name, username = :username, password = :pass WHERE role = 'Chief Hospital Administrator'");
                    $stmt->execute(['name' => $admin_name, 'username' => $username, 'pass' => $hashed_pass]);
                } else {
                    $stmt = $pdo->prepare("UPDATE users SET full_name = :name, username = :username WHERE role = 'Chief Hospital Administrator'");
                    $stmt->execute(['name' => $admin_name, 'username' => $username]);
                }
                $alert_message = "Administrator credentials updated successfully in database.";
                $alert_type = "success";
            } catch (PDOException $e) {
                $alert_message = "Database Error: " . $e->getMessage();
                $alert_type = "danger";
            }
        } else {
            // Update in mock session database
            if (!empty($new_pass)) {
                $_SESSION['mock_users'][0]['password'] = $new_pass;
            }
            $_SESSION['mock_users'][0]['full_name'] = $admin_name;
            $_SESSION['mock_users'][0]['username'] = $username;
            
            $alert_message = "Administrator profile updated successfully in Demo Session.";
            $alert_type = "success";
        }
    }
}

// Reset Demo Session Database Trigger
if (isset($_POST['reset_mock_db'])) {
    unset($_SESSION['mock_db_initialized']);
    unset($_SESSION['mock_patients']);
    unset($_SESSION['mock_doctors']);
    unset($_SESSION['mock_visits']);
    unset($_SESSION['mock_diagnoses']);
    unset($_SESSION['mock_prescriptions']);
    
    // Trigger reconnection / reinitialization
    header("Location: settings.php?reset=complete");
    exit();
}

if (isset($_GET['reset']) && $_GET['reset'] === 'complete') {
    $alert_message = "Demonstration Mock Database successfully restored to original factory seeder data.";
    $alert_type = "success";
}
?>

<div class="content-body">
    <!-- Top Module Header -->
    <div class="d-flex align-items-center justify-content-between mb-4">
        <div>
            <h3 class="fw-bold text-primary mb-0"><i class="fa-solid fa-sliders me-2"></i>System Settings</h3>
            <p class="text-muted small mb-0">Configure database credentials, adjust hospital branding, and update administrator files</p>
        </div>
        <a href="dashboard.php" class="btn btn-premium btn-premium-secondary">
            <i class="fa-solid fa-arrow-left"></i> Back to Home
        </a>
    </div>

    <!-- Feedback Alerts -->
    <?php if (!empty($alert_message)): ?>
        <div class="alert alert-<?php echo $alert_type; ?> alert-dismissible fade show border-0 rounded-3 p-3 mb-4" role="alert">
            <i class="fa-solid <?php echo ($alert_type === 'success') ? 'fa-circle-check' : 'fa-circle-exclamation'; ?> me-2"></i>
            <?php echo $alert_message; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <div class="row g-4">
        <!-- 1. LEFT SIDE - HOSPITAL AND ADMIN SETTINGS -->
        <div class="col-lg-7">
            
            <!-- Admin Profile Form -->
            <div class="card-premium mb-4">
                <div class="card-premium-header">
                    <h5 class="card-premium-title text-primary">
                        <i class="fa-solid fa-user-shield"></i> Administrator Profile Credentials
                    </h5>
                </div>
                <div class="card-premium-body">
                    <form action="settings.php" method="POST">
                        <div class="mb-3">
                            <label for="admin_name" class="form-label-premium">Administrator Full Name</label>
                            <input type="text" class="form-control form-control-premium" id="admin_name" name="admin_name" 
                                   value="<?php echo htmlspecialchars($_SESSION['admin_name'] ?? 'Dr. Alusine Kamara'); ?>" required>
                        </div>
                        
                        <div class="row g-3 mb-3">
                            <div class="col-sm-6">
                                <label for="admin_username" class="form-label-premium">System Username</label>
                                <input type="text" class="form-control form-control-premium" id="admin_username" name="admin_username" 
                                       value="<?php echo htmlspecialchars($_SESSION['admin_user'] ?? 'admin'); ?>" required>
                            </div>
                            <div class="col-sm-6">
                                <label for="admin_password" class="form-label-premium">Update Password</label>
                                <input type="password" class="form-control form-control-premium" id="admin_password" name="admin_password" 
                                       placeholder="Leave empty to keep current">
                            </div>
                        </div>

                        <button type="submit" name="update_admin_profile" class="btn btn-premium btn-premium-primary">
                            <i class="fa-solid fa-floppy-disk me-1"></i> Save Profile Settings
                        </button>
                    </form>
                </div>
            </div>

            <!-- Hospital Branding Form -->
            <div class="card-premium">
                <div class="card-premium-header">
                    <h5 class="card-premium-title text-primary">
                        <i class="fa-solid fa-hotel"></i> Hospital Branding & Details
                    </h5>
                </div>
                <div class="card-premium-body">
                    <form action="settings.php" method="POST">
                        <div class="mb-3">
                            <label for="hosp_name" class="form-label-premium">Hospital Official Name</label>
                            <input type="text" class="form-control form-control-premium" id="hosp_name" name="hosp_name" 
                                   value="<?php echo htmlspecialchars($_SESSION['hospital_name'] ?? 'Connaught Government Hospital'); ?>" required>
                            <div class="form-text small text-muted">This name appears on the top nav and printed scripts.</div>
                        </div>

                        <div class="row g-3 mb-3">
                            <div class="col-sm-8">
                                <label for="hosp_address" class="form-label-premium">Branding Address</label>
                                <input type="text" class="form-control form-control-premium" id="hosp_address" name="hosp_address" 
                                       value="<?php echo htmlspecialchars($_SESSION['hospital_address'] ?? 'Siaka Stevens Street, Freetown, Sierra Leone'); ?>" required>
                            </div>
                            <div class="col-sm-4">
                                <label for="hosp_phone" class="form-label-premium">Contact Phone</label>
                                <input type="text" class="form-control form-control-premium" id="hosp_phone" name="hosp_phone" 
                                       value="<?php echo htmlspecialchars($_SESSION['hospital_phone'] ?? '+232 76 123456'); ?>" required>
                            </div>
                        </div>

                        <button type="submit" name="save_hospital_settings" class="btn btn-premium btn-premium-primary">
                            <i class="fa-solid fa-floppy-disk me-1"></i> Save Hospital Details
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <!-- 2. RIGHT SIDE - DATABASE DIAGNOSTICS & CONTROLS -->
        <div class="col-lg-5">
            <!-- Database Mode Status Card -->
            <div class="card-premium mb-4">
                <div class="card-premium-header">
                    <h5 class="card-premium-title text-primary">
                        <i class="fa-solid fa-network-wired"></i> Connection Environment Report
                    </h5>
                </div>
                <div class="card-premium-body">
                    <div class="d-flex align-items-center gap-3 mb-4">
                        <div class="rounded-circle d-flex align-items-center justify-content-center" style="width: 50px; height: 50px; background-color: <?php echo $db_connected ? 'var(--accent-light)' : 'var(--warning-light)'; ?>; color: <?php echo $db_connected ? 'var(--accent)' : 'var(--warning)'; ?>; font-size: 22px;">
                            <i class="fa-solid <?php echo $db_connected ? 'fa-database' : 'fa-triangle-exclamation'; ?>"></i>
                        </div>
                        <div>
                            <span class="text-uppercase text-muted d-block small" style="font-size:10px; font-weight:700; letter-spacing:0.5px;">Active Engine</span>
                            <h5 class="fw-bold text-dark mb-0"><?php echo $db_connected ? 'MySQL Relational Database' : 'Session Mock Mode (Offline)'; ?></h5>
                        </div>
                    </div>

                    <div class="bg-light p-3 rounded-3 mb-4">
                        <span class="text-muted small d-block mb-1">Database Configurations:</span>
                        <div class="font-monospace small">
                            <strong>Host:</strong> localhost<br>
                            <strong>Schema:</strong> hospital_db<br>
                            <strong>Connection Type:</strong> PHP PDO Driver<br>
                            <strong>Status:</strong> <?php echo $db_connected ? '<span class="text-success fw-bold">ONLINE</span>' : '<span class="text-warning fw-bold">OFFLINE</span>'; ?>
                        </div>
                        <?php if (!$db_connected): ?>
                            <div class="text-muted small mt-2 border-top pt-2">
                                <i class="fa-solid fa-triangle-exclamation text-warning me-1"></i>
                                <?php echo htmlspecialchars($db_error_message); ?>
                            </div>
                        <?php endif; ?>
                    </div>

                    <?php if ($db_connected): ?>
                        <div class="alert alert-success border-0 small mb-0 rounded-3">
                            <i class="fa-solid fa-circle-check me-1"></i> Connection operates correctly under relational tables on MySQL server.
                        </div>
                    <?php else: ?>
                        <div class="d-grid gap-2">
                            <a href="setup.php" class="btn btn-premium btn-premium-primary justify-content-center">
                                <i class="fa-solid fa-gears me-1"></i> Run Database Installer Wizard
                            </a>
                            <div class="alert alert-warning border-0 small mb-0 rounded-3 mt-2">
                                <i class="fa-solid fa-circle-info me-1"></i> Running local MySQL database connects the system with relational SQL features.
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Demo Reset Card -->
            <div class="card-premium">
                <div class="card-premium-header">
                    <h5 class="card-premium-title text-danger">
                        <i class="fa-solid fa-triangle-exclamation"></i> Administration Controls
                    </h5>
                </div>
                <div class="card-premium-body">
                    <p class="text-muted small mb-4">Clicking the reset button deletes any newly created patient, doctor, diagnosis, or prescription entries in this demonstration session and restores original seed data.</p>
                    
                    <form action="settings.php" method="POST">
                        <button type="submit" name="reset_mock_db" class="btn btn-premium btn-premium-danger w-100 justify-content-center btn-delete-confirm">
                            <i class="fa-solid fa-arrows-rotate me-1"></i> Reset Demonstration Session
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
require_once 'footer.php';
?>
