<?php
// Role Manager
require_once __DIR__ . '/../../core/config.php';
require_once __DIR__ . '/../../core/db.php';
require_once __DIR__ . '/../../core/functions.php';

// Enforce authentication via header
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/sidebar.php';

// Process Form Submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_role'])) {
        $role_name = sanitize($_POST['role_name'] ?? '');
        
        if (empty($role_name)) {
            set_flash_message('warning', 'Role name cannot be empty.');
        } else {
            // Generate role key automatically
            // Convert to lowercase, replace non-alphanumeric characters with underscores, trim edges
            $role_key = strtolower(preg_replace('/[^a-zA-Z0-9]+/', '_', trim($role_name)));
            $role_key = trim($role_key, '_');
            
            if (empty($role_key)) {
                set_flash_message('danger', 'Invalid role name format.');
            } else {
                try {
                    // Check if role name or key already exists
                    $checkStmt = $pdo->prepare("SELECT COUNT(*) FROM sys_roles WHERE role_name = ? OR role_key = ?");
                    $checkStmt->execute([$role_name, $role_key]);
                    
                    if ($checkStmt->fetchColumn() > 0) {
                        set_flash_message('warning', 'Role name or key already exists.');
                    } else {
                        $insStmt = $pdo->prepare("INSERT INTO sys_roles (role_name, role_key, is_system_role) VALUES (?, ?, 0)");
                        $insStmt->execute([$role_name, $role_key]);
                        set_flash_message('success', 'Role "' . $role_name . '" (key: ' . $role_key . ') created successfully.');
                        redirect('dashboards/super_admin/manage_roles.php');
                    }
                } catch (Exception $e) {
                    set_flash_message('danger', 'Error creating role: ' . $e->getMessage());
                }
            }
        }
    }

    if (isset($_POST['delete_role'])) {
        $role_id = (int)$_POST['id'];
        
        try {
            // Fetch role details
            $rStmt = $pdo->prepare("SELECT * FROM sys_roles WHERE id = ?");
            $rStmt->execute([$role_id]);
            $role = $rStmt->fetch();
            
            if (!$role) {
                set_flash_message('danger', 'Role not found.');
            } elseif ((int)$role['is_system_role'] === 1) {
                set_flash_message('danger', 'Protected role "' . $role['role_name'] . '" is a system role and cannot be deleted.');
            } else {
                $role_key = $role['role_key'];
                
                $pdo->beginTransaction();
                
                // Check if users exist with this role
                $uStmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE role = ?");
                $uStmt->execute([$role_key]);
                $userCount = (int)$uStmt->fetchColumn();
                
                $migrationNote = "";
                if ($userCount > 0) {
                    // Migrate users to suspended
                    $migStmt = $pdo->prepare("UPDATE users SET role = 'suspended' WHERE role = ?");
                    $migStmt->execute([$role_key]);
                    $migrationNote = " $userCount user(s) migrated to 'suspended' role.";
                }
                
                // Delete role (cascade deletes role_access mapping)
                $delStmt = $pdo->prepare("DELETE FROM sys_roles WHERE id = ?");
                $delStmt->execute([$role_id]);
                
                $pdo->commit();
                
                set_flash_message('success', 'Role "' . $role['role_name'] . '" deleted successfully.' . $migrationNote);
                redirect('dashboards/super_admin/manage_roles.php');
            }
        } catch (Exception $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            set_flash_message('danger', 'Error deleting role: ' . $e->getMessage());
        }
    }
}

// Fetch all roles for display
$rolesStmt = $pdo->query("
    SELECT r.*, 
           (SELECT COUNT(*) FROM users u WHERE u.role = r.role_key) AS user_count
    FROM sys_roles r 
    ORDER BY r.id ASC
");
$roles = $rolesStmt->fetchAll();
?>

<!-- Content Wrapper -->
<main class="app-main">
    <div class="app-content-header">
        <div class="container-fluid">
            <div class="row align-items-center">
                <div class="col-sm-6">
                    <h3 class="mb-0">Role Manager</h3>
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
                <!-- Add Role Form -->
                <div class="col-lg-4">
                    <div class="card card-primary card-outline shadow mb-4">
                        <div class="card-header">
                            <h3 class="card-title"><i class="bi bi-plus-circle-fill me-2"></i>Create Custom Role</h3>
                        </div>
                        <form action="" method="post">
                            <div class="card-body">
                                <div class="mb-3">
                                    <label class="form-label">Role Name <span class="text-danger">*</span></label>
                                    <input type="text" name="role_name" class="form-control" placeholder="e.g. Librarian" required autocomplete="off">
                                    <div class="form-text">The role key will be automatically generated (e.g. "librarian").</div>
                                </div>
                            </div>
                            <div class="card-footer text-end">
                                <button type="submit" name="add_role" class="btn btn-primary">Create Role</button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Roles List -->
                <div class="col-lg-8">
                    <div class="card card-primary card-outline shadow mb-4">
                        <div class="card-header">
                            <h3 class="card-title"><i class="bi bi-shield-lock-fill me-2"></i>System & Custom Roles</h3>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-hover table-striped align-middle mb-0">
                                    <thead>
                                        <tr>
                                            <th style="width: 80px;">ID</th>
                                            <th>Role Name</th>
                                            <th>Role Key</th>
                                            <th>Type</th>
                                            <th>Active Users</th>
                                            <th style="width: 150px; text-align: center;">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($roles as $r): ?>
                                            <tr>
                                                <td><?php echo (int)$r['id']; ?></td>
                                                <td>
                                                    <span class="badge <?php echo getRoleBadgeClass($r['role_name']); ?> py-2 px-3">
                                                        <?php echo sanitize($r['role_name']); ?>
                                                    </span>
                                                </td>
                                                <td><code><?php echo sanitize($r['role_key']); ?></code></td>
                                                <td>
                                                    <?php if ((int)$r['is_system_role'] === 1): ?>
                                                        <span class="badge text-bg-dark border"><i class="bi bi-lock-fill me-1"></i>System</span>
                                                    <?php else: ?>
                                                        <span class="badge text-bg-secondary border"><i class="bi bi-person-fill me-1"></i>Custom</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <span class="badge bg-secondary rounded-pill">
                                                        <?php echo (int)$r['user_count']; ?>
                                                    </span>
                                                </td>
                                                <td class="text-center">
                                                    <?php if ((int)$r['is_system_role'] === 1): ?>
                                                        <button class="btn btn-sm btn-outline-secondary py-0 px-2 disabled" title="System protected roles cannot be deleted">
                                                            <i class="bi bi-lock-fill"></i> Protected
                                                        </button>
                                                    <?php else: ?>
                                                        <form action="" method="post" onsubmit="return confirm('Are you sure you want to delete the role &quot;<?php echo sanitize($r['role_name']); ?>&quot;?<?php if ((int)$r['user_count'] > 0): ?>\n\nWARNING: <?php echo (int)$r['user_count']; ?> user(s) currently assigned to this role will be migrated to the suspended status.<?php endif; ?>');">
                                                            <input type="hidden" name="id" value="<?php echo (int)$r['id']; ?>">
                                                            <button type="submit" name="delete_role" class="btn btn-sm btn-outline-danger py-0 px-2">
                                                                <i class="bi bi-trash me-1"></i> Delete
                                                            </button>
                                                        </form>
                                                    <?php endif; ?>
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
