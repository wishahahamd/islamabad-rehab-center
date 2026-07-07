<?php
// Global Configuration for Islamabad Rehab Center (IRC) Skeleton Project
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Database Configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'irc_db');

// Dynamic Base URL Detection
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https://' : 'http://';
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
$projectRoot = str_replace('\\', '/', dirname(__DIR__));
$currentScript = str_replace('\\', '/', $_SERVER['SCRIPT_FILENAME'] ?? '');
$webRootPath = '/';

if (!empty($currentScript) && strpos($currentScript, $projectRoot) === 0) {
    $scriptRelative = substr($currentScript, strlen($projectRoot));
    $scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
    if (!empty($scriptName) && strlen($scriptName) >= strlen($scriptRelative)) {
        $webRootPath = substr($scriptName, 0, strlen($scriptName) - strlen($scriptRelative));
    }
}
$webRootPath = '/' . trim($webRootPath, '/\\') . '/';
$webRootPath = ($webRootPath === '//') ? '/' : $webRootPath;

define('BASE_URL', $protocol . $host . $webRootPath);
?>
