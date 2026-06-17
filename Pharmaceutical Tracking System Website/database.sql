-- ====================================================================
-- CODES & TABLES FOR CONNAUGHT GOVERNMENT HOSPITAL DATABASE
-- Patient Record Management System (PRMS) Schema
-- Suitable for a University Database Project Presentation
-- ====================================================================

CREATE DATABASE IF NOT EXISTS hospital_db;
USE hospital_db;

-- 1. Users Table (Administrator Authentication)
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    role VARCHAR(50) DEFAULT 'Administrator',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 2. Patients Table (Demographics and Baseline Complaints)
CREATE TABLE IF NOT EXISTS patients (
    patient_id VARCHAR(10) PRIMARY KEY, -- Format: P-1001, etc.
    full_name VARCHAR(100) NOT NULL,
    age INT NOT NULL,
    gender VARCHAR(10) NOT NULL,
    address VARCHAR(255) NOT NULL,
    phone_number VARCHAR(20) NOT NULL,
    date_birth DATE NOT NULL,
    medical_complaint TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 3. Doctors Table (Medical Staff and Specialty Departments)
CREATE TABLE IF NOT EXISTS doctors (
    doctor_id VARCHAR(10) PRIMARY KEY, -- Format: D-1001, etc.
    doctor_name VARCHAR(100) NOT NULL,
    specialization VARCHAR(100) NOT NULL,
    department VARCHAR(100) NOT NULL,
    phone_number VARCHAR(20) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 4. Clinical Visits Table (Outpatient Logs and Vitals Check)
CREATE TABLE IF NOT EXISTS visits (
    id INT AUTO_INCREMENT PRIMARY KEY,
    patient_id VARCHAR(10) NOT NULL,
    doctor_id VARCHAR(10) NOT NULL,
    visit_date DATE NOT NULL,
    vitals_bp VARCHAR(20) NOT NULL,    -- Blood pressure, e.g. "120/80"
    vitals_temp DECIMAL(4,1) NOT NULL,  -- Temperature in Celsius, e.g. 37.2
    vitals_weight INT NOT NULL,         -- Weight in kilograms, e.g. 70
    visit_reason TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (patient_id) REFERENCES patients(patient_id) ON DELETE CASCADE,
    FOREIGN KEY (doctor_id) REFERENCES doctors(doctor_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 5. Diagnoses Table (Medical Evaluations)
CREATE TABLE IF NOT EXISTS diagnoses (
    id INT AUTO_INCREMENT PRIMARY KEY,
    patient_id VARCHAR(10) NOT NULL,
    doctor_id VARCHAR(10) NOT NULL,
    diagnosis_details TEXT NOT NULL,
    treatment_notes TEXT NOT NULL,
    diagnosis_date DATE NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (patient_id) REFERENCES patients(patient_id) ON DELETE CASCADE,
    FOREIGN KEY (doctor_id) REFERENCES doctors(doctor_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 6. Prescriptions Table (Drug Dispensing Records)
CREATE TABLE IF NOT EXISTS prescriptions (
    prescription_id VARCHAR(15) PRIMARY KEY, -- Format: RX-1001, etc.
    diagnosis_id INT NOT NULL,
    drug_name VARCHAR(100) NOT NULL,
    quantity INT NOT NULL,
    dosage VARCHAR(100) NOT NULL,          -- e.g. "1 tab x3 daily after meals"
    prescription_date DATE NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (diagnosis_id) REFERENCES diagnoses(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ====================================================================
-- SEED DATA & MOCK INFORMATION (SIERRA LEONE HEALTHCARE THEMED)
-- ====================================================================

-- Default Admin Profile (password: admin123, stored securely via bcrypt hash)
INSERT INTO users (username, password, full_name, role) VALUES 
('admin', '$2y$10$tZ9sD39H3kO2yR.lDpxJ2eH6JdEexDfeHq7H4M4R8Bly9.Hn5P.k2', 'Dr. Alusine Kamara', 'Chief Hospital Administrator')
ON DUPLICATE KEY UPDATE id=id;

-- Seed Doctor Profiles
INSERT INTO doctors (doctor_id, doctor_name, specialization, department, phone_number) VALUES
('D-1001', 'Dr. Lansana Sesay', 'Internal Medicine & Cardiology', 'Outpatient Department (OPD)', '+23276884433'),
('D-1002', 'Dr. Fatmata Kamara', 'Pediatrics Specialist', 'Pediatrics Ward', '+23277112233'),
('D-1003', 'Dr. Mohamed Bah', 'Consultant General Surgeon', 'Surgical Ward', '+23230449988'),
('D-1004', 'Dr. Zainab Turay', 'Obstetrics & Gynecology', 'Maternity Department', '+23288776655')
ON DUPLICATE KEY UPDATE doctor_id=doctor_id;

-- Seed Patient Profiles
INSERT INTO patients (patient_id, full_name, age, gender, address, phone_number, date_birth, medical_complaint) VALUES
('P-1001', 'Amadu Kamara', 45, 'Male', '12 Kissy Road, Freetown', '+23276123456', '1981-04-12', 'Persistent headache, chest tightness, and high blood pressure symptoms for three days.'),
('P-1002', 'Mariama Sesay', 28, 'Female', '45 Wilkinson Road, Freetown', '+23277987654', '1998-09-22', 'Severe abdominal pain in the lower right quadrant, accompanied by intermittent fever and nausea.'),
('P-1003', 'Alimamy Condeh', 8, 'Male', '88 Savage Street, Freetown', '+23230445566', '2018-05-14', 'Heavy chest congestion, wet cough, breathing difficulties, and temperature spikes.'),
('P-1004', 'Isatu Mansaray', 34, 'Female', '15 Siaka Stevens Street, Freetown', '+23288223344', '1992-11-05', 'Routine third-trimester maternity review, experiencing mild leg swelling and lower back discomfort.')
ON DUPLICATE KEY UPDATE patient_id=patient_id;

-- Seed Patient Clinical Visits
INSERT INTO visits (patient_id, doctor_id, visit_date, vitals_bp, vitals_temp, vitals_weight, visit_reason) VALUES
('P-1001', 'D-1001', '2026-05-20', '150/95', 36.8, 85, 'Hypertension review and chronic headache complaints.'),
('P-1002', 'D-1003', '2026-05-22', '115/75', 38.4, 62, 'Acute abdomen consultation; suspect appendicitis.'),
('P-1003', 'D-1002', '2026-05-24', '100/60', 39.1, 24, 'High fever, child cough, and shallow breathing.'),
('P-1004', 'D-1004', '2026-05-26', '122/82', 36.5, 78, 'Antenatal care progress tracking and routine vitals assessment.');

-- Seed Diagnoses
INSERT INTO diagnoses (id, patient_id, doctor_id, diagnosis_details, treatment_notes, diagnosis_date) VALUES
(1, 'P-1001', 'D-1001', 'Essential Hypertension (Stage 2) with acute cephalalgia.', 'Advised low sodium diet, absolute physical rest, and started daily oral antihypertensive therapy.', '2026-05-20'),
(2, 'P-1002', 'D-1003', 'Acute Appendicitis - early stages.', 'Patient admitted for emergency appendectomy surgery within 24 hours. Prepared surgical clearance.', '2026-05-22'),
(3, 'P-1003', 'D-1002', 'Severe Bronchopneumonia and Malaria (tested positive by RDT).', 'Prescribed intravenous antimalarials, oral antibiotics, and paracetamol syrup for fever control.', '2026-05-24'),
(4, 'P-1004', 'D-1004', 'Normal Singleton Pregnancy (34 Weeks Gestation).', 'Fetal heartbeat normal at 142 bpm. Prescribed iron supplements and advised scheduling next check-up in 2 weeks.', '2026-05-26')
ON DUPLICATE KEY UPDATE id=id;

-- Seed Prescriptions
INSERT INTO prescriptions (prescription_id, diagnosis_id, drug_name, quantity, dosage, prescription_date) VALUES
('RX-1001', 1, 'Amlodipine 5mg Tablets', 30, '1 tablet daily in the morning', '2026-05-20'),
('RX-1002', 1, 'Paracetamol 500mg Tablets', 20, '2 tablets every 8 hours when in pain', '2026-05-20'),
('RX-1003', 3, 'Artesunate-Amodiaquine (Coarsucam) Child Pack', 1, '1 tablet daily for 3 days', '2026-05-24'),
('RX-1004', 3, 'Amoxicillin Syrup 125mg/5ml', 2, '5ml three times daily for 7 days', '2026-05-24'),
('RX-1005', 4, 'Ferrous Sulfate + Folic Acid Tablets', 60, '1 tablet daily after meals', '2026-05-26')
ON DUPLICATE KEY UPDATE prescription_id=prescription_id;
