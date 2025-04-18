<?php
/**
 * Database Connection Passthrough
 * This file ensures the database connection is accessible to controller files
 * by using the global $conn variable from the parent script
 */

// This file should be included in controllers when they need the database connection
// that was already established in the parent script (typically via includes/db_connection.php)

// Make sure the connection is available
if (!isset($conn) || !$conn) {
    // Fallback to include the db_connection directly if it's not already available
    // First try the path relative to admin directory
    if (file_exists(__DIR__ . '/../includes/db_connection.php')) {
        require_once __DIR__ . '/../includes/db_connection.php';
    }
    // Then try the path relative to root
    else if (file_exists(__DIR__ . '/../../includes/db_connection.php')) {
        require_once __DIR__ . '/../../includes/db_connection.php';
    }
    // Log error if neither path works
    else {
        error_log("Database connection file not found in either ../includes/ or ../../includes/");
    }
}

// Check if connection is established
if (!isset($conn) || !$conn) {
    error_log("Database connection not established");
}