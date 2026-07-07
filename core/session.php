<?php
// Session authentication checks
require_once __DIR__ . '/config.php';

// Ensure session is started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Checks if the user is authenticated. If not, redirects to the login page.
 */
function checkLoggedIn() {
    if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {
        $_SESSION['redirect_url'] = $_SERVER['REQUEST_URI'];
        header("Location: " . BASE_URL . "login.php");
        exit;
    }
}

/**
 * Checks if the user has a specific role or belongs to an authorized list.
 */
function hasRole($roles) {
    if (!isset($_SESSION['user_role'])) {
        return false;
    }
    if (is_array($roles)) {
        return in_array($_SESSION['user_role'], $roles);
    }
    return $_SESSION['user_role'] === $roles;
}
?>
