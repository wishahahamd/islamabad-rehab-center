<?php
// Global helper functions
require_once __DIR__ . '/config.php';

/**
 * Sanitizes input string to prevent XSS.
 */
function sanitize($data) {
    if ($data === null) return '';
    return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
}

/**
 * Deterministically returns a Bootstrap 5 badge color class based on the role name.
 */
function getRoleBadgeClass($role_name) {
    $colors = [
        'text-bg-primary',
        'text-bg-secondary',
        'text-bg-success',
        'text-bg-danger',
        'text-bg-warning',
        'text-bg-info',
        'text-bg-dark'
    ];
    // Generate crc32 hash of lowercase role name
    $hash = crc32(strtolower(trim($role_name)));
    // Use modulo to pick index
    $index = abs($hash) % count($colors);
    return $colors[$index];
}

/**
 * Redirect helper
 */
function redirect($path) {
    $url = BASE_URL . ltrim($path, '/');
    if (headers_sent()) {
        echo '<script type="text/javascript">window.location.href="' . $url . '";</script>';
        echo '<noscript><meta http-equiv="refresh" content="0;url=' . $url . '" /></noscript>';
    } else {
        header("Location: " . $url);
    }
    exit;
}

/**
 * Flash messages helper
 */
function set_flash_message($type, $message) {
    $_SESSION['flash_message'] = [
        'type' => $type, // e.g. success, danger, warning, info
        'message' => $message
    ];
}

function display_flash_message() {
    if (isset($_SESSION['flash_message'])) {
        $flash = $_SESSION['flash_message'];
        unset($_SESSION['flash_message']);
        
        $icon = 'bi-info-circle-fill';
        if ($flash['type'] === 'success') $icon = 'bi-check-circle-fill';
        if ($flash['type'] === 'danger') $icon = 'bi-exclamation-triangle-fill';
        if ($flash['type'] === 'warning') $icon = 'bi-exclamation-circle-fill';
        
        echo '<div class="alert alert-' . sanitize($flash['type']) . ' alert-dismissible fade show" role="alert">
                <i class="' . $icon . ' me-2"></i>' . sanitize($flash['message']) . '
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
              </div>';
    }
}
?>
