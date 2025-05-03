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
function ensure_activity_logs_table($conn)
{
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

                // Check if user already has a task in progress
                $check_in_progress_query = "SELECT assignment_id 
                                           FROM tbl_project_assignments 
                                           WHERE user_id = ? AND status_assignee = 'in_progress'";
                $check_stmt = $conn->prepare($check_in_progress_query);
                $check_stmt->bind_param("i", $user_id);
                $check_stmt->execute();
                $check_result = $check_stmt->get_result();

                if ($check_result->num_rows > 0) {
                    echo json_encode([
                        'status' => 'error',
                        'message' => 'You already have a task in progress. Please complete your current task before starting a new one.'
                    ]);
                    exit;
                }

                // Update assignment status
                $update_query = "UPDATE tbl_project_assignments 
                               SET status_assignee = 'in_progress', 
                                   last_updated = NOW() 
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

                    // First, unlock all tasks for this user to reset the locking state
                    $reset_locks_query = "UPDATE tbl_project_assignments 
                                        SET is_locked = 0 
                                        WHERE user_id = ?";
                    $reset_locks_stmt = $conn->prepare($reset_locks_query);
                    $reset_locks_stmt->bind_param("i", $user_id);
                    $reset_locks_stmt->execute();

                    // Check if user has admin protection first (recently unblocked)
                    $protection_query = "SELECT last_unblocked_at FROM tbl_accounts WHERE user_id = ?";
                    $protection_stmt = $conn->prepare($protection_query);
                    $protection_stmt->bind_param("i", $user_id);
                    $protection_stmt->execute();
                    $protection_result = $protection_stmt->get_result();
                    $protection_data = $protection_result->fetch_assoc();

                    $has_protection = false;
                    if ($protection_data && !empty($protection_data['last_unblocked_at'])) {
                        $unblock_time = new DateTime($protection_data['last_unblocked_at']);
                        $now = new DateTime();

                        if ($unblock_time > $now) {
                            $has_protection = true;
                        }
                    }

                    if ($has_protection) {
                        error_log("TASK_CONTROLLER: User $user_id has admin protection - NOT locking tasks");

                        // Ensure account is not blocked during protection period
                        $update_account_query = "UPDATE tbl_accounts SET status = 'Active', has_overdue_tasks = 0 WHERE user_id = ?";
                        $update_account_stmt = $conn->prepare($update_account_query);
                        $update_account_stmt->bind_param("i", $user_id);
                        $update_account_stmt->execute();
                    } else {
                        // Check if there are any overdue tasks
                        $today = date('Y-m-d');
                        $overdue_query = "SELECT assignment_id 
                                         FROM tbl_project_assignments 
                                         WHERE user_id = ? 
                                           AND status_assignee NOT IN ('completed', 'deleted')
                                           AND deadline < ?
                                           AND (delay_acceptable IS NULL OR delay_acceptable != '1')";
                        $overdue_stmt = $conn->prepare($overdue_query);
                        $overdue_stmt->bind_param("is", $user_id, $today);
                        $overdue_stmt->execute();
                        $overdue_result = $overdue_stmt->get_result();

                        if ($overdue_result->num_rows > 0) {
                            // Lock all tasks except:
                            // 1. The current task which is now 'in_progress'
                            // 2. Any tasks already marked as 'completed'
                            $lock_query = "UPDATE tbl_project_assignments 
                                          SET is_locked = 1 
                                          WHERE user_id = ? 
                                            AND status_assignee NOT IN ('completed') 
                                            AND assignment_id != ?";
                            $lock_stmt = $conn->prepare($lock_query);
                            $lock_stmt->bind_param("ii", $user_id, $assignment_id);
                            $lock_result = $lock_stmt->execute();
                            $locked_count = $lock_stmt->affected_rows;

                            // Log locking result
                            error_log("TASK_CONTROLLER: Locking tasks for user $user_id with overdue tasks - Result: " .
                                ($lock_result ? "SUCCESS ($locked_count tasks locked)" : "FAILED: " . $conn->error));

                            // Also update account status to ensure consistency
                            $update_account_query = "UPDATE tbl_accounts SET status = 'Blocked', has_overdue_tasks = 1 WHERE user_id = ?";
                            $update_account_stmt = $conn->prepare($update_account_query);
                            $update_account_stmt->bind_param("i", $user_id);
                            $update_account_stmt->execute();

                            // Verify locks were applied
                            $verify_locks_query = "SELECT COUNT(*) as locked_count FROM tbl_project_assignments 
                                                WHERE user_id = ? AND is_locked = 1";
                            $verify_locks_stmt = $conn->prepare($verify_locks_query);
                            $verify_locks_stmt->bind_param("i", $user_id);
                            $verify_locks_stmt->execute();
                            $verify_locks_result = $verify_locks_stmt->get_result()->fetch_assoc();
                            $actual_locked = $verify_locks_result ? $verify_locks_result['locked_count'] : 0;

                            error_log("TASK_CONTROLLER: Verification shows $actual_locked tasks locked for user $user_id");
                        } else {
                            // No overdue tasks - ensure account is not blocked
                            $update_account_query = "UPDATE tbl_accounts SET status = 'Active', has_overdue_tasks = 0 WHERE user_id = ? AND status = 'Blocked'";
                            $update_account_stmt = $conn->prepare($update_account_query);
                            $update_account_stmt->bind_param("i", $user_id);
                            $update_account_stmt->execute();
                        }
                    }

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
                               SET status_assignee = 'finish', 
                                   last_updated = NOW() 
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

                    // First, unlock all tasks for this user to reset the locking state
                    $reset_locks_query = "UPDATE tbl_project_assignments 
                                        SET is_locked = 0 
                                        WHERE user_id = ?";
                    $reset_locks_stmt = $conn->prepare($reset_locks_query);
                    $reset_locks_stmt->bind_param("i", $user_id);
                    $reset_locks_stmt->execute();

                    // Check if user has admin protection (recently unblocked)
                    $protection_query = "SELECT last_unblocked_at FROM tbl_accounts WHERE user_id = ?";
                    $protection_stmt = $conn->prepare($protection_query);
                    $protection_stmt->bind_param("i", $user_id);
                    $protection_stmt->execute();
                    $protection_result = $protection_stmt->get_result();
                    $protection_data = $protection_result->fetch_assoc();

                    $has_protection = false;
                    if ($protection_data && !empty($protection_data['last_unblocked_at'])) {
                        $unblock_time = new DateTime($protection_data['last_unblocked_at']);
                        $now = new DateTime();

                        if ($unblock_time > $now) {
                            $has_protection = true;
                        }
                    }

                    if ($has_protection) {
                        error_log("TASK_CONTROLLER (complete): User $user_id has admin protection - NOT locking tasks");

                        // Ensure account remains active during protection period
                        $update_account_query = "UPDATE tbl_accounts SET status = 'Active', has_overdue_tasks = 0 WHERE user_id = ?";
                        $update_account_stmt = $conn->prepare($update_account_query);
                        $update_account_stmt->bind_param("i", $user_id);
                        $update_account_stmt->execute();
                    } else {
                        // Check if there are any overdue tasks and lock other tasks
                        // After completing a task, lock all tasks except completed ones if there are overdue tasks
                        $today = date('Y-m-d');
                        $overdue_query = "SELECT assignment_id 
                                         FROM tbl_project_assignments 
                                         WHERE user_id = ? 
                                           AND status_assignee NOT IN ('completed', 'deleted')
                                           AND deadline < ?
                                           AND (delay_acceptable IS NULL OR delay_acceptable != '1')";
                        $overdue_stmt = $conn->prepare($overdue_query);
                        $overdue_stmt->bind_param("is", $user_id, $today);
                        $overdue_stmt->execute();
                        $overdue_result = $overdue_stmt->get_result();

                        // Also check if user has any other task in progress
                        $in_progress_query = "SELECT assignment_id 
                                             FROM tbl_project_assignments 
                                             WHERE user_id = ? 
                                               AND status_assignee = 'in_progress'";
                        $in_progress_stmt = $conn->prepare($in_progress_query);
                        $in_progress_stmt->bind_param("i", $user_id);
                        $in_progress_stmt->execute();
                        $in_progress_result = $in_progress_stmt->get_result();
                        $in_progress_task = $in_progress_result->fetch_assoc();

                        if ($overdue_result->num_rows > 0) {
                            // Lock all remaining tasks except:
                            // 1. Any task marked as 'completed'
                            // 2. Any task that's currently 'in_progress' (if any)
                            $lock_query = "UPDATE tbl_project_assignments 
                                          SET is_locked = 1 
                                          WHERE user_id = ? 
                                            AND status_assignee NOT IN ('completed')";

                            // If there's still a task in progress, don't lock it
                            if ($in_progress_result->num_rows > 0) {
                                $lock_query .= " AND assignment_id != ?";
                                $lock_stmt = $conn->prepare($lock_query);
                                $lock_stmt->bind_param("ii", $user_id, $in_progress_task['assignment_id']);
                            } else {
                                $lock_stmt = $conn->prepare($lock_query);
                                $lock_stmt->bind_param("i", $user_id);
                            }

                            $lock_result = $lock_stmt->execute();
                            $locked_count = $lock_stmt->affected_rows;

                            // Log locking result
                            error_log("TASK_CONTROLLER (complete): Locking tasks for user $user_id with overdue tasks - Result: " .
                                ($lock_result ? "SUCCESS ($locked_count tasks locked)" : "FAILED: " . $conn->error));

                            // Also update account status to ensure consistency
                            $update_account_query = "UPDATE tbl_accounts SET status = 'Blocked', has_overdue_tasks = 1 WHERE user_id = ?";
                            $update_account_stmt = $conn->prepare($update_account_query);
                            $update_account_stmt->bind_param("i", $user_id);
                            $update_account_stmt->execute();
                        } else {
                            // No overdue tasks - ensure account is not blocked
                            $update_account_query = "UPDATE tbl_accounts SET status = 'Active', has_overdue_tasks = 0 WHERE user_id = ? AND status = 'Blocked'";
                            $update_account_stmt = $conn->prepare($update_account_query);
                            $update_account_stmt->bind_param("i", $user_id);
                            $update_account_stmt->execute();
                        }
                    }

                    // Log activity
                    if (ensure_activity_logs_table($conn)) {
                        $log_query = "INSERT INTO activity_logs (user_id, activity_type, entity_id, entity_type, details) 
                                    VALUES (?, 'assignment_finished', ?, 'assignment', ?)";
                        $log_stmt = $conn->prepare($log_query);
                        $details = "Assignment ID: " . $assignment_id . " finished and ready for QA";
                        $log_stmt->bind_param("iis", $user_id, $assignment_id, $details);
                        $log_stmt->execute();
                    }

                    echo json_encode(['status' => 'success', 'message' => 'Assignment marked as finished and ready for QA']);
                } else {
                    echo json_encode(['status' => 'error', 'message' => 'Failed to mark assignment as finished: ' . $conn->error]);
                }
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Assignment ID not provided']);
            }
            break;

        case 'hide_task':
            if (isset($_POST['assignment_id'])) {
                $assignment_id = (int) $_POST['assignment_id'];

                // Verify assignment belongs to user and is completed
                $verify_query = "SELECT pa.assignment_id 
                               FROM tbl_project_assignments pa 
                               WHERE pa.assignment_id = ? AND pa.user_id = ? AND pa.status_assignee = 'completed'";
                $verify_stmt = $conn->prepare($verify_query);
                $verify_stmt->bind_param("ii", $assignment_id, $user_id);
                $verify_stmt->execute();
                $verify_result = $verify_stmt->get_result();

                if ($verify_result->num_rows === 0) {
                    echo json_encode(['status' => 'error', 'message' => 'Task not found, not assigned to you, or not completed']);
                    exit;
                }

                // Mark the task as hidden
                $update_query = "UPDATE tbl_project_assignments 
                               SET is_hidden = 1
                               WHERE assignment_id = ?";
                $update_stmt = $conn->prepare($update_query);
                $update_stmt->bind_param("i", $assignment_id);

                if ($update_stmt->execute()) {
                    // Log activity
                    if (ensure_activity_logs_table($conn)) {
                        $log_query = "INSERT INTO activity_logs (user_id, activity_type, entity_id, entity_type, details) 
                                    VALUES (?, 'task_hidden', ?, 'assignment', ?)";
                        $log_stmt = $conn->prepare($log_query);
                        $details = "Assignment ID: " . $assignment_id . " hidden";
                        $log_stmt->bind_param("iis", $user_id, $assignment_id, $details);
                        $log_stmt->execute();
                    }

                    echo json_encode(['status' => 'success', 'message' => 'Task hidden successfully']);
                } else {
                    echo json_encode(['status' => 'error', 'message' => 'Failed to hide task: ' . $conn->error]);
                }
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Assignment ID not provided']);
            }
            break;

        case 'unhide_task':
            if (isset($_POST['assignment_id'])) {
                $assignment_id = (int) $_POST['assignment_id'];

                // Verify assignment belongs to user and is hidden
                $verify_query = "SELECT pa.assignment_id 
                               FROM tbl_project_assignments pa 
                               WHERE pa.assignment_id = ? AND pa.user_id = ? AND pa.is_hidden = 1";
                $verify_stmt = $conn->prepare($verify_query);
                $verify_stmt->bind_param("ii", $assignment_id, $user_id);
                $verify_stmt->execute();
                $verify_result = $verify_stmt->get_result();

                if ($verify_result->num_rows === 0) {
                    echo json_encode(['status' => 'error', 'message' => 'Task not found, not assigned to you, or not hidden']);
                    exit;
                }

                // Mark the task as unhidden
                $update_query = "UPDATE tbl_project_assignments 
                               SET is_hidden = 0
                               WHERE assignment_id = ?";
                $update_stmt = $conn->prepare($update_query);
                $update_stmt->bind_param("i", $assignment_id);

                if ($update_stmt->execute()) {
                    // Log activity
                    if (ensure_activity_logs_table($conn)) {
                        $log_query = "INSERT INTO activity_logs (user_id, activity_type, entity_id, entity_type, details) 
                                    VALUES (?, 'task_unhidden', ?, 'assignment', ?)";
                        $log_stmt = $conn->prepare($log_query);
                        $details = "Assignment ID: " . $assignment_id . " unhidden";
                        $log_stmt->bind_param("iis", $user_id, $assignment_id, $details);
                        $log_stmt->execute();
                    }

                    echo json_encode(['status' => 'success', 'message' => 'Task unhidden successfully']);
                } else {
                    echo json_encode(['status' => 'error', 'message' => 'Failed to unhide task: ' . $conn->error]);
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