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
function logAjaxRequest($action, $data = null)
{
    $timestamp = date('Y-m-d H:i:s');
    $log = "[$timestamp] AJAX Request: $action";
    if ($data !== null) {
        $log .= " - Data: " . json_encode($data);
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
                
                // Update project status to in_progress if it's pending
                $updateProjectSql = "UPDATE tbl_projects 
                                    SET status_project = CASE 
                                        WHEN status_project = 'pending' THEN 'in_progress' 
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

            $success = true;
            $conn->begin_transaction();

            try {
                // Update all selected images
                $updateSql = "UPDATE tbl_project_images 
                            SET assignment_id = ?, status_image = 'assigned' 
                            WHERE image_id = ? AND project_id = ?";
                $updateStmt = $conn->prepare($updateSql);

                foreach ($image_ids as $image_id) {
                    $updateStmt->bind_param("iii", $assignment_id, $image_id, $project_id);
                    if (!$updateStmt->execute()) {
                        throw new Exception("Error updating image assignment: " . $updateStmt->error);
                    }
                }

                // Update the assignment image count
                updateAssignmentImageCounts($project_id);

                // Commit transaction
                $conn->commit();

                echo json_encode([
                    'status' => 'success',
                    'message' => count($image_ids) . ' images assigned successfully'
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
            $assignment_id = intval($_POST['assignment_id'] ?? 0);
            $status = $_POST['status'] ?? '';

            if ($assignment_id <= 0 || empty($status)) {
                echo json_encode(['status' => 'error', 'message' => 'Missing required fields']);
                exit;
            }

            // Valid status values
            $validStatuses = ['pending', 'in_progress', 'review', 'completed', 'delayed'];
            if (!in_array($status, $validStatuses)) {
                echo json_encode(['status' => 'error', 'message' => 'Invalid status value']);
                exit;
            }

            // Update the assignment status
            $updateSql = "UPDATE tbl_project_assignments 
                        SET status_assignee = ?, last_updated = NOW() 
                        WHERE assignment_id = ?";
            $updateStmt = $conn->prepare($updateSql);
            $updateStmt->bind_param("si", $status, $assignment_id);

            if ($updateStmt->execute()) {
                echo json_encode(['status' => 'success', 'message' => 'Status updated successfully']);
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Error updating status: ' . $updateStmt->error]);
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
                          a.first_name, a.last_name
                   FROM tbl_project_images i
                   LEFT JOIN tbl_project_assignments pa ON i.assignment_id = pa.assignment_id
                   LEFT JOIN tbl_users a ON pa.user_id = a.user_id
                   WHERE i.project_id = ? AND i.assignment_id = ?
                   ORDER BY i.upload_date DESC";

            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ii", $project_id, $assignment_id);
            $stmt->execute();
            $result = $stmt->get_result();

            $images = [];
            while ($row = $result->fetch_assoc()) {
                $images[] = $row;
            }

            // Get assignment details for context
            $assignmentSql = "SELECT pa.assignment_id, pa.role_task, pa.status_assignee, pa.deadline,
                                    u.first_name, u.last_name, CONCAT(u.first_name, ' ', u.last_name) as full_name
                             FROM tbl_project_assignments pa
                             JOIN tbl_users u ON pa.user_id = u.user_id
                             WHERE pa.assignment_id = ? AND pa.project_id = ?";

            $assignmentStmt = $conn->prepare($assignmentSql);
            $assignmentStmt->bind_param("ii", $assignment_id, $project_id);
            $assignmentStmt->execute();
            $assignmentResult = $assignmentStmt->get_result();
            $assignment = $assignmentResult->fetch_assoc();

            echo json_encode([
                'status' => 'success',
                'images' => $images,
                'count' => count($images),
                'assignment' => $assignment
            ]);
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