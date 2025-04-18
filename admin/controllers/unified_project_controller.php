<?php
/**
 * Unified Project Controller
 * Combined functionality from project_controller.php and edit_project_functions.php
 */

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Log function for debugging
function debug_log($message, $data = null)
{
    $timestamp = date('Y-m-d H:i:s');
    $log = "[$timestamp] $message";
    if ($data !== null) {
        $log .= ": " . print_r($data, true);
    }
    error_log($log);
}

// Ensure database connection is available
if (!isset($conn) || !$conn) {
    // Try admin path first
    if (file_exists(__DIR__ . '/../includes/db_connection.php')) {
        require_once __DIR__ . '/../includes/db_connection.php';
    }
    // Then try root path
    else if (file_exists(__DIR__ . '/../../includes/db_connection.php')) {
        require_once __DIR__ . '/../../includes/db_connection.php';
    } else {
        // Log error if neither path works
        error_log("Database connection file not found in expected locations");
    }

    // Double check connection
    if (!isset($conn) || !$conn) {
        // Create connection directly as fallback
        $host = "localhost";
        $username = "root";
        $password = "";
        $database = "db_projectms";

        $conn = new mysqli($host, $username, $password, $database);

        if ($conn->connect_error) {
            error_log("Database connection failed: " . $conn->connect_error);
        }
    }
}

/**
 * Add a new project with associated images
 * 
 * @param string $project_title The title of the project
 * @param int $company_id The company ID associated with the project
 * @param string $description The project description
 * @param string $date_arrived The date the project arrived
 * @param string $deadline The project deadline
 * @param string $priority The project priority (low, medium, high, urgent)
 * @param string $status The project status (pending, in_progress, review, completed, delayed)
 * @param int $total_images The total number of images
 * @param int $created_by The user ID who created the project
 * @param array $file_data Array of file information (name, type, size)
 * @param bool $store_only_names If true, only store the original filenames without uploading files
 * @return array Status of the operation and the inserted project ID
 */
function addProject($project_title, $company_id, $description, $date_arrived, $deadline, $priority, $status, $total_images, $created_by, $file_data, $store_only_names = false)
{
    global $conn;

    try {
        // Start transaction
        $conn->begin_transaction();

        // Insert project data
        $sql = "INSERT INTO tbl_projects (project_title, company_id, description, date_arrived, deadline, priority, status_project, total_images, created_by) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";

        error_log("Adding project with status_project: " . $status);

        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            error_log("SQL Prepare Error: " . $conn->error);
            return ['status' => 'error', 'message' => 'Error preparing statement: ' . $conn->error];
        }

        $stmt->bind_param("sisssssis", $project_title, $company_id, $description, $date_arrived, $deadline, $priority, $status, $total_images, $created_by);

        if (!$stmt->execute()) {
            $conn->rollback();
            return ['status' => 'error', 'message' => 'Error adding project: ' . $stmt->error];
        }

        $project_id = $conn->insert_id;

        // Save file information if any
        $uploaded_files = [];

        if (!empty($file_data) && is_array($file_data)) {
            // If we're using the JSON array from the hidden field
            foreach ($file_data as $file) {
                if (isset($file['name']) && !empty($file['name'])) {
                    $file_name = $file['name'];
                    $file_type = $file['type'] ?? 'application/octet-stream';
                    $file_size = $file['size'] ?? 0;

                    // Store file information in database
                    $sql = "INSERT INTO tbl_project_images (project_id, image_path, file_type, file_size) VALUES (?, ?, ?, ?)";
                    $stmt = $conn->prepare($sql);
                    $stmt->bind_param("issi", $project_id, $file_name, $file_type, $file_size);

                    if (!$stmt->execute()) {
                        $conn->rollback();
                        return ['status' => 'error', 'message' => 'Error saving file information: ' . $stmt->error];
                    }

                    $uploaded_files[] = $file_name;
                }
            }
        }

        // Commit transaction
        $conn->commit();

        return [
            'status' => 'success',
            'message' => 'Project added successfully',
            'project_id' => $project_id,
            'uploaded_files' => $uploaded_files
        ];

    } catch (Exception $e) {
        $conn->rollback();
        return ['status' => 'error', 'message' => 'Exception: ' . $e->getMessage()];
    }
}

/**
 * Get all projects with status other than 'delayed'
 * 
 * @param string $search Optional search term
 * @param array $filters Optional filters
 * @return array Array of projects
 */
function getAllProjects($search = '', $filters = [])
{
    global $conn;

    $sql = "SELECT DISTINCT p.*, c.company_name, COUNT(pi.image_id) as image_count
            FROM tbl_projects p
            LEFT JOIN tbl_companies c ON p.company_id = c.company_id
            LEFT JOIN tbl_project_images pi ON p.project_id = pi.project_id
            LEFT JOIN tbl_project_assignments pa ON p.project_id = pa.project_id
            WHERE p.status_project != 'delayed'
            AND (pa.status_assignee != 'delayed' OR pa.status_assignee IS NULL)
            AND (pa.deadline >= CURDATE() OR pa.deadline IS NULL)";

    // Add search condition if provided
    if (!empty($search)) {
        $search = "%$search%";
        $sql .= " AND (p.project_title LIKE ? OR c.company_name LIKE ? OR p.description LIKE ?)";
    }

    // Add filters if provided
    if (!empty($filters)) {
        foreach ($filters as $column => $value) {
            if ($value !== '') {
                $sql .= " AND p.$column = ?";
            }
        }
    }

    $sql .= " GROUP BY p.project_id ORDER BY p.date_created DESC";

    error_log("getAllProjects SQL: " . $sql);

    $stmt = $conn->prepare($sql);

    if (!$stmt) {
        error_log("SQL Prepare Error: " . $conn->error);
        return [];
    }

    // Bind parameters
    if (!empty($search) || !empty($filters)) {
        $types = '';
        $params = [];

        if (!empty($search)) {
            $types .= 'sss';
            $params[] = $search;
            $params[] = $search;
            $params[] = $search;
        }

        if (!empty($filters)) {
            foreach ($filters as $column => $value) {
                if ($value !== '') {
                    $types .= 's';
                    $params[] = $value;
                }
            }
        }

        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }
    }

    $stmt->execute();
    $result = $stmt->get_result();

    $projects = [];
    while ($row = $result->fetch_assoc()) {
        // Get project images
        $imgSql = "SELECT * FROM tbl_project_images WHERE project_id = ?";
        $imgStmt = $conn->prepare($imgSql);
        $imgStmt->bind_param("i", $row['project_id']);
        $imgStmt->execute();
        $imgResult = $imgStmt->get_result();

        $images = [];
        while ($imgRow = $imgResult->fetch_assoc()) {
            $images[] = $imgRow;
        }

        $row['images'] = $images;
        $projects[] = $row;
    }

    return $projects;
}

/**
 * Get a single project by ID
 * 
 * @param int $project_id The project ID
 * @return array|null Project data or null if not found
 */
function getProjectById($project_id)
{
    global $conn;

    $sql = "SELECT p.*, c.company_name 
            FROM tbl_projects p
            LEFT JOIN tbl_companies c ON p.company_id = c.company_id
            WHERE p.project_id = ?";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $project_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        return null;
    }

    $project = $result->fetch_assoc();

    // Get project images
    $imgSql = "SELECT * FROM tbl_project_images WHERE project_id = ?";
    $imgStmt = $conn->prepare($imgSql);
    $imgStmt->bind_param("i", $project_id);
    $imgStmt->execute();
    $imgResult = $imgStmt->get_result();

    $images = [];
    while ($imgRow = $imgResult->fetch_assoc()) {
        $images[] = $imgRow;
    }

    $project['images'] = $images;

    return $project;
}

/**
 * Delete a project and its associated images
 * 
 * @param int $project_id The project ID to delete
 * @return array Status of the operation
 */
function deleteProject($project_id)
{
    global $conn;

    try {
        // Start transaction
        $conn->begin_transaction();

        // Get image paths before deleting records
        $imgSql = "SELECT image_path FROM tbl_project_images WHERE project_id = ?";
        $imgStmt = $conn->prepare($imgSql);
        $imgStmt->bind_param("i", $project_id);
        $imgStmt->execute();
        $imgResult = $imgStmt->get_result();

        $imagePaths = [];
        while ($row = $imgResult->fetch_assoc()) {
            $imagePaths[] = $row['image_path'];
        }

        // Delete project images from database
        $delImgSql = "DELETE FROM tbl_project_images WHERE project_id = ?";
        $delImgStmt = $conn->prepare($delImgSql);
        $delImgStmt->bind_param("i", $project_id);

        if (!$delImgStmt->execute()) {
            $conn->rollback();
            return ['status' => 'error', 'message' => 'Error deleting project images: ' . $delImgStmt->error];
        }

        // Delete project assignments
        $delAssignSql = "DELETE FROM tbl_project_assignments WHERE project_id = ?";
        $delAssignStmt = $conn->prepare($delAssignSql);
        $delAssignStmt->bind_param("i", $project_id);

        if (!$delAssignStmt->execute()) {
            $conn->rollback();
            return ['status' => 'error', 'message' => 'Error deleting project assignments: ' . $delAssignStmt->error];
        }

        // Delete project
        $delProjSql = "DELETE FROM tbl_projects WHERE project_id = ?";
        $delProjStmt = $conn->prepare($delProjSql);
        $delProjStmt->bind_param("i", $project_id);

        if (!$delProjStmt->execute()) {
            $conn->rollback();
            return ['status' => 'error', 'message' => 'Error deleting project: ' . $delProjStmt->error];
        }

        // Delete physical image files
        foreach ($imagePaths as $path) {
            $fullPath = "../" . $path;
            if (file_exists($fullPath)) {
                unlink($fullPath);
            }
        }

        // Commit transaction
        $conn->commit();

        return ['status' => 'success', 'message' => 'Project deleted successfully'];

    } catch (Exception $e) {
        $conn->rollback();
        return ['status' => 'error', 'message' => 'Exception: ' . $e->getMessage()];
    }
}

/**
 * Get all companies for dropdown
 * 
 * @return array List of companies
 */
function getCompaniesForDropdown()
{
    global $conn;

    $sql = "SELECT company_id, company_name FROM tbl_companies ORDER BY company_name";
    $result = $conn->query($sql);

    $companies = [];
    while ($row = $result->fetch_assoc()) {
        $companies[] = $row;
    }

    return $companies;
}

/**
 * Get all graphic artists for assignment
 * @return array Array of graphic artists
 */
function getGraphicArtists()
{
    global $conn;
    $artists = [];

    $sql = "SELECT a.account_id, a.user_id, a.username, a.role, 
            u.first_name, u.last_name, 
            CONCAT(u.first_name, ' ', u.last_name) AS full_name
            FROM tbl_accounts a
            JOIN tbl_users u ON a.user_id = u.user_id
            WHERE a.role LIKE '%graphic%artist%' AND a.status = 'Active'
            ORDER BY u.first_name, u.last_name";

    $result = $conn->query($sql);

    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $artists[] = $row;
        }
    } else {
        // Fallback to get all active users if no graphic artists found
        $backupSql = "SELECT a.account_id, a.user_id, a.username, a.role, 
                u.first_name, u.last_name, 
                CONCAT(u.first_name, ' ', u.last_name) AS full_name
                FROM tbl_accounts a
                JOIN tbl_users u ON a.user_id = u.user_id
                WHERE a.status = 'Active'
                ORDER BY u.first_name, u.last_name";

        $backupResult = $conn->query($backupSql);

        if ($backupResult && $backupResult->num_rows > 0) {
            while ($row = $backupResult->fetch_assoc()) {
                $artists[] = $row;
            }
        }
    }

    return $artists;
}

/**
 * Get all available roles for task assignment
 * @return array Array of roles
 */
function getAvailableRoles()
{
    return [
        'Retouch' => 'Basic Retouching',
        'Color' => 'Color Correction',
        'Extraction' => 'Background Extraction',
        'Final' => 'Final Review',
        'Other' => 'Other Tasks'
    ];
}

/**
 * Get project images
 * @param int $project_id
 * @return array
 */
function getProjectImages($project_id)
{
    global $conn;
    $project_id = intval($project_id);

    // Check if assignment_id column exists
    $checkColumnSql = "SHOW COLUMNS FROM tbl_project_images LIKE 'assignment_id'";
    $checkColumnResult = $conn->query($checkColumnSql);
    $hasAssignmentColumn = $checkColumnResult->num_rows > 0;

    // Only select columns that exist in the database table
    if ($hasAssignmentColumn) {
        $sql = "SELECT image_id, project_id, image_path, file_type, file_size, assignment_id, status_image 
                FROM tbl_project_images 
                WHERE project_id = ?";
    } else {
        $sql = "SELECT image_id, project_id, image_path, file_type, file_size
                FROM tbl_project_images 
                WHERE project_id = ?";
    }

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $project_id);
    $stmt->execute();
    $result = $stmt->get_result();

    $images = [];
    while ($row = $result->fetch_assoc()) {
        // Extract original filename from the image_path
        $parts = explode('_', basename($row['image_path']), 3);
        $row['file_name'] = isset($parts[2]) ? $parts[2] : basename($row['image_path']);

        // Add assignment_id if it doesn't exist
        if (!$hasAssignmentColumn) {
            $row['assignment_id'] = null;
        }

        // Add status_image if it doesn't exist
        if (!isset($row['status_image'])) {
            $row['status_image'] = 'available';
        }

        $images[] = $row;
    }

    return $images;
}

/**
 * Get project assignments
 * @param int $project_id
 * @return array
 */
function getProjectAssignments($project_id)
{
    global $conn;
    $project_id = intval($project_id);

    // Check if assignment_id column exists in tbl_project_images
    $checkColumnSql = "SHOW COLUMNS FROM tbl_project_images LIKE 'assignment_id'";
    $checkColumnResult = $conn->query($checkColumnSql);
    $hasAssignmentColumn = $checkColumnResult->num_rows > 0;

    // Check if status_assignee column exists in tbl_project_assignments
    $checkStatusAssigneeColumn = "SHOW COLUMNS FROM tbl_project_assignments LIKE 'status_assignee'";
    $statusResult = $conn->query($checkStatusAssigneeColumn);
    $hasStatusAssigneeColumn = $statusResult && $statusResult->num_rows > 0;

    error_log("Checking columns: hasAssignmentColumn=" . ($hasAssignmentColumn ? "true" : "false") .
        ", hasStatusAssigneeColumn=" . ($hasStatusAssigneeColumn ? "true" : "false"));

    // Check which user table exists
    $checkAccountsTable = "SHOW TABLES LIKE 'tbl_accounts'";
    $accountsTableResult = $conn->query($checkAccountsTable);
    $hasAccountsTable = $accountsTableResult && $accountsTableResult->num_rows > 0;

    if ($hasAccountsTable) {
        // Using tbl_accounts - The key issue is that we need to join using account_id, not user_id!
        if ($hasAssignmentColumn) {
            $sql = "SELECT pa.*, a.username, a.role, u.first_name, u.last_name, 
                    (SELECT COUNT(*) FROM tbl_project_images pi WHERE pi.assignment_id = pa.assignment_id) AS assigned_images 
                    FROM tbl_project_assignments pa 
                    LEFT JOIN tbl_accounts a ON pa.user_id = a.account_id
                    LEFT JOIN tbl_users u ON a.user_id = u.user_id 
                    WHERE pa.project_id = ?
                    ORDER BY pa.assignment_id ASC";
        } else {
            // If assignment_id doesn't exist, set assigned_images to 0
            $sql = "SELECT pa.*, a.username, a.role, u.first_name, u.last_name, 
                    0 AS assigned_images 
                    FROM tbl_project_assignments pa 
                    LEFT JOIN tbl_accounts a ON pa.user_id = a.account_id
                    LEFT JOIN tbl_users u ON a.user_id = u.user_id
                    WHERE pa.project_id = ?
                    ORDER BY pa.assignment_id ASC";
        }
    } else {
        // Fallback to tbl_users only
        if ($hasAssignmentColumn) {
            $sql = "SELECT pa.*, u.first_name, u.last_name, 
                    (SELECT COUNT(*) FROM tbl_project_images pi WHERE pi.assignment_id = pa.assignment_id) AS assigned_images 
                    FROM tbl_project_assignments pa 
                    LEFT JOIN tbl_users u ON pa.user_id = u.user_id 
                    WHERE pa.project_id = ?
                    ORDER BY pa.assignment_id ASC";
        } else {
            // If assignment_id doesn't exist, set assigned_images to 0
            $sql = "SELECT pa.*, u.first_name, u.last_name, 
                    0 AS assigned_images 
                    FROM tbl_project_assignments pa 
                    LEFT JOIN tbl_users u ON pa.user_id = u.user_id 
                    WHERE pa.project_id = ?
                    ORDER BY pa.assignment_id ASC";
        }
    }

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $project_id);
    $stmt->execute();
    $result = $stmt->get_result();

    $assignments = [];
    while ($row = $result->fetch_assoc()) {
        // Calculate if deadline is overdue
        if (isset($row['deadline'])) {
            $deadline = new DateTime($row['deadline']);
            $today = new DateTime();
            $row['is_overdue'] = $deadline < $today;
        } else {
            $row['is_overdue'] = false;
        }

        $assignments[] = $row;
    }

    return $assignments;
}

/**
 * Get project statistics for dashboard display
 * @param int $project_id Project ID
 * @return array Project statistics
 */
function getProjectStats($project_id)
{
    global $conn;

    // Get total images
    $totalSql = "SELECT COUNT(*) as total FROM tbl_project_images WHERE project_id = ?";
    $totalStmt = $conn->prepare($totalSql);
    $totalStmt->bind_param("i", $project_id);
    $totalStmt->execute();
    $totalResult = $totalStmt->get_result();
    $totalRow = $totalResult->fetch_assoc();
    $total = $totalRow['total'] ?? 0;

    // Get assigned images
    $assignedSql = "SELECT COUNT(*) as assigned FROM tbl_project_images WHERE project_id = ? AND assignment_id IS NOT NULL";
    $assignedStmt = $conn->prepare($assignedSql);
    $assignedStmt->bind_param("i", $project_id);
    $assignedStmt->execute();
    $assignedResult = $assignedStmt->get_result();
    $assignedRow = $assignedResult->fetch_assoc();
    $assigned = $assignedRow['assigned'] ?? 0;

    // Get completed images
    $completedSql = "SELECT COUNT(*) as completed FROM tbl_project_images WHERE project_id = ? AND status_image = 'completed'";
    $completedStmt = $conn->prepare($completedSql);
    $completedStmt->bind_param("i", $project_id);
    $completedStmt->execute();
    $completedResult = $completedStmt->get_result();
    $completedRow = $completedResult->fetch_assoc();
    $completed = $completedRow['completed'] ?? 0;

    // Calculate percentage completion
    $percentComplete = ($total > 0) ? round(($completed / $total) * 100) : 0;

    return [
        'total' => $total,
        'assigned' => $assigned,
        'completed' => $completed,
        'unassigned' => $total - $assigned,
        'percent_complete' => $percentComplete
    ];
}

/**
 * Get project progress stats - Alias for getProjectStats for backward compatibility
 * 
 * @param int $project_id The project ID
 * @return array Project statistics
 */
function getProjectProgressStats($project_id)
{
    return getProjectStats($project_id);
}

/**
 * Get all assignees for a project
 * 
 * @param int $project_id The project ID
 * @return array List of assignees for the project
 */
function getProjectAssignee($project_id)
{
    global $conn;

    debug_log("Getting assignees for project ID: $project_id");

    $sql = "SELECT DISTINCT u.user_id, u.first_name, u.last_name, u.email_address, u.profile_img, pa.role_task, pa.deadline, pa.status_assignee
            FROM tbl_project_assignments pa
            JOIN tbl_users u ON pa.user_id = u.user_id
            WHERE pa.project_id = ?
            ORDER BY u.first_name, u.last_name";

    $stmt = $conn->prepare($sql);

    if (!$stmt) {
        error_log("SQL Prepare Error in getProjectAssignee: " . $conn->error);
        return [];
    }

    $stmt->bind_param("i", $project_id);
    $stmt->execute();
    $result = $stmt->get_result();

    $assignees = [];
    while ($row = $result->fetch_assoc()) {
        $assignees[] = $row;
    }

    debug_log("Found " . count($assignees) . " assignees for project ID: $project_id");

    return $assignees;
}

/**
 * Check database structure to verify columns exist
 * @return array
 */
function checkDatabaseStructure()
{
    global $conn;

    debug_log("Checking database structure");

    $tables = ["tbl_projects", "tbl_project_assignments", "tbl_project_images", "tbl_accounts", "tbl_users"];
    $result = [];

    foreach ($tables as $table) {
        // Check if table exists
        $checkTable = $conn->query("SHOW TABLES LIKE '$table'");
        if ($checkTable && $checkTable->num_rows > 0) {
            $result[$table] = ["exists" => true, "columns" => []];

            // Get columns
            $columns = $conn->query("SHOW COLUMNS FROM $table");
            if ($columns) {
                while ($column = $columns->fetch_assoc()) {
                    $result[$table]["columns"][$column['Field']] = $column['Type'];
                }
            }
        } else {
            $result[$table] = ["exists" => false];
            debug_log("Table does not exist: " . $table);
        }
    }

    debug_log("Database structure", $result);
    return $result;
}


/**
 * Get all delayed projects (projects with overdue assignments)
 * 
 * @param string $search Optional search term
 * @param array $filters Optional filters
 * @return array Array of delayed projects
 */
function getDelayedProjects($search = '', $filters = [])
{
    global $conn;

    $sql = "SELECT DISTINCT p.*, c.company_name, COUNT(pi.image_id) as image_count
            FROM tbl_projects p
            LEFT JOIN tbl_companies c ON p.company_id = c.company_id
            LEFT JOIN tbl_project_images pi ON p.project_id = pi.project_id
            LEFT JOIN tbl_project_assignments pa ON p.project_id = pa.project_id
            WHERE pa.status_assignee = 'delayed' OR 
                  (pa.deadline <= CURDATE() AND pa.status_assignee != 'completed')";

    // Add search condition if provided
    if (!empty($search)) {
        $search = "%$search%";
        $sql .= " AND (p.project_title LIKE ? OR c.company_name LIKE ? OR p.description LIKE ?)";
    }

    // Add filters if provided
    if (!empty($filters)) {
        foreach ($filters as $column => $value) {
            if ($value !== '') {
                $sql .= " AND p.$column = ?";
            }
        }
    }

    $sql .= " GROUP BY p.project_id ORDER BY p.date_created DESC";

    error_log("getDelayedProjects SQL: " . $sql);

    $stmt = $conn->prepare($sql);

    if (!$stmt) {
        error_log("SQL Prepare Error: " . $conn->error);
        return [];
    }

    // Bind parameters
    if (!empty($search) || !empty($filters)) {
        $types = '';
        $params = [];

        if (!empty($search)) {
            $types .= 'sss';
            $params[] = $search;
            $params[] = $search;
            $params[] = $search;
        }

        if (!empty($filters)) {
            foreach ($filters as $column => $value) {
                if ($value !== '') {
                    $types .= 's';
                    $params[] = $value;
                }
            }
        }

        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }
    }

    $stmt->execute();
    $result = $stmt->get_result();

    $projects = [];
    while ($row = $result->fetch_assoc()) {
        // Get project images
        $imgSql = "SELECT * FROM tbl_project_images WHERE project_id = ?";
        $imgStmt = $conn->prepare($imgSql);
        $imgStmt->bind_param("i", $row['project_id']);
        $imgStmt->execute();
        $imgResult = $imgStmt->get_result();

        $images = [];
        while ($imgRow = $imgResult->fetch_assoc()) {
            $images[] = $imgRow;
        }

        $row['images'] = $images;
        $projects[] = $row;
    }

    return $projects;
}
/**
 * Get the count of images assigned to each assignee
 * @param int $project_id Project ID
 * @return array Array with assignment_id as key and count as value
 */
function getAssignmentImageCounts($project_id)
{
    global $conn;

    $sql = "SELECT assignment_id, COUNT(*) as image_count 
            FROM tbl_project_images 
            WHERE project_id = ? AND assignment_id IS NOT NULL
            GROUP BY assignment_id";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $project_id);
    $stmt->execute();
    $result = $stmt->get_result();

    $counts = [];
    while ($row = $result->fetch_assoc()) {
        $counts[$row['assignment_id']] = $row['image_count'];
    }

    return $counts;
}

/**
 * Update assignment image counts in the assignments table
 * @param int $project_id Project ID
 * @return bool Success status
 */
function updateAssignmentImageCounts($project_id)
{
    global $conn;

    $counts = getAssignmentImageCounts($project_id);

    foreach ($counts as $assignment_id => $count) {
        $sql = "UPDATE tbl_project_assignments 
                SET assigned_images = ? 
                WHERE assignment_id = ? AND project_id = ?";

        $stmt = $conn->prepare($sql);
        $stmt->bind_param("iii", $count, $assignment_id, $project_id);
        $stmt->execute();
    }

    return true;
}

/**
 * Get all project assignments with user details
 * @param int $project_id Project ID
 * @return array Array of assignments with user details
 */
function getDetailedProjectAssignments($project_id)
{
    global $conn;

    $sql = "SELECT pa.*, 
            u.first_name, u.last_name, 
            CONCAT(u.first_name, ' ', u.last_name) AS full_name,
            a.role,
            COUNT(pi.image_id) AS assigned_image_count
            FROM tbl_project_assignments pa
            JOIN tbl_users u ON pa.user_id = u.user_id
            JOIN tbl_accounts a ON u.user_id = a.user_id
            LEFT JOIN tbl_project_images pi ON pi.assignment_id = pa.assignment_id
            WHERE pa.project_id = ?
            GROUP BY pa.assignment_id
            ORDER BY pa.assigned_date DESC";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $project_id);
    $stmt->execute();
    $result = $stmt->get_result();

    $assignments = [];
    while ($row = $result->fetch_assoc()) {
        // Calculate if deadline is overdue
        if (isset($row['deadline'])) {
            $deadline = new DateTime($row['deadline']);
            $today = new DateTime();
            $row['is_overdue'] = $deadline < $today;
        } else {
            $row['is_overdue'] = false;
        }

        $assignments[] = $row;
    }

    return $assignments;
}



// Run database structure check when file is loaded
$dbStructure = checkDatabaseStructure();
?>