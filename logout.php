<?php
/**
 * Logout Script
 * Destroys the session and redirects to the login page
 */

// Start the session
session_start();

// Clear all session variables
$_SESSION = array();

// Destroy the session
session_destroy();

// Redirect to login page
header("Location: index.php");
exit;
?>