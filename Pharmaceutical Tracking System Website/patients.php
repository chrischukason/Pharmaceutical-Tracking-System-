<?php
/**
 * patients.php
 * Patient Registration & Management Module
 * Implements clinical demographic logs, automatic Patient ID generation,
 * interactive CRUD operations, and searchable patient data grid.
 */

require_once 'header.php';

$alert_message = '';
$alert_type = '';

// Edit Mode Pre-fill variables
$edit_mode = false;
$form_patient_id = '';
$form_full_name = '';
$form_age = '';
$form_gender = '';
$form_address = '';
$form_phone = '';
$form_dob = '';
$form_complaint = '';

// Generate Suggestive Next Patient ID
function generateNextPatientId($db_connected, $pdo) {
    if ($db_connected && $pdo) {
        try {
            $stmt = $pdo->query("SELECT patient_id FROM patients ORDER BY patient_id DESC LIMIT 1");
            $last_id = $stmt->fetchColumn();
            if ($last_id) {
                $num = intval(substr($last_id, 2)) + 1;
                return 'P-' . $num;
            }
        } catch (PDOException $e) {
            // fallback
        }
    } else {
        $last_id = null;
        if (isset($_SESSION['mock_patients']) && !empty($_SESSION['mock_patients'])) {
            $keys = array_keys($_SESSION['mock_patients']);
            sort($keys);
            $last_id = end($keys);
        }
        if ($last_id) {
            $num = intval(substr($last_id, 2)) + 1;
            return 'P-' . $num;
        }
    }
    return 'P-1005'; // Starting fallback
}

$suggested_id = generateNextPatientId($db_connected, $pdo);

// --------------------------------------------------------------------
// BACKEND PHP CRUD ACTIONS PROCESSOR
// --------------------------------------------------------------------

// 1. DELETE ACTION
if (isset($_GET['delete'])) {
    $delete_id = $_GET['delete'];
    
    if ($db_connected && $pdo) {
        try {
            $stmt = $pdo->prepare("DELETE FROM patients WHERE patient_id = :id");
            $stmt->execute(['id' => $delete_id]);
            $alert_message = "Patient <strong>$delete_id</strong> successfully removed from database.";
            $alert_type = "success";
            $suggested_id = generateNextPatientId($db_connected, $pdo);
        } catch (PDOException $e) {
            $alert_message = "Database Error: " . $e->getMessage();
            $alert_type = "danger";
        }
    } else {
        if (isset($_SESSION['mock_patients'][$delete_id])) {
            unset($_SESSION['mock_patients'][$delete_id]);
            // cascade deletion in other mock arrays
            foreach ($_SESSION['mock_visits'] as $k => $v) {
                if ($v['patient_id'] === $delete_id) unset($_SESSION['mock_visits'][$k]);
            }
            foreach ($_SESSION['mock_diagnoses'] as $k => $v) {
                if ($v['patient_id'] === $delete_id) unset($_SESSION['mock_diagnoses'][$k]);
            }
            $alert_message = "Patient <strong>$delete_id</strong> successfully removed from Demo session.";
            $alert_type = "success";
            $suggested_id = generateNextPatientId($db_connected, $pdo);
        }
    }
}

// 2. SAVE & UPDATE PROCESSOR
if (isset($_POST['save_patient'])) {
    $patient_id = strtoupper(trim($_POST['patient_id']));
    $full_name = trim($_POST['full_name']);
    $age = intval($_POST['age']);
    $gender = $_POST['gender'];
    $address = trim($_POST['address']);
    $phone_number = trim($_POST['phone_number']);
    $date_birth = $_POST['date_birth'];
    $medical_complaint = trim($_POST['medical_complaint']);
    $action_mode = $_POST['action_mode']; // 'insert' or 'update'

    if (empty($patient_id) || empty($full_name) || empty($phone_number) || empty($date_birth)) {
        $alert_message = "Required demographic fields must not be left empty.";
        $alert_type = "danger";
    } else {
        if ($db_connected && $pdo) {
            try {
                if ($action_mode === 'update') {
                    // Execute UPDATE
                    $stmt = $pdo->prepare("
                        UPDATE patients SET 
                        full_name = :full_name, age = :age, gender = :gender, 
                        address = :address, phone_number = :phone_number, 
                        date_birth = :date_birth, medical_complaint = :medical_complaint 
                        WHERE patient_id = :patient_id
                    ");
                    $stmt->execute([
                        'full_name' => $full_name, 'age' => $age, 'gender' => $gender,
                        'address' => $address, 'phone_number' => $phone_number,
                        'date_birth' => $date_birth, 'medical_complaint' => $medical_complaint,
                        'patient_id' => $patient_id
                    ]);
                    $alert_message = "Patient record <strong>$patient_id</strong> successfully updated.";
                    $alert_type = "success";
                } else {
                    // Check duplicate ID
                    $stmt_check = $pdo->prepare("SELECT COUNT(*) FROM patients WHERE patient_id = :id");
                    $stmt_check->execute(['id' => $patient_id]);
                    if ($stmt_check->fetchColumn() > 0) {
                        $alert_message = "Record ID <strong>$patient_id</strong> already exists. Please choose a different code.";
                        $alert_type = "danger";
                    } else {
                        // Execute INSERT
                        $stmt = $pdo->prepare("
                            INSERT INTO patients (patient_id, full_name, age, gender, address, phone_number, date_birth, medical_complaint) 
                            VALUES (:patient_id, :full_name, :age, :gender, :address, :phone_number, :date_birth, :medical_complaint)
                        ");
                        $stmt->execute([
                            'patient_id' => $patient_id, 'full_name' => $full_name, 'age' => $age,
                            'gender' => $gender, 'address' => $address, 'phone_number' => $phone_number,
                            'date_birth' => $date_birth, 'medical_complaint' => $medical_complaint
                        ]);
                        $alert_message = "New patient <strong>$full_name</strong> ($patient_id) registered successfully.";
                        $alert_type = "success";
                        $suggested_id = generateNextPatientId($db_connected, $pdo);
                    }
                }
            } catch (PDOException $e) {
                $alert_message = "Database Error: " . $e->getMessage();
                $alert_type = "danger";
            }
        } else {
            // Handle Save/Update in Session Mock Mode
            if ($action_mode === 'update') {
                if (isset($_SESSION['mock_patients'][$patient_id])) {
                    $_SESSION['mock_patients'][$patient_id] = [
                        'patient_id' => $patient_id, 'full_name' => $full_name, 'age' => $age,
                        'gender' => $gender, 'address' => $address, 'phone_number' => $phone_number,
                        'date_birth' => $date_birth, 'medical_complaint' => $medical_complaint
                    ];
                    $alert_message = "Patient <strong>$patient_id</strong> updated in Demo Session.";
                    $alert_type = "success";
                }
            } else {
                if (isset($_SESSION['mock_patients'][$patient_id])) {
                    $alert_message = "Patient ID <strong>$patient_id</strong> exists in mock records. Use different ID.";
                    $alert_type = "danger";
                } else {
                    $_SESSION['mock_patients'][$patient_id] = [
                        'patient_id' => $patient_id, 'full_name' => $full_name, 'age' => $age,
                        'gender' => $gender, 'address' => $address, 'phone_number' => $phone_number,
                        'date_birth' => $date_birth, 'medical_complaint' => $medical_complaint
                    ];
                    $alert_message = "New patient <strong>$full_name</strong> ($patient_id) registered in Demo Session.";
                    $alert_type = "success";
                    $suggested_id = generateNextPatientId($db_connected, $pdo);
                }
            }
        }
    }
}

// 3. EDIT MODE TRIGGER (FETCH DATA TO PRE-FILL FORM)
if (isset($_GET['edit'])) {
    $edit_id = $_GET['edit'];
    
    if ($db_connected && $pdo) {
        try {
            $stmt = $pdo->prepare("SELECT * FROM patients WHERE patient_id = :id");
            $stmt->execute(['id' => $edit_id]);
            $patient = $stmt->fetch();
            if ($patient) {
                $edit_mode = true;
                $form_patient_id = $patient['patient_id'];
                $form_full_name = $patient['full_name'];
                $form_age = $patient['age'];
                $form_gender = $patient['gender'];
                $form_address = $patient['address'];
                $form_phone = $patient['phone_number'];
                $form_dob = $patient['date_birth'];
                $form_complaint = $patient['medical_complaint'];
            }
        } catch (PDOException $e) {
            $alert_message = "Fetch Error: " . $e->getMessage();
            $alert_type = "danger";
        }
    } else {
        if (isset($_SESSION['mock_patients'][$edit_id])) {
            $patient = $_SESSION['mock_patients'][$edit_id];
            $edit_mode = true;
            $form_patient_id = $patient['patient_id'];
            $form_full_name = $patient['full_name'];
            $form_age = $patient['age'];
            $form_gender = $patient['gender'];
            $form_address = $patient['address'];
            $form_phone = $patient['phone_number'];
            $form_dob = $patient['date_birth'];
            $form_complaint = $patient['medical_complaint'];
        }
    }
}

// --------------------------------------------------------------------
// FETCH ALL ACTIVE RECORDS FOR GRID DISPLAY
// --------------------------------------------------------------------
$patients_list = [];
if ($db_connected && $pdo) {
    try {
        $stmt_list = $pdo->query("SELECT * FROM patients ORDER BY patient_id ASC");
        $patients_list = $stmt_list->fetchAll();
    } catch (PDOException $e) {
        //
    }
} else {
    $patients_list = $_SESSION['mock_patients'] ?? [];
}
?>

<div class="content-body">
    <!-- Top Module Header -->
    <div class="d-flex align-items-center justify-content-between mb-4">
        <div>
            <h3 class="fw-bold text-primary mb-0"><i class="fa-solid fa-hospital-user me-2"></i>Patient Registry</h3>
            <p class="text-muted small mb-0">Connaught Hospital central medical database registry system</p>
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
        <!-- 1. LEFT SIDE - REGISTRATION FORM CARD -->
        <div class="col-lg-5">
            <div class="card-premium">
                <div class="card-premium-header">
                    <h5 class="card-premium-title text-primary">
                        <i class="fa-solid <?php echo $edit_mode ? 'fa-user-pen' : 'fa-user-plus'; ?>"></i>
                        <?php echo $edit_mode ? 'Edit Patient File' : 'Register New Patient'; ?>
                    </h5>
                </div>
                <div class="card-premium-body">
                    <form action="patients.php" method="POST">
                        <input type="hidden" name="action_mode" value="<?php echo $edit_mode ? 'update' : 'insert'; ?>">
                        
                        <div class="mb-3">
                            <label for="patient_id" class="form-label-premium">Patient ID Code</label>
                            <input type="text" class="form-control form-control-premium text-uppercase" id="patient_id" name="patient_id" 
                                   value="<?php echo $edit_mode ? htmlspecialchars($form_patient_id) : htmlspecialchars($suggested_id); ?>" 
                                   <?php echo $edit_mode ? 'readonly' : ''; ?> required>
                            <div class="form-text small text-muted">Unique indexing code formatted as P-XXXX.</div>
                        </div>

                        <div class="mb-3">
                            <label for="full_name" class="form-label-premium">Patient Full Name</label>
                            <input type="text" class="form-control form-control-premium" id="full_name" name="full_name" 
                                   value="<?php echo htmlspecialchars($form_full_name); ?>" placeholder="Enter given names & surname" required>
                        </div>

                        <div class="row g-3 mb-3">
                            <div class="col-sm-6">
                                <label for="date_birth" class="form-label-premium">Date of Birth</label>
                                <input type="date" class="form-control form-control-premium" id="date_birth" name="date_birth" 
                                       value="<?php echo htmlspecialchars($form_dob); ?>" required>
                            </div>
                            
                            <div class="col-sm-6">
                                <label for="age" class="form-label-premium">Calculated Age</label>
                                <input type="number" class="form-control form-control-premium" id="age" name="age" 
                                       value="<?php echo htmlspecialchars($form_age); ?>" placeholder="Auto calculated" required>
                            </div>
                        </div>

                        <div class="row g-3 mb-3">
                            <div class="col-sm-6">
                                <label for="gender" class="form-label-premium">Gender</label>
                                <select class="form-select form-select-premium" id="gender" name="gender" required>
                                    <option value="" disabled <?php echo empty($form_gender) ? 'selected' : ''; ?>>Select Gender</option>
                                    <option value="Male" <?php echo ($form_gender === 'Male') ? 'selected' : ''; ?>>Male</option>
                                    <option value="Female" <?php echo ($form_gender === 'Female') ? 'selected' : ''; ?>>Female</option>
                                </select>
                            </div>
                            
                            <div class="col-sm-6">
                                <label for="phone_number" class="form-label-premium">Phone Number</label>
                                <input type="text" class="form-control form-control-premium" id="phone_number" name="phone_number" 
                                       value="<?php echo htmlspecialchars($form_phone); ?>" placeholder="e.g. +23276123456" required>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="address" class="form-label-premium">Residential Address</label>
                            <input type="text" class="form-control form-control-premium" id="address" name="address" 
                                   value="<?php echo htmlspecialchars($form_address); ?>" placeholder="e.g. 12 Kissy Road, Freetown" required>
                        </div>

                        <div class="mb-4">
                            <label for="medical_complaint" class="form-label-premium">Clinical Complaint Description</label>
                            <textarea class="form-control form-control-premium" id="medical_complaint" name="medical_complaint" rows="3" 
                                      placeholder="State primary symptoms, vitals summary or physical check notes details..." required><?php echo htmlspecialchars($form_complaint); ?></textarea>
                        </div>

                        <div class="d-flex flex-wrap gap-2 pt-2 border-top">
                            <?php if ($edit_mode): ?>
                                <button type="submit" name="save_patient" class="btn btn-premium btn-premium-primary flex-grow-1 justify-content-center">
                                    <i class="fa-solid fa-floppy-disk me-1"></i> Update File
                                </button>
                                <a href="patients.php" class="btn btn-premium btn-premium-secondary">
                                    Cancel
                                </a>
                            <?php else: ?>
                                <button type="submit" name="save_patient" class="btn btn-premium btn-premium-primary flex-grow-1 justify-content-center">
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
                        <i class="fa-solid fa-address-book"></i> Active Patient Registry
                    </h5>
                    <div class="input-group search-input-group style-width-override" style="max-width: 250px;">
                        <span class="input-group-text bg-light text-muted border-end-0" style="border-radius: 10px 0 0 10px; border-color: #cbd5e1;">
                            <i class="fa-solid fa-magnifying-glass"></i>
                        </span>
                        <input type="text" class="form-control form-control-premium border-start-0" id="tableSearch" placeholder="Search patients..." style="border-radius: 0 10px 10px 0;">
                    </div>
                </div>
                
                <div class="card-premium-body p-0">
                    <div class="table-responsive">
                        <table class="table table-premium align-middle" id="searchableTable">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Patient Name</th>
                                    <th>Age/Sex</th>
                                    <th>Phone / Address</th>
                                    <th class="text-center no-print">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($patients_list)): ?>
                                    <?php foreach ($patients_list as $pat): ?>
                                        <tr>
                                            <td class="fw-bold text-primary"><?php echo htmlspecialchars($pat['patient_id']); ?></td>
                                            <td>
                                                <span class="fw-bold text-dark d-block"><?php echo htmlspecialchars($pat['full_name']); ?></span>
                                                <span class="text-muted small" style="font-size: 11px;">DOB: <?php echo date('M d, Y', strtotime($pat['date_birth'])); ?></span>
                                            </td>
                                            <td>
                                                <span class="d-block text-dark fw-medium"><?php echo htmlspecialchars($pat['age']); ?> Years</span>
                                                <span class="badge-gender <?php echo ($pat['gender'] == 'Male') ? 'badge-male' : 'badge-female'; ?> mt-1 d-inline-block">
                                                    <?php echo htmlspecialchars($pat['gender']); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <span class="d-block text-dark font-monospace" style="font-size:12px;"><?php echo htmlspecialchars($pat['phone_number']); ?></span>
                                                <span class="text-muted d-block text-truncate" style="max-width: 160px;" title="<?php echo htmlspecialchars($pat['address']); ?>"><?php echo htmlspecialchars($pat['address']); ?></span>
                                            </td>
                                            <td class="text-center no-print">
                                                <div class="d-flex align-items-center justify-content-center gap-2">
                                                    <a href="patients.php?edit=<?php echo urlencode($pat['patient_id']); ?>" class="btn btn-sm btn-outline-primary" style="border-radius:8px;" title="Edit details">
                                                        <i class="fa-solid fa-pen-to-square"></i>
                                                    </a>
                                                    <a href="patients.php?delete=<?php echo urlencode($pat['patient_id']); ?>" class="btn btn-sm btn-outline-danger btn-delete-confirm" style="border-radius:8px;" title="Delete record">
                                                        <i class="fa-solid fa-trash-can"></i>
                                                    </a>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="5" class="text-center text-muted py-4">No patient records registered. Create one to begin.</td>
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
