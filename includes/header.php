<?php
// Header - The Brain
require_once __DIR__ . '/../core/config.php';
require_once __DIR__ . '/../core/db.php';
require_once __DIR__ . '/../core/functions.php';
require_once __DIR__ . '/../core/session.php';

// Enforce login for all pages including this header
checkLoggedIn();

// 1. Fetch system settings
$system_name = "Islamabad Rehab Center";
$system_logo = "https://cdn-icons-png.flaticon.com/512/3063/3063176.png";
$footer_text = "© 2026 Islamabad Rehab Center. All rights reserved.";

try {
    $stmt = $pdo->query("SELECT setting_key, setting_value FROM system_settings");
    while ($row = $stmt->fetch()) {
        if ($row['setting_key'] === 'system_name') $system_name = $row['setting_value'];
        if ($row['setting_key'] === 'system_logo') $system_logo = $row['setting_value'];
        if ($row['setting_key'] === 'footer_text') $footer_text = $row['setting_value'];
    }
    if (!empty($system_logo) && strpos($system_logo, 'http') !== 0) {
        $system_logo = BASE_URL . ltrim($system_logo, '/\\');
    }
} catch (Exception $e) {
    // Fail silently, fall back to defaults
}

// 2. Auto-detect the current script URL relative to the project root
$scriptName = $_SERVER['SCRIPT_NAME'];
$parsedBaseUrl = parse_url(BASE_URL, PHP_URL_PATH);
$relativePageUrl = "";

if ($parsedBaseUrl && strpos($scriptName, $parsedBaseUrl) === 0) {
    $relativePageUrl = substr($scriptName, strlen($parsedBaseUrl));
} else {
    $relativePageUrl = basename($scriptName);
}
$relativePageUrl = ltrim($relativePageUrl, '/');

// 3. Query sys_pages to find the Page Title and Parent Menu name
$currentPage = null;
$pageTitle = "Page";
$currentPageId = null;
$parentMenuName = "";

if (!empty($relativePageUrl)) {
    // Find current page by URL
    $pageStmt = $pdo->prepare("SELECT * FROM sys_pages WHERE page_url = ?");
    $pageStmt->execute([$relativePageUrl]);
    $currentPage = $pageStmt->fetch();
    
    // Fallback: match by suffix
    if (!$currentPage) {
        $filename = basename($scriptName);
        $pageStmt = $pdo->prepare("SELECT * FROM sys_pages WHERE page_url LIKE ?");
        $pageStmt->execute(['%' . $filename]);
        $currentPage = $pageStmt->fetch();
    }
}

if ($currentPage) {
    $pageTitle = $currentPage['page_name'];
    $currentPageId = $currentPage['id'];
    
    // Fetch parent if it exists
    if (!empty($currentPage['parent_id'])) {
        $parentStmt = $pdo->prepare("SELECT * FROM sys_pages WHERE id = ?");
        $parentStmt->execute([$currentPage['parent_id']]);
        $parentPage = $parentStmt->fetch();
        if ($parentPage) {
            $parentMenuName = $parentPage['page_name'];
        }
    }
} else {
    // If not found in sys_pages but it's dashboard.php
    if (basename($scriptName) === 'dashboard.php') {
        $pageTitle = "Dashboard";
    } else {
        $pageTitle = ucwords(str_replace(['_', '.php'], [' ', ''], basename($scriptName)));
    }
}

// 4. Auto-generate Breadcrumbs based on the DB hierarchy
$breadcrumbs = [];
$tempPage = $currentPage;
while ($tempPage) {
    array_unshift($breadcrumbs, [
        'name' => $tempPage['page_name'],
        'url' => ($tempPage['page_url'] === '#') ? '#' : BASE_URL . $tempPage['page_url']
    ]);
    if (!empty($tempPage['parent_id'])) {
        $parentStmt = $pdo->prepare("SELECT * FROM sys_pages WHERE id = ?");
        $parentStmt->execute([$tempPage['parent_id']]);
        $tempPage = $parentStmt->fetch();
    } else {
        $tempPage = null;
    }
}

// Always prepend Home / Dashboard if it's not the dashboard itself
if (basename($scriptName) !== 'dashboard.php') {
    array_unshift($breadcrumbs, [
        'name' => 'Home',
        'url' => BASE_URL . 'dashboard.php'
    ]);
}

// 5. Page access check function
function checkPageAccess($pageId, $role_key, $pdo) {
    // Allow dashboard access to all authenticated users
    if ($pageId === null || $pageId == 1) {
        return true;
    }
    
    // Restrict super_admin directory strictly to super_admin role
    if (strpos($_SERVER['SCRIPT_NAME'], '/super_admin/') !== false) {
        if ($role_key !== 'super_admin') {
            return false;
        }
    }
    
    // Check role_access table
    $accessStmt = $pdo->prepare("SELECT COUNT(*) FROM role_access WHERE role_key = ? AND page_id = ?");
    $accessStmt->execute([$role_key, $pageId]);
    return $accessStmt->fetchColumn() > 0;
}

// Enforce access control
$hasAccess = checkPageAccess($currentPageId, $_SESSION['user_role'], $pdo);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?php echo sanitize($system_name); ?> | <?php echo sanitize($pageTitle); ?></title>
    <!-- Google Fonts: Outfit & Inter -->
    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Outfit:300,400,600,700|Inter:300,400,500,600&display=swap">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <!-- AdminLTE 4 CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/admin-lte@4/dist/css/adminlte.min.css">
    <!-- Custom Theme Stylesheet -->
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/custom.css">
    
    <!-- CSS styles strictly for image constraints -->
    <style>
        .brand-image {
            max-height: 33px;
            width: auto;
        }
        .user-header i {
            font-size: 4.5rem;
        }
    </style>
    <script>
        // Check local storage for theme preference immediately to avoid flash
        const savedTheme = localStorage.getItem('theme') || 'light';
        document.documentElement.setAttribute('data-bs-theme', savedTheme);
    </script>
</head>
<body class="layout-fixed sidebar-expand-lg bg-body-tertiary">
    <div class="app-wrapper">
        <!-- Navbar -->
        <nav class="app-header navbar navbar-expand bg-body">
            <div class="container-fluid">
                <!-- Left navbar links -->
                <ul class="navbar-nav">
                    <li class="nav-item">
                        <a class="nav-link" data-lte-toggle="sidebar" href="#" role="button"><i class="bi bi-list"></i></a>
                    </li>
                    <li class="nav-item d-none d-md-block">
                        <a href="<?php echo BASE_URL; ?>dashboard.php" class="nav-link">Home</a>
                    </li>
                </ul>

                <!-- Right navbar links -->
                <ul class="navbar-nav ms-auto">
                    <!-- Dark Mode Toggle Button -->
                    <li class="nav-item">
                        <button class="nav-link btn btn-link" id="theme-toggle" type="button" onclick="toggleDarkMode()">
                            <i class="bi bi-sun-fill" id="theme-toggle-icon"></i>
                        </button>
                    </li>
                    
                    <!-- User Dropdown Menu -->
                    <li class="nav-item dropdown user-menu">
                        <a href="#" class="nav-link dropdown-toggle" data-bs-toggle="dropdown">
                            <i class="bi bi-person-circle fs-5 me-1"></i>
                            <span class="d-none d-md-inline"><?php echo sanitize($_SESSION['user_name']); ?></span>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-lg dropdown-menu-end">
                            <!-- User image -->
                            <li class="user-header text-bg-primary p-4 text-center">
                                <i class="bi bi-person-circle fs-1"></i>
                                <p>
                                    <?php echo sanitize($_SESSION['user_name']); ?>
                                    <small>Role: <?php echo sanitize($_SESSION['user_role_name'] ?? $_SESSION['user_role']); ?></small>
                                </p>
                            </li>
                            <!-- Menu Footer-->
                            <li class="user-footer d-flex justify-content-between p-3">
                                <span class="badge <?php echo getRoleBadgeClass($_SESSION['user_role_name'] ?? $_SESSION['user_role']); ?> align-self-center py-2 px-3 fs-7">
                                    <?php echo sanitize($_SESSION['user_role_name'] ?? $_SESSION['user_role']); ?>
                                </span>
                                <a href="<?php echo BASE_URL; ?>logout.php" class="btn btn-default btn-flat btn-outline-danger">Sign out</a>
                            </li>
                        </ul>
                    </li>
                </ul>
            </div>
        </nav>
        <!-- /.navbar -->

        <?php if (!$hasAccess): ?>
            <!-- Access Denied Card (Rendered immediately and halts page script execution) -->
            <?php include_once __DIR__ . '/sidebar.php'; ?>
            
            <main class="app-main">
                <div class="app-content-header">
                    <div class="container-fluid">
                        <div class="row">
                            <div class="col-sm-6">
                                <h3 class="mb-0">Access Denied</h3>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="app-content">
                    <div class="container-fluid">
                        <div class="card card-danger card-outline mt-4">
                            <div class="card-header">
                                <h3 class="card-title text-danger">
                                    <i class="bi bi-exclamation-triangle-fill me-2"></i>403 Forbidden
                                </h3>
                            </div>
                            <div class="card-body">
                                <h5 class="text-danger">You do not have permission to access this page.</h5>
                                <p>This resource is restricted by role-based access control. Please contact your system administrator if you believe this is an error.</p>
                                <a href="<?php echo BASE_URL; ?>dashboard.php" class="btn btn-primary mt-3">
                                    <i class="bi bi-arrow-left-short me-1"></i> Back to Dashboard
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
            <?php 
            include_once __DIR__ . '/footer.php'; 
            exit; 
        endif; ?>
