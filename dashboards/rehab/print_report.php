<?php
// Print & PDF Report Generator (Rehab Clinic)
require_once __DIR__ . '/../../core/config.php';
require_once __DIR__ . '/../../core/db.php';
require_once __DIR__ . '/../../core/functions.php';
require_once __DIR__ . '/../../core/session.php';

// Enforce login
checkLoggedIn();

$type = sanitize($_GET['type'] ?? 'patient');
$patientId = isset($_GET['id']) ? (int)$_GET['id'] : null;

// Fetch branding
$system_name = "Islamabad Rehab Center";
try {
    $stmt = $pdo->query("SELECT setting_value FROM system_settings WHERE setting_key = 'system_name'");
    $system_name = $stmt->fetchColumn() ?: $system_name;
} catch (Exception $e) {}

// Security Rule Check: Patients can only view/print their own reports
if ($_SESSION['user_role'] === 'patient') {
    // Look up the patient ID for the logged in user by name
    try {
        $pStmt = $pdo->prepare("SELECT id FROM patients WHERE name = ?");
        $pStmt->execute([$_SESSION['user_name']]);
        $myId = (int)$pStmt->fetchColumn();
        
        if ($type !== 'patient' || $patientId !== $myId) {
            die("Access Denied: You are only authorized to print your own Patient Recovery Report.");
        }
    } catch (Exception $e) {
        die("Error resolving patient profile.");
    }
}

// Ensure clinical staff and patients have access
if (!in_array($_SESSION['user_role'], ['super_admin', 'clinical_director', 'doctor', 'counselor', 'patient'])) {
    die("Access Denied.");
}

// Process Patient Recovery Report
if ($type === 'patient') {
    if (!$patientId) {
        die("Error: Patient ID is required.");
    }
    
    try {
        // Fetch patient
        $pStmt = $pdo->prepare("
            SELECT p.*, u.name AS therapist_name 
            FROM patients p
            LEFT JOIN users u ON p.assigned_therapist_id = u.id
            WHERE p.id = ?
        ");
        $pStmt->execute([$patientId]);
        $patient = $pStmt->fetch();
        
        if (!$patient) {
            die("Error: Patient record not found.");
        }
        
        // Fetch sessions
        $sStmt = $pdo->prepare("
            SELECT ts.*, u.name AS therapist_name 
            FROM therapy_sessions ts
            JOIN users u ON ts.therapist_id = u.id
            WHERE ts.patient_id = ?
            ORDER BY ts.session_date DESC
        ");
        $sStmt->execute([$patientId]);
        $sessions = $sStmt->fetchAll();
    } catch (Exception $e) {
        die("Database error: " . $e->getMessage());
    }
} else {
    // Process Clinic Summary Report
    try {
        $activePatientsCount = $pdo->query("SELECT COUNT(*) FROM patients WHERE treatment_status != 'Discharged'")->fetchColumn();
        $totalSessionsCount = $pdo->query("SELECT COUNT(*) FROM therapy_sessions")->fetchColumn();
        
        // Patients distribution
        $statusCounts = $pdo->query("
            SELECT treatment_status, COUNT(*) as count 
            FROM patients 
            GROUP BY treatment_status
        ")->fetchAll(PDO::FETCH_KEY_PAIR);
        
        // All patients list
        $patientsList = $pdo->query("
            SELECT p.*, u.name AS therapist_name 
            FROM patients p
            LEFT JOIN users u ON p.assigned_therapist_id = u.id
            ORDER BY p.name ASC
        ")->fetchAll();
    } catch (Exception $e) {
        die("Database error: " . $e->getMessage());
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Report - <?php echo ($type === 'patient') ? 'Patient Recovery' : 'Clinic Summary'; ?></title>
    <!-- Bootstrap 5 CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <style>
        body {
            font-size: 14px;
            color: #333;
            background: #fff;
        }
        .report-header {
            border-bottom: 3px double #333;
            padding-bottom: 20px;
            margin-bottom: 30px;
        }
        .section-title {
            background-color: #f2f2f2;
            padding: 6px 12px;
            font-weight: bold;
            margin-top: 30px;
            margin-bottom: 15px;
            border-left: 5px solid #333;
        }
        @media print {
            .no-print {
                display: none !important;
            }
            body {
                padding: 0;
                margin: 0;
            }
            .page-break {
                page-break-before: always;
            }
        }
    </style>
</head>
<body class="p-4">

    <!-- Print Control Banner -->
    <div class="container-fluid no-print mb-4 bg-light p-3 border rounded d-flex justify-content-between align-items-center">
        <div>
            <strong>Report Actions:</strong> Save as PDF or print to physical copy.
        </div>
        <div>
            <button onclick="window.print();" class="btn btn-primary btn-sm me-2">
                <i class="bi bi-printer"></i> Print / Save as PDF
            </button>
            <button onclick="window.close();" class="btn btn-secondary btn-sm">
                Close
            </button>
        </div>
    </div>

    <div class="container">
        <!-- Letterhead Header -->
        <div class="report-header d-flex justify-content-between align-items-center">
            <div>
                <h1 class="h2 text-dark font-weight-bold"><?php echo sanitize($system_name); ?></h1>
                <p class="text-secondary mb-0">Pathways to Physical and Behavioral Rehabilitation</p>
                <p class="text-secondary small">Sector H-8, Islamabad, Pakistan | Phone: +92-51-1234567</p>
            </div>
            <div class="text-end text-secondary small">
                <p class="mb-0"><strong>Generated On:</strong> <?php echo date('F d, Y - h:i A'); ?></p>
                <p class="mb-0"><strong>Audited By:</strong> <?php echo sanitize($_SESSION['user_name']); ?> (<?php echo sanitize($_SESSION['user_role_name'] ?? $_SESSION['user_role']); ?>)</p>
            </div>
        </div>

        <?php if ($type === 'patient'): ?>
            <!-- ================= PATIENT RECOVERY REPORT ================= -->
            <div class="text-center mb-4">
                <h3>PATIENT CLINICAL RECOVERY REPORT</h3>
                <p class="text-muted">Strictly Confidential - For Professional Medical Use Only</p>
            </div>

            <h4 class="section-title">1. Patient Profile Info</h4>
            <div class="row">
                <div class="col-md-6">
                    <table class="table table-bordered">
                        <tr>
                            <th class="w-50">Patient Name:</th>
                            <td><strong><?php echo sanitize($patient['name']); ?></strong></td>
                        </tr>
                        <tr>
                            <th>Patient ID:</th>
                            <td><code><?php echo sprintf("IRC-%04d", $patient['id']); ?></code></td>
                        </tr>
                        <tr>
                            <th>Age / Gender:</th>
                            <td><?php echo (int)$patient['age']; ?> yrs / <?php echo sanitize($patient['gender']); ?></td>
                        </tr>
                    </table>
                </div>
                <div class="col-md-6">
                    <table class="table table-bordered">
                        <tr>
                            <th class="w-50">Admission Date:</th>
                            <td><?php echo sanitize($patient['admission_date']); ?></td>
                        </tr>
                        <tr>
                            <th>Current Stage:</th>
                            <td><strong><?php echo sanitize($patient['treatment_status']); ?></strong></td>
                        </tr>
                        <tr>
                            <th>Primary Clinician:</th>
                            <td><?php echo sanitize($patient['therapist_name'] ?? 'Not Assigned'); ?></td>
                        </tr>
                    </table>
                </div>
            </div>

            <h4 class="section-title">2. Diagnosis & Medical History Summary</h4>
            <div class="card p-3 bg-light">
                <p class="mb-0 text-wrap"><?php echo nl2br(sanitize($patient['medical_history'] ?: 'No historical details specified.')); ?></p>
            </div>

            <h4 class="section-title">3. Therapy Progress logs & Clinical Assessments</h4>
            <table class="table table-bordered table-striped align-middle">
                <thead>
                    <tr class="table-secondary">
                        <th style="width: 180px;">Session Date & Time</th>
                        <th style="width: 140px;">Therapist</th>
                        <th style="width: 120px;">Session Type</th>
                        <th>Clinician Assessment Notes</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($sessions)): ?>
                        <tr>
                            <td colspan="4" class="text-center text-muted">No therapy session logs found on file for this patient.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($sessions as $s): ?>
                            <tr>
                                <td><code><?php echo date('M d, Y - h:i A', strtotime($s['session_date'])); ?></code></td>
                                <td><?php echo sanitize($s['therapist_name']); ?></td>
                                <td><span class="badge text-bg-light border"><?php echo sanitize($s['session_type']); ?></span></td>
                                <td class="small"><?php echo nl2br(sanitize($s['notes'])); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>

        <?php else: ?>
            <!-- ================= CLINIC SUMMARY REPORT ================= -->
            <div class="text-center mb-4">
                <h3>CLINICAL STATUS SUMMARY REPORT</h3>
                <p class="text-muted">General Facility Audit Overview</p>
            </div>

            <h4 class="section-title">1. Clinic General Metrics</h4>
            <div class="row text-center my-4">
                <div class="col-md-3">
                    <div class="border p-3 rounded">
                        <h5>Active Patients</h5>
                        <h2 class="text-primary"><?php echo (int)$activePatientsCount; ?></h2>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="border p-3 rounded">
                        <h5>Total Session Logs</h5>
                        <h2 class="text-success"><?php echo (int)$totalSessionsCount; ?></h2>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="border p-3 rounded">
                        <h5>Patient Stage Distribution</h5>
                        <div class="small d-flex justify-content-around mt-2">
                            <span>Intake: <strong><?php echo (int)($statusCounts['Intake'] ?? 0); ?></strong></span>
                            <span>Detox: <strong><?php echo (int)($statusCounts['Detox'] ?? 0); ?></strong></span>
                            <span>Rehab: <strong><?php echo (int)($statusCounts['Rehab'] ?? 0); ?></strong></span>
                            <span>Outpatient: <strong><?php echo (int)($statusCounts['Outpatient'] ?? 0); ?></strong></span>
                            <span>Discharged: <strong><?php echo (int)($statusCounts['Discharged'] ?? 0); ?></strong></span>
                        </div>
                    </div>
                </div>
            </div>

            <h4 class="section-title">2. Patient Directory Registry</h4>
            <table class="table table-bordered table-striped align-middle">
                <thead>
                    <tr class="table-secondary">
                        <th>ID</th>
                        <th>Name</th>
                        <th>Age/Gender</th>
                        <th>Admission Date</th>
                        <th>Status Stage</th>
                        <th>Assigned Therapist</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($patientsList as $p): ?>
                        <tr>
                            <td><code><?php echo sprintf("IRC-%04d", $p['id']); ?></code></td>
                            <td><strong><?php echo sanitize($p['name']); ?></strong></td>
                            <td><?php echo (int)$p['age']; ?> yrs / <?php echo sanitize($p['gender']); ?></td>
                            <td><?php echo sanitize($p['admission_date']); ?></td>
                            <td><strong><?php echo sanitize($p['treatment_status']); ?></strong></td>
                            <td><?php echo sanitize($p['therapist_name'] ?? 'Not Assigned'); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>

        <!-- Signatures Section -->
        <div class="row mt-5 pt-4 page-break">
            <div class="col-md-6 text-center">
                <hr class="w-50 mx-auto border-secondary">
                <p class="mb-0 text-secondary small">Prepared / Logged By</p>
                <p><strong><?php echo sanitize($_SESSION['user_name']); ?></strong></p>
            </div>
            <div class="col-md-6 text-center">
                <hr class="w-50 mx-auto border-secondary">
                <p class="mb-0 text-secondary small">Attesting Director Seal</p>
                <p><strong>Islamabad Rehab Center Admin</strong></p>
            </div>
        </div>
    </div>

    <!-- Automatically Trigger Print Dialog -->
    <script>
        window.addEventListener('DOMContentLoaded', () => {
            // Trigger browser print dialog automatically after page loads
            setTimeout(() => {
                window.print();
            }, 500);
        });
    </script>
</body>
</html>
