<?php
/**
 * sidebar.php
 * Reusable Sidebar Navigation Layout
 * Renders medical module navigation links, highlights the active screen,
 * and collapses dynamically on compact viewpoints.
 */

$current_page = basename($_SERVER['PHP_SELF']);
?>
<aside class="sidebar no-print" id="sidebar">
    <div class="sidebar-header">
        <a href="dashboard.php" class="sidebar-brand">
            <i class="fa-solid fa-square-h"></i>
            <span>PRM Portal</span>
        </a>
    </div>
    
    <ul class="sidebar-menu">
        <li class="sidebar-item">
            <a href="dashboard.php" class="sidebar-link <?php echo ($current_page == 'dashboard.php') ? 'active' : ''; ?>">
                <i class="fa-solid fa-chart-pie"></i>
                <span>Home</span>
            </a>
        </li>
        <li class="sidebar-item">
            <a href="patients.php" class="sidebar-link <?php echo ($current_page == 'patients.php') ? 'active' : ''; ?>">
                <i class="fa-solid fa-hospital-user"></i>
                <span>Patients</span>
            </a>
        </li>
        <li class="sidebar-item">
            <a href="doctors.php" class="sidebar-link <?php echo ($current_page == 'doctors.php') ? 'active' : ''; ?>">
                <i class="fa-solid fa-user-doctor"></i>
                <span>Doctors</span>
            </a>
        </li>
        <li class="sidebar-item">
            <a href="visits.php" class="sidebar-link <?php echo ($current_page == 'visits.php') ? 'active' : ''; ?>">
                <i class="fa-solid fa-clipboard-list"></i>
                <span>Visits</span>
            </a>
        </li>
        <li class="sidebar-item">
            <a href="diagnosis.php" class="sidebar-link <?php echo ($current_page == 'diagnosis.php') ? 'active' : ''; ?>">
                <i class="fa-solid fa-stethoscope"></i>
                <span>Diagnosis</span>
            </a>
        </li>
        <li class="sidebar-item">
            <a href="prescriptions.php" class="sidebar-link <?php echo ($current_page == 'prescriptions.php') ? 'active' : ''; ?>">
                <i class="fa-solid fa-prescription-bottle-medical"></i>
                <span>Prescriptions</span>
            </a>
        </li>
        <li class="sidebar-item">
            <a href="reports.php" class="sidebar-link <?php echo ($current_page == 'reports.php') ? 'active' : ''; ?>">
                <i class="fa-solid fa-chart-column"></i>
                <span>Reports</span>
            </a>
        </li>
        <li class="sidebar-item">
            <a href="settings.php" class="sidebar-link <?php echo ($current_page == 'settings.php') ? 'active' : ''; ?>">
                <i class="fa-solid fa-sliders"></i>
                <span>Settings</span>
            </a>
        </li>
    </ul>
    
    <div class="sidebar-footer">
        <a href="logout.php" class="sidebar-link text-white-50 hover-danger">
            <i class="fa-solid fa-right-from-bracket text-danger"></i>
            <span>Logout</span>
        </a>
    </div>
</aside>
