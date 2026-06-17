<?php
/**
 * diagnosis.php
 * Diagnosis Entry & Registry Module
 * Records medical evaluations, logs clinical treatments, and links
 * diagnosis history files to subsequent prescriptions.
 */

require_once 'header.php';

$alert_message = '';
$alert_type = '';

$edit_mode = false;
$form_diagnosis_id = '';
$form_patient_id = '';
$form_patient_name = '';
$form_doctor_id = '';
$form_doctor_name = '';
$form_details = '';
$form_notes = '';
$form_date = date('Y-m-d');

// --------------------------------------------------------------------
// BACKEND PHP CRUD ACTIONS PROCESSOR
// --------------------------------------------------------------------

// 1. DELETE ACTION
if (isset($_GET['delete'])) {
    $delete_id = intval($_GET['delete']);
    
    if ($db_connected && $pdo) {
        try {
            $stmt = $pdo->prepare("DELETE FROM diagnoses WHERE id = :id");
            $stmt->execute(['id' => $delete_id]);
            $alert_message = "Diagnosis record <strong>#$delete_id</strong> successfully deleted from database.";
            $alert_type = "success";
        } catch (PDOException $e) {
            $alert_message = "Database Error: " . $e->getMessage();
            $alert_type = "danger";
        }
    } else {
        if (isset($_SESSION['mock_diagnoses'][$delete_id])) {
            unset($_SESSION['mock_diagnoses'][$delete_id]);
            // Also delete cascading prescriptions
            foreach ($_SESSION['mock_prescriptions'] as $k => $v) {
                if ($v['diagnosis_id'] === $delete_id) unset($_SESSION['mock_prescriptions'][$k]);
            }
            $alert_message = "Diagnosis record <strong>#$delete_id</strong> removed from Demo Session.";
            $alert_type = "success";
        }
    }
}

// 2. SAVE & UPDATE PROCESSOR
if (isset($_POST['save_diagnosis'])) {
    $diagnosis_id = isset($_POST['diagnosis_id']) ? intval($_POST['diagnosis_id']) : 0;
    $patient_id = $_POST['patient_id'];
    $doctor_id = $_POST['doctor_id'];
    $diagnosis_details = trim($_POST['diagnosis_details']);
    $treatment_notes = trim($_POST['treatment_notes']);
    $diagnosis_date = $_POST['diagnosis_date'];
    $action_mode = $_POST['action_mode'];

    if (empty($patient_id) || empty($doctor_id) || empty($diagnosis_details) || empty($treatment_notes) || empty($diagnosis_date)) {
        $alert_message = "Please complete all fields before saving the diagnostic record.";
        $alert_type = "danger";
    } else {
        if ($db_connected && $pdo) {
            try {
                if ($action_mode === 'update') {
                    $stmt = $pdo->prepare("
                        UPDATE diagnoses SET 
                        patient_id = :patient_id, doctor_id = :doctor_id, 
                        diagnosis_details = :details, treatment_notes = :notes, 
                        diagnosis_date = :diagnosis_date 
                        WHERE id = :id
                    ");
                    $stmt->execute([
                        'patient_id' => $patient_id, 'doctor_id' => $doctor_id,
                        'details' => $diagnosis_details, 'notes' => $treatment_notes,
                        'diagnosis_date' => $diagnosis_date, 'id' => $diagnosis_id
                    ]);
                    $alert_message = "Diagnosis file <strong>#$diagnosis_id</strong> successfully updated.";
                    $alert_type = "success";
                } else {
                    $stmt = $pdo->prepare("
                        INSERT INTO diagnoses (patient_id, doctor_id, diagnosis_details, treatment_notes, diagnosis_date) 
                        VALUES (:patient_id, :doctor_id, :details, :notes, :diagnosis_date)
                    ");
                    $stmt->execute([
                        'patient_id' => $patient_id, 'doctor_id' => $doctor_id,
                        'details' => $diagnosis_details, 'notes' => $treatment_notes,
                        'diagnosis_date' => $diagnosis_date
                    ]);
                    $alert_message = "New diagnosis and clinical treatment recorded successfully.";
                    $alert_type = "success";
                }
            } catch (PDOException $e) {
                $alert_message = "Database Error: " . $e->getMessage();
                $alert_type = "danger";
            }
        } else {
            // Mock Session CRUD operations
            if ($action_mode === 'update') {
                if (isset($_SESSION['mock_diagnoses'][$diagnosis_id])) {
                    $_SESSION['mock_diagnoses'][$diagnosis_id] = [
                        'id' => $diagnosis_id, 'patient_id' => $patient_id, 'doctor_id' => $doctor_id,
                        'diagnosis_details' => $diagnosis_details, 'treatment_notes' => $treatment_notes,
                        'diagnosis_date' => $diagnosis_date
                    ];
                    $alert_message = "Diagnosis file <strong>#$diagnosis_id</strong> updated in Demo Session.";
                    $alert_type = "success";
                }
            } else {
                $next_mock_id = 1;
                if (!empty($_SESSION['mock_diagnoses'])) {
                    $next_mock_id = max(array_keys($_SESSION['mock_diagnoses'])) + 1;
                }
                $_SESSION['mock_diagnoses'][$next_mock_id] = [
                    'id' => $next_mock_id, 'patient_id' => $patient_id, 'doctor_id' => $doctor_id,
                    'diagnosis_details' => $diagnosis_details, 'treatment_notes' => $treatment_notes,
                    'diagnosis_date' => $diagnosis_date
                ];
                $alert_message = "New diagnosis successfully recorded in Demo Session.";
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
            $stmt = $pdo->prepare("SELECT * FROM diagnoses WHERE id = :id");
            $stmt->execute(['id' => $edit_id]);
            $d = $stmt->fetch();
            if ($d) {
                $edit_mode = true;
                $form_diagnosis_id = $d['id'];
                $form_patient_id = $d['patient_id'];
                $form_doctor_id = $d['doctor_id'];
                $form_details = $d['diagnosis_details'];
                $form_notes = $d['treatment_notes'];
                $form_date = $d['diagnosis_date'];
                
                // Get pre-filled patient name
                $stmt_p = $pdo->prepare("SELECT full_name FROM patients WHERE patient_id = :id");
                $stmt_p->execute(['id' => $form_patient_id]);
                $form_patient_name = $stmt_p->fetchColumn();
            }
        } catch (PDOException $e) {
            $alert_message = "Fetch Error: " . $e->getMessage();
            $alert_type = "danger";
        }
    } else {
        if (isset($_SESSION['mock_diagnoses'][$edit_id])) {
            $d = $_SESSION['mock_diagnoses'][$edit_id];
            $edit_mode = true;
            $form_diagnosis_id = $d['id'];
            $form_patient_id = $d['patient_id'];
            $form_doctor_id = $d['doctor_id'];
            $form_details = $d['diagnosis_details'];
            $form_notes = $d['treatment_notes'];
            $form_date = $d['diagnosis_date'];
            
            $form_patient_name = $_SESSION['mock_patients'][$form_patient_id]['full_name'] ?? '';
        }
    }
}

// --------------------------------------------------------------------
// FETCH RECORDS LISTS FOR FORM SELECTIONS & TABLE
// --------------------------------------------------------------------
$patients_list = [];
$doctors_list = [];
$diagnoses_list = [];

if ($db_connected && $pdo) {
    try {
        $patients_list = $pdo->query("SELECT patient_id, full_name FROM patients ORDER BY full_name ASC")->fetchAll();
        $doctors_list = $pdo->query("SELECT doctor_id, doctor_name, department FROM doctors ORDER BY doctor_name ASC")->fetchAll();
        
        $diag_stmt = $pdo->query("
            SELECT d.*, p.full_name AS patient_name, doc.doctor_name, doc.department 
            FROM diagnoses d 
            JOIN patients p ON d.patient_id = p.patient_id 
            JOIN doctors doc ON d.doctor_id = doc.doctor_id 
            ORDER BY d.diagnosis_date DESC, d.id DESC
        ");
        $diagnoses_list = $diag_stmt->fetchAll();
    } catch (PDOException $e) {
        //
    }
} else {
    $patients_list = $_SESSION['mock_patients'] ?? [];
    $doctors_list = $_SESSION['mock_doctors'] ?? [];
    
    // Build combined table list for Mock Mode
    if (isset($_SESSION['mock_diagnoses'])) {
        foreach ($_SESSION['mock_diagnoses'] as $d) {
            $p_id = $d['patient_id'];
            $doc_id = $d['doctor_id'];
            
            $d['patient_name'] = $_SESSION['mock_patients'][$p_id]['full_name'] ?? 'Unknown Patient';
            $d['doctor_name'] = $_SESSION['mock_doctors'][$doc_id]['doctor_name'] ?? 'Unknown Doctor';
            $d['department'] = $_SESSION['mock_doctors'][$doc_id]['department'] ?? 'General Medicine';
            
            $diagnoses_list[] = $d;
        }
        // sort by date descending
        usort($diagnoses_list, function($a, $b) {
            return strcmp($b['diagnosis_date'], $a['diagnosis_date']);
        });
    }
}
?>

<div class="content-body">
    <!-- Top Module Header -->
    <div class="d-flex align-items-center justify-content-between mb-4">
        <div>
            <h3 class="fw-bold text-primary mb-0"><i class="fa-solid fa-stethoscope me-2"></i>Diagnosis & Treatments</h3>
            <p class="text-muted small mb-0">Record pathological diagnoses, triage findings, and outline comprehensive patient treatment courses</p>
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
        <!-- 1. LEFT SIDE - REGISTER DIAGNOSIS FORM -->
        <div class="col-lg-5">
            <div class="card-premium">
                <div class="card-premium-header">
                    <h5 class="card-premium-title text-primary">
                        <i class="fa-solid <?php echo $edit_mode ? 'fa-file-signature' : 'fa-notes-medical'; ?>"></i>
                        <?php echo $edit_mode ? 'Edit Pathological Log' : 'Record Evaluation File'; ?>
                    </h5>
                </div>
                <div class="card-premium-body">
                    <form action="diagnosis.php" method="POST">
                        <input type="hidden" name="action_mode" value="<?php echo $edit_mode ? 'update' : 'insert'; ?>">
                        <?php if ($edit_mode): ?>
                            <input type="hidden" name="diagnosis_id" value="<?php echo $form_diagnosis_id; ?>">
                        <?php endif; ?>

                        <div class="mb-3">
                            <label for="patient_id_select" class="form-label-premium">Select Patient ID</label>
                            <select class="form-select form-select-premium" id="patient_id_select" name="patient_id" required>
                                <option value="" disabled <?php echo empty($form_patient_id) ? 'selected' : ''; ?>>Search Demographic Registry...</option>
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
                            <label for="patient_name_autofill" class="form-label-premium">Patient Full Name (Autofilled)</label>
                            <input type="text" class="form-control form-control-premium" id="patient_name_autofill" name="patient_name" 
                                   value="<?php echo htmlspecialchars($form_patient_name); ?>" placeholder="Auto-completes from Patient ID Selection" readonly>
                        </div>

                        <div class="mb-3">
                            <label for="doctor_id_select" class="form-label-premium">Diagnosing Medical Professional</label>
                            <select class="form-select form-select-premium" id="doctor_id_select" name="doctor_id" required>
                                <option value="" disabled <?php echo empty($form_doctor_id) ? 'selected' : ''; ?>>Attending practitioner...</option>
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
                            <label for="diagnosis_date" class="form-label-premium">Clinical Diagnostic Date</label>
                            <input type="date" class="form-control form-control-premium" id="diagnosis_date" name="diagnosis_date" 
                                   value="<?php echo htmlspecialchars($form_date); ?>" required>
                        </div>

                        <div class="mb-3">
                            <label for="diagnosis_details" class="form-label-premium">Diagnosis Details / ICD Clinical Class</label>
                            <textarea class="form-control form-control-premium" id="diagnosis_details" name="diagnosis_details" rows="3" 
                                      placeholder="State formal medical diagnosis (e.g. Stage 2 Hypertension, Acute Severe Malaria)..." required><?php echo htmlspecialchars($form_details); ?></textarea>
                        </div>

                        <div class="mb-4">
                            <label for="treatment_notes" class="form-label-premium">Treatment Plan / Prescription Directives</label>
                            <textarea class="form-control form-control-premium" id="treatment_notes" name="treatment_notes" rows="3" 
                                      placeholder="Outline clinical instructions, dietary guidelines, dosages or surgical referrals..." required><?php echo htmlspecialchars($form_notes); ?></textarea>
                        </div>

                        <div class="d-flex flex-wrap gap-2 pt-2 border-top">
                            <?php if ($edit_mode): ?>
                                <button type="submit" name="save_diagnosis" class="btn btn-premium btn-premium-primary flex-grow-1 justify-content-center">
                                    <i class="fa-solid fa-floppy-disk me-1"></i> Update Record
                                </button>
                                <a href="diagnosis.php" class="btn btn-premium btn-premium-secondary">
                                    Cancel
                                </a>
                            <?php else: ?>
                                <button type="submit" name="save_diagnosis" class="btn btn-premium btn-premium-primary flex-grow-1 justify-content-center">
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
                        <i class="fa-solid fa-folder-open"></i> Historical Diagnostic Registry
                    </h5>
                    <div class="input-group search-input-group style-width-override" style="max-width: 250px;">
                        <span class="input-group-text bg-light text-muted border-end-0" style="border-radius: 10px 0 0 10px; border-color: #cbd5e1;">
                            <i class="fa-solid fa-magnifying-glass"></i>
                        </span>
                        <input type="text" class="form-control form-control-premium border-start-0" id="tableSearch" placeholder="Search diagnoses..." style="border-radius: 0 10px 10px 0;">
                    </div>
                </div>
                
                <div class="card-premium-body p-0">
                    <div class="table-responsive">
                        <table class="table table-premium align-middle" id="searchableTable">
                            <thead>
                                <tr>
                                    <th>Patient & Date</th>
                                    <th>Diagnosing Doctor</th>
                                    <th>Clinical Details</th>
                                    <th>Treatment notes</th>
                                    <th class="text-center no-print">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($diagnoses_list)): ?>
                                    <?php foreach ($diagnoses_list as $diag): ?>
                                        <tr>
                                            <td>
                                                <span class="fw-bold text-dark d-block"><?php echo htmlspecialchars($diag['patient_name']); ?></span>
                                                <span class="text-primary font-monospace small fw-bold d-block" style="font-size: 11.5px;"><?php echo htmlspecialchars($diag['patient_id']); ?></span>
                                                <span class="text-muted small" style="font-size: 11px;">Dated: <?php echo date('M d, Y', strtotime($diag['diagnosis_date'])); ?></span>
                                            </td>
                                            <td>
                                                <span class="fw-bold text-dark d-block"><?php echo htmlspecialchars($diag['doctor_name']); ?></span>
                                                <span class="text-muted small" style="font-size: 11px;"><?php echo htmlspecialchars($diag['department']); ?></span>
                                            </td>
                                            <td>
                                                <span class="fw-bold text-danger d-block" style="font-size:13px;"><?php echo htmlspecialchars($diag['diagnosis_details']); ?></span>
                                            </td>
                                            <td>
                                                <span class="d-inline-block text-truncate text-muted small" style="max-width: 140px;" title="<?php echo htmlspecialchars($diag['treatment_notes']); ?>">
                                                    <?php echo htmlspecialchars($diag['treatment_notes']); ?>
                                                </span>
                                            </td>
                                            <td class="text-center no-print">
                                                <div class="d-flex align-items-center justify-content-center gap-2">
                                                    <a href="diagnosis.php?edit=<?php echo urlencode($diag['id']); ?>" class="btn btn-sm btn-outline-primary" style="border-radius:8px;" title="Edit details">
                                                        <i class="fa-solid fa-pen-to-square"></i>
                                                    </a>
                                                    <a href="diagnosis.php?delete=<?php echo urlencode($diag['id']); ?>" class="btn btn-sm btn-outline-danger btn-delete-confirm" style="border-radius:8px;" title="Delete record">
                                                        <i class="fa-solid fa-trash-can"></i>
                                                    </a>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="5" class="text-center text-muted py-4">No diagnostic files logged. Document evaluations using the registration module.</td>
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
