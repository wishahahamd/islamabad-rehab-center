<?php
// Therapy Sessions Logger (Rehab Center Specific)
require_once __DIR__ . '/../../core/config.php';
require_once __DIR__ . '/../../core/db.php';
require_once __DIR__ . '/../../core/functions.php';

// Enforce login via header
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/sidebar.php';

// Fetch patients for selection dropdown
$patientsStmt = $pdo->query("SELECT id, name FROM patients ORDER BY name ASC");
$allPatients = $patientsStmt->fetchAll();

// Fetch therapists for selection dropdown
$therapistsStmt = $pdo->query("SELECT id, name FROM users WHERE role IN ('doctor', 'clinical_director') AND is_active = 1 ORDER BY name ASC");
$allTherapists = $therapistsStmt->fetchAll();

// Handle Actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['log_session'])) {
        $patient_id = (int)($_POST['patient_id'] ?? 0);
        $therapist_id = (int)($_POST['therapist_id'] ?? 0);
        $session_date = sanitize($_POST['session_date'] ?? '');
        $session_type = sanitize($_POST['session_type'] ?? 'Individual');
        $notes = sanitize($_POST['notes'] ?? '');

        if (empty($patient_id) || empty($therapist_id) || empty($session_date)) {
            set_flash_message('warning', 'Please fill in all required fields.');
        } else {
            try {
                $stmt = $pdo->prepare("
                    INSERT INTO therapy_sessions (patient_id, therapist_id, session_date, session_type, notes) 
                    VALUES (?, ?, ?, ?, ?)
                ");
                $stmt->execute([$patient_id, $therapist_id, $session_date, $session_type, $notes]);
                set_flash_message('success', 'Therapy session logged successfully.');
                redirect('dashboards/rehab/therapy_sessions.php');
            } catch (Exception $e) {
                set_flash_message('danger', 'Database error: ' . $e->getMessage());
            }
        }
    }

    if (isset($_POST['delete_session'])) {
        $id = (int)$_POST['id'];
        try {
            $stmt = $pdo->prepare("DELETE FROM therapy_sessions WHERE id = ?");
            $stmt->execute([$id]);
            set_flash_message('success', 'Session log deleted.');
            redirect('dashboards/rehab/therapy_sessions.php');
        } catch (Exception $e) {
            set_flash_message('danger', 'Error deleting session: ' . $e->getMessage());
        }
    }
}

// Fetch all therapy sessions for display grid
$sessionsStmt = $pdo->query("
    SELECT ts.*, p.name AS patient_name, u.name AS therapist_name 
    FROM therapy_sessions ts 
    JOIN patients p ON ts.patient_id = p.id 
    JOIN users u ON ts.therapist_id = u.id 
    ORDER BY ts.session_date DESC
");
$sessions = $sessionsStmt->fetchAll();
?>

<!-- Content Wrapper -->
<main class="app-main">
    <div class="app-content-header">
        <div class="container-fluid">
            <div class="row align-items-center">
                <div class="col-sm-6">
                    <h3 class="mb-0">Clinical Session Logs</h3>
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
                <!-- Session Logging Form -->
                <div class="col-lg-4">
                    <div class="card card-primary card-outline shadow mb-4">
                        <div class="card-header">
                            <h3 class="card-title"><i class="bi bi-calendar-check-fill me-2"></i>Log New Session</h3>
                        </div>
                        <form action="" method="post">
                            <div class="card-body">
                                <div class="mb-3">
                                    <label class="form-label">Patient <span class="text-danger">*</span></label>
                                    <select name="patient_id" class="form-select" required>
                                        <option value="">-- Select Patient --</option>
                                        <?php foreach ($allPatients as $p): ?>
                                            <option value="<?php echo (int)$p['id']; ?>">
                                                <?php echo sanitize($p['name']); ?> (ID: <?php echo sprintf("IRC-%04d", $p['id']); ?>)
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">Conducting Clinician <span class="text-danger">*</span></label>
                                    <select name="therapist_id" class="form-select" required>
                                        <option value="">-- Select Therapist --</option>
                                        <?php foreach ($allTherapists as $t): ?>
                                            <option value="<?php echo (int)$t['id']; ?>" <?php echo ($t['id'] === (int)$_SESSION['user_id']) ? 'selected' : ''; ?>>
                                                <?php echo sanitize($t['name']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">Session Date & Time <span class="text-danger">*</span></label>
                                    <input type="datetime-local" name="session_date" class="form-control" value="<?php echo date('Y-m-d\TH:i'); ?>" required>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">Session Type <span class="text-danger">*</span></label>
                                    <select name="session_type" class="form-select" required>
                                        <option value="Individual">Individual Counseling</option>
                                        <option value="Group">Group Therapy</option>
                                        <option value="Family">Family Counseling</option>
                                        <option value="Physiotherapy">Physiotherapy / Medical Check</option>
                                    </select>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">Clinician Assessment Notes</label>
                                    <textarea name="notes" class="form-control" rows="4" placeholder="Log patient reaction, cognitive progress, therapy outcomes..." required></textarea>
                                </div>
                            </div>
                            <div class="card-footer text-end">
                                <button type="submit" name="log_session" class="btn btn-primary">Log Session</button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Session Log List -->
                <div class="col-lg-8">
                    <div class="card card-primary card-outline shadow mb-4">
                        <div class="card-header">
                            <h3 class="card-title"><i class="bi bi-card-text me-2"></i>Therapy Logs & History</h3>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-hover table-striped align-middle mb-0">
                                    <thead>
                                        <tr>
                                            <th style="width: 60px;">ID</th>
                                            <th>Patient</th>
                                            <th>Therapist</th>
                                            <th>Date & Time</th>
                                            <th>Type</th>
                                            <th>Assessment Notes</th>
                                            <th style="width: 100px; text-align: center;">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (empty($sessions)): ?>
                                            <tr>
                                                <td colspan="7" class="text-center py-4 text-muted">
                                                    <i class="bi bi-journal-x fs-2 d-block mb-2"></i>
                                                    No therapy sessions logged yet.
                                                </td>
                                            </tr>
                                        <?php else: ?>
                                            <?php foreach ($sessions as $s): ?>
                                                <tr>
                                                    <td><?php echo (int)$s['id']; ?></td>
                                                    <td><strong><?php echo sanitize($s['patient_name']); ?></strong></td>
                                                    <td><span class="text-secondary"><?php echo sanitize($s['therapist_name']); ?></span></td>
                                                    <td><code><?php echo date('M d, Y - h:i A', strtotime($s['session_date'])); ?></code></td>
                                                    <td>
                                                        <?php 
                                                        $type = $s['session_type'];
                                                        if ($type === 'Individual') echo '<span class="badge text-bg-primary">Individual</span>';
                                                        elseif ($type === 'Group') echo '<span class="badge text-bg-success">Group</span>';
                                                        elseif ($type === 'Family') echo '<span class="badge text-bg-warning">Family</span>';
                                                        else echo '<span class="badge text-bg-info">Physiotherapy</span>';
                                                        ?>
                                                    </td>
                                                    <td>
                                                        <div class="text-wrap small text-break" style="max-width: 250px;">
                                                            <?php echo nl2br(sanitize($s['notes'])); ?>
                                                        </div>
                                                    </td>
                                                    <td class="text-center">
                                                        <form action="" method="post" onsubmit="return confirm('Are you sure you want to delete this session log?');">
                                                            <input type="hidden" name="id" value="<?php echo (int)$s['id']; ?>">
                                                            <button type="submit" name="delete_session" class="btn btn-sm btn-outline-danger py-0 px-2" title="Delete Session Log">
                                                                <i class="bi bi-trash"></i>
                                                            </button>
                                                        </form>
                                                    </td>
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

        </div>
    </div>
</main>

<?php
require_once __DIR__ . '/../../includes/footer.php';
?>
