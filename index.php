<?php
// Start session
session_start();

// Check if already logged in
if (isset($_SESSION['user_logged_in']) && $_SESSION['user_logged_in'] === true) {
    // Get role from session
    $role = $_SESSION['role'] ?? '';

    // Redirect based on role
    if ($role === 'admin' || $role === 'Admin' || $role === 'Project Manager') {
        header("Location: admin/home.php");
        exit;
    } else if ($role === 'art' || $role === 'Graphic Artist') {
        header("Location: artist/home.php");
        exit;
    }
}

// If not logged in or role not recognized, redirect to login page
header("Location: project-status.php");
exit;
