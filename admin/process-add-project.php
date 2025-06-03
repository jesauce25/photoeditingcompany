<?php

/**
 * Process Add Project
 * Handles the form submission for adding a new project
 */

session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

// Include the project controller
require_once 'controllers/unified_project_controller.php';

// Enable error logging for debugging
error_log("Process Add Project script started");
error_log("POST data received: " . json_encode($_POST));

// Check if form is submitted
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Get form data
    $company_id = intval($_POST['companyName']);
    $project_title = $_POST['projectName'];
    $description = $_POST['description'];
    $date_arrived = $_POST['dateArrived'];
    $deadline = $_POST['deadline'];
    $priority = $_POST['priority'];
    $status = $_POST['status'];
    $total_images = intval($_POST['total_images']);
    $created_by = $_SESSION['user_id'] ?? 1;

    // Debug log
    error_log("Form data received: company_id=$company_id, project_title=$project_title, total_images=$total_images");

    // Check if fileNames exists in POST
    if (isset($_POST['fileNames'])) {
        error_log("fileNames found in POST: " . $_POST['fileNames']);
    } else {
        error_log("fileNames NOT found in POST");
    }

    // Validate required fields
    $errors = [];

    if ($company_id <= 0) {
        $errors[] = "Please select a company";
    }

    if (empty($date_arrived)) {
        $errors[] = "Date arrived is required";
    }

    if (empty($deadline)) {
        $errors[] = "Deadline is required";
    }

    // If there are validation errors
    if (!empty($errors)) {
        $_SESSION['error_messages'] = $errors;

        // Store form data for repopulation, excluding file inputs
        $form_data = $_POST;
        $_SESSION['form_data'] = $form_data;

        // For AJAX requests, return JSON response
        if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest') {
            header('Content-Type: application/json');
            echo json_encode(['status' => 'error', 'errors' => $errors]);
            exit();
        }

        header("Location: add-project.php");
        exit();
    }

    // Process file metadata from the hidden input field
    $file_data = [];
    $file_names_json = $_POST['fileNames'] ?? '';

    if (!empty($file_names_json)) {
        try {
            error_log("Processing fileNames JSON: " . $file_names_json);
            $files_array = json_decode($file_names_json, true);

            if (is_array($files_array)) {
                error_log("Successfully decoded JSON, found " . count($files_array) . " files");

                foreach ($files_array as $file) {
                    if (isset($file['name']) && !empty($file['name'])) {
                        $file_data[] = [
                            'name' => $file['name'],
                            'displayName' => $file['displayName'] ?? $file['name'],
                            'type' => $file['type'] ?? 'application/octet-stream',
                            'size' => $file['size'] ?? 0,
                            'batch' => $file['batch'] ?? '1'
                        ];

                        error_log("Added file to file_data: " . $file['name'] . " (batch: " . ($file['batch'] ?? '1') . ")");
                    }
                }

                // Update total_images based on actual file count if not explicitly set
                if (empty($total_images) || $total_images <= 0) {
                    $total_images = count($file_data);
                    error_log("Updated total_images to: " . $total_images);
                }
            } else {
                error_log("JSON decode did not return an array: " . print_r($files_array, true));
            }
        } catch (Exception $e) {
            error_log("Error processing file data: " . $e->getMessage());
            $file_data = [];
        }
    } else {
        error_log("fileNames is empty or not set");
    }

    // Add project with file metadata
    error_log("Calling addProject with " . count($file_data) . " files");
    $result = addProject(
        $project_title,
        $company_id,
        $description,
        $date_arrived,
        $deadline,
        $priority,
        $status,
        $total_images,
        $created_by,
        $file_data
    );

    // Check result
    if ($result['status'] === 'success') {
        $_SESSION['success_message'] = $result['message'];
        error_log("Project added successfully with ID: " . $result['project_id']);
        error_log("Uploaded files: " . print_r($result['uploaded_files'], true));

        // For AJAX requests, return JSON response
        if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest') {
            header('Content-Type: application/json');
            echo json_encode([
                'status' => 'success',
                'message' => $result['message'],
                'redirect' => isset($_POST['saveAndAddAnother']) ? 'add-project.php' : 'project-list.php'
            ]);
            exit();
        }

        // Check if save and add another
        if (isset($_POST['saveAndAddAnother'])) {
            header("Location: add-project.php");
            exit();
        } else {
            header("Location: project-list.php");
            exit();
        }
    } else {
        error_log("Error adding project: " . $result['message']);
        $_SESSION['error_messages'] = [$result['message']];
        $_SESSION['form_data'] = $_POST; // Store form data for repopulation

        // For AJAX requests, return JSON response
        if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest') {
            header('Content-Type: application/json');
            echo json_encode(['status' => 'error', 'message' => $result['message']]);
            exit();
        }

        header("Location: add-project.php");
        exit();
    }
} else {
    // If not POST request, redirect to add project page
    header("Location: add-project.php");
    exit();
}
