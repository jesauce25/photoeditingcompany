<?php
// Prevent PHP errors/notices from corrupting JSON output
error_reporting(0);
ini_set('display_errors', 0);

// Custom error handler for logging
function logError($message)
{
    // Log to server error log
    error_log("TASK_CONTROLLER_ERROR: " . $message);
}

// Set up exception handler
set_exception_handler(function ($e) {
    error_log("TASK_CONTROLLER_EXCEPTION: " . $e->getMessage() . " in " . $e->getFile() . " on line " . $e->getLine());
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'message' => 'Server error occurred: ' . $e->getMessage()
    ]);
    exit;
});

try {
    require_once '../../includes/db_connection.php';

    // Check if this file is being accessed directly
    if (basename($_SERVER['PHP_SELF']) == basename(__FILE__)) {
        // Handle direct access via AJAX
        header('Content-Type: application/json');

        if ($_SERVER['REQUEST_METHOD'] === 'GET') {
            if (isset($_GET['action'])) {
                if ($_GET['action'] === 'get_user_tasks') {
                    getUserTasks();
                } else if ($_GET['action'] === 'get_task_images') {
                    getTaskImages();
                } else {
                    echo json_encode([
                        'success' => false,
                        'message' => 'Invalid action specified'
                    ]);
                }
            } else {
                echo json_encode([
                    'success' => false,
                    'message' => 'No action specified'
                ]);
            }
        } else {
            echo json_encode([
                'success' => false,
                'message' => 'Invalid request method'
            ]);
        }
        exit;
    }

    // Handle all task-related requests when included as part of another script
    if (isset($_GET['action'])) {
        if ($_GET['action'] === 'get_user_tasks') {
            getUserTasks();
        } else if ($_GET['action'] === 'get_task_images') {
            getTaskImages();
        }
    }
} catch (Exception $e) {
    logError("Global exception: " . $e->getMessage());
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'message' => 'Server error occurred'
    ]);
    exit;
}

/**
 * Get all tasks assigned to a specific user with optional filtering
 */
function getUserTasks()
{
    global $conn;

    try {
        // Get user ID from request
        $userId = isset($_GET['user_id']) ? intval($_GET['user_id']) : 0;

        // Validate user ID
        if ($userId <= 0) {
            echo json_encode([
                'success' => false,
                'message' => 'Invalid user ID'
            ]);
            return;
        }

        // Get optional filter parameters
        $status = isset($_GET['status']) ? $_GET['status'] : '';
        $project = isset($_GET['project']) ? $_GET['project'] : '';
        $dateRange = isset($_GET['date_range']) ? $_GET['date_range'] : '';
        $taskId = isset($_GET['task_id']) ? $_GET['task_id'] : '';
        $showHidden = isset($_GET['show_hidden']) ? (bool) $_GET['show_hidden'] : false;

        // Build the query base
        $query = "SELECT 
                    pa.assignment_id,
                    p.project_title,
                    p.project_id,
                    pa.role_task,
                    pa.assigned_images,
                    pa.status_assignee,
                    pa.assigned_date,
                    pa.deadline,
                    pa.is_hidden,
                    p.priority,
                    p.deadline as project_deadline,
                    p.date_arrived,
                    c.company_name,
                    p.total_images,
                    (
                        SELECT COUNT(*) 
                        FROM tbl_project_images pi 
                        WHERE pi.assignment_id = pa.assignment_id 
                        AND pi.status_image IN ('finish', 'qa', 'completed')
                    ) as completed_images,
                    (
                        SELECT GROUP_CONCAT(DISTINCT pi.image_role SEPARATOR ',') 
                        FROM tbl_project_images pi 
                        WHERE pi.assignment_id = pa.assignment_id 
                        AND pi.image_role IS NOT NULL
                        AND pi.image_role != ''
                    ) as image_roles
                  FROM 
                    tbl_project_assignments pa
                  JOIN 
                    tbl_projects p ON pa.project_id = p.project_id
                  LEFT JOIN
                    tbl_companies c ON p.company_id = c.company_id
                  WHERE 
                    pa.user_id = ?";

        // Add is_hidden filter based on showHidden parameter
        if ($showHidden) {
            $query .= " AND pa.is_hidden = 1";
        } else {
            $query .= " AND (pa.is_hidden = 0 OR pa.is_hidden IS NULL)";
        }

        // Prepare parameters array
        $params = [$userId];
        $types = "i"; // integer for user_id

        // Add filters if provided
        if (!empty($status)) {
            $query .= " AND pa.status_assignee = ?";
            $params[] = $status;
            $types .= "s"; // string
        }

        if (!empty($project)) {
            $query .= " AND p.project_title LIKE ?";
            $params[] = "%$project%";
            $types .= "s"; // string
        }

        if (!empty($taskId)) {
            // Check if it's numeric (assignment_id) or text (search in role_task)
            if (is_numeric($taskId)) {
                $query .= " AND pa.assignment_id = ?";
                $params[] = intval($taskId);
                $types .= "i"; // integer
            } else {
                $query .= " AND pa.role_task LIKE ?";
                $params[] = "%$taskId%";
                $types .= "s"; // string
            }
        }

        if (!empty($dateRange)) {
            // Parse date range (format: MM/DD/YYYY - MM/DD/YYYY)
            $dates = explode(' - ', $dateRange);
            if (count($dates) == 2) {
                $startDate = date('Y-m-d', strtotime($dates[0]));
                $endDate = date('Y-m-d', strtotime($dates[1]));

                $query .= " AND DATE(pa.assigned_date) BETWEEN ? AND ?";
                $params[] = $startDate;
                $params[] = $endDate;
                $types .= "ss"; // two strings
            }
        }

        // Add order by
        $query .= " ORDER BY pa.assigned_date DESC";

        // Prepare and execute the query
        $stmt = $conn->prepare($query);

        if (!$stmt) {
            throw new Exception("Failed to prepare statement: " . $conn->error);
        }

        // Bind parameters dynamically
        $stmt->bind_param($types, ...$params);

        if (!$stmt->execute()) {
            throw new Exception("Failed to execute statement: " . $stmt->error);
        }

        $result = $stmt->get_result();

        $tasks = [];
        while ($row = $result->fetch_assoc()) {
            $tasks[] = $row;
        }

        echo json_encode([
            'success' => true,
            'tasks' => $tasks
        ]);

        $stmt->close();
    } catch (Exception $e) {
        logError("getUserTasks: " . $e->getMessage());
        echo json_encode([
            'success' => false,
            'message' => 'Failed to load tasks: ' . $e->getMessage()
        ]);
    }
}

/**
 * Get images assigned to a specific task
 */
function getTaskImages()
{
    global $conn;

    try {
        // Get assignment ID from request
        $assignmentId = isset($_GET['assignment_id']) ? intval($_GET['assignment_id']) : 0;

        // Validate assignment ID
        if ($assignmentId <= 0) {
            echo json_encode([
                'success' => false,
                'message' => 'Invalid assignment ID'
            ]);
            return;
        }

        // Build query to get images for this task
        $query = "SELECT 
                    pi.*,
                    p.project_id 
                  FROM 
                    tbl_project_images pi
                  JOIN 
                    tbl_project_assignments pa ON pi.assignment_id = pa.assignment_id
                  JOIN 
                    tbl_projects p ON pa.project_id = p.project_id
                  WHERE 
                    pi.assignment_id = ?
                  ORDER BY 
                    pi.upload_date DESC";

        // Prepare and execute the query
        $stmt = $conn->prepare($query);

        if (!$stmt) {
            throw new Exception("Failed to prepare statement: " . $conn->error);
        }

        $stmt->bind_param("i", $assignmentId);

        if (!$stmt->execute()) {
            throw new Exception("Failed to execute statement: " . $stmt->error);
        }

        $result = $stmt->get_result();

        $images = [];
        while ($row = $result->fetch_assoc()) {
            // Format the image path for display if needed
            if (empty($row['file_name']) && !empty($row['image_path'])) {
                $row['file_name'] = $row['image_path'];
            }
            $images[] = $row;
        }

        echo json_encode([
            'success' => true,
            'images' => $images
        ]);

        $stmt->close();
    } catch (Exception $e) {
        logError("getTaskImages: " . $e->getMessage());
        echo json_encode([
            'success' => false,
            'message' => 'Failed to load images: ' . $e->getMessage()
        ]);
    }
}