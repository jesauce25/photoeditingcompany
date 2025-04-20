<?php
session_start();
require_once 'db_connection_passthrough.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    $_SESSION['error_message'] = "You must be logged in to update your password.";
    header("Location: ../profile-settings.php");
    exit;
}

$user_id = $_SESSION['user_id'];

// Check if form was submitted
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $_SESSION['error_message'] = "Invalid request method.";
    header("Location: ../profile-settings.php");
    exit;
}

// Get form data
$current_password = isset($_POST['currentPassword']) ? $_POST['currentPassword'] : '';
$new_password = isset($_POST['newPassword']) ? $_POST['newPassword'] : '';
$confirm_password = isset($_POST['confirmPassword']) ? $_POST['confirmPassword'] : '';

// Validate required fields
if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
    $_SESSION['error_message'] = "All password fields are required.";
    header("Location: ../profile-settings.php");
    exit;
}

// Verify new password matches confirmation
if ($new_password !== $confirm_password) {
    $_SESSION['error_message'] = "New password and confirmation do not match.";
    header("Location: ../profile-settings.php");
    exit;
}

// Verify password complexity
if (strlen($new_password) < 8) {
    $_SESSION['error_message'] = "Password must be at least 8 characters long.";
    header("Location: ../profile-settings.php");
    exit;
}

// Get current hashed password from database
$stmt = $conn->prepare("SELECT password FROM tbl_accounts WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows !== 1) {
    $_SESSION['error_message'] = "User account not found.";
    header("Location: ../profile-settings.php");
    exit;
}

$row = $result->fetch_assoc();
$hashed_password = $row['password'];
$stmt->close();

// Verify current password
if (!password_verify($current_password, $hashed_password)) {
    $_SESSION['error_message'] = "Current password is incorrect.";
    header("Location: ../profile-settings.php");
    exit;
}

// Hash the new password
$new_password_hash = password_hash($new_password, PASSWORD_DEFAULT);

// Update password in database
$stmt = $conn->prepare("UPDATE tbl_accounts SET password = ? WHERE user_id = ?");
$stmt->bind_param("si", $new_password_hash, $user_id);
$success = $stmt->execute();
$stmt->close();

if ($success) {
    $_SESSION['success_message'] = "Password updated successfully!";
} else {
    $_SESSION['error_message'] = "Failed to update password. Please try again.";
}

header("Location: ../profile-settings.php");
exit;
?> 