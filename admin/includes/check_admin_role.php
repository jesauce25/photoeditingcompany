<?php
/**
 * Admin Role Check
 * 
 * This file checks if the current user has Admin privileges.
 * If not, they will be redirected to an access denied page.
 */

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Log access attempt for debugging
error_log("Admin role check accessed. User role: " . ($_SESSION['role'] ?? 'not set'));

// Check if user is logged in
if (!isset($_SESSION['user_logged_in']) || $_SESSION['user_logged_in'] !== true) {
    // User is not logged in, redirect to login page
    error_log("User not logged in, redirecting to login");
    header("Location: ../login.php");
    exit;
}

// Check if user has Admin role
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'Admin') {
    // User does not have Admin role, redirect to access denied page
    error_log("Access denied: User role is " . ($_SESSION['role'] ?? 'not set') . ", Admin role required");
    
    // Set error message
    $_SESSION['error_message'] = "Access denied. You must have Admin privileges to access this page.";
    
    // Redirect to home or access denied page
    header("Location: home.php");
    exit;
}

// If execution reaches here, the user has Admin privileges
error_log("Admin role check passed for user ID: " . ($_SESSION['user_id'] ?? 'unknown')); 