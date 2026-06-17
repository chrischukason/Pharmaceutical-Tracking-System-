<?php
/**
 * visits.php
 * Outpatient Visits Module
 * Logs active clinical check-ins, tracks crucial vitals (Blood Pressure, Temperature, Weight),
 * and links patient records to attending physicians.
 */

require_once 'header.php';

$alert_message = '';
$alert_type = '';

$edit_mode = false;
$form_visit_id = '';
$form_patient_id = '';
$form_doctor_id = '';
$form_visit_date = date('Y-m-d');
$form_vitals_bp = '';
$form_vitals_temp = '';
$form_vitals_weight = '';
$form_visit_reason = '';

// --------------------------------------------------------------------
// BACKEND PHP CRUD ACTIONS PROCESSOR
// --------------------------------------------------------------------

// 1. DELETE ACTION
if (isset($_GET['delete'])) {
    $delete_id = intval($_GET['delete']);
    
    if ($db_connected && $pdo) {
        try {
            $stmt = $pdo->prepare("DELETE FROM visits WHERE id = :id");
            $stmt->execute(['id' => $delete_id]);
            $alert_message = "Visit record <strong>#$delete_id</strong> successfully deleted from database.";
            $alert_type = "success";
        } catch (PDOException $e) {
            $alert_message = "Database Error: " . $e->getMessage();
            $alert_type = "danger";
        }
    } else {
        if (isset($_SESSION['mock_visits'][$delete_id])) {
            unset($_SESSION['mock_visits'][$delete_id]);
            $alert_message = "Visit record <strong>#$delete_id</strong> removed from Demo Session.";
            $alert_type = "success";
        }
    }
}

// 2. SAVE & UPDATE PROCESSOR
if (isset($_POST['save_visit'])) {
    $visit_id = isset($_POST['visit_id']) ? intval($_POST['visit_id']) : 0;
    $patient_id = $_POST['patient_id'];
    $doctor_id = $_POST['doctor_id'];
    $visit_date = $_POST['visit_date'];
    $vitals_bp = trim($_POST['vitals_bp']);
    $vitals_temp = floatval($_POST['vitals_temp']);
    $vitals_weight = intval($_POST['vitals_weight']);
    $visit_reason = trim($_POST['visit_reason']);
    $action_mode = $_POST['action_mode'];

    if (empty($patient_id) || empty($doctor_id) || empty($visit_date) || empty($vitals_bp) || empty($vitals_temp) || empty($vitals_weight)) {
        $alert_message = "Please complete all patient clinical vitals and demographics fields.";
        $alert_type = "danger";
    } else {
        if ($db_connected && $pdo) {
            try {
                if ($action_mode === 'update') {
                    $stmt = $pdo->prepare("
                        UPDATE visits SET 
                        patient_id = :patient_id, doctor_id = :doctor_id, 
                        visit_date = :visit_date, vitals_bp = :bp, 
                        vitals_temp = :temp, vitals_weight = :weight, 
                        visit_reason = :reason 
                        WHERE id = :id
                    ");
                    $stmt->execute([
                        'patient_id' => $patient_id, 'doctor_id' => $doctor_id,
                        'visit_date' => $visit_date, 'bp' => $vitals_bp,
                        'temp' => $vitals_temp, 'weight' => $vitals_weight,
                        'reason' => $visit_reason, 'id' => $visit_id
                    ]);
                    $alert_message = "Visit record <strong>#$visit_id</strong> successfully updated.";
                    $alert_type = "success";
                } else {
                    $stmt = $pdo->prepare("
                        INSERT INTO visits (patient_id, doctor_id, visit_date, vitals_bp, vitals_temp, vitals_weight, visit_reason) 
                        VALUES (:patient_id, :doctor_id, :visit_date, :bp, :temp, :weight, :reason)
                    ");
                    $stmt->execute([
                        'patient_id' => $patient_id, 'doctor_id' => $doctor_id,
                        'visit_date' => $visit_date, 'bp' => $vitals_bp,
                        'temp' => $vitals_temp, 'weight' => $vitals_weight,
                        'reason' => $visit_reason
                    ]);
                    $alert_message = "Outpatient visit successfully registered for patient.";
                    $alert_type = "success";
                }
            } catch (PDOException $e) {
                $alert_message = "Database Error: " . $e->getMessage();
                $alert_type = "danger";
            }
        } else {
            // Mock Session CRUD operations
            if ($action_mode === 'update') {
                if (isset($_SESSION['mock_visits'][$visit_id])) {
                    $_SESSION['mock_visits'][$visit_id] = [
                        'id' => $visit_id, 'patient_id' => $patient_id, 'doctor_id' => $doctor_id,
                        'visit_date' => $visit_date, 'vitals_bp' => $vitals_bp,
                        'vitals_temp' => $vitals_temp, 'vitals_weight' => $vitals_weight,
                        'visit_reason' => $visit_reason
                    ];
                    $alert_message = "Visit record <strong>#$visit_id</strong> successfully updated in Demo Session.";
                    $alert_type = "success";
                }
            } else {
                $next_mock_id = 1;
                if (!empty($_SESSION['mock_visits'])) {
                    $next_mock_id = max(array_keys($_SESSION['mock_visits'])) + 1;
                }
                $_SESSION['mock_visits'][$next_mock_id] = [
                    'id' => $next_mock_id, 'patient_id' => $patient_id, 'doctor_id' => $doctor_id,
                    'visit_date' => $visit_date, 'vitals_bp' => $vitals_bp,
                    'vitals_temp' => $vitals_temp, 'vitals_weight' => $vitals_weight,
                    'visit_reason' => $visit_reason
                ];
                $alert_message = "Outpatient visit successfully registered in Demo Session.";
                $alert_type = "success";
            }
        }
    }
}

// 3. EDIT MODE TRIGGER
if (isset($_GET['edit'])) {
    $edit_id = intval($_GET['edit']);
    
    if ($db_connected && $pdo) {
        try {
            $stmt = $pdo->prepare("SELECT * FROM visits WHERE id = :id");
            $stmt->execute(['id' => $edit_id]);
            $v = $stmt->fetch();
            if ($v) {
                $edit_mode = true;
                $form_visit_id = $v['id'];
                $form_patient_id = $v['patient_id'];
                $form_doctor_id = $v['doctor_id'];
                $form_visit_date = $v['visit_date'];
                $form_vitals_bp = $v['vitals_bp'];
                $form_vitals_temp = $v['vitals_temp'];
                $form_vitals_weight = $v['vitals_weight'];
                $form_visit_reason = $v['visit_reason'];
            }
        } catch (PDOException $e) {
            $alert_message = "Fetch Error: " . $e->getMessage();
            $alert_type = "danger";
        }
    } else {
        if (isset($_SESSION['mock_visits'][$edit_id])) {
            $v = $_SESSION['mock_visits'][$edit_id];
            $edit_mode = true;
            $form_visit_id = $v['id'];
            $form_patient_id = $v['patient_id'];
            $form_doctor_id = $v['doctor_id'];
            $form_visit_date = $v['visit_date'];
            $form_vitals_bp = $v['vitals_bp'];
            $form_vitals_temp = $v['vitals_temp'];
            $form_vitals_weight = $v['vitals_weight'];
            $form_visit_reason = $v['visit_reason'];
        }
    }
}

// --------------------------------------------------------------------
// FETCH RECORDS LISTS FOR FORM DROPDOWNS & TABLE
// --------------------------------------------------------------------
$patients_list = [];
$doctors_list = [];
$visits_list = [];

if ($db_connected && $pdo) {
    try {
        $patients_list = $pdo->query("SELECT patient_id, full_name FROM patients ORDER BY full_name ASC")->fetchAll();
        $doctors_list = $pdo->query("SELECT doctor_id, doctor_name, department FROM doctors ORDER BY doctor_name ASC")->fetchAll();
        
        $visits_stmt = $pdo->query("
            SELECT v.*, p.full_name AS patient_name, d.doctor_name, d.department 
            FROM visits v 
            JOIN patients p ON v.patient_id = p.patient_id 
            JOIN doctors d ON v.doctor_id = d.doctor_id 
            ORDER BY v.visit_date DESC, v.id DESC
        ");
        $visits_list = $visits_stmt->fetchAll();
    } catch (PDOException $e) {
        //
    }
} else {
    $patients_list = $_SESSION['mock_patients'] ?? [];
    $doctors_list = $_SESSION['mock_doctors'] ?? [];
    
    // Build combined table list for Mock Mode
    if (isset($_SESSION['mock_visits'])) {
        foreach ($_SESSION['mock_visits'] as $v) {
            $p_id = $v['patient_id'];
            $d_id = $v['doctor_id'];
            
            $v['patient_name'] = $_SESSION['mock_patients'][$p_id]['full_name'] ?? 'Unknown Patient';
            $v['doctor_name'] = $_SESSION['mock_doctors'][$d_id]['doctor_name'] ?? 'Unknown Doctor';
            $v['department'] = $_SESSION['mock_doctors'][$d_id]['department'] ?? 'General Medicine';
            
            $visits_list[] = $v;
        }
        // sort by date descending
        usort($visits_list, function($a, $b) {
            return strcmp($b['visit_date'], $a['visit_date']);
        });
    }
}
?>

<div class="content-body">
    <!-- Top Module Header -->
    <div class="d-flex align-items-center justify-content-between mb-4">
        <div>
            <h3 class="fw-bold text-primary mb-0"><i class="fa-solid fa-clipboard-list me-2"></i>Outpatient Logbook</h3>
            <p class="text-muted small mb-0">Record and review consultations, check-ins, and initial patient triage parameters</p>
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
        <!-- 1. LEFT SIDE - REGISTER VISIT FORM -->
        <div class="col-lg-5">
            <div class="card-premium">
                <div class="card-premium-header">
                    <h5 class="card-premium-title text-primary">
                        <i class="fa-solid <?php echo $edit_mode ? 'fa-clipboard-question' : 'fa-book-medical'; ?>"></i>
                        <?php echo $edit_mode ? 'Edit Check-in Entry' : 'Log Clinical Visit'; ?>
                    </h5>
                </div>
                <div class="card-premium-body">
                    <form action="visits.php" method="POST">
                        <input type="hidden" name="action_mode" value="<?php echo $edit_mode ? 'update' : 'insert'; ?>">
                        <?php if ($edit_mode): ?>
                            <input type="hidden" name="visit_id" value="<?php echo $form_visit_id; ?>">
                        <?php endif; ?>

                        <div class="mb-3">
                            <label for="patient_id_select" class="form-label-premium">Select Patient</label>
                            <select class="form-select form-select-premium" id="patient_id_select" name="patient_id" required>
                                <option value="" disabled <?php echo empty($form_patient_id) ? 'selected' : ''; ?>>Find Patient Name...</option>
                                <?php foreach ($patients_list as $pat): ?>
                                    <?php 
                                        $id = $db_connected ? $pat['patient_id'] : $pat['patient_id']; 
                                        $name = $db_connected ? $pat['full_name'] : $pat['full_name'];
                                    ?>
                                    <option value="<?php echo htmlspecialchars($id); ?>" <?php echo ($form_patient_id === $id) ? 'selected' : ''; ?> data-name="<?php echo htmlspecialchars($name); ?>">
                                        <?php echo htmlspecialchars($id . " - " . $name); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label for="doctor_id_select" class="form-label-premium">Assign Attending Physician</label>
                            <select class="form-select form-select-premium" id="doctor_id_select" name="doctor_id" required>
                                <option value="" disabled <?php echo empty($form_doctor_id) ? 'selected' : ''; ?>>Select Doctor...</option>
                                <?php foreach ($doctors_list as $doc): ?>
                                    <?php 
                                        $id = $db_connected ? $doc['doctor_id'] : $doc['doctor_id']; 
                                        $name = $db_connected ? $doc['doctor_name'] : $doc['doctor_name'];
                                        $dept = $db_connected ? $doc['department'] : $doc['department'];
                                    ?>
                                    <option value="<?php echo htmlspecialchars($id); ?>" <?php echo ($form_doctor_id === $id) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($name . " (" . $dept . ")"); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label for="visit_date" class="form-label-premium">Consultation Date</label>
                            <input type="date" class="form-control form-control-premium" id="visit_date" name="visit_date" 
                                   value="<?php echo htmlspecialchars($form_visit_date); ?>" required>
                        </div>

                        <div class="row g-2 mb-3">
                            <div class="col-sm-4">
                                <label for="vitals_bp" class="form-label-premium">Blood Pressure</label>
                                <input type="text" class="form-control form-control-premium text-center font-monospace" id="vitals_bp" name="vitals_bp" 
                                       value="<?php echo htmlspecialchars($form_vitals_bp); ?>" placeholder="e.g. 120/80" required>
                            </div>
                            
                            <div class="col-sm-4">
                                <label for="vitals_temp" class="form-label-premium">Temp (&deg;C)</label>
                                <input type="number" step="0.1" class="form-control form-control-premium text-center font-monospace" id="vitals_temp" name="vitals_temp" 
                                       value="<?php echo htmlspecialchars($form_vitals_temp); ?>" placeholder="e.g. 37.0" required>
                            </div>
                            
                            <div class="col-sm-4">
                                <label for="vitals_weight" class="form-label-premium">Weight (kg)</label>
                                <input type="number" class="form-control form-control-premium text-center font-monospace" id="vitals_weight" name="vitals_weight" 
                                       value="<?php echo htmlspecialchars($form_vitals_weight); ?>" placeholder="e.g. 70" required>
                            </div>
                        </div>

                        <div class="mb-4">
                            <label for="visit_reason" class="form-label-premium">Consultation Purpose / Vitals Note</label>
                            <textarea class="form-control form-control-premium" id="visit_reason" name="visit_reason" rows="3" 
                                      placeholder="State current conditions or triage assessment details..." required><?php echo htmlspecialchars($form_visit_reason); ?></textarea>
                        </div>

                        <div class="d-flex flex-wrap gap-2 pt-2 border-top">
                            <?php if ($edit_mode): ?>
                                <button type="submit" name="save_visit" class="btn btn-premium btn-premium-primary flex-grow-1 justify-content-center">
                                    <i class="fa-solid fa-floppy-disk me-1"></i> Update Log
                                </button>
                                <a href="visits.php" class="btn btn-premium btn-premium-secondary">
                                    Cancel
                                </a>
                            <?php else: ?>
                                <button type="submit" name="save_visit" class="btn btn-premium btn-premium-primary flex-grow-1 justify-content-center">
                                    <i class="fa-solid fa-floppy-disk me-1"></i> Save
                                </button>
                                <button type="button" class="btn btn-premium btn-premium-secondary btn-clear-form">
                                    Clear
                                </button>
                            <?php endif; ?>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- 2. RIGHT SIDE - DYNAMIC DATA GRID VIEW -->
        <div class="col-lg-7">
            <div class="card-premium">
                <div class="card-premium-header flex-column flex-sm-row gap-3">
                    <h5 class="card-premium-title text-primary my-auto">
                        <i class="fa-solid fa-clock-rotate-left"></i> Consultation Logbook Registry
                    </h5>
                    <div class="input-group search-input-group style-width-override" style="max-width: 250px;">
                        <span class="input-group-text bg-light text-muted border-end-0" style="border-radius: 10px 0 0 10px; border-color: #cbd5e1;">
                            <i class="fa-solid fa-magnifying-glass"></i>
                        </span>
                        <input type="text" class="form-control form-control-premium border-start-0" id="tableSearch" placeholder="Search visits..." style="border-radius: 0 10px 10px 0;">
                    </div>
                </div>
                
                <div class="card-premium-body p-0">
                    <div class="table-responsive">
                        <table class="table table-premium align-middle" id="searchableTable">
                            <thead>
                                <tr>
                                    <th>Patient & Date</th>
                                    <th>Attending Physician</th>
                                    <th>Vitals (BP/Temp/Wt)</th>
                                    <th>Reason for Visit</th>
                                    <th class="text-center no-print">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($visits_list)): ?>
                                    <?php foreach ($visits_list as $v): ?>
                                        <tr>
                                            <td>
                                                <span class="fw-bold text-dark d-block"><?php echo htmlspecialchars($v['patient_name']); ?></span>
                                                <span class="text-primary small fw-bold font-monospace d-block" style="font-size: 11.5px;"><?php echo htmlspecialchars($v['patient_id']); ?></span>
                                                <span class="text-muted small" style="font-size: 11px;">Visited: <?php echo date('M d, Y', strtotime($v['visit_date'])); ?></span>
                                            </td>
                                            <td>
                                                <span class="fw-bold text-dark d-block"><?php echo htmlspecialchars($v['doctor_name']); ?></span>
                                                <span class="text-muted small" style="font-size: 11.5px;"><?php echo htmlspecialchars($v['department']); ?></span>
                                            </td>
                                            <td>
                                                <div class="small leading-tight">
                                                    <span class="d-block text-dark font-monospace">BP: <strong><?php echo htmlspecialchars($v['vitals_bp']); ?></strong></span>
                                                    <span class="d-block text-muted font-monospace">Temp: <strong><?php echo htmlspecialchars($v['vitals_temp']); ?>&deg;C</strong></span>
                                                    <span class="d-block text-muted font-monospace">Weight: <strong><?php echo htmlspecialchars($v['vitals_weight']); ?> kg</strong></span>
                                                </div>
                                            </td>
                                            <td>
                                                <span class="d-inline-block text-truncate text-muted small" style="max-width: 140px;" title="<?php echo htmlspecialchars($v['visit_reason']); ?>">
                                                    <?php echo htmlspecialchars($v['visit_reason']); ?>
                                                </span>
                                            </td>
                                            <td class="text-center no-print">
                                                <div class="d-flex align-items-center justify-content-center gap-2">
                                                    <a href="visits.php?edit=<?php echo urlencode($v['id']); ?>" class="btn btn-sm btn-outline-primary" style="border-radius:8px;" title="Edit log">
                                                        <i class="fa-solid fa-pen-to-square"></i>
                                                    </a>
                                                    <a href="visits.php?delete=<?php echo urlencode($v['id']); ?>" class="btn btn-sm btn-outline-danger btn-delete-confirm" style="border-radius:8px;" title="Delete log">
                                                        <i class="fa-solid fa-trash-can"></i>
                                                    </a>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="5" class="text-center text-muted py-4">No outpatient visits logged. Record clinical check-ins above.</td>
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
