<?php
/**
 * Task Block Check
 * 
 * This file contains functions to check if a graphic artist should be blocked
 * from accessing new tasks due to having overdue tasks.
 */

// Make sure we have a database connection
if (!isset($conn)) {
    require_once '../../includes/db_connection.php';
}

/**
 * Checks if a graphic artist has any overdue tasks
 * 
 * @param int $user_id The user ID to check
 * @return array An array with 'blocked' (boolean) and 'reason' (string)
 */
function checkArtistOverdueTasks($user_id)
{
    global $conn;

    $result = [
        'blocked' => false,
        'reason' => '',
        'overdue_tasks' => [],
        'first_overdue' => null
    ];

    try {
        // Get the current date for comparison
        $today = date('Y-m-d');

        // First, check if the user is already blocked in tbl_accounts and get last_unblocked_at
        $statusQuery = "SELECT status, last_unblocked_at FROM tbl_accounts WHERE user_id = ?";
        $statusStmt = $conn->prepare($statusQuery);
        $statusStmt->bind_param("i", $user_id);
        $statusStmt->execute();
        $statusResult = $statusStmt->get_result();
        $userStatus = $statusResult->fetch_assoc();

        // Step 1: Reset all is_first_overdue flags to 0 for this user
        $resetQuery = "UPDATE tbl_project_assignments SET is_first_overdue = 0 WHERE user_id = ?";
        $resetStmt = $conn->prepare($resetQuery);
        if ($resetStmt) {
            $resetStmt->bind_param("i", $user_id);
            $resetStmt->execute();
        }

        // Step 2: Get all overdue tasks for this user, ordered by deadline (earliest first)
        // Only consider tasks that were assigned after the last unblock time
        $query = "SELECT pa.assignment_id, pa.project_id, pa.role_task, pa.deadline, pa.delay_acceptable,
                        p.project_title, p.status_project, pa.status_assignee
                 FROM tbl_project_assignments pa
                 JOIN tbl_projects p ON pa.project_id = p.project_id
                 WHERE pa.user_id = ? 
                   AND pa.status_assignee NOT IN ('completed', 'deleted')
                   AND pa.deadline < ?
                   AND (pa.delay_acceptable IS NULL OR pa.delay_acceptable != '1')
                   AND (? IS NULL OR pa.assigned_date > ?)
                 ORDER BY pa.deadline ASC"; // Order by deadline to get earliest overdue task first

        $stmt = $conn->prepare($query);

        if (!$stmt) {
            error_log("Query preparation failed in checkArtistOverdueTasks: " . $conn->error);
            $result['blocked'] = false;
            $result['reason'] = "Error checking overdue tasks. Please contact admin.";
            return $result;
        }

        $stmt->bind_param("isss", $user_id, $today, $userStatus['last_unblocked_at'], $userStatus['last_unblocked_at']);

        if (!$stmt->execute()) {
            error_log("Query execution failed in checkArtistOverdueTasks: " . $stmt->error);
            $result['blocked'] = false;
            $result['reason'] = "Error checking overdue tasks. Please contact admin.";
            return $result;
        }

        $taskResult = $stmt->get_result();
        $overdueTasks = [];
        $first_overdue = null;

        while ($task = $taskResult->fetch_assoc()) {
            $overdueTasks[] = $task;

            // Store the first overdue task separately (earliest deadline)
            if ($first_overdue === null) {
                $first_overdue = $task['assignment_id'];
            }
        }

        // Block if there are any overdue tasks
        if (count($overdueTasks) > 0) {
            $result['blocked'] = true;
            $result['reason'] = "You have " . count($overdueTasks) . " overdue task(s). Please complete the earliest overdue task before accessing other tasks.";
            $result['overdue_tasks'] = $overdueTasks;
            $result['first_overdue'] = $first_overdue;

            // Update the user status to 'Blocked' in tbl_accounts
            $updateQuery = "UPDATE tbl_accounts SET status = 'Blocked' WHERE user_id = ?";
            $updateStmt = $conn->prepare($updateQuery);

            if ($updateStmt) {
                $updateStmt->bind_param("i", $user_id);
                $updateStmt->execute();
            }

            // Step 3: Mark the earliest overdue task with is_first_overdue = 1
            if ($first_overdue !== null) {
                $markQuery = "UPDATE tbl_project_assignments SET is_first_overdue = 1 WHERE assignment_id = ?";
                $markStmt = $conn->prepare($markQuery);
                if ($markStmt) {
                    $markStmt->bind_param("i", $first_overdue);
                    $markStmt->execute();
                }
            }

            // Step 4: Apply locking rules
            // - Do not lock completed tasks
            // - Do not lock the task with is_first_overdue = 1
            // - Lock all other tasks

            // First, reset all locks for this user's tasks
            $resetLocksQuery = "UPDATE tbl_project_assignments 
                              SET is_locked = 0 
                              WHERE user_id = ? AND status_assignee = 'completed'";
            $resetStmt = $conn->prepare($resetLocksQuery);
            if ($resetStmt) {
                $resetStmt->bind_param("i", $user_id);
                $resetStmt->execute();
            }

            // Lock all tasks except completed and first overdue
            $lockQuery = "UPDATE tbl_project_assignments 
                         SET is_locked = 1 
                         WHERE user_id = ? 
                         AND status_assignee != 'completed' 
                         AND is_first_overdue = 0";
            $lockStmt = $conn->prepare($lockQuery);
            if ($lockStmt) {
                $lockStmt->bind_param("i", $user_id);
                $lockStmt->execute();
            }
        } else {
            // No overdue tasks, reset all flags and locks
            if ($userStatus['status'] === 'Blocked') {
                // No overdue tasks found, unblock the user
                $updateQuery = "UPDATE tbl_accounts SET status = 'Active' WHERE user_id = ?";
                $updateStmt = $conn->prepare($updateQuery);
                if ($updateStmt) {
                    $updateStmt->bind_param("i", $user_id);
                    $updateStmt->execute();
                }
            }

            // Unlock all tasks
            $unlockAllQuery = "UPDATE tbl_project_assignments 
                             SET is_locked = 0, is_first_overdue = 0
                             WHERE user_id = ?";
            $unlockStmt = $conn->prepare($unlockAllQuery);
            if ($unlockStmt) {
                $unlockStmt->bind_param("i", $user_id);
                $unlockStmt->execute();
            }
        }

        return $result;

    } catch (Exception $e) {
        error_log("Exception in checkArtistOverdueTasks: " . $e->getMessage());
        $result['blocked'] = false;
        $result['reason'] = "Error checking overdue tasks: " . $e->getMessage();
        return $result;
    }
}

/**
 * Checks if a specific task is blocked for access
 * 
 * @param int $user_id The user ID
 * @param int $assignment_id The assignment ID being accessed
 * @return array An array with 'blocked' (boolean) and 'reason' (string)
 */
function isTaskBlocked($user_id, $assignment_id)
{
    global $conn;

    // First, check if the user has any overdue tasks and set flags
    $overdue = checkArtistOverdueTasks($user_id);

    // If no overdue tasks, then no blocking needed
    if (!$overdue['blocked']) {
        return [
            'blocked' => false,
            'reason' => ''
        ];
    }

    try {
        // Just check if the task is locked based on our rules
        $query = "SELECT is_locked, status_assignee, is_first_overdue
                 FROM tbl_project_assignments
                 WHERE assignment_id = ? AND user_id = ?";

        $stmt = $conn->prepare($query);

        if (!$stmt) {
            error_log("Query preparation failed in isTaskBlocked: " . $conn->error);
            return $overdue;
        }

        $stmt->bind_param("ii", $assignment_id, $user_id);

        if (!$stmt->execute()) {
            error_log("Query execution failed in isTaskBlocked: " . $stmt->error);
            return $overdue;
        }

        $result = $stmt->get_result();
        $task = $result->fetch_assoc();

        // If this is one of the artist's tasks
        if ($task) {
            // Check the is_locked flag which we set in checkArtistOverdueTasks
            // This should already follow our rules:
            // - Completed tasks are not locked
            // - First overdue task is not locked
            // - All other tasks are locked
            if (!$task['is_locked']) {
                return [
                    'blocked' => false,
                    'reason' => ''
                ];
            }

            // Task is locked - provide reason based on status
            $reason = "You have overdue tasks. Please complete your earliest overdue task before accessing this task.";

            return [
                'blocked' => true,
                'reason' => $reason
            ];
        }

        // Default to blocking if we can't determine
        return $overdue;

    } catch (Exception $e) {
        error_log("Exception in isTaskBlocked: " . $e->getMessage());
        return $overdue;
    }
}