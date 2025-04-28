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
        'overdue_tasks' => []
    ];

    try {
        // Get the current date for comparison
        $today = date('Y-m-d');

        // Query to get overdue tasks for this artist
        $query = "SELECT pa.assignment_id, pa.project_id, pa.role_task, pa.deadline, pa.delay_acceptable,
                        p.project_title, p.status_project
                 FROM tbl_project_assignments pa
                 JOIN tbl_projects p ON pa.project_id = p.project_id
                 WHERE pa.user_id = ? 
                   AND pa.status_assignee NOT IN ('completed', 'deleted')
                   AND pa.deadline < ?
                   AND (pa.delay_acceptable IS NULL OR pa.delay_acceptable != '1')";

        $stmt = $conn->prepare($query);

        if (!$stmt) {
            error_log("Query preparation failed in checkArtistOverdueTasks: " . $conn->error);
            $result['blocked'] = false;
            $result['reason'] = "Error checking overdue tasks. Please contact admin.";
            return $result;
        }

        $stmt->bind_param("is", $user_id, $today);

        if (!$stmt->execute()) {
            error_log("Query execution failed in checkArtistOverdueTasks: " . $stmt->error);
            $result['blocked'] = false;
            $result['reason'] = "Error checking overdue tasks. Please contact admin.";
            return $result;
        }

        $taskResult = $stmt->get_result();
        $overdueTasks = [];

        while ($task = $taskResult->fetch_assoc()) {
            $overdueTasks[] = $task;
        }

        // Block if there are any overdue tasks
        if (count($overdueTasks) > 0) {
            $result['blocked'] = true;
            $result['reason'] = "You have " . count($overdueTasks) . " overdue task(s). Please complete them before starting new tasks.";
            $result['overdue_tasks'] = $overdueTasks;
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

    // First, check if the user has any overdue tasks
    $overdue = checkArtistOverdueTasks($user_id);

    // If no overdue tasks, then no blocking needed
    if (!$overdue['blocked']) {
        return [
            'blocked' => false,
            'reason' => ''
        ];
    }

    try {
        // Check if this specific task is one of the overdue tasks
        // (we should allow artists to access their own overdue tasks)
        $query = "SELECT pa.assignment_id, pa.deadline, pa.delay_acceptable
                 FROM tbl_project_assignments pa
                 WHERE pa.assignment_id = ? AND pa.user_id = ?";

        $stmt = $conn->prepare($query);

        if (!$stmt) {
            error_log("Query preparation failed in isTaskBlocked: " . $conn->error);
            // Default to blocking if we can't determine
            return $overdue;
        }

        $stmt->bind_param("ii", $assignment_id, $user_id);

        if (!$stmt->execute()) {
            error_log("Query execution failed in isTaskBlocked: " . $stmt->error);
            // Default to blocking if we can't determine
            return $overdue;
        }

        $result = $stmt->get_result();
        $task = $result->fetch_assoc();

        // If this is one of the artist's tasks
        if ($task) {
            $today = date('Y-m-d');
            $isOverdue = strtotime($task['deadline']) < strtotime($today);
            $isUnderstandableDelay = isset($task['delay_acceptable']) && $task['delay_acceptable'] == '1';

            // Allow if it's an overdue task (they should be able to work on their own overdue tasks)
            // Also allow if the delay has been marked as acceptable
            if ($isOverdue || $isUnderstandableDelay) {
                return [
                    'blocked' => false,
                    'reason' => ''
                ];
            }

            // For non-overdue tasks, block only if there are other overdue tasks
            return $overdue;
        }

        // Default to blocking if we can't determine
        return $overdue;

    } catch (Exception $e) {
        error_log("Exception in isTaskBlocked: " . $e->getMessage());

        // Default to blocking if we can't determine
        return $overdue;
    }
}