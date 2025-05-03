<?php
/**
 * Protection Status Check Tool
 * 
 * This script provides a way to check the protection status of users without waiting
 * for 24 hours. It's useful for debugging and verifying that the protection feature works.
 */

// Include database connection
require_once '../includes/db_connection.php';

// Set content type to text/plain for easy reading of output
header('Content-Type: text/plain');

// Check if user_id is provided
if (!isset($_GET['user_id']) || !is_numeric($_GET['user_id'])) {
    echo "ERROR: Please provide a valid user_id parameter.\n";
    echo "Example usage: check_protection.php?user_id=12\n";
    exit;
}

$user_id = (int) $_GET['user_id'];

// Get user account details
$query = "SELECT a.user_id, a.username, a.status, a.has_overdue_tasks, a.is_protected, 
                 a.last_unblocked_at, u.first_name, u.last_name 
          FROM tbl_accounts a
          JOIN tbl_users u ON a.user_id = u.user_id
          WHERE a.user_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo "ERROR: User ID $user_id not found.\n";
    exit;
}

$user = $result->fetch_assoc();

echo "PROTECTION STATUS CHECK\n";
echo "=======================\n\n";

echo "User Information:\n";
echo "- ID: {$user['user_id']}\n";
echo "- Name: {$user['first_name']} {$user['last_name']}\n";
echo "- Username: {$user['username']}\n";
echo "- Account Status: {$user['status']}\n";
echo "- Has Overdue Tasks: " . ($user['has_overdue_tasks'] ? "YES" : "NO") . "\n";
echo "- Is Protected: " . ($user['is_protected'] ? "YES" : "NO") . "\n";

// Check if user has protection and calculate time remaining
if (!empty($user['last_unblocked_at'])) {
    $unblockTime = new DateTime($user['last_unblocked_at']);
    $now = new DateTime();

    if ($unblockTime > $now) {
        $timeRemaining = $now->diff($unblockTime);
        echo "- Protection Status: ACTIVE\n";
        echo "- Protection Until: " . $unblockTime->format('Y-m-d H:i:s') . "\n";
        echo "- Time Remaining: " . $timeRemaining->format('%d days, %h hours, %i minutes, %s seconds') . "\n";
    } else {
        echo "- Protection Status: EXPIRED\n";
        echo "- Protection Expired At: " . $unblockTime->format('Y-m-d H:i:s') . "\n";
        echo "- Time Since Expiration: " . $now->diff($unblockTime)->format('%d days, %h hours, %i minutes, %s seconds') . "\n";
    }
} else {
    echo "- Protection Status: NONE (never unblocked)\n";
}

// Check for overdue tasks
$today = date('Y-m-d');
$overdueQuery = "SELECT COUNT(*) as overdue_count 
                 FROM tbl_project_assignments 
                 WHERE user_id = ? 
                   AND status_assignee NOT IN ('completed', 'deleted') 
                   AND deadline < ?";
$overdueStmt = $conn->prepare($overdueQuery);
$overdueStmt->bind_param("is", $user_id, $today);
$overdueStmt->execute();
$overdueData = $overdueStmt->get_result()->fetch_assoc();

$hasOverdueTasks = ($overdueData && $overdueData['overdue_count'] > 0);

echo "\nOverdue Tasks Information:\n";
echo "- Has Overdue Tasks: " . ($hasOverdueTasks ? "YES" : "NO") . "\n";
echo "- Overdue Task Count: " . ($overdueData ? $overdueData['overdue_count'] : 0) . "\n";

// Check locked tasks count
$lockedQuery = "SELECT 
                    COUNT(*) as total_count,
                    SUM(CASE WHEN is_locked = 1 THEN 1 ELSE 0 END) as locked_count,
                    SUM(CASE WHEN status_assignee = 'in_progress' THEN 1 ELSE 0 END) as in_progress_count,
                    SUM(CASE WHEN status_assignee = 'finish' THEN 1 ELSE 0 END) as finish_count,
                    SUM(CASE WHEN status_assignee = 'completed' THEN 1 ELSE 0 END) as completed_count,
                    SUM(CASE WHEN status_assignee = 'pending' THEN 1 ELSE 0 END) as pending_count
                FROM tbl_project_assignments
                WHERE user_id = ?";
$lockedStmt = $conn->prepare($lockedQuery);
$lockedStmt->bind_param("i", $user_id);
$lockedStmt->execute();
$taskData = $lockedStmt->get_result()->fetch_assoc();

echo "\nTask Information:\n";
echo "- Total Tasks: " . $taskData['total_count'] . "\n";
echo "- Locked Tasks: " . $taskData['locked_count'] . "\n";
echo "- In Progress Tasks: " . $taskData['in_progress_count'] . "\n";
echo "- Finish Tasks: " . $taskData['finish_count'] . "\n";
echo "- Completed Tasks: " . $taskData['completed_count'] . "\n";
echo "- Pending Tasks: " . $taskData['pending_count'] . "\n";

echo "\n\n============== TEST SIMULATION ==============\n\n";
echo "Simulating what would happen if user logged in now...\n\n";

// Include task_block_check
if (file_exists('../artist/includes/task_block_check.php')) {
    require_once '../artist/includes/task_block_check.php';

    // Call the force block to see what would happen
    $blockResult = forceBlockUserByOverdue($user_id);

    echo "Block Check Result:\n";
    echo "- Has Overdue Tasks: " . ($blockResult['has_overdue'] ? "YES" : "NO") . "\n";
    echo "- Has Protection: " . ($blockResult['has_protection'] ? "YES" : "NO") . "\n";
    if (isset($blockResult['protection_time_remaining'])) {
        echo "- Protection Remaining: " . $blockResult['protection_time_remaining'] . "\n";
    }
    echo "- Status Would Update: " . ($blockResult['status_updated'] ? "YES" : "NO") . "\n";
    echo "- Tasks Would Lock: " . ($blockResult['tasks_locked'] ? "YES" : "NO") . "\n";
    echo "- Message: " . $blockResult['message'] . "\n";
} else {
    echo "ERROR: Could not include task_block_check.php for simulation\n";
}

// Output the database tables for the user
echo "\n\n============== USER RECORD DETAILS ==============\n\n";

// Check tbl_accounts
echo "Table: tbl_accounts\n";
$accountQuery = "SELECT * FROM tbl_accounts WHERE user_id = ?";
$accountStmt = $conn->prepare($accountQuery);
$accountStmt->bind_param("i", $user_id);
$accountStmt->execute();
$accountData = $accountStmt->get_result()->fetch_assoc();

foreach ($accountData as $key => $value) {
    echo "- $key: $value\n";
}

// Output footer
echo "\n\n==============================================\n";
echo "Generated at: " . date('Y-m-d H:i:s') . "\n";
echo "==============================================\n";