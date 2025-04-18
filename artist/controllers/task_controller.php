<?php
// Task Controller for Artist
// This controller handles AJAX requests for task operations

session_start();
require_once '../../includes/db_connection.php';

// Set content type to JSON
header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'User not logged in']);
    exit;
}

// Get the current user's ID
$user_id = $_SESSION['user_id'];

/**
 * Function to ensure the activity_logs table exists
 * @param mysqli $conn Database connection
 * @return bool True if table exists or was created, false on error
 */
function ensure_activity_logs_table($conn) {
    // Check if table exists
    $check_table_query = "SHOW TABLES LIKE 'activity_logs'";
    $check_table_result = $conn->query($check_table_query);
    
    if ($check_table_result->num_rows > 0) {
        // Table already exists
        return true;
    }
    
    // Create the table if it doesn't exist
    $create_table_query = "CREATE TABLE IF NOT EXISTS `activity_logs` (
        `log_id` int(11) NOT NULL AUTO_INCREMENT,
        `user_id` int(11) NOT NULL,
        `activity_type` varchar(50) NOT NULL,
        `entity_id` int(11) NOT NULL,
        `entity_type` varchar(50) NOT NULL,
        `details` text,
        `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (`log_id`),
        KEY `user_id` (`user_id`),
        KEY `entity_id` (`entity_id`),
        KEY `entity_type` (`entity_type`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
    
    if ($conn->query($create_table_query)) {
        return true;
    } else {
        // Failed to create table, but we'll continue without logging
        return false;
    }
}

// Handle different actions
if (isset($_POST['action'])) {
    switch ($_POST['action']) {
        case 'start_task':
            if (isset($_POST['assignment_id'])) {
                $assignment_id = $_POST['assignment_id'];
                
                // Verify assignment belongs to user
                $verify_query = "SELECT pa.assignment_id 
                               FROM tbl_project_assignments pa 
                               WHERE pa.assignment_id = ? AND pa.user_id = ?";
                $verify_stmt = $conn->prepare($verify_query);
                $verify_stmt->bind_param("ii", $assignment_id, $user_id);
                $verify_stmt->execute();
                $verify_result = $verify_stmt->get_result();
                
                if ($verify_result->num_rows === 0) {
                    echo json_encode(['status' => 'error', 'message' => 'Assignment not found or not assigned to you']);
                    exit;
                }
                
                // Update assignment status
                $update_query = "UPDATE tbl_project_assignments 
                               SET status = 'in_progress', 
                                   updated_at = NOW() 
                               WHERE assignment_id = ?";
                $update_stmt = $conn->prepare($update_query);
                $update_stmt->bind_param("i", $assignment_id);
                
                if ($update_stmt->execute()) {
                    // Update associated images status
                    $update_images_query = "UPDATE tbl_project_images 
                                          SET status_image = 'in_progress' 
                                          WHERE assignment_id = ?";
                    $update_images_stmt = $conn->prepare($update_images_query);
                    $update_images_stmt->bind_param("i", $assignment_id);
                    $update_images_stmt->execute();
                    
                    // Log activity
                    if (ensure_activity_logs_table($conn)) {
                        $log_query = "INSERT INTO activity_logs (user_id, activity_type, entity_id, entity_type, details) 
                                    VALUES (?, 'assignment_started', ?, 'assignment', ?)";
                        $log_stmt = $conn->prepare($log_query);
                        $details = "Assignment ID: " . $assignment_id . " started";
                        $log_stmt->bind_param("iis", $user_id, $assignment_id, $details);
                        $log_stmt->execute();
                    }
                    
                    echo json_encode(['status' => 'success', 'message' => 'Assignment started successfully']);
                } else {
                    echo json_encode(['status' => 'error', 'message' => 'Failed to start assignment: ' . $conn->error]);
                }
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Assignment ID not provided']);
            }
            break;
            
        case 'complete_task':
            if (isset($_POST['assignment_id'])) {
                $assignment_id = $_POST['assignment_id'];
                
                // Verify assignment belongs to user
                $verify_query = "SELECT pa.assignment_id 
                               FROM tbl_project_assignments pa 
                               WHERE pa.assignment_id = ? AND pa.user_id = ?";
                $verify_stmt = $conn->prepare($verify_query);
                $verify_stmt->bind_param("ii", $assignment_id, $user_id);
                $verify_stmt->execute();
                $verify_result = $verify_stmt->get_result();
                
                if ($verify_result->num_rows === 0) {
                    echo json_encode(['status' => 'error', 'message' => 'Assignment not found or not assigned to you']);
                    exit;
                }
                
                // Update assignment status
                $update_query = "UPDATE tbl_project_assignments 
                               SET status = 'completed', 
                                   updated_at = NOW() 
                               WHERE assignment_id = ?";
                $update_stmt = $conn->prepare($update_query);
                $update_stmt->bind_param("i", $assignment_id);
                
                if ($update_stmt->execute()) {
                    // Update associated images status
                    $update_images_query = "UPDATE tbl_project_images 
                                          SET status_image = 'completed' 
                                          WHERE assignment_id = ?";
                    $update_images_stmt = $conn->prepare($update_images_query);
                    $update_images_stmt->bind_param("i", $assignment_id);
                    $update_images_stmt->execute();
                    
                    // Log activity
                    if (ensure_activity_logs_table($conn)) {
                        $log_query = "INSERT INTO activity_logs (user_id, activity_type, entity_id, entity_type, details) 
                                    VALUES (?, 'assignment_completed', ?, 'assignment', ?)";
                        $log_stmt = $conn->prepare($log_query);
                        $details = "Assignment ID: " . $assignment_id . " completed";
                        $log_stmt->bind_param("iis", $user_id, $assignment_id, $details);
                        $log_stmt->execute();
                    }
                    
                    echo json_encode(['status' => 'success', 'message' => 'Assignment completed successfully']);
                } else {
                    echo json_encode(['status' => 'error', 'message' => 'Failed to complete assignment: ' . $conn->error]);
                }
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Assignment ID not provided']);
            }
            break;
            
        default:
            echo json_encode(['status' => 'error', 'message' => 'Invalid action']);
            break;
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'No action specified']);
}
?> 