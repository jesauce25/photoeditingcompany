<?php
// Set headers to handle errors
header('Content-Type: application/json');
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include database connection and config
include_once '../includes/db_connection.php';
include_once '../includes/config.php';

// Log function for debugging
function logError($message)
{
    error_log(date('[Y-m-d H:i:s] ') . $message . PHP_EOL, 3, '../logs/upload_errors.log');
}

// Check if database connection is available
if (!isset($conn)) {
    logError("Database connection not available");
    echo json_encode(['success' => false, 'message' => 'Database connection error']);
    exit;
}

// Process only POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

// Check for required parameters
if (!isset($_POST['project_id']) || !isset($_POST['action'])) {
    echo json_encode(['success' => false, 'message' => 'Missing required parameters']);
    exit;
}

$project_id = intval($_POST['project_id']);
$action = $_POST['action'];

// Verify project exists
$stmt = $conn->prepare("SELECT id FROM projects WHERE id = ?");
$stmt->bind_param("i", $project_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode(['success' => false, 'message' => 'Project not found']);
    exit;
}

// Process file uploads
if ($action === 'upload') {
    // Check if files were actually sent
    if (!isset($_FILES['project_images']) || empty($_FILES['project_images']['name'][0])) {
        echo json_encode(['success' => false, 'message' => 'No files were uploaded']);
        exit;
    }

    $file_count = count($_FILES['project_images']['name']);
    $uploaded_files = [];
    $warnings = [];

    // Start transaction
    $conn->begin_transaction();

    try {
        for ($i = 0; $i < $file_count; $i++) {
            // Get file details
            $file_name = $_FILES['project_images']['name'][$i];
            $file_tmp = $_FILES['project_images']['tmp_name'][$i];
            $file_size = $_FILES['project_images']['size'][$i];
            $file_error = $_FILES['project_images']['error'][$i];
            $file_type = $_FILES['project_images']['type'][$i];

            // Skip empty file slots
            if (empty($file_name))
                continue;

            // Log upload attempt
            logError("Processing file: {$file_name}, Size: " . formatFileSize($file_size) . ", Type: {$file_type}");

            // Check for upload errors
            if ($file_error !== UPLOAD_ERR_OK) {
                $error_message = match ($file_error) {
                    UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE => "File {$file_name} exceeds size limit",
                    UPLOAD_ERR_PARTIAL => "File {$file_name} was only partially uploaded",
                    UPLOAD_ERR_NO_FILE => "No file was uploaded for slot {$i}",
                    UPLOAD_ERR_NO_TMP_DIR => "Missing temporary folder for {$file_name}",
                    UPLOAD_ERR_CANT_WRITE => "Failed to write {$file_name} to disk",
                    UPLOAD_ERR_EXTENSION => "A PHP extension stopped the upload of {$file_name}",
                    default => "Unknown upload error for {$file_name}"
                };
                $warnings[] = $error_message;
                logError($error_message);
                continue;
            }

            // Validate file size
            if ($file_size > MAX_UPLOAD_SIZE) {
                $warnings[] = "File {$file_name} exceeds size limit of " . formatFileSize(MAX_UPLOAD_SIZE);
                continue;
            }

            // Validate file type
            if (!in_array($file_type, ALLOWED_IMAGE_TYPES)) {
                $warnings[] = "File type {$file_type} not allowed for {$file_name}";
                continue;
            }

            // Generate unique filename
            $file_ext = pathinfo($file_name, PATHINFO_EXTENSION);
            $new_filename = uniqid('project_' . $project_id . '_') . '.' . $file_ext;
            $upload_path = '../uploads/projects/' . $new_filename;

            // Create directory if not exists
            if (!file_exists('../uploads/projects/')) {
                mkdir('../uploads/projects/', 0755, true);
            }

            // Move uploaded file
            if (move_uploaded_file($file_tmp, $upload_path)) {
                // Insert into database
                $stmt = $conn->prepare("INSERT INTO project_images (project_id, image_path, original_filename, file_type, file_size, upload_date) VALUES (?, ?, ?, ?, ?, NOW())");
                $stmt->bind_param("isssi", $project_id, $new_filename, $file_name, $file_type, $file_size);

                if ($stmt->execute()) {
                    $image_id = $conn->insert_id;
                    $uploaded_files[] = [
                        'id' => $image_id,
                        'name' => $file_name,
                        'path' => $new_filename,
                        'size' => formatFileSize($file_size),
                        'type' => $file_type
                    ];
                } else {
                    $warnings[] = "Failed to save {$file_name} to database: " . $stmt->error;
                    unlink($upload_path); // Remove the file if database insertion fails
                }
            } else {
                $warnings[] = "Failed to move uploaded file {$file_name}";
            }
        }

        // Commit the transaction if we have at least one successful upload
        if (!empty($uploaded_files)) {
            $conn->commit();
            echo json_encode([
                'success' => true,
                'message' => count($uploaded_files) . ' files uploaded successfully',
                'files' => $uploaded_files,
                'warnings' => $warnings
            ]);
        } else {
            $conn->rollback();
            echo json_encode([
                'success' => false,
                'message' => 'No files were successfully uploaded',
                'warnings' => $warnings
            ]);
        }
    } catch (Exception $e) {
        $conn->rollback();
        logError("Exception: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Error processing uploads: ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid action']);
}
?>