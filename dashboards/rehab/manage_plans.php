<?php
// Clinical Treatment Plans Management
require_once __DIR__ . '/../../core/config.php';
require_once __DIR__ . '/../../core/db.php';
require_once __DIR__ . '/../../core/functions.php';

// Enforce login and sidebar
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/sidebar.php';

// Access Control: Only clinical staff and admins
if (!in_array($_SESSION['user_role'], ['super_admin', 'clinical_director', 'doctor', 'counselor'])) {
    echo "<script>window.location.href='" . BASE_URL . "dashboard.php';</script>";
    exit;
}

$selectedPatientId = isset($_GET['patient_id']) ? (int)$_GET['patient_id'] : null;
$editPlan = null;
$selectedPatient = null;

// Fetch all patients for dropdown/sidebar list
$patientsStmt = $pdo->query("SELECT id, name, treatment_status FROM patients ORDER BY name ASC");
$allPatients = $patientsStmt->fetchAll();

if ($selectedPatientId) {
    // Fetch patient details
    $patStmt = $pdo->prepare("SELECT * FROM patients WHERE id = ?");
    $patStmt->execute([$selectedPatientId]);
    $selectedPatient = $patStmt->fetch();

    if ($selectedPatient) {
        // Fetch existing treatment plan
        $planStmt = $pdo->prepare("
            SELECT tp.*, u.name AS clinician_name 
            FROM treatment_plans tp
            LEFT JOIN users u ON tp.created_by = u.id
            WHERE tp.patient_id = ?
            ORDER BY tp.id DESC LIMIT 1
        ");
        $planStmt->execute([$selectedPatientId]);
        $editPlan = $planStmt->fetch();
    }
}

// Handle Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_plan'])) {
    $patient_id = (int)$_POST['patient_id'];
    $detox_plan = sanitize($_POST['detox_plan'] ?? '');
    $therapy_goals = sanitize($_POST['therapy_goals'] ?? '');
    $aftercare_notes = sanitize($_POST['aftercare_notes'] ?? '');
    $clinician_id = $_SESSION['user_id'];

    if ($patient_id <= 0) {
        set_flash_message('danger', 'Invalid patient selected.');
    } else {
        try {
            // Check if plan already exists
            $checkStmt = $pdo->prepare("SELECT id FROM treatment_plans WHERE patient_id = ?");
            $checkStmt->execute([$patient_id]);
            $existingPlanId = $checkStmt->fetchColumn();

            if ($existingPlanId) {
                // Update
                $updateStmt = $pdo->prepare("
                    UPDATE treatment_plans 
                    SET detox_plan = ?, therapy_goals = ?, aftercare_notes = ?, created_by = ? 
                    WHERE id = ?
                ");
                $updateStmt->execute([$detox_plan, $therapy_goals, $aftercare_notes, $clinician_id, $existingPlanId]);
                set_flash_message('success', 'Treatment plan updated successfully.');
            } else {
                // Insert
                $insertStmt = $pdo->prepare("
                    INSERT INTO treatment_plans (patient_id, created_by, detox_plan, therapy_goals, aftercare_notes) 
                    VALUES (?, ?, ?, ?, ?)
                ");
                $insertStmt->execute([$patient_id, $clinician_id, $detox_plan, $therapy_goals, $aftercare_notes]);
                set_flash_message('success', 'Treatment plan created successfully.');
            }
            redirect("dashboards/rehab/manage_plans.php?patient_id=" . $patient_id);
        } catch (Exception $e) {
            set_flash_message('danger', 'Database error: ' . $e->getMessage());
        }
    }
}
?>

<!-- Content Wrapper -->
<main class="app-main animate-fade-in">
    <!-- Header -->
    <div class="app-content-header">
        <div class="container-fluid">
            <div class="row align-items-center">
                <div class="col-sm-6">
                    <h3 class="mb-0"><i class="bi bi-clipboard2-pulse text-primary me-2"></i>Clinical Treatment Plans</h3>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-end">
                        <li class="breadcrumb-item"><a href="<?php echo BASE_URL; ?>dashboard.php">Home</a></li>
                        <li class="breadcrumb-item active">Treatment Plans</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Content -->
    <div class="app-content">
        <div class="container-fluid">
            <?php display_flash_message(); ?>

            <div class="row">
                <!-- Patient Selector Column -->
                <div class="col-lg-4">
                    <div class="card shadow-sm mb-4">
                        <div class="card-header bg-light">
                            <h5 class="card-title mb-0"><i class="bi bi-people-fill me-2"></i>Select Patient Directory</h5>
                        </div>
                        <div class="list-group list-group-flush" style="max-height: 500px; overflow-y: auto;">
                            <?php if (empty($allPatients)): ?>
                                <div class="p-3 text-center text-muted">No admitted patients.</div>
                            <?php else: ?>
                                <?php foreach ($allPatients as $pat): ?>
                                    <?php 
                                    $activeClass = ($selectedPatientId === (int)$pat['id']) ? 'active bg-primary-subtle text-primary border-primary' : '';
                                    ?>
                                    <a href="manage_plans.php?patient_id=<?php echo (int)$pat['id']; ?>" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center <?php echo $activeClass; ?>">
                                        <div>
                                            <strong><?php echo sanitize($pat['name']); ?></strong>
                                            <div class="small text-muted">ID: <?php echo sprintf("IRC-%04d", $pat['id']); ?></div>
                                        </div>
                                        <span class="badge text-bg-light border"><?php echo sanitize($pat['treatment_status']); ?></span>
                                    </a>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Treatment Plan Editor Column -->
                <div class="col-lg-8">
                    <?php if (!$selectedPatient): ?>
                        <div class="card shadow-sm border-dashed">
                            <div class="card-body text-center py-5">
                                <i class="bi bi-file-earmark-medical text-muted display-4 d-block mb-3"></i>
                                <h4>No Patient Selected</h4>
                                <p class="text-secondary">Please select a patient from the directory on the left to view or configure their recovery treatment plan.</p>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="card card-primary card-outline shadow mb-4">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h3 class="card-title mb-0">
                                    <i class="bi bi-file-earmark-text-fill me-2 text-primary"></i>Plan for: <strong><?php echo sanitize($selectedPatient['name']); ?></strong>
                                </h3>
                                <span class="badge bg-light text-dark border">ID: <?php echo sprintf("IRC-%04d", $selectedPatient['id']); ?></span>
                            </div>
                            
                            <form action="" method="post">
                                <input type="hidden" name="patient_id" value="<?php echo (int)$selectedPatient['id']; ?>">
                                
                                <div class="card-body">
                                    <?php if ($editPlan): ?>
                                        <div class="alert alert-info py-2 d-flex align-items-center mb-4">
                                            <i class="bi bi-info-circle-fill me-2 fs-5"></i>
                                            <div>
                                                Last updated by <strong><?php echo sanitize($editPlan['clinician_name']); ?></strong> on <code><?php echo date('M d, Y - h:i A', strtotime($editPlan['created_at'])); ?></code>.
                                            </div>
                                        </div>
                                    <?php endif; ?>

                                    <!-- Section 1: Detoxification Stage Plan -->
                                    <div class="mb-4">
                                        <h5 class="border-bottom pb-2 mb-3 text-dark fw-bold"><span class="badge bg-primary me-2">1</span>Detoxification Plan & Medical Regimen</h5>
                                        <div class="form-group">
                                            <label class="form-label text-secondary small">Detail withdrawal symptom profiling, medication schedules, hydration goals, and physiological metrics to monitor.</label>
                                            <textarea name="detox_plan" class="form-control" rows="4" placeholder="e.g. 7-day vitals monitoring, fluid intake metrics, withdrawal symptom management notes..."><?php echo $editPlan ? sanitize($editPlan['detox_plan']) : ''; ?></textarea>
                                        </div>
                                    </div>

                                    <!-- Section 2: Therapy & Cognitive Goals -->
                                    <div class="mb-4">
                                        <h5 class="border-bottom pb-2 mb-3 text-dark fw-bold"><span class="badge bg-primary me-2">2</span>Psychological & Therapy Goals</h5>
                                        <div class="form-group">
                                            <label class="form-label text-secondary small">Detail rehabilitation goals, cognitive behavioral targets (CBT/DBT sessions), trauma resolution, and coping mechanisms.</label>
                                            <textarea name="therapy_goals" class="form-control" rows="4" placeholder="e.g. 1. Build emotional regulation; 2. Identify substance triggers; 3. Participate in group sharing..."><?php echo $editPlan ? sanitize($editPlan['therapy_goals']) : ''; ?></textarea>
                                        </div>
                                    </div>

                                    <!-- Section 3: Aftercare & Relapse Prevention -->
                                    <div class="mb-4">
                                        <h5 class="border-bottom pb-2 mb-3 text-dark fw-bold"><span class="badge bg-primary me-2">3</span>Aftercare & Relapse Prevention Strategy</h5>
                                        <div class="form-group">
                                            <label class="form-label text-secondary small">Define follow-up consultation intervals, family support check-ins, outpatient counseling scheduling, and relapse safety nets.</label>
                                            <textarea name="aftercare_notes" class="form-control" rows="4" placeholder="e.g. Weekly counseling intervals, family trigger-prevention coaching, monthly peer support meetings..."><?php echo $editPlan ? sanitize($editPlan['aftercare_notes']) : ''; ?></textarea>
                                        </div>
                                    </div>
                                </div>

                                <div class="card-footer d-flex justify-content-between">
                                    <a href="<?php echo BASE_URL; ?>dashboards/rehab/print_report.php?type=patient&id=<?php echo (int)$selectedPatient['id']; ?>" target="_blank" class="btn btn-outline-secondary">
                                        <i class="bi bi-printer me-1"></i> Print / Save Report
                                    </a>
                                    <button type="submit" name="save_plan" class="btn btn-primary px-4">
                                        <i class="bi bi-save me-1"></i> Save Treatment Plan
                                    </button>
                                </div>
                            </form>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</main>

<?php
require_once __DIR__ . '/../../includes/footer.php';
?>
