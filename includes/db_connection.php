<?php
/**
 * Database Connection
 * This file establishes a connection to the database
 */

// Database credentials
$host = "localhost";
$username = "root";
$password = "";
$database = "db_projectms";

// Create database connection
$conn = new mysqli($host, $username, $password, $database);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>