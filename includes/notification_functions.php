<?php
/**
 * Notification Functions
 * 
 * This file contains functions for managing notifications throughout the system.
 */

/**
 * Add a notification to the database
 * 
 * @param int|null $user_id The user ID to send notification to (null for system-wide)
 * @param string $message The notification message
 * @param string $type The notification type (project, assignment, deadline, user, warning)
 * @param int|null $entity_id Related entity ID (project_id, assignment_id, etc.)
 * @param string|null $entity_type Related entity type (project, assignment, etc.)
 * @return bool True if notification was added, false otherwise
 */
function add_notification($user_id, $message, $type = 'info', $entity_id = null, $entity_type = null)
{
    global $conn;

    // Connect to database if not already connected
    if (!isset($conn) || $conn === null) {
        require_once __DIR__ . '/db_connection.php';
    }

    // Ensure notifications table exists
    $createTableSQL = "CREATE TABLE IF NOT EXISTS tbl_notifications (
        notification_id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT,
        message VARCHAR(255) NOT NULL,
        type VARCHAR(50) NOT NULL,
        is_read TINYINT(1) DEFAULT 0,
        entity_id INT,
        entity_type VARCHAR(50),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES tbl_users(user_id) ON DELETE CASCADE
    )";
    $conn->query($createTableSQL);

    // Prepare the statement
    $stmt = $conn->prepare("INSERT INTO tbl_notifications (user_id, message, type, entity_id, entity_type) VALUES (?, ?, ?, ?, ?)");
    if (!$stmt) {
        error_log("Notification Error: Failed to prepare statement - " . $conn->error);
        return false;
    }

    // Bind parameters
    $stmt->bind_param("issss", $user_id, $message, $type, $entity_id, $entity_type);

    // Execute the statement
    $result = $stmt->execute();

    if (!$result) {
        error_log("Notification Error: Failed to add notification - " . $stmt->error);
        return false;
    }

    return true;
}

/**
 * Add notification for assignment status change
 * 
 * @param int $assignment_id The assignment ID
 * @param string $new_status The new status
 * @param int|null $user_id The user ID to notify (null for system-wide)
 * @return bool Success status
 */
function notify_assignment_status_change($assignment_id, $new_status, $user_id = null)
{
    global $conn;

    // Connect to database if not already connected
    if (!isset($conn) || $conn === null) {
        require_once __DIR__ . '/db_connection.php';
    }

    // Get assignment details
    $stmt = $conn->prepare("
        SELECT pa.project_id, p.project_title, u.first_name, u.last_name 
        FROM tbl_project_assignments pa
        JOIN tbl_projects p ON pa.project_id = p.project_id
        JOIN tbl_users u ON pa.user_id = u.user_id
        WHERE pa.assignment_id = ?
    ");
    $stmt->bind_param("i", $assignment_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result && $row = $result->fetch_assoc()) {
        $project_title = $row['project_title'];
        $assignee_name = $row['first_name'] . ' ' . $row['last_name'];
        $project_id = $row['project_id'];

        // Format status text
        $status_text = str_replace('_', ' ', $new_status);

        // Create appropriate message based on status
        switch ($new_status) {
            case 'in_progress':
                $message = "$assignee_name started work on project: $project_title";
                break;
            case 'finish':
                $message = "$assignee_name finished work on project: $project_title";
                break;
            case 'qa':
                $message = "Task sent for approval in project: $project_title";
                break;
            case 'approved':
                $message = "Task has been approved in project: $project_title";
                break;
            case 'completed':
                $message = "Task has been completed in project: $project_title";
                break;
            default:
                $message = "Task status changed to $status_text in project: $project_title";
        }

        return add_notification($user_id, $message, 'assignment', $project_id, 'project');
    }

    return false;
}

/**
 * Add notification for overdue assignment
 * 
 * @param int $assignment_id The assignment ID
 * @param int|null $user_id The user ID to notify (null for system-wide)
 * @return bool Success status
 */
function notify_overdue_assignment($assignment_id, $user_id = null)
{
    global $conn;

    // Connect to database if not already connected
    if (!isset($conn) || $conn === null) {
        require_once __DIR__ . '/db_connection.php';
    }

    // Get assignment details
    $stmt = $conn->prepare("
        SELECT pa.project_id, p.project_title, u.first_name, u.last_name, pa.deadline,
            DATEDIFF(CURDATE(), pa.deadline) as days_overdue
        FROM tbl_project_assignments pa
        JOIN tbl_projects p ON pa.project_id = p.project_id
        JOIN tbl_users u ON pa.user_id = u.user_id
        WHERE pa.assignment_id = ? AND pa.deadline < CURDATE()
    ");
    $stmt->bind_param("i", $assignment_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result && $row = $result->fetch_assoc()) {
        $project_title = $row['project_title'];
        $assignee_name = $row['first_name'] . ' ' . $row['last_name'];
        $project_id = $row['project_id'];
        $days_overdue = $row['days_overdue'];

        $message = "Assignment for $assignee_name is overdue by $days_overdue day(s) in project: $project_title";

        return add_notification($user_id, $message, 'deadline', $project_id, 'project');
    }

    return false;
}

/**
 * Add notification for project due tomorrow
 * 
 * @param int $project_id The project ID
 * @param int|null $user_id The user ID to notify (null for system-wide)
 * @return bool Success status
 */
function notify_project_due_tomorrow($project_id, $user_id = null)
{
    global $conn;

    // Connect to database if not already connected
    if (!isset($conn) || $conn === null) {
        require_once __DIR__ . '/db_connection.php';
    }

    // Get project details
    $stmt = $conn->prepare("
        SELECT project_title 
        FROM tbl_projects 
        WHERE project_id = ? AND DATE(deadline) = DATE_ADD(CURDATE(), INTERVAL 1 DAY)
    ");
    $stmt->bind_param("i", $project_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result && $row = $result->fetch_assoc()) {
        $project_title = $row['project_title'];

        $message = "Project: $project_title is due tomorrow";

        return add_notification($user_id, $message, 'deadline', $project_id, 'project');
    }

    return false;
}