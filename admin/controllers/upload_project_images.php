<?php
/**
 * Upload Project Images Controller
 * Handles AJAX uploads of project images
 */

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Start output buffering
ob_start();

// For logging
function debug_log($message, $data = null)
{
    error_log(date('Y-m-d H:i:s') . " - " . $message . " - " . ($data !== null ? json_encode($data) : ""));
}

// Ensure database connection is available
if (file_exists(__DIR__ . '/../includes/db_connection.php')) {
    require_once __DIR__ . '/../includes/db_connection.php';
} else if (file_exists(__DIR__ . '/../../includes/db_connection.php')) {
    require_once __DIR__ . '/../../includes/db_connection.php';
} else {
    // Clear any output
    ob_end_clean();
    // Return error response if db connection file not found
    header('Content-Type: application/json');
    echo json_encode([
        'status' => 'error',
        'message' => 'Database connection file not found'
    ]);
    exit;
}

// Check if it's a POST request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    debug_log("Invalid request method", $_SERVER['REQUEST_METHOD']);
    // Clear any output
    ob_end_clean();
    header('Content-Type: application/json');
    echo json_encode([
        'status' => 'error',
        'message' => 'Invalid request method'
    ]);
    exit;
}

// Get project ID
$project_id = isset($_POST['project_id']) ? intval($_POST['project_id']) : 0;

// Validate project ID
if ($project_id <= 0) {
    debug_log("Invalid project ID", $project_id);
    header('Content-Type: application/json');
    echo json_encode([
        'status' => 'error',
        'message' => 'Invalid project ID'
    ]);
    exit;
}

// Check if files were uploaded
if (!isset($_FILES['projectImagesUpload']) || empty($_FILES['projectImagesUpload'])) {
    debug_log("No files uploaded");
    header('Content-Type: application/json');
    echo json_encode([
        'status' => 'error',
        'message' => 'No files were uploaded'
    ]);
    exit;
}

try {
    // Begin transaction
    $conn->begin_transaction();

    // Get the upload directory path
    $upload_dir = "../../uploads/projects/";

    // Create directory if it doesn't exist
    if (!file_exists($upload_dir)) {
        debug_log("Upload directory does not exist, creating", ["path" => $upload_dir]);
        if (!mkdir($upload_dir, 0777, true)) {
            throw new Exception("Failed to create upload directory: " . $upload_dir);
        }
    }

    $uploaded_count = 0;
    $errors = [];

    // Process each uploaded file
    $files = $_FILES['projectImagesUpload'];

    debug_log("Uploading files for project", [
        "project_id" => $project_id,
        "file_count" => count($files['name'])
    ]);

    for ($i = 0; $i < count($files['name']); $i++) {
        // Skip if there was an error with the file
        if ($files['error'][$i] !== UPLOAD_ERR_OK) {
            $errors[] = "Error with file {$files['name'][$i]}: " . $files['error'][$i];
            continue;
        }

        // Generate a unique filename
        $timestamp = time();
        $unique_id = uniqid();
        $file_ext = pathinfo($files['name'][$i], PATHINFO_EXTENSION);
        $file_name = $files['name'][$i]; // Original file name

        // Use the original file name instead of generating a new one
        // Check if file already exists, add a numbered suffix if needed
        $base_name = pathinfo($file_name, PATHINFO_FILENAME);
        $new_filename = $file_name;
        $counter = 1;

        while (file_exists($upload_dir . $new_filename)) {
            $new_filename = $base_name . "($counter)." . $file_ext;
            $counter++;
        }

        $upload_path = $upload_dir . $new_filename;

        debug_log("Processing file", [
            "original_name" => $file_name,
            "new_filename" => $new_filename
        ]);

        // Move the uploaded file to the destination
        if (move_uploaded_file($files['tmp_name'][$i], $upload_path)) {
            // Insert into database
            $sql = "INSERT INTO tbl_project_images (project_id, image_path, file_type, file_size) VALUES (?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);

            if (!$stmt) {
                throw new Exception("Prepare statement error: " . $conn->error);
            }

            $relative_path = $new_filename;
            $file_type = $files['type'][$i];
            $file_size = $files['size'][$i];

            $stmt->bind_param("issi", $project_id, $relative_path, $file_type, $file_size);

            if (!$stmt->execute()) {
                throw new Exception("Error saving image record: " . $stmt->error);
            }

            $uploaded_count++;
            debug_log("File uploaded successfully", [
                "file_name" => $file_name,
                "image_id" => $conn->insert_id
            ]);
        } else {
            $errors[] = "Failed to move uploaded file {$file_name}";
            debug_log("Failed to move uploaded file", ["file_name" => $file_name]);
        }
    }

    // Update the total_images count in the project
    if ($uploaded_count > 0) {
        $updateSql = "UPDATE tbl_projects SET total_images = (SELECT COUNT(*) FROM tbl_project_images WHERE project_id = ?) WHERE project_id = ?";
        $updateStmt = $conn->prepare($updateSql);

        if (!$updateStmt) {
            throw new Exception("Prepare update statement error: " . $conn->error);
        }

        $updateStmt->bind_param("ii", $project_id, $project_id);

        if (!$updateStmt->execute()) {
            throw new Exception("Error updating project total_images: " . $updateStmt->error);
        }

        debug_log("Updated project total_images count");
    }

    // Commit transaction
    $conn->commit();

    // Return success response
    header('Content-Type: application/json');
    echo json_encode([
        'status' => 'success',
        'message' => "{$uploaded_count} files uploaded successfully" . (count($errors) > 0 ? " with " . count($errors) . " errors" : ""),
        'uploaded' => $uploaded_count,
        'errors' => $errors
    ]);

} catch (Exception $e) {
    // Roll back transaction
    $conn->rollback();

    debug_log("Error uploading images", [
        "error" => $e->getMessage(),
        "trace" => $e->getTraceAsString()
    ]);

    // Return error response
    header('Content-Type: application/json');
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
}