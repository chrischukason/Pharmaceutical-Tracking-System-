<?php
/**
 * db.php
 * Database Connection Module
 * Uses PDO for secure and flexible relational database queries.
 * Gracefully falls back to Demo Mode if MySQL is offline.
 */

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Database Configuration Defaults
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'hospital_db');

$db_connected = false;
$pdo = null;
$db_error_message = '';

try {
    // Attempt standard connection to the MySQL database
    $dsn = "mysql:host=" . DB_HOST . ";charset=utf8mb4";
    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ];
    
    // First connect to MySQL without specifying db to check host
    $temp_pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
    
    // Check if the hospital database exists
    $query = $temp_pdo->query("SELECT SCHEMA_NAME FROM INFORMATION_SCHEMA.SCHEMATA WHERE SCHEMA_NAME = '" . DB_NAME . "'");
    $db_exists = $query->fetch();
    
    if ($db_exists) {
        // Reconnect with database specified
        $pdo = new PDO($dsn . ";dbname=" . DB_NAME, DB_USER, DB_PASS, $options);
        $db_connected = true;
    } else {
        // Database exists check failed (database needs creation via setup.php)
        $db_error_message = "Database '" . DB_NAME . "' was not found. Please run the setup.php installer.";
        $pdo = $temp_pdo; // Keep basic connection for setup
    }
} catch (PDOException $e) {
    $db_connected = false;
    $db_error_message = "MySQL Offline: " . $e->getMessage();
}

// Handle Mock Mode Session Database Initialization
// If database is offline, we populate $_SESSION with rich data as a fail-safe mock DB.
if (!$db_connected) {
    if (!isset($_SESSION['mock_db_initialized'])) {
        $_SESSION['mock_db_initialized'] = true;
        
        // Mock Users
        $_SESSION['mock_users'] = [
            [
                'username' => 'admin',
                'password' => 'admin123', // support simple login for mock testing
                'full_name' => 'Dr. Alusine Kamara',
                'role' => 'Chief Hospital Administrator (Demo Mode)'
            ]
        ];

        // Mock Doctors
        $_SESSION['mock_doctors'] = [
            'D-1001' => ['doctor_id' => 'D-1001', 'doctor_name' => 'Dr. Lansana Sesay', 'specialization' => 'Internal Medicine & Cardiology', 'department' => 'Outpatient Department (OPD)', 'phone_number' => '+23276884433'],
            'D-1002' => ['doctor_id' => 'D-1002', 'doctor_name' => 'Dr. Fatmata Kamara', 'specialization' => 'Pediatrics Specialist', 'department' => 'Pediatrics Ward', 'phone_number' => '+23277112233'],
            'D-1003' => ['doctor_id' => 'D-1003', 'doctor_name' => 'Dr. Mohamed Bah', 'specialization' => 'Consultant General Surgeon', 'department' => 'Surgical Ward', 'phone_number' => '+23230449988'],
            'D-1004' => ['doctor_id' => 'D-1004', 'doctor_name' => 'Dr. Zainab Turay', 'specialization' => 'Obstetrics & Gynecology', 'department' => 'Maternity Department', 'phone_number' => '+23288776655']
        ];

        // Mock Patients
        $_SESSION['mock_patients'] = [
            'P-1001' => ['patient_id' => 'P-1001', 'full_name' => 'Amadu Kamara', 'age' => 45, 'gender' => 'Male', 'address' => '12 Kissy Road, Freetown', 'phone_number' => '+23276123456', 'date_birth' => '1981-04-12', 'medical_complaint' => 'Persistent headache, chest tightness, and high blood pressure symptoms.'],
            'P-1002' => ['patient_id' => 'P-1002', 'full_name' => 'Mariama Sesay', 'age' => 28, 'gender' => 'Female', 'address' => '45 Wilkinson Road, Freetown', 'phone_number' => '+23277987654', 'date_birth' => '1998-09-22', 'medical_complaint' => 'Severe abdominal pain in lower quadrant, fever and nausea.'],
            'P-1003' => ['patient_id' => 'P-1003', 'full_name' => 'Alimamy Condeh', 'age' => 8, 'gender' => 'Male', 'address' => '88 Savage Street, Freetown', 'phone_number' => '+23230445566', 'date_birth' => '2018-05-14', 'medical_complaint' => 'Heavy chest congestion, wet cough, breathing difficulties.'],
            'P-1004' => ['patient_id' => 'P-1004', 'full_name' => 'Isatu Mansaray', 'age' => 34, 'gender' => 'Female', 'address' => '15 Siaka Stevens Street, Freetown', 'phone_number' => '+23288223344', 'date_birth' => '1992-11-05', 'medical_complaint' => 'Routine pregnancy review, Experiencing back discomfort.']
        ];

        // Mock Visits
        $_SESSION['mock_visits'] = [
            1 => ['id' => 1, 'patient_id' => 'P-1001', 'doctor_id' => 'D-1001', 'visit_date' => '2026-05-20', 'vitals_bp' => '150/95', 'vitals_temp' => 36.8, 'vitals_weight' => 85, 'visit_reason' => 'Hypertension review and chronic headache complaints.'],
            2 => ['id' => 2, 'patient_id' => 'P-1002', 'doctor_id' => 'D-1003', 'visit_date' => '2026-05-22', 'vitals_bp' => '115/75', 'vitals_temp' => 38.4, 'vitals_weight' => 62, 'visit_reason' => 'Acute abdomen consultation; suspect appendicitis.'],
            3 => ['id' => 3, 'patient_id' => 'P-1003', 'doctor_id' => 'D-1002', 'visit_date' => '2026-05-24', 'vitals_bp' => '100/60', 'vitals_temp' => 39.1, 'vitals_weight' => 24, 'visit_reason' => 'High fever, child cough, and shallow breathing.'],
            4 => ['id' => 4, 'patient_id' => 'P-1004', 'doctor_id' => 'D-1004', 'visit_date' => '2026-05-26', 'vitals_bp' => '122/82', 'vitals_temp' => 36.5, 'vitals_weight' => 78, 'visit_reason' => 'Antenatal care progress tracking and routine vitals.']
        ];

        // Mock Diagnoses
        $_SESSION['mock_diagnoses'] = [
            1 => ['id' => 1, 'patient_id' => 'P-1001', 'doctor_id' => 'D-1001', 'diagnosis_details' => 'Essential Hypertension (Stage 2) with acute cephalalgia.', 'treatment_notes' => 'Advised low sodium diet, absolute physical rest, and started daily oral antihypertensive therapy.', 'diagnosis_date' => '2026-05-20'],
            2 => ['id' => 2, 'patient_id' => 'P-1002', 'doctor_id' => 'D-1003', 'diagnosis_details' => 'Acute Appendicitis - early stages.', 'treatment_notes' => 'Patient admitted for emergency appendectomy surgery within 24 hours.', 'diagnosis_date' => '2026-05-22'],
            3 => ['id' => 3, 'patient_id' => 'P-1003', 'doctor_id' => 'D-1002', 'diagnosis_details' => 'Severe Bronchopneumonia and Malaria (tested positive by RDT).', 'treatment_notes' => 'Prescribed intravenous antimalarials, oral antibiotics, and paracetamol syrup.', 'diagnosis_date' => '2026-05-24'],
            4 => ['id' => 4, 'patient_id' => 'P-1004', 'doctor_id' => 'D-1004', 'diagnosis_details' => 'Normal Singleton Pregnancy (34 Weeks Gestation).', 'treatment_notes' => 'Fetal heartbeat normal at 142 bpm. Prescribed prenatal vitamins.', 'diagnosis_date' => '2026-05-26']
        ];

        // Mock Prescriptions
        $_SESSION['mock_prescriptions'] = [
            'RX-1001' => ['prescription_id' => 'RX-1001', 'diagnosis_id' => 1, 'drug_name' => 'Amlodipine 5mg Tablets', 'quantity' => 30, 'dosage' => '1 tablet daily in the morning', 'prescription_date' => '2026-05-20'],
            'RX-1002' => ['prescription_id' => 'RX-1002', 'diagnosis_id' => 1, 'drug_name' => 'Paracetamol 500mg Tablets', 'quantity' => 20, 'dosage' => '2 tablets every 8 hours when in pain', 'prescription_date' => '2026-05-20'],
            'RX-1003' => ['prescription_id' => 'RX-1003', 'diagnosis_id' => 3, 'drug_name' => 'Artesunate-Amodiaquine (Coarsucam)', 'quantity' => 1, 'dosage' => '1 tablet daily for 3 days', 'prescription_date' => '2026-05-24'],
            'RX-1004' => ['prescription_id' => 'RX-1004', 'diagnosis_id' => 3, 'drug_name' => 'Amoxicillin Syrup 125mg/5ml', 'quantity' => 2, 'dosage' => '5ml three times daily for 7 days', 'prescription_date' => '2026-05-24'],
            'RX-1005' => ['prescription_id' => 'RX-1005', 'diagnosis_id' => 4, 'drug_name' => 'Ferrous Sulfate + Folic Acid Tablets', 'quantity' => 60, 'dosage' => '1 tablet daily after meals', 'prescription_date' => '2026-05-26']
        ];
    }
}

// Safety fallback: Ensure mock_users is always available, even if initialization was skipped
if (!isset($_SESSION['mock_users'])) {
    $_SESSION['mock_users'] = [
        [
            'username' => 'admin',
            'password' => 'admin123',
            'full_name' => 'Dr. Alusine Kamara',
            'role' => 'Chief Hospital Administrator (Demo Mode)'
        ]
    ];
}
?>
