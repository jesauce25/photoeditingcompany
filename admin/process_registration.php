<?php
/**
 * Process Registration
 * Handles user registration form submission
 */

// Include role check - restrict to Admin only
require_once('includes/check_admin_role.php');

// Enable error reporting for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Include directory check script
require_once(dirname(__FILE__) . '/ensure_directories.php');

// Include user controller
require_once(dirname(__FILE__) . '/controllers/user_controller.php');

// Function to log errors to a file
function logError($message, $data = [])
{
    $log_file = '../logs/registration_errors.log';
    $log_dir = dirname($log_file);

    // Create logs directory if it doesn't exist
    if (!file_exists($log_dir)) {
        mkdir($log_dir, 0777, true);
    }

    $timestamp = date('Y-m-d H:i:s');
    $log_message = "[{$timestamp}] {$message}" . PHP_EOL;

    // Add data if provided
    if (!empty($data)) {
        $log_message .= "Data: " . json_encode($data) . PHP_EOL;
    }

    // Add separator for readability
    $log_message .= "------------------------------------" . PHP_EOL;

    // Append to log file
    file_put_contents($log_file, $log_message, FILE_APPEND);
}

// Check if form is submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Log raw data
        error_log('POST data: ' . json_encode($_POST));
        error_log('FILES data: ' . json_encode($_FILES));

        // Check upload directory
        $upload_dir = '../profiles/';
        error_log('Checking upload directory: ' . $upload_dir);

        if (!file_exists($upload_dir)) {
            error_log('Upload directory does not exist, attempting to create it');
            if (!mkdir($upload_dir, 0777, true)) {
                error_log('Failed to create upload directory');
                throw new Exception('Failed to create upload directory');
            } else {
                error_log('Upload directory created successfully');
            }
        } else {
            error_log('Upload directory exists');
            error_log('Upload directory writable: ' . (is_writable($upload_dir) ? 'Yes' : 'No'));

            if (!is_writable($upload_dir)) {
                // Try to make it writable
                chmod($upload_dir, 0777);
                error_log('Attempted to make directory writable: ' . (is_writable($upload_dir) ? 'Success' : 'Failed'));

                if (!is_writable($upload_dir)) {
                    throw new Exception('Upload directory is not writable');
                }
            }
        }

        // Validate required fields
        $required_fields = ['firstName', 'lastName', 'username', 'password', 'confirmPassword', 'role', 'emailAddress', 'birthDate', 'address', 'contactNum'];
        $missing_fields = [];

        foreach ($required_fields as $field) {
            if (empty($_POST[$field])) {
                $missing_fields[] = $field;
            }
        }

        if (!empty($missing_fields)) {
            logError('Missing required fields', ['missing_fields' => $missing_fields]);
            throw new Exception('Please fill in all required fields: ' . implode(', ', $missing_fields));
        }

        // Check if passwords match
        if ($_POST['password'] !== $_POST['confirmPassword']) {
            logError('Passwords do not match');
            throw new Exception('Passwords do not match');
        }

        // Validate email format
        if (!filter_var($_POST['emailAddress'], FILTER_VALIDATE_EMAIL)) {
            logError('Invalid email format', ['email' => $_POST['emailAddress']]);
            throw new Exception('Please enter a valid email address');
        }

        // Prepare user data from form
        $userData = [
            'firstName' => trim($_POST['firstName']),
            'midName' => trim($_POST['midName'] ?? ''),
            'lastName' => trim($_POST['lastName']),
            'birthDate' => $_POST['birthDate'],
            'address' => trim($_POST['address']),
            'contactNum' => trim($_POST['contactNum']),
            'emailAddress' => trim($_POST['emailAddress']),
            'username' => trim($_POST['username']),
            'password' => $_POST['password'],
            'role' => $_POST['role']
        ];

        // Log registration attempt
        logError('Registration attempt started', [
            'username' => $userData['username'],
            'email' => $userData['emailAddress']
        ]);

        // Add profile image if available
        if (isset($_FILES['profileImg']) && $_FILES['profileImg']['error'] !== UPLOAD_ERR_NO_FILE) {
            // Log file information
            logError('Profile image processing', [
                'filename' => $_FILES['profileImg']['name'],
                'size' => $_FILES['profileImg']['size'],
                'type' => $_FILES['profileImg']['type'],
                'error_code' => $_FILES['profileImg']['error']
            ]);

            $userData['profileImg'] = $_FILES['profileImg'];
        }

        // Register the user
        $result = registerUser($userData);

        // Log the result
        logError($result['success'] ? 'Registration successful' : 'Registration failed', $result);

        // Handle the response - for regular form submit instead of AJAX
        if ($result['success']) {
            // Success - store success message in session and redirect
            session_start();
            $_SESSION['registration_success'] = true;
            $_SESSION['registration_message'] = $result['message'];
            header('Location: user-list.php');
            exit;
        } else {
            // Error - store error in session and redirect back to form
            session_start();
            $_SESSION['registration_error'] = true;
            $_SESSION['registration_message'] = $result['message'];
            header('Location: add-user.php');
            exit;
        }
    } catch (Exception $e) {
        // Log the exception
        logError('Exception occurred', [
            'message' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]);

        // Store error in session and redirect back to form
        session_start();
        $_SESSION['registration_error'] = true;
        $_SESSION['registration_message'] = 'An error occurred during registration: ' . $e->getMessage();
        header('Location: add-user.php');
        exit;
    }
} else {
    // Not a POST request - redirect to the form
    header('Location: add-user.php');
    exit;
}
