<?php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'User not authenticated']);
    exit;
}

// Connect to database
require_once __DIR__ . '/../../includes/db_connection.php';

// Get current user ID
$user_id = $_SESSION['user_id'];

// Log the action for debugging
function logAction($action, $data = [])
{
    $log_message = "[" . date('Y-m-d H:i:s') . "] [NOTIFICATION_AJAX] " . $action;
    if (!empty($data)) {
        $log_message .= ": " . json_encode($data);
    }
    error_log($log_message);
}

// Handle AJAX requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    switch ($action) {
        // Mark a notification as read
        case 'mark_read':
            $notification_id = isset($_POST['notification_id']) ? intval($_POST['notification_id']) : 0;

            if ($notification_id <= 0) {
                echo json_encode(['status' => 'error', 'message' => 'Invalid notification ID']);
                exit;
            }

            // Update the notification as read
            $stmt = $conn->prepare("UPDATE tbl_notifications SET is_read = 1 WHERE notification_id = ? AND (user_id = ? OR user_id IS NULL)");
            $stmt->bind_param("ii", $notification_id, $user_id);

            if ($stmt->execute()) {
                logAction('Marked notification as read', ['notification_id' => $notification_id, 'user_id' => $user_id]);
                echo json_encode(['status' => 'success', 'message' => 'Notification marked as read']);
            } else {
                logAction('Failed to mark notification as read', ['error' => $stmt->error]);
                echo json_encode(['status' => 'error', 'message' => 'Failed to mark notification as read: ' . $stmt->error]);
            }
            break;

        // Mark all notifications as read
        case 'mark_all_read':
            $stmt = $conn->prepare("UPDATE tbl_notifications SET is_read = 1 WHERE (user_id = ? OR user_id IS NULL)");
            $stmt->bind_param("i", $user_id);

            if ($stmt->execute()) {
                logAction('Marked all notifications as read', ['user_id' => $user_id]);
                echo json_encode(['status' => 'success', 'message' => 'All notifications marked as read']);
            } else {
                logAction('Failed to mark all notifications as read', ['error' => $stmt->error]);
                echo json_encode(['status' => 'error', 'message' => 'Failed to mark all notifications as read: ' . $stmt->error]);
            }
            break;

        // Clear all notifications
        case 'clear_all':
            $stmt = $conn->prepare("DELETE FROM tbl_notifications WHERE (user_id = ? OR user_id IS NULL)");
            $stmt->bind_param("i", $user_id);

            if ($stmt->execute()) {
                logAction('Cleared all notifications', ['user_id' => $user_id]);
                echo json_encode(['status' => 'success', 'message' => 'All notifications cleared']);
            } else {
                logAction('Failed to clear all notifications', ['error' => $stmt->error]);
                echo json_encode(['status' => 'error', 'message' => 'Failed to clear all notifications: ' . $stmt->error]);
            }
            break;

        // Add a new notification (for testing)
        case 'add_notification':
            $message = $_POST['message'] ?? 'Test notification';
            $type = $_POST['type'] ?? 'info';
            $entity_id = isset($_POST['entity_id']) ? intval($_POST['entity_id']) : null;
            $entity_type = $_POST['entity_type'] ?? null;

            $stmt = $conn->prepare("INSERT INTO tbl_notifications (user_id, message, type, entity_id, entity_type) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("issss", $user_id, $message, $type, $entity_id, $entity_type);

            if ($stmt->execute()) {
                logAction('Added new notification', [
                    'user_id' => $user_id,
                    'message' => $message,
                    'type' => $type
                ]);
                echo json_encode(['status' => 'success', 'message' => 'Notification added successfully']);
            } else {
                logAction('Failed to add notification', ['error' => $stmt->error]);
                echo json_encode(['status' => 'error', 'message' => 'Failed to add notification: ' . $stmt->error]);
            }
            break;

        default:
            echo json_encode(['status' => 'error', 'message' => 'Invalid action specified']);
            break;
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method']);
}