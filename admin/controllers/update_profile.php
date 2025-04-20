<?php
session_start();
require_once 'db_connection_passthrough.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    $_SESSION['error_message'] = "You must be logged in to update your profile.";
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
$first_name = isset($_POST['firstName']) ? trim($_POST['firstName']) : '';
$last_name = isset($_POST['lastName']) ? trim($_POST['lastName']) : '';
$email = isset($_POST['email']) ? trim($_POST['email']) : '';
$username = isset($_POST['username']) ? trim($_POST['username']) : '';
$phone = isset($_POST['phone']) ? trim($_POST['phone']) : '';

// Validate required fields
if (empty($first_name) || empty($last_name) || empty($email) || empty($username)) {
    $_SESSION['error_message'] = "First name, last name, email, and username are required.";
    header("Location: ../profile-settings.php");
    exit;
}

// Validate email format
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $_SESSION['error_message'] = "Invalid email format.";
    header("Location: ../profile-settings.php");
    exit;
}

// Check if email already exists for another user
$stmt = $conn->prepare("SELECT user_id FROM tbl_accounts WHERE email = ? AND user_id != ?");
$stmt->bind_param("si", $email, $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $_SESSION['error_message'] = "Email already exists for another user.";
    header("Location: ../profile-settings.php");
    exit;
}
$stmt->close();

// Check if username already exists for another user
$stmt = $conn->prepare("SELECT user_id FROM tbl_accounts WHERE username = ? AND user_id != ?");
$stmt->bind_param("si", $username, $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $_SESSION['error_message'] = "Username already exists for another user.";
    header("Location: ../profile-settings.php");
    exit;
}
$stmt->close();

// Update user info in tbl_users
$stmt = $conn->prepare("UPDATE tbl_users SET first_name = ?, last_name = ?, phone = ? WHERE user_id = ?");
$stmt->bind_param("sssi", $first_name, $last_name, $phone, $user_id);
$users_updated = $stmt->execute();
$stmt->close();

// Update account info in tbl_accounts
$stmt = $conn->prepare("UPDATE tbl_accounts SET email = ?, username = ? WHERE user_id = ?");
$stmt->bind_param("ssi", $email, $username, $user_id);
$accounts_updated = $stmt->execute();
$stmt->close();

if ($users_updated && $accounts_updated) {
    $_SESSION['success_message'] = "Profile updated successfully!";
    
    // Update session variables
    $_SESSION['first_name'] = $first_name;
    $_SESSION['last_name'] = $last_name;
    $_SESSION['username'] = $username;
    $_SESSION['email'] = $email;
} else {
    $_SESSION['error_message'] = "Failed to update profile. Please try again.";
}

header("Location: ../profile-settings.php");
exit;
?> 