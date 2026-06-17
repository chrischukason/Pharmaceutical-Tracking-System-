<?php
/**
 * header.php
 * Reusable Header Layout
 * Declares system configurations, starts session, validates admin login,
 * and renders the top navigation bar with dynamic database connectivity badges.
 */

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Identify the current page name
$current_page = basename($_SERVER['PHP_SELF']);

// Check if user is logged in (excluding login screen and setup installer)
if (!isset($_SESSION['admin_user']) && $current_page !== 'index.php' && $current_page !== 'setup.php') {
    header("Location: index.php");
    exit();
}

require_once 'db.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Patient Record Management System for Connaught Government Hospital, Freetown, Sierra Leone.">
    <title>Patient Record Management System | Connaught Hospital</title>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Google Fonts (Plus Jakarta Sans) -->
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    
    <!-- FontAwesome 6 Icons -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    
    <!-- Custom Stylesheet -->
    <link href="assets/css/style.css" rel="stylesheet">
</head>
<body>

<?php if ($current_page !== 'index.php' && $current_page !== 'setup.php'): ?>
<div class="app-wrapper">
    
    <!-- Include responsive sidebar layout -->
    <?php include 'sidebar.php'; ?>
    
    <!-- Main content page container -->
    <div class="main-content" id="mainContent">
        
        <!-- Shared Header top navbar -->
        <header class="top-navbar no-print">
            <div class="d-flex align-items-center gap-3">
                <button class="nav-toggle" id="sidebarToggle" title="Toggle Sidebar">
                    <i class="fa-solid fa-bars-staggered"></i>
                </button>
                <div class="d-none d-md-flex flex-column">
                    <span class="fw-bold text-dark mb-0" style="font-size: 15px;">Connaught Government Hospital</span>
                    <span class="text-muted small" style="font-size: 11.5px;">National Referral Hospital &bull; Freetown, Sierra Leone</span>
                </div>
            </div>
            
            <div class="d-flex align-items-center gap-3">
                <!-- Dynamic Database Connection Status Badge -->
                <?php if ($db_connected): ?>
                    <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill px-3 py-2 small d-none d-sm-inline-block">
                        <i class="fa-solid fa-circle me-1" style="font-size: 8px;"></i> MySQL Connected
                    </span>
                <?php else: ?>
                    <a href="setup.php" class="badge bg-warning-subtle text-warning border border-warning-subtle rounded-pill px-3 py-2 small text-decoration-none d-none d-sm-inline-block" title="Click to run database setup wizard">
                        <i class="fa-solid fa-triangle-exclamation me-1" style="font-size: 8px;"></i> Demo Mode (Offline)
                    </a>
                <?php endif; ?>
                
                <!-- Administrator Details -->
                <div class="d-flex align-items-center gap-2 border-start ps-3">
                    <div class="bg-primary-light text-primary rounded-circle d-flex align-items-center justify-content-center" style="width: 38px; height: 38px; font-weight: 700; font-size: 14px;">
                        AK
                    </div>
                    <div class="d-none d-lg-flex flex-column text-start">
                        <span class="fw-bold text-dark small leading-none mb-0"><?php echo htmlspecialchars($_SESSION['admin_name'] ?? 'Administrator'); ?></span>
                        <span class="text-muted" style="font-size: 10px;">Sys Admin</span>
                    </div>
                </div>
            </div>
        </header>
<?php endif; ?>
