<?php
/**
 * Artist Session Check
 * This file checks if the user is logged in and has the Graphic Artist role
 */

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if the user is logged in
$is_logged_in = isset($_SESSION['user_logged_in']) && $_SESSION['user_logged_in'] === true;

// Check if the user has the correct role (Graphic Artist)
$has_access = false;
if ($is_logged_in && isset($_SESSION['role'])) {
    $has_access = ($_SESSION['role'] === 'Graphic Artist');

    // Log access attempt
    error_log("Artist area access - Username: " . ($_SESSION['username'] ?? 'unknown') .
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

// Set a flag for the artist role (used in templates)
$is_artist = true;
?>