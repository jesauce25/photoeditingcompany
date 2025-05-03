<?php
/**
 * Cron Job: Check Overdue Tasks
 * 
 * This script should be run via cron job to regularly check for overdue tasks
 * and block users accordingly.
 * 
 * Recommended schedule: Every day at midnight
 * Example cron: 0 0 * * * php /path/to/check_overdue_tasks.php
 */

// Set to script max execution time
set_time_limit(300);

// Disable direct access
if (php_sapi_name() != 'cli') {
    die('This script can only be run from the command line');
}

// Include database connection
$base_path = dirname(__FILE__, 3);
require_once $base_path . '/includes/db_connection.php';

// Include task block check functions
require_once $base_path . '/artist/includes/task_block_check.php';

// Log function for cron output
function cron_log($message)
{
    echo "[" . date('Y-m-d H:i:s') . "] " . $message . "\n";
}

cron_log("Starting overdue tasks check...");

// Get all graphic artists
$artists_query = "SELECT user_id FROM tbl_accounts WHERE role = 'Graphic Artist'";
$artists_result = $conn->query($artists_query);

if (!$artists_result) {
    cron_log("Error fetching artists: " . $conn->error);
    exit(1);
}

$artist_count = $artists_result->num_rows;
cron_log("Found $artist_count graphic artists to check");
$blocked_count = 0;
$unblocked_count = 0;

// Process each artist
while ($artist = $artists_result->fetch_assoc()) {
    $user_id = $artist['user_id'];

    // Get current user status
    $status_query = "SELECT status, has_overdue_tasks, last_unblocked_at FROM tbl_accounts WHERE user_id = ?";
    $status_stmt = $conn->prepare($status_query);
    $status_stmt->bind_param("i", $user_id);
    $status_stmt->execute();
    $user_status = $status_stmt->get_result()->fetch_assoc();

    // Skip users with active admin protection (recently unblocked)
    if ($user_status && !empty($user_status['last_unblocked_at'])) {
        $unblock_time = new DateTime($user_status['last_unblocked_at']);
        $now = new DateTime();

        if ($unblock_time > $now) {
            cron_log("User ID $user_id has admin protection until " . $unblock_time->format('Y-m-d H:i:s') . " - SKIPPING");
            continue;
        }
    }

    // Force block check
    $block_result = forceBlockUserByOverdue($user_id);

    // Log the result
    if ($block_result['has_overdue']) {
        cron_log("User ID $user_id has overdue tasks - Status updated: " .
            ($block_result['status_updated'] ? 'YES' : 'NO') .
            ", Tasks locked: " . ($block_result['tasks_locked'] ? 'YES' : 'NO'));
        $blocked_count++;
    } else {
        if (isset($user_status) && $user_status['status'] === 'Blocked' && $user_status['has_overdue_tasks'] == 1) {
            cron_log("User ID $user_id was previously blocked but no longer has overdue tasks - unblocking");
            $unblocked_count++;
        } else {
            cron_log("User ID $user_id has no overdue tasks - no action needed");
        }
    }
}

// Final summary
cron_log("Completed overdue tasks check");
cron_log("Artists checked: $artist_count");
cron_log("Artists blocked: $blocked_count");
cron_log("Artists unblocked: $unblocked_count");

// Close database connection
$conn->close();