<?php
/**
 * Delete Project Image Controller
 * Handles AJAX deletion of project images
 */

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

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
    header('Content-Type: application/json');
    echo json_encode([
        'status' => 'error',
        'message' => 'Invalid request method'
    ]);
    exit;
}

// Get parameters
$image_id = isset($_POST['image_id']) ? intval($_POST['image_id']) : 0;
$project_id = isset($_POST['project_id']) ? intval($_POST['project_id']) : 0;

// Validate parameters
if ($image_id <= 0 || $project_id <= 0) {
    debug_log("Invalid parameters", ["image_id" => $image_id, "project_id" => $project_id]);
    header('Content-Type: application/json');
    echo json_encode([
        'status' => 'error',
        'message' => 'Invalid image or project ID'
    ]);
    exit;
}

try {
    // Begin transaction
    $conn->begin_transaction();

    debug_log("Deleting project image", ["image_id" => $image_id, "project_id" => $project_id]);

    // First, get the image path so we can delete the file
    $sql = "SELECT image_path FROM tbl_project_images WHERE image_id = ? AND project_id = ?";
    $stmt = $conn->prepare($sql);

    if (!$stmt) {
        throw new Exception("Prepare statement error: " . $conn->error);
    }

    $stmt->bind_param("ii", $image_id, $project_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        throw new Exception("Image not found");
    }

    $image = $result->fetch_assoc();
    $image_path = $image['image_path'];

    debug_log("Found image path", ["path" => $image_path]);

    // Delete the database record
    $sql = "DELETE FROM tbl_project_images WHERE image_id = ? AND project_id = ?";
    $stmt = $conn->prepare($sql);

    if (!$stmt) {
        throw new Exception("Prepare delete statement error: " . $conn->error);
    }

    $stmt->bind_param("ii", $image_id, $project_id);

    if (!$stmt->execute()) {
        throw new Exception("Error deleting image record: " . $stmt->error);
    }

    debug_log("Deleted image from database");

    // Update the total_images count in the project
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

    // Attempt to delete the physical file
    if (!empty($image_path)) {
        $full_path = "../../" . $image_path;

        if (file_exists($full_path)) {
            if (unlink($full_path)) {
                debug_log("Deleted physical file", ["path" => $full_path]);
            } else {
                debug_log("Failed to delete physical file", ["path" => $full_path]);
                // Continue anyway since the database record is deleted
            }
        } else {
            debug_log("Physical file not found", ["path" => $full_path]);
        }
    }

    // Commit transaction
    $conn->commit();

    // Return success response
    header('Content-Type: application/json');
    echo json_encode([
        'status' => 'success',
        'message' => 'Image deleted successfully'
    ]);

} catch (Exception $e) {
    // Roll back transaction
    $conn->rollback();

    debug_log("Error deleting image", [
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