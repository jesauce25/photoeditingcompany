<?php
/**
 * Process Delete Project
 * Handles the form submission for deleting a project
 */

session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

// Include the project controller
require_once 'controllers/unified_project_controller.php';

// Check if form is submitted with project_id
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['project_id'])) {
    $project_id = intval($_POST['project_id']);

    // Validate project ID
    if ($project_id <= 0) {
        $_SESSION['notification'] = [
            'type' => 'error',
            'title' => 'Error',
            'message' => 'Invalid project ID'
        ];
        header("Location: project-list.php");
        exit();
    }

    // Get project details first to check if it exists
    $project = getProjectById($project_id);

    if (!$project) {
        $_SESSION['notification'] = [
            'type' => 'error',
            'title' => 'Error',
            'message' => 'Project not found'
        ];
        header("Location: project-list.php");
        exit();
    }

    // Delete the project
    $result = deleteProject($project_id);

    if ($result['status'] === 'success') {
        $_SESSION['notification'] = [
            'type' => 'success',
            'title' => 'Success',
            'message' => $result['message']
        ];
    } else {
        $_SESSION['notification'] = [
            'type' => 'error',
            'title' => 'Error',
            'message' => $result['message']
        ];
    }

    header("Location: project-list.php");
    exit();
} else {
    // If not POST request with project_id, redirect to project list
    header("Location: project-list.php");
    exit();
}
?>