<?php
// Patient Management (Rehab Center Specific)
require_once __DIR__ . '/../../core/config.php';
require_once __DIR__ . '/../../core/db.php';
require_once __DIR__ . '/../../core/functions.php';

// Enforce login via header
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/sidebar.php';

// Fetch therapists (Doctors/Directors) for dropdown
$therapistsStmt = $pdo->query("SELECT id, name FROM users WHERE role IN ('doctor', 'clinical_director') AND is_active = 1 ORDER BY name ASC");
$therapists = $therapistsStmt->fetchAll();

// Handle Actions (Add, Edit, Delete)
$action = sanitize($_GET['action'] ?? 'list');
$editPatient = null;

if ($action === 'edit' && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    $stmt = $pdo->prepare("SELECT * FROM patients WHERE id = ?");
    $stmt->execute([$id]);
    $editPatient = $stmt->fetch();
    
    if (!$editPatient) {
        set_flash_message('danger', 'Patient record not found.');
        redirect('dashboards/rehab/manage_patients.php');
    }
}

// Process Post Forms
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['save_patient'])) {
        $id = isset($_POST['id']) ? (int)$_POST['id'] : null;
        $name = sanitize($_POST['name'] ?? '');
        $age = (int)($_POST['age'] ?? 0);
        $gender = sanitize($_POST['gender'] ?? '');
        $admission_date = sanitize($_POST['admission_date'] ?? '');
        $treatment_status = sanitize($_POST['treatment_status'] ?? 'Intake');
        $assigned_therapist_id = !empty($_POST['assigned_therapist_id']) ? (int)$_POST['assigned_therapist_id'] : null;
        $medical_history = sanitize($_POST['medical_history'] ?? '');

        if (empty($name) || empty($age) || empty($gender) || empty($admission_date)) {
            set_flash_message('warning', 'Please fill in all required fields.');
        } else {
            try {
                if ($id) {
                    // Update
                    $stmt = $pdo->prepare("
                        UPDATE patients 
                        SET name = ?, age = ?, gender = ?, admission_date = ?, treatment_status = ?, assigned_therapist_id = ?, medical_history = ? 
                        WHERE id = ?
                    ");
                    $stmt->execute([$name, $age, $gender, $admission_date, $treatment_status, $assigned_therapist_id, $medical_history, $id]);
                    set_flash_message('success', 'Patient profile updated successfully.');
                } else {
                    // Insert
                    $stmt = $pdo->prepare("
                        INSERT INTO patients (name, age, gender, admission_date, treatment_status, assigned_therapist_id, medical_history) 
                        VALUES (?, ?, ?, ?, ?, ?, ?)
                    ");
                    $stmt->execute([$name, $age, $gender, $admission_date, $treatment_status, $assigned_therapist_id, $medical_history]);
                    set_flash_message('success', 'Patient intake completed successfully.');
                }
                redirect('dashboards/rehab/manage_patients.php');
            } catch (Exception $e) {
                set_flash_message('danger', 'Database error: ' . $e->getMessage());
            }
        }
    }

    if (isset($_POST['delete_patient'])) {
        $id = (int)$_POST['id'];
        try {
            $stmt = $pdo->prepare("DELETE FROM patients WHERE id = ?");
            $stmt->execute([$id]);
            set_flash_message('success', 'Patient record deleted.');
            redirect('dashboards/rehab/manage_patients.php');
        } catch (Exception $e) {
            set_flash_message('danger', 'Error deleting patient record: ' . $e->getMessage());
        }
    }
}

// Fetch all patients for listing
$patientsStmt = $pdo->query("
    SELECT p.*, u.name AS therapist_name 
    FROM patients p 
    LEFT JOIN users u ON p.assigned_therapist_id = u.id 
    ORDER BY p.id DESC
");
$patients = $patientsStmt->fetchAll();
?>

<!-- Content Wrapper -->
<main class="app-main">
    <div class="app-content-header">
        <div class="container-fluid">
            <div class="row align-items-center">
                <div class="col-sm-6">
                    <h3 class="mb-0">Patient Directory & Intake</h3>
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

    <!-- Main Content -->
    <div class="app-content">
        <div class="container-fluid">
            
            <?php display_flash_message(); ?>

            <div class="row">
                <!-- Add / Edit Patient Intake Form -->
                <div class="col-lg-4">
                    <div class="card card-primary card-outline shadow mb-4">
                        <div class="card-header">
                            <h3 class="card-title">
                                <i class="bi <?php echo $editPatient ? 'bi-pencil-square' : 'bi-person-plus-fill'; ?> me-2"></i>
                                <?php echo $editPatient ? 'Update Patient Info' : 'New Patient Intake'; ?>
                            </h3>
                        </div>
                        <form action="" method="post">
                            <div class="card-body">
                                <?php if ($editPatient): ?>
                                    <input type="hidden" name="id" value="<?php echo (int)$editPatient['id']; ?>">
                                <?php endif; ?>

                                <div class="mb-3">
                                    <label class="form-label">Patient Full Name <span class="text-danger">*</span></label>
                                    <input type="text" name="name" class="form-control" placeholder="e.g. Ali Raza" value="<?php echo $editPatient ? sanitize($editPatient['name']) : ''; ?>" required>
                                </div>

                                <div class="row">
                                    <div class="col-6 mb-3">
                                        <label class="form-label">Age <span class="text-danger">*</span></label>
                                        <input type="number" name="age" class="form-control" placeholder="Age" min="1" max="120" value="<?php echo $editPatient ? (int)$editPatient['age'] : ''; ?>" required>
                                    </div>
                                    <div class="col-6 mb-3">
                                        <label class="form-label">Gender <span class="text-danger">*</span></label>
                                        <select name="gender" class="form-select" required>
                                            <option value="">Select</option>
                                            <option value="Male" <?php echo ($editPatient && $editPatient['gender'] === 'Male') ? 'selected' : ''; ?>>Male</option>
                                            <option value="Female" <?php echo ($editPatient && $editPatient['gender'] === 'Female') ? 'selected' : ''; ?>>Female</option>
                                            <option value="Other" <?php echo ($editPatient && $editPatient['gender'] === 'Other') ? 'selected' : ''; ?>>Other</option>
                                        </select>
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">Admission Date <span class="text-danger">*</span></label>
                                    <input type="date" name="admission_date" class="form-control" value="<?php echo $editPatient ? sanitize($editPatient['admission_date']) : date('Y-m-d'); ?>" required>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">Treatment Stage <span class="text-danger">*</span></label>
                                    <select name="treatment_status" class="form-select" required>
                                        <option value="Intake" <?php echo ($editPatient && $editPatient['treatment_status'] === 'Intake') ? 'selected' : ''; ?>>Intake / Screening</option>
                                        <option value="Detox" <?php echo ($editPatient && $editPatient['treatment_status'] === 'Detox') ? 'selected' : ''; ?>>Detoxification</option>
                                        <option value="Rehab" <?php echo ($editPatient && $editPatient['treatment_status'] === 'Rehab') ? 'selected' : ''; ?>>Residential Rehabilitation</option>
                                        <option value="Outpatient" <?php echo ($editPatient && $editPatient['treatment_status'] === 'Outpatient') ? 'selected' : ''; ?>>Outpatient Counseling</option>
                                        <option value="Discharged" <?php echo ($editPatient && $editPatient['treatment_status'] === 'Discharged') ? 'selected' : ''; ?>>Discharged</option>
                                    </select>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">Assigned Medical Therapist</label>
                                    <select name="assigned_therapist_id" class="form-select">
                                        <option value="">-- Unassigned --</option>
                                        <?php foreach ($therapists as $t): ?>
                                            <option value="<?php echo (int)$t['id']; ?>" <?php echo ($editPatient && (int)$editPatient['assigned_therapist_id'] === (int)$t['id']) ? 'selected' : ''; ?>>
                                                <?php echo sanitize($t['name']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">Clinical History & Diagnosis</label>
                                    <textarea name="medical_history" class="form-control" rows="3" placeholder="Symptom profiles, rehabilitation needs..."><?php echo $editPatient ? sanitize($editPatient['medical_history']) : ''; ?></textarea>
                                </div>
                            </div>
                            <div class="card-footer d-flex justify-content-between">
                                <?php if ($editPatient): ?>
                                    <a href="manage_patients.php" class="btn btn-secondary">Cancel</a>
                                <?php endif; ?>
                                <button type="submit" name="save_patient" class="btn btn-primary ms-auto">Save Patient</button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Patients Directory Grid -->
                <div class="col-lg-8">
                    <div class="card card-primary card-outline shadow mb-4">
                        <div class="card-header">
                            <h3 class="card-title"><i class="bi bi-people-fill me-2"></i>Active Admitted Patients</h3>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-hover table-striped align-middle mb-0">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>Patient Name</th>
                                            <th>Details</th>
                                            <th>Admission</th>
                                            <th>Treatment Status</th>
                                            <th>Assigned Clinician</th>
                                            <th style="width: 150px; text-align: center;">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($patients as $p): ?>
                                            <tr>
                                                <td><?php echo (int)$p['id']; ?></td>
                                                <td>
                                                    <strong><?php echo sanitize($p['name']); ?></strong>
                                                    <div class="text-muted small">ID: <?php echo sprintf("IRC-%04d", $p['id']); ?></div>
                                                </td>
                                                <td>
                                                    <span class="badge bg-light text-dark border me-1"><?php echo sanitize($p['gender']); ?></span>
                                                    <span class="badge bg-light text-dark border"><?php echo (int)$p['age']; ?> Yrs</span>
                                                </td>
                                                <td><code><?php echo sanitize($p['admission_date']); ?></code></td>
                                                <td>
                                                    <?php 
                                                    $status = $p['treatment_status'];
                                                    if ($status === 'Intake') echo '<span class="badge text-bg-warning border"><i class="bi bi-file-earmark-person me-1"></i>Intake</span>';
                                                    elseif ($status === 'Detox') echo '<span class="badge text-bg-danger border"><i class="bi bi-prescription2 me-1"></i>Detox</span>';
                                                    elseif ($status === 'Rehab') echo '<span class="badge text-bg-primary border"><i class="bi bi-house-door-fill me-1"></i>Rehab</span>';
                                                    elseif ($status === 'Outpatient') echo '<span class="badge text-bg-info border"><i class="bi bi-chat-dots-fill me-1"></i>Outpatient</span>';
                                                    else echo '<span class="badge text-bg-success border"><i class="bi bi-check-lg me-1"></i>Discharged</span>';
                                                    ?>
                                                </td>
                                                <td>
                                                    <?php if ($p['therapist_name']): ?>
                                                        <span class="text-body"><i class="bi bi-person-fill text-muted me-1"></i><?php echo sanitize($p['therapist_name']); ?></span>
                                                    <?php else: ?>
                                                        <span class="text-warning"><i class="bi bi-exclamation-triangle me-1"></i>Not Assigned</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td class="text-center">
                                                    <div class="d-flex gap-2 justify-content-center">
                                                        <a href="<?php echo BASE_URL; ?>dashboards/rehab/print_report.php?type=patient&id=<?php echo (int)$p['id']; ?>" target="_blank" class="btn btn-sm btn-outline-success py-0 px-2" title="Print Recovery Report">
                                                            <i class="bi bi-printer"></i>
                                                        </a>
                                                        <a href="manage_patients.php?action=edit&id=<?php echo (int)$p['id']; ?>" class="btn btn-sm btn-outline-primary py-0 px-2" title="Edit Profile">
                                                            <i class="bi bi-pencil-square"></i>
                                                        </a>
                                                        <form action="" method="post" onsubmit="return confirm('Are you sure you want to delete this patient record? All therapy logs for this patient will be deleted.');">
                                                            <input type="hidden" name="id" value="<?php echo (int)$p['id']; ?>">
                                                            <button type="submit" name="delete_patient" class="btn btn-sm btn-outline-danger py-0 px-2" title="Delete Profile">
                                                                <i class="bi bi-trash"></i>
                                                            </button>
                                                        </form>
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
            </div>

        </div>
    </div>
</main>

<?php
require_once __DIR__ . '/../../includes/footer.php';
?>
