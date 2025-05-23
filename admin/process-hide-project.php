<?php
require_once '../includes/config.php';
require_once '../includes/db_connection.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['project_id'])) {
    $project_id = intval($_POST['project_id']);
    $return_page = isset($_POST['return_page']) ? $_POST['return_page'] : 'project-list.php';

    // Toggle the hidden status
    $stmt = $conn->prepare("UPDATE tbl_projects SET hidden = NOT hidden WHERE project_id = ?");
    $stmt->bind_param("i", $project_id);

    if ($stmt->execute()) {
        // Get the new hidden status
        $check_stmt = $conn->prepare("SELECT hidden FROM tbl_projects WHERE project_id = ?");
        $check_stmt->bind_param("i", $project_id);
        $check_stmt->execute();
        $result = $check_stmt->get_result();
        $row = $result->fetch_assoc();

        $message = $row['hidden'] ? "Project hidden successfully" : "Project unhidden successfully";
        header("Location: " . $return_page . "?success=" . urlencode($message));
    } else {
        header("Location: " . $return_page . "?error=Failed to update project status");
    }

    $stmt->close();
    exit();
} else {
    header("Location: project-list.php?error=Invalid request");
    exit();
}
