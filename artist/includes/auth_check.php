<?php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Log authentication check for debugging
error_log("Artist auth_check.php - Checking authentication");
error_log("Session user_id: " . ($_SESSION['user_id'] ?? 'not set'));
error_log("Session role: " . ($_SESSION['role'] ?? 'not set'));

// Verify user is logged in
if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {
    error_log("Auth check failed: User not logged in, redirecting to login page");
    // Not logged in, redirect to login page
    header("Location: ../login.php");
    exit;
}

// Verify user role to allow only artist access
$validArtistRoles = ['art', 'Graphic Artist'];
if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], $validArtistRoles)) {
    error_log("Auth check failed: User role '" . ($_SESSION['role'] ?? 'none') . "' is not valid for artist area");

    // User is logged     in but not artist, redirect to appropriate area
    // If user is admin, redirect to admin area
    if ($_SESSION['role'] == 'admin' || $_SESSION['role'] == 'Admin' || $_SESSION['role'] == 'Project Manager') {
        error_log("Redirecting admin user to admin area");
        header("Location: ../admin/dashboard.php");
        exit;
    }

    // For any other unauthori    zed role
    error_log("Redirecting unauthorized user to login page");
    header("Location: ../login.php");
    exit;
}

error_log("Artist auth_check.php - Authentication successful for role: " . $_SESSION['role']);
?>