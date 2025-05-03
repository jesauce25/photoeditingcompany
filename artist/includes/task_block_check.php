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
 * Force block a user with overdue tasks - direct implementation
 * This function directly sets/updates all three states:
 * 1. tbl_accounts.status = 'Blocked'
 * 2. tbl_accounts.has_overdue_tasks = 1
 * 3. tbl_project_assignments.is_locked = 1 (for non-completed tasks)
 *
 * @param int $user_id The user ID to check and block if needed
 * @return array Result with status information
 */
function forceBlockUserByOverdue($user_id)
{
    global $conn;

    // Initialize result array
    $result = [
        'has_overdue' => false,
        'status_updated' => false,
        'tasks_locked' => false,
        'message' => '',
        'has_protection' => false,
        'protection_time_remaining' => null
    ];

    try {
        // Begin transaction
        $conn->begin_transaction();

        // Step 1: Check if user has admin protection (recently unblocked)
        $protectionQuery = "SELECT status, has_overdue_tasks, last_unblocked_at, is_protected FROM tbl_accounts WHERE user_id = ?";
        $protectionStmt = $conn->prepare($protectionQuery);
        $protectionStmt->bind_param("i", $user_id);
        $protectionStmt->execute();
        $userData = $protectionStmt->get_result()->fetch_assoc();

        $hasAdminProtection = false;
        $protection_time_remaining = null;
        if ($userData && !empty($userData['last_unblocked_at'])) {
            $unblockTime = new DateTime($userData['last_unblocked_at']);
            $now = new DateTime();

            // CRITICAL FIX: Proper timestamp comparison for admin protection
            if ($unblockTime > $now) {
                $hasAdminProtection = true;
                $timeRemaining = $now->diff($unblockTime);
                $protection_time_remaining = $timeRemaining->format('%h hours, %i minutes, %s seconds');

                error_log("PROTECTION STATUS: User ID $user_id has admin protection for " .
                    $protection_time_remaining . " until " . $unblockTime->format('Y-m-d H:i:s') .
                    " - Current is_protected value: " . $userData['is_protected']);

                // If is_protected is not set to 1, update it
                if (!$userData['is_protected']) {
                    $updateProtectedQuery = "UPDATE tbl_accounts SET is_protected = 1 WHERE user_id = ?";
                    $updateProtectedStmt = $conn->prepare($updateProtectedQuery);
                    $updateProtectedStmt->bind_param("i", $user_id);
                    $updateProtectedStmt->execute();
                    error_log("PROTECTION STATUS: Updated is_protected to 1 for user ID $user_id");
                }

                // If user has admin protection, ensure their tasks are all unlocked
                $unlockQuery = "UPDATE tbl_project_assignments SET is_locked = 0 WHERE user_id = ?";
                $unlockStmt = $conn->prepare($unlockQuery);
                $unlockStmt->bind_param("i", $user_id);
                $unlockStmt->execute();
                $unlocked_count = $unlockStmt->affected_rows;
                error_log("PROTECTION STATUS: Unlocked $unlocked_count tasks for protected user ID $user_id");

                // Also ensure account status is Active and has_overdue_tasks is 0
                $activateQuery = "UPDATE tbl_accounts SET status = 'Active', has_overdue_tasks = 0 WHERE user_id = ?";
                $activateStmt = $conn->prepare($activateQuery);
                $activateStmt->bind_param("i", $user_id);
                $activateStmt->execute();

                $result['has_protection'] = true;
                $result['protection_time_remaining'] = $protection_time_remaining;
                $result['message'] = "User has admin protection until " . $unblockTime->format('Y-m-d H:i:s');

                // Commit transaction and return early - protection is in effect
                $conn->commit();
                return $result;
            } else {
                error_log("PROTECTION STATUS: User ID $user_id has NO admin protection - unblock time has passed (" .
                    $unblockTime->format('Y-m-d H:i:s') . " < " . $now->format('Y-m-d H:i:s') . ")");

                // Clear is_protected if protection period has expired
                if ($userData['is_protected']) {
                    $updateProtectedQuery = "UPDATE tbl_accounts SET is_protected = 0 WHERE user_id = ?";
                    $updateProtectedStmt = $conn->prepare($updateProtectedQuery);
                    $updateProtectedStmt->bind_param("i", $user_id);
                    $updateProtectedStmt->execute();
                    error_log("PROTECTION STATUS: EXPIRED AND CLEARED for user ID $user_id - Protection flag set to 0");
                    error_log("PROTECTION STATUS: User is now vulnerable to overdue task detection");
                }
            }
        } else {
            error_log("PROTECTION STATUS: User ID $user_id has no last_unblocked_at timestamp");
        }

        // Step 2: Check for overdue tasks
        $today = date('Y-m-d');

        // First, check if forgiven_at column exists
        $check_column = "SHOW COLUMNS FROM tbl_project_assignments LIKE 'forgiven_at'";
        $column_exists = $conn->query($check_column)->num_rows > 0;

        if ($column_exists) {
            // Column exists, use it in the query to exclude forgiven tasks
            $overdueQuery = "SELECT COUNT(*) as overdue_count 
                              FROM tbl_project_assignments 
                              WHERE user_id = ? 
                                AND status_assignee NOT IN ('completed', 'deleted') 
                                AND deadline < ? 
                                AND (delay_acceptable IS NULL OR delay_acceptable != '1')
                                AND (forgiven_at IS NULL)";  // Exclude forgiven tasks
        } else {
            // Column doesn't exist, use the original query
            $overdueQuery = "SELECT COUNT(*) as overdue_count 
                              FROM tbl_project_assignments 
                              WHERE user_id = ? 
                                AND status_assignee NOT IN ('completed', 'deleted') 
                                AND deadline < ? 
                                AND (delay_acceptable IS NULL OR delay_acceptable != '1')";
        }

        $overdueStmt = $conn->prepare($overdueQuery);
        $overdueStmt->bind_param("is", $user_id, $today);
        $overdueStmt->execute();
        $overdueData = $overdueStmt->get_result()->fetch_assoc();

        $hasOverdueTasks = ($overdueData && $overdueData['overdue_count'] > 0);
        $result['has_overdue'] = $hasOverdueTasks;

        error_log("Force Block: User ID $user_id - Has overdue tasks: " . ($hasOverdueTasks ? 'YES' : 'NO') .
            ", Count: " . ($overdueData ? $overdueData['overdue_count'] : 0) .
            ", Admin protection: " . ($hasAdminProtection ? 'YES' : 'NO'));

        // Step 3: Apply block/unblock logic
        if ($hasOverdueTasks) {
            // User has overdue tasks - apply blocking if no admin protection
            if (!$hasAdminProtection) {
                // Update user account: set status = 'Blocked' and has_overdue_tasks = 1
                $blockQuery = "UPDATE tbl_accounts 
                              SET status = 'Blocked', has_overdue_tasks = 1 
                              WHERE user_id = ?";
                $blockStmt = $conn->prepare($blockQuery);
                $blockStmt->bind_param("i", $user_id);
                $blockResult = $blockStmt->execute();
                $affectedRows = $blockStmt->affected_rows;

                error_log("Force Block: Status update executed - Success: " . ($blockResult ? 'YES' : 'NO') .
                    ", Affected rows: " . $affectedRows);

                // Double-check the update actually happened
                $verifyQuery = "SELECT status, has_overdue_tasks FROM tbl_accounts WHERE user_id = ?";
                $verifyStmt = $conn->prepare($verifyQuery);
                $verifyStmt->bind_param("i", $user_id);
                $verifyStmt->execute();
                $afterUser = $verifyStmt->get_result()->fetch_assoc();

                if ($afterUser) {
                    error_log("Force Block: After update - Status: '{$afterUser['status']}', has_overdue_tasks: {$afterUser['has_overdue_tasks']}");
                    $statusActuallyChanged = ($afterUser['status'] === 'Blocked');
                    $result['status_updated'] = $statusActuallyChanged;
                }

                // Reset all locks first
                $resetLocksQuery = "UPDATE tbl_project_assignments 
                                   SET is_locked = 0 
                                   WHERE user_id = ?";
                $resetStmt = $conn->prepare($resetLocksQuery);
                $resetStmt->bind_param("i", $user_id);
                $resetStmt->execute();

                // Apply locks to all tasks EXCEPT completed, in_progress, and finish tasks
                $lockQuery = "UPDATE tbl_project_assignments 
                             SET is_locked = 1 
                             WHERE user_id = ? 
                               AND status_assignee NOT IN ('completed', 'in_progress', 'finish')";

                $lockStmt = $conn->prepare($lockQuery);
                $lockStmt->bind_param("i", $user_id);

                // Execute and ensure it works
                $lockSuccess = $lockStmt->execute();
                $tasksLocked = $lockStmt->affected_rows;
                $result['tasks_locked'] = ($tasksLocked > 0);

                error_log("Force Block: Lock query execution - Success: " . ($lockSuccess ? 'YES' : 'NO') .
                    ", Error: " . ($lockSuccess ? 'None' : $lockStmt->error) .
                    ", Locked $tasksLocked tasks for user ID: $user_id");

                $result['message'] = "User blocked due to overdue tasks";
                error_log("Force Block: User ID $user_id blocked with {$overdueData['overdue_count']} overdue tasks");
            } else {
                // Admin protection active - do NOT set any flags that would trigger re-blocking
                error_log("Force Block: User ID $user_id has overdue tasks but protected by admin unblock - KEEPING PROTECTED STATUS");
                $result['message'] = "User has overdue tasks but protected by admin unblock";
                $result['has_protection'] = true;
            }
        } else {
            // User has NO overdue tasks - unblock if currently blocked
            if ($userData && ($userData['status'] === 'Blocked' || $userData['has_overdue_tasks'] == 1)) {
                // No admin protection needed for unblocking
                $unblockQuery = "UPDATE tbl_accounts 
                                SET status = 'Active', has_overdue_tasks = 0 
                                WHERE user_id = ?";
                $unblockStmt = $conn->prepare($unblockQuery);
                $unblockStmt->bind_param("i", $user_id);
                $unblockStmt->execute();
                $result['status_updated'] = ($unblockStmt->affected_rows > 0);

                // Unlock all tasks
                $unlockQuery = "UPDATE tbl_project_assignments 
                               SET is_locked = 0 
                               WHERE user_id = ?";
                $unlockStmt = $conn->prepare($unlockQuery);
                $unlockStmt->bind_param("i", $user_id);
                $unlockStmt->execute();

                $result['message'] = "User unblocked - no overdue tasks";
                error_log("Force Block: User ID $user_id unblocked - no overdue tasks found");
            } else {
                $result['message'] = "No overdue tasks found, user status unchanged";
            }
        }

        // Commit transaction
        $conn->commit();

        return $result;

    } catch (Exception $e) {
        // Rollback on error
        if ($conn->inTransaction()) {
            $conn->rollback();
        }

        error_log("Force Block ERROR: Exception in forceBlockUserByOverdue: " . $e->getMessage());
        return [
            'has_overdue' => false,
            'status_updated' => false,
            'tasks_locked' => false,
            'message' => "Error: " . $e->getMessage(),
            'has_protection' => false,
            'protection_time_remaining' => null
        ];
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

    // Force check and update the block state
    $blockState = forceBlockUserByOverdue($user_id);

    // If user is not blocked, allow access
    if (!$blockState['has_overdue']) {
        return [
            'blocked' => false,
            'reason' => ''
        ];
    }

    // Check if this specific task is locked
    $query = "SELECT is_locked, status_assignee
              FROM tbl_project_assignments
              WHERE assignment_id = ? AND user_id = ?";
    $stmt = $conn->prepare($query);

    if (!$stmt) {
        error_log("Query preparation failed in isTaskBlocked: " . $conn->error);
        return ['blocked' => true, 'reason' => "Error checking task access."];
    }

    $stmt->bind_param("ii", $assignment_id, $user_id);

    if (!$stmt->execute()) {
        error_log("Query execution failed in isTaskBlocked: " . $stmt->error);
        return ['blocked' => true, 'reason' => "Error checking task access."];
    }

    $result = $stmt->get_result();
    $task = $result->fetch_assoc();

    // Task not found or is locked
    if (!$task || $task['is_locked']) {
        return [
            'blocked' => true,
            'reason' => "You have overdue tasks. Please complete your current task before accessing other tasks."
        ];
    }

    // Task exists and is not locked (should be a completed or in-progress task)
    return [
        'blocked' => false,
        'reason' => ''
    ];
}