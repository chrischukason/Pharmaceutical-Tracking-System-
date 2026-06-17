# Patient Record Management System (PRMS)
### Connaught Government Hospital &mdash; Freetown, Sierra Leone
**University Database Management System Course Project Prototype**

---

## 📋 System Overview

The **Patient Record Management System (PRMS)** is a modern, responsive web application designed for the administrative and clinical workflows of a government hospital. Specifically modeled around **Connaught Government Hospital** (the national referral hospital in Freetown, Sierra Leone), the system delivers a professional, clean medical-themed interface in classic hospital blue and white colors.

The project is structured in modular **PHP** and **MySQL**, utilizing secure **PHP PDO** database transactions. It is ready for academic evaluation, featuring an automatic database installer, dual-engine fail-safe mechanisms, live search filters, dynamic patient age calculation, and signature-ready printing formats.

---

## 🚀 Key Functional Modules

1. **Secure Admin Authentication (`index.php`)**
   * Double-branded login gateway featuring hospital logo, diagnostic alerts, and a secure session manager.
2. **Operational Dashboard (`dashboard.php`)**
   * Summary metric cards tracking hospital capacity, welcoming banners, and activity tables showing recent patient registrations and pathologically checked diagnosis logs.
3. **Demographic Patient Registry (`patients.php`)**
   * Complete CRUD (Create, Read, Update, Delete) registry featuring auto-generated ID tracking codes (`P-XXXX`), residential inputs, phone validations, and automatic age calculations from Date of Birth.
4. **Physician Staff Directory (`doctors.php`)**
   * Medical staff manager to assign physicians to specialized hospital wards (OPD, Pediatrics, Surgical, Maternity, Cardiology, Emergency).
5. **Outpatient Check-in Logbook (`visits.php`)**
   * Consultations logger tracking crucial triage parameters, including Blood Pressure (BP), Temperature (°C), Weight (kg), and consultation reasons.
6. **Pathological Diagnosis Registry (`diagnosis.php`)**
   * Logbook mapping patient demographic IDs to formal pathological assessments and medical treatment instructions.
7. **Pharmacy Prescription Dispensation (`prescriptions.php`)**
   * Billing and pharmacy orders desk with automatic track codes (`RX-XXXX`). Generates double-signature, professional print slips.
8. **Reports & Metric Analytics (`reports.php`)**
   * High-fidelity interactive analytics canvas leveraging **Chart.js** to compile patient gender statistics, appointment volume timelines, doctor distribution charts, and hospital Key Performance Indicators (KPIs).
9. **Hospital Settings & Controls (`settings.php`)**
   * Custom hospital branding options, admin password managers, connection environment logs, and factory reset buttons.

---

## ⚙️ Connection Architecture (Dual-Engine Fail-Safe)

To ensure the prototype remains 100% interactive under any deployment environment, the system utilizes a **dual-connectivity engine**:

* **Relational Mode (MySQL Database)**: Connects securely using PHP PDO. An automated browser-based setup wizard (`setup.php`) is provided to auto-install tables and seed data instantly.
* **Demonstration Mode (Session Fail-Safe)**: If a local MySQL server is offline or not configured, the system automatically switches to a session-based relational array simulator. This ensures the entire system is interactive and editable immediately out-of-the-box!

---

## 🛠️ Step-by-Step Installation & Deployment

### Prerequisite: Set up a local PHP server
Download and install [XAMPP](https://www.apachefriends.org/) (recommended) or WampServer.

### Step 1: Copy Project Directory
Move the `Pharmaceutical Tracking System Website` folder into your local server root:
* **XAMPP**: `C:\xampp\htdocs\hospital-portal\`
* **WAMP**: `C:\wamp64\www\hospital-portal\`

### Step 2: Start Apache and MySQL Services
Open the XAMPP/WAMP Control Panel and start **Apache** and **MySQL**.

### Step 3: Run Database Setup Wizard
1. Open your browser and navigate to: `http://localhost/hospital-portal/setup.php`
2. Review the pre-populated MySQL settings:
   * **Host**: `localhost`
   * **Username**: `root`
   * **Password**: *[Leave empty]*
   * **Database**: `hospital_db`
3. Click **Run Database Setup**.
4. The wizard will create the schema, establish all index keys, and load realistic seeder data (mock patients, doctors, visits, diagnoses, prescriptions, and hashed administrator profiles).

### Step 4: Login to the Administrator Portal
Navigate to: `http://localhost/hospital-portal/index.php`
* **Default Username**: `admin`
* **Default Password**: `admin123`

---

## 📂 File Architecture

```text
hospital-portal/
│
├── database.sql        # Raw SQL schema containing structural DDL and seeder data
├── db.php              # PHP PDO connection driver and Session Mock Mode initializer
├── setup.php           # Browser-based interactive database installer wizard
│
├── header.php          # Reusable header component, CDNs, navbar, and auth checks
├── sidebar.php         # Reusable responsive left navigation medical modules
├── footer.php          # Closure tags, library CDN scripts, and custom app bindings
│
├── index.php           # Secure administrative login portal
├── logout.php          # Ends session and redirects to login
│
├── dashboard.php       # Operational admin greeting dashboard
├── patients.php        # Patient demographics entry and searchable CRUD table
├── doctors.php         # Physician specialties registry and searchable CRUD table
├── visits.php          # Outpatient check-in log and vital signs tracker
├── diagnosis.php       # Pathology logger linking consultations to assessments
├── prescriptions.php   # Drug dispenser module and signature-ready slip printer
├── reports.php         # Canvas report charts (Chart.js) and hospital KPIs
├── settings.php        # Branding configurations and demonstration resets
│
└── assets/
    ├── css/
    │   └── style.css   # Variables stylesheet, glass cards, and printing overrides
    └── js/
        └── app.js      # Sidebar toggler, dob age calculations, and dynamic autofills
```

---

## 📊 Relational Database Model

The database is built on relational integrity constraints using foreign key cascades:

```mermaid
erDiagram
    users {
        int id PK
        varchar username UNIQUE
        varchar password
        varchar full_name
        varchar role
        timestamp created_at
    }
    patients {
        varchar patient_id PK
        varchar full_name
        int age
        varchar gender
        varchar address
        varchar phone_number
        date date_birth
        text medical_complaint
        timestamp created_at
    }
    doctors {
        varchar doctor_id PK
        varchar doctor_name
        varchar specialization
        varchar department
        varchar phone_number
        timestamp created_at
    }
    visits {
        int id PK
        varchar patient_id FK
        varchar doctor_id FK
        date visit_date
        varchar vitals_bp
        decimal vitals_temp
        int vitals_weight
        text visit_reason
        timestamp created_at
    }
    diagnoses {
        int id PK
        varchar patient_id FK
        varchar doctor_id FK
        text diagnosis_details
        text treatment_notes
        date diagnosis_date
        timestamp created_at
    }
    prescriptions {
        varchar prescription_id PK
        int diagnosis_id FK
        varchar drug_name
        int quantity
        varchar dosage
        date prescription_date
        timestamp created_at
    }

    patients ||--o{ visits : "attends"
    doctors ||--o{ visits : "conducts"
    patients ||--o{ diagnoses : "receives"
    doctors ||--o{ diagnoses : "records"
    diagnoses ||--o{ prescriptions : "includes"
```

---

## 🎨 UI/UX Features and Presentation Details

* **Healthcare Aesthetic**: Built using premium HSL values featuring classic hospital deep blue (`#0f4c81`) and emerald teal (`#0f8a5f`) accents with soft blue-gray floating panels.
* **Date to Age Converter**: In the patient registry screen, selecting a Date of Birth dynamically calculates and fills the Age field instantly.
* **Dropdown Autofill Engine**: Selecting a Patient ID on the Diagnosis or Visits screen instantly populates the read-only Patient Name field.
* **Pharmacy Printing Engine**: Clicking "Print" next to a prescription formats a custom signature-ready document. It hides headers and sidebars via CSS media queries, opening the browser's native print-to-PDF utility.
* **Responsive Visual Framework**: Hand-crafted CSS grids adjust layouts for desktop, tablet, and mobile views.
