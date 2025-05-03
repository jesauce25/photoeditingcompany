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

// Include task blocking code
if (file_exists('../../artist/includes/task_block_check.php')) {
    require_once '../../artist/includes/task_block_check.php';
} elseif (file_exists('../artist/includes/task_block_check.php')) {
    require_once '../artist/includes/task_block_check.php';
}

// Function to check if a transaction is in progress (compatibility function)
function isInTransaction($conn)
{
    // Check if the inTransaction method exists (PHP 5.5.0+)
    if (method_exists($conn, 'inTransaction')) {
        return $conn->inTransaction();
    }

    // Fallback for older MySQL versions - attempt a dummy query
    // If we're in transaction and there was an error, this won't commit automatically
    $initialAutocommit = $conn->autocommit(false);
    $inTransaction = !$initialAutocommit;
    $conn->autocommit($initialAutocommit);

    return $inTransaction;
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
        $sql = "SELECT u.*, a.username, a.role as role_name, a.status, a.has_overdue_tasks
                    FROM tbl_users u
                    JOIN tbl_accounts a ON u.user_id = a.user_id
                    ORDER BY u.user_id DESC";

        $result = $conn->query($sql);

        if ($result) {
            while ($row = $result->fetch_assoc()) {
                // For graphic artists, enforce block state if they have overdue tasks
                if ($row['role_name'] === 'Graphic Artist') {
                    error_log("ADMIN LIST: Checking artist ID " . $row['user_id'] . " - Current status: '{$row['status']}', has_overdue_tasks: {$row['has_overdue_tasks']}");

                    if (function_exists('forceBlockUserByOverdue')) {
                        // Use the new direct approach
                        $blockResult = forceBlockUserByOverdue($row['user_id']);
                        error_log("ADMIN LIST: Block check for artist ID " . $row['user_id'] .
                            ": Has overdue: " . ($blockResult['has_overdue'] ? 'YES' : 'NO') .
                            ", Status updated: " . ($blockResult['status_updated'] ? 'YES' : 'NO'));

                        // If the artist has overdue tasks but wasn't blocked, force it directly
                        if ($blockResult['has_overdue'] && !$blockResult['status_updated']) {
                            error_log("ADMIN LIST: Artist has overdue but status not updated - checking for admin protection");

                            // Check if user has admin protection before forcing block
                            $protectQuery = "SELECT last_unblocked_at FROM tbl_accounts WHERE user_id = ? AND last_unblocked_at > NOW()";
                            $protectStmt = $conn->prepare($protectQuery);
                            $protectStmt->bind_param("i", $row['user_id']);
                            $protectStmt->execute();
                            $hasProtection = $protectStmt->get_result()->num_rows > 0;

                            if (!$hasProtection) {
                                error_log("ADMIN LIST: No admin protection found - forcing block update");
                                // Direct force query to ensure blocking happens only if not protected
                                $forceQuery = "UPDATE tbl_accounts SET status = 'Blocked', has_overdue_tasks = 1 WHERE user_id = ?";
                                $forceStmt = $conn->prepare($forceQuery);
                                $forceStmt->bind_param("i", $row['user_id']);
                                $forceResult = $forceStmt->execute();

                                error_log("ADMIN LIST: Force update for artist ID " . $row['user_id'] . ": " .
                                    ($forceResult ? 'SUCCESS' : 'FAILED'));
                            } else {
                                error_log("ADMIN LIST: Artist has admin protection - NOT forcing block");
                            }
                        }

                        // Get current status from DB to update the row data
                        $statusQuery = "SELECT status, has_overdue_tasks FROM tbl_accounts WHERE user_id = ?";
                        $statusStmt = $conn->prepare($statusQuery);
                        $statusStmt->bind_param("i", $row['user_id']);
                        $statusStmt->execute();
                        $userStatus = $statusStmt->get_result()->fetch_assoc();

                        if ($userStatus) {
                            $row['status'] = $userStatus['status'];
                            $row['has_overdue_tasks'] = $userStatus['has_overdue_tasks'];
                            error_log("ADMIN LIST: Updated artist data - Status: '{$row['status']}', has_overdue_tasks: {$row['has_overdue_tasks']}");
                        }
                    } else {
                        error_log("ADMIN LIST: Function forceBlockUserByOverdue not found");
                    }
                }

                // Add the row to the users array (with potentially updated status)
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

        // Handle profile image upload if available
        $profile_img_update = "";
        $profile_img_param_types = "sssssssi"; // Default param types without profile image
        $bind_params = [
            $userData['firstName'],
            $userData['midName'],
            $userData['lastName'],
            $birthDate,
            $userData['address'],
            $userData['contactNum'],
            $userData['emailAddress'],
            $user_id
        ];

        // If profile image is being updated
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
            $upload_dir = '../uploads/profile_pictures/';

            // Create directory if it doesn't exist
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0755, true);
            }

            $upload_path = $upload_dir . $new_filename;
            $profile_img = 'uploads/profile_pictures/' . $new_filename;

            // Move uploaded file
            if (move_uploaded_file($tmp_name, $upload_path)) {
                // Add profile_img to the SQL update
                $profile_img_update = ", profile_img = ?";
                $profile_img_param_types = "ssssssssi"; // Add 's' for the profile_img parameter
                array_splice($bind_params, 7, 0, [$profile_img]); // Insert profile_img before user_id

                // Delete old profile image if it exists
                if (!empty($currentUser['profile_img'])) {
                    $old_path = '../' . $currentUser['profile_img'];
                    if (
                        file_exists($old_path) &&
                        strpos($old_path, '/uploads/profile_pictures/') !== false
                    ) {
                        @unlink($old_path);
                    }
                }
            } else {
                throw new Exception('Failed to upload profile image');
            }
        }

        // Update user details in tbl_users
        $stmt = $conn->prepare("UPDATE tbl_users SET 
            first_name = ?, 
            mid_name = ?, 
            last_name = ?, 
            birth_date = ?, 
            address = ?, 
            contact_num = ?, 
            email_address = ? 
            $profile_img_update,
            date_updated = NOW() 
            WHERE user_id = ?");

        $stmt->bind_param($profile_img_param_types, ...$bind_params);

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
 * Update user status (Active/Blocked)
 *
 * @param int $user_id User ID
 * @param string $status New status (Active/Blocked)
 * @return array Result array with success status and message
 */
function updateUserStatus($user_id, $status)
{
    global $conn;

    // Ensure user_id is treated as an integer to prevent SQL injection
    $user_id = intval($user_id);

    // Log initial state for debugging
    error_log("updateUserStatus START: User ID: $user_id, New status: $status");

    // Check if we're unblocking a user by querying current status
    $current_query = "SELECT status, has_overdue_tasks, is_protected, last_unblocked_at FROM tbl_accounts WHERE user_id = $user_id";
    $current_result = $conn->query($current_query);
    $current_data = $current_result->fetch_assoc();
    $current_status = $current_data ? $current_data['status'] : 'Unknown';
    $has_overdue_tasks = $current_data ? $current_data['has_overdue_tasks'] : 0;
    $is_protected = $current_data ? $current_data['is_protected'] : 0;
    $current_unblock_time = $current_data && !empty($current_data['last_unblocked_at']) ? $current_data['last_unblocked_at'] : null;

    $isUnblocking = ($current_status === 'Blocked' && $status === 'Active');
    error_log("User current status: $current_status, has_overdue_tasks: $has_overdue_tasks, is_protected: $is_protected, isUnblocking: " . ($isUnblocking ? 'YES' : 'NO'));

    if ($isUnblocking) {
        error_log("UNBLOCKING USER ID: $user_id");

        // Begin transaction to ensure all operations complete together
        $conn->begin_transaction();

        try {
            // Calculate protection end time - 1 minute from now (for testing)
            $now = new DateTime();
            $protection_end = $now->add(new DateInterval('PT1M'));  // Changed from PT24H to PT1M (1 minute)
            $protection_timestamp = $protection_end->format('Y-m-d H:i:s');

            error_log("PROTECTION INFO: Setting protection until $protection_timestamp (1 MINUTE from now - TEST MODE)");

            // STEP 0: Mark all existing overdue tasks as forgiven
            // This will be used in the task_block_check to ignore these specific tasks
            $today = date('Y-m-d');
            $forgive_query = "UPDATE tbl_project_assignments 
                             SET forgiven_at = NOW() 
                             WHERE user_id = ? 
                               AND status_assignee NOT IN ('completed', 'deleted')
                               AND deadline < ?";

            // Check if the forgiven_at column exists
            $check_column = "SHOW COLUMNS FROM tbl_project_assignments LIKE 'forgiven_at'";
            $column_exists = $conn->query($check_column)->num_rows > 0;

            if ($column_exists) {
                // Column exists, proceed with update
                $forgive_stmt = $conn->prepare($forgive_query);
                $forgive_stmt->bind_param("is", $user_id, $today);
                $forgive_result = $forgive_stmt->execute();
                $forgiven_count = $forgive_stmt->affected_rows;
                error_log("OVERDUE FORGIVENESS: Marked $forgiven_count existing overdue tasks as forgiven for user $user_id");
            } else {
                // Column doesn't exist, log a warning
                error_log("WARNING: forgiven_at column does not exist in tbl_project_assignments. Please run the SQL in admin/db_update_forgiven.sql");
            }

            // STEP 1: Update the account
            $account_sql = "UPDATE tbl_accounts 
                           SET status = 'Active', 
                               has_overdue_tasks = 0, 
                               is_protected = 1,
                               last_unblocked_at = ?
                           WHERE user_id = ?";

            $account_stmt = $conn->prepare($account_sql);
            $account_stmt->bind_param("si", $protection_timestamp, $user_id);
            $account_result = $account_stmt->execute();
            $affected_rows = $account_stmt->affected_rows;

            if (!$account_result || $affected_rows === 0) {
                throw new Exception("Failed to update account status: " . $conn->error);
            }
            error_log("ACCOUNT UPDATE RESULT: SUCCESS (Affected rows: $affected_rows)");

            // STEP 2: Unlock all tasks 
            $tasks_sql = "UPDATE tbl_project_assignments 
                         SET is_locked = 0 
                         WHERE user_id = ?";

            $tasks_stmt = $conn->prepare($tasks_sql);
            $tasks_stmt->bind_param("i", $user_id);
            $task_result = $tasks_stmt->execute();
            $tasks_affected = $tasks_stmt->affected_rows;

            if (!$task_result) {
                throw new Exception("Failed to unlock tasks: " . $conn->error);
            }
            error_log("TASK UNLOCK RESULT: SUCCESS (Affected: $tasks_affected)");

            // STEP 3: VERIFY that everything was updated correctly
            $check_sql = "SELECT status, has_overdue_tasks, last_unblocked_at, is_protected
                          FROM tbl_accounts 
                          WHERE user_id = ?";
            $check_stmt = $conn->prepare($check_sql);
            $check_stmt->bind_param("i", $user_id);
            $check_stmt->execute();
            $check_result = $check_stmt->get_result();
            $account_data = $check_result->fetch_assoc();

            $lock_check = "SELECT COUNT(*) as locked_count 
                          FROM tbl_project_assignments 
                          WHERE user_id = ? AND is_locked = 1";
            $lock_stmt = $conn->prepare($lock_check);
            $lock_stmt->bind_param("i", $user_id);
            $lock_stmt->execute();
            $lock_result = $lock_stmt->get_result();
            $lock_data = $lock_result->fetch_assoc();

            // Log the verification results
            error_log("VERIFICATION: Status = " . $account_data['status'] .
                ", has_overdue_tasks = " . $account_data['has_overdue_tasks'] .
                ", is_protected = " . $account_data['is_protected'] .
                ", locked_count = " . $lock_data['locked_count'] .
                ", protection until = " . $account_data['last_unblocked_at']);

            // Commit the transaction
            $conn->commit();

            // Check for overdue tasks (for informational purposes only)
            $today = date('Y-m-d');
            $overdue_check = "SELECT COUNT(*) as overdue_count
                             FROM tbl_project_assignments
                             WHERE user_id = ?
                               AND status_assignee NOT IN ('completed', 'deleted')
                               AND deadline < ?";
            $overdue_stmt = $conn->prepare($overdue_check);
            $overdue_stmt->bind_param("is", $user_id, $today);
            $overdue_stmt->execute();
            $overdue_result = $overdue_stmt->get_result()->fetch_assoc();
            $overdue_count = $overdue_result ? $overdue_result['overdue_count'] : 0;

            error_log("INFO: User ID $user_id had overdue tasks, but they have been marked as forgiven");

            return array(
                'success' => true,
                'message' => "User unblocked successfully. Tasks have been unlocked and existing overdue tasks forgiven. Protection period set for 1 minute (until $protection_timestamp)."
            );
        } catch (Exception $e) {
            // Roll back transaction on error
            if ($conn->inTransaction()) {
                $conn->rollback();
            }
            error_log("ERROR UNBLOCKING USER: " . $e->getMessage());
            return array('success' => false, 'message' => "Failed to unblock user: " . $e->getMessage());
        }
    } else {
        // ==============================================
        // Regular status update (not unblocking)
        // ==============================================
        error_log("REGULAR STATUS UPDATE: User ID: $user_id, New status: $status");

        // Use prepared statement for better security
        $sql = "UPDATE tbl_accounts SET status = ? WHERE user_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("si", $status, $user_id);
        $result = $stmt->execute();

        if (!$result) {
            error_log("STATUS UPDATE FAILED: " . $conn->error);
            return array('success' => false, 'message' => "Failed to update status: " . $conn->error);
        }

        return array(
            'success' => true,
            'message' => 'User status updated to ' . $status
        );
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

        // Format profile image path - completely new approach
        $profile_img_path = '../dist/img/user-default.jpg'; // Default fallback

        if (!empty($user['profile_img'])) {
            // Check if file exists in uploads directory
            $profile_path = '../../uploads/profile_pictures/' . basename($user['profile_img']);
            $direct_path = '../../' . $user['profile_img'];

            // Debug logging
            error_log("Checking profile image paths:");
            error_log("Original path: " . $user['profile_img']);
            error_log("Profile path: " . $profile_path);
            error_log("Direct path: " . $direct_path);

            // Try different path possibilities
            if (file_exists($profile_path)) {
                $profile_img_path = '../../uploads/profile_pictures/' . basename($user['profile_img']);
                error_log("Using profile path: " . $profile_img_path);
            } else if (file_exists($direct_path)) {
                $profile_img_path = '../../' . $user['profile_img'];
                error_log("Using direct path: " . $profile_img_path);
            } else if (file_exists('../../' . 'uploads/profile_pictures/' . $user['profile_img'])) {
                $profile_img_path = '../../uploads/profile_pictures/' . $user['profile_img'];
                error_log("Using full uploads path: " . $profile_img_path);
            } else {
                error_log("No valid path found, using default image");
            }
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
            $upload_dir = '../uploads/profile_pictures/';

            // Create directory if it doesn't exist
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0755, true);
            }

            $upload_path = $upload_dir . $new_filename;
            $db_path = 'uploads/profile_pictures/' . $new_filename;

            // Move uploaded file
            if (move_uploaded_file($tmp_name, $upload_path)) {
                $profile_img = $db_path;
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
        if (isInTransaction($conn)) {
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