<?php
/**
 * Delete Company
 * Handles deletion of company records
 */
session_start();
include("../includes/db_connection.php");

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    // If it's an AJAX request
    if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
        header('Content-Type: application/json');
        echo json_encode(['status' => 'error', 'message' => 'Unauthorized access']);
        exit();
    } else {
        $_SESSION['error_message'] = 'You must log in to perform this action.';
        header("Location: ../login.php");
        exit();
    }
}

// Check if company ID is provided
if (isset($_POST['company_id']) && !empty($_POST['company_id'])) {
    $companyId = intval($_POST['company_id']);

    // Start transaction
    $conn->begin_transaction();

    try {
        // Get company info before deletion
        $getCompanyQuery = "SELECT company_name, logo_path FROM tbl_companies WHERE company_id = ?";
        $stmtGet = $conn->prepare($getCompanyQuery);
        $stmtGet->bind_param("i", $companyId);
        $stmtGet->execute();
        $result = $stmtGet->get_result();

        if ($row = $result->fetch_assoc()) {
            $companyName = $row['company_name'];
            $logoPath = $row['logo_path'];

            // Delete the company
            $deleteQuery = "DELETE FROM tbl_companies WHERE company_id = ?";
            $stmtDelete = $conn->prepare($deleteQuery);
            $stmtDelete->bind_param("i", $companyId);

            if ($stmtDelete->execute()) {
                // Log the action
                $userId = $_SESSION['user_id'];
                $action = "Deleted company: $companyName (ID: $companyId)";
                $logQuery = "INSERT INTO tbl_activity_logs (user_id, action, log_timestamp) VALUES (?, ?, NOW())";

                // If the activity log table exists
                if ($conn->query("SHOW TABLES LIKE 'tbl_activity_logs'")->num_rows > 0) {
                    $logStmt = $conn->prepare($logQuery);
                    $logStmt->bind_param("is", $userId, $action);
                    $logStmt->execute();
                    $logStmt->close();
                }

                // Delete the logo file if it exists
                if (!empty($logoPath) && file_exists("../" . $logoPath)) {
                    unlink("../" . $logoPath);
                }

                $conn->commit();

                $successMessage = "Company \"$companyName\" has been successfully deleted";

                // Check if it's an AJAX request
                if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
                    // Return JSON response for AJAX
                    header('Content-Type: application/json');
                    echo json_encode([
                        'status' => 'success',
                        'message' => $successMessage
                    ]);
                    exit();
                } else {
                    // Set session message and redirect for regular form submission
                    $_SESSION['success_message'] = $successMessage;
                    header("Location: company-list.php");
                    exit();
                }
            } else {
                throw new Exception("Failed to delete company: " . $stmtDelete->error);
            }

            $stmtDelete->close();
        } else {
            throw new Exception("Company not found");
        }

        $stmtGet->close();

    } catch (Exception $e) {
        // Rollback the transaction in case of error
        $conn->rollback();
        $errorMessage = $e->getMessage();

        // Check if it's an AJAX request
        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
            // Return JSON error for AJAX
            header('Content-Type: application/json');
            echo json_encode([
                'status' => 'error',
                'message' => $errorMessage
            ]);
            exit();
        } else {
            // Set session error and redirect for regular form submission
            $_SESSION['error_message'] = $errorMessage;
            header("Location: company-list.php");
            exit();
        }
    }
} else {
    $errorMessage = 'Company ID is required';

    // Check if it's an AJAX request
    if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
        // Return JSON error for AJAX
        header('Content-Type: application/json');
        echo json_encode([
            'status' => 'error',
            'message' => $errorMessage
        ]);
        exit();
    } else {
        // Set session error and redirect for regular form submission
        $_SESSION['error_message'] = $errorMessage;
        header("Location: company-list.php");
        exit();
    }
}
?>