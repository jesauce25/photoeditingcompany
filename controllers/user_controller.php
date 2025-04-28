<?php
// Include necessary files
require_once '../includes/db_connection.php';

// Set content type to JSON
header('Content-Type: application/json');

// Get the action from the request
$action = isset($_GET['action']) ? $_GET['action'] : '';

try {
    // Process based on action
    switch ($action) {
        case 'get_users':
            // Get all users with their roles from tbl_accounts and tbl_users, without relying on tbl_roles
            $sql = "SELECT 
                    u.user_id, 
                    u.first_name, 
                    u.last_name, 
                    CONCAT(u.first_name, ' ', u.last_name) as full_name,
                    u.email_address, 
                    u.profile_img,
                    a.username, 
                    a.role, 
                    a.status 
                FROM 
                    tbl_users u
                JOIN 
                    tbl_accounts a ON u.user_id = a.user_id
                ORDER BY 
                    u.first_name, u.last_name";

            // Execute the query
            $result = $conn->query($sql);

            if (!$result) {
                throw new Exception("Database error: " . $conn->error);
            }

            // Fetch all users
            $users = [];
            while ($row = $result->fetch_assoc()) {
                // Set default profile image if none exists
                if (empty($row['profile_img'])) {
                    $row['profile_img'] = '../dist/img/user-default.jpg';
                } else if (file_exists("../uploads/profile_pictures/" . basename($row['profile_img']))) {
                    $row['profile_img'] = '../uploads/profile_pictures/' . basename($row['profile_img']);
                } else if (strpos($row['profile_img'], 'uploads/profile_pictures/') !== false) {
                    $row['profile_img'] = '../' . $row['profile_img'];
                } else {
                    // Fallback to default if file doesn't exist
                    $row['profile_img'] = '../dist/img/user-default.jpg';
                }

                $users[] = $row;
            }

            // Return the users as JSON
            echo json_encode([
                'success' => true,
                'users' => $users
            ]);
            break;

        default:
            throw new Exception("Invalid action");
    }
} catch (Exception $e) {
    // Return error message
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}