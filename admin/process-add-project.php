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

    // Validate required fields
    $errors = [];

    if (empty($project_title)) {
        $errors[] = "Project name is required";
    }

    if ($company_id <= 0) {
        $errors[] = "Please select a company";
    }

    if (empty($date_arrived)) {
        $errors[] = "Date arrived is required";
    }

    if (empty($deadline)) {
        $errors[] = "Deadline is required";
    }

    // Total images validation - make it optional since we're auto-calculating it
    // Only show error if the user explicitly sets a value and it's invalid
    if (isset($_POST['total_images']) && $_POST['total_images'] === "0" && isset($_FILES['projectImages']) && empty($_FILES['projectImages']['name'][0])) {
        // Add a warning instead of an error
        $_SESSION['warning_message'] = "No files were selected. You can add files later.";
    }

    // If there are validation errors
    if (!empty($errors)) {
        $_SESSION['error_messages'] = $errors;

        // Store form data for repopulation, excluding file inputs
        $form_data = $_POST;
        $_SESSION['form_data'] = $form_data;

        header("Location: add-project.php");
        exit();
    }

    // Handle file names from the hidden input
    $file_data = [];
    $file_names_json = $_POST['fileNames'] ?? '';

    if (!empty($file_names_json)) {
        try {
            $file_data = json_decode($file_names_json, true);

            // If file data is not an array, initialize it as empty array
            if (!is_array($file_data)) {
                $file_data = [];
            }
        } catch (Exception $e) {
            // If JSON decode fails, just use empty array
            $file_data = [];
        }
    }

    // Add project with only file names (no actual file uploads)
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
        $file_data,
        true // Store only filenames
    );

    // Check result
    if ($result['status'] === 'success') {
        $_SESSION['success_message'] = $result['message'];

        // Check if save and add another
        if (isset($_POST['saveAndAddAnother'])) {
            header("Location: add-project.php");
            exit();
        } else {
            header("Location: project-list.php");
            exit();
        }
    } else {
        $_SESSION['error_messages'] = [$result['message']];
        $_SESSION['form_data'] = $_POST; // Store form data for repopulation
        header("Location: add-project.php");
        exit();
    }
} else {
    // If not POST request, redirect to add project page
    header("Location: add-project.php");
    exit();
}
?>