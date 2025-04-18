<?php
/**
 * Update Assignment Controller
 * Handles adding and removing team members from projects
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
    error_log("[" . date('Y-m-d H:i:s') . "] UPDATE_ASSIGNMENT: " . $message .
        ($data ? " - Data: " . json_encode($data) : ""));
}

// Check if required parameters exist
function checkRequiredParams($params)
{
    foreach ($params as $param) {
        if (!isset($_POST[$param]) || empty($_POST[$param])) {
            return false;
        }
    }
    return true;
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

// Check for action parameter
if (!isset($_POST['action'])) {
    ob_clean();
    echo json_encode([
        'success' => false,
        'message' => 'Missing required action parameter'
    ]);
    exit;
}

$action = $_POST['action'];

try {
    // Start transaction
    $conn->begin_transaction();

    switch ($action) {
        case 'add':
            // Required parameters for adding an assignment
            if (!checkRequiredParams(['project_id', 'user_id', 'role_task', 'deadline'])) {
                throw new Exception('Missing required parameters for add action');
            }

            $projectId = (int) $_POST['project_id'];
            $userId = (int) $_POST['user_id'];
            $roleTask = $_POST['role_task'];
            $deadline = $_POST['deadline'];

            // Verify user exists
            $checkUserSql = "SELECT account_id FROM tbl_accounts WHERE account_id = ?";
            $checkUserStmt = $conn->prepare($checkUserSql);
            $checkUserStmt->bind_param("i", $userId);
            $checkUserStmt->execute();
            $userResult = $checkUserStmt->get_result();

            if ($userResult->num_rows === 0) {
                throw new Exception("User not found");
            }

            // Check for existing assignment
            $checkAssignmentSql = "SELECT assignment_id FROM tbl_project_assignments 
                                  WHERE project_id = ? AND user_id = ?";
            $checkAssignmentStmt = $conn->prepare($checkAssignmentSql);
            $checkAssignmentStmt->bind_param("ii", $projectId, $userId);
            $checkAssignmentStmt->execute();
            $assignmentResult = $checkAssignmentStmt->get_result();

            if ($assignmentResult->num_rows > 0) {
                // Assignment already exists, update it
                $assignmentData = $assignmentResult->fetch_assoc();
                $assignmentId = $assignmentData['assignment_id'];

                $updateSql = "UPDATE tbl_project_assignments 
                             SET role_task = ?, deadline = ?, date_modified = NOW() 
                             WHERE assignment_id = ?";
                $updateStmt = $conn->prepare($updateSql);
                $updateStmt->bind_param("ssi", $roleTask, $deadline, $assignmentId);

                if (!$updateStmt->execute()) {
                    throw new Exception("Failed to update assignment: " . $updateStmt->error);
                }

                $result = [
                    'success' => true,
                    'message' => 'Assignment updated successfully',
                    'assignment_id' => $assignmentId,
                    'is_new' => false
                ];
            } else {
                // Create new assignment
                $insertSql = "INSERT INTO tbl_project_assignments 
                             (project_id, user_id, role_task, deadline, status_assignee, date_created) 
                             VALUES (?, ?, ?, ?, 'pending', NOW())";
                $insertStmt = $conn->prepare($insertSql);
                $statusAssignee = 'pending';
                $insertStmt->bind_param("iiss", $projectId, $userId, $roleTask, $deadline);

                if (!$insertStmt->execute()) {
                    throw new Exception("Failed to create assignment: " . $insertStmt->error);
                }

                $assignmentId = $conn->insert_id;

                // Update project status if it's pending
                $updateProjectSql = "UPDATE tbl_projects 
                                    SET status_project = CASE 
                                        WHEN status_project = 'pending' THEN 'in progress' 
                                        ELSE status_project 
                                    END 
                                    WHERE project_id = ?";
                $updateProjectStmt = $conn->prepare($updateProjectSql);
                $updateProjectStmt->bind_param("i", $projectId);
                $updateProjectStmt->execute();

                $result = [
                    'success' => true,
                    'message' => 'Assignment created successfully',
                    'assignment_id' => $assignmentId,
                    'is_new' => true
                ];
            }
            break;

        case 'remove':
            // Required parameters for removing an assignment
            if (!isset($_POST['assignment_id'])) {
                throw new Exception('Missing required assignment_id parameter');
            }

            $assignmentId = (int) $_POST['assignment_id'];

            // Verify assignment exists
            $checkAssignmentSql = "SELECT a.assignment_id, a.project_id, a.user_id, 
                                  u.first_name, u.last_name
                                  FROM tbl_project_assignments a
                                  LEFT JOIN tbl_users u ON a.user_id = u.user_id
                                  WHERE a.assignment_id = ?";
            $checkAssignmentStmt = $conn->prepare($checkAssignmentSql);
            $checkAssignmentStmt->bind_param("i", $assignmentId);
            $checkAssignmentStmt->execute();
            $assignmentResult = $checkAssignmentStmt->get_result();

            if ($assignmentResult->num_rows === 0) {
                throw new Exception("Assignment not found");
            }

            $assignmentData = $assignmentResult->fetch_assoc();
            $projectId = $assignmentData['project_id'];
            $assigneeName = $assignmentData['first_name'] . ' ' . $assignmentData['last_name'];

            // Clear any image assignments for this assignment
            $updateImagesSql = "UPDATE tbl_project_images 
                              SET assignment_id = NULL
                              WHERE assignment_id = ?";
            $updateImagesStmt = $conn->prepare($updateImagesSql);
            $updateImagesStmt->bind_param("i", $assignmentId);
            $updateImagesStmt->execute();

            // Now delete the assignment
            $deleteAssignmentSql = "DELETE FROM tbl_project_assignments 
                                   WHERE assignment_id = ?";
            $deleteAssignmentStmt = $conn->prepare($deleteAssignmentSql);
            $deleteAssignmentStmt->bind_param("i", $assignmentId);

            if (!$deleteAssignmentStmt->execute()) {
                throw new Exception("Failed to delete assignment: " . $deleteAssignmentStmt->error);
            }

            // Check if this was the last assignment
            $countAssignmentsSql = "SELECT COUNT(*) as assignment_count 
                                   FROM tbl_project_assignments 
                                   WHERE project_id = ?";
            $countAssignmentsStmt = $conn->prepare($countAssignmentsSql);
            $countAssignmentsStmt->bind_param("i", $projectId);
            $countAssignmentsStmt->execute();
            $countResult = $countAssignmentsStmt->get_result();
            $countData = $countResult->fetch_assoc();

            // If no assignments left, update project status to 'pending'
            if ($countData['assignment_count'] === 0) {
                $updateProjectSql = "UPDATE tbl_projects 
                                    SET status_project = 'pending' 
                                    WHERE project_id = ?";
                $updateProjectStmt = $conn->prepare($updateProjectSql);
                $updateProjectStmt->bind_param("i", $projectId);
                $updateProjectStmt->execute();
            }

            $result = [
                'success' => true,
                'message' => 'Assignment removed successfully',
                'assignment_id' => $assignmentId,
                'assignee_name' => $assigneeName
            ];
            break;

        default:
            throw new Exception("Unknown action: $action");
    }

    // Commit transaction
    $conn->commit();

    ob_clean();
    echo json_encode($result);

} catch (Exception $e) {
    // Rollback transaction on error
    if (isset($conn) && $conn->ping()) {
        $conn->rollback();
    }

    logError("Error: " . $e->getMessage(), $_POST);

    ob_clean();
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>