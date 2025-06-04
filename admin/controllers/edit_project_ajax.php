<?php

/**
 * Edit Project AJAX Controller
 * Handles all AJAX requests for the edit project page
 */

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Make sure database connection is available
require_once __DIR__ . '/db_connection_passthrough.php';
require_once 'unified_project_controller.php';

// Function to check if a transaction is in progress (compatibility function)
function isInTransaction($conn)
{
    // Check if the inTransaction method exists (PHP 5.5.0+)
    if (method_exists($conn, 'inTransaction')) {
        return $conn->inTransaction();
    }

    // Fallback for older MySQL versions - attempt a dummy query
    // If we're in transaction and there was an error, this won't commit automatically
    $initialAutocommit = $conn->autocommit(false);
    $inTransaction = !$initialAutocommit;
    $conn->autocommit($initialAutocommit);

    return $inTransaction;
}

// Function to log AJAX requests for debugging
function logAjaxRequest($action, $data = null, $files = null)
{
    $timestamp = date('Y-m-d H:i:s');
    $log = "[$timestamp] AJAX Request: $action";

    if ($data !== null) {
        // Filter out sensitive data like passwords
        $filtered_data = $data;
        if (isset($filtered_data['password'])) {
            $filtered_data['password'] = '***FILTERED***';
        }

        $log .= " - Data: " . json_encode($filtered_data);
    }

    if ($files !== null) {
        $files_info = [];
        foreach ($files as $key => $file_array) {
            if (is_array($file_array['name'])) {
                $files_info[$key] = [
                    'count' => count($file_array['name']),
                    'names' => $file_array['name']
                ];
            } else {
                $files_info[$key] = [
                    'name' => $file_array['name'],
                    'size' => $file_array['size']
                ];
            }
        }
        $log .= " - Files: " . json_encode($files_info);
    }

    error_log($log);
}

// Check if it's an AJAX request
if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
    $action = $_POST['action'] ?? '';
    logAjaxRequest($action, $_POST);

    // Handle different AJAX actions
    switch ($action) {
        case 'get_project_stats':
            $project_id = $_POST['project_id'] ?? 0;
            if ($project_id > 0) {
                // Check if project has overdue assignments first
                checkAndUpdateProjectDelayStatus($project_id);

                // Then get the latest stats
                $stats = getProjectStats($project_id);
                echo json_encode(['status' => 'success', 'data' => $stats]);
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Invalid project ID']);
            }
            break;

        case 'save_assignment':
            $project_id = intval($_POST['project_id'] ?? 0);
            $user_id = intval($_POST['user_id'] ?? 0);
            $role_task = $_POST['role_task'] ?? '';
            $deadline = $_POST['deadline'] ?? '';

            if ($project_id <= 0 || $user_id <= 0 || empty($role_task) || empty($deadline)) {
                echo json_encode(['status' => 'error', 'message' => 'Missing required fields']);
                exit;
            }

            // Log debug info
            error_log("Saving assignment: project_id=$project_id, user_id=$user_id, role_task=$role_task, deadline=$deadline");

            // Begin transaction
            $conn->begin_transaction();

            try {
                // Insert new assignment
                $sql = "INSERT INTO tbl_project_assignments 
                        (project_id, user_id, role_task, status_assignee, deadline) 
                        VALUES (?, ?, ?, 'pending', ?)";

                $stmt = $conn->prepare($sql);
                if (!$stmt) {
                    throw new Exception("Error preparing statement: " . $conn->error);
                }

                $stmt->bind_param("iiss", $project_id, $user_id, $role_task, $deadline);

                if (!$stmt->execute()) {
                    throw new Exception("Error creating assignment: " . $stmt->error);
                }

                $assignment_id = $conn->insert_id;

                // Update project status to in_progress if it's pending or completed
                // This ensures adding a new assignee changes status from "Completed" to "In Progress"
                $updateProjectSql = "UPDATE tbl_projects 
                                    SET status_project = CASE 
                                        WHEN status_project IN ('pending', 'completed') THEN 'in_progress' 
                                        ELSE status_project 
                                    END,
                                    date_updated = NOW()
                                    WHERE project_id = ?";
                $updateStmt = $conn->prepare($updateProjectSql);
                $updateStmt->bind_param("i", $project_id);
                $updateStmt->execute();

                // Get user details for response
                $userSql = "SELECT u.first_name, u.last_name, 
                            CONCAT(u.first_name, ' ', u.last_name) AS full_name,
                            a.role
                            FROM tbl_users u
                            LEFT JOIN tbl_accounts a ON u.user_id = a.user_id
                            WHERE u.user_id = ?";
                $userStmt = $conn->prepare($userSql);
                $userStmt->bind_param("i", $user_id);
                $userStmt->execute();
                $userResult = $userStmt->get_result();

                if ($userResult->num_rows === 0) {
                    // Try alternate query without role
                    $userSql = "SELECT u.first_name, u.last_name, 
                                CONCAT(u.first_name, ' ', u.last_name) AS full_name
                                FROM tbl_users u
                                WHERE u.user_id = ?";
                    $userStmt = $conn->prepare($userSql);
                    $userStmt->bind_param("i", $user_id);
                    $userStmt->execute();
                    $userResult = $userStmt->get_result();
                }

                $user = $userResult->fetch_assoc();

                if (!$user) {
                    throw new Exception("User not found with ID: $user_id");
                }

                // Commit transaction
                $conn->commit();

                // Return success response
                $response = [
                    'status' => 'success',
                    'message' => 'Assignment created successfully',
                    'assignment_id' => $assignment_id,
                    'user' => $user,
                    'role_task' => $role_task,
                    'deadline' => $deadline
                ];

                echo json_encode($response);
            } catch (Exception $e) {
                // Roll back transaction and return error
                $conn->rollback();
                echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
                error_log("Assignment creation error: " . $e->getMessage());
            }
            break;

        case 'delete_assignment':
            $assignment_id = intval($_POST['assignment_id'] ?? 0);
            $project_id = intval($_POST['project_id'] ?? 0);

            if ($assignment_id <= 0 || $project_id <= 0) {
                echo json_encode(['status' => 'error', 'message' => 'Invalid assignment ID']);
                exit;
            }

            // First, update any assigned images to remove the assignment
            $updateImagesSql = "UPDATE tbl_project_images 
                              SET assignment_id = NULL, status_image = 'available' 
                              WHERE assignment_id = ? AND project_id = ?";
            $updateImagesStmt = $conn->prepare($updateImagesSql);
            $updateImagesStmt->bind_param("ii", $assignment_id, $project_id);
            $updateImagesStmt->execute();

            // Then delete the assignment
            $deleteSql = "DELETE FROM tbl_project_assignments WHERE assignment_id = ? AND project_id = ?";
            $deleteStmt = $conn->prepare($deleteSql);
            $deleteStmt->bind_param("ii", $assignment_id, $project_id);

            if ($deleteStmt->execute()) {
                echo json_encode(['status' => 'success', 'message' => 'Assignment deleted successfully']);
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Error deleting assignment: ' . $deleteStmt->error]);
            }
            break;

        case 'assign_images':
            $project_id = intval($_POST['project_id'] ?? 0);
            $assignment_id = intval($_POST['assignment_id'] ?? 0);
            $image_ids = json_decode($_POST['image_ids'] ?? '[]', true);

            if ($project_id <= 0 || $assignment_id <= 0 || empty($image_ids)) {
                echo json_encode(['status' => 'error', 'message' => 'Missing required fields']);
                exit;
            }

            $conn->begin_transaction();

            try {
                // First get the assignee's name and role
                $assigneeSql = "SELECT u.first_name, pa.role_task 
                               FROM tbl_project_assignments pa
                               JOIN tbl_users u ON pa.user_id = u.user_id
                               WHERE pa.assignment_id = ?";
                $assigneeStmt = $conn->prepare($assigneeSql);
                $assigneeStmt->bind_param("i", $assignment_id);
                $assigneeStmt->execute();
                $assigneeResult = $assigneeStmt->get_result();
                $assignee = $assigneeResult->fetch_assoc();

                // Set the status to the assignee's first name or "assigned" if name not found
                $statusImage = isset($assignee['first_name']) ? $assignee['first_name'] : 'assigned';
                $roleTask = isset($assignee['role_task']) ? $assignee['role_task'] : '';

                // Update the images
                $updateSql = "UPDATE tbl_project_images 
                             SET assignment_id = ?, status_image = ?, image_role = ? 
                             WHERE image_id = ? AND project_id = ?";
                $updateStmt = $conn->prepare($updateSql);

                foreach ($image_ids as $image_id) {
                    $updateStmt->bind_param("issii", $assignment_id, $statusImage, $roleTask, $image_id, $project_id);
                    $updateStmt->execute();
                }

                // Update assignment image counts
                $updateCountsSql = "UPDATE tbl_project_assignments 
                                   SET assigned_images = (
                                       SELECT COUNT(*) 
                                       FROM tbl_project_images 
                                       WHERE assignment_id = ? AND project_id = ?
                                   ) 
                                   WHERE assignment_id = ? AND project_id = ?";
                $updateCountsStmt = $conn->prepare($updateCountsSql);
                $updateCountsStmt->bind_param("iiii", $assignment_id, $project_id, $assignment_id, $project_id);
                $updateCountsStmt->execute();

                $conn->commit();

                echo json_encode([
                    'status' => 'success',
                    'message' => count($image_ids) . ' images assigned successfully',
                    'assignee_name' => $statusImage
                ]);
            } catch (Exception $e) {
                $conn->rollback();
                echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
            }
            break;

        case 'unassign_images':
            $project_id = intval($_POST['project_id'] ?? 0);
            $image_ids = json_decode($_POST['image_ids'] ?? '[]', true);

            if ($project_id <= 0 || empty($image_ids)) {
                echo json_encode(['status' => 'error', 'message' => 'Missing required fields']);
                exit;
            }

            $success = true;
            $conn->begin_transaction();

            try {
                // Update all selected images
                $updateSql = "UPDATE tbl_project_images 
                            SET assignment_id = NULL, status_image = 'available' 
                            WHERE image_id = ? AND project_id = ?";
                $updateStmt = $conn->prepare($updateSql);

                foreach ($image_ids as $image_id) {
                    $updateStmt->bind_param("ii", $image_id, $project_id);
                    if (!$updateStmt->execute()) {
                        throw new Exception("Error unassigning image: " . $updateStmt->error);
                    }
                }

                // Update the assignment image counts
                updateAssignmentImageCounts($project_id);

                // Commit transaction
                $conn->commit();

                echo json_encode([
                    'status' => 'success',
                    'message' => count($image_ids) . ' images unassigned successfully'
                ]);
            } catch (Exception $e) {
                $conn->rollback();
                echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
            }
            break;

        case 'update_assignment_status':
            if (!isset($_POST['assignment_id']) || !isset($_POST['status'])) {
                echo json_encode(['status' => 'error', 'message' => 'Missing required parameters']);
                exit;
            }

            $assignmentId = intval($_POST['assignment_id']);
            $newStatus = $_POST['status'];

            // Validate status
            $validStatuses = ['pending', 'in_progress', 'finish', 'qa', 'approved', 'completed'];
            if (!in_array($newStatus, $validStatuses)) {
                echo json_encode(['status' => 'error', 'message' => 'Invalid status value']);
                exit;
            }

            try {
                // Start transaction
                $conn->begin_transaction();

                // Get the current assignment info
                $assignmentQuery = "SELECT project_id, status_assignee, user_id FROM tbl_project_assignments WHERE assignment_id = ?";
                $stmt = $conn->prepare($assignmentQuery);
                $stmt->bind_param('i', $assignmentId);
                $stmt->execute();
                $result = $stmt->get_result();

                if ($result->num_rows === 0) {
                    throw new Exception('Assignment not found');
                }

                $assignmentData = $result->fetch_assoc();
                $projectId = $assignmentData['project_id'];
                $userId = $assignmentData['user_id'];

                // If trying to set to 'in_progress', check if user already has a task in progress
                if ($newStatus === 'in_progress') {
                    $inProgressQuery = "SELECT assignment_id 
                                       FROM tbl_project_assignments 
                                       WHERE user_id = ? 
                                         AND status_assignee = 'in_progress'
                                         AND assignment_id != ?";
                    $inProgressStmt = $conn->prepare($inProgressQuery);
                    $inProgressStmt->bind_param('ii', $userId, $assignmentId);
                    $inProgressStmt->execute();
                    $inProgressResult = $inProgressStmt->get_result();

                    if ($inProgressResult->num_rows > 0) {
                        throw new Exception('The artist already has a task in progress. Only one task can be in progress at a time.');
                    }
                }

                // Update the assignment status
                $updateQuery = "UPDATE tbl_project_assignments SET status_assignee = ?, last_updated = NOW() WHERE assignment_id = ?";
                $stmt = $conn->prepare($updateQuery);
                $stmt->bind_param('si', $newStatus, $assignmentId);

                if (!$stmt->execute()) {
                    throw new Exception('Failed to update assignment status: ' . $conn->error);
                }

                // If status is changed to 'in_progress', update the project status accordingly
                if ($newStatus === 'in_progress') {
                    $updateProjectQuery = "UPDATE tbl_projects SET status_project = 'in_progress' WHERE project_id = ? AND status_project = 'pending'";
                    $stmt = $conn->prepare($updateProjectQuery);
                    $stmt->bind_param('i', $projectId);
                    $stmt->execute();
                }

                // Commit transaction
                $conn->commit();

                echo json_encode([
                    'status' => 'success',
                    'message' => 'Assignment status updated successfully',
                    'new_status' => $newStatus
                ]);
            } catch (Exception $e) {
                // Rollback transaction on error
                if (isInTransaction($conn)) {
                    $conn->rollback();
                }
                echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
            }
            break;

        case 'update_image_redo':
            $image_id = isset($_POST['image_id']) ? intval($_POST['image_id']) : 0;
            $redo_value = isset($_POST['redo_value']) ? $_POST['redo_value'] : '0';

            // Convert to proper value for database (ensure 1 or 0)
            $redo_value = ($redo_value == '1' || $redo_value == 1) ? '1' : '0';

            if (!$image_id) {
                echo json_encode(['status' => 'error', 'message' => 'Invalid image ID']);
                exit;
            }

            // Log the action for debugging
            error_log("Updating image redo status - ID: $image_id, Value: $redo_value");

            // Update the redo status in the database
            $updateSql = "UPDATE tbl_project_images SET redo = ? WHERE image_id = ?";
            $updateStmt = $conn->prepare($updateSql);

            if (!$updateStmt) {
                echo json_encode(['status' => 'error', 'message' => 'Error preparing statement: ' . $conn->error]);
                exit;
            }

            $updateStmt->bind_param("si", $redo_value, $image_id);

            if (!$updateStmt->execute()) {
                echo json_encode(['status' => 'error', 'message' => 'Error executing statement: ' . $updateStmt->error]);
                exit;
            }

            // Check if update was successful
            if ($updateStmt->affected_rows > 0 || $conn->affected_rows > 0) {
                echo json_encode([
                    'status' => 'success',
                    'new_redo_value' => $redo_value,
                    'image_id' => $image_id
                ]);
            } else {
                // Check if the record already had that value
                $checkSql = "SELECT redo FROM tbl_project_images WHERE image_id = ?";
                $checkStmt = $conn->prepare($checkSql);
                $checkStmt->bind_param("i", $image_id);
                $checkStmt->execute();
                $checkResult = $checkStmt->get_result();
                $current = $checkResult->fetch_assoc();

                if ($current && $current['redo'] == $redo_value) {
                    echo json_encode([
                        'status' => 'success',
                        'new_redo_value' => $redo_value,
                        'image_id' => $image_id
                    ]);
                } else {
                    echo json_encode(['status' => 'error', 'message' => 'No rows updated or image not found']);
                }
            }
            break;

        case 'update_image_status':
            $image_id = isset($_POST['image_id']) ? intval($_POST['image_id']) : 0;
            $status = isset($_POST['status']) ? $_POST['status'] : '';

            if (!$image_id || empty($status)) {
                echo json_encode(['status' => 'error', 'message' => 'Invalid image ID or status']);
                exit;
            }

            // Validate status
            $validStatuses = ['available', 'assigned', 'in_progress', 'finish', 'completed'];
            if (!in_array($status, $validStatuses)) {
                echo json_encode(['status' => 'error', 'message' => 'Invalid status value']);
                exit;
            }

            // Log the action for debugging
            error_log("Updating image status - ID: $image_id, Status: $status");

            // Begin transaction
            $conn->begin_transaction();

            try {
                // Update the image status
                $updateSql = "UPDATE tbl_project_images SET status_image = ? WHERE image_id = ?";
                $updateStmt = $conn->prepare($updateSql);
                $updateStmt->bind_param("si", $status, $image_id);

                if (!$updateStmt->execute()) {
                    throw new Exception("Error updating image status: " . $updateStmt->error);
                }

                // If status is 'completed', check if all images in this assignment are completed
                if ($status === 'completed') {
                    // Get the assignment ID for this image
                    $getSql = "SELECT assignment_id, project_id FROM tbl_project_images WHERE image_id = ?";
                    $getStmt = $conn->prepare($getSql);
                    $getStmt->bind_param("i", $image_id);
                    $getStmt->execute();
                    $result = $getStmt->get_result();
                    $image = $result->fetch_assoc();

                    if ($image && $image['assignment_id']) {
                        $assignment_id = $image['assignment_id'];
                        $project_id = $image['project_id'];

                        // Check if all images in this assignment are completed
                        $checkSql = "SELECT COUNT(*) as total, 
                                   SUM(CASE WHEN status_image = 'completed' THEN 1 ELSE 0 END) as completed 
                                   FROM tbl_project_images 
                                   WHERE assignment_id = ? AND project_id = ?";
                        $checkStmt = $conn->prepare($checkSql);
                        $checkStmt->bind_param("ii", $assignment_id, $project_id);
                        $checkStmt->execute();
                        $checkResult = $checkStmt->get_result();
                        $counts = $checkResult->fetch_assoc();

                        // If all images are completed, update the assignment status
                        if ($counts['total'] > 0 && $counts['total'] == $counts['completed']) {
                            $updateAssignmentSql = "UPDATE tbl_project_assignments 
                                                  SET status_assignee = 'completed', last_updated = NOW() 
                                                  WHERE assignment_id = ? AND project_id = ?";
                            $updateAssignmentStmt = $conn->prepare($updateAssignmentSql);
                            $updateAssignmentStmt->bind_param("ii", $assignment_id, $project_id);
                            $updateAssignmentStmt->execute();

                            // Also check if all assignments in the project are completed
                            $checkProjectSql = "SELECT COUNT(*) as total,
                                              SUM(CASE WHEN status_assignee = 'completed' THEN 1 ELSE 0 END) as completed 
                                              FROM tbl_project_assignments WHERE project_id = ?";
                            $checkProjectStmt = $conn->prepare($checkProjectSql);
                            $checkProjectStmt->bind_param("i", $project_id);
                            $checkProjectStmt->execute();
                            $projectResult = $checkProjectStmt->get_result();
                            $projectCounts = $projectResult->fetch_assoc();

                            // If all assignments are completed, update the project status
                            if ($projectCounts['total'] > 0 && $projectCounts['total'] == $projectCounts['completed']) {
                                $updateProjectSql = "UPDATE tbl_projects 
                                                   SET status_project = 'completed', date_updated = NOW() 
                                                   WHERE project_id = ?";
                                $updateProjectStmt = $conn->prepare($updateProjectSql);
                                $updateProjectStmt->bind_param("i", $project_id);
                                $updateProjectStmt->execute();
                            }
                        }
                    }
                }

                // Commit transaction
                $conn->commit();

                echo json_encode([
                    'status' => 'success',
                    'message' => 'Image status updated successfully',
                    'new_status' => $status
                ]);
            } catch (Exception $e) {
                // Roll back transaction on error
                $conn->rollback();
                echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
                error_log("Error updating image status: " . $e->getMessage());
            }
            break;

        case 'check_assignment_completion':
            $assignment_id = isset($_POST['assignment_id']) ? intval($_POST['assignment_id']) : 0;

            if (!$assignment_id) {
                echo json_encode(['status' => 'error', 'message' => 'Invalid assignment ID']);
                exit;
            }

            try {
                // Get the project ID for this assignment
                $getSql = "SELECT project_id FROM tbl_project_assignments WHERE assignment_id = ?";
                $getStmt = $conn->prepare($getSql);
                $getStmt->bind_param("i", $assignment_id);
                $getStmt->execute();
                $result = $getStmt->get_result();
                $assignment = $result->fetch_assoc();

                if (!$assignment) {
                    echo json_encode(['status' => 'error', 'message' => 'Assignment not found']);
                    exit;
                }

                $project_id = $assignment['project_id'];

                // Check if all images in this assignment are completed
                $checkSql = "SELECT COUNT(*) as total, 
                           SUM(CASE WHEN status_image = 'completed' THEN 1 ELSE 0 END) as completed 
                           FROM tbl_project_images 
                           WHERE assignment_id = ? AND project_id = ?";
                $checkStmt = $conn->prepare($checkSql);
                $checkStmt->bind_param("ii", $assignment_id, $project_id);
                $checkStmt->execute();
                $checkResult = $checkStmt->get_result();
                $counts = $checkResult->fetch_assoc();

                $allCompleted = ($counts['total'] > 0 && $counts['total'] == $counts['completed']);

                echo json_encode([
                    'status' => 'success',
                    'all_completed' => $allCompleted,
                    'total' => $counts['total'],
                    'completed' => $counts['completed']
                ]);
            } catch (Exception $e) {
                echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
                error_log("Error checking assignment completion: " . $e->getMessage());
            }
            break;

        case 'update_project_status':
            $project_id = intval($_POST['project_id'] ?? 0);
            $status = $_POST['status'] ?? '';

            if ($project_id <= 0 || empty($status)) {
                echo json_encode(['status' => 'error', 'message' => 'Missing required fields']);
                exit;
            }

            // Valid status values
            $validStatuses = ['pending', 'in_progress', 'review', 'completed', 'delayed'];
            if (!in_array($status, $validStatuses)) {
                echo json_encode(['status' => 'error', 'message' => 'Invalid status value']);
                exit;
            }

            // Update the project status
            $updateSql = "UPDATE tbl_projects 
                        SET status_project = ?, date_updated = NOW() 
                        WHERE project_id = ?";
            $updateStmt = $conn->prepare($updateSql);
            $updateStmt->bind_param("si", $status, $project_id);

            if ($updateStmt->execute()) {
                echo json_encode(['status' => 'success', 'message' => 'Project status updated successfully']);
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Error updating project status: ' . $updateStmt->error]);
            }
            break;

        case 'delete_images':
            $project_id = intval($_POST['project_id'] ?? 0);
            $image_ids = json_decode($_POST['image_ids'] ?? '[]', true);

            if ($project_id <= 0 || empty($image_ids)) {
                echo json_encode(['status' => 'error', 'message' => 'Missing required fields']);
                exit;
            }

            $conn->begin_transaction();

            try {
                // First get the file paths so we can delete the physical files
                $filesSql = "SELECT image_path FROM tbl_project_images WHERE image_id IN (" .
                    implode(',', array_fill(0, count($image_ids), '?')) . ") AND project_id = ?";

                $filesStmt = $conn->prepare($filesSql);

                $types = str_repeat('i', count($image_ids)) . 'i';
                $params = $image_ids;
                $params[] = $project_id;

                $filesStmt->bind_param($types, ...$params);
                $filesStmt->execute();
                $filesResult = $filesStmt->get_result();

                $filesToDelete = [];
                while ($row = $filesResult->fetch_assoc()) {
                    $filesToDelete[] = $row['image_path'];
                }

                // Delete from database
                $deleteSql = "DELETE FROM tbl_project_images WHERE image_id IN (" .
                    implode(',', array_fill(0, count($image_ids), '?')) . ") AND project_id = ?";

                $deleteStmt = $conn->prepare($deleteSql);
                $deleteStmt->bind_param($types, ...$params);

                if (!$deleteStmt->execute()) {
                    throw new Exception("Error deleting images from database: " . $deleteStmt->error);
                }

                // Get new total count
                $countSql = "SELECT COUNT(*) as count FROM tbl_project_images WHERE project_id = ?";
                $countStmt = $conn->prepare($countSql);
                $countStmt->bind_param("i", $project_id);
                $countStmt->execute();
                $countResult = $countStmt->get_result();
                $countRow = $countResult->fetch_assoc();
                $newCount = $countRow['count'];

                // Update project total_images count
                $updateCountSql = "UPDATE tbl_projects SET total_images = ? WHERE project_id = ?";
                $updateCountStmt = $conn->prepare($updateCountSql);
                $updateCountStmt->bind_param("ii", $newCount, $project_id);

                if (!$updateCountStmt->execute()) {
                    throw new Exception("Error updating project image count: " . $updateCountStmt->error);
                }

                // Commit transaction
                $conn->commit();

                // Now try to delete the physical files (outside transaction since filesystem errors shouldn't affect DB integrity)
                $uploadDir = '../../uploads/project_images/';
                foreach ($filesToDelete as $filePath) {
                    $fullPath = $uploadDir . $filePath;
                    if (file_exists($fullPath)) {
                        @unlink($fullPath); // Suppress errors with file deletion
                    }
                }

                echo json_encode([
                    'status' => 'success',
                    'message' => count($image_ids) . ' images deleted successfully',
                    'new_count' => $newCount
                ]);
            } catch (Exception $e) {
                $conn->rollback();
                echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
            }
            break;

        case 'upload_project_images':
            // Validate request
            $project_id = intval($_POST['project_id'] ?? 0);

            if ($project_id <= 0) {
                echo json_encode(['status' => 'error', 'message' => 'Invalid project ID']);
                exit;
            }

            // Verify project exists
            $check_sql = "SELECT project_id FROM tbl_projects WHERE project_id = ?";
            $check_stmt = $conn->prepare($check_sql);
            $check_stmt->bind_param("i", $project_id);
            $check_stmt->execute();
            $check_result = $check_stmt->get_result();

            if ($check_result->num_rows === 0) {
                echo json_encode(['status' => 'error', 'message' => 'Project not found']);
                exit;
            }

            // Process uploaded images
            $uploaded_count = 0;
            $failed_count = 0;
            $errors = [];

            // Check if files were uploaded
            if (!isset($_FILES['projectImages']) || empty($_FILES['projectImages']['name'][0])) {
                echo json_encode(['status' => 'error', 'message' => 'No files were uploaded']);
                exit;
            }

            // Create directories if they don't exist
            $base_upload_dir = "../../uploads/";
            $project_upload_dir = $base_upload_dir . "projects/{$project_id}/";

            if (!is_dir($base_upload_dir)) {
                if (!mkdir($base_upload_dir, 0777, true)) {
                    echo json_encode(['status' => 'error', 'message' => 'Failed to create base upload directory']);
                    exit;
                }
            }

            if (!is_dir($project_upload_dir)) {
                if (!mkdir($project_upload_dir, 0777, true)) {
                    echo json_encode(['status' => 'error', 'message' => 'Failed to create project upload directory']);
                    exit;
                }
            }

            // Process each file
            $files = $_FILES['projectImages'];

            for ($i = 0; $i < count($files['name']); $i++) {
                // Skip if there was an error
                if ($files['error'][$i] !== UPLOAD_ERR_OK) {
                    $failed_count++;
                    $errors[] = "Error with file {$files['name'][$i]}";
                    continue;
                }

                // Get original filename and extension
                $original_filename = $files['name'][$i];
                $file_ext = strtolower(pathinfo($original_filename, PATHINFO_EXTENSION));
                $base_name = pathinfo($original_filename, PATHINFO_FILENAME);

                // Check file type is an allowed image type (include TIF/TIFF explicitly)
                $allowed_types = ['jpg', 'jpeg', 'png', 'gif', 'tif', 'tiff'];
                $allowed_mime_types = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/tiff'];

                $file_type = $files['type'][$i];

                // Special handling for TIFF files which might have inconsistent MIME types
                if ($file_ext === 'tif' || $file_ext === 'tiff') {
                    // Force the correct file type for TIF files
                    $file_type = 'image/tiff';
                }

                if (!in_array($file_ext, $allowed_types) && !in_array($file_type, $allowed_mime_types)) {
                    $failed_count++;
                    $errors[] = "Invalid file type for {$original_filename}. Allowed types: " . implode(', ', $allowed_types);
                    continue;
                }

                // Handle filename conflicts by adding a counter suffix if needed
                $new_filename = $original_filename;
                $counter = 1;

                // Skip file upload, only store the filename
                $sql = "INSERT INTO tbl_project_images (project_id, image_path, file_type, file_size, status_image) 
                       VALUES (?, ?, ?, ?, 'available')";
                $stmt = $conn->prepare($sql);

                $file_size = $files['size'][$i];
                $image_path = $new_filename; // Store just the filename

                $stmt->bind_param("issi", $project_id, $image_path, $file_type, $file_size);

                if ($stmt->execute()) {
                    $uploaded_count++;
                } else {
                    $failed_count++;
                    $errors[] = "Database error for file {$original_filename}: " . $stmt->error;
                }
            }

            // Update the total_images count in the project
            if ($uploaded_count > 0) {
                $update_sql = "UPDATE tbl_projects SET total_images = (SELECT COUNT(*) FROM tbl_project_images WHERE project_id = ?) WHERE project_id = ?";
                $update_stmt = $conn->prepare($update_sql);
                $update_stmt->bind_param("ii", $project_id, $project_id);
                $update_stmt->execute();

                // Update project status if needed
                // if (function_exists('updateProjectStatus')) {
                //     updateProjectStatus($project_id);
                // }
            }

            // Return response
            echo json_encode([
                'status' => $uploaded_count > 0 ? 'success' : 'error',
                'message' => $uploaded_count > 0 ? 'Images uploaded successfully' : 'Failed to upload images',
                'uploaded' => $uploaded_count,
                'failed' => $failed_count,
                'errors' => $errors
            ]);
            break;

        case 'get_available_images':
            $project_id = intval($_POST['project_id'] ?? 0);

            if ($project_id <= 0) {
                echo json_encode(['status' => 'error', 'message' => 'Invalid project ID']);
                exit;
            }

            // Get available (unassigned) images for this project
            $sql = "SELECT image_id, image_path, file_type, upload_date 
                    FROM tbl_project_images 
                    WHERE project_id = ? AND (assignment_id IS NULL OR assignment_id = 0) 
                      AND status_image = 'available'
                    ORDER BY upload_date DESC";

            $stmt = $conn->prepare($sql);
            $stmt->bind_param("i", $project_id);
            $stmt->execute();
            $result = $stmt->get_result();

            $images = [];
            while ($row = $result->fetch_assoc()) {
                $images[] = $row;
            }

            echo json_encode([
                'status' => 'success',
                'images' => $images,
                'count' => count($images)
            ]);
            break;

        case 'get_assigned_images':
            $project_id = intval($_POST['project_id'] ?? 0);
            $assignment_id = intval($_POST['assignment_id'] ?? 0);

            if ($project_id <= 0) {
                echo json_encode(['status' => 'error', 'message' => 'Invalid project ID']);
                exit;
            }

            // If assignment_id is provided, get images for that specific assignment
            if ($assignment_id > 0) {
                // Get images assigned to this assignment
                $sql = "SELECT i.image_id, i.image_path, i.status_image, i.file_type, i.upload_date,
                              i.image_role, i.estimated_time, i.redo,
                              u.first_name, u.last_name
                       FROM tbl_project_images i
                       LEFT JOIN tbl_project_assignments pa ON i.assignment_id = pa.assignment_id
                       LEFT JOIN tbl_users u ON pa.user_id = u.user_id
                       WHERE i.project_id = ? AND i.assignment_id = ?
                       ORDER BY i.upload_date DESC";

                $stmt = $conn->prepare($sql);
                $stmt->bind_param("ii", $project_id, $assignment_id);
                $stmt->execute();
                $result = $stmt->get_result();

                $images = [];
                while ($row = $result->fetch_assoc()) {
                    // Ensure image path is complete for display
                    if (!empty($row['image_path'])) {
                        $image_url = '../uploads/projects/' . $project_id . '/' . $row['image_path'];
                        $row['image_url'] = $image_url;
                    }
                    $images[] = $row;
                }

                // Get assignment details for context with a detailed query
                $assignmentSql = "SELECT pa.assignment_id, pa.role_task, pa.status_assignee, pa.deadline, pa.assigned_images,
                                       u.first_name, u.last_name, CONCAT(u.first_name, ' ', u.last_name) as full_name
                                 FROM tbl_project_assignments pa
                                 LEFT JOIN tbl_users u ON pa.user_id = u.user_id
                                 WHERE pa.assignment_id = ?";

                $assignmentStmt = $conn->prepare($assignmentSql);
                $assignmentStmt->bind_param("i", $assignment_id);
                $assignmentStmt->execute();
                $assignmentResult = $assignmentStmt->get_result();
                $assignment = $assignmentResult->fetch_assoc();

                echo json_encode([
                    'status' => 'success',
                    'images' => $images,
                    'assignment' => $assignment
                ]);
            } else {
                // Get all assigned images for the project
                $sql = "SELECT i.image_id, i.image_path, i.status_image, i.file_type, i.upload_date,
                              i.image_role, i.estimated_time, i.assignment_id, i.redo,
                              pa.role_task, pa.status_assignee,
                              u.first_name, u.last_name, CONCAT(u.first_name, ' ', u.last_name) as team_name
                       FROM tbl_project_images i
                       LEFT JOIN tbl_project_assignments pa ON i.assignment_id = pa.assignment_id
                       LEFT JOIN tbl_users u ON pa.user_id = u.user_id
                       WHERE i.project_id = ? AND i.assignment_id IS NOT NULL
                       ORDER BY i.upload_date DESC";

                $stmt = $conn->prepare($sql);
                $stmt->bind_param("i", $project_id);
                $stmt->execute();
                $result = $stmt->get_result();

                $images = [];
                while ($row = $result->fetch_assoc()) {
                    // Ensure image path is complete for display
                    if (!empty($row['image_path'])) {
                        $image_url = '../uploads/projects/' . $project_id . '/' . $row['image_path'];
                        $row['image_url'] = $image_url;
                    }
                    $images[] = $row;
                }

                echo json_encode([
                    'status' => 'success',
                    'images' => $images,
                    'count' => count($images)
                ]);
            }
            break;

        case 'update_image_details':
            $image_id = isset($_POST['image_id']) ? intval($_POST['image_id']) : 0;
            $image_role = $_POST['image_role'] ?? '';
            $hours = isset($_POST['estimated_hours']) ? intval($_POST['estimated_hours']) : null;
            $minutes = isset($_POST['estimated_minutes']) ? intval($_POST['estimated_minutes']) : null;

            if (!$image_id) {
                echo json_encode(['status' => 'error', 'message' => 'Invalid image ID']);
                exit;
            }

            // Begin transaction
            $conn->begin_transaction();

            try {
                // First, get current values to preserve them if not provided
                $currentSql = "SELECT estimated_time, image_role FROM tbl_project_images WHERE image_id = ?";
                $currentStmt = $conn->prepare($currentSql);
                $currentStmt->bind_param("i", $image_id);
                $currentStmt->execute();
                $result = $currentStmt->get_result();
                $currentData = $result->fetch_assoc();

                // Calculate total minutes from hours and minutes
                $totalMinutes = null;

                // Only update time if both hours and minutes are provided
                if ($hours !== null && $minutes !== null) {
                    $totalMinutes = ($hours * 60) + $minutes;
                } else if ($currentData && isset($currentData['estimated_time'])) {
                    // Keep existing time if not both provided
                    $totalMinutes = $currentData['estimated_time'];
                }

                // Use current image_role if not provided
                if (empty($image_role) && $currentData && !empty($currentData['image_role'])) {
                    $image_role = $currentData['image_role'];
                }

                // Build the update query dynamically based on which values are provided
                $updateFields = [];
                $updateParams = [];
                $types = "";

                if ($totalMinutes !== null) {
                    $updateFields[] = "estimated_time = ?";
                    $updateParams[] = $totalMinutes;
                    $types .= "i";
                }

                if (!empty($image_role)) {
                    $updateFields[] = "image_role = ?";
                    $updateParams[] = $image_role;
                    $types .= "s";
                }

                if (empty($updateFields)) {
                    echo json_encode(['status' => 'error', 'message' => 'No fields to update']);
                    exit;
                }

                // Add image_id parameter
                $updateParams[] = $image_id;
                $types .= "i";

                $updateSql = "UPDATE tbl_project_images SET " . implode(", ", $updateFields) . " WHERE image_id = ?";
                $updateStmt = $conn->prepare($updateSql);
                $updateStmt->bind_param($types, ...$updateParams);

                if ($updateStmt->execute()) {
                    $conn->commit();

                    // Return the values that were actually saved
                    $responseData = [
                        'status' => 'success'
                    ];

                    if ($totalMinutes !== null) {
                        $responseData['hours'] = floor($totalMinutes / 60);
                        $responseData['minutes'] = $totalMinutes % 60;
                        $responseData['total_minutes'] = $totalMinutes;
                    }

                    if (!empty($image_role)) {
                        $responseData['role'] = $image_role;
                    }

                    echo json_encode($responseData);
                } else {
                    $conn->rollback();
                    echo json_encode(['status' => 'error', 'message' => 'Error updating image details: ' . $updateStmt->error]);
                }
            } catch (Exception $e) {
                $conn->rollback();
                echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
            }
            break;

        case 'update_assignment_deadline':
            $assignment_id = intval($_POST['assignment_id'] ?? 0);
            $deadline = $_POST['deadline'] ?? '';

            if ($assignment_id <= 0 || empty($deadline)) {
                echo json_encode(['status' => 'error', 'message' => 'Missing required fields']);
                exit;
            }

            // Update the assignment deadline
            $updateSql = "UPDATE tbl_project_assignments 
                        SET deadline = ?, last_updated = NOW() 
                        WHERE assignment_id = ?";
            $updateStmt = $conn->prepare($updateSql);
            $updateStmt->bind_param("si", $deadline, $assignment_id);

            if ($updateStmt->execute()) {
                // Calculate deadline status for response
                $deadline_date = new DateTime($deadline);
                $today = new DateTime('today');
                $deadline_status = '';

                if ($deadline_date == $today) {
                    $deadline_status = 'today';
                } else if ($deadline_date < $today) {
                    $deadline_status = 'overdue';
                }

                echo json_encode([
                    'status' => 'success',
                    'message' => 'Deadline updated successfully',
                    'deadline_status' => $deadline_status
                ]);
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Error updating deadline: ' . $updateStmt->error]);
            }
            break;

        case 'update_assignment_assignee':
            $assignment_id = intval($_POST['assignment_id'] ?? 0);
            $user_id = intval($_POST['user_id'] ?? 0);

            if ($assignment_id <= 0 || $user_id <= 0) {
                echo json_encode(['status' => 'error', 'message' => 'Missing required fields']);
                exit;
            }

            // Get new user details
            $userSql = "SELECT first_name, last_name FROM tbl_users WHERE user_id = ?";
            $userStmt = $conn->prepare($userSql);
            $userStmt->bind_param("i", $user_id);
            $userStmt->execute();
            $userResult = $userStmt->get_result();
            $user = $userResult->fetch_assoc();

            if (!$user) {
                echo json_encode(['status' => 'error', 'message' => 'User not found']);
                exit;
            }

            // Update the assignment user
            $updateSql = "UPDATE tbl_project_assignments 
                        SET user_id = ?, last_updated = NOW() 
                        WHERE assignment_id = ?";
            $updateStmt = $conn->prepare($updateSql);
            $updateStmt->bind_param("ii", $user_id, $assignment_id);

            if ($updateStmt->execute()) {
                echo json_encode([
                    'status' => 'success',
                    'message' => 'Assignee updated successfully',
                    'first_name' => $user['first_name'],
                    'last_name' => $user['last_name']
                ]);
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Error updating assignee: ' . $updateStmt->error]);
            }
            break;

        case 'update_assignment_role':
            if (!isset($_POST['assignment_id']) || !isset($_POST['role_task'])) {
                echo json_encode(['status' => 'error', 'message' => 'Missing required fields']);
                exit;
            }

            $assignmentId = intval($_POST['assignment_id']);
            $roleTask = $_POST['role_task'];

            // Start transaction
            $conn->begin_transaction();

            try {
                // Update the role task in the assignment table
                $updateRoleQuery = "UPDATE tbl_project_assignments SET role_task = ? WHERE assignment_id = ?";
                $stmt = $conn->prepare($updateRoleQuery);
                $stmt->bind_param('si', $roleTask, $assignmentId);

                if (!$stmt->execute()) {
                    throw new Exception('Failed to update assignment role: ' . $conn->error);
                }

                // Update all images assigned to this assignment to have the same role
                $updateImagesQuery = "UPDATE tbl_project_images SET image_role = ? WHERE assignment_id = ?";
                $imgStmt = $conn->prepare($updateImagesQuery);
                $imgStmt->bind_param('si', $roleTask, $assignmentId);

                if (!$imgStmt->execute()) {
                    throw new Exception('Failed to sync image roles: ' . $conn->error);
                }

                // Get number of updated images
                $updatedImageCount = $imgStmt->affected_rows;

                // Commit transaction
                $conn->commit();

                echo json_encode([
                    'status' => 'success',
                    'message' => 'Role updated successfully',
                    'updated_images' => $updatedImageCount
                ]);
            } catch (Exception $e) {
                // Rollback on error
                $conn->rollback();
                echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
            }
            break;

        case 'mark_delay_acceptable':
            // Validate assignment ID
            if (!isset($_POST['assignment_id']) || empty($_POST['assignment_id'])) {
                echo json_encode([
                    'status' => 'error',
                    'message' => 'Assignment ID is required'
                ]);
                exit;
            }

            $assignment_id = $_POST['assignment_id'];

            try {
                // Start transaction
                $conn->begin_transaction();

                // Update assignment to mark delay as acceptable
                $sql = "UPDATE tbl_project_assignments 
                        SET delay_acceptable = 1, 
                            last_updated = NOW() 
                        WHERE assignment_id = ?";

                $stmt = $conn->prepare($sql);
                if (!$stmt) {
                    throw new Exception("Error preparing statement: " . $conn->error);
                }

                $stmt->bind_param("i", $assignment_id);
                $result = $stmt->execute();

                if (!$result) {
                    throw new Exception("Error executing query: " . $stmt->error);
                }

                // Check if update was successful
                if ($stmt->affected_rows > 0) {
                    // Get assignment details for response
                    $get_assignment = "SELECT pa.*, p.project_title, u.first_name, u.last_name 
                    FROM tbl_project_assignments pa
                    JOIN tbl_projects p ON pa.project_id = p.project_id
                    JOIN tbl_users u ON pa.user_id = u.user_id
                    WHERE pa.assignment_id = ?";



                    $stmt_details = $conn->prepare($get_assignment);
                    $stmt_details->bind_param("i", $assignment_id);
                    $stmt_details->execute();
                    $result_details = $stmt_details->get_result();
                    $assignment = $result_details->fetch_assoc();

                    // Commit transaction
                    $conn->commit();

                    // Return success response
                    echo json_encode([
                        'status' => 'success',
                        'message' => 'Delay marked as acceptable',
                        'assignment' => $assignment
                    ]);
                } else {
                    throw new Exception("Assignment not found or no changes made");
                }
            } catch (Exception $e) {
                // Rollback transaction on error
                $conn->rollback();

                // Log error
                error_log("Error marking delay as acceptable: " . $e->getMessage());

                // Return error response
                echo json_encode([
                    'status' => 'error',
                    'message' => 'Failed to mark delay as acceptable: ' . $e->getMessage()
                ]);
            }
            exit;

        case 'remove_assigned_images':
            if (!isset($_POST['assignment_id'])) {
                echo json_encode(['status' => 'error', 'message' => 'Missing assignment ID']);
                exit;
            }

            $assignmentId = intval($_POST['assignment_id']);

            // Start transaction
            $conn->begin_transaction();

            try {
                // Get the project ID from the assignment
                $getAssignmentQuery = "SELECT project_id FROM tbl_project_assignments WHERE assignment_id = ?";
                $stmt = $conn->prepare($getAssignmentQuery);
                $stmt->bind_param('i', $assignmentId);
                $stmt->execute();
                $assignmentResult = $stmt->get_result();

                if ($assignmentResult->num_rows === 0) {
                    throw new Exception('Assignment not found');
                }

                $assignmentData = $assignmentResult->fetch_assoc();
                $projectId = $assignmentData['project_id'];

                // Unassign all images for this assignment
                $unassignImagesQuery = "UPDATE tbl_project_images 
                                       SET assignment_id = NULL, 
                                           status_image = 'available'
                                       WHERE assignment_id = ?";
                $stmt = $conn->prepare($unassignImagesQuery);
                $stmt->bind_param('i', $assignmentId);

                if (!$stmt->execute()) {
                    throw new Exception('Failed to unassign images: ' . $conn->error);
                }

                // Update the assignment's assigned images count to 0
                $updateAssignmentQuery = "UPDATE tbl_project_assignments 
                                         SET assigned_images = 0 
                                         WHERE assignment_id = ?";
                $stmt = $conn->prepare($updateAssignmentQuery);
                $stmt->bind_param('i', $assignmentId);

                if (!$stmt->execute()) {
                    throw new Exception('Failed to update assignment count: ' . $conn->error);
                }

                // Commit transaction
                $conn->commit();

                echo json_encode([
                    'status' => 'success',
                    'message' => 'All images have been unassigned successfully'
                ]);
            } catch (Exception $e) {
                // Rollback transaction on error
                $conn->rollback();
                echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
            }
            break;

        case 'remove_all_images':
            if (!isset($_POST['project_id'])) {
                echo json_encode(['status' => 'error', 'message' => 'Missing project ID']);
                exit;
            }

            $projectId = intval($_POST['project_id']);

            // Start transaction
            $conn->begin_transaction();

            try {
                // First, get all image paths to delete files if needed
                $getImagesQuery = "SELECT image_path FROM tbl_project_images WHERE project_id = ?";
                $stmt = $conn->prepare($getImagesQuery);
                $stmt->bind_param('i', $projectId);
                $stmt->execute();
                $result = $stmt->get_result();

                $imagePaths = [];
                while ($row = $result->fetch_assoc()) {
                    $imagePaths[] = $row['image_path'];
                }

                // Next, update any assignments related to this project to have 0 assigned images
                $updateAssignmentsQuery = "UPDATE tbl_project_assignments 
                                          SET assigned_images = 0 
                                          WHERE project_id = ?";
                $stmt = $conn->prepare($updateAssignmentsQuery);
                $stmt->bind_param('i', $projectId);

                if (!$stmt->execute()) {
                    throw new Exception('Failed to update assignments: ' . $conn->error);
                }

                // Delete all images from the database
                $deleteImagesQuery = "DELETE FROM tbl_project_images WHERE project_id = ?";
                $stmt = $conn->prepare($deleteImagesQuery);
                $stmt->bind_param('i', $projectId);

                if (!$stmt->execute()) {
                    throw new Exception('Failed to delete images: ' . $conn->error);
                }

                $deletedCount = $stmt->affected_rows;

                // Update the project's total_images count to 0
                $updateProjectQuery = "UPDATE tbl_projects SET total_images = 0 WHERE project_id = ?";
                $stmt = $conn->prepare($updateProjectQuery);
                $stmt->bind_param('i', $projectId);

                if (!$stmt->execute()) {
                    throw new Exception('Failed to update project: ' . $conn->error);
                }

                // Commit transaction
                $conn->commit();

                // Optionally delete the physical files
                $uploadDir = '../../uploads/projects/' . $projectId . '/';
                foreach ($imagePaths as $imagePath) {
                    $filePath = $uploadDir . $imagePath;
                    if (file_exists($filePath)) {
                        @unlink($filePath);
                    }
                }

                echo json_encode([
                    'status' => 'success',
                    'message' => 'All ' . $deletedCount . ' images have been removed successfully',
                    'deletedCount' => $deletedCount
                ]);
            } catch (Exception $e) {
                // Rollback transaction on error
                $conn->rollback();
                echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
            }
            break;

        case 'update_project_field':
            $project_id = intval($_POST['project_id'] ?? 0);
            $field_name = $_POST['field_name'] ?? '';
            $field_value = $_POST['field_value'] ?? '';

            if ($project_id <= 0 || empty($field_name)) {
                echo json_encode(['status' => 'error', 'message' => 'Missing required fields']);
                exit;
            }

            logAjaxRequest('update_project_field', [
                'project_id' => $project_id,
                'field_name' => $field_name,
                'field_value' => $field_value
            ]);

            try {
                // Map field names from the form to database columns
                $field_mapping = [
                    'projectName' => 'project_title',
                    'description' => 'description',
                    'company' => 'company_id', // Special case - will need to get/insert company ID
                    'priority' => 'priority',
                    'dateArrived' => 'date_arrived',
                    'deadline' => 'deadline',
                    'status_project' => 'status_project'
                ];

                // Check if the field name is valid
                if (!isset($field_mapping[$field_name]) && $field_name !== 'company') {
                    throw new Exception("Invalid field name: $field_name");
                }

                // Begin transaction
                $conn->begin_transaction();

                // Special handling for company field
                if ($field_name === 'company') {
                    // Get or create company ID
                    $company_id = 0;
                    $company_name = $field_value;

                    // First check if company exists
                    $companySql = "SELECT company_id FROM tbl_companies WHERE company_name = ?";
                    $companyStmt = $conn->prepare($companySql);
                    if (!$companyStmt) {
                        throw new Exception("Error preparing company statement: " . $conn->error);
                    }

                    $companyStmt->bind_param("s", $company_name);
                    $companyStmt->execute();
                    $companyResult = $companyStmt->get_result();

                    if ($companyResult->num_rows > 0) {
                        // Company exists
                        $companyRow = $companyResult->fetch_assoc();
                        $company_id = $companyRow['company_id'];
                    } else {
                        // Create new company
                        $insertCompanySql = "INSERT INTO tbl_companies (company_name) VALUES (?)";
                        $insertCompanyStmt = $conn->prepare($insertCompanySql);
                        if (!$insertCompanyStmt) {
                            throw new Exception("Error preparing insert company statement: " . $conn->error);
                        }

                        $insertCompanyStmt->bind_param("s", $company_name);
                        if (!$insertCompanyStmt->execute()) {
                            throw new Exception("Error inserting company: " . $insertCompanyStmt->error);
                        }

                        $company_id = $conn->insert_id;
                    }

                    // Now update the project with company ID
                    $updateSql = "UPDATE tbl_projects SET company_id = ?, date_updated = NOW() WHERE project_id = ?";
                    $updateStmt = $conn->prepare($updateSql);
                    if (!$updateStmt) {
                        throw new Exception("Error preparing update statement: " . $conn->error);
                    }

                    $updateStmt->bind_param("ii", $company_id, $project_id);
                } else {
                    // For other fields, do a direct update
                    $db_field = $field_mapping[$field_name];

                    $updateSql = "UPDATE tbl_projects SET $db_field = ?, date_updated = NOW() WHERE project_id = ?";
                    $updateStmt = $conn->prepare($updateSql);
                    if (!$updateStmt) {
                        throw new Exception("Error preparing update statement: " . $conn->error);
                    }

                    $updateStmt->bind_param("si", $field_value, $project_id);
                }

                if (!$updateStmt->execute()) {
                    throw new Exception("Error updating project: " . $updateStmt->error);
                }

                // Commit transaction
                $conn->commit();

                echo json_encode([
                    'status' => 'success',
                    'message' => 'Project updated successfully'
                ]);
            } catch (Exception $e) {
                // Roll back transaction on error
                if (isInTransaction($conn)) {
                    $conn->rollback();
                }

                echo json_encode([
                    'status' => 'error',
                    'message' => $e->getMessage()
                ]);

                error_log("Error updating project field: " . $e->getMessage());
            }
            break;

        case 'check_all_assignments_completed':
            if (!isset($_POST['project_id'])) {
                echo json_encode(['status' => 'error', 'message' => 'Missing project ID']);
                exit;
            }

            $projectId = intval($_POST['project_id']);

            try {
                // Get current project status
                $statusQuery = "SELECT status_project FROM tbl_projects WHERE project_id = ?";
                $statusStmt = $conn->prepare($statusQuery);
                $statusStmt->bind_param('i', $projectId);
                $statusStmt->execute();
                $statusResult = $statusStmt->get_result();
                $statusRow = $statusResult->fetch_assoc();
                $currentStatus = $statusRow['status_project'];

                // Check if all assignments for this project are completed
                $checkQuery = "SELECT COUNT(*) as total, 
                               SUM(CASE WHEN status_assignee = 'completed' THEN 1 ELSE 0 END) as completed 
                               FROM tbl_project_assignments WHERE project_id = ?";
                $stmt = $conn->prepare($checkQuery);
                $stmt->bind_param('i', $projectId);
                $stmt->execute();
                $result = $stmt->get_result();
                $counts = $result->fetch_assoc();

                $allCompleted = ($counts['total'] > 0 && $counts['total'] == $counts['completed']);

                // If all are completed AND the project wasn't delayed, update the status to completed
                // If the project was delayed, keep it as delayed even if all tasks are now completed
                if ($allCompleted && $currentStatus !== 'delayed') {
                    $updateQuery = "UPDATE tbl_projects SET status_project = 'completed', date_updated = NOW() WHERE project_id = ?";
                    $stmt = $conn->prepare($updateQuery);
                    $stmt->bind_param('i', $projectId);
                    $stmt->execute();

                    error_log("Project $projectId marked as completed - all assignments done");
                }

                echo json_encode([
                    'status' => 'success',
                    'all_completed' => $allCompleted,
                    'current_status' => $currentStatus,
                    'total' => $counts['total'],
                    'completed' => $counts['completed']
                ]);
            } catch (Exception $e) {
                echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
            }
            break;

        case 'update_image_assignee':
            $image_id = isset($_POST['image_id']) ? intval($_POST['image_id']) : 0;
            $project_id = isset($_POST['project_id']) ? intval($_POST['project_id']) : 0;

            // Check if user_id exists in the request - if not, this is an unassignment
            $is_unassign = !isset($_POST['user_id']);
            $user_id = isset($_POST['user_id']) ? intval($_POST['user_id']) : 0;

            // Validate image_id and project_id (these are always required)
            if (!$image_id || !$project_id) {
                echo json_encode(['status' => 'error', 'message' => 'Invalid image ID or project ID']);
                exit;
            }

            // Only validate user_id if this is not an unassignment
            if (!$is_unassign && !$user_id) {
                echo json_encode(['status' => 'error', 'message' => 'Invalid user ID']);
                exit;
            }

            // Begin transaction
            $conn->begin_transaction();

            try {
                // First, get the current assignment ID and role from the image
                $currentSql = "SELECT assignment_id, image_role FROM tbl_project_images WHERE image_id = ?";
                $currentStmt = $conn->prepare($currentSql);
                $currentStmt->bind_param("i", $image_id);
                $currentStmt->execute();
                $currentResult = $currentStmt->get_result();
                $currentData = $currentResult->fetch_assoc();
                $currentAssignmentId = $currentData['assignment_id'] ?? 0;
                $imageRole = $currentData['image_role'] ?? '';

                // Handle unassignment case
                if ($is_unassign) {
                    // Update the image to remove assignment and set status to unassigned
                    $unassignSql = "UPDATE tbl_project_images 
                                   SET assignment_id = NULL, status_image = 'unassigned' 
                                   WHERE image_id = ? AND project_id = ?";
                    $unassignStmt = $conn->prepare($unassignSql);
                    $unassignStmt->bind_param("ii", $image_id, $project_id);

                    if (!$unassignStmt->execute()) {
                        throw new Exception("Error unassigning image: " . $unassignStmt->error);
                    }

                    // Update the old assignment count if it exists
                    if ($currentAssignmentId > 0) {
                        $countSql = "UPDATE tbl_project_assignments 
                                   SET assigned_images = (
                                       SELECT COUNT(*) FROM tbl_project_images 
                                       WHERE assignment_id = ? AND project_id = ?
                                   ) 
                                   WHERE assignment_id = ?";
                        $countStmt = $conn->prepare($countSql);
                        $countStmt->bind_param("iii", $currentAssignmentId, $project_id, $currentAssignmentId);
                        $countStmt->execute();

                        // Check if the old assignment now has 0 images, if yes, delete it
                        $checkEmptySql = "SELECT COUNT(*) as img_count FROM tbl_project_images WHERE assignment_id = ?";
                        $checkEmptyStmt = $conn->prepare($checkEmptySql);
                        $checkEmptyStmt->bind_param("i", $currentAssignmentId);
                        $checkEmptyStmt->execute();
                        $emptyResult = $checkEmptyStmt->get_result();
                        $imgCount = $emptyResult->fetch_assoc()['img_count'];

                        if ($imgCount == 0) {
                            // Delete the empty assignment
                            $deleteEmptySql = "DELETE FROM tbl_project_assignments WHERE assignment_id = ?";
                            $deleteEmptyStmt = $conn->prepare($deleteEmptySql);
                            $deleteEmptyStmt->bind_param("i", $currentAssignmentId);
                            $deleteEmptyStmt->execute();
                        }
                    }

                    // Commit transaction
                    $conn->commit();

                    echo json_encode([
                        'status' => 'success',
                        'message' => 'Image unassigned successfully'
                    ]);
                }
                // Handle assignment case
                else {
                    // Check if the user already has an assignment for this project with the same role
                    $checkSql = "SELECT assignment_id FROM tbl_project_assignments 
                               WHERE project_id = ? AND user_id = ? AND status_assignee != 'deleted'";
                    $checkStmt = $conn->prepare($checkSql);
                    $checkStmt->bind_param("ii", $project_id, $user_id);
                    $checkStmt->execute();
                    $checkResult = $checkStmt->get_result();
                    $assignment = $checkResult->fetch_assoc();

                    $new_assignment_id = 0;

                    if ($assignment) {
                        // Use existing assignment
                        $new_assignment_id = $assignment['assignment_id'];
                    } else {
                        // Create new assignment
                        $createSql = "INSERT INTO tbl_project_assignments 
                                    (project_id, user_id, role_task, status_assignee, deadline) 
                                    VALUES (?, ?, ?, 'pending', NOW() + INTERVAL 7 DAY)";
                        $createStmt = $conn->prepare($createSql);
                        $createStmt->bind_param("iis", $project_id, $user_id, $imageRole);

                        if (!$createStmt->execute()) {
                            throw new Exception("Error creating assignment: " . $createStmt->error);
                        }

                        $new_assignment_id = $conn->insert_id;
                    }

                    // Update the image with the new assignment ID and status
                    $updateSql = "UPDATE tbl_project_images 
                                 SET assignment_id = ?, status_image = 'assigned' 
                                 WHERE image_id = ? AND project_id = ?";
                    $updateStmt = $conn->prepare($updateSql);
                    $updateStmt->bind_param("iii", $new_assignment_id, $image_id, $project_id);

                    if (!$updateStmt->execute()) {
                        throw new Exception("Error updating image: " . $updateStmt->error);
                    }

                    // Update the assigned images count for BOTH old and new assignment
                    // First update the new assignment count
                    $countSql = "UPDATE tbl_project_assignments 
                               SET assigned_images = (
                                   SELECT COUNT(*) FROM tbl_project_images 
                                   WHERE assignment_id = ? AND project_id = ?
                               ) 
                               WHERE assignment_id = ?";

                    $countStmt = $conn->prepare($countSql);
                    // Update new assignment count
                    $countStmt->bind_param("iii", $new_assignment_id, $project_id, $new_assignment_id);
                    $countStmt->execute();

                    // Also update the old assignment count if it exists
                    if ($currentAssignmentId > 0 && $currentAssignmentId != $new_assignment_id) {
                        $countStmt->bind_param("iii", $currentAssignmentId, $project_id, $currentAssignmentId);
                        $countStmt->execute();

                        // Check if the old assignment now has 0 images, if yes, delete it
                        $checkEmptySql = "SELECT COUNT(*) as img_count FROM tbl_project_images WHERE assignment_id = ?";
                        $checkEmptyStmt = $conn->prepare($checkEmptySql);
                        $checkEmptyStmt->bind_param("i", $currentAssignmentId);
                        $checkEmptyStmt->execute();
                        $emptyResult = $checkEmptyStmt->get_result();
                        $imgCount = $emptyResult->fetch_assoc()['img_count'];

                        if ($imgCount == 0) {
                            // Delete the empty assignment
                            $deleteEmptySql = "DELETE FROM tbl_project_assignments WHERE assignment_id = ?";
                            $deleteEmptyStmt = $conn->prepare($deleteEmptySql);
                            $deleteEmptyStmt->bind_param("i", $currentAssignmentId);
                            $deleteEmptyStmt->execute();
                        }
                    }

                    // Commit transaction
                    $conn->commit();

                    // Get assignee name for response
                    $nameSql = "SELECT first_name, last_name FROM tbl_users WHERE user_id = ?";
                    $nameStmt = $conn->prepare($nameSql);
                    $nameStmt->bind_param("i", $user_id);
                    $nameStmt->execute();
                    $nameResult = $nameStmt->get_result();
                    $user = $nameResult->fetch_assoc();

                    echo json_encode([
                        'status' => 'success',
                        'message' => 'Image assignee updated successfully',
                        'assignment_id' => $new_assignment_id,
                        'user_name' => $user['first_name'] . ' ' . $user['last_name']
                    ]);
                }
            } catch (Exception $e) {
                // Roll back transaction on error
                $conn->rollback();
                echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
                error_log("Error updating image assignee: " . $e->getMessage());
            }
            break;

        case 'upload_images':
            try {
                // Begin transaction
                $conn->begin_transaction();

                // Validate project ID
                if (!isset($_POST['project_id']) || empty($_POST['project_id'])) {
                    throw new Exception("Project ID is required");
                }

                $project_id = intval($_POST['project_id']);

                // Check if project exists
                $checkProject = $conn->prepare("SELECT project_id FROM tbl_projects WHERE project_id = ?");
                $checkProject->bind_param("i", $project_id);
                $checkProject->execute();
                $projectResult = $checkProject->get_result();

                if ($projectResult->num_rows === 0) {
                    throw new Exception("Project not found");
                }

                // Check if files were uploaded
                if (!isset($_FILES['images']) || empty($_FILES['images']['name'][0])) {
                    throw new Exception("No images were uploaded");
                }

                // Create upload directory if it doesn't exist
                $upload_dir = "../uploads/projects/{$project_id}/";
                if (!file_exists($upload_dir)) {
                    if (!mkdir($upload_dir, 0755, true)) {
                        throw new Exception("Failed to create upload directory");
                    }
                }

                // Process each uploaded file
                $uploaded_images = 0;
                $files = $_FILES['images'];
                $allowed_types = array('image/jpeg', 'image/png', 'image/gif', 'image/jpg');

                for ($i = 0; $i < count($files['name']); $i++) {
                    $file_name = $files['name'][$i];
                    $file_tmp = $files['tmp_name'][$i];
                    $file_type = $files['type'][$i];
                    $file_size = $files['size'][$i];
                    $file_error = $files['error'][$i];

                    // Skip files with errors
                    if ($file_error !== UPLOAD_ERR_OK) {
                        continue;
                    }

                    // Validate file type
                    if (!in_array($file_type, $allowed_types)) {
                        continue;
                    }

                    // Generate unique filename to prevent overwriting
                    $file_extension = pathinfo($file_name, PATHINFO_EXTENSION);
                    $new_file_name = $file_name; // Use original file name
                    $upload_path = $upload_dir . $new_file_name;

                    // If file with same name exists, add timestamp to make it unique
                    if (file_exists($upload_path)) {
                        $timestamp = time();
                        $new_file_name = pathinfo($file_name, PATHINFO_FILENAME) . "_{$timestamp}." . $file_extension;
                        $upload_path = $upload_dir . $new_file_name;
                    }

                    // Upload the file
                    if (move_uploaded_file($file_tmp, $upload_path)) {
                        // Get batch ID from the posted data, default to 1 if not provided
                        $batch_id = isset($_POST['batch_id']) ? intval($_POST['batch_id']) : 1;

                        // Insert into database
                        $insertStmt = $conn->prepare("INSERT INTO tbl_project_images (project_id, user_id, image_path, file_type, file_size, upload_date, status_image, batch_id) VALUES (?, ?, ?, ?, ?, NOW(), 'available', ?)");
                        $user_id = 0; // Default user ID or you can use the current user's ID
                        $insertStmt->bind_param("iissis", $project_id, $user_id, $new_file_name, $file_type, $file_size, $batch_id);

                        if ($insertStmt->execute()) {
                            $uploaded_images++;
                        }
                    }
                }

                // Update project total images count
                $updateProject = $conn->prepare("UPDATE tbl_projects SET total_images = (SELECT COUNT(*) FROM tbl_project_images WHERE project_id = ?) WHERE project_id = ?");
                $updateProject->bind_param("ii", $project_id, $project_id);
                $updateProject->execute();

                // Commit transaction
                $conn->commit();

                echo json_encode([
                    'status' => 'success',
                    'message' => "Successfully uploaded {$uploaded_images} images",
                    'count' => $uploaded_images
                ]);
            } catch (Exception $e) {
                // Rollback transaction on error
                $conn->rollback();
                echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
                error_log("Error uploading images: " . $e->getMessage());
            }
            break;

        case 'create_assignment':
            // Start output buffering to catch any PHP errors/warnings
            ob_start();

            $project_id = intval($_POST['project_id'] ?? 0);
            $user_id = intval($_POST['user_id'] ?? 0);
            $role_task = $_POST['role_task'] ?? '';
            $deadline = $_POST['deadline'] ?? '';
            $image_ids = json_decode($_POST['image_ids'] ?? '[]', true);

            // Log debug info
            error_log("Creating assignment: project_id=$project_id, user_id=$user_id, role_task=$role_task, deadline=$deadline, images=" . count($image_ids));

            if ($project_id <= 0 || $user_id <= 0 || empty($role_task) || empty($deadline) || empty($image_ids)) {
                // Discard any buffered output
                ob_end_clean();
                echo json_encode(['status' => 'error', 'message' => 'Missing required fields']);
                exit;
            }

            // Begin transaction
            $conn->begin_transaction();

            try {
                // Check for already assigned images
                $assignedSql = "SELECT i.image_id, i.assignment_id, a.user_id, 
                               CONCAT(u.first_name, ' ', u.last_name) as assignee_name
                               FROM tbl_project_images i
                               JOIN tbl_project_assignments a ON i.assignment_id = a.assignment_id
                               JOIN tbl_users u ON a.user_id = u.user_id
                               WHERE i.image_id IN (" . implode(',', array_fill(0, count($image_ids), '?')) . ")
                               AND i.assignment_id IS NOT NULL";

                $assignedStmt = $conn->prepare($assignedSql);

                if (!$assignedStmt) {
                    throw new Exception("Error preparing statement: " . $conn->error);
                }

                // Bind all image IDs
                $bindTypes = str_repeat('i', count($image_ids));
                $bindParams = array_merge([$bindTypes], $image_ids);
                call_user_func_array([$assignedStmt, 'bind_param'], $bindParams);

                $assignedStmt->execute();
                $assignedResult = $assignedStmt->get_result();

                $assignedImages = [];
                while ($row = $assignedResult->fetch_assoc()) {
                    $assignedImages[$row['image_id']] = $row;
                }

                // Check if the user already has an assignment for this project with the same role
                $checkSql = "SELECT assignment_id FROM tbl_project_assignments 
                           WHERE project_id = ? AND user_id = ? AND role_task = ?";
                $checkStmt = $conn->prepare($checkSql);
                $checkStmt->bind_param("iis", $project_id, $user_id, $role_task);
                $checkStmt->execute();
                $checkResult = $checkStmt->get_result();
                $existingAssignment = $checkResult->fetch_assoc();

                if ($existingAssignment) {
                    // Use existing assignment
                    $assignment_id = $existingAssignment['assignment_id'];
                } else {
                    // Insert new assignment
                    $sql = "INSERT INTO tbl_project_assignments 
                            (project_id, user_id, role_task, status_assignee, deadline, assigned_images, is_hidden) 
                            VALUES (?, ?, ?, 'pending', ?, 0, 0)";

                    $stmt = $conn->prepare($sql);
                    $stmt->bind_param("iiss", $project_id, $user_id, $role_task, $deadline);

                    if (!$stmt->execute()) {
                        throw new Exception("Error creating assignment: " . $stmt->error);
                    }

                    $assignment_id = $conn->insert_id;
                }

                // Update the user's name for the response
                $userSql = "SELECT CONCAT(first_name, ' ', last_name) as full_name FROM tbl_users WHERE user_id = ?";
                $userStmt = $conn->prepare($userSql);
                $userStmt->bind_param("i", $user_id);
                $userStmt->execute();
                $userResult = $userStmt->get_result();
                $user = $userResult->fetch_assoc();
                $user_name = $user ? $user['full_name'] : 'Unknown User';

                // Update images with the assignment
                $updateSql = "UPDATE tbl_project_images 
                             SET assignment_id = ?, status_image = 'assigned', image_role = ? 
                             WHERE image_id = ? AND project_id = ?";
                $updateStmt = $conn->prepare($updateSql);

                foreach ($image_ids as $image_id) {
                    $updateStmt->bind_param("isii", $assignment_id, $role_task, $image_id, $project_id);
                    if (!$updateStmt->execute()) {
                        throw new Exception("Error updating image: " . $updateStmt->error);
                    }
                }

                // Update the assigned_images count for the assignment
                $countSql = "UPDATE tbl_project_assignments 
                           SET assigned_images = (
                               SELECT COUNT(*) FROM tbl_project_images 
                               WHERE assignment_id = ? AND project_id = ?
                           ) 
                           WHERE assignment_id = ?";
                $countStmt = $conn->prepare($countSql);
                $countStmt->bind_param("iii", $assignment_id, $project_id, $assignment_id);
                $countStmt->execute();

                // Also update the count for any other assignments that had images transferred
                if (!empty($assignedImages)) {
                    $uniqueAssignmentIds = array_unique(array_column($assignedImages, 'assignment_id'));
                    foreach ($uniqueAssignmentIds as $oldAssignmentId) {
                        $countStmt->bind_param("iii", $oldAssignmentId, $project_id, $oldAssignmentId);
                        $countStmt->execute();

                        // Check if this assignment now has 0 images, if yes, delete it
                        $checkEmptySql = "SELECT COUNT(*) as img_count FROM tbl_project_images WHERE assignment_id = ?";
                        $checkEmptyStmt = $conn->prepare($checkEmptySql);
                        $checkEmptyStmt->bind_param("i", $oldAssignmentId);
                        $checkEmptyStmt->execute();
                        $emptyResult = $checkEmptyStmt->get_result();
                        $imgCount = $emptyResult->fetch_assoc()['img_count'];

                        if ($imgCount == 0) {
                            // Delete the empty assignment
                            $deleteEmptySql = "DELETE FROM tbl_project_assignments WHERE assignment_id = ?";
                            $deleteEmptyStmt = $conn->prepare($deleteEmptySql);
                            $deleteEmptyStmt->bind_param("i", $oldAssignmentId);
                            $deleteEmptyStmt->execute();
                        }
                    }
                }

                // Update project status to in_progress if it's pending
                $updateProjectSql = "UPDATE tbl_projects 
                                   SET status_project = CASE 
                                       WHEN status_project = 'pending' THEN 'in_progress' 
                                       ELSE status_project 
                                   END,
                                   date_updated = NOW()
                                   WHERE project_id = ?";
                $updateProjectStmt = $conn->prepare($updateProjectSql);
                $updateProjectStmt->bind_param("i", $project_id);
                $updateProjectStmt->execute();

                // Commit transaction
                $conn->commit();

                // Prepare response with information about reassigned images
                $response = [
                    'status' => 'success',
                    'message' => 'Assignment created and images assigned successfully',
                    'assignment_id' => $assignment_id
                ];

                if (!empty($assignedImages)) {
                    $response['reassigned_images'] = count($assignedImages);
                    $response['reassigned_from'] = array_unique(array_column($assignedImages, 'assignee_name'));
                }

                // Discard any buffered output
                ob_end_clean();
                echo json_encode($response);
            } catch (Exception $e) {
                // Roll back transaction
                $conn->rollback();

                // Discard any buffered output
                ob_end_clean();
                echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
                error_log("Assignment creation error: " . $e->getMessage());
            }
            break;

        default:
            echo json_encode(['status' => 'error', 'message' => 'Invalid action']);
            break;
    }

    exit;
} else {
    // Not an AJAX request
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method']);
    exit;
}
