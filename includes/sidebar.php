<?php
// Sidebar - The Navigation
require_once __DIR__ . '/../core/config.php';
require_once __DIR__ . '/../core/db.php';
require_once __DIR__ . '/../core/functions.php';

// Fetch user role
$role_key = $_SESSION['user_role'] ?? '';

// 1. Fetch all page IDs the current role has access to
$allowedPageIds = [];
if (!empty($role_key)) {
    try {
        $accessStmt = $pdo->prepare("SELECT page_id FROM role_access WHERE role_key = ?");
        $accessStmt->execute([$role_key]);
        $allowedPageIds = $accessStmt->fetchAll(PDO::FETCH_COLUMN);
        
        // Ensure Dashboard (ID 1) is always accessible for navigation purposes
        if (!in_array(1, $allowedPageIds)) {
            $allowedPageIds[] = 1;
        }
    } catch (Exception $e) {
        // Fallback
    }
}

// Convert elements to integers for strict checks
$allowedPageIds = array_map('intval', $allowedPageIds);

// 2. Fetch all pages sorted by sort_order
$allPages = [];
try {
    $pagesStmt = $pdo->query("SELECT * FROM sys_pages ORDER BY sort_order ASC");
    $allPages = $pagesStmt->fetchAll();
} catch (Exception $e) {
    // Fallback
}

// 3. Resolve active path IDs to open menus
if (!isset($activePathIds)) {
    $activePathIds = [];
    $tempPage = $currentPage ?? null;
    while ($tempPage) {
        $activePathIds[] = (int)$tempPage['id'];
        if (!empty($tempPage['parent_id'])) {
            try {
                $pStmt = $pdo->prepare("SELECT * FROM sys_pages WHERE id = ?");
                $pStmt->execute([$tempPage['parent_id']]);
                $tempPage = $pStmt->fetch();
            } catch (Exception $ex) {
                $tempPage = null;
            }
        } else {
            $tempPage = null;
        }
    }
}

/**
 * Filter pages list into a hierarchical tree based on role permissions.
 */
function getVisibleMenuTree($pages, $allowedIds, $parentId = null) {
    $tree = [];
    foreach ($pages as $p) {
        if (($parentId === null && $p['parent_id'] === null) || ($parentId !== null && (int)$p['parent_id'] === (int)$parentId)) {
            $children = getVisibleMenuTree($pages, $allowedIds, $p['id']);
            $hasVisibleChildren = !empty($children);
            $isDirectlyAllowed = in_array((int)$p['id'], $allowedIds);
            
            // Show parent if it has visible children or if the parent itself is directly allowed
            if ($isDirectlyAllowed || $hasVisibleChildren) {
                $p['children'] = $children;
                $tree[] = $p;
            }
        }
    }
    return $tree;
}

$menuTree = getVisibleMenuTree($allPages, $allowedPageIds);

/**
 * Recursively render tree navigation menu.
 */
function renderSidebarMenu($menuItems, $activePathIds) {
    foreach ($menuItems as $item) {
        $hasChildren = !empty($item['children']);
        $isActive = in_array((int)$item['id'], $activePathIds);
        
        $liClass = 'nav-item';
        if ($hasChildren) {
            if ($isActive) {
                $liClass .= ' menu-open';
            }
        }
        
        $aClass = 'nav-link';
        if ($isActive) {
            $aClass .= ' active';
        }
        
        $url = ($item['page_url'] === '#') ? '#' : BASE_URL . $item['page_url'];
        $icon = !empty($item['icon_class']) ? $item['icon_class'] : 'bi bi-circle';
        
        echo '<li class="' . $liClass . '">';
        echo '  <a href="' . $url . '" class="' . $aClass . '">';
        echo '    <i class="nav-icon ' . sanitize($icon) . '"></i>';
        echo '    <p>';
        echo '      ' . sanitize($item['page_name']);
        if ($hasChildren) {
            echo '    <i class="nav-arrow bi bi-chevron-right"></i>';
        }
        echo '    </p>';
        echo '  </a>';
        
        if ($hasChildren) {
            echo '  <ul class="nav nav-treeview">';
            renderSidebarMenu($item['children'], $activePathIds);
            echo '  </ul>';
        }
        echo '</li>';
    }
}
?>
<!-- Main Sidebar Container -->
<aside class="app-sidebar bg-body-secondary shadow" data-bs-theme="dark">
    <!-- Brand Logo -->
    <div class="sidebar-brand">
        <a href="<?php echo BASE_URL; ?>dashboard.php" class="brand-link navbar-brand">
            <img src="<?php echo sanitize($system_logo); ?>" alt="System Logo" class="brand-image opacity-75 shadow-sm rounded">
            <span class="brand-text fw-light"><?php echo sanitize($system_name); ?></span>
        </a>
    </div>
    
    <!-- Sidebar Menu Wrapper -->
    <div class="sidebar-wrapper">
        <nav class="mt-2">
            <ul class="nav sidebar-menu flex-column" data-lte-toggle="treeview" role="menu" data-accordion="false">
                <?php renderSidebarMenu($menuTree, $activePathIds); ?>
            </ul>
        </nav>
    </div>
</aside>
