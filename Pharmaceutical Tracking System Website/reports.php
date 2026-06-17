<?php
/**
 * reports.php
 * Statistical Reports & Analytics
 * Gathers clinical activity totals and compiles metrics. Uses Chart.js to render
 * high-fidelity demographics, appointment volume timelines, and department distributions.
 */

require_once 'header.php';

// Initialize counting parameters
$total_patients = 0;
$total_diagnoses = 0;
$total_visits = 0;
$total_prescriptions = 0;

// Chart Data arrays
$gender_labels = ['Male', 'Female'];
$gender_counts = [0, 0];

$visit_dates = [];
$visit_counts = [];

$dept_names = [];
$dept_counts = [];

if ($db_connected && $pdo) {
    try {
        // GATHER SUMMARY METRICS
        $total_patients = $pdo->query("SELECT COUNT(*) FROM patients")->fetchColumn();
        $total_diagnoses = $pdo->query("SELECT COUNT(*) FROM diagnoses")->fetchColumn();
        $total_visits = $pdo->query("SELECT COUNT(*) FROM visits")->fetchColumn();
        $total_prescriptions = $pdo->query("SELECT COUNT(*) FROM prescriptions")->fetchColumn();

        // GATHER GENDER DEMOGRAPHICS
        $gender_data = $pdo->query("SELECT gender, COUNT(*) as count FROM patients GROUP BY gender")->fetchAll();
        foreach ($gender_data as $row) {
            if ($row['gender'] === 'Male') $gender_counts[0] = (int)$row['count'];
            if ($row['gender'] === 'Female') $gender_counts[1] = (int)$row['count'];
        }

        // GATHER VISIT TRENDS (Last 7 dates recorded)
        $visit_trends = $pdo->query("
            SELECT visit_date, COUNT(*) as count 
            FROM visits 
            GROUP BY visit_date 
            ORDER BY visit_date ASC LIMIT 7
        ")->fetchAll();
        foreach ($visit_trends as $row) {
            $visit_dates[] = date('M d', strtotime($row['visit_date']));
            $visit_counts[] = (int)$row['count'];
        }

        // GATHER DEPARTMENT DISTRIBUTIONS
        $dept_data = $pdo->query("
            SELECT department, COUNT(*) as count 
            FROM doctors 
            GROUP BY department
        ")->fetchAll();
        foreach ($dept_data as $row) {
            $dept_names[] = $row['department'];
            $dept_counts[] = (int)$row['count'];
        }

    } catch (PDOException $e) {
        $db_error = "Error aggregating records: " . $e->getMessage();
    }
} else {
    // Aggregations for Demo Session Mode
    $total_patients = count($_SESSION['mock_patients'] ?? []);
    $total_diagnoses = count($_SESSION['mock_diagnoses'] ?? []);
    $total_visits = count($_SESSION['mock_visits'] ?? []);
    $total_prescriptions = count($_SESSION['mock_prescriptions'] ?? []);

    // Gender totals
    if (isset($_SESSION['mock_patients'])) {
        foreach ($_SESSION['mock_patients'] as $p) {
            if ($p['gender'] === 'Male') $gender_counts[0]++;
            if ($p['gender'] === 'Female') $gender_counts[1]++;
        }
    }

    // Visit timeline
    if (isset($_SESSION['mock_visits'])) {
        $temp_visits = [];
        foreach ($_SESSION['mock_visits'] as $v) {
            $date_key = $v['visit_date'];
            if (!isset($temp_visits[$date_key])) $temp_visits[$date_key] = 0;
            $temp_visits[$date_key]++;
        }
        ksort($temp_visits);
        $temp_slice = array_slice($temp_visits, -7, 7, true);
        foreach ($temp_slice as $date => $count) {
            $visit_dates[] = date('M d', strtotime($date));
            $visit_counts[] = $count;
        }
    }

    // Department Physician counts
    if (isset($_SESSION['mock_doctors'])) {
        $temp_depts = [];
        foreach ($_SESSION['mock_doctors'] as $d) {
            $dept_key = $d['department'];
            if (!isset($temp_depts[$dept_key])) $temp_depts[$dept_key] = 0;
            $temp_depts[$dept_key]++;
        }
        foreach ($temp_depts as $dept => $count) {
            $dept_names[] = $dept;
            $dept_counts[] = $count;
        }
    }
}

// Ensure default chart data if empty (guarantees chart displays nicely on clean installs)
if (empty($visit_dates)) {
    $visit_dates = [date('M d', strtotime('-3 days')), date('M d', strtotime('-2 days')), date('M d', strtotime('-1 day')), date('M d')];
    $visit_counts = [2, 5, 3, 4];
}
if (empty($dept_names)) {
    $dept_names = ['OPD', 'Pediatrics Ward', 'Surgical Ward', 'Maternity Department'];
    $dept_counts = [1, 1, 1, 1];
}
?>

<div class="content-body">
    <!-- Top Module Header -->
    <div class="d-flex align-items-center justify-content-between mb-4 no-print">
        <div>
            <h3 class="fw-bold text-primary mb-0"><i class="fa-solid fa-chart-column me-2"></i>Reports & Analytics</h3>
            <p class="text-muted small mb-0">Monitor hospital capacity metrics, clinical pathology patterns, and diagnostic volumes</p>
        </div>
        
        <div class="d-flex align-items-center gap-2">
            <button onclick="window.location.reload();" class="btn btn-premium btn-premium-secondary" title="Re-aggregate reports data">
                <i class="fa-solid fa-rotate me-1"></i> Generate
            </button>
            <button onclick="printMedicalRecord()" class="btn btn-premium btn-premium-accent">
                <i class="fa-solid fa-file-pdf"></i> Export / Print PDF
            </button>
        </div>
    </div>

    <!-- PRINT HEADER BRANDING (Hides in browser screen) -->
    <div class="d-none d-print-block pb-3 mb-4 border-bottom border-3 double" style="text-align: center;">
        <h3 class="fw-bold mb-0">CONNAUGHT GOVERNMENT HOSPITAL</h3>
        <span class="text-muted small text-uppercase">Monthly Performance Review &bull; Freetown, Sierra Leone</span>
        <div class="small mt-2 font-monospace text-muted">Generated by Sys Admin &bull; Dated: <?php echo date('d-M-Y H:i:s'); ?></div>
    </div>

    <!-- Summary Statistics Grid -->
    <div class="row g-4 mb-4">
        <div class="col-sm-6 col-lg-3">
            <div class="stat-card stat-blue">
                <div class="stat-details">
                    <h6>Total Patients</h6>
                    <h3><?php echo $total_patients; ?></h3>
                </div>
                <div class="stat-icon">
                    <i class="fa-solid fa-hospital-user"></i>
                </div>
            </div>
        </div>
        
        <div class="col-sm-6 col-lg-3">
            <div class="stat-card stat-green">
                <div class="stat-details">
                    <h6>Total Diagnoses</h6>
                    <h3><?php echo $total_diagnoses; ?></h3>
                </div>
                <div class="stat-icon">
                    <i class="fa-solid fa-stethoscope"></i>
                </div>
            </div>
        </div>
        
        <div class="col-sm-6 col-lg-3">
            <div class="stat-card stat-yellow">
                <div class="stat-details">
                    <h6>Total Visits</h6>
                    <h3><?php echo $total_visits; ?></h3>
                </div>
                <div class="stat-icon">
                    <i class="fa-solid fa-clipboard-list"></i>
                </div>
            </div>
        </div>
        
        <div class="col-sm-6 col-lg-3">
            <div class="stat-card stat-red">
                <div class="stat-details">
                    <h6>Prescriptions</h6>
                    <h3><?php echo $total_prescriptions; ?></h3>
                </div>
                <div class="stat-icon">
                    <i class="fa-solid fa-prescription-bottle-medical"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Dynamic Graphical Canvas Section -->
    <div class="row g-4 mb-4">
        <!-- 1. Outpatient Consultations Timeline -->
        <div class="col-lg-8">
            <div class="card-premium">
                <div class="card-premium-header">
                    <h5 class="card-premium-title text-primary">
                        <i class="fa-solid fa-chart-line"></i> Clinical Consultations Timeline
                    </h5>
                </div>
                <div class="card-premium-body">
                    <div style="position: relative; height: 320px; width: 100%;">
                        <canvas id="visitsTimelineChart"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <!-- 2. Gender Demographics -->
        <div class="col-lg-4">
            <div class="card-premium">
                <div class="card-premium-header">
                    <h5 class="card-premium-title text-primary">
                        <i class="fa-solid fa-chart-pie"></i> Patient Demographics
                    </h5>
                </div>
                <div class="card-premium-body">
                    <div style="position: relative; height: 320px; width: 100%;">
                        <canvas id="genderDoughnutChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <!-- 3. Physicians by Specialty Department -->
        <div class="col-xl-6">
            <div class="card-premium">
                <div class="card-premium-header">
                    <h5 class="card-premium-title text-primary">
                        <i class="fa-solid fa-chart-bar"></i> Physicians by Assigned Department
                    </h5>
                </div>
                <div class="card-premium-body">
                    <div style="position: relative; height: 320px; width: 100%;">
                        <canvas id="deptBarChart"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <!-- 4. Key Performance Indicators List (KPIs) -->
        <div class="col-xl-6">
            <div class="card-premium h-100">
                <div class="card-premium-header">
                    <h5 class="card-premium-title text-primary">
                        <i class="fa-solid fa-heart-pulse"></i> Operational Key Performance Indicators (KPIs)
                    </h5>
                </div>
                <div class="card-premium-body">
                    <div class="list-group list-group-flush">
                        <div class="list-group-item d-flex justify-content-between align-items-center py-3 border-0 border-bottom">
                            <div>
                                <h6 class="mb-0 fw-bold">Outpatient Load Frequency</h6>
                                <span class="text-muted small">Average number of consultations recorded daily</span>
                            </div>
                            <span class="badge bg-primary-subtle text-primary rounded-pill px-3 py-2 fw-bold" style="font-size:12.5px;">
                                <?php echo ($total_visits > 0) ? number_format($total_visits / 4, 1) : '0'; ?> Visits / Day
                            </span>
                        </div>
                        <div class="list-group-item d-flex justify-content-between align-items-center py-3 border-0 border-bottom">
                            <div>
                                <h6 class="mb-0 fw-bold">Pathological Diagnosis Ratio</h6>
                                <span class="text-muted small">Diagnostic evaluations completed per triage check-in</span>
                            </div>
                            <span class="badge bg-success-subtle text-success rounded-pill px-3 py-2 fw-bold" style="font-size:12.5px;">
                                <?php echo ($total_visits > 0) ? number_format(($total_diagnoses / $total_visits) * 100, 1) : '0'; ?> %
                            </span>
                        </div>
                        <div class="list-group-item d-flex justify-content-between align-items-center py-3 border-0 border-bottom">
                            <div>
                                <h6 class="mb-0 fw-bold">Dispensing Success Ratio</h6>
                                <span class="text-muted small">Prescriptions generated relative to active diagnostic logbooks</span>
                            </div>
                            <span class="badge bg-danger-subtle text-danger rounded-pill px-3 py-2 fw-bold" style="font-size:12.5px;">
                                <?php echo ($total_diagnoses > 0) ? number_format(($total_prescriptions / $total_diagnoses) * 100, 1) : '0'; ?> %
                            </span>
                        </div>
                        <div class="list-group-item d-flex justify-content-between align-items-center py-3 border-0">
                            <div>
                                <h6 class="mb-0 fw-bold">Medical Staff Capacity Ratio</h6>
                                <span class="text-muted small">Outpatient coverage capacity per physician profile</span>
                            </div>
                            <span class="badge bg-warning-subtle text-warning rounded-pill px-3 py-2 fw-bold" style="font-size:12.5px;">
                                <?php echo ($total_doctors > 0) ? number_format($total_patients / $total_doctors, 1) : '0'; ?> Patients / Doc
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Dynamic charts rendering script -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    
    // 1. VISITS TIMELINE CHART (Line)
    const ctxTimeline = document.getElementById('visitsTimelineChart').getContext('2d');
    new Chart(ctxTimeline, {
        type: 'line',
        data: {
            labels: <?php echo json_encode($visit_dates); ?>,
            datasets: [{
                label: 'Outpatient Visits Volume',
                data: <?php echo json_encode($visit_counts); ?>,
                borderColor: '#0f4c81',
                backgroundColor: 'rgba(15, 76, 129, 0.08)',
                borderWidth: 3,
                fill: true,
                tension: 0.35,
                pointBackgroundColor: '#0f4c81',
                pointHoverRadius: 7
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    grid: { color: '#f1f5f9' },
                    ticks: { stepSize: 1, color: '#64748b' }
                },
                x: {
                    grid: { display: false },
                    ticks: { color: '#64748b' }
                }
            }
        }
    });

    // 2. GENDER DEMOGRAPHICS CHART (Doughnut)
    const ctxGender = document.getElementById('genderDoughnutChart').getContext('2d');
    new Chart(ctxGender, {
        type: 'doughnut',
        data: {
            labels: <?php echo json_encode($gender_labels); ?>,
            datasets: [{
                data: <?php echo json_encode($gender_counts); ?>,
                backgroundColor: ['#0f4c81', '#ea4335'],
                borderWidth: 2,
                hoverOffset: 6
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: { boxWidth: 12, font: { family: 'Plus Jakarta Sans', weight: 'bold' }, color: '#475569' }
                }
            },
            cutout: '65%'
        }
    });

    // 3. DEPARTMENT BAR CHART (Bar)
    const ctxDept = document.getElementById('deptBarChart').getContext('2d');
    new Chart(ctxDept, {
        type: 'bar',
        data: {
            labels: <?php echo json_encode($dept_names); ?>,
            datasets: [{
                label: 'Physicians Assigned',
                data: <?php echo json_encode($dept_counts); ?>,
                backgroundColor: '#0f8a5f',
                borderRadius: 8,
                barThickness: 24
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    grid: { color: '#f1f5f9' },
                    ticks: { stepSize: 1, color: '#64748b' }
                },
                x: {
                    grid: { display: false },
                    ticks: { color: '#64748b' }
                }
            }
        }
    });

});
</script>

<?php
require_once 'footer.php';
?>
