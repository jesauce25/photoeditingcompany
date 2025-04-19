<?php
/**
 * Artist Notifications Controller
 * Handles notifications for artists including new assignments and project updates
 */

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Make sure database connection is available
require_once __DIR__ . '/../../admin/controllers/db_connection_passthrough.php';

/**
 * Get all unread notifications for a specific artist
 * @param int $user_id The user ID of the artist
 * @return array Array of notifications
 */
function getArtistNotifications($user_id) {
    global $conn;
    
    // Check if the notifications table exists
    $checkTableSql = "SHOW TABLES LIKE 'tbl_notifications'";
    $tableResult = $conn->query($checkTableSql);
    
    // Create table if it doesn't exist
    if ($tableResult->num_rows === 0) {
        createNotificationsTable();
    }
    
    // Get unread notifications for this user
    $sql = "SELECT n.notification_id, n.message, n.type, n.created_at, n.is_read, 
                   n.reference_id, n.reference_type
            FROM tbl_notifications n 
            WHERE n.user_id = ? 
            ORDER BY n.created_at DESC 
            LIMIT 50";
            
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $notifications = [];
    while ($row = $result->fetch_assoc()) {
        $notifications[] = $row;
    }
    
    return $notifications;
}

/**
 * Get count of unread notifications
 * @param int $user_id The user ID
 * @return int Count of unread notifications
 */
function getUnreadNotificationsCount($user_id) {
    global $conn;
    
    // Check if the notifications table exists
    $checkTableSql = "SHOW TABLES LIKE 'tbl_notifications'";
    $tableResult = $conn->query($checkTableSql);
    
    // If table doesn't exist, there are no notifications
    if ($tableResult->num_rows === 0) {
        return 0;
    }
    
    $sql = "SELECT COUNT(*) as count 
            FROM tbl_notifications 
            WHERE user_id = ? AND is_read = 0";
            
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    
    return (int)$row['count'];
}

/**
 * Mark a notification as read
 * @param int $notification_id The notification ID
 * @param int $user_id The user ID (for security)
 * @return bool Success status
 */
function markNotificationAsRead($notification_id, $user_id) {
    global $conn;
    
    $sql = "UPDATE tbl_notifications 
            SET is_read = 1 
            WHERE notification_id = ? AND user_id = ?";
            
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $notification_id, $user_id);
    $result = $stmt->execute();
    
    return $result;
}

/**
 * Mark all notifications as read for a user
 * @param int $user_id The user ID
 * @return bool Success status
 */
function markAllNotificationsAsRead($user_id) {
    global $conn;
    
    $sql = "UPDATE tbl_notifications 
            SET is_read = 1 
            WHERE user_id = ?";
            
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $user_id);
    $result = $stmt->execute();
    
    return $result;
}

/**
 * Create a new notification for a user
 * @param int $user_id The recipient user ID
 * @param string $message The notification message
 * @param string $type The notification type (assignment, update, etc)
 * @param int $reference_id Optional ID of the referenced item (project_id, assignment_id)
 * @param string $reference_type Optional type of reference (project, assignment)
 * @return int|bool The new notification ID or false on failure
 */
function createNotification($user_id, $message, $type, $reference_id = null, $reference_type = null) {
    global $conn;
    
    // Check if the notifications table exists
    $checkTableSql = "SHOW TABLES LIKE 'tbl_notifications'";
    $tableResult = $conn->query($checkTableSql);
    
    // Create table if it doesn't exist
    if ($tableResult->num_rows === 0) {
        createNotificationsTable();
    }
    
    $sql = "INSERT INTO tbl_notifications 
            (user_id, message, type, reference_id, reference_type, created_at, is_read) 
            VALUES (?, ?, ?, ?, ?, NOW(), 0)";
            
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("issss", $user_id, $message, $type, $reference_id, $reference_type);
    
    if ($stmt->execute()) {
        return $conn->insert_id;
    }
    
    return false;
}

/**
 * Create the notifications table if it doesn't exist
 */
function createNotificationsTable() {
    global $conn;
    
    $sql = "CREATE TABLE IF NOT EXISTS tbl_notifications (
        notification_id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        message TEXT NOT NULL,
        type VARCHAR(50) NOT NULL,
        reference_id VARCHAR(50) NULL,
        reference_type VARCHAR(50) NULL,
        created_at DATETIME NOT NULL,
        is_read TINYINT(1) NOT NULL DEFAULT 0,
        INDEX idx_user_id (user_id),
        INDEX idx_is_read (is_read)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
    
    return $conn->query($sql);
}

/**
 * Check for new assignments for an artist and create notifications
 * @param int $user_id The artist user ID
 * @return int Number of new notifications created
 */
function checkForNewAssignments($user_id) {
    global $conn;
    
    // Get the last time we checked for this user
    $lastCheckedKey = "last_checked_assignments_" . $user_id;
    $lastChecked = getLastCheckedTime($lastCheckedKey);
    
    // Query for new assignments since last check
    $sql = "SELECT pa.assignment_id, pa.project_id, pa.role_task, pa.deadline, 
                   p.project_title, COUNT(pi.image_id) as image_count
            FROM tbl_project_assignments pa
            JOIN tbl_projects p ON pa.project_id = p.project_id
            LEFT JOIN tbl_project_images pi ON pi.assignment_id = pa.assignment_id
            WHERE pa.user_id = ? AND pa.assigned_date > ?
            GROUP BY pa.assignment_id";
            
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("is", $user_id, $lastChecked);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $notificationCount = 0;
    
    while ($row = $result->fetch_assoc()) {
        $message = "New task assigned: {$row['role_task']} for project '{$row['project_title']}'";
        if ($row['image_count'] > 0) {
            $message .= " with {$row['image_count']} images";
        }
        $message .= ". Deadline: " . date('M d, Y', strtotime($row['deadline']));
        
        createNotification(
            $user_id, 
            $message, 
            'assignment', 
            $row['assignment_id'], 
            'assignment'
        );
        
        $notificationCount++;
    }
    
    // Check for updates to existing assignments
    $updateSql = "SELECT pa.assignment_id, pa.project_id, pa.role_task, pa.deadline,
                        p.project_title, COUNT(pi.image_id) as image_count
                 FROM tbl_project_assignments pa
                 JOIN tbl_projects p ON pa.project_id = p.project_id
                 LEFT JOIN tbl_project_images pi ON pi.assignment_id = pa.assignment_id
                 WHERE pa.user_id = ? AND pa.last_updated > ? AND pa.assigned_date <= ?
                 GROUP BY pa.assignment_id";
                 
    $updateStmt = $conn->prepare($updateSql);
    $updateStmt->bind_param("iss", $user_id, $lastChecked, $lastChecked);
    $updateStmt->execute();
    $updateResult = $updateStmt->get_result();
    
    while ($row = $updateResult->fetch_assoc()) {
        $message = "Task updated: {$row['role_task']} for project '{$row['project_title']}'";
        if ($row['image_count'] > 0) {
            $message .= " with {$row['image_count']} images";
        }
        $message .= ". Deadline: " . date('M d, Y', strtotime($row['deadline']));
        
        createNotification(
            $user_id, 
            $message, 
            'update', 
            $row['assignment_id'], 
            'assignment'
        );
        
        $notificationCount++;
    }
    
    // Update the last checked time
    updateLastCheckedTime($lastCheckedKey);
    
    return $notificationCount;
}

/**
 * Get the last time we checked for notifications
 * @param string $key The key to identify the check type
 * @return string The datetime of last check
 */
function getLastCheckedTime($key) {
    global $conn;
    
    // Create the table if it doesn't exist
    $checkTableSql = "SHOW TABLES LIKE 'tbl_settings'";
    $tableResult = $conn->query($checkTableSql);
    
    if ($tableResult->num_rows === 0) {
        $createTableSql = "CREATE TABLE IF NOT EXISTS tbl_settings (
            setting_key VARCHAR(100) PRIMARY KEY,
            setting_value TEXT NOT NULL,
            updated_at DATETIME NOT NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
        $conn->query($createTableSql);
    }
    
    // Get the setting
    $sql = "SELECT setting_value FROM tbl_settings WHERE setting_key = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $key);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        return $row['setting_value'];
    }
    
    // Default to 24 hours ago if not found
    return date('Y-m-d H:i:s', strtotime('-24 hours'));
}

/**
 * Update the last checked time
 * @param string $key The key to identify the check type
 * @return bool Success status
 */
function updateLastCheckedTime($key) {
    global $conn;
    
    $now = date('Y-m-d H:i:s');
    
    $sql = "INSERT INTO tbl_settings (setting_key, setting_value, updated_at) 
            VALUES (?, ?, NOW())
            ON DUPLICATE KEY UPDATE 
            setting_value = VALUES(setting_value),
            updated_at = VALUES(updated_at)";
            
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ss", $key, $now);
    return $stmt->execute();
}

/**
 * AJAX endpoint for notifications
 */
if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
    $action = $_POST['action'] ?? '';
    
    switch ($action) {
        case 'get_notifications':
            $user_id = $_POST['user_id'] ?? 0;
            
            if ($user_id > 0) {
                // Check for new notifications first
                checkForNewAssignments($user_id);
                
                // Then get all notifications including any new ones
                $notifications = getArtistNotifications($user_id);
                $unreadCount = getUnreadNotificationsCount($user_id);
                
                echo json_encode([
                    'status' => 'success',
                    'notifications' => $notifications,
                    'unread_count' => $unreadCount
                ]);
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Invalid user ID']);
            }
            break;
            
        case 'mark_read':
            $user_id = $_POST['user_id'] ?? 0;
            $notification_id = $_POST['notification_id'] ?? 0;
            
            if ($user_id > 0 && $notification_id > 0) {
                $success = markNotificationAsRead($notification_id, $user_id);
                $unreadCount = getUnreadNotificationsCount($user_id);
                
                echo json_encode([
                    'status' => $success ? 'success' : 'error',
                    'message' => $success ? 'Notification marked as read' : 'Failed to mark notification as read',
                    'unread_count' => $unreadCount
                ]);
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Invalid parameters']);
            }
            break;
            
        case 'mark_all_read':
            $user_id = $_POST['user_id'] ?? 0;
            
            if ($user_id > 0) {
                $success = markAllNotificationsAsRead($user_id);
                
                echo json_encode([
                    'status' => $success ? 'success' : 'error',
                    'message' => $success ? 'All notifications marked as read' : 'Failed to mark notifications as read',
                    'unread_count' => 0
                ]);
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Invalid user ID']);
            }
            break;
            
        default:
            echo json_encode(['status' => 'error', 'message' => 'Invalid action']);
            break;
    }
    
    exit;
} 