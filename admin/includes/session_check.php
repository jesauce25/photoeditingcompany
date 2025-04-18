<?php
/**
 * Admin Session Check
 * This file checks if the user is logged in and has appropriate privileges (Admin or Project Manager)
 */

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if the user is logged in
$is_logged_in = isset($_SESSION['user_logged_in']) && $_SESSION['user_logged_in'] === true;

// Check if the user has the correct role (Admin or Project Manager)
$has_access = false;
if ($is_logged_in && isset($_SESSION['role'])) {
    $has_access = ($_SESSION['role'] === 'Admin' || $_SESSION['role'] === 'Project Manager');

    // Log access attempt
    error_log("Admin area access - Username: " . ($_SESSION['username'] ?? 'unknown') .
        ", Role: " . ($_SESSION['role'] ?? 'unknown') .
        ", Access: " . ($has_access ? 'granted' : 'denied'));
}

// If the user is not logged in or doesn't have privileges, redirect to login page
if (!$is_logged_in || !$has_access) {
    // Destroy any existing session
    session_destroy();

    // Redirect to login page
    header("Location: ../login.php");
    exit;
}

// Set flags for template use
$is_admin = $_SESSION['role'] === 'Admin';
$is_project_manager = $_SESSION['role'] === 'Project Manager';

// Check for access denied messages
if (isset($_SESSION['access_denied']) && $_SESSION['access_denied'] === true) {
    $access_denied = true;
    $access_message = $_SESSION['access_message'] ?? 'Access denied. You do not have permission to access this page.';

    // Clear the session messages
    unset($_SESSION['access_denied']);
    unset($_SESSION['access_message']);
} else {
    $access_denied = false;
    $access_message = '';
}
?>