<?php
// Check for token
if (!isset($_POST["token"]) || empty($_POST["token"])) {
    header("Location: forgot_password.php");
    exit;
}

$token = $_POST["token"];
$token_hash = hash("sha256", $token);

// Include the database connection
require_once("includes/db_connection.php");

// Look up the token in the database
$sql = "SELECT user_id, token_expires FROM tbl_users WHERE reset_token = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $token_hash);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header("Location: reset-password.php?token=" . urlencode($token) . "&error=" . urlencode("Invalid token. Please request a new password reset link."));
    exit;
}

$user = $result->fetch_assoc();

// Check if the token has expired
if (strtotime($user["token_expires"]) <= time()) {
    // Clear expired token from database
    $update_sql = "UPDATE tbl_users SET reset_token = NULL, token_expires = NULL WHERE user_id = ?";
    $update_stmt = $conn->prepare($update_sql);
    $update_stmt->bind_param("i", $user["user_id"]);
    $update_stmt->execute();

    header("Location: forgot_password.php?error=" . urlencode("Password reset token has expired. Please request a new one."));
    exit;
}

// Validate password
if (!isset($_POST["password"]) || empty($_POST["password"])) {
    header("Location: reset-password.php?token=" . urlencode($token) . "&error=" . urlencode("Password is required."));
    exit;
}

if (!isset($_POST["password_confirmation"]) || empty($_POST["password_confirmation"])) {
    header("Location: reset-password.php?token=" . urlencode($token) . "&error=" . urlencode("Password confirmation is required."));
    exit;
}

$password = $_POST["password"];
$password_confirmation = $_POST["password_confirmation"];

// Check password length
if (strlen($password) < 8) {
    header("Location: reset-password.php?token=" . urlencode($token) . "&error=" . urlencode("Password must be at least 8 characters long."));
    exit;
}

// Check if password contains at least one number
if (!preg_match("/[0-9]/", $password)) {
    header("Location: reset-password.php?token=" . urlencode($token) . "&error=" . urlencode("Password must contain at least one number."));
    exit;
}

// Check if passwords match
if ($password !== $password_confirmation) {
    header("Location: reset-password.php?token=" . urlencode($token) . "&error=" . urlencode("Passwords do not match."));
    exit;
}

// Get the account associated with this user_id
$account_sql = "SELECT account_id FROM tbl_accounts WHERE user_id = ?";
$account_stmt = $conn->prepare($account_sql);
$account_stmt->bind_param("i", $user["user_id"]);
$account_stmt->execute();
$account_result = $account_stmt->get_result();

if ($account_result->num_rows === 0) {
    header("Location: reset-password.php?token=" . urlencode($token) . "&error=" . urlencode("Account not found. Please contact support."));
    exit;
}

$account = $account_result->fetch_assoc();

// Update the user's password in tbl_accounts
$update_sql = "UPDATE tbl_accounts SET password = ? WHERE account_id = ?";
$update_stmt = $conn->prepare($update_sql);
$update_stmt->bind_param("si", $password, $account["account_id"]); // Note: No hashing as per current system
$update_stmt->execute();

// Clear the reset token
$clear_token_sql = "UPDATE tbl_users SET reset_token = NULL, token_expires = NULL WHERE user_id = ?";
$clear_token_stmt = $conn->prepare($clear_token_sql);
$clear_token_stmt->bind_param("i", $user["user_id"]);
$clear_token_stmt->execute();

// Redirect to login page with success message
header("Location: login.php?success=" . urlencode("Your password has been reset successfully. You can now log in with your new password."));
exit;
?>