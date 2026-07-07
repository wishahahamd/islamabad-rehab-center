<?php
// Patient & Clinician Support Communication Tickets
require_once __DIR__ . '/../../core/config.php';
require_once __DIR__ . '/../../core/db.php';
require_once __DIR__ . '/../../core/functions.php';

// Enforce login and sidebar
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/sidebar.php';

$userRole = $_SESSION['user_role'];
$userId = $_SESSION['user_id'];
$userName = $_SESSION['user_name'];
$patientId = null;

// If patient, resolve patient ID
if ($userRole === 'patient') {
    try {
        $pStmt = $pdo->prepare("SELECT id FROM patients WHERE name = ?");
        $pStmt->execute([$userName]);
        $patientId = $pStmt->fetchColumn();
    } catch (Exception $e) {
        set_flash_message('danger', 'Error resolving patient credentials.');
    }
}

// Handle Submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 1. Patient submitting support ticket
    if (isset($_POST['submit_ticket']) && $userRole === 'patient') {
        $subject = sanitize($_POST['subject'] ?? '');
        $message = sanitize($_POST['message'] ?? '');

        if (!$patientId) {
            set_flash_message('danger', 'Error: No patient profile associated with your user session.');
        } elseif (empty($subject) || empty($message)) {
            set_flash_message('warning', 'Please fill in all fields.');
        } else {
            try {
                $stmt = $pdo->prepare("
                    INSERT INTO support_requests (patient_id, subject, message, status) 
                    VALUES (?, ?, ?, 'Pending')
                ");
                $stmt->execute([$patientId, $subject, $message]);
                set_flash_message('success', 'Support request submitted successfully. Clinicians will review it shortly.');
                redirect('dashboards/rehab/support_requests.php');
            } catch (Exception $e) {
                set_flash_message('danger', 'Database error: ' . $e->getMessage());
            }
        }
    }

    // 2. Clinician resolving support ticket
    if (isset($_POST['resolve_ticket']) && in_array($userRole, ['super_admin', 'clinical_director', 'doctor', 'counselor'])) {
        $ticketId = (int)$_POST['ticket_id'];
        $resolution_notes = sanitize($_POST['resolution_notes'] ?? '');

        if ($ticketId <= 0 || empty($resolution_notes)) {
            set_flash_message('warning', 'Please provide resolution notes.');
        } else {
            try {
                $stmt = $pdo->prepare("
                    UPDATE support_requests 
                    SET status = 'Resolved', resolution_notes = ? 
                    WHERE id = ?
                ");
                $stmt->execute([$resolution_notes, $ticketId]);
                set_flash_message('success', 'Support ticket marked as Resolved.');
                redirect('dashboards/rehab/support_requests.php');
            } catch (Exception $e) {
                set_flash_message('danger', 'Database error: ' . $e->getMessage());
            }
        }
    }
}

// Fetch Tickets based on role
$tickets = [];
try {
    if ($userRole === 'patient') {
        if ($patientId) {
            $stmt = $pdo->prepare("
                SELECT * FROM support_requests 
                WHERE patient_id = ? 
                ORDER BY id DESC
            ");
            $stmt->execute([$patientId]);
            $tickets = $stmt->fetchAll();
        }
    } else {
        // Clinicians see everything
        $tickets = $pdo->query("
            SELECT sr.*, p.name AS patient_name 
            FROM support_requests sr
            JOIN patients p ON sr.patient_id = p.id
            ORDER BY sr.status ASC, sr.id DESC
        ")->fetchAll();
    }
} catch (Exception $e) {
    set_flash_message('danger', 'Error fetching support tickets: ' . $e->getMessage());
}
?>

<!-- Content Wrapper -->
<main class="app-main animate-fade-in">
    <!-- Header -->
    <div class="app-content-header">
        <div class="container-fluid">
            <div class="row align-items-center">
                <div class="col-sm-6">
                    <h3 class="mb-0"><i class="bi bi-chat-left-text text-primary me-2"></i>Support Tickets & Assistance</h3>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-end">
                        <li class="breadcrumb-item"><a href="<?php echo BASE_URL; ?>dashboard.php">Home</a></li>
                        <li class="breadcrumb-item active">Support Tickets</li>
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
                <!-- Left Hand Side: Submit Ticket (Patient) OR Filters (Staff) -->
                <div class="col-lg-4">
                    <?php if ($userRole === 'patient'): ?>
                        <div class="card card-primary card-outline shadow mb-4">
                            <div class="card-header">
                                <h5 class="card-title mb-0"><i class="bi bi-pencil-square me-2"></i>New Assistance Ticket</h5>
                            </div>
                            <form action="" method="post">
                                <div class="card-body">
                                    <div class="mb-3">
                                        <label class="form-label">Subject / Topic <span class="text-danger">*</span></label>
                                        <input type="text" name="subject" class="form-control" placeholder="e.g. Schedule concern, medication inquiry" required>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Detailed Message <span class="text-danger">*</span></label>
                                        <textarea name="message" class="form-control" rows="5" placeholder="Detail your request or concern here. The clinical staff will reply directly..." required></textarea>
                                    </div>
                                </div>
                                <div class="card-footer text-end">
                                    <button type="submit" name="submit_ticket" class="btn btn-primary px-4">
                                        <i class="bi bi-send-fill me-1"></i> Send Request
                                    </button>
                                </div>
                            </form>
                        </div>
                    <?php else: ?>
                        <!-- Clinician Instructions -->
                        <div class="card shadow-sm mb-4">
                            <div class="card-header bg-light">
                                <h5 class="card-title mb-0"><i class="bi bi-info-circle-fill me-2"></i>Support Management</h5>
                            </div>
                            <div class="card-body">
                                <p class="small text-secondary">
                                    Patients use this portal to request therapy adjustments, clarify scheduling, or signal emotional triggers.
                                </p>
                                <ul>
                                    <li class="small text-secondary mb-2">Review Pending items carefully.</li>
                                    <li class="small text-secondary mb-2">Engage in direct patient counseling to address critical issues.</li>
                                    <li class="small text-secondary">Ensure resolution notes are detailed and constructive before marking resolved.</li>
                                </ul>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Right Hand Side: Ticket Registry List -->
                <div class="col-lg-8">
                    <div class="card shadow-sm mb-4">
                        <div class="card-header bg-light">
                            <h5 class="card-title mb-0"><i class="bi bi-mailbox me-2"></i>Active Ticket History</h5>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-striped align-middle mb-0">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <?php if ($userRole !== 'patient'): ?>
                                                <th>Patient</th>
                                            <?php endif; ?>
                                            <th>Subject / Date</th>
                                            <th>Status</th>
                                            <th>Message</th>
                                            <th>Resolution Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (empty($tickets)): ?>
                                            <tr>
                                                <td colspan="6" class="text-center py-5 text-muted">
                                                    <i class="bi bi-chat-left-dots display-6 d-block mb-3"></i>
                                                    No support tickets logged.
                                                </td>
                                            </tr>
                                        <?php else: ?>
                                            <?php foreach ($tickets as $ticket): ?>
                                                <tr>
                                                    <td><code>#<?php echo sprintf("%03d", $ticket['id']); ?></code></td>
                                                    <?php if ($userRole !== 'patient'): ?>
                                                        <td><strong><?php echo sanitize($ticket['patient_name']); ?></strong></td>
                                                    <?php endif; ?>
                                                    <td>
                                                        <strong><?php echo sanitize($ticket['subject']); ?></strong>
                                                        <div class="small text-muted"><?php echo date('M d, Y', strtotime($ticket['created_at'])); ?></div>
                                                    </td>
                                                    <td>
                                                        <?php if ($ticket['status'] === 'Pending'): ?>
                                                            <span class="badge text-bg-warning">Pending</span>
                                                        <?php else: ?>
                                                            <span class="badge text-bg-success">Resolved</span>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td>
                                                        <div class="small text-wrap" style="max-width: 200px;">
                                                            <?php echo sanitize($ticket['message']); ?>
                                                        </div>
                                                    </td>
                                                    <td>
                                                        <?php if ($ticket['status'] === 'Pending'): ?>
                                                            <?php if (in_array($userRole, ['super_admin', 'clinical_director', 'doctor', 'counselor'])): ?>
                                                                <!-- Resolve form -->
                                                                <form action="" method="post" class="d-flex gap-2">
                                                                    <input type="hidden" name="ticket_id" value="<?php echo (int)$ticket['id']; ?>">
                                                                    <input type="text" name="resolution_notes" class="form-control form-control-sm" placeholder="Resolution notes..." required>
                                                                    <button type="submit" name="resolve_ticket" class="btn btn-sm btn-success py-0 px-2">Resolve</button>
                                                                </form>
                                                            <?php else: ?>
                                                                <span class="text-muted small italic">Awaiting therapist response...</span>
                                                            <?php endif; ?>
                                                        <?php else: ?>
                                                            <div class="p-2 bg-light rounded text-success small border" style="max-width: 250px;">
                                                                <i class="bi bi-check-circle-fill me-1"></i><strong>Response:</strong><br>
                                                                <?php echo sanitize($ticket['resolution_notes']); ?>
                                                            </div>
                                                        <?php endif; ?>
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
