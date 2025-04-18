<?php
/**
 * Process Edit Company Form
 * Handles the form submission from edit-company.php
 */
session_start();
include("../includes/db_connection.php");

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    $_SESSION['error_message'] = 'You must log in to perform this action.';
    header("Location: ../login.php");
    exit();
}

// Check if form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // Get form data
    $companyId = isset($_POST['company_id']) ? intval($_POST['company_id']) : 0;
    $companyName = trim($_POST['companyName']);
    $dateSignedUp = $_POST['dateSignedUp'];
    $address = trim($_POST['address']);
    $country = $_POST['country'];
    $email = trim($_POST['email']);
    $incharge = trim($_POST['incharge']);
    $currentLogoPath = isset($_POST['current_logo_path']) ? $_POST['current_logo_path'] : '';

    // Validate company ID
    if ($companyId <= 0) {
        $_SESSION['error_message'] = 'Invalid company ID.';
        header("Location: company-list.php");
        exit();
    }

    // Validate required fields
    if (
        empty($companyName) || empty($dateSignedUp) || empty($address) ||
        empty($country) || empty($email) || empty($incharge)
    ) {
        $_SESSION['error_message'] = 'All required fields must be filled out.';
        header("Location: edit-company.php?id=$companyId");
        exit();
    }

    // Validate email
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $_SESSION['error_message'] = 'Please enter a valid email address.';
        header("Location: edit-company.php?id=$companyId");
        exit();
    }

    // Handle logo upload
    $logoPath = $currentLogoPath; // Keep existing logo by default
    if (isset($_FILES['companyLogo']) && $_FILES['companyLogo']['error'] == 0) {
        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
        $maxSize = 2 * 1024 * 1024; // 2MB

        // Check file type and size
        if (!in_array($_FILES['companyLogo']['type'], $allowedTypes)) {
            $_SESSION['error_message'] = 'Only JPG, PNG, and GIF image files are allowed.';
            header("Location: edit-company.php?id=$companyId");
            exit();
        }

        if ($_FILES['companyLogo']['size'] > $maxSize) {
            $_SESSION['error_message'] = 'Logo file size should not exceed 2MB.';
            header("Location: edit-company.php?id=$companyId");
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
            // Delete old logo if it exists
            if (!empty($currentLogoPath) && file_exists("../" . $currentLogoPath)) {
                unlink("../" . $currentLogoPath);
            }
            $logoPath = 'uploads/company_logos/' . $newFileName;
        } else {
            $_SESSION['error_message'] = 'Failed to upload logo. Please try again.';
            header("Location: edit-company.php?id=$companyId");
            exit();
        }
    }

    // Additional fields (optional)
    $website = isset($_POST['website']) ? trim($_POST['website']) : null;
    $phone = isset($_POST['phone']) ? trim($_POST['phone']) : null;
    $notes = isset($_POST['notes']) ? trim($_POST['notes']) : null;

    // Check if a company with the same name already exists (excluding current company)
    $checkQuery = "SELECT company_id FROM tbl_companies WHERE company_name = ? AND company_id != ?";
    $checkStmt = $conn->prepare($checkQuery);
    $checkStmt->bind_param("si", $companyName, $companyId);
    $checkStmt->execute();
    $result = $checkStmt->get_result();

    if ($result->num_rows > 0) {
        $_SESSION['error_message'] = "A company with the name \"$companyName\" already exists.";
        header("Location: edit-company.php?id=$companyId");
        exit();
    }
    $checkStmt->close();

    // Start transaction
    $conn->begin_transaction();

    try {
        // Update company data in database
        $query = "UPDATE tbl_companies SET 
                  company_name = ?, 
                  address = ?, 
                  country = ?, 
                  email = ?, 
                  person_in_charge = ?, 
                  logo_path = ?, 
                  date_signed_up = ?,
                  date_updated = NOW() 
                  WHERE company_id = ?";

        $stmt = $conn->prepare($query);
        $stmt->bind_param("sssssssi", $companyName, $address, $country, $email, $incharge, $logoPath, $dateSignedUp, $companyId);

        if ($stmt->execute()) {
            // Log the action
            $userId = $_SESSION['user_id'];
            $action = "Updated company: $companyName (ID: $companyId)";
            $logQuery = "INSERT INTO tbl_activity_logs (user_id, action, log_timestamp) VALUES (?, ?, NOW())";

            // If the activity log table exists
            if ($conn->query("SHOW TABLES LIKE 'tbl_activity_logs'")->num_rows > 0) {
                $logStmt = $conn->prepare($logQuery);
                $logStmt->bind_param("is", $userId, $action);
                $logStmt->execute();
                $logStmt->close();
            }

            $conn->commit();

            $_SESSION['success_message'] = "Company \"$companyName\" has been updated successfully!";

            // Always redirect to company list
            header("Location: company-list.php");
            exit();
        } else {
            throw new Exception("Database error: " . $stmt->error);
        }

        $stmt->close();

    } catch (Exception $e) {
        // Rollback the transaction in case of error
        $conn->rollback();
        $_SESSION['error_message'] = $e->getMessage();
        header("Location: edit-company.php?id=$companyId");
        exit();
    }
}

// Function to get company by ID
function getCompanyById($companyId, $conn)
{
    $query = "SELECT * FROM tbl_companies WHERE company_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $companyId);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        return $result->fetch_assoc();
    } else {
        return null;
    }
}

// Check if it's a GET request with an ID parameter (to fetch company data)
if ($_SERVER["REQUEST_METHOD"] == "GET" && isset($_GET['id'])) {
    $companyId = intval($_GET['id']);

    // Get company data
    $company = getCompanyById($companyId, $conn);

    if ($company) {
        // Return company data as JSON
        header('Content-Type: application/json');
        echo json_encode([
            'status' => 'success',
            'data' => $company
        ]);
    } else {
        // Return error
        header('Content-Type: application/json');
        echo json_encode([
            'status' => 'error',
            'message' => 'Company not found'
        ]);
    }
    exit();
}

// If we get here, something went wrong (invalid request method)
$_SESSION['error_message'] = 'Invalid request.';
header("Location: company-list.php");
exit();
?>