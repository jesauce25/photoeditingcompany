<?php
/**
 * Process Add Company Form
 * Handles the form submission from add-company.php
 */
session_start();
include("../includes/db_connection.php");

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

// Check if form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // Get form data
    $companyName = trim($_POST['companyName']);
    $dateSignedUp = $_POST['dateSignedUp'];
    $address = trim($_POST['address']);
    $country = $_POST['country'];
    $email = trim($_POST['email']);
    $incharge = trim($_POST['incharge']);

    // Validate required fields
    if (
        empty($companyName) || empty($dateSignedUp) || empty($address) ||
        empty($country) || empty($email) || empty($incharge)
    ) {
        $_SESSION['error_message'] = 'All required fields must be filled out.';
        header("Location: add-company.php");
        exit();
    }

    // Validate email
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $_SESSION['error_message'] = 'Please enter a valid email address.';
        header("Location: add-company.php");
        exit();
    }

    // Handle logo upload
    $logoPath = null;
    if (isset($_FILES['companyLogo']) && $_FILES['companyLogo']['error'] == 0) {
        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
        $maxSize = 2 * 1024 * 1024; // 2MB

        // Check file type and size
        if (!in_array($_FILES['companyLogo']['type'], $allowedTypes)) {
            $_SESSION['error_message'] = 'Only JPG, PNG, and GIF image files are allowed.';
            header("Location: add-company.php");
            exit();
        }

        if ($_FILES['companyLogo']['size'] > $maxSize) {
            $_SESSION['error_message'] = 'Logo file size should not exceed 2MB.';
            header("Location: add-company.php");
            exit();
        }

        // Generate unique filename
        $fileExtension = pathinfo($_FILES['companyLogo']['name'], PATHINFO_EXTENSION);
        $newFileName = 'company_' . time() . '_' . uniqid() . '.' . $fileExtension;
        $uploadPath = '../uploads/company_logos/' . $newFileName;

        // Create directory if it doesn't exist
        if (!is_dir('../uploads/company_logos/')) {
            mkdir('../uploads/company_logos/', 0777, true);
        }

        // Move the uploaded file
        if (move_uploaded_file($_FILES['companyLogo']['tmp_name'], $uploadPath)) {
            $logoPath = 'uploads/company_logos/' . $newFileName;
        } else {
            $_SESSION['error_message'] = 'Failed to upload logo. Please try again.';
            header("Location: add-company.php");
            exit();
        }
    }

    // Additional fields (optional)
    $website = isset($_POST['website']) ? trim($_POST['website']) : null;
    $phone = isset($_POST['phone']) ? trim($_POST['phone']) : null;
    $notes = isset($_POST['notes']) ? trim($_POST['notes']) : null;

    // Insert company data into database
    $query = "INSERT INTO tbl_companies (company_name, address, country, email, person_in_charge, 
                        logo_path, date_signed_up, date_created) 
              VALUES (?, ?, ?, ?, ?, ?, ?, NOW())";

    $stmt = $conn->prepare($query);
    $stmt->bind_param("sssssss", $companyName, $address, $country, $email, $incharge, $logoPath, $dateSignedUp);

    if ($stmt->execute()) {
        $companyId = $conn->insert_id;

        // Log the action
        $userId = $_SESSION['user_id'];
        $action = "Added new company: $companyName";
        $logQuery = "INSERT INTO tbl_activity_logs (user_id, action, log_timestamp) VALUES (?, ?, NOW())";

        // If the activity log table exists
        if ($conn->query("SHOW TABLES LIKE 'tbl_activity_logs'")->num_rows > 0) {
            $logStmt = $conn->prepare($logQuery);
            $logStmt->bind_param("is", $userId, $action);
            $logStmt->execute();
            $logStmt->close();
        }

        $_SESSION['success_message'] = "Company '$companyName' has been added successfully!";

        // Always redirect to company list
        header("Location: company-list.php");
        exit();
    } else {
        $_SESSION['error_message'] = 'Database error: ' . $stmt->error;
        header("Location: add-company.php");
        exit();
    }

    $stmt->close();
}

// If we get here, something went wrong (invalid request method)
$_SESSION['error_message'] = 'Invalid request.';
header("Location: add-company.php");
exit();
?>