<?php
session_start();
require_once 'db_connection_passthrough.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    $_SESSION['error_message'] = "You must be logged in to update your profile picture.";
    header("Location: ../profile-settings.php");
    exit;
}

$user_id = $_SESSION['user_id'];

// Check if file was uploaded
if (!isset($_FILES['profileImage']) || $_FILES['profileImage']['error'] !== UPLOAD_ERR_OK) {
    $error_message = "Error uploading file: ";
    
    if (isset($_FILES['profileImage'])) {
        switch ($_FILES['profileImage']['error']) {
            case UPLOAD_ERR_INI_SIZE:
                $error_message .= "File exceeds the maximum size allowed by PHP.";
                break;
            case UPLOAD_ERR_FORM_SIZE:
                $error_message .= "File exceeds the maximum size allowed by the form.";
                break;
            case UPLOAD_ERR_PARTIAL:
                $error_message .= "File was only partially uploaded.";
                break;
            case UPLOAD_ERR_NO_FILE:
                $error_message .= "No file was uploaded.";
                break;
            case UPLOAD_ERR_NO_TMP_DIR:
                $error_message .= "Missing temporary folder.";
                break;
            case UPLOAD_ERR_CANT_WRITE:
                $error_message .= "Failed to write file to disk.";
                break;
            case UPLOAD_ERR_EXTENSION:
                $error_message .= "File upload stopped by extension.";
                break;
            default:
                $error_message .= "Unknown error occurred.";
        }
    } else {
        $error_message .= "No file uploaded.";
    }
    
    error_log($error_message);
    $_SESSION['error_message'] = $error_message;
    header("Location: ../profile-settings.php");
    exit;
}

// Validate file size (2MB max)
if ($_FILES['profileImage']['size'] > 2 * 1024 * 1024) {
    $_SESSION['error_message'] = "File size exceeds 2MB limit.";
    header("Location: ../profile-settings.php");
    exit;
}

// Validate file type
$allowed_types = ['image/jpeg', 'image/jpg', 'image/png'];
$file_info = finfo_open(FILEINFO_MIME_TYPE);
$file_type = finfo_file($file_info, $_FILES['profileImage']['tmp_name']);
finfo_close($file_info);

if (!in_array($file_type, $allowed_types)) {
    $_SESSION['error_message'] = "Invalid file type. Only JPG, JPEG, and PNG formats are allowed.";
    header("Location: ../profile-settings.php");
    exit;
}

// Create directory if it doesn't exist
$upload_dir = '../../uploads/profile_images/';
if (!file_exists($upload_dir)) {
    mkdir($upload_dir, 0755, true);
}

// Generate a unique filename
$file_extension = pathinfo($_FILES['profileImage']['name'], PATHINFO_EXTENSION);
$new_filename = 'profile_' . $user_id . '_' . time() . '.' . $file_extension;
$upload_path = $upload_dir . $new_filename;

// Move the uploaded file
if (!move_uploaded_file($_FILES['profileImage']['tmp_name'], $upload_path)) {
    error_log("Failed to move uploaded file to $upload_path");
    $_SESSION['error_message'] = "Failed to upload profile picture. Please try again.";
    header("Location: ../profile-settings.php");
    exit;
}

// Update the database
$relative_path = 'uploads/profile_images/' . $new_filename;

// First check which table stores the profile image
$tables_to_check = [
    'tbl_accounts' => 'UPDATE tbl_accounts SET profile_img = ? WHERE user_id = ?',
    'tbl_users' => 'UPDATE tbl_users SET profile_img = ? WHERE user_id = ?'
];

$updated = false;

foreach ($tables_to_check as $table => $query) {
    // First check if the column exists
    $check_column_sql = "";
    if ($table === 'tbl_accounts') {
        $check_column_sql = "SHOW COLUMNS FROM tbl_accounts LIKE 'profile_img'";
    } else {
        $check_column_sql = "SHOW COLUMNS FROM tbl_users LIKE 'profile_img'";
    }
    
    $column_result = $conn->query($check_column_sql);
    
    if ($column_result && $column_result->num_rows > 0) {
        $stmt = $conn->prepare($query);
        if ($stmt) {
            $stmt->bind_param("si", $relative_path, $user_id);
            if ($stmt->execute()) {
                if ($stmt->affected_rows > 0) {
                    $updated = true;
                    error_log("Profile image updated in $table");
                }
            } else {
                error_log("Error executing query: " . $stmt->error);
            }
            $stmt->close();
        } else {
            error_log("Error preparing statement: " . $conn->error);
        }
    }
}

if ($updated) {
    $_SESSION['success_message'] = "Profile picture updated successfully!";
} else {
    error_log("Failed to update profile image in database.");
    $_SESSION['error_message'] = "Failed to update profile picture in database.";
}

header("Location: ../profile-settings.php");
exit;
?> 