<?php
/**
 * Overdue Task Check
 * 
 * This file contains functions to check if a user has more than 1 overdue task
 * and updates their account status accordingly.
 */

// Make sure we have a database connection
if (!isset($conn)) {
    require_once 'db_connection.php';
}

/**
 * Checks if a user has more than 1 overdue task and updates account status
 * 
 * @param int $user_id The user ID to check
 * @return array Array with block status information
 */
function checkAndUpdateOverdueStatus($user_id)
{
    global $conn;

    $result = [
        'blocked' => false,
        'overdue_count' => 0,
        'overdue_tasks' => [],
        'locked_tasks' => []
    ];

    try {
        // Get the current date for comparison
        $today = date('Y-m-d');

        // Query to get overdue tasks for this user (excluding completed tasks)
        $query = "SELECT pa.assignment_id, pa.project_id, pa.role_task, pa.deadline, pa.delay_acceptable,
                        p.project_title, p.status_project
                 FROM tbl_project_assignments pa
                 JOIN tbl_projects p ON pa.project_id = p.project_id
                 WHERE pa.user_id = ? 
                   AND pa.status_assignee NOT IN ('completed', 'deleted')
                   AND p.status_project != 'Completed'
                   AND pa.deadline < ?
                   AND (pa.delay_acceptable IS NULL OR pa.delay_acceptable != '1')
                 ORDER BY pa.deadline ASC";

        $stmt = $conn->prepare($query);

        if (!$stmt) {
            error_log("Query preparation failed in checkAndUpdateOverdueStatus: " . $conn->error);
            return $result;
        }

        $stmt->bind_param("is", $user_id, $today);

        if (!$stmt->execute()) {
            error_log("Query execution failed in checkAndUpdateOverdueStatus: " . $stmt->error);
            return $result;
        }

        $taskResult = $stmt->get_result();
        $overdueTasks = [];

        while ($task = $taskResult->fetch_assoc()) {
            $overdueTasks[] = $task;
        }

        $result['overdue_count'] = count($overdueTasks);
        $result['overdue_tasks'] = $overdueTasks;

        // If user has more than 1 overdue task, the additional overdue tasks should be locked
        if ($result['overdue_count'] > 1) {
            $result['blocked'] = true;

            // First overdue task stays unlocked
            // All subsequent overdue tasks should be locked
            for ($i = 1; $i < count($overdueTasks); $i++) {
                $result['locked_tasks'][] = $overdueTasks[$i];
            }

            // Update user status to 'Blocked'
            $updateStatus = "UPDATE tbl_accounts SET status = 'Blocked' WHERE user_id = ? AND status != 'Blocked'";
            $updateStmt = $conn->prepare($updateStatus);

            if ($updateStmt) {
                $updateStmt->bind_param("i", $user_id);
                $updateStmt->execute();
            }
        } else {
            // If user has 0 or 1 overdue task, no need to block
            $result['blocked'] = false;
        }

        // Check if user is currently blocked but shouldn't be (admin may have unblocked manually)
        $checkStatus = "SELECT status FROM tbl_accounts WHERE user_id = ?";
        $checkStmt = $conn->prepare($checkStatus);

        if ($checkStmt) {
            $checkStmt->bind_param("i", $user_id);

            if ($checkStmt->execute()) {
                $statusResult = $checkStmt->get_result();

                if ($row = $statusResult->fetch_assoc()) {
                    // If user is manually unblocked by admin, we respect that decision
                    if ($row['status'] != 'Blocked') {
                        $result['blocked'] = false;
                        $result['locked_tasks'] = []; // No locked tasks if admin unblocked
                    } else if ($result['overdue_count'] <= 1) {
                        // User was previously blocked but now has 1 or 0 overdue tasks
                        // Update status to Active
                        $updateStatus = "UPDATE tbl_accounts SET status = 'Active' WHERE user_id = ?";
                        $updateStmt = $conn->prepare($updateStatus);

                        if ($updateStmt) {
                            $updateStmt->bind_param("i", $user_id);
                            $updateStmt->execute();
                        }
                    }
                }
            }
        }

        return $result;

    } catch (Exception $e) {
        error_log("Exception in checkAndUpdateOverdueStatus: " . $e->getMessage());
        return $result;
    }
}

/**
 * Check if a specific task should be locked due to multiple overdue tasks
 * 
 * @param int $user_id The user ID
 * @param int $assignment_id The assignment ID to check
 * @return boolean True if task should be locked, false otherwise
 */
function isTaskLocked($user_id, $assignment_id)
{
    $overdueStatus = checkAndUpdateOverdueStatus($user_id);

    // If user is not blocked, nothing is locked
    if (!$overdueStatus['blocked']) {
        return false;
    }

    // Check if this task is one of the locked tasks (all overdue tasks except the first one)
    foreach ($overdueStatus['locked_tasks'] as $task) {
        if ($task['assignment_id'] == $assignment_id) {
            return true;
        }
    }

    return false;
}