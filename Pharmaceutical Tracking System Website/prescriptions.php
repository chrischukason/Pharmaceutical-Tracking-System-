<?php
/**
 * prescriptions.php
 * Prescription Module
 * Manages clinical drug dispensing, generates unique RX-XXXX tracking codes,
 * and compiles official, signature-ready pharmacy slips for patient printing.
 */

require_once 'header.php';

$alert_message = '';
$alert_type = '';

$edit_mode = false;
$form_rx_id = '';
$form_diagnosis_id = '';
$form_drug_name = '';
$form_qty = '';
$form_dosage = '';
$form_date = date('Y-m-d');

// Edit view print helper
$print_active = false;
$print_data = null;

// Generate Suggestive Next Prescription ID
function generateNextRxId($db_connected, $pdo) {
    if ($db_connected && $pdo) {
        try {
            $stmt = $pdo->query("SELECT prescription_id FROM prescriptions ORDER BY prescription_id DESC LIMIT 1");
            $last_id = $stmt->fetchColumn();
            if ($last_id) {
                $num = intval(substr($last_id, 3)) + 1;
                return 'RX-' . $num;
            }
        } catch (PDOException $e) {
            // fallback
        }
    } else {
        $last_id = null;
        if (isset($_SESSION['mock_prescriptions']) && !empty($_SESSION['mock_prescriptions'])) {
            $keys = array_keys($_SESSION['mock_prescriptions']);
            sort($keys);
            $last_id = end($keys);
        }
        if ($last_id) {
            $num = intval(substr($last_id, 3)) + 1;
            return 'RX-' . $num;
        }
    }
    return 'RX-1006'; // Starting fallback
}

$suggested_id = generateNextRxId($db_connected, $pdo);

// --------------------------------------------------------------------
// BACKEND PHP CRUD ACTIONS PROCESSOR
// --------------------------------------------------------------------

// 1. DELETE ACTION
if (isset($_GET['delete'])) {
    $delete_id = $_GET['delete'];
    
    if ($db_connected && $pdo) {
        try {
            $stmt = $pdo->prepare("DELETE FROM prescriptions WHERE prescription_id = :id");
            $stmt->execute(['id' => $delete_id]);
            $alert_message = "Prescription <strong>$delete_id</strong> successfully deleted from database.";
            $alert_type = "success";
            $suggested_id = generateNextRxId($db_connected, $pdo);
        } catch (PDOException $e) {
            $alert_message = "Database Error: " . $e->getMessage();
            $alert_type = "danger";
        }
    } else {
        if (isset($_SESSION['mock_prescriptions'][$delete_id])) {
            unset($_SESSION['mock_prescriptions'][$delete_id]);
            $alert_message = "Prescription <strong>$delete_id</strong> removed from Demo Session.";
            $alert_type = "success";
            $suggested_id = generateNextRxId($db_connected, $pdo);
        }
    }
}

// 2. SAVE & UPDATE PROCESSOR
if (isset($_POST['save_prescription'])) {
    $prescription_id = strtoupper(trim($_POST['prescription_id']));
    $diagnosis_id = intval($_POST['diagnosis_id']);
    $drug_name = trim($_POST['drug_name']);
    $quantity = intval($_POST['quantity']);
    $dosage = trim($_POST['dosage']);
    $prescription_date = $_POST['prescription_date'];
    $action_mode = $_POST['action_mode'];

    if (empty($prescription_id) || empty($diagnosis_id) || empty($drug_name) || empty($quantity) || empty($dosage) || empty($prescription_date)) {
        $alert_message = "Required prescription details and instructions cannot be empty.";
        $alert_type = "danger";
    } else {
        if ($db_connected && $pdo) {
            try {
                if ($action_mode === 'update') {
                    $stmt = $pdo->prepare("
                        UPDATE prescriptions SET 
                        diagnosis_id = :diag_id, drug_name = :drug, 
                        quantity = :qty, dosage = :dosage, 
                        prescription_date = :rx_date 
                        WHERE prescription_id = :rx_id
                    ");
                    $stmt->execute([
                        'diag_id' => $diagnosis_id, 'drug' => $drug_name,
                        'qty' => $quantity, 'dosage' => $dosage,
                        'rx_date' => $prescription_date, 'rx_id' => $prescription_id
                    ]);
                    $alert_message = "Prescription <strong>$prescription_id</strong> successfully updated.";
                    $alert_type = "success";
                } else {
                    // Check duplicate ID
                    $stmt_check = $pdo->prepare("SELECT COUNT(*) FROM prescriptions WHERE prescription_id = :id");
                    $stmt_check->execute(['id' => $prescription_id]);
                    if ($stmt_check->fetchColumn() > 0) {
                        $alert_message = "Prescription ID <strong>$prescription_id</strong> already exists in database.";
                        $alert_type = "danger";
                    } else {
                        $stmt = $pdo->prepare("
                            INSERT INTO prescriptions (prescription_id, diagnosis_id, drug_name, quantity, dosage, prescription_date) 
                            VALUES (:rx_id, :diag_id, :drug, :qty, :dosage, :rx_date)
                        ");
                        $stmt->execute([
                            'rx_id' => $prescription_id, 'diag_id' => $diagnosis_id,
                            'drug' => $drug_name, 'qty' => $quantity,
                            'dosage' => $dosage, 'rx_date' => $prescription_date
                        ]);
                        $alert_message = "Prescription <strong>$prescription_id</strong> recorded successfully.";
                        $alert_type = "success";
                        $suggested_id = generateNextRxId($db_connected, $pdo);
                    }
                }
            } catch (PDOException $e) {
                $alert_message = "Database Error: " . $e->getMessage();
                $alert_type = "danger";
            }
        } else {
            // Demo Session CRUD
            if ($action_mode === 'update') {
                if (isset($_SESSION['mock_prescriptions'][$prescription_id])) {
                    $_SESSION['mock_prescriptions'][$prescription_id] = [
                        'prescription_id' => $prescription_id, 'diagnosis_id' => $diagnosis_id,
                        'drug_name' => $drug_name, 'quantity' => $quantity,
                        'dosage' => $dosage, 'prescription_date' => $prescription_date
                    ];
                    $alert_message = "Prescription <strong>$prescription_id</strong> updated in Demo Session.";
                    $alert_type = "success";
                }
            } else {
                if (isset($_SESSION['mock_prescriptions'][$prescription_id])) {
                    $alert_message = "Prescription ID <strong>$prescription_id</strong> exists in mock records.";
                    $alert_type = "danger";
                } else {
                    $_SESSION['mock_prescriptions'][$prescription_id] = [
                        'prescription_id' => $prescription_id, 'diagnosis_id' => $diagnosis_id,
                        'drug_name' => $drug_name, 'quantity' => $quantity,
                        'dosage' => $dosage, 'prescription_date' => $prescription_date
                    ];
                    $alert_message = "New prescription <strong>$prescription_id</strong> recorded in Demo Session.";
                    $alert_type = "success";
                    $suggested_id = generateNextRxId($db_connected, $pdo);
                }
            }
        }
    }
}

// 3. EDIT MODE TRIGGER
if (isset($_GET['edit'])) {
    $edit_id = $_GET['edit'];
    
    if ($db_connected && $pdo) {
        try {
            $stmt = $pdo->prepare("SELECT * FROM prescriptions WHERE prescription_id = :id");
            $stmt->execute(['id' => $edit_id]);
            $rx = $stmt->fetch();
            if ($rx) {
                $edit_mode = true;
                $form_rx_id = $rx['prescription_id'];
                $form_diagnosis_id = $rx['diagnosis_id'];
                $form_drug_name = $rx['drug_name'];
                $form_qty = $rx['quantity'];
                $form_dosage = $rx['dosage'];
                $form_date = $rx['prescription_date'];
            }
        } catch (PDOException $e) {
            $alert_message = "Fetch Error: " . $e->getMessage();
            $alert_type = "danger";
        }
    } else {
        if (isset($_SESSION['mock_prescriptions'][$edit_id])) {
            $rx = $_SESSION['mock_prescriptions'][$edit_id];
            $edit_mode = true;
            $form_rx_id = $rx['prescription_id'];
            $form_diagnosis_id = $rx['diagnosis_id'];
            $form_drug_name = $rx['drug_name'];
            $form_qty = $rx['quantity'];
            $form_dosage = $rx['dosage'];
            $form_date = $rx['prescription_date'];
        }
    }
}

// 4. GENERATE SPECIFIC PRINT DATA PREVIEW
if (isset($_GET['print'])) {
    $print_id = $_GET['print'];
    
    if ($db_connected && $pdo) {
        try {
            $stmt = $pdo->prepare("
                SELECT r.*, d.diagnosis_details, d.treatment_notes, 
                       p.full_name AS patient_name, p.patient_id, p.age, p.gender, p.address,
                       doc.doctor_name, doc.department
                FROM prescriptions r 
                JOIN diagnoses d ON r.diagnosis_id = d.id 
                JOIN patients p ON d.patient_id = p.patient_id 
                JOIN doctors doc ON d.doctor_id = doc.doctor_id 
                WHERE r.prescription_id = :id
            ");
            $stmt->execute(['id' => $print_id]);
            $print_data = $stmt->fetch();
            if ($print_data) {
                $print_active = true;
            }
        } catch (PDOException $e) {
            //
        }
    } else {
        if (isset($_SESSION['mock_prescriptions'][$print_id])) {
            $rx = $_SESSION['mock_prescriptions'][$print_id];
            $diag_id = $rx['diagnosis_id'];
            
            $diag = $_SESSION['mock_diagnoses'][$diag_id] ?? null;
            if ($diag) {
                $p_id = $diag['patient_id'];
                $doc_id = $diag['doctor_id'];
                
                $p = $_SESSION['mock_patients'][$p_id] ?? null;
                $doc = $_SESSION['mock_doctors'][$doc_id] ?? null;
                
                $print_data = [
                    'prescription_id' => $rx['prescription_id'],
                    'drug_name' => $rx['drug_name'],
                    'quantity' => $rx['quantity'],
                    'dosage' => $rx['dosage'],
                    'prescription_date' => $rx['prescription_date'],
                    'diagnosis_details' => $diag['diagnosis_details'],
                    'treatment_notes' => $diag['treatment_notes'],
                    'patient_id' => $p_id,
                    'patient_name' => $p['full_name'] ?? 'Unknown',
                    'age' => $p['age'] ?? 'N/A',
                    'gender' => $p['gender'] ?? 'N/A',
                    'address' => $p['address'] ?? 'N/A',
                    'doctor_name' => $doc['doctor_name'] ?? 'Unknown',
                    'department' => $doc['department'] ?? 'General Medicine'
                ];
                $print_active = true;
            }
        }
    }
}

// --------------------------------------------------------------------
// FETCH RECORDS LISTS FOR FORM DROPDOWNS & TABLE
// --------------------------------------------------------------------
$diagnoses_dropdown = [];
$prescriptions_list = [];

if ($db_connected && $pdo) {
    try {
        // Fetch diagnoses for selection
        $diagnoses_dropdown = $pdo->query("
            SELECT d.id, d.diagnosis_details, d.diagnosis_date, p.full_name AS patient_name 
            FROM diagnoses d 
            JOIN patients p ON d.patient_id = p.patient_id 
            ORDER BY d.diagnosis_date DESC
        ")->fetchAll();

        // Fetch prescription registry
        $stmt_list = $pdo->query("
            SELECT r.*, p.full_name AS patient_name, d.diagnosis_details, doc.doctor_name 
            FROM prescriptions r 
            JOIN diagnoses d ON r.diagnosis_id = d.id 
            JOIN patients p ON d.patient_id = p.patient_id 
            JOIN doctors doc ON d.doctor_id = doc.doctor_id 
            ORDER BY r.prescription_date DESC
        ");
        $prescriptions_list = $stmt_list->fetchAll();
    } catch (PDOException $e) {
        //
    }
} else {
    // Session Mock lists
    if (isset($_SESSION['mock_diagnoses'])) {
        foreach ($_SESSION['mock_diagnoses'] as $d) {
            $p_id = $d['patient_id'];
            $d['patient_name'] = $_SESSION['mock_patients'][$p_id]['full_name'] ?? 'Unknown Patient';
            $diagnoses_dropdown[] = $d;
        }
    }
    
    if (isset($_SESSION['mock_prescriptions'])) {
        foreach ($_SESSION['mock_prescriptions'] as $rx) {
            $diag_id = $rx['diagnosis_id'];
            $diag = $_SESSION['mock_diagnoses'][$diag_id] ?? null;
            
            if ($diag) {
                $p_id = $diag['patient_id'];
                $doc_id = $diag['doctor_id'];
                
                $rx['patient_name'] = $_SESSION['mock_patients'][$p_id]['full_name'] ?? 'Unknown';
                $rx['diagnosis_details'] = $diag['diagnosis_details'];
                $rx['doctor_name'] = $_SESSION['mock_doctors'][$doc_id]['doctor_name'] ?? 'Unknown';
                
                $prescriptions_list[] = $rx;
            }
        }
    }
}
?>

<div class="content-body">
    <!-- Top Module Header -->
    <div class="d-flex align-items-center justify-content-between mb-4 no-print">
        <div>
            <h3 class="fw-bold text-primary mb-0"><i class="fa-solid fa-prescription-bottle-medical me-2"></i>Pharmacy Dispensing</h3>
            <p class="text-muted small mb-0">Record outpatient pharmaceutical supplies, configure dosing frequencies, and print official slips</p>
        </div>
        <a href="dashboard.php" class="btn btn-premium btn-premium-secondary">
            <i class="fa-solid fa-arrow-left"></i> Back to Home
        </a>
    </div>

    <!-- Printable Pharmacy Slip Overlay (Always active when print param triggered) -->
    <?php if ($print_active && $print_data): ?>
        <div class="card-premium show-screen-print mb-4 border border-dark rounded-3">
            <div id="printablePrescription" class="print-receipt-sheet">
                
                <!-- Shield and Banner Header -->
                <div class="print-header d-flex align-items-center justify-content-between pb-3 mb-4">
                    <div class="d-flex align-items-center gap-3">
                        <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center" style="width: 60px; height: 60px; font-weight: 800; font-size: 26px;">
                            H
                        </div>
                        <div class="text-start">
                            <h4 class="print-title fw-bold text-dark mb-0">CONNAUGHT GOVERNMENT HOSPITAL</h4>
                            <span class="text-muted small" style="font-size: 11px;">Ministry of Health & Sanitation &bull; Siaka Stevens Street, Freetown, Sierra Leone</span>
                        </div>
                    </div>
                    <div class="text-end font-monospace text-muted small leading-tight">
                        <strong>SL-PRMS REGISTRY</strong><br>
                        Slip ID: <?php echo htmlspecialchars($print_data['prescription_id']); ?><br>
                        Date: <?php echo date('d-M-Y', strtotime($print_data['prescription_date'])); ?>
                    </div>
                </div>

                <!-- Patient Demographics Section -->
                <div class="row g-3 mb-4 border-bottom pb-3">
                    <div class="col-sm-6 text-start">
                        <span class="text-uppercase text-muted d-block small" style="font-size:10px; font-weight:700; letter-spacing:0.5px;">Patient Demographics</span>
                        <h5 class="fw-bold text-dark mb-1"><?php echo htmlspecialchars($print_data['patient_name']); ?></h5>
                        <span class="text-muted small d-block">ID Code: <strong><?php echo htmlspecialchars($print_data['patient_id']); ?></strong></span>
                        <span class="text-muted small">Age/Sex: <?php echo htmlspecialchars($print_data['age']); ?> Yrs / <?php echo htmlspecialchars($print_data['gender']); ?></span>
                    </div>
                    <div class="col-sm-6 text-end">
                        <span class="text-uppercase text-muted d-block small" style="font-size:10px; font-weight:700; letter-spacing:0.5px;">Consulting Staff</span>
                        <h6 class="fw-bold text-dark mb-1"><?php echo htmlspecialchars($print_data['doctor_name']); ?></h6>
                        <span class="text-muted small d-block"><?php echo htmlspecialchars($print_data['department']); ?></span>
                        <span class="text-muted small">Address: <?php echo htmlspecialchars($print_data['address']); ?></span>
                    </div>
                </div>

                <!-- Medical Evaluation Details -->
                <div class="bg-light p-3 rounded-3 mb-4 text-start">
                    <span class="text-uppercase text-muted d-block small mb-2" style="font-size:10px; font-weight:700; letter-spacing:0.5px;">Pathology Assessment</span>
                    <p class="text-danger fw-bold mb-1" style="font-size: 14px;"><?php echo htmlspecialchars($print_data['diagnosis_details']); ?></p>
                    <p class="text-muted small mb-0"><strong>Clinical Notes:</strong> <?php echo htmlspecialchars($print_data['treatment_notes']); ?></p>
                </div>

                <!-- Prescription Drug Listing -->
                <div class="mb-5">
                    <span class="text-uppercase text-muted d-block small mb-2 text-start" style="font-size:10px; font-weight:700; letter-spacing:0.5px;">Medication Ordered (Rx)</span>
                    <table class="table border align-middle text-start">
                        <thead>
                            <tr class="table-light">
                                <th class="py-2 px-3 text-uppercase font-weight-bold" style="font-size:11px;">Drug Formulation</th>
                                <th class="py-2 px-3 text-uppercase text-center font-weight-bold" style="font-size:11px;" width="150">Quantity Supplied</th>
                                <th class="py-2 px-3 text-uppercase font-weight-bold" style="font-size:11px;">Dosing Instruction</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td class="py-3 px-3 fw-bold text-primary"><?php echo htmlspecialchars($print_data['drug_name']); ?></td>
                                <td class="py-3 px-3 text-center fw-bold font-monospace"><?php echo htmlspecialchars($print_data['quantity']); ?> Unit(s)</td>
                                <td class="py-3 px-3 text-muted"><?php echo htmlspecialchars($print_data['dosage']); ?></td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Signature Authority Panel -->
                <div class="row pt-5 mt-5">
                    <div class="col-6 text-start">
                        <div class="border-top border-dark mx-auto pt-2" style="width: 200px; margin-left:0 !important;">
                            <span class="text-muted small">Patient Acknowledgment</span>
                        </div>
                    </div>
                    <div class="col-6 text-end">
                        <div class="border-top border-dark mx-auto pt-2" style="width: 200px; margin-right:0 !important;">
                            <span class="text-muted small">Authorized Medical Signature</span>
                        </div>
                    </div>
                </div>

            </div>
            
            <div class="card-premium-footer border-top bg-light p-3 d-flex justify-content-end gap-2 no-print">
                <button onclick="printMedicalRecord('printablePrescription')" class="btn btn-premium btn-premium-accent">
                    <i class="fa-solid fa-print"></i> Print Official Slip
                </button>
                <a href="prescriptions.php" class="btn btn-premium btn-premium-secondary">
                    Close Slip Viewer
                </a>
            </div>
        </div>
    <?php endif; ?>

    <!-- Feedback Alerts -->
    <?php if (!empty($alert_message)): ?>
        <div class="alert alert-<?php echo $alert_type; ?> alert-dismissible fade show border-0 rounded-3 p-3 mb-4 no-print" role="alert">
            <i class="fa-solid <?php echo ($alert_type === 'success') ? 'fa-circle-check' : 'fa-circle-exclamation'; ?> me-2"></i>
            <?php echo $alert_message; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <div class="row g-4 no-print">
        <!-- 1. LEFT SIDE - NEW PRESCRIPTION FORM -->
        <div class="col-lg-5">
            <div class="card-premium">
                <div class="card-premium-header">
                    <h5 class="card-premium-title text-primary">
                        <i class="fa-solid <?php echo $edit_mode ? 'fa-prescription' : 'fa-file-medical'; ?>"></i>
                        <?php echo $edit_mode ? 'Edit Prescription File' : 'Issue Pharmacy Rx'; ?>
                    </h5>
                </div>
                <div class="card-premium-body">
                    <form action="prescriptions.php" method="POST">
                        <input type="hidden" name="action_mode" value="<?php echo $edit_mode ? 'update' : 'insert'; ?>">
                        
                        <div class="mb-3">
                            <label for="prescription_id" class="form-label-premium">Prescription ID</label>
                            <input type="text" class="form-control form-control-premium text-uppercase" id="prescription_id" name="prescription_id" 
                                   value="<?php echo $edit_mode ? htmlspecialchars($form_rx_id) : htmlspecialchars($suggested_id); ?>" 
                                   <?php echo $edit_mode ? 'readonly' : ''; ?> required>
                            <div class="form-text small text-muted">Unique billing and track code formatted as RX-XXXX.</div>
                        </div>

                        <div class="mb-3">
                            <label for="diagnosis_id" class="form-label-premium">Select Case File (Patient + Pathology)</label>
                            <select class="form-select form-select-premium" id="diagnosis_id" name="diagnosis_id" required>
                                <option value="" disabled <?php echo empty($form_diagnosis_id) ? 'selected' : ''; ?>>Select Diagnostic Log...</option>
                                <?php foreach ($diagnoses_dropdown as $diag): ?>
                                    <?php 
                                        $id = $db_connected ? $diag['id'] : $diag['id']; 
                                        $p_name = $db_connected ? $diag['patient_name'] : $diag['patient_name'];
                                        $details = $db_connected ? $diag['diagnosis_details'] : $diag['diagnosis_details'];
                                        $date = $db_connected ? $diag['diagnosis_date'] : $diag['diagnosis_date'];
                                    ?>
                                    <option value="<?php echo htmlspecialchars($id); ?>" <?php echo ($form_diagnosis_id == $id) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($p_name . " - " . $details . " (" . date('M d', strtotime($date)) . ")"); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label for="drug_name" class="form-label-premium">Drug Formulation Name</label>
                            <input type="text" class="form-control form-control-premium" id="drug_name" name="drug_name" 
                                   value="<?php echo htmlspecialchars($form_drug_name); ?>" placeholder="e.g. Paracetamol 500mg, Amoxicillin Syrup" required>
                            <div class="form-text small text-muted">Standard brand formulation or active pharmaceutical ingredient (API).</div>
                        </div>

                        <div class="row g-3 mb-3">
                            <div class="col-sm-6">
                                <label for="quantity" class="form-label-premium">Dispense Quantity</label>
                                <input type="number" class="form-control form-control-premium" id="quantity" name="quantity" 
                                       value="<?php echo htmlspecialchars($form_qty); ?>" placeholder="Total capsules/vials" required>
                            </div>
                            
                            <div class="col-sm-6">
                                <label for="prescription_date" class="form-label-premium">Dispensing Date</label>
                                <input type="date" class="form-control form-control-premium" id="prescription_date" name="prescription_date" 
                                       value="<?php echo htmlspecialchars($form_date); ?>" required>
                            </div>
                        </div>

                        <div class="mb-4">
                            <label for="dosage" class="form-label-premium">Frequency & Dosage Instruction</label>
                            <input type="text" class="form-control form-control-premium" id="dosage" name="dosage" 
                                   value="<?php echo htmlspecialchars($form_dosage); ?>" placeholder="e.g. 1 tablet every 8 hours for 5 days" required>
                        </div>

                        <div class="d-flex flex-wrap gap-2 pt-2 border-top">
                            <?php if ($edit_mode): ?>
                                <button type="submit" name="save_prescription" class="btn btn-premium btn-premium-primary flex-grow-1 justify-content-center">
                                    <i class="fa-solid fa-floppy-disk me-1"></i> Update Rx
                                </button>
                                <a href="prescriptions.php" class="btn btn-premium btn-premium-secondary">
                                    Cancel
                                </a>
                            <?php else: ?>
                                <button type="submit" name="save_prescription" class="btn btn-premium btn-premium-primary flex-grow-1 justify-content-center">
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
                        <i class="fa-solid fa-pills"></i> Pharmacy Order Logs
                    </h5>
                    <div class="input-group search-input-group style-width-override" style="max-width: 250px;">
                        <span class="input-group-text bg-light text-muted border-end-0" style="border-radius: 10px 0 0 10px; border-color: #cbd5e1;">
                            <i class="fa-solid fa-magnifying-glass"></i>
                        </span>
                        <input type="text" class="form-control form-control-premium border-start-0" id="tableSearch" placeholder="Search pharmacy logs..." style="border-radius: 0 10px 10px 0;">
                    </div>
                </div>
                
                <div class="card-premium-body p-0">
                    <div class="table-responsive">
                        <table class="table table-premium align-middle" id="searchableTable">
                            <thead>
                                <tr>
                                    <th>Rx ID & Date</th>
                                    <th>Patient & Diagnosing Doctor</th>
                                    <th>Formulation Supplied</th>
                                    <th>Dose Frequency</th>
                                    <th class="text-center">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($prescriptions_list)): ?>
                                    <?php foreach ($prescriptions_list as $rx): ?>
                                        <tr>
                                            <td class="fw-bold text-primary">
                                                <?php echo htmlspecialchars($rx['prescription_id']); ?><br>
                                                <span class="text-muted small font-sans" style="font-size: 11px; font-weight: normal;">Issued: <?php echo date('M d, Y', strtotime($rx['prescription_date'])); ?></span>
                                            </td>
                                            <td>
                                                <span class="fw-bold text-dark d-block"><?php echo htmlspecialchars($rx['patient_name']); ?></span>
                                                <span class="text-muted small d-block">Attending: <?php echo htmlspecialchars($rx['doctor_name']); ?></span>
                                                <span class="text-truncate text-muted d-block small" style="max-width: 140px; font-size: 11px;" title="<?php echo htmlspecialchars($rx['diagnosis_details']); ?>">Case: <?php echo htmlspecialchars($rx['diagnosis_details']); ?></span>
                                            </td>
                                            <td>
                                                <span class="fw-bold text-success d-block"><?php echo htmlspecialchars($rx['drug_name']); ?></span>
                                                <span class="text-muted small">Quantity: <strong><?php echo htmlspecialchars($rx['quantity']); ?> Capsule(s)</strong></span>
                                            </td>
                                            <td>
                                                <span class="text-dark small fw-medium"><?php echo htmlspecialchars($rx['dosage']); ?></span>
                                            </td>
                                            <td class="text-center">
                                                <div class="d-flex align-items-center justify-content-center gap-2">
                                                    <a href="prescriptions.php?print=<?php echo urlencode($rx['prescription_id']); ?>" class="btn btn-sm btn-outline-success" style="border-radius:8px;" title="Print official invoice slip">
                                                        <i class="fa-solid fa-print"></i>
                                                    </a>
                                                    <a href="prescriptions.php?edit=<?php echo urlencode($rx['prescription_id']); ?>" class="btn btn-sm btn-outline-primary" style="border-radius:8px;" title="Edit prescription details">
                                                        <i class="fa-solid fa-pen-to-square"></i>
                                                    </a>
                                                    <a href="prescriptions.php?delete=<?php echo urlencode($rx['prescription_id']); ?>" class="btn btn-sm btn-outline-danger btn-delete-confirm" style="border-radius:8px;" title="Delete record">
                                                        <i class="fa-solid fa-trash-can"></i>
                                                    </a>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="5" class="text-center text-muted py-4">No prescription logs recorded. Complete diagnostic files to dispense medications.</td>
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
