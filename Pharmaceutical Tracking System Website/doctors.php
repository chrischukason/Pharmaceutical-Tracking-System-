<?php
/**
 * doctors.php
 * Doctor Management Module
 * Implements clinical staff tracking, specialty department logs,
 * interactive CRUD operations, and live staff table search.
 */

require_once 'header.php';

$alert_message = '';
$alert_type = '';

// Edit Mode Pre-fill variables
$edit_mode = false;
$form_doctor_id = '';
$form_doctor_name = '';
$form_specialization = '';
$form_department = '';
$form_phone = '';

// Generate Suggestive Next Doctor ID
function generateNextDoctorId($db_connected, $pdo) {
    if ($db_connected && $pdo) {
        try {
            $stmt = $pdo->query("SELECT doctor_id FROM doctors ORDER BY doctor_id DESC LIMIT 1");
            $last_id = $stmt->fetchColumn();
            if ($last_id) {
                $num = intval(substr($last_id, 2)) + 1;
                return 'D-' . $num;
            }
        } catch (PDOException $e) {
            // fallback
        }
    } else {
        $last_id = null;
        if (isset($_SESSION['mock_doctors']) && !empty($_SESSION['mock_doctors'])) {
            $keys = array_keys($_SESSION['mock_doctors']);
            sort($keys);
            $last_id = end($keys);
        }
        if ($last_id) {
            $num = intval(substr($last_id, 2)) + 1;
            return 'D-' . $num;
        }
    }
    return 'D-1005'; // Starting fallback
}

$suggested_id = generateNextDoctorId($db_connected, $pdo);

// --------------------------------------------------------------------
// BACKEND PHP CRUD ACTIONS PROCESSOR
// --------------------------------------------------------------------

// 1. DELETE ACTION
if (isset($_GET['delete'])) {
    $delete_id = $_GET['delete'];
    
    if ($db_connected && $pdo) {
        try {
            $stmt = $pdo->prepare("DELETE FROM doctors WHERE doctor_id = :id");
            $stmt->execute(['id' => $delete_id]);
            $alert_message = "Doctor <strong>$delete_id</strong> successfully removed from staff registry.";
            $alert_type = "success";
            $suggested_id = generateNextDoctorId($db_connected, $pdo);
        } catch (PDOException $e) {
            $alert_message = "Database Error: Cannot delete doctor due to existing visit/diagnosis references.";
            $alert_type = "danger";
        }
    } else {
        if (isset($_SESSION['mock_doctors'][$delete_id])) {
            unset($_SESSION['mock_doctors'][$delete_id]);
            // cascade deletion in other mock arrays
            foreach ($_SESSION['mock_visits'] as $k => $v) {
                if ($v['doctor_id'] === $delete_id) unset($_SESSION['mock_visits'][$k]);
            }
            foreach ($_SESSION['mock_diagnoses'] as $k => $v) {
                if ($v['doctor_id'] === $delete_id) unset($_SESSION['mock_diagnoses'][$k]);
            }
            $alert_message = "Doctor <strong>$delete_id</strong> removed from Demo Session.";
            $alert_type = "success";
            $suggested_id = generateNextDoctorId($db_connected, $pdo);
        }
    }
}

// 2. SAVE & UPDATE PROCESSOR
if (isset($_POST['save_doctor'])) {
    $doctor_id = strtoupper(trim($_POST['doctor_id']));
    $doctor_name = trim($_POST['doctor_name']);
    $specialization = trim($_POST['specialization']);
    $department = $_POST['department'];
    $phone_number = trim($_POST['phone_number']);
    $action_mode = $_POST['action_mode'];

    if (empty($doctor_id) || empty($doctor_name) || empty($specialization) || empty($department) || empty($phone_number)) {
        $alert_message = "All doctor parameters must be completely filled out.";
        $alert_type = "danger";
    } else {
        if ($db_connected && $pdo) {
            try {
                if ($action_mode === 'update') {
                    $stmt = $pdo->prepare("
                        UPDATE doctors SET 
                        doctor_name = :name, specialization = :spec, 
                        department = :dept, phone_number = :phone 
                        WHERE doctor_id = :doctor_id
                    ");
                    $stmt->execute([
                        'name' => $doctor_name, 'spec' => $specialization,
                        'dept' => $department, 'phone' => $phone_number,
                        'doctor_id' => $doctor_id
                    ]);
                    $alert_message = "Doctor profile <strong>$doctor_id</strong> updated in registry.";
                    $alert_type = "success";
                } else {
                    // Check duplicate ID
                    $stmt_check = $pdo->prepare("SELECT COUNT(*) FROM doctors WHERE doctor_id = :id");
                    $stmt_check->execute(['id' => $doctor_id]);
                    if ($stmt_check->fetchColumn() > 0) {
                        $alert_message = "Doctor ID <strong>$doctor_id</strong> already exists in database.";
                        $alert_type = "danger";
                    } else {
                        $stmt = $pdo->prepare("
                            INSERT INTO doctors (doctor_id, doctor_name, specialization, department, phone_number) 
                            VALUES (:doctor_id, :name, :spec, :dept, :phone)
                        ");
                        $stmt->execute([
                            'doctor_id' => $doctor_id, 'name' => $doctor_name,
                            'spec' => $specialization, 'dept' => $department,
                            'phone' => $phone_number
                        ]);
                        $alert_message = "New doctor <strong>$doctor_name</strong> successfully registered.";
                        $alert_type = "success";
                        $suggested_id = generateNextDoctorId($db_connected, $pdo);
                    }
                }
            } catch (PDOException $e) {
                $alert_message = "Database Error: " . $e->getMessage();
                $alert_type = "danger";
            }
        } else {
            // Demo Session Mode CRUD
            if ($action_mode === 'update') {
                if (isset($_SESSION['mock_doctors'][$doctor_id])) {
                    $_SESSION['mock_doctors'][$doctor_id] = [
                        'doctor_id' => $doctor_id, 'doctor_name' => $doctor_name,
                        'specialization' => $specialization, 'department' => $department,
                        'phone_number' => $phone_number
                    ];
                    $alert_message = "Doctor <strong>$doctor_id</strong> updated in Demo Session.";
                    $alert_type = "success";
                }
            } else {
                if (isset($_SESSION['mock_doctors'][$doctor_id])) {
                    $alert_message = "Doctor ID <strong>$doctor_id</strong> exists in mock records.";
                    $alert_type = "danger";
                } else {
                    $_SESSION['mock_doctors'][$doctor_id] = [
                        'doctor_id' => $doctor_id, 'doctor_name' => $doctor_name,
                        'specialization' => $specialization, 'department' => $department,
                        'phone_number' => $phone_number
                    ];
                    $alert_message = "New doctor <strong>$doctor_name</strong> registered in Demo Session.";
                    $alert_type = "success";
                    $suggested_id = generateNextDoctorId($db_connected, $pdo);
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
            $stmt = $pdo->prepare("SELECT * FROM doctors WHERE doctor_id = :id");
            $stmt->execute(['id' => $edit_id]);
            $doc = $stmt->fetch();
            if ($doc) {
                $edit_mode = true;
                $form_doctor_id = $doc['doctor_id'];
                $form_doctor_name = $doc['doctor_name'];
                $form_specialization = $doc['specialization'];
                $form_department = $doc['department'];
                $form_phone = $doc['phone_number'];
            }
        } catch (PDOException $e) {
            $alert_message = "Fetch Error: " . $e->getMessage();
            $alert_type = "danger";
        }
    } else {
        if (isset($_SESSION['mock_doctors'][$edit_id])) {
            $doc = $_SESSION['mock_doctors'][$edit_id];
            $edit_mode = true;
            $form_doctor_id = $doc['doctor_id'];
            $form_doctor_name = $doc['doctor_name'];
            $form_specialization = $doc['specialization'];
            $form_department = $doc['department'];
            $form_phone = $doc['phone_number'];
        }
    }
}

// --------------------------------------------------------------------
// FETCH DOCTOR REGISTRY FOR DATA TABLES
// --------------------------------------------------------------------
$doctors_list = [];
if ($db_connected && $pdo) {
    try {
        $stmt_list = $pdo->query("SELECT * FROM doctors ORDER BY doctor_id ASC");
        $doctors_list = $stmt_list->fetchAll();
    } catch (PDOException $e) {
        //
    }
} else {
    $doctors_list = $_SESSION['mock_doctors'] ?? [];
}
?>

<div class="content-body">
    <!-- Top Module Header -->
    <div class="d-flex align-items-center justify-content-between mb-4">
        <div>
            <h3 class="fw-bold text-primary mb-0"><i class="fa-solid fa-user-doctor me-2"></i>Medical Staff Directory</h3>
            <p class="text-muted small mb-0">Connaught Hospital physician specialty profiles management database</p>
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
        <!-- 1. LEFT SIDE - REGISTER/EDIT FORM -->
        <div class="col-lg-5">
            <div class="card-premium">
                <div class="card-premium-header">
                    <h5 class="card-premium-title text-primary">
                        <i class="fa-solid <?php echo $edit_mode ? 'fa-user-pen' : 'fa-user-plus'; ?>"></i>
                        <?php echo $edit_mode ? 'Edit Staff Profile' : 'Register New Doctor'; ?>
                    </h5>
                </div>
                <div class="card-premium-body">
                    <form action="doctors.php" method="POST">
                        <input type="hidden" name="action_mode" value="<?php echo $edit_mode ? 'update' : 'insert'; ?>">
                        
                        <div class="mb-3">
                            <label for="doctor_id" class="form-label-premium">Doctor ID Code</label>
                            <input type="text" class="form-control form-control-premium text-uppercase" id="doctor_id" name="doctor_id" 
                                   value="<?php echo $edit_mode ? htmlspecialchars($form_doctor_id) : htmlspecialchars($suggested_id); ?>" 
                                   <?php echo $edit_mode ? 'readonly' : ''; ?> required>
                            <div class="form-text small text-muted">Unique indexing staff code formatted as D-XXXX.</div>
                        </div>

                        <div class="mb-3">
                            <label for="doctor_name" class="form-label-premium">Doctor Name</label>
                            <input type="text" class="form-control form-control-premium" id="doctor_name" name="doctor_name" 
                                   value="<?php echo htmlspecialchars($form_doctor_name); ?>" placeholder="e.g. Dr. Lansana Sesay" required>
                        </div>

                        <div class="mb-3">
                            <label for="specialization" class="form-label-premium">Specialty Field</label>
                            <input type="text" class="form-control form-control-premium" id="specialization" name="specialization" 
                                   value="<?php echo htmlspecialchars($form_specialization); ?>" placeholder="e.g. Consultant General Surgeon" required>
                        </div>

                        <div class="mb-3">
                            <label for="department" class="form-label-premium">Clinical Department Assignment</label>
                            <select class="form-select form-select-premium" id="department" name="department" required>
                                <option value="" disabled <?php echo empty($form_department) ? 'selected' : ''; ?>>Assign Department</option>
                                <option value="Outpatient Department (OPD)" <?php echo ($form_department === 'Outpatient Department (OPD)') ? 'selected' : ''; ?>>Outpatient Department (OPD)</option>
                                <option value="Pediatrics Ward" <?php echo ($form_department === 'Pediatrics Ward') ? 'selected' : ''; ?>>Pediatrics Ward</option>
                                <option value="Surgical Ward" <?php echo ($form_department === 'Surgical Ward') ? 'selected' : ''; ?>>Surgical Ward</option>
                                <option value="Maternity Department" <?php echo ($form_department === 'Maternity Department') ? 'selected' : ''; ?>>Maternity Department</option>
                                <option value="Cardiology Unit" <?php echo ($form_department === 'Cardiology Unit') ? 'selected' : ''; ?>>Cardiology Unit</option>
                                <option value="Emergency Response" <?php echo ($form_department === 'Emergency Response') ? 'selected' : ''; ?>>Emergency Response</option>
                            </select>
                        </div>

                        <div class="mb-4">
                            <label for="phone_number" class="form-label-premium">Contact Phone Number</label>
                            <input type="text" class="form-control form-control-premium" id="phone_number" name="phone_number" 
                                   value="<?php echo htmlspecialchars($form_phone); ?>" placeholder="e.g. +23276884433" required>
                        </div>

                        <div class="d-flex flex-wrap gap-2 pt-2 border-top">
                            <?php if ($edit_mode): ?>
                                <button type="submit" name="save_doctor" class="btn btn-premium btn-premium-primary flex-grow-1 justify-content-center">
                                    <i class="fa-solid fa-floppy-disk me-1"></i> Update Profile
                                </button>
                                <a href="doctors.php" class="btn btn-premium btn-premium-secondary">
                                    Cancel
                                </a>
                            <?php else: ?>
                                <button type="submit" name="save_doctor" class="btn btn-premium btn-premium-primary flex-grow-1 justify-content-center">
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
                        <i class="fa-solid fa-user-shield"></i> Registered Medical Practitioners
                    </h5>
                    <div class="input-group search-input-group style-width-override" style="max-width: 250px;">
                        <span class="input-group-text bg-light text-muted border-end-0" style="border-radius: 10px 0 0 10px; border-color: #cbd5e1;">
                            <i class="fa-solid fa-magnifying-glass"></i>
                        </span>
                        <input type="text" class="form-control form-control-premium border-start-0" id="tableSearch" placeholder="Search staff..." style="border-radius: 0 10px 10px 0;">
                    </div>
                </div>
                
                <div class="card-premium-body p-0">
                    <div class="table-responsive">
                        <table class="table table-premium align-middle" id="searchableTable">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Practitioner Info</th>
                                    <th>Department Assignment</th>
                                    <th>Contact Phone</th>
                                    <th class="text-center no-print">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($doctors_list)): ?>
                                    <?php foreach ($doctors_list as $doc): ?>
                                        <tr>
                                            <td class="fw-bold text-primary"><?php echo htmlspecialchars($doc['doctor_id']); ?></td>
                                            <td>
                                                <span class="fw-bold text-dark d-block"><?php echo htmlspecialchars($doc['doctor_name']); ?></span>
                                                <span class="text-muted small" style="font-size: 11px;"><?php echo htmlspecialchars($doc['specialization']); ?></span>
                                            </td>
                                            <td>
                                                <span class="badge-dept">
                                                    <?php echo htmlspecialchars($doc['department']); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <span class="font-monospace text-dark" style="font-size:12.5px;"><?php echo htmlspecialchars($doc['phone_number']); ?></span>
                                            </td>
                                            <td class="text-center no-print">
                                                <div class="d-flex align-items-center justify-content-center gap-2">
                                                    <a href="doctors.php?edit=<?php echo urlencode($doc['doctor_id']); ?>" class="btn btn-sm btn-outline-primary" style="border-radius:8px;" title="Edit profile">
                                                        <i class="fa-solid fa-pen-to-square"></i>
                                                    </a>
                                                    <a href="doctors.php?delete=<?php echo urlencode($doc['doctor_id']); ?>" class="btn btn-sm btn-outline-danger btn-delete-confirm" style="border-radius:8px;" title="Remove doctor">
                                                        <i class="fa-solid fa-trash-can"></i>
                                                    </a>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="5" class="text-center text-muted py-4">No doctor records registered. Please register clinical staff to begin.</td>
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
