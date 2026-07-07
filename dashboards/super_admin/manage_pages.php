<?php
// Page Manager
require_once __DIR__ . '/../../core/config.php';
require_once __DIR__ . '/../../core/db.php';
require_once __DIR__ . '/../../core/functions.php';

// Enforce authentication via header
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/sidebar.php';

// Fetch all roles for checkboxes and display
$rolesStmt = $pdo->query("SELECT * FROM sys_roles ORDER BY id ASC");
$allRoles = $rolesStmt->fetchAll();

// Fetch all pages for parent selection and display listing
$pagesStmt = $pdo->query("SELECT * FROM sys_pages ORDER BY sort_order ASC, page_name ASC");
$allPages = $pagesStmt->fetchAll();

$action = sanitize($_GET['action'] ?? 'list');
$editPage = null;
$editRoles = [];

if ($action === 'edit' && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    $stmt = $pdo->prepare("SELECT * FROM sys_pages WHERE id = ?");
    $stmt->execute([$id]);
    $editPage = $stmt->fetch();
    
    if ($editPage) {
        $rStmt = $pdo->prepare("SELECT role_key FROM role_access WHERE page_id = ?");
        $rStmt->execute([$id]);
        $editRoles = $rStmt->fetchAll(PDO::FETCH_COLUMN);
    } else {
        set_flash_message('danger', 'Page not found.');
        redirect('dashboards/super_admin/manage_pages.php');
    }
}

// Process Form Submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['save_page'])) {
        $id = isset($_POST['id']) ? (int)$_POST['id'] : null;
        $page_name = sanitize($_POST['page_name'] ?? '');
        $page_url = sanitize($_POST['page_url'] ?? '');
        $icon_class = sanitize($_POST['icon_class'] ?? '');
        $parent_id = !empty($_POST['parent_id']) ? (int)$_POST['parent_id'] : null;
        $sort_order = isset($_POST['sort_order']) ? (int)$_POST['sort_order'] : 0;
        $selectedRoles = $_POST['roles'] ?? [];

        if (empty($page_name) || empty($page_url)) {
            set_flash_message('warning', 'Page name and URL are required.');
        } else {
            try {
                $pdo->beginTransaction();
                
                if ($id) {
                    // Update
                    $stmt = $pdo->prepare("UPDATE sys_pages SET parent_id = ?, page_name = ?, page_url = ?, icon_class = ?, sort_order = ? WHERE id = ?");
                    $stmt->execute([$parent_id, $page_name, $page_url, $icon_class, $sort_order, $id]);
                    
                    // Clear and restore role access
                    $delStmt = $pdo->prepare("DELETE FROM role_access WHERE page_id = ?");
                    $delStmt->execute([$id]);
                    
                    $insAccess = $pdo->prepare("INSERT INTO role_access (role_key, page_id) VALUES (?, ?)");
                    foreach ($selectedRoles as $roleKey) {
                        $insAccess->execute([$roleKey, $id]);
                    }
                    set_flash_message('success', 'Page updated successfully.');
                } else {
                    // Insert
                    $stmt = $pdo->prepare("INSERT INTO sys_pages (parent_id, page_name, page_url, icon_class, sort_order) VALUES (?, ?, ?, ?, ?)");
                    $stmt->execute([$parent_id, $page_name, $page_url, $icon_class, $sort_order]);
                    $newId = $pdo->lastInsertId();
                    
                    $insAccess = $pdo->prepare("INSERT INTO role_access (role_key, page_id) VALUES (?, ?)");
                    foreach ($selectedRoles as $roleKey) {
                        $insAccess->execute([$roleKey, $newId]);
                    }
                    set_flash_message('success', 'Page created successfully.');
                }
                
                $pdo->commit();
                redirect('dashboards/super_admin/manage_pages.php');
            } catch (Exception $e) {
                $pdo->rollBack();
                set_flash_message('danger', 'Error saving page: ' . $e->getMessage());
            }
        }
    }

    if (isset($_POST['delete_page'])) {
        $id = (int)$_POST['id'];
        
        // Prevent deleting dynamic pages essential to configuration (Dashboard ID 1, System Management, Pages, Roles, Users)
        if ($id <= 5) {
            set_flash_message('danger', 'System pages are protected and cannot be deleted.');
        } else {
            try {
                $stmt = $pdo->prepare("DELETE FROM sys_pages WHERE id = ?");
                $stmt->execute([$id]);
                set_flash_message('success', 'Page deleted successfully.');
                redirect('dashboards/super_admin/manage_pages.php');
            } catch (Exception $e) {
                set_flash_message('danger', 'Error deleting page: ' . $e->getMessage());
            }
        }
    }
}

// Hierarchical Parent Options helper
function renderParentSelectOptions($pages, $parentId = null, $excludeId = null, $prefix = '', $selectedId = null) {
    foreach ($pages as $p) {
        // Match parent structure
        $match = ($parentId === null && $p['parent_id'] === null) || ($parentId !== null && (int)$p['parent_id'] === (int)$parentId);
        if ($match) {
            // Do not list self or its children as selectable parent
            if ($excludeId !== null && (int)$p['id'] === (int)$excludeId) {
                continue;
            }
            $selected = ($selectedId !== null && (int)$p['id'] === (int)$selectedId) ? 'selected' : '';
            echo '<option value="' . (int)$p['id'] . '" ' . $selected . '>' . sanitize($prefix . $p['page_name']) . '</option>';
            renderParentSelectOptions($pages, $p['id'], $excludeId, $prefix . $p['page_name'] . ' &gt; ', $selectedId);
        }
    }
}
?>

<!-- Content Wrapper -->
<main class="app-main">
    <div class="app-content-header">
        <div class="container-fluid">
            <div class="row align-items-center">
                <div class="col-sm-6">
                    <h3 class="mb-0">Page Manager</h3>
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
                <!-- Add / Edit Page Form -->
                <div class="col-lg-4">
                    <div class="card card-primary card-outline shadow mb-4">
                        <div class="card-header">
                            <h3 class="card-title">
                                <i class="bi <?php echo $editPage ? 'bi-pencil-square' : 'bi-plus-circle-fill'; ?> me-2"></i>
                                <?php echo $editPage ? 'Edit Page' : 'Create New Page'; ?>
                            </h3>
                        </div>
                        <form action="" method="post">
                            <div class="card-body">
                                <?php if ($editPage): ?>
                                    <input type="hidden" name="id" value="<?php echo (int)$editPage['id']; ?>">
                                <?php endif; ?>

                                <div class="mb-3">
                                    <label class="form-label">Page Name <span class="text-danger">*</span></label>
                                    <input type="text" name="page_name" class="form-control" placeholder="e.g. User Management" value="<?php echo $editPage ? sanitize($editPage['page_name']) : ''; ?>" required>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">Page URL <span class="text-danger">*</span></label>
                                    <input type="text" name="page_url" class="form-control" placeholder="e.g. dashboards/super_admin/manage_users.php" value="<?php echo $editPage ? sanitize($editPage['page_url']) : ''; ?>" required>
                                    <div class="form-text">Use '#' for non-clickable parent folders.</div>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">Icon Class</label>
                                    <input type="text" name="icon_class" class="form-control" placeholder="e.g. bi bi-people-fill" value="<?php echo $editPage ? sanitize($editPage['icon_class']) : ''; ?>">
                                    <div class="form-text">Bootstrap Icon classes. e.g. <code>bi bi-gear-fill</code></div>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">Parent Menu Item</label>
                                    <select name="parent_id" class="form-select">
                                        <option value="">-- No Parent (Top Level) --</option>
                                        <?php renderParentSelectOptions($allPages, null, $editPage ? (int)$editPage['id'] : null, '', $editPage ? $editPage['parent_id'] : null); ?>
                                    </select>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">Sort Order</label>
                                    <input type="number" name="sort_order" class="form-control" value="<?php echo $editPage ? (int)$editPage['sort_order'] : 0; ?>">
                                </div>

                                <div class="mb-3">
                                    <label class="form-label d-block">Role Access Permissions</label>
                                    <div class="row">
                                        <?php foreach ($allRoles as $role): ?>
                                            <?php if ($role['role_key'] === 'suspended') continue; ?>
                                            <div class="col-6 mb-2">
                                                <div class="form-check">
                                                    <input class="form-check-input" type="checkbox" name="roles[]" value="<?php echo sanitize($role['role_key']); ?>" id="role_<?php echo sanitize($role['role_key']); ?>"
                                                    <?php 
                                                        if ($editPage && in_array($role['role_key'], $editRoles)) echo 'checked';
                                                        elseif (!$editPage && $role['role_key'] === 'super_admin') echo 'checked'; // Default check super admin
                                                    ?>>
                                                    <label class="form-check-label" for="role_<?php echo sanitize($role['role_key']); ?>">
                                                        <?php echo sanitize($role['role_name']); ?>
                                                    </label>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            </div>
                            <div class="card-footer d-flex justify-content-between">
                                <?php if ($editPage): ?>
                                    <a href="manage_pages.php" class="btn btn-secondary">Cancel</a>
                                <?php endif; ?>
                                <button type="submit" name="save_page" class="btn btn-primary ms-auto">Save Page</button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Page Directory List -->
                <div class="col-lg-8">
                    <div class="card card-primary card-outline shadow mb-4">
                        <div class="card-header">
                            <h3 class="card-title"><i class="bi bi-list-ul me-2"></i>Registered Dynamic Pages</h3>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-hover table-striped align-middle mb-0">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>Page Name</th>
                                            <th>Page URL</th>
                                            <th>Icon</th>
                                            <th>Sort</th>
                                            <th>Roles Allowed</th>
                                            <th style="width: 150px;">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php 
                                        // Simple lookup map for page names
                                        $pageMap = [];
                                        foreach ($allPages as $p) {
                                            $pageMap[$p['id']] = $p['page_name'];
                                        }

                                        // Lookup map for page roles access
                                        $pageRolesMap = [];
                                        try {
                                            $paStmt = $pdo->query("
                                                SELECT ra.page_id, r.role_name, r.role_key 
                                                FROM role_access ra 
                                                JOIN sys_roles r ON ra.role_key = r.role_key
                                            ");
                                            while ($row = $paStmt->fetch()) {
                                                $pageRolesMap[$row['page_id']][] = $row;
                                            }
                                        } catch (Exception $ex) {}

                                        foreach ($allPages as $p): 
                                        ?>
                                            <tr>
                                                <td><?php echo (int)$p['id']; ?></td>
                                                <td>
                                                    <?php if ($p['parent_id']): ?>
                                                        <span class="text-muted fs-8 me-1"><?php echo sanitize($pageMap[$p['parent_id']] ?? ''); ?> &gt;</span>
                                                    <?php endif; ?>
                                                    <strong><?php echo sanitize($p['page_name']); ?></strong>
                                                </td>
                                                <td><code><?php echo sanitize($p['page_url']); ?></code></td>
                                                <td>
                                                    <?php if ($p['icon_class']): ?>
                                                        <i class="<?php echo sanitize($p['icon_class']); ?> me-1 fs-5"></i>
                                                    <?php else: ?>
                                                        <span class="text-muted">-</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td><?php echo (int)$p['sort_order']; ?></td>
                                                <td>
                                                    <?php 
                                                    $allowed = $pageRolesMap[$p['id']] ?? [];
                                                    if ($p['id'] == 1): // Dashboard is hardcoded accessible
                                                    ?>
                                                        <span class="badge text-bg-light border">All Authed Users</span>
                                                    <?php 
                                                    elseif (empty($allowed)): 
                                                    ?>
                                                        <span class="badge text-bg-danger">No Access</span>
                                                    <?php 
                                                    else: 
                                                        foreach ($allowed as $ra): 
                                                    ?>
                                                        <span class="badge <?php echo getRoleBadgeClass($ra['role_name']); ?> me-1">
                                                            <?php echo sanitize($ra['role_name']); ?>
                                                        </span>
                                                    <?php 
                                                        endforeach;
                                                    endif; 
                                                    ?>
                                                </td>
                                                <td>
                                                    <div class="d-flex gap-2">
                                                        <a href="manage_pages.php?action=edit&id=<?php echo (int)$p['id']; ?>" class="btn btn-sm btn-outline-primary py-0 px-2" title="Edit">
                                                            <i class="bi bi-pencil-square"></i>
                                                        </a>
                                                        <?php if ($p['id'] > 5): ?>
                                                            <form action="" method="post" onsubmit="return confirm('Are you sure you want to delete this page? This will clear all permissions assigned to it.');">
                                                                <input type="hidden" name="id" value="<?php echo (int)$p['id']; ?>">
                                                                <button type="submit" name="delete_page" class="btn btn-sm btn-outline-danger py-0 px-2" title="Delete">
                                                                    <i class="bi bi-trash"></i>
                                                                </button>
                                                            </form>
                                                        <?php else: ?>
                                                            <button class="btn btn-sm btn-outline-secondary py-0 px-2 disabled" title="System Protected">
                                                                <i class="bi bi-lock-fill"></i>
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
