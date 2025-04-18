<?php
/**
 * Update Image Assignment Controller
 * This file handles AJAX requests to update image assignments
 */

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

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
    header('Content-Type: application/json');
    echo json_encode([
        'status' => 'error',
        'message' => 'Invalid request method'
    ]);
    exit;
}

// Get parameters from POST
$image_id = isset($_POST['image_id']) ? intval($_POST['image_id']) : 0;
$assignment_id = isset($_POST['assignment_id']) ? $_POST['assignment_id'] : null;
$action = isset($_POST['action']) ? $_POST['action'] : '';

// Validate image_id
if ($image_id <= 0) {
    header('Content-Type: application/json');
    echo json_encode([
        'status' => 'error',
        'message' => 'Invalid image ID'
    ]);
    exit;
}

try {
    // Process based on action
    switch ($action) {
        case 'remove':
            // Update the image to clear its assignment
            $sql = "UPDATE tbl_project_images SET assignment_id = NULL WHERE image_id = ?";
            $stmt = $conn->prepare($sql);
            if (!$stmt) {
                throw new Exception("Prepare statement error: " . $conn->error);
            }

            $stmt->bind_param("i", $image_id);

            if (!$stmt->execute()) {
                throw new Exception("Error updating image assignment: " . $stmt->error);
            }

            // Get the affected assignment to update its count
            $getAssignmentSql = "SELECT assignment_id FROM tbl_project_images WHERE image_id = ?";
            $getAssignmentStmt = $conn->prepare($getAssignmentSql);
            $getAssignmentStmt->bind_param("i", $image_id);
            $getAssignmentStmt->execute();
            $getAssignmentResult = $getAssignmentStmt->get_result();

            if ($getAssignmentResult->num_rows > 0) {
                $row = $getAssignmentResult->fetch_assoc();
                $prev_assignment_id = $row['assignment_id'];

                // Update the assignment's image count
                $updateCountSql = "UPDATE tbl_project_assignments 
                                  SET assigned_images = (
                                    SELECT COUNT(*) 
                                    FROM tbl_project_images 
                                    WHERE assignment_id = ?
                                  )
                                  WHERE assignment_id = ?";
                $updateCountStmt = $conn->prepare($updateCountSql);
                $updateCountStmt->bind_param("ii", $prev_assignment_id, $prev_assignment_id);
                $updateCountStmt->execute();

                // Log the update for debugging
                error_log("Updated assignment ID: $prev_assignment_id with new image count");
            }

            // Return success response
            header('Content-Type: application/json');
            echo json_encode([
                'status' => 'success',
                'message' => 'Image assignment removed successfully'
            ]);
            break;

        case 'assign':
            // Check if assignment_id is valid
            if ($assignment_id !== null && (!is_numeric($assignment_id) || intval($assignment_id) <= 0)) {
                throw new Exception("Invalid assignment ID");
            }

            // Update the image's assignment
            $sql = "UPDATE tbl_project_images SET assignment_id = ? WHERE image_id = ?";
            $stmt = $conn->prepare($sql);

            if (is_numeric($assignment_id)) {
                $assignment_id_int = intval($assignment_id);
                $stmt->bind_param("ii", $assignment_id_int, $image_id);
            } else {
                $null_value = null;
                $stmt->bind_param("ii", $null_value, $image_id);
            }

            if (!$stmt->execute()) {
                throw new Exception("Error updating image assignment: " . $stmt->error);
            }

            // Update the assignment's image count
            if (is_numeric($assignment_id)) {
                $assignment_id_int = intval($assignment_id);
                $updateCountSql = "UPDATE tbl_project_assignments 
                                  SET assigned_images = (
                                    SELECT COUNT(*) 
                                    FROM tbl_project_images 
                                    WHERE assignment_id = ?
                                  )
                                  WHERE assignment_id = ?";
                $updateCountStmt = $conn->prepare($updateCountSql);
                $updateCountStmt->bind_param("ii", $assignment_id_int, $assignment_id_int);
                $updateCountStmt->execute();
            }

            // Return success response
            header('Content-Type: application/json');
            echo json_encode([
                'status' => 'success',
                'message' => 'Image assignment updated successfully'
            ]);
            break;

        default:
            throw new Exception("Invalid action specified");
    }
} catch (Exception $e) {
    // Return error response
    header('Content-Type: application/json');
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
}