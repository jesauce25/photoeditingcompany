<?php
/**
 * User Controller
 * Handles user-related functionality including user listing and registration
 */

// Start output buffering
ob_start();

// For debugging - uncomment if needed
// error_log("Current directory: " . __DIR__);
// error_log("Include path: " . get_include_path());

// Include database connection (fix path)
if (file_exists('../../includes/db_connection.php')) {
    require_once '../../includes/db_connection.php';
} elseif (file_exists('../includes/db_connection.php')) {
    require_once '../includes/db_connection.php';
} else {
    // If neither path works, respond with an error
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Database connection file not found']);
    exit;
}

// Check if session has started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Get all users with account information
 * 
 * @return array Array of user records
 */
function getAllUsers()
{
    global $conn;
    $users = array();

    try {
        // Modified query to use tbl_accounts instead of tbl_roles
        $sql = "SELECT u.*, a.username, a.role as role_name, a.status
                FROM tbl_users u 
                JOIN tbl_accounts a ON u.user_id = a.user_id 
                ORDER BY u.user_id DESC";

        $result = $conn->query($sql);

        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $users[] = $row;
            }
            $result->free();
        } else {
            error_log("Error in getAllUsers: " . $conn->error);
            throw new Exception("Database error occurred");
        }

        return $users;
    } catch (Exception $e) {
        error_log("Exception in getAllUsers: " . $e->getMessage());
        throw $e;
    }
}

/**
 * Get user details by ID
 * 
 * @param int $user_id User ID
 * @return array|null User record or null if not found
 */
function getUserById($user_id)
{
    global $conn;

    $user_id = (int) $user_id;

    $query = "SELECT u.*, a.username, a.role, a.status 
              FROM tbl_users u 
              JOIN tbl_accounts a ON u.user_id = a.user_id 
              WHERE u.user_id = ?";

    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();

    $result = $stmt->get_result();

    if ($result && $result->num_rows > 0) {
        $user = $result->fetch_assoc();
        // Format birth date for HTML input
        if (!empty($user['birth_date'])) {
            $user['birth_date'] = date('Y-m-d', strtotime($user['birth_date']));
        }
        return $user;
    }

    return null;
}

/**
 * Update user details
 * 
 * @param int $user_id User ID
 * @param array $userData User data to update
 * @return array Response with success status and message
 */
function updateUser($user_id, $userData)
{
    global $conn;

    try {
        // Get the current user data
        $currentUser = getUserById($user_id);
        if (!$currentUser) {
            throw new Exception("User not found");
        }

        // No special role handling - keep whatever role is submitted
        // If it's admin, it stays admin. If it's project manager, it stays project manager, etc.

        // Start transaction
        $conn->begin_transaction();

        // Format birth date for database
        $birthDate = !empty($userData['birthDate']) ? date('Y-m-d', strtotime($userData['birthDate'])) : null;

        // Update user details in tbl_users
        $stmt = $conn->prepare("UPDATE tbl_users SET 
            first_name = ?, 
            mid_name = ?, 
            last_name = ?, 
            birth_date = ?, 
            address = ?, 
            contact_num = ?, 
            email_address = ?, 
            date_updated = NOW() 
            WHERE user_id = ?");

        $stmt->bind_param(
            "sssssssi",
            $userData['firstName'],
            $userData['midName'],
            $userData['lastName'],
            $birthDate,
            $userData['address'],
            $userData['contactNum'],
            $userData['emailAddress'],
            $user_id
        );

        if (!$stmt->execute()) {
            throw new Exception("Failed to update user details");
        }

        // Update account details in tbl_accounts
        $stmt = $conn->prepare("UPDATE tbl_accounts SET 
            username = ?, 
            role = ?, 
            status = ? 
            WHERE user_id = ?");

        $stmt->bind_param(
            "sssi",
            $userData['username'],
            $userData['role'],
            $userData['status'],
            $user_id
        );

        if (!$stmt->execute()) {
            throw new Exception("Failed to update account details");
        }

        // Commit transaction
        $conn->commit();

        return array('success' => true, 'message' => 'User updated successfully');

    } catch (Exception $e) {
        // Rollback transaction on error
        $conn->rollback();
        return array('success' => false, 'message' => $e->getMessage());
    }
}

/**
 * Delete a user
 * 
 * @param int $user_id User ID to delete
 * @return array Response with success status and message
 */
function deleteUser($user_id)
{
    global $conn;

    try {
        // Start transaction
        $conn->begin_transaction();

        // Delete user from tbl_accounts first (due to foreign key constraint)
        $stmt = $conn->prepare("DELETE FROM tbl_accounts WHERE user_id = ?");
        $stmt->bind_param("i", $user_id);

        if (!$stmt->execute()) {
            throw new Exception("Failed to delete user account");
        }

        // Delete user from tbl_users
        $stmt = $conn->prepare("DELETE FROM tbl_users WHERE user_id = ?");
        $stmt->bind_param("i", $user_id);

        if (!$stmt->execute()) {
            throw new Exception("Failed to delete user record");
        }

        // Commit transaction
        $conn->commit();

        return array('success' => true, 'message' => 'User deleted successfully');

    } catch (Exception $e) {
        // Rollback transaction on error
        $conn->rollback();
        return array('success' => false, 'message' => $e->getMessage());
    }
}

/**
 * Update user status
 * 
 * @param int $user_id User ID
 * @param string $status New status (Active/Blocked)
 * @return array Response with success status and message
 */
function updateUserStatus($user_id, $status)
{
    global $conn;

    try {
        // Validate status
        if (!in_array($status, ['Active', 'Blocked'])) {
            throw new Exception("Invalid status value");
        }

        // Update status in tbl_accounts
        $stmt = $conn->prepare("UPDATE tbl_accounts SET status = ? WHERE user_id = ?");
        $stmt->bind_param("si", $status, $user_id);

        if (!$stmt->execute()) {
            throw new Exception("Failed to update user status");
        }

        return array('success' => true, 'message' => 'User status updated successfully');

    } catch (Exception $e) {
        return array('success' => false, 'message' => $e->getMessage());
    }
}

/**
 * Format users list for display
 * 
 * @param array $users Array of user records
 * @return array Formatted users for display
 */
function formatUsersForDisplay($users)
{
    $formatted_users = [];
    foreach ($users as $user) {
        // Format full name
        $full_name = $user['first_name'] . ' ' . ($user['mid_name'] ? $user['mid_name'] . ' ' : '') . $user['last_name'];

        // Format profile image path
        $profile_img_path = '';
        if (!empty($user['profile_img'])) {
            // Check if file exists
            $img_path = dirname(dirname(__FILE__)) . '/assets/img/profile/' . $user['profile_img'];
            if (file_exists($img_path)) {
                $profile_img_path = 'assets/img/profile/' . $user['profile_img'];
            } else {
                $profile_img_path = 'dist/img/user-default.jpg';
            }
        } else {
            $profile_img_path = 'dist/img/user-default.jpg';
        }

        $formatted_users[] = [
            'user_id' => $user['user_id'],
            'full_name' => $full_name,
            'username' => $user['username'],
            'email_address' => $user['email_address'],
            'role' => $user['role_name'],
            'status' => $user['status'],
            'profile_img' => $profile_img_path,
            'birth_date' => $user['birth_date'],
            'address' => $user['address'],
            'contact_num' => $user['contact_num'],
            'date_added' => $user['date_added']
        ];
    }

    return $formatted_users;
}

/**
 * Check if username already exists
 * 
 * @param string $username Username to check
 * @return bool True if username exists, false otherwise
 */
function usernameExists($username)
{
    global $conn;

    $query = "SELECT username FROM tbl_accounts WHERE username = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    return $result->num_rows > 0;
}

/**
 * Check if email already exists
 * 
 * @param string $email Email to check
 * @return bool True if email exists, false otherwise
 */
function emailExists($email)
{
    global $conn;

    $query = "SELECT email_address FROM tbl_users WHERE email_address = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    return $result->num_rows > 0;
}

/**
 * Register a new user
 * 
 * @param array $userData User data for registration
 * @return array Response with success status and message
 */
function registerUser($userData)
{
    global $conn;

    try {
        // Check if username already exists
        if (usernameExists($userData['username'])) {
            return array('success' => false, 'message' => 'Username already exists');
        }

        // Check if email already exists
        if (emailExists($userData['emailAddress'])) {
            return array('success' => false, 'message' => 'Email address already exists');
        }

        // Validate role - ensure it's one of the allowed roles
        if (!in_array($userData['role'], ['Admin', 'Project Manager', 'Graphic Artist'])) {
            $userData['role'] = 'Graphic Artist'; // Default role if invalid
        }

        // Start transaction
        $conn->begin_transaction();

        // Format birth date
        $birthDate = date('Y-m-d', strtotime($userData['birthDate']));

        // Handle profile image upload if available
        $profile_img = null;
        if (isset($userData['profileImg']) && $userData['profileImg']['error'] === UPLOAD_ERR_OK) {
            $file = $userData['profileImg'];
            $filename = $file['name'];
            $tmp_name = $file['tmp_name'];
            $file_ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

            // Check file extension
            $allowed_ext = array('jpg', 'jpeg', 'png', 'gif');
            if (!in_array($file_ext, $allowed_ext)) {
                throw new Exception('Invalid file type. Only JPG, JPEG, PNG and GIF files are allowed.');
            }

            // Generate unique filename
            $new_filename = uniqid('profile_') . '.' . $file_ext;
            $upload_path = dirname(dirname(__FILE__)) . '/assets/img/profile/' . $new_filename;

            // Move uploaded file
            if (move_uploaded_file($tmp_name, $upload_path)) {
                $profile_img = $new_filename;
            } else {
                throw new Exception('Failed to upload profile image');
            }
        }

        // Insert into tbl_users
        $stmt = $conn->prepare("INSERT INTO tbl_users (first_name, mid_name, last_name, birth_date, address, contact_num, email_address, profile_img, date_added) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())");

        $stmt->bind_param(
            "ssssssss",
            $userData['firstName'],
            $userData['midName'],
            $userData['lastName'],
            $birthDate,
            $userData['address'],
            $userData['contactNum'],
            $userData['emailAddress'],
            $profile_img
        );

        if (!$stmt->execute()) {
            throw new Exception("Failed to insert user details: " . $stmt->error);
        }

        // Get the inserted user_id
        $user_id = $conn->insert_id;

        // Hash the password
        $hashed_password = password_hash($userData['password'], PASSWORD_DEFAULT);

        // Insert into tbl_accounts
        $stmt = $conn->prepare("INSERT INTO tbl_accounts (user_id, username, password, role, status, date_added) VALUES (?, ?, ?, ?, 'Active', NOW())");

        $stmt->bind_param(
            "isss",
            $user_id,
            $userData['username'],
            $hashed_password,
            $userData['role']
        );

        if (!$stmt->execute()) {
            throw new Exception("Failed to insert account details: " . $stmt->error);
        }

        // Commit transaction
        $conn->commit();

        return array('success' => true, 'message' => 'User registered successfully');

    } catch (Exception $e) {
        // Rollback transaction on error
        if (isset($conn) && $conn->ping()) {
            $conn->rollback();
        }
        return array('success' => false, 'message' => $e->getMessage());
    }
}

// Only run the API code if this file is being accessed directly, not when included
if (basename($_SERVER['SCRIPT_NAME']) === basename(__FILE__)) {
    // Set the content type to JSON 
    header('Content-Type: application/json');

    // POST requests for user actions - handle them first to prioritize form submission
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Clean the buffer for the POST request
        ob_clean();

        $response = array('success' => false, 'message' => 'Invalid request');

        // Handle different actions based on request parameters

        // Delete user
        if (isset($_POST['action']) && $_POST['action'] === 'delete' && isset($_POST['user_id'])) {
            $user_id = (int) $_POST['user_id'];
            $response = deleteUser($user_id);
        }
        // Update user status
        else if (isset($_POST['action']) && $_POST['action'] === 'update_status' && isset($_POST['user_id']) && isset($_POST['new_status'])) {
            $user_id = (int) $_POST['user_id'];
            $status = $_POST['new_status'];
            $response = updateUserStatus($user_id, $status);
        }
        // Update user from edit form
        else if (isset($_POST['action']) && $_POST['action'] === 'update') {
            // Get user ID from URL parameter
            $user_id = isset($_GET['id']) ? (int) $_GET['id'] : 0;

            if ($user_id > 0) {
                $userData = array(
                    'firstName' => $_POST['firstName'],
                    'midName' => $_POST['midName'],
                    'lastName' => $_POST['lastName'],
                    'birthDate' => $_POST['birthDate'],
                    'address' => $_POST['address'],
                    'contactNum' => $_POST['contactNum'],
                    'emailAddress' => $_POST['emailAddress'],
                    'username' => $_POST['username'],
                    'role' => $_POST['role'],
                    'status' => $_POST['status']
                );
                $response = updateUser($user_id, $userData);
            } else {
                $response = array('success' => false, 'message' => 'Invalid user ID');
            }
        }
        // Handle status update from user-list.php
        else if (isset($_POST['status']) && isset($_POST['user_id'])) {
            $user_id = (int) $_POST['user_id'];
            $status = $_POST['status'];
            $response = updateUserStatus($user_id, $status);
        }
        // Handle delete user from user-list.php
        else if (isset($_POST['user_id']) && !isset($_POST['action'])) {
            $user_id = (int) $_POST['user_id'];
            $response = deleteUser($user_id);
        }

        echo json_encode($response);
        ob_end_flush();
        exit();
    }
    // GET requests for users
    else if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        try {
            // Clean the buffer for a fresh start
            ob_clean();

            // Set proper headers
            header('Content-Type: application/json');
            header('Cache-Control: no-cache, must-revalidate');

            // Handle request for a specific user by ID
            if (isset($_GET['user_id'])) {
                $user_id = (int) $_GET['user_id'];
                $user = getUserById($user_id);

                if ($user) {
                    // Add full name for convenience
                    $user['full_name'] = $user['first_name'] . ' ' . $user['last_name'];

                    $response = [
                        'success' => true,
                        'user' => $user
                    ];
                } else {
                    $response = [
                        'success' => false,
                        'message' => 'User not found'
                    ];
                }

                echo json_encode($response);
                ob_end_flush();
                exit();
            }

            // Get and format all users if no specific user was requested
            $users = getAllUsers();
            $formatted_users = formatUsersForDisplay($users);

            error_log("Sending response with " . count($formatted_users) . " formatted users");

            $response = [
                'success' => true,
                'users' => $formatted_users
            ];

            echo json_encode($response);

        } catch (Exception $e) {
            error_log("Error in GET request handler: " . $e->getMessage());

            $response = [
                'success' => false,
                'message' => 'Error loading users: ' . $e->getMessage()
            ];

            echo json_encode($response);
        }

        // End processing for GET requests
        ob_end_flush();
        exit();
    }

    // Flush the output buffer for any other request type
    ob_end_flush();
} else {
    // Just define the functions when included, don't output anything
    // This way the file can be included without generating output
}