<?php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Log authentication check for debugging
error_log("Admin auth_check.php - Checking authentication");
error_log("Session user_id: " . ($_SESSION['user_id'] ?? 'not set'));
error_log("Session role: " . ($_SESSION['role'] ?? 'not set'));

// Verify user is logged in
if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {
    error_log("Auth check failed: User not logged in, redirecting to login page");
    // Not logged in, redirect to login page
    header("Location: ../login.php");
    exit;
}

// Verify user role to allow only admin access
$validAdminRoles = ['admin', 'Admin', 'Project Manager'];
if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], $validAdminRoles)) {
    error_log("Auth check failed: User role '" . ($_SESSION['role'] ?? 'none') . "' is not valid for admin area");
    
    // User is logged in but not admin, redirect to appropriate area
    // If user is graphic artist, redirect to artist area
    if ($_SESSION['role'] == 'art' || $_SESSION['role'] == 'Graphic Artist') {
        error_log("Redirecting artist user to artist area");
        header("Location: ../artist/home.php");
        exit;
    }
    
    // For any other unauthorized role
    error_log("Redirecting unauthorized user to login page");
    header("Location: ../login.php");
    exit;
}

error_log("Admin auth_check.php - Authentication successful for role: " . $_SESSION['role']);
?> 