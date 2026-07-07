<?php
// Dashboard landing page
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/sidebar.php';

// Initialize variables for different roles
$userCount = 0;
$pageCount = 0;
$roleCount = 0;
$patientInfo = null;
$mySessions = [];
$treatmentPlan = null;
$todayLog = null;
$pendingTicketsCount = 0;

$activePatientsCount = 0;
$totalSessionsCount = 0;
$recentSessions = [];
$clinicPatients = [];
$therapistLoads = [];
$stageCounts = [
    'Intake' => 0,
    'Detox' => 0,
    'Rehab' => 0,
    'Outpatient' => 0,
    'Discharged' => 0
];

try {
    // 1. Fetch metrics for Super Admin & General
    $userCount = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
    $pageCount = $pdo->query("SELECT COUNT(*) FROM sys_pages")->fetchColumn();
    $roleCount = $pdo->query("SELECT COUNT(*) FROM sys_roles")->fetchColumn();
    
    // 2. Fetch records for Patient Portal
    if ($_SESSION['user_role'] === 'patient') {
        $patStmt = $pdo->prepare("
            SELECT p.*, u.name AS therapist_name, u.email AS therapist_email 
            FROM patients p
            LEFT JOIN users u ON p.assigned_therapist_id = u.id
            WHERE p.name = ?
        ");
        $patStmt->execute([$_SESSION['user_name']]);
        $patientInfo = $patStmt->fetch();
        
        if ($patientInfo) {
            // Sessions
            $sessStmt = $pdo->prepare("
                SELECT ts.*, u.name AS therapist_name 
                FROM therapy_sessions ts
                JOIN users u ON ts.therapist_id = u.id
                WHERE ts.patient_id = ?
                ORDER BY ts.session_date DESC LIMIT 5
            ");
            $sessStmt->execute([$patientInfo['id']]);
            $mySessions = $sessStmt->fetchAll();

            // Active Treatment Plan
            $planStmt = $pdo->prepare("
                SELECT tp.*, u.name AS clinician_name 
                FROM treatment_plans tp 
                LEFT JOIN users u ON tp.created_by = u.id 
                WHERE tp.patient_id = ? 
                ORDER BY tp.id DESC LIMIT 1
            ");
            $planStmt->execute([$patientInfo['id']]);
            $treatmentPlan = $planStmt->fetch();

            // Today's Mood Log
            $logStmt = $pdo->prepare("SELECT * FROM patient_daily_logs WHERE patient_id = ? AND log_date = ?");
            $logStmt->execute([$patientInfo['id'], date('Y-m-d')]);
            $todayLog = $logStmt->fetch();
        }
    }

    // 3. Fetch records for Doctor & Clinician Portal
    if (in_array($_SESSION['user_role'], ['doctor', 'clinical_director', 'counselor', 'super_admin'])) {
        $activePatientsCount = $pdo->query("SELECT COUNT(*) FROM patients WHERE treatment_status != 'Discharged'")->fetchColumn();
        $totalSessionsCount = $pdo->query("SELECT COUNT(*) FROM therapy_sessions")->fetchColumn();
        $pendingTicketsCount = $pdo->query("SELECT COUNT(*) FROM support_requests WHERE status = 'Pending'")->fetchColumn();
        
        // Recent Sessions
        $recentSessionsStmt = $pdo->query("
            SELECT ts.*, p.name AS patient_name, u.name AS therapist_name 
            FROM therapy_sessions ts
            JOIN patients p ON ts.patient_id = p.id
            JOIN users u ON ts.therapist_id = u.id
            ORDER BY ts.session_date DESC LIMIT 5
        ");
        $recentSessions = $recentSessionsStmt->fetchAll();
        
        // Recent Patients
        $patientsListStmt = $pdo->query("
            SELECT p.*, u.name AS therapist_name 
            FROM patients p
            LEFT JOIN users u ON p.assigned_therapist_id = u.id
            ORDER BY p.admission_date DESC LIMIT 5
        ");
        $clinicPatients = $patientsListStmt->fetchAll();

        // Therapist Load Breakdown
        $therapistLoads = $pdo->query("
            SELECT u.name, COUNT(p.id) as load_count 
            FROM users u 
            LEFT JOIN patients p ON p.assigned_therapist_id = u.id 
            WHERE u.role IN ('doctor', 'clinical_director') AND u.is_active = 1 
            GROUP BY u.id 
            ORDER BY load_count DESC
        ")->fetchAll();

        // Stage Distribution
        $rawStageCounts = $pdo->query("
            SELECT treatment_status, COUNT(*) as count 
            FROM patients 
            GROUP BY treatment_status
        ")->fetchAll(PDO::FETCH_KEY_PAIR);
        foreach ($rawStageCounts as $stg => $cnt) {
            $stageCounts[$stg] = $cnt;
        }
    }
} catch (Exception $e) {
    // Fail silently
}

// POST: Handle Patient Mood Logging
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['log_mood']) && $_SESSION['user_role'] === 'patient') {
    $mood_score = (int)($_POST['mood_score'] ?? 3);
    $notes = sanitize($_POST['mood_notes'] ?? '');
    $log_date = date('Y-m-d');
    
    if ($patientInfo) {
        try {
            $stmt = $pdo->prepare("
                INSERT INTO patient_daily_logs (patient_id, log_date, mood_score, notes) 
                VALUES (?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE mood_score = VALUES(mood_score), notes = VALUES(notes)
            ");
            $stmt->execute([$patientInfo['id'], $log_date, $mood_score, $notes]);
            set_flash_message('success', 'Daily status logged successfully!');
            redirect('dashboard.php');
        } catch (Exception $e) {
            set_flash_message('danger', 'Error saving daily log: ' . $e->getMessage());
        }
    }
}

// POST: Handle Admin Settings Update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_settings']) && $_SESSION['user_role'] === 'super_admin') {
    $new_name = sanitize($_POST['system_name'] ?? 'Islamabad Rehab Center');
    $new_footer = sanitize($_POST['footer_text'] ?? '');
    
    try {
        $stmt = $pdo->prepare("INSERT INTO system_settings (setting_key, setting_value) VALUES ('system_name', ?) ON DUPLICATE KEY UPDATE setting_value = ?");
        $stmt->execute([$new_name, $new_name]);
        $stmt = $pdo->prepare("INSERT INTO system_settings (setting_key, setting_value) VALUES ('footer_text', ?) ON DUPLICATE KEY UPDATE setting_value = ?");
        $stmt->execute([$new_footer, $new_footer]);
        set_flash_message('success', 'System branding settings updated successfully!');
        redirect('dashboard.php');
    } catch (Exception $e) {
        set_flash_message('danger', 'Error updating settings: ' . $e->getMessage());
    }
}
?>

<!-- Include Chart.js from CDN for medical visual telemetry graphs -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<!-- Content Wrapper -->
<main class="app-main animate-fade-in">
    <!-- Content Header -->
    <div class="app-content-header">
        <div class="container-fluid">
            <div class="row align-items-center">
                <div class="col-sm-6">
                    <h3 class="mb-0">
                        <?php 
                        if ($_SESSION['user_role'] === 'patient') echo "Patient Dashboard";
                        elseif (in_array($_SESSION['user_role'], ['doctor', 'clinical_director', 'counselor'])) echo "Clinician Workspace";
                        else echo "System Admin Panel";
                        ?>
                    </h3>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-end">
                        <?php foreach ($breadcrumbs as $crumb): ?>
                            <?php if ($crumb['url'] === '#'): ?>
                                <li class="breadcrumb-item active"><?php echo sanitize($crumb['name']); ?></li>
                            <?php else: ?>
                                <li class="breadcrumb-item"><a href="<?php echo $crumb['url']; ?>"><?php echo sanitize($crumb['name']); ?></a></li>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    <!-- Main content -->
    <div class="app-content">
        <div class="container-fluid">
            <?php display_flash_message(); ?>
            
            <!-- ================= PATIENT PORTAL VIEW ================= -->
            <?php if ($_SESSION['user_role'] === 'patient'): ?>
                <div class="row">
                    <!-- Left Column: Patient Details & Mood Tracker -->
                    <div class="col-lg-5">
                        <!-- Patient Admission Card -->
                        <div class="card card-primary card-outline shadow mb-4">
                            <div class="card-header d-flex justify-content-between align-items-center bg-transparent border-bottom-0">
                                <h5 class="card-title mb-0"><i class="bi bi-person-heart me-2 text-primary"></i>Admission Information</h5>
                                <?php if ($patientInfo): ?>
                                    <a href="<?php echo BASE_URL; ?>dashboards/rehab/print_report.php?type=patient&id=<?php echo (int)$patientInfo['id']; ?>" target="_blank" class="btn btn-sm btn-outline-primary py-1 px-3 rounded-pill">
                                        <i class="bi bi-printer me-1"></i> Print PDF
                                    </a>
                                <?php endif; ?>
                            </div>
                            <div class="card-body">
                                <?php if ($patientInfo): ?>
                                    <div class="text-center mb-4">
                                        <div class="display-5 text-primary mb-2">
                                            <i class="bi bi-person-circle"></i>
                                        </div>
                                        <h4 class="fw-bold mb-1"><?php echo sanitize($patientInfo['name']); ?></h4>
                                        <span class="badge bg-light text-dark border">Patient ID: <?php echo sprintf("IRC-%04d", $patientInfo['id']); ?></span>
                                    </div>
                                    <table class="table table-bordered mb-0">
                                        <tr>
                                            <th class="bg-light">Gender / Age</th>
                                            <td><?php echo sanitize($patientInfo['gender']); ?> / <?php echo (int)$patientInfo['age']; ?> yrs</td>
                                        </tr>
                                        <tr>
                                            <th class="bg-light">Admission Date</th>
                                            <td><code><?php echo sanitize($patientInfo['admission_date']); ?></code></td>
                                        </tr>
                                        <tr>
                                            <th class="bg-light">Assigned Clinician</th>
                                            <td>
                                                <strong><?php echo sanitize($patientInfo['therapist_name'] ?? 'Not Assigned'); ?></strong>
                                            </td>
                                        </tr>
                                        <tr>
                                            <th class="bg-light">Recovery Phase</th>
                                            <td>
                                                <?php 
                                                $status = $patientInfo['treatment_status'];
                                                if ($status === 'Intake') echo '<span class="badge text-bg-warning">Intake / Screening</span>';
                                                elseif ($status === 'Detox') echo '<span class="badge text-bg-danger">Detoxification</span>';
                                                elseif ($status === 'Rehab') echo '<span class="badge text-bg-primary">Residential Rehab</span>';
                                                elseif ($status === 'Outpatient') echo '<span class="badge text-bg-info">Outpatient</span>';
                                                else echo '<span class="badge text-bg-success">Discharged</span>';
                                                ?>
                                            </td>
                                        </tr>
                                    </table>
                                <?php else: ?>
                                    <div class="alert alert-warning mb-0">
                                        <i class="bi bi-exclamation-triangle-fill me-2"></i>No patient profile associated with your user name "<?php echo sanitize($_SESSION['user_name']); ?>".
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Mood & Status Tracker -->
                        <?php if ($patientInfo): ?>
                            <div class="card shadow mb-4">
                                <div class="card-header bg-light">
                                    <h5 class="card-title mb-0"><i class="bi bi-heart-pulse-fill text-primary me-2"></i>Daily Mood & Status Logger</h5>
                                </div>
                                <div class="card-body">
                                    <?php if ($todayLog): ?>
                                        <div class="p-3 bg-light rounded text-center border">
                                            <div class="fs-1 mb-2">
                                                <?php 
                                                $moods = [
                                                    1 => '😠 Critical Distress',
                                                    2 => '😟 Struggling',
                                                    3 => '😐 Neutral',
                                                    4 => '🙂 Good',
                                                    5 => '😄 Excellent'
                                                ];
                                                echo $moods[$todayLog['mood_score']] ?? '😐';
                                                ?>
                                            </div>
                                            <p class="text-secondary small mb-1">Status logged today:</p>
                                            <strong class="text-dark">"<?php echo sanitize($todayLog['notes'] ?: 'No details written'); ?>"</strong>
                                        </div>
                                    <?php else: ?>
                                        <form action="" method="post">
                                            <div class="mb-3 text-center">
                                                <label class="form-label text-secondary small mb-3">How are you feeling today?</label>
                                                <div class="d-flex justify-content-center gap-2">
                                                    <span onclick="setMood(1, this)" class="mood-btn mood-1" title="Critical">😠</span>
                                                    <span onclick="setMood(2, this)" class="mood-btn mood-2" title="Struggling">😟</span>
                                                    <span onclick="setMood(3, this)" class="mood-btn mood-3 active" title="Neutral">😐</span>
                                                    <span onclick="setMood(4, this)" class="mood-btn mood-4" title="Good">🙂</span>
                                                    <span onclick="setMood(5, this)" class="mood-btn mood-5" title="Excellent">😄</span>
                                                </div>
                                                <input type="hidden" name="mood_score" id="mood_score_val" value="3">
                                            </div>
                                            <div class="mb-3">
                                                <textarea name="mood_notes" class="form-control" rows="2" placeholder="Write any reflections, cravings, or withdrawal notes..." required></textarea>
                                            </div>
                                            <button type="submit" name="log_mood" class="btn btn-primary w-100 py-2">
                                                <i class="bi bi-check-lg me-1"></i> Log Status
                                            </button>
                                        </form>
                                        <script>
                                            function setMood(val, element) {
                                                document.getElementById('mood_score_val').value = val;
                                                document.querySelectorAll('.mood-btn').forEach(b => b.classList.remove('active'));
                                                element.classList.add('active');
                                            }
                                        </script>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Right Column: Recovery Goals & Timelines -->
                    <div class="col-lg-7">
                        <!-- Active Treatment Goals -->
                        <div class="card card-success card-outline shadow mb-4">
                            <div class="card-header bg-transparent border-bottom-0">
                                <h5 class="card-title mb-0"><i class="bi bi-compass-fill text-success me-2"></i>My Recovery Treatment Goals</h5>
                            </div>
                            <div class="card-body">
                                <?php if ($treatmentPlan): ?>
                                    <div class="mb-3">
                                        <h6 class="text-secondary small fw-bold">Therapy Goals:</h6>
                                        <div class="p-3 bg-light rounded text-dark font-monospace small" style="white-space: pre-wrap;"><?php echo sanitize($treatmentPlan['therapy_goals']); ?></div>
                                    </div>
                                    <div class="mb-3">
                                        <h6 class="text-secondary small fw-bold">Detox Phase Protocol:</h6>
                                        <p class="small text-secondary mb-0"><?php echo nl2br(sanitize($treatmentPlan['detox_plan'])); ?></p>
                                    </div>
                                    <div class="text-end text-muted small mt-2">
                                        Formulated by clinician: <strong><?php echo sanitize($treatmentPlan['clinician_name']); ?></strong>
                                    </div>
                                <?php else: ?>
                                    <div class="p-4 bg-light rounded text-center text-muted">
                                        <i class="bi bi-clipboard2-pulse fs-2 mb-2 d-block"></i>
                                        No active treatment plan registered on file yet.
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Therapy Session History -->
                        <div class="card shadow mb-4">
                            <div class="card-header bg-light">
                                <h5 class="card-title mb-0"><i class="bi bi-calendar-check me-2 text-primary"></i>My Therapy Session History</h5>
                            </div>
                            <div class="card-body p-0">
                                <div class="table-responsive">
                                    <table class="table table-striped align-middle mb-0">
                                        <thead>
                                            <tr>
                                                <th>Date / Time</th>
                                                <th>Therapist</th>
                                                <th>Type</th>
                                                <th>Clinician Notes</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php if (empty($mySessions)): ?>
                                                <tr>
                                                    <td colspan="4" class="text-center py-4 text-muted">No logged sessions.</td>
                                                </tr>
                                            <?php else: ?>
                                                <?php foreach ($mySessions as $s): ?>
                                                    <tr>
                                                        <td><code><?php echo date('M d - h:i A', strtotime($s['session_date'])); ?></code></td>
                                                        <td><strong><?php echo sanitize($s['therapist_name']); ?></strong></td>
                                                        <td><span class="badge text-bg-light border"><?php echo sanitize($s['session_type']); ?></span></td>
                                                        <td><span class="small text-muted"><?php echo sanitize($s['notes']); ?></span></td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            <?php endif; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

            <!-- ================= CLINICIAN & STAFF VIEW ================= -->
            <?php elseif (in_array($_SESSION['user_role'], ['doctor', 'clinical_director', 'counselor'])): ?>
                <!-- Action row -->
                <div class="row mb-4">
                    <div class="col-12 d-flex justify-content-between align-items-center">
                        <span class="text-secondary">Logged in as: <strong><?php echo sanitize($_SESSION['user_name']); ?></strong> (<?php echo sanitize($_SESSION['user_role_name'] ?? $_SESSION['user_role']); ?>)</span>
                        <div class="d-flex gap-2">
                            <a href="<?php echo BASE_URL; ?>dashboards/rehab/print_report.php?type=clinical" target="_blank" class="btn btn-outline-primary btn-sm px-4 rounded-pill shadow-sm">
                                <i class="bi bi-printer me-1"></i> Export Summary PDF
                            </a>
                        </div>
                    </div>
                </div>
                <!-- Clinician Stats Boxes -->
                <div class="row">
                    <div class="col-lg-4 col-md-6 col-12">
                        <div class="small-box text-bg-primary mb-4 p-3 rounded position-relative">
                            <div class="inner">
                                <h3><?php echo (int)$activePatientsCount; ?></h3>
                                <p>Active Admitted Patients</p>
                            </div>
                            <div class="icon position-absolute end-0 top-0 mt-2 me-3 fs-1 opacity-25">
                                <i class="bi bi-people-fill"></i>
                            </div>
                            <a href="<?php echo BASE_URL; ?>dashboards/rehab/manage_patients.php" class="small-box-footer text-white text-decoration-none d-block mt-3 text-center small">
                                Patient Intake Directory <i class="bi bi-arrow-right-circle-fill ms-1"></i>
                            </a>
                        </div>
                    </div>
                    <div class="col-lg-4 col-md-6 col-12">
                        <div class="small-box text-bg-success mb-4 p-3 rounded position-relative">
                            <div class="inner">
                                <h3><?php echo (int)$totalSessionsCount; ?></h3>
                                <p>Logged Therapy Sessions</p>
                            </div>
                            <div class="icon position-absolute end-0 top-0 mt-2 me-3 fs-1 opacity-25">
                                <i class="bi bi-card-text"></i>
                            </div>
                            <a href="<?php echo BASE_URL; ?>dashboards/rehab/therapy_sessions.php" class="small-box-footer text-white text-decoration-none d-block mt-3 text-center small">
                                Therapy Session Logs <i class="bi bi-arrow-right-circle-fill ms-1"></i>
                            </a>
                        </div>
                    </div>
                    <div class="col-lg-4 col-md-6 col-12">
                        <div class="small-box text-bg-warning mb-4 p-3 rounded position-relative">
                            <div class="inner text-white">
                                <h3><?php echo (int)$pendingTicketsCount; ?></h3>
                                <p>Pending Support Requests</p>
                            </div>
                            <div class="icon position-absolute end-0 top-0 mt-2 me-3 fs-1 opacity-25 text-white">
                                <i class="bi bi-chat-left-dots-fill"></i>
                            </div>
                            <a href="<?php echo BASE_URL; ?>dashboards/rehab/support_requests.php" class="small-box-footer text-white text-decoration-none d-block mt-3 text-center small">
                                Resolve Tickets <i class="bi bi-arrow-right-circle-fill ms-1"></i>
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Main charts & logs row -->
                <div class="row">
                    <!-- Left: Clinical Telemetry Chart -->
                    <div class="col-lg-6">
                        <div class="card shadow mb-4">
                            <div class="card-header bg-light">
                                <h5 class="card-title mb-0"><i class="bi bi-activity text-primary me-2"></i>Patients by Rehabilitation Phase</h5>
                            </div>
                            <div class="card-body" style="height: 300px; position: relative;">
                                <canvas id="stageChart"></canvas>
                            </div>
                        </div>

                        <!-- Doctor Patient Loads -->
                        <div class="card shadow mb-4">
                            <div class="card-header bg-light">
                                <h5 class="card-title mb-0"><i class="bi bi-shield-heart me-2 text-primary"></i>Doctor Assignment Loads</h5>
                            </div>
                            <div class="card-body p-0">
                                <div class="table-responsive">
                                    <table class="table table-striped align-middle mb-0">
                                        <thead>
                                            <tr>
                                                <th>Doctor / Psychologist Name</th>
                                                <th>Active Assigned Patients</th>
                                                <th>Load Indicator</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($therapistLoads as $load): ?>
                                                <tr>
                                                    <td><strong><?php echo sanitize($load['name']); ?></strong></td>
                                                    <td><span class="badge bg-primary px-3 py-1"><?php echo (int)$load['load_count']; ?> Patients</span></td>
                                                    <td>
                                                        <div class="progress" style="height: 10px; max-width: 150px;">
                                                            <div class="progress-bar bg-warning" role="progressbar" style="width: <?php echo min(100, $load['load_count'] * 20); ?>%"></div>
                                                        </div>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Right: Recent Admissions & Sessions -->
                    <div class="col-lg-6">
                        <!-- Recent Admissions -->
                        <div class="card card-primary card-outline shadow mb-4">
                            <div class="card-header">
                                <h5 class="card-title"><i class="bi bi-person-plus-fill me-2 text-primary"></i>Recent Patient Intakes</h5>
                            </div>
                            <div class="card-body p-0">
                                <div class="table-responsive">
                                    <table class="table table-hover align-middle mb-0">
                                        <thead>
                                            <tr>
                                                <th>Patient</th>
                                                <th>Admission Date</th>
                                                <th>Stage</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($clinicPatients as $p): ?>
                                                <tr>
                                                    <td>
                                                        <strong><?php echo sanitize($p['name']); ?></strong>
                                                        <div class="small text-muted">ID: <?php echo sprintf("IRC-%04d", $p['id']); ?></div>
                                                    </td>
                                                    <td><code><?php echo sanitize($p['admission_date']); ?></code></td>
                                                    <td>
                                                        <?php 
                                                        $status = $p['treatment_status'];
                                                        if ($status === 'Intake') echo '<span class="badge text-bg-warning">Intake</span>';
                                                        elseif ($status === 'Detox') echo '<span class="badge text-bg-danger">Detox</span>';
                                                        elseif ($status === 'Rehab') echo '<span class="badge text-bg-primary">Rehab</span>';
                                                        elseif ($status === 'Outpatient') echo '<span class="badge text-bg-info">Outpatient</span>';
                                                        else echo '<span class="badge text-bg-success">Discharged</span>';
                                                        ?>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>

                        <!-- Recent Session Logs -->
                        <div class="card shadow mb-4">
                            <div class="card-header bg-light">
                                <h5 class="card-title mb-0"><i class="bi bi-journal-medical me-2 text-primary"></i>Latest Session Diagnostics</h5>
                            </div>
                            <div class="card-body p-0">
                                <div class="table-responsive">
                                    <table class="table table-hover align-middle mb-0">
                                        <thead>
                                            <tr>
                                                <th>Patient</th>
                                                <th>Clinician</th>
                                                <th>Type / Date</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($recentSessions as $s): ?>
                                                <tr>
                                                    <td><strong><?php echo sanitize($s['patient_name']); ?></strong></td>
                                                    <td><span class="text-secondary"><?php echo sanitize($s['therapist_name']); ?></span></td>
                                                    <td>
                                                        <span class="badge text-bg-light border"><?php echo sanitize($s['session_type']); ?></span>
                                                        <div class="small text-muted"><?php echo date('M d', strtotime($s['session_date'])); ?></div>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Stage Graph Render Configuration -->
                <script>
                    document.addEventListener("DOMContentLoaded", function () {
                        const ctx = document.getElementById('stageChart').getContext('2d');
                        new Chart(ctx, {
                            type: 'bar',
                            data: {
                                labels: ['Intake', 'Detox', 'Rehab', 'Outpatient', 'Discharged'],
                                datasets: [{
                                    label: 'Patients',
                                    data: [
                                        <?php echo (int)$stageCounts['Intake']; ?>,
                                        <?php echo (int)$stageCounts['Detox']; ?>,
                                        <?php echo (int)$stageCounts['Rehab']; ?>,
                                        <?php echo (int)$stageCounts['Outpatient']; ?>,
                                        <?php echo (int)$stageCounts['Discharged']; ?>
                                    ],
                                    backgroundColor: [
                                        'rgba(255, 193, 7, 0.75)',
                                        'rgba(220, 53, 69, 0.75)',
                                        'rgba(13, 110, 253, 0.75)',
                                        'rgba(13, 202, 240, 0.75)',
                                        'rgba(25, 135, 84, 0.75)'
                                    ],
                                    borderColor: [
                                        '#ffc107',
                                        '#dc3545',
                                        '#0d6efd',
                                        '#0dcaf0',
                                        '#198754'
                                    ],
                                    borderWidth: 1.5,
                                    borderRadius: 6
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
                                        ticks: { stepSize: 1 }
                                    }
                                }
                            }
                        });
                    });
                </script>

            <!-- ================= SUPER ADMIN VIEW ================= -->
            <?php else: ?>
                <!-- Admin Stats boxes -->
                <div class="row animate-fade-in">
                    <div class="col-lg-4 col-md-6 col-12">
                        <div class="small-box text-bg-primary mb-4 p-3 rounded position-relative">
                            <div class="inner">
                                <h3><?php echo (int)$userCount; ?></h3>
                                <p>System Logins</p>
                            </div>
                            <div class="icon position-absolute end-0 top-0 mt-2 me-3 fs-1 opacity-25">
                                <i class="bi bi-people-fill"></i>
                            </div>
                            <a href="<?php echo BASE_URL; ?>dashboards/super_admin/manage_users.php" class="small-box-footer text-white text-decoration-none d-block mt-3 text-center small">
                                User Account Controls <i class="bi bi-arrow-right-circle-fill ms-1"></i>
                            </a>
                        </div>
                    </div>
                    <div class="col-lg-4 col-md-6 col-12">
                        <div class="small-box text-bg-success mb-4 p-3 rounded position-relative">
                            <div class="inner">
                                <h3><?php echo (int)$roleCount; ?></h3>
                                <p>Dynamic Security Roles</p>
                            </div>
                            <div class="icon position-absolute end-0 top-0 mt-2 me-3 fs-1 opacity-25">
                                <i class="bi bi-shield-lock-fill"></i>
                            </div>
                            <a href="<?php echo BASE_URL; ?>dashboards/super_admin/manage_roles.php" class="small-box-footer text-white text-decoration-none d-block mt-3 text-center small">
                                Role Configuration Access <i class="bi bi-arrow-right-circle-fill ms-1"></i>
                            </a>
                        </div>
                    </div>
                    <div class="col-lg-4 col-md-6 col-12">
                        <div class="small-box text-bg-warning mb-4 p-3 rounded position-relative">
                            <div class="inner text-white">
                                <h3><?php echo (int)$pageCount; ?></h3>
                                <p>Dynamic Routing Pages</p>
                            </div>
                            <div class="icon position-absolute end-0 top-0 mt-2 me-3 fs-1 opacity-25 text-white">
                                <i class="bi bi-file-earmark-medical-fill"></i>
                            </div>
                            <a href="<?php echo BASE_URL; ?>dashboards/super_admin/manage_pages.php" class="small-box-footer text-white text-decoration-none d-block mt-3 text-center small">
                                Page Mapping Control <i class="bi bi-arrow-right-circle-fill ms-1"></i>
                            </a>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <!-- Clinic settings branding panel -->
                    <div class="col-lg-6">
                        <div class="card card-primary card-outline shadow mb-4">
                            <div class="card-header bg-transparent border-bottom-0">
                                <h5 class="card-title mb-0"><i class="bi bi-sliders me-2 text-primary"></i>Center Branding Settings</h5>
                            </div>
                            <form action="" method="post">
                                <div class="card-body">
                                    <div class="mb-3">
                                        <label class="form-label text-secondary small">Rehab Center Name</label>
                                        <input type="text" name="system_name" class="form-control" value="<?php echo sanitize($system_name); ?>" required>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label text-secondary small">Footer Attributions Text</label>
                                        <input type="text" name="footer_text" class="form-control" value="<?php echo sanitize($footer_text); ?>" required>
                                    </div>
                                </div>
                                <div class="card-footer text-end">
                                    <button type="submit" name="update_settings" class="btn btn-primary">
                                        <i class="bi bi-check-circle me-1"></i> Update Brand Details
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>

                    <!-- Welcome Box Admin details -->
                    <div class="col-lg-6">
                        <div class="card shadow mb-4">
                            <div class="card-header bg-light">
                                <h5 class="card-title mb-0"><i class="bi bi-gear-wide-connected me-2 text-primary"></i>Telemetry Controls</h5>
                            </div>
                            <div class="card-body">
                                <h5>Authorized Account: <strong><?php echo sanitize($_SESSION['user_name']); ?></strong></h5>
                                <p class="mt-3">
                                    You have full Super Admin capabilities. You can manage roles, allocate checkbox routes, activate/suspend clinicians, and customize site header options.
                                </p>
                                <div class="d-flex gap-2 mt-4">
                                    <a href="<?php echo BASE_URL; ?>dashboards/rehab/manage_patients.php" class="btn btn-outline-primary rounded-pill btn-sm px-3">
                                        <i class="bi bi-person-badge-fill me-1"></i> Intake Registry
                                    </a>
                                    <a href="<?php echo BASE_URL; ?>dashboards/rehab/therapy_sessions.php" class="btn btn-outline-success rounded-pill btn-sm px-3">
                                        <i class="bi bi-calendar-event me-1"></i> Clinical Logs
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

        </div><!-- /.container-fluid -->
    </div>
    <!-- /.content -->
</main>
<!-- /.content-wrapper -->

<?php
require_once __DIR__ . '/includes/footer.php';
?>
