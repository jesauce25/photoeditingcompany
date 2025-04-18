<?php
/**
 * Delete Project Image Controller
 * Handles deleting images from projects
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
    error_log("[" . date('Y-m-d H:i:s') . "] DELETE_PROJECT_IMAGE: " . $message .
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
if (!isset($_POST['image_id']) || !isset($_POST['action'])) {
    ob_clean();
    echo json_encode([
        'success' => false,
        'message' => 'Missing required parameters: image_id and/or action'
    ]);
    exit;
}

// Verify action is correct
if ($_POST['action'] !== 'delete') {
    ob_clean();
    echo json_encode([
        'success' => false,
        'message' => 'Invalid action. Only delete is supported.'
    ]);
    exit;
}

$imageId = (int) $_POST['image_id'];

try {
    // Start transaction
    $conn->begin_transaction();

    // Get image details before deletion
    $getImageSql = "SELECT pi.*, p.project_name 
                    FROM tbl_project_images pi
                    LEFT JOIN tbl_projects p ON pi.project_id = p.project_id
                    WHERE pi.image_id = ?";
    $getImageStmt = $conn->prepare($getImageSql);
    $getImageStmt->bind_param("i", $imageId);
    $getImageStmt->execute();
    $imageResult = $getImageStmt->get_result();

    if ($imageResult->num_rows === 0) {
        throw new Exception("Image not found");
    }

    $imageData = $imageResult->fetch_assoc();
    $fileName = $imageData['image_file_name'];
    $projectId = $imageData['project_id'];
    $filePath = isset($imageData['image_file_path']) ? $imageData['image_file_path'] : '';

    // Full physical path to the file
    $fullFilePath = '';
    if (!empty($filePath) && !empty($fileName)) {
        if (file_exists(__DIR__ . '/../' . $filePath . '/' . $fileName)) {
            $fullFilePath = __DIR__ . '/../' . $filePath . '/' . $fileName;
        } else if (file_exists(__DIR__ . '/../../' . $filePath . '/' . $fileName)) {
            $fullFilePath = __DIR__ . '/../../' . $filePath . '/' . $fileName;
        }
    }

    // Delete the image record
    $deleteImageSql = "DELETE FROM tbl_project_images WHERE image_id = ?";
    $deleteImageStmt = $conn->prepare($deleteImageSql);
    $deleteImageStmt->bind_param("i", $imageId);

    if (!$deleteImageStmt->execute()) {
        throw new Exception("Failed to delete image: " . $deleteImageStmt->error);
    }

    if ($deleteImageStmt->affected_rows === 0) {
        throw new Exception("No image was deleted. Possible race condition.");
    }

    // Try to delete the physical file if it exists
    $fileDeleteSuccess = false;
    $fileDeleteMessage = "";

    if (!empty($fullFilePath) && file_exists($fullFilePath)) {
        if (unlink($fullFilePath)) {
            $fileDeleteSuccess = true;
        } else {
            $fileDeleteMessage = "Warning: Could not delete the physical file at: " . $fullFilePath;
            logError($fileDeleteMessage);
        }
    } else if (!empty($fileName)) {
        $fileDeleteMessage = "Warning: Physical file not found for: " . $fileName;
        logError($fileDeleteMessage);
    }

    // Commit transaction
    $conn->commit();

    // Prepare response
    $response = [
        'success' => true,
        'message' => "Image deleted successfully" .
            (!$fileDeleteSuccess && !empty($fileDeleteMessage) ? " (Note: " . $fileDeleteMessage . ")" : ""),
        'image_id' => $imageId,
        'project_id' => $projectId,
        'file_name' => $fileName
    ];

    ob_clean();
    echo json_encode($response);

} catch (Exception $e) {
    // Rollback transaction on error
    if (isset($conn) && $conn->ping()) {
        $conn->rollback();
    }

    logError("Error: " . $e->getMessage(), ['image_id' => $imageId]);

    ob_clean();
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),
        'image_id' => $imageId
    ]);
}
?>