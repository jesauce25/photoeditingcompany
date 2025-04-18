<?php
/**
 * Update Image Assignment Controller
 * Handles assigning and unassigning images from team members
 */

// Set proper headers
header('Content-Type: application/json');

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Start output buffering to prevent any unwanted output
ob_start();

// Include database connection
if (file_exists(__DIR__ . '/../includes/db_connection.php')) {
    require_once __DIR__ . '/../includes/db_connection.php';
} else if (file_exists(__DIR__ . '/../../includes/db_connection.php')) {
    require_once __DIR__ . '/../../includes/db_connection.php';
}

// Check if the database connection is available
if (!isset($conn) || !$conn) {
    ob_clean();
    echo json_encode([
        'success' => false,
        'message' => 'Database connection failed'
    ]);
    exit;
}

// Function to log errors
function logError($message, $data = null)
{
    error_log("[" . date('Y-m-d H:i:s') . "] UPDATE_IMAGE_ASSIGNMENT: " . $message .
        ($data ? " - Data: " . json_encode($data) : ""));
}

// Check if this is a POST request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    ob_clean();
    echo json_encode([
        'success' => false,
        'message' => 'Invalid request method. Only POST is supported.'
    ]);
    exit;
}

// Check for required parameters
if (!isset($_POST['action'])) {
    ob_clean();
    echo json_encode([
        'success' => false,
        'message' => 'Missing required action parameter'
    ]);
    exit;
}

// Process different actions
$action = $_POST['action'];

try {
    // Start transaction
    $conn->begin_transaction();

    // Handle the remove action
    if ($action === 'remove_assigned_images') {
        // Check for required parameters
        if (!isset($_POST['assignment_id'])) {
            throw new Exception('Missing required assignment_id parameter');
        }

        $assignmentId = (int) $_POST['assignment_id'];

        // Verify assignment exists
        $checkAssignmentSql = "SELECT * FROM tbl_project_assignments WHERE assignment_id = ?";
        $checkAssignmentStmt = $conn->prepare($checkAssignmentSql);
        $checkAssignmentStmt->bind_param("i", $assignmentId);
        $checkAssignmentStmt->execute();
        $assignmentResult = $checkAssignmentStmt->get_result();

        if ($assignmentResult->num_rows === 0) {
            throw new Exception("Assignment not found");
        }

        // Get all images assigned to this assignment
        $getImagesSql = "SELECT image_id FROM tbl_project_images WHERE assignment_id = ?";
        $getImagesStmt = $conn->prepare($getImagesSql);
        $getImagesStmt->bind_param("i", $assignmentId);
        $getImagesStmt->execute();
        $imagesResult = $getImagesStmt->get_result();

        $imageIds = [];
        while ($row = $imagesResult->fetch_assoc()) {
            $imageIds[] = $row['image_id'];
        }

        // Update the images to remove the assignment
        if (!empty($imageIds)) {
            $updateImagesSql = "UPDATE tbl_project_images SET assignment_id = NULL WHERE assignment_id = ?";
            $updateImagesStmt = $conn->prepare($updateImagesSql);
            $updateImagesStmt->bind_param("i", $assignmentId);

            if (!$updateImagesStmt->execute()) {
                throw new Exception("Failed to remove image assignments: " . $updateImagesStmt->error);
            }

            $totalImages = count($imageIds);
            $affectedRows = $updateImagesStmt->affected_rows;
        } else {
            $totalImages = 0;
            $affectedRows = 0;
        }

        // Commit transaction
        $conn->commit();

        ob_clean();
        echo json_encode([
            'success' => true,
            'message' => "Removed assigned images successfully",
            'affected_images' => $affectedRows,
            'total_images' => $totalImages,
            'assignment_id' => $assignmentId
        ]);
    }
    // Handle the assign action
    else if ($action === 'assign_images') {
        // Check for required parameters
        if (!isset($_POST['assignment_id']) || !isset($_POST['image_ids'])) {
            throw new Exception('Missing required parameters: assignment_id and/or image_ids');
        }

        $assignmentId = (int) $_POST['assignment_id'];
        $imageIds = json_decode($_POST['image_ids'], true);

        if (!is_array($imageIds) || empty($imageIds)) {
            throw new Exception('Invalid or empty image_ids parameter');
        }

        // Verify assignment exists
        $checkAssignmentSql = "SELECT * FROM tbl_project_assignments WHERE assignment_id = ?";
        $checkAssignmentStmt = $conn->prepare($checkAssignmentSql);
        $checkAssignmentStmt->bind_param("i", $assignmentId);
        $checkAssignmentStmt->execute();
        $assignmentResult = $checkAssignmentStmt->get_result();

        if ($assignmentResult->num_rows === 0) {
            throw new Exception("Assignment not found");
        }

        // Update the images to add the assignment
        $updateImagesSql = "UPDATE tbl_project_images SET assignment_id = ? WHERE image_id = ? AND (assignment_id IS NULL OR assignment_id = 0)";
        $updateImagesStmt = $conn->prepare($updateImagesSql);

        $successCount = 0;
        $alreadyAssignedCount = 0;

        foreach ($imageIds as $imageId) {
            // Check if image is already assigned
            $checkImageSql = "SELECT assignment_id FROM tbl_project_images WHERE image_id = ?";
            $checkImageStmt = $conn->prepare($checkImageSql);
            $checkImageStmt->bind_param("i", $imageId);
            $checkImageStmt->execute();
            $imageResult = $checkImageStmt->get_result();

            if ($imageResult->num_rows === 0) {
                // Skip non-existent images
                continue;
            }

            $imageData = $imageResult->fetch_assoc();

            if ($imageData['assignment_id'] !== null && $imageData['assignment_id'] > 0) {
                $alreadyAssignedCount++;
                continue;
            }

            // Assign the image
            $updateImagesStmt->bind_param("ii", $assignmentId, $imageId);

            if ($updateImagesStmt->execute() && $updateImagesStmt->affected_rows > 0) {
                $successCount++;
            }
        }

        // Commit transaction
        $conn->commit();

        ob_clean();
        echo json_encode([
            'success' => true,
            'message' => "Assigned images successfully",
            'assigned_count' => $successCount,
            'already_assigned_count' => $alreadyAssignedCount,
            'assignment_id' => $assignmentId
        ]);
    } else {
        throw new Exception('Invalid action specified');
    }
} catch (Exception $e) {
    // Rollback transaction on error
    if (isset($conn) && $conn->ping()) {
        $conn->rollback();
    }

    logError("Error: " . $e->getMessage(), ['action' => $action]);

    ob_clean();
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>