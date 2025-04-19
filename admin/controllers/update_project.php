<?php
/**
 * Update Project Controller
 * This file handles the update of projects from the edit-project.php form
 */

// Use the more reliable passthrough for db connection
require_once __DIR__ . '/db_connection_passthrough.php';
require_once 'unified_project_controller.php';

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// debug_log function is already defined in project_controller.php, so we don't need to redefine it here
// Using debug_log from project_controller.php

// Debug incoming data
debug_log("[START] Update project request received", [
    "method" => $_SERVER['REQUEST_METHOD'],
    "POST data keys" => array_keys($_POST),
    "POST data size" => strlen(json_encode($_POST)),
    "DB conn available" => isset($conn) && $conn instanceof mysqli ? "Yes" : "No",
    "Current time" => date('Y-m-d H:i:s')
]);

// Check if it's a POST request
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get project ID from the form submission or query string
    $project_id = isset($_POST['project_id']) ? intval($_POST['project_id']) : (isset($_GET['id']) ? intval($_GET['id']) : 0);

    debug_log("Processing project ID", $project_id);

    if ($project_id <= 0) {
        debug_log("ERROR: Invalid project ID", $project_id);
        echo json_encode(['status' => 'error', 'message' => 'Invalid project ID']);
        exit();
    }

    try {
        // Get form data
        $project_title = $_POST['projectName'] ?? '';
        $company = $_POST['company'] ?? '';
        $description = $_POST['description'] ?? ''; // Allow empty description
        $total_images = intval($_POST['totalImages'] ?? 0);
        $status = $_POST['status'] ?? 'Pending';
        $priority = $_POST['priority'] ?? 'Medium';
        $date_arrived = $_POST['dateArrived'] ?? date('Y-m-d');
        $deadline = $_POST['deadline'] ?? date('Y-m-d', strtotime('+30 days'));
        $imageAssignmentsJSON = $_POST['imageAssignments'] ?? '[]';

        debug_log("Form data parsed", [
            "project_title" => $project_title,
            "company" => $company,
            "status" => $status,
            "priority" => $priority,
            "date_arrived" => $date_arrived,
            "deadline" => $deadline,
            "total_images" => $total_images
        ]);

        // Decode image assignments JSON
        $imageAssignments = [];
        if (!empty($imageAssignmentsJSON)) {
            $imageAssignments = json_decode($imageAssignmentsJSON, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                debug_log("JSON decode error", [
                    "error" => json_last_error_msg(),
                    "input" => $imageAssignmentsJSON
                ]);
                throw new Exception("Invalid image assignments data format");
            }

            // Validate the structure of image assignments
            foreach ($imageAssignments as $assignment) {
                if (!isset($assignment['image_id']) || !isset($assignment['assignment_id'])) {
                    throw new Exception("Invalid image assignment structure");
                }
            }
        }
        debug_log("Image assignments validated", $imageAssignments);

        // Start transaction
        $conn->begin_transaction();
        debug_log("Transaction started");

        // Update project status (view-only)
        $status_project = $_POST['status_project'] ?? 'pending';
        debug_log("Project status (view-only)", $status_project);

        // Update task status (view-only)
        $status_assignee = $_POST['status_assignee'] ?? 'pending';
        debug_log("Task status (view-only)", $status_assignee);

        // Only update status if explicitly approved
        if (isset($_POST['approve_task']) && $_POST['approve_task'] === 'true') {
            $status_assignee = 'completed';
            debug_log("Task approved, updating status to completed");
        }

        // First, update the project details
        $sql = "UPDATE tbl_projects SET 
                project_title = ?, 
                description = ?, 
                date_arrived = ?, 
                deadline = ?, 
                priority = ?, 
                status_project = ?, 
                total_images = ?, 
                date_updated = NOW()
                WHERE project_id = ?";

        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            throw new Exception("Prepare statement error: " . $conn->error);
        }

        debug_log("Updating project with status_project: " . $status_project);
        $stmt->bind_param("ssssssii", $project_title, $description, $date_arrived, $deadline, $priority, $status_project, $total_images, $project_id);

        debug_log("Executing project update query");
        if (!$stmt->execute()) {
            throw new Exception("Error updating project: " . $stmt->error);
        }
        debug_log("Project details updated successfully");

        // Get company ID or insert it if it doesn't exist
        $company_id = 0;
        $companySql = "SELECT company_id FROM tbl_companies WHERE company_name = ?";
        $companyStmt = $conn->prepare($companySql);
        if (!$companyStmt) {
            throw new Exception("Prepare company statement error: " . $conn->error);
        }

        $companyStmt->bind_param("s", $company);
        $companyStmt->execute();
        $companyResult = $companyStmt->get_result();

        if ($companyResult->num_rows > 0) {
            $companyRow = $companyResult->fetch_assoc();
            $company_id = $companyRow['company_id'];
            debug_log("Existing company found", $company_id);
        } else {
            // Insert new company
            debug_log("Creating new company", $company);
            $insertCompanySql = "INSERT INTO tbl_companies (company_name) VALUES (?)";
            $insertCompanyStmt = $conn->prepare($insertCompanySql);
            if (!$insertCompanyStmt) {
                throw new Exception("Prepare insert company statement error: " . $conn->error);
            }

            $insertCompanyStmt->bind_param("s", $company);

            if (!$insertCompanyStmt->execute()) {
                throw new Exception("Error adding new company: " . $insertCompanyStmt->error);
            }

            $company_id = $conn->insert_id;
            debug_log("New company created", $company_id);
        }

        // Update the company ID
        $updateCompanySql = "UPDATE tbl_projects SET company_id = ? WHERE project_id = ?";
        $updateCompanyStmt = $conn->prepare($updateCompanySql);
        if (!$updateCompanyStmt) {
            throw new Exception("Prepare update company statement error: " . $conn->error);
        }

        $updateCompanyStmt->bind_param("ii", $company_id, $project_id);

        if (!$updateCompanyStmt->execute()) {
            throw new Exception("Error updating project company: " . $updateCompanyStmt->error);
        }
        debug_log("Project company updated", $company_id);

        // Handle assignees and their images
        // First, get the current assignments
        $currentAssignments = getProjectAssignments($project_id);
        $currentAssignmentIds = [];
        foreach ($currentAssignments as $assignment) {
            $currentAssignmentIds[$assignment['assignment_id']] = $assignment;
        }
        debug_log("Current assignments", $currentAssignmentIds);

        // Initialize array for new assignment image mapping
        $newAssignmentImages = [];

        // Handle image assignments between people
        if (!empty($imageAssignments)) {
            // Check if assignment_id column exists in tbl_project_images
            $checkColumnSql = "SHOW COLUMNS FROM tbl_project_images LIKE 'assignment_id'";
            $checkColumnResult = $conn->query($checkColumnSql);
            $hasAssignmentColumn = $checkColumnResult->num_rows > 0;
            debug_log("Has assignment_id column", $hasAssignmentColumn);

            // If the column doesn't exist, create it
            if (!$hasAssignmentColumn) {
                debug_log("Adding assignment_id column to tbl_project_images");
                $alterTableSql = "ALTER TABLE tbl_project_images 
                                 ADD COLUMN assignment_id INT NULL, 
                                 ADD INDEX idx_assignment_id (assignment_id)";
                if (!$conn->query($alterTableSql)) {
                    throw new Exception("Error adding assignment_id column: " . $conn->error);
                }
                $hasAssignmentColumn = true;
            }

            // Now update with new assignments
            debug_log("Processing image assignments");
            foreach ($imageAssignments as $assignment) {
                $imageId = $assignment['image_id'];
                $assignmentId = $assignment['assignment_id'];
                debug_log("Processing image", [
                    "image_id" => $imageId,
                    "assignment_id" => $assignmentId
                ]);

                // Handle both numeric and 'new-X' assignment IDs
                if (is_numeric($assignmentId) || (is_string($assignmentId) && strpos($assignmentId, 'new-') === 0)) {
                    // For existing assignments
                    if (is_numeric($assignmentId) && isset($currentAssignmentIds[$assignmentId])) {
                        debug_log("Updating existing assignment");
                        $updateImageSql = "UPDATE tbl_project_images SET assignment_id = ? WHERE image_id = ? AND project_id = ?";
                        $updateImageStmt = $conn->prepare($updateImageSql);
                        if (!$updateImageStmt) {
                            throw new Exception("Prepare update image statement error: " . $conn->error);
                        }

                        $updateImageStmt->bind_param("iii", $assignmentId, $imageId, $project_id);

                        if (!$updateImageStmt->execute()) {
                            throw new Exception("Error updating image assignment: " . $updateImageStmt->error);
                        }
                    }
                    // For new assignments, we'll handle this after new assignments are created
                    else if (is_string($assignmentId) && strpos($assignmentId, 'new-') === 0) {
                        // Store for later processing
                        debug_log("Storing new assignment image", [
                            "assignment_id" => $assignmentId,
                            "image_id" => $imageId
                        ]);
                        $newAssignmentImages[$assignmentId][] = $imageId;
                    }
                }
            }
        }

        // Process each assignee from the form
        $newAssignmentIds = [];  // To track new assignment IDs for image linking

        // Get existing assignment IDs from the database first to avoid duplication
        $existingAssignmentsSql = "SELECT assignment_id FROM tbl_project_assignments WHERE project_id = ?";
        $existingAssignmentsStmt = $conn->prepare($existingAssignmentsSql);
        $existingAssignmentsStmt->bind_param("i", $project_id);
        $existingAssignmentsStmt->execute();
        $existingAssignmentsResult = $existingAssignmentsStmt->get_result();

        $existingAssignmentIds = [];
        while ($row = $existingAssignmentsResult->fetch_assoc()) {
            $existingAssignmentIds[] = $row['assignment_id'];
        }

        debug_log("Existing assignment IDs", $existingAssignmentIds);

        // Track the assignment IDs we've seen in this form submission
        $processedAssignmentIds = [];

        if (isset($_POST['assignee']) && is_array($_POST['assignee'])) {
            $assignees = $_POST['assignee'];
            $roles = $_POST['role'] ?? [];
            $deadlines = $_POST['taskDeadline'] ?? [];

            debug_log("Processing assignees", [
                "count" => count($assignees),
                "assignees" => $assignees
            ]);

            foreach ($assignees as $index => $assignee) {
                if (empty($assignee)) {
                    debug_log("Empty assignee at index $index, skipping");
                    continue;
                }

                debug_log("Processing assignee", [
                    "index" => $index,
                    "name" => $assignee
                ]);

                $role = $roles[$index] ?? '';
                $task_deadline = $deadlines[$index] ?? date('Y-m-d', strtotime('+7 days'));

                // This variable will hold the assignment ID for this person
                $assignmentId = null;

                // Check if we're processing an existing assignment
                if (isset($currentAssignmentIds[$index])) {
                    // Update existing assignment
                    $assignmentId = $index;
                    $processedAssignmentIds[] = $assignmentId;

                    debug_log("Updating existing assignment", $assignmentId);
                    $updateAssignSql = "UPDATE tbl_project_assignments SET 
                                      role_task = ?,
                                      deadline = ?,
                                      status_assignee = ?
                                      WHERE assignment_id = ?";
                    $updateAssignStmt = $conn->prepare($updateAssignSql);
                    if (!$updateAssignStmt) {
                        throw new Exception("Prepare update assignment statement error: " . $conn->error);
                    }

                    $status_assignee = $_POST['status_assignee'][$index] ?? 'pending';
                    debug_log("Setting deadline for assignment: " . $assignmentId . " to " . $task_deadline);
                    debug_log("Setting role for assignment: " . $assignmentId . " to '" . $role . "'");
                    debug_log("Setting status for assignment: " . $assignmentId . " to '" . $status_assignee . "'");
                    debug_log("SQL Update Statement", $updateAssignSql);
                    debug_log("Binding parameters for update", [
                        "role" => $role,
                        "deadline" => $task_deadline,
                        "status_assignee" => $status_assignee,
                        "assignment_id" => $assignmentId
                    ]);

                    $updateAssignStmt->bind_param("sssi", $role, $task_deadline, $status_assignee, $assignmentId);

                    if (!$updateAssignStmt->execute()) {
                        throw new Exception("Error updating assignment: " . $updateAssignStmt->error . ". SQL: " . $updateAssignSql);
                    }

                    // Verify the update after execution
                    $verifyUpdateSql = "SELECT deadline FROM tbl_project_assignments WHERE assignment_id = ?";
                    $verifyStmt = $conn->prepare($verifyUpdateSql);
                    $verifyStmt->bind_param("i", $assignmentId);
                    $verifyStmt->execute();
                    $verifyResult = $verifyStmt->get_result();
                    $verifyRow = $verifyResult->fetch_assoc();
                    debug_log("Verified deadline after update", [
                        "assignment_id" => $assignmentId,
                        "deadline" => $verifyRow['deadline'] ?? 'NULL'
                    ]);
                } else {
                    // Check which user table exists
                    $checkQuery = "SHOW TABLES LIKE 'tbl_accounts'";
                    $tableResult = $conn->query($checkQuery);
                    debug_log("Checking for accounts table", $tableResult->num_rows > 0);

                    if ($tableResult && $tableResult->num_rows > 0) {
                        // Using tbl_accounts
                        $nameParts = explode(' ', $assignee);
                        $firstName = $nameParts[0] ?? '';
                        $lastName = $nameParts[1] ?? '';
                        debug_log("Name parts", [
                            "firstName" => $firstName,
                            "lastName" => $lastName
                        ]);

                        // Look up user ID - fix the JOIN condition to use account_id
                        $userSql = "SELECT a.account_id as user_id 
                                  FROM tbl_accounts a
                                  JOIN tbl_users u ON a.user_id = u.user_id
                                  WHERE u.first_name LIKE ? AND u.last_name LIKE ?";
                        $userStmt = $conn->prepare($userSql);
                        if (!$userStmt) {
                            throw new Exception("Prepare user lookup statement error: " . $conn->error);
                        }

                        $firstNameParam = $firstName . '%';
                        $lastNameParam = $lastName . '%';
                        $userStmt->bind_param("ss", $firstNameParam, $lastNameParam);
                        $userStmt->execute();
                        $userResult = $userStmt->get_result();
                        debug_log("User lookup result", [
                            "rows" => $userResult->num_rows,
                            "SQL" => $userSql,
                            "params" => [$firstNameParam, $lastNameParam]
                        ]);

                        $userId = null;
                        if ($userResult->num_rows > 0) {
                            $userRow = $userResult->fetch_assoc();
                            $userId = $userRow['user_id'];
                            debug_log("Existing user found", $userId);
                        } else {
                            // Create a new user if not found
                            $createUserSql = "INSERT INTO tbl_users (first_name, last_name, mid_name, email_address, profile_img, birth_date, address, contact_num)
                                  VALUES (?, ?, '', ?, 'default.jpg', NOW(), '', '')";
                            $username = strtolower($firstName . '.' . $lastName);
                            $email = $username . '@example.com';
                            $createUserStmt = $conn->prepare($createUserSql);
                            $createUserStmt->bind_param("sss", $firstName, $lastName, $email);

                            if (!$createUserStmt->execute()) {
                                throw new Exception("Error creating new user: " . $createUserStmt->error);
                            }

                            $userId = $conn->insert_id;

                            // Now create account in tbl_accounts table
                            $defaultPassword = password_hash('password123', PASSWORD_DEFAULT);
                            $createAccountSql = "INSERT INTO tbl_accounts (user_id, username, password, role, status) 
                                             VALUES (?, ?, ?, 'Graphic Artist', 'Active')";
                            $createAccountStmt = $conn->prepare($createAccountSql);
                            $createAccountStmt->bind_param("iss", $userId, $username, $defaultPassword);

                            if (!$createAccountStmt->execute()) {
                                throw new Exception("Error creating new account: " . $createAccountStmt->error);
                            }
                        }

                        // Now create the assignment
                        debug_log("Creating new assignment for project", [
                            "project_id" => $project_id,
                            "user_id" => $userId,
                            "role" => $role,
                            "deadline" => $task_deadline,
                            "status_assignee" => 'pending'
                        ]);

                        $newAssignSql = "INSERT INTO tbl_project_assignments 
                                      (project_id, user_id, role_task, assigned_date, deadline, status_assignee) 
                                      VALUES (?, ?, ?, NOW(), ?, 'pending')";
                        $newAssignStmt = $conn->prepare($newAssignSql);
                        if (!$newAssignStmt) {
                            throw new Exception("Prepare new assignment statement error: " . $conn->error);
                        }

                        debug_log("SQL Insert Statement for new assignment", $newAssignSql);
                        debug_log("Binding parameters", [
                            "project_id" => $project_id,
                            "user_id" => $userId,
                            "role" => $role,
                            "deadline" => $task_deadline
                        ]);

                        $newAssignStmt->bind_param("iiss", $project_id, $userId, $role, $task_deadline);

                        debug_log("Executing new assignment insert with deadline: " . $task_deadline);
                        if (!$newAssignStmt->execute()) {
                            throw new Exception("Error creating assignment: " . $newAssignStmt->error . ". SQL: " . $newAssignSql);
                        }

                        $assignmentId = $conn->insert_id;
                        debug_log("New assignment created", $assignmentId);

                        // Store this for new image assignments
                        $newAssignmentKey = "new-" . $index;
                        $newAssignmentIds[$newAssignmentKey] = $assignmentId;
                    } else {
                        // Fallback to tbl_users
                        debug_log("Using tbl_users fallback");
                        // Parse name to find user ID
                        $nameParts = explode(' ', $assignee);
                        $firstName = $nameParts[0] ?? '';
                        $lastName = $nameParts[1] ?? '';

                        // Look up user ID
                        $userSql = "SELECT user_id FROM tbl_users 
                               WHERE first_name LIKE ? AND last_name LIKE ?";
                        $userStmt = $conn->prepare($userSql);
                        if (!$userStmt) {
                            throw new Exception("Prepare user lookup statement error: " . $conn->error);
                        }

                        $firstNameParam = $firstName . '%';
                        $lastNameParam = $lastName . '%';
                        $userStmt->bind_param("ss", $firstNameParam, $lastNameParam);
                        $userStmt->execute();
                        $userResult = $userStmt->get_result();

                        $userId = null;
                        if ($userResult->num_rows > 0) {
                            $userRow = $userResult->fetch_assoc();
                            $userId = $userRow['user_id'];
                        } else {
                            // Create a new user if not found
                            $createUserSql = "INSERT INTO tbl_users (first_name, last_name, mid_name, email_address, profile_img, birth_date, address, contact_num)
                                  VALUES (?, ?, '', ?, 'default.jpg', NOW(), '', '')";
                            $username = strtolower($firstName . '.' . $lastName);
                            $email = $username . '@example.com';
                            $createUserStmt = $conn->prepare($createUserSql);
                            $createUserStmt->bind_param("sss", $firstName, $lastName, $email);

                            if (!$createUserStmt->execute()) {
                                throw new Exception("Error creating new user: " . $createUserStmt->error);
                            }

                            $userId = $conn->insert_id;
                        }
                    }
                }

                // Now process images for this assignment if they were selected from existing ones
                if (isset($_POST['imageId'][$index]) && is_array($_POST['imageId'][$index])) {
                    foreach ($_POST['imageId'][$index] as $imgIndex => $imageId) {
                        $updateImageSql = "UPDATE tbl_project_images SET assignment_id = ? WHERE image_id = ? AND project_id = ?";
                        $updateImageStmt = $conn->prepare($updateImageSql);
                        $updateImageStmt->bind_param("iii", $assignmentId, $imageId, $project_id);

                        if (!$updateImageStmt->execute()) {
                            throw new Exception("Error assigning image: " . $updateImageStmt->error);
                        }
                    }
                }

                // Process new images for this assignee if any
                if (isset($_FILES['images']) && isset($_FILES['images']['name'][$index]) && is_array($_FILES['images']['name'][$index])) {
                    foreach ($_FILES['images']['name'][$index] as $imgIndex => $imageName) {
                        if (empty($imageName))
                            continue;

                        // Validate file type
                        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
                        $fileType = $_FILES['images']['type'][$index][$imgIndex];
                        if (!in_array($fileType, $allowedTypes)) {
                            throw new Exception("Invalid file type. Only JPEG, PNG, and GIF images are allowed.");
                        }

                        // Validate file size (5MB max)
                        $maxFileSize = 5 * 1024 * 1024; // 5MB in bytes
                        $fileSize = $_FILES['images']['size'][$index][$imgIndex];
                        if ($fileSize > $maxFileSize) {
                            throw new Exception("File size exceeds the maximum limit of 5MB.");
                        }

                        // Check for upload errors
                        if ($_FILES['images']['error'][$index][$imgIndex] !== UPLOAD_ERR_OK) {
                            $uploadErrors = [
                                UPLOAD_ERR_INI_SIZE => "The uploaded file exceeds the upload_max_filesize directive in php.ini",
                                UPLOAD_ERR_FORM_SIZE => "The uploaded file exceeds the MAX_FILE_SIZE directive in the HTML form",
                                UPLOAD_ERR_PARTIAL => "The uploaded file was only partially uploaded",
                                UPLOAD_ERR_NO_FILE => "No file was uploaded",
                                UPLOAD_ERR_NO_TMP_DIR => "Missing a temporary folder",
                                UPLOAD_ERR_CANT_WRITE => "Failed to write file to disk",
                                UPLOAD_ERR_EXTENSION => "A PHP extension stopped the file upload"
                            ];
                            $errorMessage = $uploadErrors[$_FILES['images']['error'][$index][$imgIndex]] ?? "Unknown upload error";
                            throw new Exception("File upload failed: " . $errorMessage);
                        }

                        // Generate a unique filename
                        $timestamp = time();
                        $unique_id = uniqid();
                        $file_ext = pathinfo($imageName, PATHINFO_EXTENSION);
                        $new_filename = "project_{$project_id}_{$timestamp}_{$unique_id}.{$file_ext}";
                        $upload_path = "../../uploads/projects/" . $new_filename;

                        // Move uploaded file
                        if (move_uploaded_file($_FILES['images']['tmp_name'][$index][$imgIndex], $upload_path)) {
                            // Save image information in database
                            $imageSql = "INSERT INTO tbl_project_images 
                                        (project_id, assignment_id, image_path, file_type, file_size) 
                                        VALUES (?, ?, ?, ?, ?)";
                            $relativePath = "uploads/projects/" . $new_filename;
                            $fileType = $_FILES['images']['type'][$index][$imgIndex];
                            $fileSize = $_FILES['images']['size'][$index][$imgIndex];

                            $imageStmt = $conn->prepare($imageSql);
                            $imageStmt->bind_param("iissi", $project_id, $assignmentId, $relativePath, $fileType, $fileSize);

                            if (!$imageStmt->execute()) {
                                throw new Exception("Error saving image information: " . $imageStmt->error);
                            }
                        }
                    }
                }
            }
        } else {
            debug_log("No assignees found in form data");
        }

        // Now handle images for new assignments
        if (!empty($newAssignmentIds) && !empty($newAssignmentImages)) {
            debug_log("Processing images for new assignments", [
                "newAssignmentIds" => $newAssignmentIds,
                "newAssignmentImages count" => count($newAssignmentImages)
            ]);

            foreach ($newAssignmentIds as $tempId => $realId) {
                if (isset($newAssignmentImages[$tempId])) {
                    foreach ($newAssignmentImages[$tempId] as $imageId) {
                        debug_log("Updating image assignment", [
                            "tempId" => $tempId,
                            "realId" => $realId,
                            "imageId" => $imageId
                        ]);

                        $updateImageSql = "UPDATE tbl_project_images SET assignment_id = ? WHERE image_id = ? AND project_id = ?";
                        $updateImageStmt = $conn->prepare($updateImageSql);
                        if (!$updateImageStmt) {
                            throw new Exception("Prepare update image statement error: " . $conn->error);
                        }

                        $updateImageStmt->bind_param("iii", $realId, $imageId, $project_id);

                        if (!$updateImageStmt->execute()) {
                            throw new Exception("Error updating image assignment: " . $updateImageStmt->error);
                        }
                    }
                }
            }
        }

        // Handle newly uploaded files
        // Check for new uploaded files
        debug_log("Checking for new image uploads");
        $newImageFiles = [];

        foreach ($_POST as $key => $value) {
            if (strpos($key, 'newImageFile') === 0 && is_array($value)) {
                foreach ($value as $personIndex => $files) {
                    if (is_array($files)) {
                        $newImageFiles[$personIndex] = $files;
                    }
                }
            }
        }

        if (!empty($newImageFiles)) {
            debug_log("Processing new image files", $newImageFiles);
            // Process the new files here
        }

        // After processing all assignments, remove any assignments that weren't included in this submission
        $removalAssignmentIds = array_diff($existingAssignmentIds, $processedAssignmentIds);

        if (!empty($removalAssignmentIds)) {
            debug_log("Removing assignments not in current submission", $removalAssignmentIds);

            foreach ($removalAssignmentIds as $removalId) {
                // First update any assigned images to remove the assignment
                $clearImagesSql = "UPDATE tbl_project_images SET assignment_id = NULL WHERE assignment_id = ?";
                $clearImagesStmt = $conn->prepare($clearImagesSql);
                if ($clearImagesStmt) {
                    $clearImagesStmt->bind_param("i", $removalId);
                    $clearImagesStmt->execute();
                }

                // Then delete the assignment
                $deleteAssignmentSql = "DELETE FROM tbl_project_assignments WHERE assignment_id = ?";
                $deleteAssignmentStmt = $conn->prepare($deleteAssignmentSql);
                if ($deleteAssignmentStmt) {
                    $deleteAssignmentStmt->bind_param("i", $removalId);
                    $deleteAssignmentStmt->execute();
                }
            }
        }

        // After all updates to image assignments, count and update assigned_images values
        debug_log("Updating assigned_images counts in tbl_project_assignments");

        // Process all project assignments to update their assigned_images count
        $updateAssignedImagesSql = "UPDATE tbl_project_assignments pa 
                                  SET pa.assigned_images = (
                                      SELECT COUNT(*) 
                                      FROM tbl_project_images pi 
                                      WHERE pi.assignment_id = pa.assignment_id AND pi.project_id = ?
                                  )
                                  WHERE pa.project_id = ?";
        $updateAssignedImagesStmt = $conn->prepare($updateAssignedImagesSql);
        if ($updateAssignedImagesStmt) {
            $updateAssignedImagesStmt->bind_param("ii", $project_id, $project_id);
            $updateAssignedImagesStmt->execute();
            debug_log("Updated assigned_images counts for all assignments in project");
        } else {
            debug_log("Error preparing update assigned_images statement: " . $conn->error);
        }

        // Commit transaction if we made it this far
        $conn->commit();
        debug_log("[SUCCESS] Transaction committed successfully for project ID: " . $project_id);

        // Return success response
        $response = ['status' => 'success', 'message' => 'Project updated successfully'];
        debug_log("[RESPONSE] Sending success response", $response);
        echo json_encode($response);

    } catch (Exception $e) {
        // Roll back the transaction on error
        $conn->rollback();
        $errorMessage = $e->getMessage();
        debug_log("[ERROR] Exception occurred: " . $errorMessage, [
            'trace' => $e->getTraceAsString(),
            'project_id' => $project_id,
            'time' => date('Y-m-d H:i:s')
        ]);
        echo json_encode(['status' => 'error', 'message' => $errorMessage]);
    } finally {
        // Close all statements
        if (isset($stmt))
            $stmt->close();
        if (isset($companyStmt))
            $companyStmt->close();
        if (isset($insertCompanyStmt))
            $insertCompanyStmt->close();
        if (isset($updateCompanyStmt))
            $updateCompanyStmt->close();
        if (isset($updateImageStmt))
            $updateImageStmt->close();
        if (isset($userStmt))
            $userStmt->close();
        if (isset($createUserStmt))
            $createUserStmt->close();
        if (isset($updateAssignStmt))
            $updateAssignStmt->close();
        if (isset($newAssignStmt))
            $newAssignStmt->close();
        if (isset($updateAssignedImagesStmt))
            $updateAssignedImagesStmt->close();

        debug_log("[END] Update project processing complete", [
            'project_id' => $project_id,
            'time' => date('Y-m-d H:i:s')
        ]);
    }

    // Check for specific actions that don't require form validation
    if (isset($_POST['action'])) {
        $action = $_POST['action'];
        debug_log("Processing action", $action);

        try {
            // Start transaction if not started yet
            if (!$conn->begin_transaction(MYSQLI_TRANS_START_READ_WRITE)) {
                throw new Exception("Failed to start transaction");
            }

            switch ($action) {
                case 'approve_task':
                    // Handle task approval
                    $assignment_id = isset($_POST['assignment_id']) ? intval($_POST['assignment_id']) : 0;
                    $project_id = isset($_POST['project_id']) ? intval($_POST['project_id']) : 0;

                    debug_log("Approving task", ["assignment_id" => $assignment_id, "project_id" => $project_id]);

                    if ($assignment_id <= 0 || $project_id <= 0) {
                        throw new Exception("Invalid assignment ID or project ID");
                    }

                    // Verify assignment exists and belongs to this project
                    $checkSql = "SELECT * FROM tbl_project_assignments WHERE assignment_id = ? AND project_id = ?";
                    $checkStmt = $conn->prepare($checkSql);
                    if (!$checkStmt) {
                        throw new Exception("Failed to prepare check statement: " . $conn->error);
                    }

                    $checkStmt->bind_param("ii", $assignment_id, $project_id);
                    if (!$checkStmt->execute()) {
                        throw new Exception("Failed to check assignment: " . $checkStmt->error);
                    }

                    $result = $checkStmt->get_result();
                    if ($result->num_rows === 0) {
                        throw new Exception("Assignment not found or doesn't belong to this project");
                    }

                    // Update the assignment status to completed
                    $updateSql = "UPDATE tbl_project_assignments SET status_assignee = 'completed', date_modified = NOW() WHERE assignment_id = ?";
                    $updateStmt = $conn->prepare($updateSql);
                    if (!$updateStmt) {
                        throw new Exception("Failed to prepare update statement: " . $conn->error);
                    }

                    $updateStmt->bind_param("i", $assignment_id);
                    if (!$updateStmt->execute()) {
                        throw new Exception("Failed to update assignment status: " . $updateStmt->error);
                    }

                    // Check if all assignments for this project are completed
                    $checkAllSql = "SELECT COUNT(*) as total, SUM(CASE WHEN status_assignee = 'completed' THEN 1 ELSE 0 END) as completed 
                                    FROM tbl_project_assignments WHERE project_id = ?";
                    $checkAllStmt = $conn->prepare($checkAllSql);
                    $checkAllStmt->bind_param("i", $project_id);
                    $checkAllStmt->execute();
                    $statsResult = $checkAllStmt->get_result()->fetch_assoc();

                    // If all assignments are completed, update project status to completed
                    if ($statsResult['total'] > 0 && $statsResult['total'] == $statsResult['completed']) {
                        $updateProjectSql = "UPDATE tbl_projects SET status_project = 'completed', date_updated = NOW() WHERE project_id = ?";
                        $updateProjectStmt = $conn->prepare($updateProjectSql);
                        $updateProjectStmt->bind_param("i", $project_id);
                        $updateProjectStmt->execute();
                    }

                    // Commit the transaction
                    if (!$conn->commit()) {
                        throw new Exception("Failed to commit transaction");
                    }

                    echo json_encode([
                        'success' => true,
                        'message' => 'Task approved successfully'
                    ]);
                    exit();

                case 'upload_project_images':
                    // Handle image upload
                    if (!isset($_POST['project_id'])) {
                        throw new Exception("Missing project ID");
                    }

                    $projectId = intval($_POST['project_id']);
                    debug_log("Uploading images for project", $projectId);

                    if (!isset($_FILES['images']) && !isset($_FILES['projectImagesUpload'])) {
                        throw new Exception("No images uploaded");
                    }

                    // Handle different field names for file uploads
                    $fileField = isset($_FILES['images']) ? 'images' : 'projectImagesUpload';
                    $files = $_FILES[$fileField];

                    // Prepare the upload directory
                    $uploadDir = "../../uploads/projects/{$projectId}/";
                    if (!is_dir($uploadDir)) {
                        if (!mkdir($uploadDir, 0777, true)) {
                            throw new Exception("Failed to create upload directory");
                        }
                    }

                    debug_log("Upload directory prepared", $uploadDir);

                    // Get current assignments for this project to potentially assign images
                    $assignmentQuery = "SELECT assignment_id FROM tbl_project_assignments WHERE project_id = ? AND status_assignee != 'completed' LIMIT 1";
                    $assignmentStmt = $conn->prepare($assignmentQuery);
                    $assignmentStmt->bind_param("i", $projectId);
                    $assignmentStmt->execute();
                    $assignmentResult = $assignmentStmt->get_result();

                    // Get the first active assignment ID if available
                    $assignmentId = null;
                    if ($assignmentResult->num_rows > 0) {
                        $assignmentRow = $assignmentResult->fetch_assoc();
                        $assignmentId = $assignmentRow['assignment_id'];
                        debug_log("Found active assignment for image upload", $assignmentId);
                    }

                    // Process each uploaded file
                    $uploadedImages = [];
                    for ($i = 0; $i < count($files['name']); $i++) {
                        if ($files['error'][$i] !== UPLOAD_ERR_OK) {
                            debug_log("Upload error for file", [
                                "name" => $files['name'][$i],
                                "error" => $files['error'][$i]
                            ]);
                            continue;
                        }

                        // Generate a unique filename
                        $originalName = $files['name'][$i];
                        $extension = pathinfo($originalName, PATHINFO_EXTENSION);
                        $newFilename = "project_{$projectId}_" . time() . "_" . uniqid() . ".{$extension}";
                        $targetFile = $uploadDir . $newFilename;

                        // Move the uploaded file
                        if (move_uploaded_file($files['tmp_name'][$i], $targetFile)) {
                            debug_log("File uploaded successfully", $targetFile);

                            // Insert into database
                            $relativePath = "uploads/projects/{$projectId}/{$newFilename}";
                            $insertQuery = "INSERT INTO tbl_project_images (project_id, image_path, assignment_id, upload_date) VALUES (?, ?, ?, NOW())";
                            $insertStmt = $conn->prepare($insertQuery);
                            $insertStmt->bind_param("isi", $projectId, $relativePath, $assignmentId);

                            if ($insertStmt->execute()) {
                                $imageId = $conn->insert_id;
                                debug_log("Image record created", $imageId);

                                $uploadedImages[] = [
                                    'image_id' => $imageId,
                                    'project_id' => $projectId,
                                    'image_path' => $relativePath,
                                    'assignment_id' => $assignmentId
                                ];
                            } else {
                                debug_log("Failed to insert image record", $conn->error);
                            }
                        } else {
                            debug_log("Failed to move uploaded file", $targetFile);
                        }
                    }

                    // Update the total_images count in the project
                    $updateCountQuery = "UPDATE tbl_projects SET total_images = (SELECT COUNT(*) FROM tbl_project_images WHERE project_id = ?) WHERE project_id = ?";
                    $updateCountStmt = $conn->prepare($updateCountQuery);
                    $updateCountStmt->bind_param("ii", $projectId, $projectId);
                    $updateCountStmt->execute();

                    // Get all project images
                    $allImagesQuery = "SELECT * FROM tbl_project_images WHERE project_id = ?";
                    $allImagesStmt = $conn->prepare($allImagesQuery);
                    $allImagesStmt->bind_param("i", $projectId);
                    $allImagesStmt->execute();
                    $allImagesResult = $allImagesStmt->get_result();

                    $allImages = [];
                    while ($row = $allImagesResult->fetch_assoc()) {
                        $allImages[] = $row;
                    }

                    // Commit transaction
                    $conn->commit();

                    echo json_encode([
                        'status' => 'success',
                        'message' => 'Images uploaded successfully',
                        'images' => $allImages
                    ]);
                    exit();

                case 'delete_project_image':
                    // Handle image deletion
                    if (!isset($_POST['image_id'])) {
                        throw new Exception("Missing image ID");
                    }

                    $imageId = intval($_POST['image_id']);
                    debug_log("Deleting image", $imageId);

                    // Delete image from database
                    $deleteSql = "DELETE FROM tbl_project_images WHERE image_id = ?";
                    $deleteStmt = $conn->prepare($deleteSql);
                    if (!$deleteStmt) {
                        throw new Exception("Failed to prepare delete statement: " . $conn->error);
                    }

                    $deleteStmt->bind_param("i", $imageId);
                    if (!$deleteStmt->execute()) {
                        throw new Exception("Error deleting image: " . $deleteStmt->error);
                    }

                    // Update the total_images count in the project
                    $updateCountQuery = "UPDATE tbl_projects SET total_images = (SELECT COUNT(*) FROM tbl_project_images WHERE project_id = ?) WHERE project_id = ?";
                    $updateCountStmt = $conn->prepare($updateCountQuery);
                    $updateCountStmt->bind_param("ii", $projectId, $projectId);
                    $updateCountStmt->execute();

                    echo json_encode([
                        'status' => 'success',
                        'message' => 'Image deleted successfully'
                    ]);
                    exit();
            }
        } catch (Exception $e) {
            // Roll back the transaction on error
            $conn->rollback();
            $errorMessage = $e->getMessage();
            debug_log("[ERROR] Exception occurred: " . $errorMessage, [
                'trace' => $e->getTraceAsString(),
                'project_id' => $project_id,
                'time' => date('Y-m-d H:i:s')
            ]);
            echo json_encode(['status' => 'error', 'message' => $errorMessage]);
        }
    }
} else {
    // Not a POST request
    debug_log("[ERROR] Invalid request method: " . $_SERVER['REQUEST_METHOD']);
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method']);
}