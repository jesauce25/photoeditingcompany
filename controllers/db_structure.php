<?php
// Include database connection
require_once '../includes/db_connection.php';

// Set header for JSON response
header('Content-Type: application/json');

// Check if the request is POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get the action from the request
    $action = $_POST['action'] ?? '';

    // Handle different actions
    switch ($action) {
        case 'create_roles_table':
            createRolesTable();
            break;

        case 'create_tasks_table':
            createTasksTable();
            break;

        default:
            sendResponse(false, 'Invalid action');
            break;
    }
} else {
    sendResponse(false, 'Invalid request method');
}

/**
 * Create the missing tbl_roles table
 */
function createRolesTable()
{
    global $conn;

    try {
        // Check if the table already exists
        $result = $conn->query("SHOW TABLES LIKE 'tbl_roles'");
        if ($result->num_rows > 0) {
            sendResponse(false, 'Table tbl_roles already exists');
            return;
        }

        // Create the table
        $sql = "CREATE TABLE `tbl_roles` (
            `role_id` int(11) NOT NULL AUTO_INCREMENT,
            `role_name` varchar(50) NOT NULL,
            `description` text DEFAULT NULL,
            `date_created` timestamp NOT NULL DEFAULT current_timestamp(),
            `date_updated` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
            PRIMARY KEY (`role_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;";

        if ($conn->query($sql) === TRUE) {
            // Insert default roles
            $insertSql = "INSERT INTO `tbl_roles` (`role_name`, `description`) VALUES
                ('Admin', 'Super administrator with all privileges'),
                ('Project Manager', 'Manages projects and assigns tasks'),
                ('Graphic Artist', 'Works on assigned tasks and uploads results');";

            if ($conn->query($insertSql) === TRUE) {
                sendResponse(true, 'Table tbl_roles created successfully with default roles');
            } else {
                sendResponse(false, 'Table created but failed to insert default roles: ' . $conn->error);
            }
        } else {
            sendResponse(false, 'Failed to create table: ' . $conn->error);
        }
    } catch (Exception $e) {
        sendResponse(false, 'Error: ' . $e->getMessage());
    }
}

/**
 * Create the missing tasks table
 */
function createTasksTable()
{
    global $conn;

    try {
        // Check if the table already exists
        $result = $conn->query("SHOW TABLES LIKE 'tasks'");
        if ($result->num_rows > 0) {
            sendResponse(false, 'Table tasks already exists');
            return;
        }

        // Create the table
        $sql = "CREATE TABLE `tasks` (
            `task_id` int(11) NOT NULL AUTO_INCREMENT,
            `project_id` int(11) NOT NULL,
            `user_id` int(11) NOT NULL,
            `assignment_id` int(11) DEFAULT NULL,
            `task_description` text DEFAULT NULL,
            `role` varchar(50) NOT NULL,
            `status` enum('pending','in_progress','qa','completed','delayed') NOT NULL DEFAULT 'pending',
            `deadline` date NOT NULL,
            `date_assigned` timestamp NOT NULL DEFAULT current_timestamp(),
            `last_updated` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
            `completed_date` timestamp NULL DEFAULT NULL,
            PRIMARY KEY (`task_id`),
            KEY `project_id` (`project_id`),
            KEY `user_id` (`user_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;";

        if ($conn->query($sql) === TRUE) {
            // Create relationship between tasks and project_assignments
            $migrateSql = "INSERT INTO tasks (
                project_id, user_id, assignment_id, role, status, deadline, date_assigned
            )
            SELECT 
                pa.project_id, pa.user_id, pa.assignment_id, pa.role_task, pa.status_assignee,
                pa.deadline, pa.assigned_date
            FROM tbl_project_assignments pa
            WHERE pa.status_assignee != 'deleted'";

            $conn->query($migrateSql); // Attempt to migrate, but don't fail if no data

            sendResponse(true, 'Table tasks created successfully');
        } else {
            sendResponse(false, 'Failed to create table: ' . $conn->error);
        }
    } catch (Exception $e) {
        sendResponse(false, 'Error: ' . $e->getMessage());
    }
}

/**
 * Send JSON response
 */
function sendResponse($success, $message, $data = [])
{
    echo json_encode([
        'success' => $success,
        'message' => $message,
        'data' => $data
    ]);
    exit;
}