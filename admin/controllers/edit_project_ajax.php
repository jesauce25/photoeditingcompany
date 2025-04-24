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

// Function to log AJAX requests for debugging
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
                $assignmentQuery = "SELECT project_id, status_assignee FROM tbl_project_assignments WHERE assignment_id = ?";
                $stmt = $conn->prepare($assignmentQuery);
                $stmt->bind_param('i', $assignmentId);
                $stmt->execute();
                $result = $stmt->get_result();

                if ($result->num_rows === 0) {
                    throw new Exception('Assignment not found');
                }

                $assignmentData = $result->fetch_assoc();
                $projectId = $assignmentData['project_id'];

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
                if ($conn->inTransaction()) {
                    $conn->rollback();
                }
                echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
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
            logAjaxRequest('upload_project_images', $_POST, $_FILES);

            // Validate project ID
            $project_id = isset($_POST['project_id']) ? intval($_POST['project_id']) : 0;
            if ($project_id <= 0) {
                echo json_encode(['status' => 'error', 'message' => 'Invalid project ID']);
                exit;
            }

            // Check if project exists
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
                $file_ext = pathinfo($original_filename, PATHINFO_EXTENSION);
                $base_name = pathinfo($original_filename, PATHINFO_FILENAME);

                // Handle filename conflicts by adding a counter suffix if needed
                $new_filename = $original_filename;
                $counter = 1;

                while (file_exists($project_upload_dir . $new_filename)) {
                    $new_filename = $base_name . "(" . $counter . ")." . $file_ext;
                    $counter++;
                }

                // Move the uploaded file
                if (move_uploaded_file($files['tmp_name'][$i], $project_upload_dir . $new_filename)) {
                    // Insert into database - Note: no file_name column anymore
                    $sql = "INSERT INTO tbl_project_images (project_id, image_path, file_type, file_size, status_image) 
                           VALUES (?, ?, ?, ?, 'available')";
                    $stmt = $conn->prepare($sql);

                    $file_type = $files['type'][$i];
                    $file_size = $files['size'][$i];
                    $image_path = $new_filename; // Store just the filename, not the full path

                    $stmt->bind_param("issi", $project_id, $image_path, $file_type, $file_size);

                    if ($stmt->execute()) {
                        $uploaded_count++;
                    } else {
                        $failed_count++;
                        $errors[] = "Database error for file {$original_filename}: " . $stmt->error;
                        // Remove the file if database insertion fails
                        @unlink($project_upload_dir . $new_filename);
                    }
                } else {
                    $failed_count++;
                    $errors[] = "Failed to move uploaded file {$original_filename}";
                }
            }

            // Update the total_images count in the project
            if ($uploaded_count > 0) {
                $update_sql = "UPDATE tbl_projects SET total_images = (SELECT COUNT(*) FROM tbl_project_images WHERE project_id = ?) WHERE project_id = ?";
                $update_stmt = $conn->prepare($update_sql);
                $update_stmt->bind_param("ii", $project_id, $project_id);
                $update_stmt->execute();

                // Update project status if needed
                if (function_exists('updateProjectStatus')) {
                    updateProjectStatus($project_id);
                }
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

            if ($project_id <= 0 || $assignment_id <= 0) {
                echo json_encode(['status' => 'error', 'message' => 'Invalid project or assignment ID']);
                exit;
            }

            // Get images assigned to this assignment
            $sql = "SELECT i.image_id, i.image_path, i.status_image, i.file_type, i.upload_date,
                          i.image_role, i.estimated_time,
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
            break;

        case 'update_image_details':
            $image_id = intval($_POST['image_id'] ?? 0);
            $image_role = $_POST['image_role'] ?? '';

            // Get hours and minutes from the request
            $estimated_hours = isset($_POST['estimated_hours']) ? intval($_POST['estimated_hours']) : 0;
            $estimated_minutes = isset($_POST['estimated_minutes']) ? intval($_POST['estimated_minutes']) : 0;

            // Calculate total minutes for storage in the database
            $estimated_time = ($estimated_hours * 60) + $estimated_minutes;

            if ($image_id <= 0) {
                echo json_encode(['status' => 'error', 'message' => 'Invalid image ID']);
                exit;
            }

            error_log("Updating image details for image ID: $image_id, role: $image_role, hours: $estimated_hours, minutes: $estimated_minutes, total: $estimated_time");

            // Get the assignment status to check if edits are allowed
            $checkSql = "SELECT pa.status_assignee 
                         FROM tbl_project_images pi
                         JOIN tbl_project_assignments pa ON pi.assignment_id = pa.assignment_id
                         WHERE pi.image_id = ?";

            $checkStmt = $conn->prepare($checkSql);
            $checkStmt->bind_param("i", $image_id);
            $checkStmt->execute();
            $checkResult = $checkStmt->get_result();

            if ($checkResult->num_rows > 0) {
                $statusRow = $checkResult->fetch_assoc();

                // Check if editing is allowed based on status
                if ($statusRow['status_assignee'] != 'pending' && $statusRow['status_assignee'] != 'completed') {
                    error_log("Cannot update image details: status is " . $statusRow['status_assignee']);
                    echo json_encode(['status' => 'error', 'message' => 'Cannot edit image details while task is in progress']);
                    exit;
                }
            }

            // Update the image details with the total minutes 
            $sql = "UPDATE tbl_project_images SET 
                    image_role = ?, 
                    estimated_time = ? 
                    WHERE image_id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("sii", $image_role, $estimated_time, $image_id);

            if ($stmt->execute()) {
                error_log("Successfully updated image details for image ID: $image_id");
                echo json_encode([
                    'status' => 'success',
                    'message' => 'Image details updated successfully',
                    'estimated_time' => $estimated_time
                ]);
            } else {
                error_log("Failed to update image details: " . $stmt->error);
                echo json_encode(['status' => 'error', 'message' => 'Failed to update image details: ' . $stmt->error]);
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

                // Update the assignment's assigned_images count to 0
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
                if ($conn->inTransaction()) {
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
