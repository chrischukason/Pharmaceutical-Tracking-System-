<?php
/**
 * dashboard.php
 * Administrative Dashboard
 * Pulls summary counts and recent medical logs. Integrates dynamic visual metrics
 * and lists recent records for streamlined patient triage overview.
 */

require_once 'header.php';

// Initialize core counting and list variables
$total_patients = 0;
$total_doctors = 0;
$total_visits = 0;
$total_prescriptions = 0;
$recent_patients = [];
$recent_diagnoses = [];

if ($db_connected && $pdo) {
    try {
        // Query database statistics
        $total_patients = $pdo->query("SELECT COUNT(*) FROM patients")->fetchColumn();
        $total_doctors = $pdo->query("SELECT COUNT(*) FROM doctors")->fetchColumn();
        $total_visits = $pdo->query("SELECT COUNT(*) FROM visits")->fetchColumn();
        $total_prescriptions = $pdo->query("SELECT COUNT(*) FROM prescriptions")->fetchColumn();

        // Fetch recent patient registrations (limit 5)
        $stmt_pat = $pdo->query("SELECT * FROM patients ORDER BY created_at DESC LIMIT 5");
        $recent_patients = $stmt_pat->fetchAll();

        // Fetch recent diagnoses with join details (limit 5)
        $stmt_diag = $pdo->query("
            SELECT d.*, p.full_name AS patient_name, doc.doctor_name 
            FROM diagnoses d 
            JOIN patients p ON d.patient_id = p.patient_id 
            JOIN doctors doc ON d.doctor_id = doc.doctor_id 
            ORDER BY d.diagnosis_date DESC LIMIT 5
        ");
        $recent_diagnoses = $stmt_diag->fetchAll();

    } catch (PDOException $e) {
        $db_error = "Error loading statistics: " . $e->getMessage();
    }
} else {
    // Demo Fallback Mode: Read counts from PHP session arrays
    $total_patients = count($_SESSION['mock_patients'] ?? []);
    $total_doctors = count($_SESSION['mock_doctors'] ?? []);
    $total_visits = count($_SESSION['mock_visits'] ?? []);
    $total_prescriptions = count($_SESSION['mock_prescriptions'] ?? []);

    // Get 5 recent patients from session array
    if (isset($_SESSION['mock_patients'])) {
        $temp_patients = array_reverse($_SESSION['mock_patients']);
        $recent_patients = array_slice($temp_patients, 0, 5);
    }

    // Compile recent diagnoses from session array
    if (isset($_SESSION['mock_diagnoses'])) {
        $temp_diagnoses = array_reverse($_SESSION['mock_diagnoses']);
        $temp_slice = array_slice($temp_diagnoses, 0, 5);
        
        foreach ($temp_slice as $diag) {
            $patient_id = $diag['patient_id'];
            $doctor_id = $diag['doctor_id'];
            
            $p_name = $_SESSION['mock_patients'][$patient_id]['full_name'] ?? 'Unknown Patient';
            $d_name = $_SESSION['mock_doctors'][$doctor_id]['doctor_name'] ?? 'Unknown Doctor';
            
            $diag['patient_name'] = $p_name;
            $diag['doctor_name'] = $d_name;
            
            $recent_diagnoses[] = $diag;
        }
    }
}
?>

<div class="content-body">
    <!-- Welcome Header Banner -->
    <div class="welcome-banner">
        <h2 class="fw-bold mb-2">Welcome Back, <?php echo htmlspecialchars($_SESSION['admin_name'] ?? 'Administrator'); ?></h2>
        <p class="mb-0 opacity-75">Connaught Government Hospital Patient Record Management system is active. Below is a current overview of outpatient and clinical operations.</p>
    </div>

    <!-- Summary Metrics Cards Grid -->
    <div class="row g-4 mb-5">
        <div class="col-sm-6 col-lg-3">
            <a href="patients.php" class="stat-card stat-blue">
                <div class="stat-details">
                    <h6>Total Patients</h6>
                    <h3><?php echo number_format($total_patients); ?></h3>
                </div>
                <div class="stat-icon">
                    <i class="fa-solid fa-hospital-user"></i>
                </div>
            </a>
        </div>
        
        <div class="col-sm-6 col-lg-3">
            <a href="doctors.php" class="stat-card stat-green">
                <div class="stat-details">
                    <h6>Total Doctors</h6>
                    <h3><?php echo number_format($total_doctors); ?></h3>
                </div>
                <div class="stat-icon">
                    <i class="fa-solid fa-user-doctor"></i>
                </div>
            </a>
        </div>
        
        <div class="col-sm-6 col-lg-3">
            <a href="visits.php" class="stat-card stat-yellow">
                <div class="stat-details">
                    <h6>Total Visits</h6>
                    <h3><?php echo number_format($total_visits); ?></h3>
                </div>
                <div class="stat-icon">
                    <i class="fa-solid fa-clipboard-list"></i>
                </div>
            </a>
        </div>
        
        <div class="col-sm-6 col-lg-3">
            <a href="prescriptions.php" class="stat-card stat-red">
                <div class="stat-details">
                    <h6>Prescriptions</h6>
                    <h3><?php echo number_format($total_prescriptions); ?></h3>
                </div>
                <div class="stat-icon">
                    <i class="fa-solid fa-prescription-bottle-medical"></i>
                </div>
            </a>
        </div>
    </div>

    <!-- Recent Actions and Tables -->
    <div class="row g-4">
        <!-- Recent Patients Registered -->
        <div class="col-xl-6">
            <div class="card-premium">
                <div class="card-premium-header">
                    <h5 class="card-premium-title">
                        <i class="fa-solid fa-user-plus text-primary"></i> Recent Patients
                    </h5>
                    <a href="patients.php" class="btn btn-sm btn-outline-primary" style="border-radius: 8px;">View All</a>
                </div>
                <div class="card-premium-body p-0">
                    <div class="table-responsive">
                        <table class="table table-premium align-middle">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Patient Name</th>
                                    <th>Gender</th>
                                    <th>Phone</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($recent_patients)): ?>
                                    <?php foreach ($recent_patients as $patient): ?>
                                        <tr>
                                            <td class="fw-bold text-primary"><?php echo htmlspecialchars($patient['patient_id']); ?></td>
                                            <td><?php echo htmlspecialchars($patient['full_name']); ?></td>
                                            <td>
                                                <span class="badge-gender <?php echo ($patient['gender'] == 'Male') ? 'badge-male' : 'badge-female'; ?>">
                                                    <?php echo htmlspecialchars($patient['gender']); ?>
                                                </span>
                                            </td>
                                            <td><?php echo htmlspecialchars($patient['phone_number']); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="4" class="text-center text-muted py-4">No recent patients registered.</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Recent Clinical Diagnoses -->
        <div class="col-xl-6">
            <div class="card-premium">
                <div class="card-premium-header">
                    <h5 class="card-premium-title">
                        <i class="fa-solid fa-notes-medical text-accent"></i> Recent Diagnoses
                    </h5>
                    <a href="diagnosis.php" class="btn btn-sm btn-outline-success" style="border-radius: 8px;">View All</a>
                </div>
                <div class="card-premium-body p-0">
                    <div class="table-responsive">
                        <table class="table table-premium align-middle">
                            <thead>
                                <tr>
                                    <th>Patient</th>
                                    <th>Doctor</th>
                                    <th>Diagnosis Details</th>
                                    <th>Date</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($recent_diagnoses)): ?>
                                    <?php foreach ($recent_diagnoses as $diag): ?>
                                        <tr>
                                            <td class="fw-bold"><?php echo htmlspecialchars($diag['patient_name']); ?></td>
                                            <td><?php echo htmlspecialchars($diag['doctor_name']); ?></td>
                                            <td>
                                                <span class="text-truncate d-inline-block" style="max-width: 180px;" title="<?php echo htmlspecialchars($diag['diagnosis_details']); ?>">
                                                    <?php echo htmlspecialchars($diag['diagnosis_details']); ?>
                                                </span>
                                            </td>
                                            <td class="text-muted small"><?php echo date('M d, Y', strtotime($diag['diagnosis_date'])); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="4" class="text-center text-muted py-4">No recent diagnoses recorded.</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
require_once 'footer.php';
?>
