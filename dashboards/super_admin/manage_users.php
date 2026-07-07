<?php
// User Manager
require_once __DIR__ . '/../../core/config.php';
require_once __DIR__ . '/../../core/db.php';
require_once __DIR__ . '/../../core/functions.php';

// Enforce authentication via header
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/sidebar.php';

// Fetch roles dynamically for selection
$rolesStmt = $pdo->query("SELECT * FROM sys_roles ORDER BY id ASC");
$allRoles = $rolesStmt->fetchAll();

// Handle Actions (Add, Edit, Delete, Toggle Active)
$action = sanitize($_GET['action'] ?? 'list');
$editUser = null;

if ($action === 'edit' && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$id]);
    $editUser = $stmt->fetch();
    
    if (!$editUser) {
        set_flash_message('danger', 'User not found.');
        redirect('dashboards/super_admin/manage_users.php');
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['save_user'])) {
        $id = isset($_POST['id']) ? (int)$_POST['id'] : null;
        $name = sanitize($_POST['name'] ?? '');
        $email = sanitize($_POST['email'] ?? '');
        $role = sanitize($_POST['role'] ?? '');
        $password = $_POST['password'] ?? '';
        $identity_no = sanitize($_POST['identity_no'] ?? '');
        $is_active = isset($_POST['is_active']) ? 1 : 0;

        if (empty($name) || empty($email) || empty($role)) {
            set_flash_message('warning', 'Name, email, and role are required.');
        } else {
            try {
                // Check if email already exists
                $emailCheck = $pdo->prepare("SELECT COUNT(*) FROM users WHERE email = ? AND id != ?");
                $emailCheck->execute([$email, $id ?? 0]);
                
                if ($emailCheck->fetchColumn() > 0) {
                    set_flash_message('warning', 'Email address is already in use by another user.');
                } else {
                    if ($id) {
                        // Update user
                        if (!empty($password)) {
                            // Update including password
                            $hashed = password_hash($password, PASSWORD_DEFAULT);
                            $stmt = $pdo->prepare("
                                UPDATE users 
                                SET name = ?, email = ?, role = ?, password = ?, identity_no = ?, is_active = ? 
                                WHERE id = ?
                            ");
                            $stmt->execute([$name, $email, $role, $hashed, $identity_no, $is_active, $id]);
                        } else {
                            // Update excluding password
                            $stmt = $pdo->prepare("
                                UPDATE users 
                                SET name = ?, email = ?, role = ?, identity_no = ?, is_active = ? 
                                WHERE id = ?
                            ");
                            $stmt->execute([$name, $email, $role, $identity_no, $is_active, $id]);
                        }
                        
                        // If updating current logged in user, refresh session name
                        if ($id === (int)$_SESSION['user_id']) {
                            $_SESSION['user_name'] = $name;
                            $_SESSION['user_email'] = $email;
                        }
                        
                        set_flash_message('success', 'User updated successfully.');
                    } else {
                        // Create user
                        if (empty($password)) {
                            set_flash_message('warning', 'Password is required for new users.');
                        } else {
                            $hashed = password_hash($password, PASSWORD_DEFAULT);
                            $stmt = $pdo->prepare("
                                INSERT INTO users (name, email, role, password, identity_no, is_active) 
                                VALUES (?, ?, ?, ?, ?, ?)
                            ");
                            $stmt->execute([$name, $email, $role, $hashed, $identity_no, $is_active]);
                            set_flash_message('success', 'User created successfully.');
                        }
                    }
                    redirect('dashboards/super_admin/manage_users.php');
                }
            } catch (Exception $e) {
                set_flash_message('danger', 'Error saving user: ' . $e->getMessage());
            }
        }
    }

    if (isset($_POST['delete_user'])) {
        $id = (int)$_POST['id'];
        
        // Prevent deleting oneself
        if ($id === (int)$_SESSION['user_id']) {
            set_flash_message('danger', 'You cannot delete your own account while logged in.');
        } else {
            try {
                $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
                $stmt->execute([$id]);
                set_flash_message('success', 'User deleted successfully.');
                redirect('dashboards/super_admin/manage_users.php');
            } catch (Exception $e) {
                set_flash_message('danger', 'Error deleting user: ' . $e->getMessage());
            }
        }
    }
}

// Fetch users list with role name details
$usersStmt = $pdo->query("
    SELECT u.*, r.role_name 
    FROM users u 
    JOIN sys_roles r ON u.role = r.role_key 
    ORDER BY u.id DESC
");
$users = $usersStmt->fetchAll();
?>

<!-- Content Wrapper -->
<main class="app-main">
    <div class="app-content-header">
        <div class="container-fluid">
            <div class="row align-items-center">
                <div class="col-sm-6">
                    <h3 class="mb-0">User Manager</h3>
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
                <!-- Add / Edit User Form -->
                <div class="col-lg-4">
                    <div class="card card-primary card-outline shadow mb-4">
                        <div class="card-header">
                            <h3 class="card-title">
                                <i class="bi <?php echo $editUser ? 'bi-pencil-square' : 'bi-person-plus-fill'; ?> me-2"></i>
                                <?php echo $editUser ? 'Edit User Profile' : 'Register New User'; ?>
                            </h3>
                        </div>
                        <form action="" method="post">
                            <div class="card-body">
                                <?php if ($editUser): ?>
                                    <input type="hidden" name="id" value="<?php echo (int)$editUser['id']; ?>">
                                <?php endif; ?>

                                <div class="mb-3">
                                    <label class="form-label">Full Name <span class="text-danger">*</span></label>
                                    <input type="text" name="name" class="form-control" placeholder="e.g. Dr. Muhammad Ahmed" value="<?php echo $editUser ? sanitize($editUser['name']) : ''; ?>" required autocomplete="name">
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">Email Address <span class="text-danger">*</span></label>
                                    <input type="email" name="email" class="form-control" placeholder="e.g. ahmed@irc.gov.pk" value="<?php echo $editUser ? sanitize($editUser['email']) : ''; ?>" required autocomplete="email">
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">System Role <span class="text-danger">*</span></label>
                                    <select name="role" class="form-select" required>
                                        <option value="">-- Choose Role --</option>
                                        <?php foreach ($allRoles as $role): ?>
                                            <option value="<?php echo sanitize($role['role_key']); ?>" <?php echo ($editUser && $editUser['role'] === $role['role_key']) ? 'selected' : ''; ?>>
                                                <?php echo sanitize($role['role_name']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">Password <?php echo $editUser ? '' : '<span class="text-danger">*</span>'; ?></label>
                                    <input type="password" name="password" class="form-control" placeholder="<?php echo $editUser ? 'Leave blank to keep current password' : 'Enter login password'; ?>" <?php echo $editUser ? '' : 'required'; ?> autocomplete="new-password">
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">Identity Card / CNIC No</label>
                                    <input type="text" name="identity_no" class="form-control" placeholder="e.g. 37405-1234567-8" value="<?php echo $editUser ? sanitize($editUser['identity_no']) : ''; ?>">
                                </div>

                                <div class="mb-3">
                                    <div class="form-check form-switch mt-4">
                                        <input class="form-check-input" type="checkbox" name="is_active" id="is_active" <?php echo (!$editUser || $editUser['is_active'] == 1) ? 'checked' : ''; ?>>
                                        <label class="form-check-label" for="is_active">Account Status (Active)</label>
                                    </div>
                                </div>
                            </div>
                            <div class="card-footer d-flex justify-content-between">
                                <?php if ($editUser): ?>
                                    <a href="manage_users.php" class="btn btn-secondary">Cancel</a>
                                <?php endif; ?>
                                <button type="submit" name="save_user" class="btn btn-primary ms-auto">Save User</button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Users Directory List -->
                <div class="col-lg-8">
                    <div class="card card-primary card-outline shadow mb-4">
                        <div class="card-header">
                            <h3 class="card-title"><i class="bi bi-people-fill me-2"></i>Active Members & Staff</h3>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-hover table-striped align-middle mb-0">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>Name</th>
                                            <th>Email</th>
                                            <th>Role</th>
                                            <th>Identity Card</th>
                                            <th>Status</th>
                                            <th style="width: 150px; text-align: center;">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($users as $u): ?>
                                            <tr>
                                                <td><?php echo (int)$u['id']; ?></td>
                                                <td><strong><?php echo sanitize($u['name']); ?></strong></td>
                                                <td><code><?php echo sanitize($u['email']); ?></code></td>
                                                <td>
                                                    <!-- Deterministic badge color class from helpers based on role name -->
                                                    <span class="badge <?php echo getRoleBadgeClass($u['role_name']); ?> py-2 px-3">
                                                        <?php echo sanitize($u['role_name']); ?>
                                                    </span>
                                                </td>
                                                <td><?php echo !empty($u['identity_no']) ? sanitize($u['identity_no']) : '<span class="text-muted">-</span>'; ?></td>
                                                <td>
                                                    <?php if ($u['role'] === 'suspended'): ?>
                                                        <span class="badge text-bg-dark border"><i class="bi bi-dash-circle-fill me-1"></i>Suspended</span>
                                                    <?php elseif ($u['is_active'] == 1): ?>
                                                        <span class="badge text-bg-success border"><i class="bi bi-check-circle-fill me-1"></i>Active</span>
                                                    <?php else: ?>
                                                        <span class="badge text-bg-danger border"><i class="bi bi-x-circle-fill me-1"></i>Inactive</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td class="text-center">
                                                    <div class="d-flex gap-2 justify-content-center">
                                                        <a href="manage_users.php?action=edit&id=<?php echo (int)$u['id']; ?>" class="btn btn-sm btn-outline-primary py-0 px-2" title="Edit">
                                                            <i class="bi bi-pencil-square"></i>
                                                        </a>
                                                        <?php if ($u['id'] !== (int)$_SESSION['user_id']): ?>
                                                            <form action="" method="post" onsubmit="return confirm('Are you sure you want to delete this user? This cannot be undone.');">
                                                                <input type="hidden" name="id" value="<?php echo (int)$u['id']; ?>">
                                                                <button type="submit" name="delete_user" class="btn btn-sm btn-outline-danger py-0 px-2" title="Delete">
                                                                    <i class="bi bi-trash"></i>
                                                                </button>
                                                            </form>
                                                        <?php else: ?>
                                                            <button class="btn btn-sm btn-outline-secondary py-0 px-2 disabled" title="You cannot delete yourself">
                                                                <i class="bi bi-person-fill-lock"></i>
                                                            </button>
                                                        <?php endif; ?>
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
