<?php
/**
 * Logout Script
 * Destroys the session and redirects to the login page
 */

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Perform logout directly (no need to use auth.php)
// Unset all session variables
$_SESSION = array();

// Destroy the session
session_destroy();

// Add a success message to the URL
$success_message = urlencode("You have been successfully logged out");

// Redirect to login page with success message
header("Location: login.php?success=" . $success_message);
exit;
?>