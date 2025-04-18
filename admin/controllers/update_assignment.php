<?php
/**
 * Update Assignment Controller
 * This file handles AJAX requests to update project assignments
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

// Get parameters from POST
$assignment_id = isset($_POST['assignment_id']) ? intval($_POST['assignment_id']) : 0;
$action = isset($_POST['action']) ? $_POST['action'] : '';

// Validate assignment_id
if ($assignment_id <= 0) {
    debug_log("Invalid assignment ID", $assignment_id);
    header('Content-Type: application/json');
    echo json_encode([
        'status' => 'error',
        'message' => 'Invalid assignment ID'
    ]);
    exit;
}

try {
    debug_log("Processing assignment action", ["action" => $action, "assignment_id" => $assignment_id]);

    // Begin transaction
    $conn->begin_transaction();

    // Process based on action
    switch ($action) {
        case 'remove':
            debug_log("Removing assignment", $assignment_id);

            // First get the project ID for this assignment (needed for logging)
            $getProjectSql = "SELECT project_id FROM tbl_project_assignments WHERE assignment_id = ?";
            $getProjectStmt = $conn->prepare($getProjectSql);
            if (!$getProjectStmt) {
                throw new Exception("Prepare project ID statement error: " . $conn->error);
            }

            $getProjectStmt->bind_param("i", $assignment_id);
            $getProjectStmt->execute();
            $getProjectResult = $getProjectStmt->get_result();

            if ($getProjectResult->num_rows === 0) {
                throw new Exception("Assignment not found");
            }

            $projectRow = $getProjectResult->fetch_assoc();
            $project_id = $projectRow['project_id'];
            debug_log("Found project ID for assignment", ["assignment_id" => $assignment_id, "project_id" => $project_id]);

            // First update any assigned images to remove the assignment
            $clearImagesSql = "UPDATE tbl_project_images SET assignment_id = NULL WHERE assignment_id = ?";
            $clearImagesStmt = $conn->prepare($clearImagesSql);
            if (!$clearImagesStmt) {
                throw new Exception("Prepare clear images statement error: " . $conn->error);
            }

            $clearImagesStmt->bind_param("i", $assignment_id);
            if (!$clearImagesStmt->execute()) {
                throw new Exception("Error clearing image assignments: " . $clearImagesStmt->error);
            }

            $affectedImages = $clearImagesStmt->affected_rows;
            debug_log("Cleared image assignments", ["affected_images" => $affectedImages]);

            // Then delete the assignment
            $deleteAssignmentSql = "DELETE FROM tbl_project_assignments WHERE assignment_id = ?";
            $deleteAssignmentStmt = $conn->prepare($deleteAssignmentSql);
            if (!$deleteAssignmentStmt) {
                throw new Exception("Prepare delete assignment statement error: " . $conn->error);
            }

            $deleteAssignmentStmt->bind_param("i", $assignment_id);
            if (!$deleteAssignmentStmt->execute()) {
                throw new Exception("Error deleting assignment: " . $deleteAssignmentStmt->error);
            }

            debug_log("Assignment deleted", ["affected_rows" => $deleteAssignmentStmt->affected_rows]);

            // Commit the transaction
            $conn->commit();

            // Return success response
            header('Content-Type: application/json');
            echo json_encode([
                'status' => 'success',
                'message' => 'Assignment removed successfully',
                'affected_images' => $affectedImages
            ]);
            break;

        default:
            throw new Exception("Invalid action specified");
    }
} catch (Exception $e) {
    // Roll back the transaction
    $conn->rollback();

    // Log the error
    debug_log("Error in update_assignment.php", ["error" => $e->getMessage(), "trace" => $e->getTraceAsString()]);

    // Return error response
    header('Content-Type: application/json');
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
}