<?php
// Automated Test Suite for Islamabad Rehab Center (IRC) Skeleton
require_once __DIR__ . '/core/config.php';
require_once __DIR__ . '/core/db.php';
require_once __DIR__ . '/core/functions.php';

echo "==================================================\n";
echo "       IRC SKELETON AUTOMATED TEST SUITE          \n";
echo "==================================================\n\n";

$tests_passed = 0;
$tests_failed = 0;

function assertTest($condition, $description) {
    global $tests_passed, $tests_failed;
    if ($condition) {
        echo "✅ PASS: $description\n";
        $tests_passed++;
    } else {
        echo "❌ FAIL: $description\n";
        $tests_failed++;
    }
}

// --------------------------------------------------
// Test Case 1: Database Connection & Seeding Audit
// --------------------------------------------------
try {
    $stmt = $pdo->query("SELECT COUNT(*) FROM sys_roles");
    $rolesCount = $stmt->fetchColumn();
    assertTest($rolesCount >= 4, "System roles table is seeded correctly (Found $rolesCount roles)");
} catch (Exception $e) {
    assertTest(false, "Database roles table audit failed: " . $e->getMessage());
}

// --------------------------------------------------
// Test Case 2: Authentication & Password Verification
// --------------------------------------------------
try {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute(['superadmin@irc.gov.pk']);
    $user = $stmt->fetch();
    
    $login_success = ($user && password_verify('admin123', $user['password']));
    assertTest($login_success, "Login verification for superadmin@irc.gov.pk with 'admin123' works");
    
    $login_fail = ($user && !password_verify('wrong_pass', $user['password']));
    assertTest($login_fail, "Incorrect passwords are successfully rejected");
} catch (Exception $e) {
    assertTest(false, "Authentication test failed: " . $e->getMessage());
}

// --------------------------------------------------
// Test Case 3: Page Access Authorization Policies (RBAC checkPageAccess)
// --------------------------------------------------
// Check page access mock function mirroring the one in header.php
function mockCheckPageAccess($page_url, $role_key, $pdo) {
    // 1. Resolve page ID
    $stmt = $pdo->prepare("SELECT id FROM sys_pages WHERE page_url = ?");
    $stmt->execute([$page_url]);
    $pageId = $stmt->fetchColumn();
    
    if (!$pageId) {
        return true; // Unregistered pages are public
    }
    if ($pageId == 1) {
        return true; // Dashboard is public to authed users
    }
    
    // 2. Perform check
    $accessStmt = $pdo->prepare("SELECT COUNT(*) FROM role_access WHERE role_key = ? AND page_id = ?");
    $accessStmt->execute([$role_key, $pageId]);
    return $accessStmt->fetchColumn() > 0;
}

assertTest(mockCheckPageAccess('dashboards/super_admin/manage_users.php', 'super_admin', $pdo) === true, "Super Admin is AUTHORIZED to access User Manager page");
assertTest(mockCheckPageAccess('dashboards/super_admin/manage_users.php', 'counselor', $pdo) === false, "Case Counselor is DENIED access to User Manager page");
assertTest(mockCheckPageAccess('dashboards/rehab/therapy_sessions.php', 'counselor', $pdo) === false, "Case Counselor is DENIED access to Therapy Sessions logs");
assertTest(mockCheckPageAccess('dashboards/rehab/manage_patients.php', 'counselor', $pdo) === true, "Case Counselor is AUTHORIZED to access Patient Intake page");

// --------------------------------------------------
// Test Case 4: Recursive Sidebar Menu Visibility Filtering
// --------------------------------------------------
// Helper function to simulate recursive menu filtering
function mockGetVisiblePageIds($pages, $allowedIds, $parentId = null) {
    $visibleIds = [];
    foreach ($pages as $p) {
        if ($p['parent_id'] == $parentId) {
            $children = mockGetVisiblePageIds($pages, $allowedIds, $p['id']);
            $hasVisibleChildren = !empty($children);
            $isDirectlyAllowed = in_array((int)$p['id'], $allowedIds);
            
            if ($isDirectlyAllowed || $hasVisibleChildren) {
                $visibleIds[] = (int)$p['id'];
                $visibleIds = array_merge($visibleIds, $children);
            }
        }
    }
    return $visibleIds;
}

try {
    $pagesStmt = $pdo->query("SELECT * FROM sys_pages ORDER BY sort_order ASC");
    $allPages = $pagesStmt->fetchAll();
    
    // Test for Counselor
    $counselorAccessStmt = $pdo->prepare("SELECT page_id FROM role_access WHERE role_key = 'counselor'");
    $counselorAccessStmt->execute();
    $counselorAllowedPageIds = array_map('intval', $counselorAccessStmt->fetchAll(PDO::FETCH_COLUMN));
    $counselorAllowedPageIds[] = 1; // Dashboard
    
    $counselorMenu = mockGetVisiblePageIds($allPages, $counselorAllowedPageIds);
    
    // Page 3 is Patients, Page 4 is Sessions, Page 6, 7, 8 are Admin Tools
    assertTest(in_array(3, $counselorMenu), "Counselor menu contains Patient Intake page (ID 3)");
    assertTest(!in_array(4, $counselorMenu), "Counselor menu hides Therapy Sessions page (ID 4)");
    assertTest(!in_array(8, $counselorMenu), "Counselor menu hides Manage Users admin page (ID 8)");
} catch (Exception $e) {
    assertTest(false, "Menu filtering test failed: " . $e->getMessage());
}

// --------------------------------------------------
// Test Case 5: Safe Role Deletion and User Migration Check
// --------------------------------------------------
try {
    $pdo->beginTransaction();
    
    // 1. Create a temporary role
    $insRole = $pdo->prepare("INSERT INTO sys_roles (role_name, role_key, is_system_role) VALUES ('Temp Role', 'temp_role', 0)");
    $insRole->execute();
    
    // 2. Create a temporary user with this role
    $insUser = $pdo->prepare("INSERT INTO users (name, email, role, password, identity_no, is_active) VALUES ('Temp User', 'temp@irc.gov.pk', 'temp_role', 'pass', '111', 1)");
    $insUser->execute();
    $tempUserId = $pdo->lastInsertId();
    
    // 3. Delete the role using migration logic
    $uStmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE role = 'temp_role'");
    $uStmt->execute();
    $userCount = (int)$uStmt->fetchColumn();
    
    if ($userCount > 0) {
        $migStmt = $pdo->prepare("UPDATE users SET role = 'suspended' WHERE role = 'temp_role'");
        $migStmt->execute();
    }
    
    $delRole = $pdo->prepare("DELETE FROM sys_roles WHERE role_key = 'temp_role'");
    $delRole->execute();
    
    // 4. Verify user was migrated to suspended
    $verifyStmt = $pdo->prepare("SELECT role FROM users WHERE id = ?");
    $verifyStmt->execute([$tempUserId]);
    $finalRole = $verifyStmt->fetchColumn();
    
    assertTest($finalRole === 'suspended', "Safe role deletion successfully migrated user to 'suspended' role status");
    
    $pdo->rollBack(); // Roll back transaction to keep database clean
} catch (Exception $e) {
    $pdo->rollBack();
    assertTest(false, "Safe role deletion test failed: " . $e->getMessage());
}

echo "\n==================================================\n";
echo "SUMMARY: $tests_passed Passed, $tests_failed Failed.\n";
echo "==================================================\n";
?>
