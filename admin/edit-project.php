<?php

/**
 * Edit Project (Refactored)
 * A more maintainable version of the edit project page
 */

// Add server-side logging function for enhanced logging
function log_server_action($action, $data = null)
{
    $log_message = "[" . date('Y-m-d H:i:s') . "] [EDIT-PROJECT] " . $action;
    if ($data !== null) {
        $log_message .= ": " . json_encode($data);
    }
    error_log($log_message);
}

// Include header and required controllers
include("includes/header.php");
include("controllers/unified_project_controller.php");


// Add a console logging function that works with both error_log and JavaScript
function console_log($message, $data = null)
{
    // Log to server error log
    if ($data !== null) {
        error_log($message . ": " . print_r($data, true));
    } else {
        error_log($message);
    }

    // This will output to PHP error log only
    // For browser console, we'll rely on the JavaScript logAction function
}

// Include database structure check
if (file_exists('includes/check_db_structure.php')) {
    include_once 'includes/check_db_structure.php';
}

// Get all graphic artists for dropdown
$graphicArtists = getGraphicArtists();

// Check if project ID is provided
if (!isset($_GET['id'])) {
    header("Location: project-list.php");
    exit();
}

$project_id = intval($_GET['id']);

// Log the project access
log_server_action("Project edit page accessed", array("project_id" => $project_id, "user" => $_SESSION['username'] ?? 'Unknown'));

// Fetch project data from database
$project = getProjectById($project_id);

if (!$project) {
    $_SESSION['error_message'] = "Project not found";
    log_server_action("Project not found", array("project_id" => $project_id));
    header("Location: project-list.php");
    exit();
}

// Get companies for dropdown
$companies = getCompaniesForDropdown();

// Get all project images
$images = getProjectImages($project_id);
log_server_action("Retrieved images", array("count" => count($images), "project_id" => $project_id));

// Get project assignments
$assignments = getProjectAssignments($project_id);
log_server_action("Retrieved assignments", array("count" => count($assignments), "project_id" => $project_id));

// Get project progress (for charts)
$projectProgress = getProjectProgressStats($project_id);

// Display the page
?>


<!-- Add JavaScript for AJAX saving -->
<script>
    // Function to log actions to console and server
    function logAction(action, data = null) {
        const timestamp = new Date().toISOString();
        const logMessage = `[${timestamp}] ${action}`;

        // Log to console
        if (data) {
            console.log(logMessage, data);
        } else {
            console.log(logMessage);
        }
    }

    // Function to show notification
    function showNotification(type, message) {
        const alertClass = type === 'success' ? 'alert-success' : 'alert-danger';
        const icon = type === 'success' ? 'fas fa-check-circle' : 'fas fa-exclamation-circle';

        const alertHtml = `
        <div class="alert ${alertClass} alert-dismissible fade show" role="alert" id="tempAlert">
            <i class="${icon} mr-2"></i> ${message}
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
    `;

        // Remove any existing alerts
        $('#tempAlert').remove();

        // Add the new alert at the top of the content
        $('.content-header').after(alertHtml);

        // Auto-dismiss after 3 seconds
        setTimeout(() => {
            $('#tempAlert').alert('close');
        }, 3000);
    }

    // Function to update project field via AJAX
    function updateProjectField(fieldName, value) {
        const projectId = <?php echo $project_id; ?>;

        logAction(`Updating project field: ${fieldName}`, {
            value
        });

        $.ajax({
            url: 'controllers/edit_project_ajax.php',
            type: 'POST',
            data: {
                action: 'update_project_field',
                project_id: projectId,
                field_name: fieldName,
                field_value: value
            },
            dataType: 'json',
            success: function (response) {
                logAction('Update response received', response);

                if (response.status === 'success') {
                    showNotification('success', `Project ${fieldName} updated successfully`);
                } else {
                    showNotification('error', `Error: ${response.message}`);
                }
            },
            error: function (xhr, status, error) {
                logAction('AJAX error', {
                    xhr,
                    status,
                    error
                });
                showNotification('error', 'An error occurred while updating the project');
            }
        });
    }

    // Function to update project status
    function updateProjectStatus(status) {
        const projectId = <?php echo $project_id; ?>;

        logAction(`Updating project status to: ${status}`);

        $.ajax({
            url: 'controllers/edit_project_ajax.php',
            type: 'POST',
            data: {
                action: 'update_project_status',
                project_id: projectId,
                status: status
            },
            dataType: 'json',
            success: function (response) {
                logAction('Status update response received', response);

                if (response.status === 'success') {
                    showNotification('success', 'Project status updated successfully');
                    // Update status badge in UI
                    updateStatusBadgeUI(status);
                } else {
                    showNotification('error', `Error: ${response.message}`);
                }
            },
            error: function (xhr, status, error) {
                logAction('AJAX error', {
                    xhr,
                    status,
                    error
                });
                showNotification('error', 'An error occurred while updating the project status');
            }
        });
    }

    // Function to update status badge in UI
    function updateStatusBadgeUI(status) {
        const statusBadge = $('#statusBadge');

        // Remove all existing classes
        statusBadge.removeClass('badge-primary badge-warning badge-success badge-danger badge-info');

        // Add appropriate class based on status
        switch (status) {
            case 'pending':
                statusBadge.addClass('badge-warning').text('Pending');
                break;
            case 'in_progress':
                statusBadge.addClass('badge-primary').text('In Progress');
                break;
            case 'review':
                statusBadge.addClass('badge-info').text('Review');
                break;
            case 'completed':
                statusBadge.addClass('badge-success').text('Completed');
                break;
            case 'delayed':
                statusBadge.addClass('badge-danger').text('Delayed');
                break;
        }
    }

    // Document ready function
    $(document).ready(function () {
        logAction('Edit project page loaded', {
            project_id: <?php echo $project_id; ?>
        });

        // Add event listeners for project details fields
        $('#projectName, #description, #priority, #dateArrived, #deadline').on('change', function () {
            const fieldName = $(this).attr('id');
            const value = $(this).val();
            updateProjectField(fieldName, value);
        });

        // Add event listener for company field (select2)
        $('#company').on('change', function () {
            const value = $(this).val();
            updateProjectField('company', value);
        });

        // Add event listeners for status buttons
        $('.status-btn').on('click', function () {
            const status = $(this).data('status');
            updateProjectStatus(status);
        });

        // Setup console logging
        const logging = {
            debug: function (message, data = null) {
                console.debug(`[DEBUG][${new Date().toISOString()}] ${message}`, data || '');
            },
            info: function (message, data = null) {
                console.info(`[INFO][${new Date().toISOString()}] ${message}`, data || '');
            },
            warning: function (message, data = null) {
                console.warn(`[WARNING][${new Date().toISOString()}] ${message}`, data || '');
            },
            error: function (message, data = null) {
                console.error(`[ERROR][${new Date().toISOString()}] ${message}`, data || '');
            },
            interaction: function (action, data = null) {
                console.log(`[USER ACTION][${new Date().toISOString()}] ${action}`, data || '');

                // Send interaction to server for logging if needed
                if (window.navigator.sendBeacon) {
                    try {
                        const logData = {
                            action: action,
                            data: data || {},
                            timestamp: new Date().toISOString(),
                            page: 'edit-project',
                            projectId: projectId
                        };
                        navigator.sendBeacon('controllers/log_client_action.php', JSON.stringify(logData));
                    } catch (e) {
                        console.error('Error sending beacon log:', e);
                    }
                }
            },
            ajax: function (method, url, data = null) {
                console.log(`[AJAX REQUEST][${new Date().toISOString()}] ${method} ${url}`, data || '');
            },
            ajaxSuccess: function (method, url, response = null) {
                console.log(`[AJAX SUCCESS][${new Date().toISOString()}] ${method} ${url}`, response || '');
            },
            ajaxError: function (method, url, error = null) {
                console.error(`[AJAX ERROR][${new Date().toISOString()}] ${method} ${url}`, error || '');
            }
        };
    });
</script>

<!-- Custom CSS for image selection -->
<style>
    .image-container {
        cursor: pointer;
        position: relative;
        transition: all 0.2s ease;
    }

    .image-container.selected .card {
        border: 2px solid #007bff;
        box-shadow: 0 0 10px rgba(0, 123, 255, 0.5);
    }

    /* Status Timeline Styles */
    .status-timeline {
        display: flex;
        align-items: center;
        position: relative;
    }

    .status-step {
        width: 24px;
        height: 24px;
        border-radius: 50%;
        background-color: #e9ecef;
        color: #6c757d;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: bold;
        cursor: pointer;
        position: relative;
        z-index: 2;
        transition: all 0.3s ease;
    }

    .status-step.active {
        background-color: #28a745;
        color: white;
    }

    .status-step.current {
        border: 2px solid #007bff;
    }

    .status-connector {
        height: 3px;
        flex-grow: 1;
        background-color: #e9ecef;
        margin: 0 2px;
        position: relative;
        z-index: 1;
    }

    .status-connector.active {
        background-color: #28a745;
    }

    .image-selection-indicator {
        position: absolute;
        top: 8px;
        right: 8px;
        width: 20px;
        height: 20px;
        border-radius: 50%;
        background-color: #fff;
        display: flex;
        align-items: center;
        justify-content: center;
        opacity: 0;
        transition: opacity 0.2s ease;
        z-index: 2;
    }

    .image-selection-indicator i {
        color: #28a745;
        font-size: 16px;
    }

    .image-container.selected .image-selection-indicator {
        opacity: 1;
    }

    .image-container .delete-image {
        opacity: 0.7;
        transition: opacity 0.2s ease;
    }

    .image-container:hover .delete-image,
    .image-container.selected .delete-image {
        opacity: 1;
    }

    /* New styles for improved image cards */
    .image-card-body {
        display: flex;
        flex-direction: column;
        padding: 6px;
    }

    .image-file-name {
        word-break: break-word;
        font-size: 0.75rem;
        line-height: 1.2;
        margin-bottom: 4px;
        flex-grow: 1;
        overflow-wrap: break-word;
    }

    .image-actions {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-top: auto;
    }

    .image-status {
        width: 100%;
        text-align: center;
        padding: 2px;
        font-size: 0.7rem;
    }

    .image-container .badge-primary {
        position: relative;
    }

    .image-container .badge-primary:after {
        content: '\f023';
        /* Lock icon */
        font-family: 'Font Awesome 5 Free';
        font-weight: 900;
        font-size: 0.7em;
        position: absolute;
        top: -3px;
        right: -3px;
        background-color: #fff;
        border-radius: 50%;
        width: 14px;
        height: 14px;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #007bff;
        border: 1px solid #007bff;
    }

    .image-container.already-assigned {
        position: relative;
        overflow: hidden;
    }

    .image-container.already-assigned:before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background-color: rgba(0, 123, 255, 0.05);
        z-index: 1;
        pointer-events: none;
    }

    .image-container.already-assigned:hover {
        cursor: not-allowed;
    }
</style>

<!-- Custom styles for the image cards -->
<style>
    /* Image Container Styles */
    .image-container {
        position: relative;
        transition: all 0.2s ease;
        cursor: pointer;
        border: 2px solid transparent;
    }

    .image-container:hover {
        box-shadow: 0 0.25rem 0.75rem rgba(0, 0, 0, 0.15) !important;
    }

    .image-container.selected {
        border-color: #007bff;
        background-color: rgba(0, 123, 255, 0.05);
    }

    /* Styling for already assigned images */
    .image-container.already-assigned {
        opacity: 0.7;
        position: relative;
        cursor: not-allowed;
        border: 2px dashed #d9534f;
        background-color: rgba(217, 83, 79, 0.05);
    }

    .image-container.already-assigned:hover {
        box-shadow: none !important;
        border: 2px dashed #d9534f;
    }

    .image-container.already-assigned::after {
        content: "Already Assigned";
        position: absolute;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
        background-color: rgba(217, 83, 79, 0.8);
        color: white;
        padding: 4px 8px;
        border-radius: 4px;
        font-size: 12px;
        font-weight: bold;
        pointer-events: none;
        opacity: 0;
        transition: opacity 0.2s ease;
    }

    .image-container.already-assigned:hover::after {
        opacity: 1;
    }

    /* Selection Indicator */
    .image-selection-indicator {
        position: absolute;
        top: 0;
        left: 0;
        width: 20px;
        height: 20px;
        background-color: #007bff;
        border-radius: 50%;
        color: white;
        display: none;
        align-items: center;
        justify-content: center;
        z-index: 2;
        transform: translate(-30%, -30%);
    }

    .image-container.selected .image-selection-indicator {
        display: flex;
    }

    /* Badge styling */
    .badge {
        font-size: 85%;
        font-weight: 500;
    }
</style>

<div class="wrapper">
    <?php include("includes/nav.php"); ?>

    <!-- Content Wrapper -->
    <div class="content-wrapper">
        <!-- Content Header -->
        <section class="content-header">
            <div class="container-fluid">
                <div class="row mb-2">
                    <div class="col-sm-6">
                        <h1>
                            <i class="fas fa-edit mr-2"></i>
                            Edit Project
                        </h1>

                    </div>
                    <div class="col-sm-6">
                        <ol class="breadcrumb float-sm-right">
                            <li class="breadcrumb-item"><a href="#">Home</a></li>
                            <li class="breadcrumb-item"><a href="project-list.php">Projects</a></li>
                            <li class="breadcrumb-item active">Edit Project</li>
                        </ol>
                    </div>
                </div>
            </div>
        </section>

        <!-- Display messages -->
        <?php if (isset($_SESSION['success_message'])): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?php echo $_SESSION['success_message']; ?>
                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <?php unset($_SESSION['success_message']); ?>
        <?php endif; ?>

        <?php if (isset($_SESSION['error_message'])): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?php echo $_SESSION['error_message']; ?>
                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <?php unset($_SESSION['error_message']); ?>
        <?php endif; ?>

        <!-- Main content -->
        <section class="content">
            <div class="container-fluid">

                <!-- Project Statistics Card -->
                <div class="row">
                    <div class="col-12">
                        <div class="card mb-4">
                            <div class="card-header bg-info text-white">
                                <h3 class="card-title">Project Statistics</h3>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-3 col-sm-6 col-12">
                                        <div class="info-box bg-info">
                                            <span class="info-box-icon"><i class="fas fa-images"></i></span>
                                            <div class="info-box-content">
                                                <span class="info-box-text">Total Images</span>
                                                <span
                                                    class="info-box-number"><?php echo $projectProgress['total']; ?></span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-3 col-sm-6 col-12">
                                        <div class="info-box bg-success">
                                            <span class="info-box-icon"><i class="fas fa-check-circle"></i></span>
                                            <div class="info-box-content">
                                                <span class="info-box-text">Completed</span>
                                                <span
                                                    class="info-box-number"><?php echo $projectProgress['completed']; ?></span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-3 col-sm-6 col-12">
                                        <div class="info-box bg-warning">
                                            <span class="info-box-icon"><i class="fas fa-user-check"></i></span>
                                            <div class="info-box-content">
                                                <span class="info-box-text">Assigned</span>
                                                <span
                                                    class="info-box-number"><?php echo $projectProgress['assigned']; ?></span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-3 col-sm-6 col-12">
                                        <div class="info-box bg-danger">
                                            <span class="info-box-icon"><i class="fas fa-user-times"></i></span>
                                            <div class="info-box-content">
                                                <span class="info-box-text">Unassigned</span>
                                                <span
                                                    class="info-box-number"><?php echo $projectProgress['unassigned']; ?></span>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                            </div>
                        </div>
                    </div>
                </div>

                <form id="editProjectForm" method="post" enctype="multipart/form-data">
                    <!-- Hidden field for project ID -->
                    <input type="hidden" name="project_id" value="<?php echo $project_id; ?>">

                    <!-- Project Overview Section -->
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header bg-primary text-white">
                                    <h3 class="card-title">Project Details</h3>
                                </div>
                                <div class="card-body">
                                    <div class="form-group">
                                        <label for="projectName">Project Name</label>
                                        <input type="text" class="form-control" id="projectName" name="projectName"
                                            value="<?php echo htmlspecialchars($project['project_title']); ?>"
                                            onchange="updateProjectField('projectName', this.value)">
                                    </div>
                                    <div class="form-group">
                                        <label for="company">Company</label>
                                        <select class="form-control" id="company" name="company" required
                                            onchange="updateProjectField('company', this.value)">
                                            <option value="">-- Select Company --</option>
                                            <?php foreach ($companies as $company): ?>
                                                <option value="<?php echo htmlspecialchars($company['company_name']); ?>"
                                                    <?php echo ($project['company_name'] == $company['company_name']) ? 'selected' : ''; ?>>
                                                    <?php echo htmlspecialchars($company['company_name']); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="form-group">
                                        <label for="description">Description</label>
                                        <textarea class="form-control" id="description" name="description" rows="4"
                                            onchange="updateProjectField('description', this.value)"><?php echo htmlspecialchars($project['description']); ?></textarea>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header bg-primary text-white">
                                    <h3 class="card-title">Status & Timeline</h3>
                                </div>
                                <div class="card-body">
                                    <div class="form-group">
                                        <label for="status_project">Project Status</label>
                                        <input type="text" class="form-control" id="status_project"
                                            name="status_project"
                                            value="<?php echo htmlspecialchars($project['status_project']); ?>"
                                            readonly>
                                    </div>
                                    <div class="form-group">
                                        <label for="priority">Priority</label>
                                        <select class="form-control" id="priority" name="priority" required
                                            onchange="updateProjectField('priority', this.value)">
                                            <option value="High" <?php echo ($project['priority'] == 'High') ? 'selected' : ''; ?>>High</option>
                                            <option value="Medium" <?php echo ($project['priority'] == 'Medium') ? 'selected' : ''; ?>>Medium</option>
                                            <option value="Low" <?php echo ($project['priority'] == 'Low') ? 'selected' : ''; ?>>Low</option>
                                        </select>
                                    </div>
                                    <div class="form-group">
                                        <label for="dateArrived">Start Date</label>
                                        <input type="date" class="form-control" id="dateArrived" name="dateArrived"
                                            value="<?php echo $project['date_arrived']; ?>" required
                                            onchange="updateProjectField('dateArrived', this.value)">
                                    </div>
                                    <div class="form-group">
                                        <label for="deadline">Deadline</label>
                                        <input type="date" class="form-control" id="deadline" name="deadline"
                                            value="<?php echo $project['deadline']; ?>" required
                                            onchange="updateProjectField('deadline', this.value)">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Project Images Section -->
                    <div class="row mt-4">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-header d-flex justify-content-between align-items-center">
                                    <h5 class="mb-0">Project Images (Total:
                                        <?php echo count($images); ?>)
                                    </h5>
                                    <div>
                                        <button type="button" class="btn btn-sm btn-outline-danger mr-2"
                                            id="removeAllImages">
                                            <i class="fas fa-trash-alt"></i> Remove All
                                        </button>
                                        <button type="button" class="btn btn-sm btn-outline-primary" data-toggle="modal"
                                            data-target="#addImagesModal">
                                            <i class="fas fa-plus"></i> Add Images
                                        </button>
                                    </div>
                                </div>
                                <div class="card-body">
                                    <!-- Batch Actions (initially hidden) -->
                                    <div class="row mb-3" id="batchActions">
                                        <div class="col-12">
                                            <button type="button" class="btn btn-primary" id="assignSelected"
                                                data-toggle="modal" data-target="#addAssignmentModal">
                                                <i class="fas fa-user-plus mr-1"></i> Assign Selected
                                            </button>
                                            <button type="button" class="btn btn-danger" id="deleteSelected">
                                                <i class="fas fa-trash-alt mr-1"></i> Delete Selected
                                            </button>
                                            <span class="ml-3 text-muted" id="selectedCount">0 images selected</span>
                                        </div>
                                    </div>

                                    <!-- Images Grid -->
                                    <div class="row" id="imageGallery">
                                        <?php if (empty($images)): ?>
                                            <div class="col-12 text-center py-5">
                                                <p class="text-muted">No images uploaded yet.</p>
                                            </div>
                                        <?php else: ?>
                                            <?php foreach ($images as $image): ?>
                                                <?php
                                                // Determine status for UI display
                                                $statusClass = 'badge-success';
                                                $statusText = 'Available';

                                                if (isset($image['assignment_id']) && $image['assignment_id']) {
                                                    $statusClass = 'badge-primary';
                                                    // Show assignee's first name if assigned
                                                    $statusText = isset($image['assignee_first_name']) ? $image['assignee_first_name'] : 'Assigned';
                                                }

                                                if (isset($image['status_image']) && $image['status_image'] === 'completed') {
                                                    $statusClass = 'badge-success';
                                                    $statusText = 'Completed';
                                                }

                                                // Get file name for display
                                                $fileName = pathinfo($image['image_path'], PATHINFO_BASENAME);

                                                // Format estimated time
                                                $estimatedTimeDisplay = '';
                                                if (isset($image['estimated_time']) && !empty($image['estimated_time'])) {
                                                    $hours = floor($image['estimated_time'] / 60);
                                                    $minutes = $image['estimated_time'] % 60;

                                                    if ($hours > 0) {
                                                        $estimatedTimeDisplay .= $hours . 'h ';
                                                    }
                                                    if ($minutes > 0 || $hours == 0) {
                                                        $estimatedTimeDisplay .= $minutes . 'm';
                                                    }
                                                }
                                                ?>
                                                <div class="col-md-6 col-lg-3 mb-2">
                                                    <div class="image-container card shadow-sm <?php echo (isset($image['assignment_id']) && $image['assignment_id']) ? 'already-assigned' : ''; ?>"
                                                        data-image-id="<?php echo $image['image_id']; ?>">
                                                        <div class="card-body p-2">
                                                            <div class="d-flex align-items-center">
                                                                <!-- Small thumbnail -->
                                                                <div class="image-selection-indicator">
                                                                    <i class="fas fa-check-circle"></i>
                                                                </div>

                                                                <!-- Image details -->
                                                                <div class="flex-grow-1">
                                                                    <h6 class="mb-0 text-truncate"
                                                                        title="<?php echo $fileName; ?>">
                                                                        <?php echo $fileName; ?>
                                                                    </h6>
                                                                    <div class="d-flex flex-wrap mt-1">
                                                                        <!-- Assignee badge -->
                                                                        <span
                                                                            class="badge <?php echo $statusClass; ?> mr-1 mb-1">
                                                                            <i class="fas fa-user mr-1"></i>
                                                                            <?php echo $statusText; ?>
                                                                        </span>

                                                                        <!-- Role badge -always show, even if empty -->
                                                                        <span class="badge badge-info mr-1 mb-1">
                                                                            <i class="fas fa-tasks mr-1"></i>
                                                                            <?php echo !empty($image['image_role']) ? htmlspecialchars($image['image_role']) : 'Not Set'; ?>
                                                                        </span>

                                                                        <!-- Estimated time badge - always show, even if empty -->
                                                                        <span class="badge badge-secondary mb-1">
                                                                            <i class="far fa-clock mr-1"></i>
                                                                            <?php echo !empty($estimatedTimeDisplay) ? $estimatedTimeDisplay : 'No Time Set'; ?>
                                                                        </span>
                                                                    </div>
                                                                </div>

                                                                <!-- Delete button -->
                                                                <div class="ml-auto">
                                                                    <button class="btn btn-sm btn-danger delete-image"
                                                                        data-image-id="<?php echo $image['image_id']; ?>">
                                                                        <i class="fas fa-trash"></i>
                                                                    </button>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- TEAM ASSIGNMENTS Section -->
                    <div class="row mt-4">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-header d-flex justify-content-between align-items-center">
                                    <h5 class="mb-0">Team Assignments</h5>

                                </div>
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table class="table table-hover">
                                            <thead>
                                                <tr>
                                                    <th>Team Member</th>
                                                    <th>Role</th>
                                                    <th>Assigned Images</th>
                                                    <th>Status</th>
                                                    <th>Deadline</th>
                                                    <th>Actions</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php if (count($assignments) > 0): ?>
                                                    <?php foreach ($assignments as $assignment): ?>
                                                        <tr data-assignment-id="<?php echo $assignment['assignment_id']; ?>">
                                                            <td>
                                                                <div class="team-member-col">
                                                                    <select class="form-control team-member-select" <?php echo $assignment['status_assignee'] != 'pending' ? 'disabled' : ''; ?>
                                                                        data-assignment-id="<?php echo $assignment['assignment_id']; ?>">
                                                                        <option value="">Select Team Member</option>
                                                                        <?php foreach ($graphicArtists as $artist): ?>
                                                                            <option value="<?php echo $artist['user_id']; ?>" <?php echo ($artist['user_id'] == $assignment['user_id']) ? 'selected' : ''; ?>>
                                                                                <?php echo htmlspecialchars($artist['full_name'] . ' (' . $artist['role'] . ')'); ?>
                                                                                <?php echo ($artist['status'] == 'Blocked') ? ' [Blocked]' : ''; ?>
                                                                            </option>
                                                                        <?php endforeach; ?>
                                                                    </select>
                                                                    <?php if ($assignment['status_assignee'] != 'pending'): ?>
                                                                        <div class="mt-2">
                                                                            <span class="badge badge-info">
                                                                                <i class="fas fa-lock mr-1"></i> Locked: Status is
                                                                                "<?php echo ucfirst($assignment['status_assignee']); ?>"
                                                                            </span>
                                                                        </div>
                                                                    <?php endif; ?>
                                                                </div>
                                                            </td>
                                                            <td>
                                                                <select class="form-control role-select"
                                                                    data-assignment-id="<?php echo $assignment['assignment_id']; ?>"
                                                                    <?php echo $assignment['status_assignee'] != 'pending' ? 'disabled' : ''; ?>>
                                                                    <option value="">Select Role</option>
                                                                    <option value="Clipping Path" <?php echo ($assignment['role_task'] == 'Clipping Path') ? 'selected' : ''; ?>>Clipping Path</option>
                                                                    <option value="Color Correction" <?php echo ($assignment['role_task'] == 'Color Correction') ? 'selected' : ''; ?>>Color Correction</option>
                                                                    <option value="Retouch" <?php echo ($assignment['role_task'] == 'Retouch') ? 'selected' : ''; ?>>Retouch</option>
                                                                    <option value="Final" <?php echo ($assignment['role_task'] == 'Final') ? 'selected' : ''; ?>>Final</option>
                                                                </select>
                                                                <?php if ($assignment['status_assignee'] != 'pending'): ?>
                                                                    <div class="mt-1">
                                                                        <span class="badge badge-info">
                                                                            <i class="fas fa-lock mr-1"></i> Locked
                                                                        </span>
                                                                    </div>
                                                                <?php endif; ?>
                                                            </td>
                                                            <td>
                                                                <?php echo $assignment['assigned_images']; ?> Images
                                                                <div class="btn-group ml-2">
                                                                    <button type="button"
                                                                        class="btn btn-sm btn-outline-primary view-assigned-images"
                                                                        data-assignment-id="<?php echo $assignment['assignment_id']; ?>"
                                                                        title="View Assigned Images">
                                                                        <i class="fas fa-eye"></i>
                                                                    </button>
                                                                    <button type="button"
                                                                        class="btn btn-sm btn-outline-success add-more-images"
                                                                        data-assignment-id="<?php echo $assignment['assignment_id']; ?>"
                                                                        title="Add More Images" <?php echo $assignment['status_assignee'] != 'pending' ? 'disabled' : ''; ?>>
                                                                        <i class="fas fa-plus"></i>
                                                                    </button>
                                                                </div>
                                                                <?php if ($assignment['status_assignee'] != 'pending'): ?>
                                                                    <div class="mt-1">
                                                                        <small class="text-muted">
                                                                            <i class="fas fa-lock mr-1"></i> Some actions locked
                                                                        </small>
                                                                    </div>
                                                                <?php endif; ?>
                                                            </td>
                                                            <td>
                                                                <?php
                                                                // Define the timeline steps
                                                                $timelineSteps = ['pending', 'in_progress', 'finish', 'qa', 'approved', 'completed'];
                                                                $currentStatus = $assignment['status_assignee'];

                                                                // Determine the current step index
                                                                $currentStepIndex = array_search($currentStatus, $timelineSteps);
                                                                if ($currentStepIndex === false)
                                                                    $currentStepIndex = 0;

                                                                // Display the timeline
                                                                ?>
                                                                <div class="status-timeline d-flex align-items-center"
                                                                    style="font-size: 0.8rem;">
                                                                    <?php foreach ($timelineSteps as $index => $step):
                                                                        $isActive = $index <= $currentStepIndex;
                                                                        $isCurrent = $index == $currentStepIndex;

                                                                        // Determine classes
                                                                        $stepClass = 'status-step';
                                                                        if ($isActive)
                                                                            $stepClass .= ' active';
                                                                        if ($isCurrent)
                                                                            $stepClass .= ' current';

                                                                        // Display connector line for steps after the first
                                                                        if ($index > 0): ?>
                                                                            <div
                                                                                class="status-connector <?php echo $index <= $currentStepIndex ? 'active' : ''; ?>">
                                                                            </div>
                                                                        <?php endif; ?>

                                                                        <div class="<?php echo $stepClass; ?>"
                                                                            data-assignment-id="<?php echo $assignment['assignment_id']; ?>"
                                                                            data-status="<?php echo $step; ?>"
                                                                            title="<?php echo ucfirst(str_replace('_', ' ', $step)); ?>">
                                                                            <?php echo substr(ucfirst($step), 0, 1); ?>
                                                                        </div>
                                                                    <?php endforeach; ?>
                                                                </div>

                                                                <?php if ($currentStatus === 'finish'): ?>
                                                                    <button type="button"
                                                                        class="btn btn-sm btn-success mt-2 approve-task-btn"
                                                                        data-assignment-id="<?php echo $assignment['assignment_id']; ?>">
                                                                        <i class="fas fa-check-circle"></i> Approve
                                                                    </button>
                                                                <?php endif; ?>

                                                                <input type="hidden" class="current-status"
                                                                    value="<?php echo $assignment['status_assignee']; ?>"
                                                                    data-assignment-id="<?php echo $assignment['assignment_id']; ?>">
                                                            </td>
                                                            <td>
                                                                <?php
                                                                $deadline_date = new DateTime($assignment['deadline']);
                                                                $today = new DateTime('today');
                                                                $deadline_status = '';
                                                                $badge_class = '';

                                                                if ($deadline_date == $today) {
                                                                    $deadline_status = 'Today';
                                                                    $badge_class = 'badge-warning';
                                                                } else if ($deadline_date < $today) {
                                                                    // Calculate days overdue
                                                                    $interval = $today->diff($deadline_date);
                                                                    $days_overdue = $interval->days;
                                                                    $deadline_status = 'Overdue by ' . $days_overdue . ($days_overdue > 1 ? ' days' : ' day');
                                                                    $badge_class = 'badge-danger';
                                                                } else {
                                                                    // Calculate days remaining
                                                                    $interval = $today->diff($deadline_date);
                                                                    $days_left = $interval->days;
                                                                    if ($days_left == 1) {
                                                                        $deadline_status = 'Due tomorrow';
                                                                        $badge_class = 'badge-warning';
                                                                    } else if ($days_left <= 3) {
                                                                        $deadline_status = $days_left . ' days left';
                                                                        $badge_class = 'badge-warning';
                                                                    } else {
                                                                        $deadline_status = $days_left . ' days left';
                                                                        $badge_class = 'badge-info';
                                                                    }
                                                                }

                                                                // Check if assignment is marked as understandable (will use this in the next feature)
                                                                $isUnderstandable = isset($assignment['delay_acceptable']) && $assignment['delay_acceptable'] == 1;
                                                                if ($isUnderstandable && $badge_class == 'badge-danger') {
                                                                    $badge_class = 'badge-success';
                                                                }
                                                                ?>
                                                                <div class="deadline-container">
                                                                    <input type="date" class="form-control deadline-input"
                                                                        value="<?php echo $assignment['deadline']; ?>"
                                                                        data-assignment-id="<?php echo $assignment['assignment_id']; ?>"
                                                                        <?php echo $assignment['status_assignee'] != 'pending' ? 'disabled' : ''; ?>>
                                                                    <div class="d-flex mt-1">

                                                                        <!-- Status badges -->
                                                                        <div class="d-flex flex-wrap align-items-center mt-2">
                                                                            <?php if (!empty($deadline_status)): ?>
                                                                                <span
                                                                                    class="badge <?php echo $badge_class; ?> mr-2 mb-1">
                                                                                    <?php echo $deadline_status; ?>
                                                                                </span>
                                                                            <?php endif; ?>
                                                                            <?php if ($assignment['status_assignee'] != 'pending'): ?>
                                                                                <span class="badge badge-info mr-2 mb-1">
                                                                                    <i class="fas fa-lock mr-1"></i> Locked
                                                                                </span>
                                                                            <?php endif; ?>
                                                                        </div>

                                                                        <!-- Mark as Acceptable button in its own row if needed -->
                                                                        <?php if ($badge_class == 'badge-danger' && !$isUnderstandable): ?>
                                                                            <div class="mt-0">
                                                                                <button type="button"
                                                                                    class="btn btn-sm btn-outline-success mark-acceptable-btn p-1"
                                                                                    data-assignment-id="<?php echo $assignment['assignment_id']; ?>"
                                                                                    data-toggle="tooltip"
                                                                                    title="Mark this delay as understandable">
                                                                                    <i class="fas fa-check-circle mr-1"></i> Mark as
                                                                                    Acceptable
                                                                                </button>
                                                                            </div>
                                                                        <?php endif; ?>
                                                                    </div>
                                                                </div>
                                                            </td>
                                                            <td>
                                                                <button class="btn btn-sm btn-danger delete-assignment"
                                                                    data-assignment-id="<?php echo $assignment['assignment_id']; ?>">
                                                                    <i class="fas fa-trash"></i>
                                                                </button>

                                                            </td>
                                                        </tr>
                                                    <?php endforeach; ?>
                                                <?php else: ?>
                                                    <tr>
                                                        <td colspan="6" class="text-center">No team members assigned yet.
                                                        </td>
                                                    </tr>
                                                <?php endif; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Submit Button -->
                    <div class="row mb-4">
                        <div class="col-12">
                            <a href="project-list.php" class="btn btn-secondary btn-lg">
                                <i class="fas fa-arrow-left mr-2"></i> Back to List
                            </a>
                        </div>
                    </div>
                </form>
            </div>
        </section>
    </div>

    <?php include("includes/footer.php"); ?>
</div>

<!-- Add toastr for notifications -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css">
<script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>

<style>
    /* Custom styles for deadline and mark as acceptable button */
    .deadline-container {
        position: relative;
    }

    .mark-acceptable-btn {
        transition: all 0.2s ease;
        border-width: 2px;
    }

    .mark-acceptable-btn:hover {
        background-color: #28a745;
        color: white;
        box-shadow: 0 2px 5px rgba(0, 0, 0, 0.2);
    }
</style>

<!-- Add Images Modal -->
<div class="modal fade" id="addImagesModal" tabindex="-1" role="dialog" aria-labelledby="addImagesModalLabel"
    aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="addImagesModalLabel">Add New Images</h5>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form id="uploadImagesForm" enctype="multipart/form-data">
                    <div class="form-group">
                        <label for="projectImages">Select Images</label>
                        <input type="file" class="form-control-file" id="projectImages" name="projectImages[]" multiple>
                        <small class="form-text text-muted">You can select multiple images at once.</small>
                    </div>
                    <div id="imagePreviewContainer" class="row mt-3"></div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="saveImages">Upload Images</button>
            </div>
        </div>
    </div>
</div>

<!-- Add Assignment Modal -->
<div class="modal fade" id="addAssignmentModal" tabindex="-1" role="dialog" aria-labelledby="addAssignmentModalLabel"
    aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="addAssignmentModalLabel">Add New Assignment</h5>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form id="addAssignmentForm">
                    <div class="form-group">
                        <label for="assigneeSelect">Assignee</label>
                        <select class="form-control" id="assigneeSelect" name="assignee" required>
                            <option value="">-- Select Assignee --</option>
                            <?php foreach ($graphicArtists as $artist): ?>
                                <option value="<?php echo $artist['user_id']; ?>">
                                    <?php echo htmlspecialchars($artist['full_name'] . ' (' . $artist['role'] . ')'); ?>
                                    <?php echo ($artist['status'] == 'Blocked') ? ' [Blocked]' : ''; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="roleSelect">Role/Task</label>
                        <select class="form-control" id="roleSelect" name="role" required>
                            <option value="">-- Select Role --</option>
                            <option value="Clipping Path">Clipping Path</option>
                            <option value="Color Correction">Color Correction</option>
                            <option value="Retouch">Retouch</option>
                            <option value="Final">Final</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="assignmentDeadline">Deadline</label>
                        <input type="date" class="form-control" id="assignmentDeadline" name="deadline"
                            value="<?php echo date('Y-m-d', strtotime('+7 days')); ?>" required>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="saveAssignment">Save Assignment</button>
            </div>
        </div>
    </div>
</div>

<!-- JavaScript for AJAX interactions -->
<script>


    // Project ID from PHP
    const projectId = <?php echo $project_id; ?>;
    console.log("Project ID is:", projectId);

    // Set up logging functions
    const logging = {
        debug: function (message, data = null) {
            console.debug(`[DEBUG][${new Date().toISOString()}] ${message}`, data || '');
        },
        info: function (message, data = null) {
            console.info(`[INFO][${new Date().toISOString()}] ${message}`, data || '');
        },
        warning: function (message, data = null) {
            console.warn(`[WARNING][${new Date().toISOString()}] ${message}`, data || '');
        },
        error: function (message, data = null) {
            console.error(`[ERROR][${new Date().toISOString()}] ${message}`, data || '');
        },
        interaction: function (action, data = null) {
            console.log(`[USER ACTION][${new Date().toISOString()}] ${action}`, data || '');

            // Send interaction to server for logging if needed
            if (window.navigator.sendBeacon) {
                try {
                    const logData = {
                        action: action,
                        data: data || {},
                        timestamp: new Date().toISOString(),
                        page: 'edit-project',
                        projectId: projectId
                    };
                    navigator.sendBeacon('controllers/log_client_action.php', JSON.stringify(logData));
                } catch (e) {
                    console.error('Error sending beacon log:', e);
                }
            }
        },
        ajax: function (method, url, data = null) {
            console.log(`[AJAX REQUEST][${new Date().toISOString()}] ${method} ${url}`, data || '');
        },
        ajaxSuccess: function (method, url, response = null) {
            console.log(`[AJAX SUCCESS][${new Date().toISOString()}] ${method} ${url}`, response || '');
        },
        ajaxError: function (method, url, error = null) {
            console.error(`[AJAX ERROR][${new Date().toISOString()}] ${method} ${url}`, error || '');
        }
    };

    // Function to get all selected image IDs
    function getSelectedImageIds() {
        const selectedImages = [];
        $('.image-container.selected').each(function () {
            selectedImages.push($(this).data('image-id'));
        });
        return selectedImages;
    }

    // Function to delete images (works for both single and batch)
    function deleteImages(imageIds) {
        if (!imageIds || imageIds.length === 0) {
            logging.error('No image IDs provided for deletion');
            return;
        }

        logging.debug('Deleting images', imageIds);

        // AJAX call to delete images
        $.ajax({
            url: 'controllers/edit_project_ajax.php',
            type: 'POST',
            data: {
                action: 'delete_images',
                project_id: projectId,
                image_ids: JSON.stringify(imageIds)
            },
            success: function (response) {
                try {
                    const data = JSON.parse(response);
                    if (data.status === 'success') {
                        logging.info('Images deleted successfully', data);
                        // Reload the page to show updated image list
                        location.reload();
                    } else {
                        logging.error('Failed to delete images', data.message);
                        alert('Error: ' + data.message);
                    }
                } catch (e) {
                    logging.error('Error parsing JSON response', {
                        error: e,
                        response
                    });
                    alert("An error occurred while processing the response.");
                }
            },
            error: function (xhr, status, error) {
                logging.error('AJAX Error', {
                    status,
                    error
                });
                alert('An error occurred while deleting images: ' + error);
            }
        });
    }

    // Image selection via container click
    $(document).on('click', '.image-container', function (e) {
        // Don't trigger selection if clicking on delete button
        if ($(e.target).closest('.delete-image').length) {
            return;
        }

        // Check if image is already assigned to someone
        const statusBadge = $(this).find('.badge:first');
        if (statusBadge.hasClass('badge-primary')) {
            // Get the assignee name
            const assigneeName = statusBadge.text().trim();
            // Show a tooltip or message that the image is already assigned
            Swal.fire({
                title: 'Image Already Assigned',
                html: `This image is already assigned to <strong>${assigneeName}</strong>.<br>To reassign, you must first unassign it from the current assignee.`,
                icon: 'warning',
                confirmButtonText: 'OK'
            });
            return; // Exit the function without selecting
        }

        $(this).toggleClass('selected');
        updateBatchActions();

        // Log the selection
        const imageId = $(this).data('image-id');
        const isSelected = $(this).hasClass('selected');
        logging.interaction('Image selection changed', {
            imageId: imageId,
            selected: isSelected
        });
    });

    $(document).ready(function () {
        // Log page load
        logging.info(`Edit project page loaded for project ID: ${projectId}`);

        // Add AJAX setup for proper headers
        $.ajaxSetup({
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        });

        // Function to update batch actions visibility and selected count
        function updateBatchActions() {
            const selectedCount = $('.image-container.selected').length;
            $('#selectedCount').text(`${selectedCount} image${selectedCount !== 1 ? 's' : ''} selected`);

            if (selectedCount > 0) {
                $('#batchActions').fadeIn(200);
            } else {
                $('#batchActions').fadeOut(200);
            }
        }

        // Handle image deletion (single)
        $(document).on('click', '.delete-image', function (e) {
            e.stopPropagation(); // Prevent container selection
            const imageId = $(this).data('image-id');

            if (confirm('Are you sure you want to delete this image? This cannot be undone.')) {
                logging.interaction('Single image delete requested', {
                    imageId
                });
                deleteImages([imageId]);
            }
        });

        // Handle batch deletion
        $('#deleteSelected').click(function () {
            const selectedImages = getSelectedImageIds();

            if (selectedImages.length === 0) {
                alert('Please select at least one image to delete.');
                return;
            }

            if (confirm(`Are you sure you want to delete ${selectedImages.length} image(s)? This cannot be undone.`)) {
                logging.interaction('Batch image delete requested', {
                    count: selectedImages.length
                });
                deleteImages(selectedImages);
            }
        });

        // Handle image assignment
        $('.assign-to').click(function (e) {
            e.preventDefault();

            const assignmentId = $(this).data('assignment-id');
            const selectedImages = getSelectedImageIds();

            if (selectedImages.length === 0) {
                alert('Please select at least one image to assign.');
                return;
            }

            logging.interaction('Assigning images', {
                assignmentId,
                imageCount: selectedImages.length
            });

            // AJAX call to assign images
            $.ajax({
                url: 'controllers/edit_project_ajax.php',
                type: 'POST',
                data: {
                    action: 'assign_images',
                    project_id: projectId,
                    assignment_id: assignmentId,
                    image_ids: JSON.stringify(selectedImages)
                },
                success: function (response) {
                    try {
                        const data = JSON.parse(response);
                        if (data.status === 'success') {
                            logging.info('Images assigned successfully', data);

                            // Reload the page to show updated assignments
                            location.reload();
                        } else {
                            logging.error('Failed to assign images', data.message);
                            alert('Error: ' + data.message);
                        }
                    } catch (e) {
                        logging.error('Error parsing JSON response', {
                            error: e,
                            response
                        });
                        alert("An error occurred while processing the response.");
                    }
                },
                error: function (xhr, status, error) {
                    logging.error('AJAX Error', {
                        status,
                        error
                    });
                    alert('An error occurred while assigning images: ' + error);
                }
            });
        });

        // Handle unassign selected images
        $('#unassignSelected').click(function () {
            const selectedImages = getSelectedImageIds();

            if (selectedImages.length === 0) {
                alert('Please select at least one image to unassign.');
                return;
            }

            logging.interaction('Unassigning images', {
                count: selectedImages.length
            });

            // AJAX call to unassign images
            $.ajax({
                url: 'controllers/edit_project_ajax.php',
                type: 'POST',
                data: {
                    action: 'unassign_images',
                    project_id: projectId,
                    image_ids: JSON.stringify(selectedImages)
                },
                success: function (response) {
                    try {
                        const data = JSON.parse(response);
                        if (data.status === 'success') {
                            logging.info('Images unassigned successfully', data);
                            // Reload the page to show updated assignments
                            location.reload();
                        } else {
                            logging.error('Failed to unassign images', data.message);
                            alert('Error: ' + data.message);
                        }
                    } catch (e) {
                        logging.error('Error parsing JSON response', {
                            error: e,
                            response
                        });
                        alert("An error occurred while processing the response.");
                    }
                },
                error: function (xhr, status, error) {
                    logging.error('AJAX Error', {
                        status,
                        error
                    });
                    alert('An error occurred while unassigning images: ' + error);
                }
            });
        });

        // Handle assignment status change for assignment-status-select class
        $('.assignment-status-select').change(function () {
            const assignmentId = $(this).data('assignment-id');
            const newStatus = $(this).val();
            const selectElement = $(this);

            logging.interaction('Status change (assignment-status-select)', {
                assignmentId,
                newStatus
            });

            // AJAX call to update assignment status
            $.ajax({
                url: 'controllers/edit_project_ajax.php',
                type: 'POST',
                data: {
                    action: 'update_assignment_status',
                    assignment_id: assignmentId,
                    status: newStatus
                },
                success: function (response) {
                    try {
                        const data = JSON.parse(response);
                        if (data.status === 'success') {
                            logging.info('Status updated successfully');

                            // Update the option text for in_progress to show assignee's first name
                            if (newStatus === 'in_progress') {
                                const inProgressOption = selectElement.find('option[value="in_progress"]');
                                inProgressOption.text(data.assignee_first_name || 'In Progress');
                            }

                            // Show success notification
                            alert('Status updated successfully');
                        } else {
                            logging.error('Failed to update status', data.message);
                            alert('Error: ' + data.message);
                        }
                    } catch (e) {
                        logging.error('Error parsing JSON response', {
                            error: e,
                            response
                        });
                        alert("An error occurred while processing the response.");
                    }
                },
                error: function (xhr, status, error) {
                    logging.error('AJAX Error', {
                        status,
                        error
                    });
                    alert('An error occurred while updating status: ' + error);
                }
            });
        });

        // Handle delete assignment
        $('.delete-assignment').click(function () {
            const assignmentId = $(this).data('assignment-id');

            if (!confirm('Are you sure you want to delete this assignment? This will unassign all associated images.')) {
                return;
            }

            logging.interaction('Deleting assignment', {
                assignmentId
            });

            // AJAX call to delete assignment
            $.ajax({
                url: 'controllers/edit_project_ajax.php',
                type: 'POST',
                data: {
                    action: 'delete_assignment',
                    assignment_id: assignmentId,
                    project_id: projectId
                },
                success: function (response) {
                    try {
                        const data = JSON.parse(response);
                        if (data.status === 'success') {
                            logging.info('Assignment deleted successfully');
                            // Reload to refresh assignments and images
                            location.reload();
                        } else {
                            logging.error('Failed to delete assignment', data.message);
                            alert('Error: ' + data.message);
                        }
                    } catch (e) {
                        logging.error('Error parsing JSON response', {
                            error: e,
                            response
                        });
                        alert("An error occurred while processing the response.");
                    }
                },
                error: function (xhr, status, error) {
                    logging.error('AJAX Error', {
                        status,
                        error
                    });
                    alert('An error occurred while deleting the assignment: ' + error);
                }
            });
        });

        // Save new assignment
        $('#saveAssignment').click(function () {
            const userId = $('#assigneeSelect').val();
            const roleTask = $('#roleSelect').val();
            const deadline = $('#assignmentDeadline').val();

            if (!userId || !roleTask || !deadline) {
                alert('Please fill in all required fields.');
                return;
            }

            // Check if there are selected images from the "Assign Selected" button
            const selectedImages = getSelectedImageIds();
            const isAssigningSelectedImages = $('#assignSelected').data('clicked') === true;

            logging.interaction('Saving new assignment', {
                userId,
                roleTask,
                deadline,
                hasSelectedImages: selectedImages.length > 0,
                isAssigningFromButton: isAssigningSelectedImages
            });

            // AJAX call to save new assignment
            $.ajax({
                url: 'controllers/edit_project_ajax.php',
                type: 'POST',
                data: {
                    action: 'save_assignment',
                    project_id: projectId,
                    user_id: userId,
                    role_task: roleTask,
                    deadline: deadline
                },
                beforeSend: function () {
                    console.log('Sending save_assignment request with data:', {
                        action: 'save_assignment',
                        project_id: projectId,
                        user_id: userId,
                        role_task: roleTask,
                        deadline: deadline
                    });
                },
                success: function (response) {
                    console.log('Raw assignment save response:', response);
                    try {
                        const data = JSON.parse(response);
                        console.log('Parsed assignment response:', data);
                        if (data.status === 'success') {
                            logging.info('Assignment saved successfully');

                            // If there are selected images and the assignment was initiated from the "Assign Selected" button,
                            // assign them to the new assignment
                            if (isAssigningSelectedImages && selectedImages.length > 0) {
                                logging.info('Assigning selected images to new assignment', {
                                    assignmentId: data.assignment_id,
                                    imageCount: selectedImages.length
                                });

                                // Make a second AJAX call to assign images
                                $.ajax({
                                    url: 'controllers/edit_project_ajax.php',
                                    type: 'POST',
                                    data: {
                                        action: 'assign_images',
                                        project_id: projectId,
                                        assignment_id: data.assignment_id,
                                        image_ids: JSON.stringify(selectedImages)
                                    },
                                    success: function (assignResponse) {
                                        try {
                                            const assignData = JSON.parse(assignResponse);
                                            console.log('Assign images response:', assignData);
                                            if (assignData.status === 'success') {
                                                logging.info('Images assigned to new assignment successfully');
                                            } else {
                                                logging.error('Failed to assign images to new assignment', assignData.message);
                                            }
                                            // Reload the page to show changes
                                            location.reload();
                                        } catch (e) {
                                            logging.error('Error parsing assign image response', {
                                                error: e
                                            });
                                            // Still reload to show at least the new assignment
                                            location.reload();
                                        }
                                    },
                                    error: function (xhr, status, error) {
                                        logging.error('AJAX Error while assigning images', {
                                            status,
                                            error
                                        });
                                        // Still reload to show at least the new assignment
                                        location.reload();
                                    }
                                });
                            } else {
                                // No images to assign, just reload the page
                                location.reload();
                            }
                        } else {
                            logging.error('Failed to save assignment', data.message);
                            alert('Error: ' + data.message);
                        }
                    } catch (e) {
                        logging.error('Error parsing JSON response', {
                            error: e,
                            response
                        });
                        console.error('JSON parse error:', e);
                        console.log('Response that failed to parse:', response);
                        alert("An error occurred while processing the response.");
                    }
                },
                error: function (xhr, status, error) {
                    logging.error('AJAX Error', {
                        status,
                        error
                    });
                    console.error('AJAX error details:', xhr.responseText);
                    alert('An error occurred while saving the assignment: ' + error);
                }
            });
        });

        // Save images - handle form submission directly
        $('#saveImages').click(function () {
            const fileInput = $('#projectImages')[0];

            if (fileInput.files.length === 0) {
                alert('Please select at least one image to upload.');
                return;
            }

            logging.interaction('Uploading images', {
                count: fileInput.files.length
            });
            console.log(`Preparing to upload ${fileInput.files.length} images for project ID ${projectId}`);

            // Create a form data object and append all needed fields
            const formData = new FormData();
            formData.append('project_id', projectId);
            formData.append('action', 'upload_project_images');

            // Show a loading indicator with progress information
            const loadingHtml = `
                <div id="uploadSpinner" class="text-center my-3">
                    <div class="spinner-border text-primary" role="status">
                        <span class="sr-only">Uploading...</span>
                    </div>
                    <div class="mt-2">Uploading images, please wait...</div>
                    <div class="progress mt-3">
                        <div id="uploadProgressBar" class="progress-bar" role="progressbar" style="width: 0%;" 
                             aria-valuenow="0" aria-valuemin="0" aria-valuemax="100">0%</div>
                    </div>
                    <div id="uploadStatus" class="mt-2">Preparing files...</div>
                </div>
            `;
            $('#imagePreviewContainer').append(loadingHtml);
            $('#saveImages').prop('disabled', true).text('Uploading...');

            // Process files in batches
            const batchSize = 5; // Number of files per batch
            const totalFiles = fileInput.files.length;
            let processedFiles = 0;
            let successfulUploads = 0;
            let failedUploads = 0;

            // Function to update progress
            function updateProgress(processed, total) {
                const percentage = Math.round((processed / total) * 100);
                $('#uploadProgressBar').css('width', percentage + '%').attr('aria-valuenow', percentage).text(percentage + '%');
                $('#uploadStatus').text(`Processed ${processed} of ${total} files (${successfulUploads} successful, ${failedUploads} failed)`);
            }

            // Function to process a batch of files
            function processBatch(startIndex) {
                // Create a new FormData for this batch
                const batchFormData = new FormData();
                batchFormData.append('project_id', projectId);
                batchFormData.append('action', 'upload_project_images');

                // Add files for this batch
                const endIndex = Math.min(startIndex + batchSize, totalFiles);
                let batchFileDetails = [];

                for (let i = startIndex; i < endIndex; i++) {
                    batchFormData.append('projectImages[]', fileInput.files[i]);
                    logging.debug(`Adding file to batch upload: ${fileInput.files[i].name}`);
                    batchFileDetails.push({
                        name: fileInput.files[i].name,
                        size: fileInput.files[i].size,
                        type: fileInput.files[i].type
                    });
                }

                console.log(`Processing batch ${startIndex}-${endIndex - 1} of ${totalFiles} files:`, batchFileDetails);

                // Send the AJAX request for this batch
                $.ajax({
                    url: 'controllers/edit_project_ajax.php',
                    type: 'POST',
                    data: batchFormData,
                    processData: false,
                    contentType: false,
                    success: function (response) {
                        console.log(`Batch ${startIndex}-${endIndex - 1} response:`, response);
                        try {
                            const data = JSON.parse(response);
                            if (data.status === 'success') {
                                successfulUploads += (endIndex - startIndex);
                                logging.info(`Batch ${startIndex}-${endIndex - 1} uploaded successfully`);
                            } else {
                                failedUploads += (endIndex - startIndex);
                                logging.error(`Failed to upload batch ${startIndex}-${endIndex - 1}`, data.message);
                                console.error(`Batch upload error: ${data.message}`);
                            }
                        } catch (e) {
                            failedUploads += (endIndex - startIndex);
                            logging.error('Error parsing JSON response for batch', {
                                error: e,
                                response,
                                batch: `${startIndex}-${endIndex - 1}`
                            });
                            console.error('JSON parse error for batch:', e);
                        }

                        // Update processed count and progress
                        processedFiles += (endIndex - startIndex);
                        updateProgress(processedFiles, totalFiles);

                        // Process next batch or finish
                        if (endIndex < totalFiles) {
                            processBatch(endIndex);
                        } else {
                            // All batches processed
                            $('#uploadStatus').text(`Upload complete: ${successfulUploads} successful, ${failedUploads} failed`);

                            if (failedUploads > 0) {
                                alert(`Upload completed with some issues. ${successfulUploads} files uploaded successfully, ${failedUploads} files failed.`);
                                $('#uploadSpinner').remove();
                                $('#saveImages').prop('disabled', false).text('Upload Images');
                            } else {
                                logging.info('All images uploaded successfully', {
                                    count: successfulUploads
                                });
                                // Short delay before reload to show completion
                                setTimeout(function () {
                                    location.reload();
                                }, 1000);
                            }
                        }
                    },
                    error: function (xhr, status, error) {
                        failedUploads += (endIndex - startIndex);
                        logging.error('AJAX Error during batch upload', {
                            status,
                            error,
                            batch: `${startIndex}-${endIndex - 1}`
                        });
                        console.error(`Batch ${startIndex}-${endIndex - 1} error:`, xhr.responseText);

                        // Update processed count and progress
                        processedFiles += (endIndex - startIndex);
                        updateProgress(processedFiles, totalFiles);

                        // Continue with next batch despite error
                        if (endIndex < totalFiles) {
                            processBatch(endIndex);
                        } else {
                            // All batches processed
                            $('#uploadStatus').text(`Upload complete: ${successfulUploads} successful, ${failedUploads} failed`);

                            if (successfulUploads > 0) {
                                alert(`Upload completed with some issues. ${successfulUploads} files uploaded successfully, ${failedUploads} files failed.`);
                                setTimeout(function () {
                                    location.reload();
                                }, 1000);
                            } else {
                                alert('Error uploading images. Please try again.');
                                $('#uploadSpinner').remove();
                                $('#saveImages').prop('disabled', false).text('Upload Images');
                            }
                        }
                    }
                });
            }

            // Start processing the first batch
            processBatch(0);
        });

        // Image preview on selection
        $('#projectImages').change(function () {
            const files = this.files;
            $('#imagePreviewContainer').empty();

            logging.interaction('Images selected for upload preview', {
                count: files.length
            });

            for (let i = 0; i < files.length; i++) {
                const file = files[i];
                const reader = new FileReader();

                reader.onload = function (e) {
                    const preview = `
                        <div class="col-md-3 mb-3">
                            <div class="card">
                                <img src="${e.target.result}" alt="Preview">
                                <div class="card-body p-2">
                                    <small>${file.name}</small>
                                </div>
                            </div>
                        </div>
                    `;
                    $('#imagePreviewContainer').append(preview);
                };

                reader.readAsDataURL(file);
            }
        });

        // Try loading jQuery validation plugin
        if ($.fn.validate) {
            // Initialize validation for the form
            $('#editProjectForm').validate({
                rules: {
                    projectName: {
                        required: true,
                        minlength: 2
                    },
                    company: {
                        required: true
                    },
                    dateArrived: {
                        required: true,
                        date: true
                    },
                    deadline: {
                        required: true,
                        date: true
                    }
                },
                messages: {
                    projectName: {
                        required: "Please enter a project name",
                        minlength: "Project name must be at least 2 characters"
                    },
                    company: {
                        required: "Please enter a company name"
                    },
                    dateArrived: {
                        required: "Please enter a start date",
                        date: "Please enter a valid date"
                    },
                    deadline: {
                        required: "Please enter a deadline",
                        date: "Please enter a valid date"
                    }
                },
                errorElement: 'div',
                errorPlacement: function (error, element) {
                    error.addClass('invalid-feedback');
                    element.closest('.form-group').append(error);
                },
                highlight: function (element, errorClass, validClass) {
                    $(element).addClass('is-invalid').removeClass('is-valid');
                },
                unhighlight: function (element, errorClass, validClass) {
                    $(element).removeClass('is-invalid').addClass('is-valid');
                },
                submitHandler: function (form) {
                    logging.interaction('Form submitted');
                    form.submit();
                }
            });
        } else {
            logging.error('jQuery validation plugin not loaded');
            console.error('jQuery validation plugin is not available. Form validation will not work.');
        }

        // Log that page is fully initialized
        logging.info('Page initialization complete');

        // Handle save assignment changes
        $('.save-assignment-changes').click(function () {
            const assignmentId = $(this).data('assignment-id');
            const row = $(this).closest('tr');

            const userId = row.find('.assignee-select').val();
            const roleTask = row.find('.role-select').val();
            const status = row.find('.status-select').val();
            const deadline = row.find('.deadline-input').val();

            logging.interaction('Saving assignment changes', {
                assignmentId,
                userId,
                roleTask,
                status,
                deadline
            });

            // AJAX call to update assignment
            $.ajax({
                url: 'controllers/edit_project_ajax.php',
                type: 'POST',
                data: {
                    action: 'update_assignment',
                    assignment_id: assignmentId,
                    user_id: userId,
                    role_task: roleTask,
                    status: status,
                    deadline: deadline,
                    project_id: projectId
                },
                success: function (response) {
                    try {
                        const data = JSON.parse(response);
                        if (data.status === 'success') {
                            logging.info('Assignment updated successfully');

                            // Show success message
                            alert('Assignment updated successfully');

                            // Update data-original values
                            row.find('.assignee-select').data('original-value', userId);
                            row.find('.role-select').data('original-value', roleTask);
                        } else {
                            logging.error('Failed to update assignment', data.message);
                            alert('Error: ' + data.message);
                        }
                    } catch (e) {
                        logging.error('Error parsing JSON response', {
                            error: e,
                            response
                        });
                        alert("An error occurred while processing the response.");
                    }
                },
                error: function (xhr, status, error) {
                    logging.error('AJAX Error', {
                        status,
                        error
                    });
                    alert('An error occurred while updating the assignment: ' + error);
                }
            });
        });

        // Handle view assigned images
        $('.view-assigned-images').click(function () {
            const assignmentId = $(this).data('assignment-id');

            logging.interaction('Viewing assigned images', {
                assignmentId
            });

            // AJAX call to get assigned images
            logging.ajax('POST', 'controllers/edit_project_ajax.php', {
                action: 'get_assigned_images',
                project_id: projectId,
                assignment_id: assignmentId
            });

            $.ajax({
                url: 'controllers/edit_project_ajax.php',
                type: 'POST',
                data: {
                    action: 'get_assigned_images',
                    project_id: projectId,
                    assignment_id: assignmentId
                },
                success: function (response) {
                    try {
                        const data = JSON.parse(response);
                        logging.ajaxSuccess('POST', 'controllers/edit_project_ajax.php', data);

                        if (data.status === 'success') {
                            logging.info('Retrieved assigned images', {
                                count: data.images.length
                            });

                            let statusEditable = true;
                            let assignmentStatus = '';

                            if (data.assignment && data.assignment.status_assignee) {
                                assignmentStatus = data.assignment.status_assignee;
                                // Disable editing if status is not pending or completed
                                if (assignmentStatus !== 'pending' && assignmentStatus !== 'completed') {
                                    statusEditable = false;
                                }
                            }

                            let imagesHtml = '';

                            if (data.images && data.images.length > 0) {
                                // Create a table for image details
                                imagesHtml = `
                                <div class="table-responsive" style="max-height: 600px; overflow-y: auto;">
                                    <table class="table table-bordered table-hover" id="assigned-images-table">
                                        <thead class="thead-light" style="position: sticky; top: 0; z-index: 10;">
                                            <tr>
                                                <th style="width: 25%">Image Name</th>
                                                <th style="width: 35%">Estimated Time</th>
                                                <th style="width: 20%">Role</th>
                                                <th style="width: 20%">Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                `;

                                data.images.forEach(image => {
                                    const fileName = image.image_path ? image.image_path.split('/').pop() : 'Unknown';
                                    const imageUrl = image.image_url || '';

                                    // Editable fields only if status allows
                                    const estimatedTimeValue = image.estimated_time || '';
                                    const imageRole = image.image_role || '';

                                    // Split estimated time into hours and minutes
                                    let estimatedHours = 0;
                                    let estimatedMinutes = 0;

                                    if (image.estimated_time) {
                                        const totalMinutes = parseInt(image.estimated_time);
                                        estimatedHours = Math.floor(totalMinutes / 60);
                                        estimatedMinutes = totalMinutes % 60;
                                    }

                                    // Create a row for each image with editable fields
                                    imagesHtml += `
                                        <tr data-image-id="${image.image_id}" ${image.redo === '1' ? 'class="table-danger"' : ''} style="vertical-align: middle;">
                                            <td class="align-middle">
                                                <a href="${imageUrl}" target="_blank" class="image-preview-link">
                                                    ${fileName}
                                                </a>
                                                ${image.redo === '1' ? '<span class="badge badge-danger ml-2">Redo Required</span>' : ''}
                                            </td>
                                            <td>
                                                <div class="row no-gutters">
                                                    <div class="col-md-6 pr-2">
                                                        <div class="input-group">
                                                            <input type="number" 
                                                                class="form-control estimated-hours-input" 
                                                                value="${estimatedHours}"
                                                                min="0"
                                                                ${!statusEditable ? 'disabled' : ''}
                                                                data-image-id="${image.image_id}">
                                                            <div class="input-group-append">
                                                                <span class="input-group-text">hr</span>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <div class="input-group">
                                                            <input type="number" 
                                                                class="form-control estimated-minutes-input" 
                                                                value="${estimatedMinutes}"
                                                                min="0"
                                                                max="59"
                                                                ${!statusEditable ? 'disabled' : ''}
                                                                data-image-id="${image.image_id}">
                                                            <div class="input-group-append">
                                                                <span class="input-group-text">min</span>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </td>
                                            <td class="align-middle">
                                                <select class="form-control image-role-select"
                                                        ${!statusEditable ? 'disabled' : ''}
                                                        data-image-id="${image.image_id}">
                                                    <option value="">Select Role</option>
                                                    <option value="Clipping Path" ${imageRole === 'Clipping Path' ? 'selected' : ''}>Clipping Path</option>
                                                    <option value="Color Correction" ${imageRole === 'Color Correction' ? 'selected' : ''}>Color Correction</option>
                                                    <option value="Retouch" ${imageRole === 'Retouch' ? 'selected' : ''}>Retouch</option>
                                                    <option value="Final" ${imageRole === 'Final' ? 'selected' : ''}>Final</option>
                                                </select>
                                            </td>
                                            <td class="align-middle">
                                                ${statusEditable ? `
                                                <div class="btn-group">
                                                    <button type="button" class="btn btn-sm btn-danger remove-image-from-assignment" 
                                                            data-image-id="${image.image_id}">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                    <button type="button" class="btn btn-sm ${image.redo === '1' ? 'btn-danger' : 'btn-warning'} mark-redo-btn ml-1" 
                                                            data-image-id="${image.image_id}"
                                                            data-redo="${image.redo === '1' ? '0' : '1'}"
                                                            title="${image.redo === '1' ? 'Cancel Redo Request' : 'Mark for Redo'}">
                                                        <i class="fas ${image.redo === '1' ? 'fa-times' : 'fa-redo-alt'}"></i> ${image.redo === '1' ? 'Cancel Redo' : 'Redo'}
                                                    </button>
                                                </div>` :
                                            `<span class="badge badge-info">Locked</span>`}
                                            </td>
                                        </tr>
                                    `;
                                });

                                imagesHtml += `
                                        </tbody>
                                    </table>
                                </div>
                                `;

                                if (!statusEditable) {
                                    imagesHtml += `
                                    <div class="alert alert-info mt-3">
                                        <i class="fas fa-info-circle mr-2"></i>
                                        These image details cannot be edited while the task is in progress.
                                    </div>
                                    `;
                                }
                            } else {
                                imagesHtml = '<div class="alert alert-info">No images assigned to this team member.</div>';
                            }

                            // Create and show modal
                            const modalHtml = `
                                <div class="modal fade" id="viewAssignedImagesModal" tabindex="-1" role="dialog">
                                    <div class="modal-dialog modal-xl" style="max-width: 90%; margin: 10px auto;" role="document">
                                        <div class="modal-content">
                                            <div class="modal-header bg-primary text-white">
                                                <h5 class="modal-title">
                                                    <i class="fas fa-images mr-2"></i>
                                                    Assigned Images (${data.images.length})
                                                </h5>
                                                <button type="button" class="close text-white" data-dismiss="modal">
                                                    <span>&times;</span>
                                                </button>
                                            </div>
                                            <div class="modal-body">
                                                ${imagesHtml}
                                            </div>
                                            <div class="modal-footer d-flex justify-content-between">
                                                <div>
                                                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                                                </div>
                                                <div>
                                                    <button type="button" class="btn btn-danger remove-all-assigned-images mr-2" data-assignment-id="${assignmentId}" ${!statusEditable ? 'disabled' : ''}>
                                                        <i class="fas fa-trash mr-1"></i> Remove All Images
                                                    </button>
                                                    <button type="button" class="btn btn-success save-all-image-details" data-assignment-id="${assignmentId}" ${!statusEditable ? 'disabled' : ''}>
                                                        <i class="fas fa-save mr-1"></i> Save All Changes
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            `;

                            // Add modal to body and show it
                            $('body').append(modalHtml);
                            $('#viewAssignedImagesModal').modal('show');

                            // Make sure the modal is removed from the DOM when hidden to prevent duplicates
                            $('#viewAssignedImagesModal').on('hidden.bs.modal', function () {
                                $(this).remove();
                            });

                            // Log redo information and ensure button states are correct
                            data.images.forEach(image => {
                                console.log(`Image ${image.image_id} redo status: ${image.redo}`);
                                const redoBtn = $(`.mark-redo-btn[data-image-id="${image.image_id}"]`);
                                if (image.redo === '1') {
                                    // Ensure "Cancel Redo" state
                                    redoBtn.removeClass('btn-warning').addClass('btn-danger');
                                    redoBtn.data('redo', '0');
                                    redoBtn.attr('data-redo', '0');
                                    redoBtn.attr('title', 'Cancel Redo Request');
                                    redoBtn.html('<i class="fas fa-times"></i> Cancel Redo');
                                } else {
                                    // Ensure "Redo" state
                                    redoBtn.removeClass('btn-danger').addClass('btn-warning');
                                    redoBtn.data('redo', '1');
                                    redoBtn.attr('data-redo', '1');
                                    redoBtn.attr('title', 'Mark for Redo');
                                    redoBtn.html('<i class="fas fa-redo-alt"></i> Redo');
                                }
                            });

                            // Now let's handle the new single save button for all image details
                            $('.save-all-image-details').click(function () {
                                const assignmentId = $(this).data('assignment-id');
                                const rows = $('#assigned-images-table tr[data-image-id]');
                                const updates = [];

                                // Collect all changes from each row
                                rows.each(function () {
                                    const imageId = $(this).data('image-id');
                                    const row = $(this);
                                    const estimatedHours = row.find('.estimated-hours-input').val();
                                    const estimatedMinutes = row.find('.estimated-minutes-input').val();
                                    const imageRole = row.find('.image-role-select').val();

                                    updates.push({
                                        image_id: imageId,
                                        estimated_hours: estimatedHours,
                                        estimated_minutes: estimatedMinutes,
                                        image_role: imageRole
                                    });
                                });

                                // Show loading state
                                const saveBtn = $(this);
                                saveBtn.prop('disabled', true)
                                    .html('<i class="fas fa-spinner fa-spin mr-1"></i> Saving...');

                                // Track progress
                                let completed = 0;
                                let errors = 0;

                                // Function to check if all updates are done
                                function checkCompletion() {
                                    if (completed + errors === updates.length) {
                                        saveBtn.prop('disabled', false)
                                            .html('<i class="fas fa-save mr-1"></i> Save All Changes');

                                        if (errors === 0) {
                                            toastr.success('All changes saved successfully');
                                        } else {
                                            toastr.error(`${errors} updates failed. ${completed} updates succeeded.`);
                                        }
                                    }
                                }

                                // Process each update
                                updates.forEach(update => {
                                    $.ajax({
                                        url: 'controllers/edit_project_ajax.php',
                                        type: 'POST',
                                        data: {
                                            action: 'update_image_details',
                                            image_id: update.image_id,
                                            estimated_hours: update.estimated_hours,
                                            estimated_minutes: update.estimated_minutes,
                                            image_role: update.image_role
                                        },
                                        success: function (response) {
                                            try {
                                                const data = JSON.parse(response);
                                                if (data.status === 'success') {
                                                    completed++;
                                                    // Highlight the row to indicate success
                                                    $(`tr[data-image-id="${update.image_id}"]`).addClass('table-success');
                                                    setTimeout(() => {
                                                        $(`tr[data-image-id="${update.image_id}"]`).removeClass('table-success');
                                                    }, 2000);
                                                } else {
                                                    errors++;
                                                    console.error('Error updating image details:', data.message);
                                                }
                                            } catch (e) {
                                                errors++;
                                                console.error('Error parsing update response:', e);
                                            }
                                            checkCompletion();
                                        },
                                        error: function () {
                                            errors++;
                                            console.error('Server error while updating image details');
                                            checkCompletion();
                                        }
                                    });
                                });
                            });

                            // Handle remove image from assignment
                            $(document).on('click', '.remove-image-from-assignment', function () {
                                const imageId = $(this).data('image-id');
                                const row = $(`tr[data-image-id="${imageId}"]`);

                                if (confirm('Are you sure you want to remove this image from the assignment?')) {
                                    $.ajax({
                                        url: 'controllers/edit_project_ajax.php',
                                        type: 'POST',
                                        data: {
                                            action: 'unassign_images',
                                            project_id: projectId,
                                            image_ids: JSON.stringify([imageId])
                                        },
                                        success: function (response) {
                                            try {
                                                const data = JSON.parse(response);
                                                if (data.status === 'success') {
                                                    toastr.success('Image removed from assignment');
                                                    // Remove the row from the table
                                                    row.fadeOut(300, function () {
                                                        $(this).remove();
                                                        // Update the count in the modal title
                                                        const currentCount = parseInt($('.modal-title').text().match(/\d+/)[0]) - 1;
                                                        $('.modal-title').html(`<i class="fas fa-images mr-2"></i> Assigned Images (${currentCount})`);
                                                    });
                                                } else {
                                                    toastr.error(data.message || 'Failed to remove image');
                                                }
                                            } catch (e) {
                                                console.error('Error parsing response:', e);
                                                toastr.error('Error removing image');
                                            }
                                        },
                                        error: function () {
                                            toastr.error('Server error while removing image');
                                        }
                                    });
                                }
                            });

                            // Handle remove all assigned images
                            $(document).on('click', '.remove-all-assigned-images', function () {
                                const assignmentId = $(this).data('assignment-id');

                                // Log this action
                                console.log('Removing all images from assignment', { assignmentId });

                                if (confirm('Are you sure you want to remove ALL images from this assignment? This action cannot be undone.')) {
                                    // Get all image IDs from the table
                                    const imageIds = [];
                                    $('#assigned-images-table tr[data-image-id]').each(function () {
                                        imageIds.push($(this).data('image-id'));
                                    });

                                    if (imageIds.length === 0) {
                                        toastr.info('No images to remove');
                                        return;
                                    }

                                    // Show loading state
                                    const btn = $(this);
                                    btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-1"></i> Removing...');

                                    $.ajax({
                                        url: 'controllers/edit_project_ajax.php',
                                        type: 'POST',
                                        data: {
                                            action: 'unassign_images',
                                            project_id: projectId,
                                            image_ids: JSON.stringify(imageIds)
                                        },
                                        success: function (response) {
                                            try {
                                                const data = JSON.parse(response);
                                                if (data.status === 'success') {
                                                    toastr.success('All images removed from assignment');

                                                    // Close the modal after successful removal
                                                    $('#viewAssignedImagesModal').modal('hide');

                                                    // Find the assignment row and update only the image count text
                                                    const assignmentRow = $(`tr[data-assignment-id="${assignmentId}"]`);
                                                    const imageCell = assignmentRow.find('td:nth-child(3)'); // The "Assigned Images" cell

                                                    // Only update the text part, not the buttons
                                                    if (imageCell.length > 0) {
                                                        // Keep the HTML structure but just update the text part
                                                        const cellHtml = imageCell.html();
                                                        // Replace just the number before "Images" with 0
                                                        const newHtml = cellHtml.replace(/\d+\s+Images/, "0 Images");
                                                        imageCell.html(newHtml);

                                                        // Log this action
                                                        console.log('Updated image count display to 0', { assignmentId });
                                                    }
                                                } else {
                                                    toastr.error(data.message || 'Failed to remove images');
                                                    btn.prop('disabled', false).html('<i class="fas fa-trash mr-1"></i> Remove All Images');
                                                }
                                            } catch (e) {
                                                console.error('Error parsing response:', e);
                                                toastr.error('Error removing images');
                                                btn.prop('disabled', false).html('<i class="fas fa-trash mr-1"></i> Remove All Images');
                                            }
                                        },
                                        error: function () {
                                            toastr.error('Server error while removing images');
                                            btn.prop('disabled', false).html('<i class="fas fa-trash mr-1"></i> Remove All Images');
                                        }
                                    });
                                }
                            });

                            // Remove modal when hidden
                            $('#viewAssignedImagesModal').on('hidden.bs.modal', function () {
                                $(this).remove();
                            });
                        } else {
                            showToast('error', data.message || 'Error retrieving images');
                        }
                    } catch (e) {
                        console.error('Error parsing response:', e);
                        logging.error('Error parsing AJAX response', e);
                        toastr.error('Error retrieving images');
                    }
                },
                error: function (xhr, status, error) {
                    logging.ajaxError('POST', 'controllers/edit_project_ajax.php', {
                        status: status,
                        error: error,
                        xhr: xhr
                    });
                    toastr.error('Server error while retrieving images');
                }
            });
        });

        // Handle add more images
        $('.add-more-images').click(function () {
            const assignmentId = $(this).data('assignment-id');
            logging.interaction('Adding more images to assignment', {
                assignmentId
            });

            // AJAX call to get available images
            $.ajax({
                url: 'controllers/edit_project_ajax.php',
                type: 'POST',
                data: {
                    action: 'get_available_images',
                    project_id: projectId
                },
                success: function (response) {
                    try {
                        const data = JSON.parse(response);

                        if (data.status === 'success') {
                            logging.info('Retrieved available images', {
                                count: data.images.length
                            });

                            if (data.images.length === 0) {
                                alert('No available images to assign. Please upload more images first.');
                                return;
                            }

                            // Create modal to display available images for selection
                            let imagesHtml = '<div class="row">';

                            data.images.forEach(image => {
                                const fileName = image.image_path.split('/').pop();
                                const imageUrl = `../uploads/projects/${projectId}/${image.image_path}`;

                                imagesHtml += `
                                    <div class="col-md-4 mb-3">
                                        <div class="card selectable-image" data-image-id="${image.image_id}">
                                            <div class="card-body p-2">
                                                <div class="d-flex align-items-center justify-content-center bg-light py-2 mb-2">
                                                    <i class="fas fa-file-image text-primary mr-2"></i>
                                                    <small class="text-truncate" title="${fileName}">${fileName}</small>
                                                </div>
                                                <div class="image-select-checkbox mt-2 text-center">
                                                    <input type="checkbox" class="form-check-input image-select" value="${image.image_id}">
                                                    <label class="form-check-label">Select</label>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                `;
                            });

                            imagesHtml += '</div>';

                            // Create and show modal
                            const modalHtml = `
                                <div class="modal fade" id="addMoreImagesModal" tabindex="-1" role="dialog">
                                    <div class="modal-dialog modal-lg" role="document">
                                        <div class="modal-content">
                                            <div class="modal-header bg-primary text-white">
                                                <h5 class="modal-title">
                                                    <i class="fas fa-plus-circle mr-2"></i>
                                                    Add Images to Assignment
                                                </h5>
                                                <button type="button" class="close text-white" data-dismiss="modal">
                                                    <span>&times;</span>
                                                </button>
                                            </div>
                                            <div class="modal-body">
                                                <p>Select images to add to this assignment:</p>
                                                ${imagesHtml}
                                            </div>
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                                                <button type="button" class="btn btn-primary" id="confirmAddImages">Add Selected Images</button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            `;

                            // Add modal to body
                            $('body').append(modalHtml);
                            $('#addMoreImagesModal').modal('show');

                            // Handle click on selectable image
                            $('.selectable-image').click(function (e) {
                                if (!$(e.target).hasClass('image-select') && !$(e.target).hasClass('form-check-label')) {
                                    const checkbox = $(this).find('.image-select');
                                    checkbox.prop('checked', !checkbox.prop('checked'));
                                }
                            });

                            // Handle confirmation
                            $('#confirmAddImages').click(function () {
                                const selectedImageIds = [];

                                $('#addMoreImagesModal .image-select:checked').each(function () {
                                    selectedImageIds.push($(this).val());
                                });

                                if (selectedImageIds.length === 0) {
                                    alert('Please select at least one image to add.');
                                    return;
                                }

                                logging.interaction('Confirming add images to assignment', {
                                    assignmentId,
                                    imageCount: selectedImageIds.length
                                });

                                // AJAX call to assign selected images
                                $.ajax({
                                    url: 'controllers/edit_project_ajax.php',
                                    type: 'POST',
                                    data: {
                                        action: 'assign_images',
                                        assignment_id: assignmentId,
                                        project_id: projectId,
                                        image_ids: JSON.stringify(selectedImageIds)
                                    },
                                    success: function (response) {
                                        try {
                                            const data = JSON.parse(response);

                                            if (data.status === 'success') {
                                                logging.info('Images added to assignment successfully');
                                                $('#addMoreImagesModal').modal('hide');

                                                // Reload page to show updated assignments
                                                location.reload();
                                            } else {
                                                logging.error('Failed to add images to assignment', data.message);
                                                alert('Error: ' + data.message);
                                            }
                                        } catch (e) {
                                            logging.error('Error parsing JSON response', {
                                                error: e,
                                                response
                                            });
                                            alert("An error occurred while processing the response.");
                                        }
                                    },
                                    error: function (xhr, status, error) {
                                        logging.error('AJAX Error', {
                                            status,
                                            error
                                        });
                                        alert('An error occurred while adding images to assignment: ' + error);
                                    }
                                });
                            });

                            // Remove modal from DOM when hidden
                            $('#addMoreImagesModal').on('hidden.bs.modal', function () {
                                $(this).remove();
                            });
                        } else {
                            logging.error('Failed to retrieve available images', data.message);
                            alert('Error: ' + data.message);
                        }
                    } catch (e) {
                        logging.error('Error parsing JSON response', {
                            error: e,
                            response
                        });
                        alert("An error occurred while processing the response.");
                    }
                },
                error: function (xhr, status, error) {
                    logging.error('AJAX Error', {
                        status,
                        error
                    });
                    alert('An error occurred while retrieving available images: ' + error);
                }
            });
        });

        // Initialize tooltips
        $('[data-toggle="tooltip"]').tooltip();

        // Set the assignSelected flag when clicking the button
        $('#assignSelected').click(function () {
            $(this).data('clicked', true);

            // Update the modal title to indicate images are being assigned
            const selectedImagesCount = getSelectedImageIds().length;
            $('#addAssignmentModalLabel').text(`Assign ${selectedImagesCount} Selected Image(s)`);
        });

        // Reset the flag when the modal is closed
        $('#addAssignmentModal').on('hidden.bs.modal', function () {
            $('#assignSelected').data('clicked', false);
            $('#addAssignmentModalLabel').text('Add New Assignment');
        });

        // Handle deadline change
        $('.deadline-input').change(function () {
            const assignmentId = $(this).data('assignment-id');
            const newDeadline = $(this).val();
            const inputElement = $(this);

            logging.interaction('Deadline change', {
                assignmentId,
                newDeadline
            });

            // AJAX call to update assignment deadline
            $.ajax({
                url: 'controllers/edit_project_ajax.php',
                type: 'POST',
                data: {
                    action: 'update_assignment_deadline',
                    assignment_id: assignmentId,
                    deadline: newDeadline
                },
                success: function (response) {
                    try {
                        const data = JSON.parse(response);
                        if (data.status === 'success') {
                            logging.info('Deadline updated successfully');

                            // Update the deadline status badge
                            const badgeContainer = inputElement.siblings('.badge');

                            // Check if we need to add/update/remove deadline badge
                            if (data.deadline_status) {
                                let badgeClass = '';
                                let badgeText = '';

                                if (data.deadline_status === 'today') {
                                    badgeClass = 'badge-warning';
                                    badgeText = 'Deadline Today';
                                } else if (data.deadline_status === 'overdue') {
                                    badgeClass = 'badge-danger';
                                    badgeText = 'Overdue';
                                }

                                // If badge exists, update it, otherwise create it
                                if (badgeContainer.length) {
                                    badgeContainer.attr('class', 'badge ' + badgeClass + ' mt-1 w-100')
                                        .text(badgeText);
                                } else if (badgeText) {
                                    inputElement.after('<span class="badge ' + badgeClass + ' mt-1 w-100">' + badgeText + '</span>');
                                }
                            } else {
                                // Remove badge if no special status
                                badgeContainer.remove();
                            }

                            // Show subtle indication of success
                            inputElement.addClass('border-success').delay(1000).queue(function (next) {
                                $(this).removeClass('border-success');
                                next();
                            });
                        } else {
                            logging.error('Failed to update deadline', data.message);
                            alert('Error: ' + data.message);
                        }
                    } catch (e) {
                        logging.error('Error parsing JSON response', {
                            error: e,
                            response
                        });
                        alert("An error occurred while processing the response.");
                    }
                },
                error: function (xhr, status, error) {
                    logging.error('AJAX Error', {
                        status,
                        error
                    });
                    alert('An error occurred while updating deadline: ' + error);
                }
            });
        });

        // Handle assignee change
        $('.assignee-select').change(function () {
            const assignmentId = $(this).data('assignment-id');
            const userId = $(this).val();
            const selectElement = $(this);

            logging.interaction('Assignee change', {
                assignmentId,
                userId
            });

            // AJAX call to update assignment assignee
            $.ajax({
                url: 'controllers/edit_project_ajax.php',
                type: 'POST',
                data: {
                    action: 'update_assignment_assignee',
                    assignment_id: assignmentId,
                    user_id: userId
                },
                success: function (response) {
                    try {
                        const data = JSON.parse(response);
                        if (data.status === 'success') {
                            logging.info('Assignee updated successfully');

                            // Update in_progress option text if needed
                            const statusSelect = $('select.assignment-status-select[data-assignment-id="' + assignmentId + '"]');
                            const inProgressOption = statusSelect.find('option[value="in_progress"]');
                            if (inProgressOption.length && data.first_name) {
                                inProgressOption.text('In Progress (' + data.first_name + ')');
                            }

                            // Show subtle indication of success
                            selectElement.addClass('border-success').delay(1000).queue(function (next) {
                                $(this).removeClass('border-success');
                                next();
                            });
                        } else {
                            logging.error('Failed to update assignee', data.message);
                            alert('Error: ' + data.message);
                        }
                    } catch (e) {
                        logging.error('Error parsing JSON response', {
                            error: e,
                            response
                        });
                        alert("An error occurred while processing the response.");
                    }
                },
                error: function (xhr, status, error) {
                    logging.error('AJAX Error', {
                        status,
                        error
                    });
                    alert('An error occurred while updating assignee: ' + error);
                }
            });
        });

        // Handle role change
        $('.role-select').change(function () {
            const assignmentId = $(this).data('assignment-id');
            const roleTask = $(this).val();
            const selectElement = $(this);

            logging.interaction('Role change', {
                assignmentId,
                roleTask
            });

            // AJAX call to update assignment role
            $.ajax({
                url: 'controllers/edit_project_ajax.php',
                type: 'POST',
                data: {
                    action: 'update_assignment_role',
                    assignment_id: assignmentId,
                    role_task: roleTask
                },
                success: function (response) {
                    try {
                        const data = JSON.parse(response);
                        if (data.status === 'success') {
                            logging.info('Role updated successfully');

                            // Show subtle indication of success
                            selectElement.addClass('border-success').delay(1000).queue(function (next) {
                                $(this).removeClass('border-success');
                                next();
                            });
                        } else {
                            logging.error('Failed to update role', data.message);
                            alert('Error: ' + data.message);
                        }
                    } catch (e) {
                        logging.error('Error parsing JSON response', {
                            error: e,
                            response
                        });
                        alert("An error occurred while processing the response.");
                    }
                },
                error: function (xhr, status, error) {
                    logging.error('AJAX Error', {
                        status,
                        error
                    });
                    alert('An error occurred while updating role: ' + error);
                }
            });
        });

        // Handle removing assigned images
        $('.remove-assigned-images').on('click', function () {
            if (!confirm('Are you sure you want to remove all assigned images from this team member?')) {
                return;
            }

            var assignmentId = $(this).data('assignment-id');
            var button = $(this);

            console.log('Removing assigned images for assignment ID:', assignmentId);

            $.ajax({
                url: 'controllers/edit_project_ajax.php',
                type: 'POST',
                data: {
                    action: 'remove_assigned_images',
                    assignment_id: assignmentId
                },
                success: function (response) {
                    var data = JSON.parse(response);
                    console.log('Server response:', data);

                    if (data.status === 'success') {
                        // Find the cell with the image count and update only that text
                        // without affecting the buttons
                        var row = button.closest('tr');
                        var imagesCell = row.find('td:contains("Images")');

                        // Only update the text content, not the HTML
                        if (imagesCell.length > 0) {
                            var cellHtml = imagesCell.html();
                            // Replace just the number before "Images" with 0
                            var newHtml = cellHtml.replace(/\d+\s+Images/, "0 Images");
                            imagesCell.html(newHtml);
                        }

                        toastr.success(data.message);
                    } else {
                        toastr.error(data.message);
                    }
                },
                error: function (xhr, status, error) {
                    console.error('Error:', error);
                    toastr.error('An error occurred while removing assigned images');
                }
            });
        });

        // Function to update project fields via AJAX
        function updateProjectField(fieldName, fieldValue) {
            logging.interaction('Updating project field', {
                field: fieldName,
                value: fieldValue
            });

            // Show a loading indicator or toast notification
            const toastHtml = `
                <div class="toast position-fixed bg-info text-white" style="top: 20px; right: 20px; z-index: 9999;" data-delay="2000">
                    <div class="toast-body d-flex align-items-center">
                        <i class="fas fa-spinner fa-spin mr-2"></i> Saving changes...
                    </div>
                </div>
            `;

            // Append toast if it doesn't exist
            if ($('#saveToast').length === 0) {
                $('body').append('<div id="saveToast"></div>');
            }

            $('#saveToast').html(toastHtml);
            $('.toast').toast('show');

            // Make AJAX request
            $.ajax({
                url: 'controllers/edit_project_ajax.php',
                type: 'POST',
                data: {
                    action: 'update_project_field',
                    project_id: projectId,
                    field_name: fieldName,
                    field_value: fieldValue
                },
                success: function (response) {
                    try {
                        const data = JSON.parse(response);

                        if (data.status === 'success') {
                            // Update toast to show success
                            $('.toast').removeClass('bg-info').addClass('bg-success');
                            $('.toast .toast-body').html('<i class="fas fa-check-circle mr-2"></i> Saved successfully');

                            // Hide toast after delay
                            setTimeout(() => {
                                $('.toast').toast('hide');
                            }, 2000);

                            logging.info('Field updated successfully', {
                                field: fieldName
                            });
                        } else {
                            // Update toast to show error
                            $('.toast').removeClass('bg-info').addClass('bg-danger');
                            $('.toast .toast-body').html(`<i class="fas fa-exclamation-circle mr-2"></i> ${data.message}`);

                            logging.error('Failed to update field', {
                                field: fieldName,
                                error: data.message
                            });
                        }
                    } catch (e) {
                        // Update toast to show error
                        $('.toast').removeClass('bg-info').addClass('bg-danger');
                        $('.toast .toast-body').html('<i class="fas fa-exclamation-circle mr-2"></i> Error processing response');

                        logging.error('Error parsing JSON response', {
                            error: e,
                            response
                        });
                    }
                },
                error: function (xhr, status, error) {
                    // Update toast to show error
                    $('.toast').removeClass('bg-info').addClass('bg-danger');
                    $('.toast .toast-body').html('<i class="fas fa-exclamation-circle mr-2"></i> Network error');

                    logging.error('AJAX Error', {
                        status,
                        error
                    });
                }
            });
        }

        // Handle "Remove All Images" button click
        $('#removeAllImages').on('click', function () {
            // Show confirmation dialog
            if (!confirm('Are you sure? This will remove all the uploaded images.')) {
                return;
            }

            // Show loading
            $(this).html('<i class="fas fa-spinner fa-spin"></i> Processing...');
            $(this).prop('disabled', true);

            // Make AJAX request to remove all images
            $.ajax({
                url: 'controllers/edit_project_ajax.php',
                type: 'POST',
                data: {
                    action: 'remove_all_images',
                    project_id: <?php echo $project_id; ?>
                },
                dataType: 'json',
                success: function (response) {
                    if (response.status === 'success') {
                        // Remove all image elements
                        $('.image-container').remove();

                        // Show empty state
                        $('#imagesContainer').html('<div class="col-12 text-center p-4"><p class="text-muted">No images uploaded yet</p></div>');

                        // Show success message
                        toastr.success(response.message || 'All images have been removed successfully');
                    } else {
                        // Show error message
                        toastr.error(response.message || 'Failed to remove images');
                    }

                    // Reset button
                    $('#removeAllImages').html('<i class="fas fa-trash-alt"></i> Remove All');
                    $('#removeAllImages').prop('disabled', false);
                },
                error: function () {
                    // Show error message
                    toastr.error('Error connecting to server');

                    // Reset button
                    $('#removeAllImages').html('<i class="fas fa-trash-alt"></i> Remove All');
                    $('#removeAllImages').prop('disabled', false);
                }
            });
        });

        // Handle status timeline clicks
        $(document).on('click', '.status-step', function () {
            const assignmentId = $(this).data('assignment-id');
            const newStatus = $(this).data('status');
            const currentStatus = $(`input.current-status[data-assignment-id="${assignmentId}"]`).val();

            // Log the attempted status change
            console.log('Status timeline click', {
                assignmentId,
                currentStatus,
                attemptedNewStatus: newStatus
            });

            // Admin can't change status, only approve when status is 'finish'
            toastr.warning('Admin cannot change task status directly. Only the assigned artist can update status progress.');
            return false;
        });

        // Handle approve task button click
        $(document).on('click', '.approve-task-btn', function () {
            const assignmentId = $(this).data('assignment-id');
            const currentStatus = $(`input.current-status[data-assignment-id="${assignmentId}"]`).val();

            // Only allow approval when status is 'finish'
            if (currentStatus !== 'finish') {
                toastr.error('This task can only be approved when it is in "Finish" status.');
                console.log('Approve button clicked but status not eligible', {
                    assignmentId,
                    currentStatus
                });
                return;
            }

            console.log('Admin approving task', { assignmentId, currentStatus });

            // Update directly to 'completed' status (skipping 'approved')
            updateAssignmentStatus(assignmentId, 'completed');
        });

        // Function to update assignment status
        function updateAssignmentStatus(assignmentId, newStatus) {

            $.ajax({
                url: 'controllers/edit_project_ajax.php',
                type: 'POST',
                data: {
                    action: 'update_assignment_status',
                    assignment_id: assignmentId,
                    status: newStatus
                },
                dataType: 'json',
                success: function (response) {
                    if (response.status === 'success') {
                        // Update the timeline UI
                        updateTimelineUI(assignmentId, newStatus);

                        // Show success message
                        toastr.success('Status updated successfully');

                        // Check if all assignments are completed to update project status
                        if (newStatus === 'completed') {
                            checkAndUpdateProjectStatus();
                        }
                    } else {
                        // Show error message
                        toastr.error(response.message || 'Failed to update status');
                    }
                },
                error: function () {
                    // Show error message
                    toastr.error('Error connecting to server');
                }
            });
        }

        // Function to update the timeline UI
        function updateTimelineUI(assignmentId, newStatus) {
            const timelineSteps = ['pending', 'in_progress', 'finish', 'qa', 'approved', 'completed'];
            const newIndex = timelineSteps.indexOf(newStatus);

            // Update the hidden input
            $(`input.current-status[data-assignment-id="${assignmentId}"]`).val(newStatus);

            // Update the timeline steps
            const $timeline = $(`.status-timeline .status-step[data-assignment-id="${assignmentId}"]`).parent();
            $timeline.find('.status-step').each(function (index) {
                if (index <= newIndex) {
                    $(this).addClass('active');
                } else {
                    $(this).removeClass('active');
                }

                if (index === newIndex) {
                    $(this).addClass('current');
                } else {
                    $(this).removeClass('current');
                }
            });

            // Update the connectors
            $timeline.find('.status-connector').each(function (index) {
                if (index < newIndex) {
                    $(this).addClass('active');
                } else {
                    $(this).removeClass('active');
                }
            });

            // Show/hide approve button
            if (newStatus === 'finish') {
                const $approveBtn = $(`<button type="button" class="btn btn-sm btn-success mt-2 approve-task-btn" 
                                             data-assignment-id="${assignmentId}">
                                        <i class="fas fa-check-circle"></i> Approve
                                      </button>`);
                $timeline.parent().find('.approve-task-btn').remove();
                $timeline.after($approveBtn);
            } else {
                $timeline.parent().find('.approve-task-btn').remove();
            }
        }

        // Function to check if all assignments are completed and update project status
        function checkAndUpdateProjectStatus() {
            const projectId = <?php echo $project_id; ?>;

            $.ajax({
                url: 'controllers/edit_project_ajax.php',
                type: 'POST',
                data: {
                    action: 'check_all_assignments_completed',
                    project_id: projectId
                },
                dataType: 'json',
                success: function (response) {
                    if (response.status === 'success' && response.all_completed) {
                        // Only update UI if project is not delayed
                        if (response.current_status !== 'delayed') {
                            // Update project status to completed
                            updateProjectStatus('completed');
                            toastr.info('All tasks completed - project status updated to Completed');
                        } else {
                            // Project remains delayed even though all tasks are completed
                            toastr.info('All tasks completed - project remains marked as Delayed because it missed deadlines');
                        }
                    }
                }
            });
        }

        // Handle team member selection
        $('.team-member-select').change(function () {
            const assignmentId = $(this).data('assignment-id');
            const userId = $(this).val();
            const selectElement = $(this);

            // Check if the status allows edits
            const currentStatus = $(`input.current-status[data-assignment-id="${assignmentId}"]`).val();
            if (currentStatus && currentStatus !== 'pending') {
                showToast('error', 'Cannot change team member while task is in progress');
                // Reset selection to original value
                $(this).val($(this).find('option[selected]').val());
                return;
            }

            if (!userId) {
                return; // Nothing selected
            }

            // AJAX call to update assignment
            $.ajax({
                url: 'controllers/edit_project_ajax.php',
                type: 'POST',
                data: {
                    action: 'update_assignment_user',
                    assignment_id: assignmentId,
                    user_id: userId
                },
                success: function (response) {
                    try {
                        const data = JSON.parse(response);
                        if (data.status === 'success') {
                            showToast('success', 'Team member updated successfully');
                        } else {
                            showToast('error', data.message || 'Failed to update team member');
                            // Reset selection
                            selectElement.val(selectElement.find('option[selected]').val());
                        }
                    } catch (e) {
                        console.error('Error parsing response:', e);
                        showToast('error', 'Error updating team member');
                        // Reset selection
                        selectElement.val(selectElement.find('option[selected]').val());
                    }
                },
                error: function () {
                    showToast('error', 'Server error while updating team member');
                    // Reset selection
                    selectElement.val(selectElement.find('option[selected]').val());
                }
            });
        });


        // Handle mark as acceptable button click
        $(document).on('click', '.mark-acceptable-btn', function () {
            const assignmentId = $(this).data('assignment-id');

            // Log the action
            console.log('Marking delay as acceptable', { assignmentId });

            // Show confirmation dialog
            Swal.fire({
                title: 'Confirm Action',
                text: 'Are you sure you want to mark this delay as acceptable?',
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#28a745',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Yes, mark as acceptable'
            }).then((result) => {
                if (result.isConfirmed) {
                    // Show loading indicator
                    const loadingModal = Swal.fire({
                        title: 'Processing...',
                        html: '<div class="text-center"><i class="fas fa-spinner fa-spin fa-3x"></i></div>',
                        showConfirmButton: false,
                        allowOutsideClick: false
                    });

                    // Make AJAX call to update
                    $.ajax({
                        url: 'controllers/edit_project_ajax.php',
                        type: 'POST',
                        data: {
                            action: 'mark_delay_acceptable',
                            assignment_id: assignmentId
                        },
                        success: function (response) {
                            loadingModal.close();

                            try {
                                const data = JSON.parse(response);

                                if (data.status === 'success') {
                                    // Show success message
                                    Swal.fire({
                                        title: 'Success!',
                                        text: data.message || 'Delay marked as acceptable.',
                                        icon: 'success',
                                        confirmButtonColor: '#28a745'
                                    }).then(() => {
                                        // Update the UI without reloading
                                        const button = $(`.mark-acceptable-btn[data-assignment-id="${assignmentId}"]`);
                                        const deadlineContainer = button.closest('.deadline-container');
                                        const badgeElement = deadlineContainer.find('.badge');

                                        // Change badge color from danger to success
                                        badgeElement.removeClass('badge-danger').addClass('badge-success');
                                        // Update text to include (Acceptable)
                                        badgeElement.text(badgeElement.text() + ' (Acceptable)');
                                        // Remove the button
                                        button.remove();
                                    });
                                } else {
                                    // Show error message
                                    Swal.fire({
                                        title: 'Error',
                                        text: data.message || 'Failed to mark delay as acceptable.',
                                        icon: 'error'
                                    });
                                }
                            } catch (error) {
                                console.error('Error parsing response:', error, response);
                                Swal.fire({
                                    title: 'Error',
                                    text: 'Something went wrong. Please try again.',
                                    icon: 'error'
                                });
                            }
                        },
                        error: function (xhr, status, error) {
                            loadingModal.close();
                            console.error('AJAX error:', xhr, status, error);
                            Swal.fire({
                                title: 'Error',
                                text: 'Failed to communicate with the server. Please try again.',
                                icon: 'error'
                            });
                        }
                    });
                }
            });
        });

        // Handle mark for redo button
        $(document).on('click', '.mark-redo-btn', function () {
            const imageId = $(this).data('image-id');
            const redoValue = $(this).data('redo');
            const button = $(this);

            // Show confirmation based on action type
            const confirmMessage = redoValue === '1'
                ? 'Are you sure you want to mark this image for redo?'
                : 'Cancel redo request for this image?';

            if (confirm(confirmMessage)) {
                // Update button UI to show loading state
                const originalHtml = button.html();
                button.html('<i class="fas fa-spinner fa-spin"></i> Updating...');
                button.prop('disabled', true);

                // Make AJAX call to update redo status
                $.ajax({
                    url: 'controllers/edit_project_ajax.php',
                    type: 'POST',
                    data: {
                        action: 'update_image_redo',
                        image_id: imageId,
                        redo_value: redoValue
                    },
                    success: function (response) {
                        try {
                            const data = JSON.parse(response);

                            if (data.status === 'success') {
                                toastr.success(data.message || 'Image updated successfully');

                                // Update button appearance based on new state
                                if (redoValue === '1') {
                                    button.removeClass('btn-warning').addClass('btn-outline-warning');
                                    button.html('<i class="fas fa-redo"></i> Cancel Redo');
                                    button.data('redo', '0');
                                    button.attr('data-redo', '0');
                                    button.attr('title', 'Cancel Redo Request');
                                } else {
                                    button.removeClass('btn-outline-warning').addClass('btn-warning');
                                    button.html('<i class="fas fa-redo"></i> Redo');
                                    button.data('redo', '1');
                                    button.attr('data-redo', '1');
                                    button.attr('title', 'Mark for Redo');
                                }
                            } else {
                                toastr.error(data.message || 'Error updating image');
                                button.html(originalHtml);
                            }
                        } catch (e) {
                            console.error('Error parsing response:', e);
                            toastr.error('Error processing server response');
                            button.html(originalHtml);
                        }

                        button.prop('disabled', false);
                    },
                    error: function (xhr, status, error) {
                        console.error('AJAX Error:', xhr, status, error);
                        toastr.error('Server error while updating image');
                        button.html(originalHtml);
                        button.prop('disabled', false);
                    }
                });
            }
        });
    });
</script>

<!-- Custom code to fix jQuery issues and redo button -->
<script>
    // Wait for the DOM to be fully loaded
    document.addEventListener('DOMContentLoaded', function () {
        // Make sure jQuery is available
        if (typeof jQuery === 'undefined') {
            console.error('jQuery is not loaded! Please check your includes.');
            return;
        }

        // Now that we know jQuery is available, we can use it
        jQuery(function ($) {
            console.log('Fix script loaded for redo functionality');

            // Handle mark-redo-btn click event
            $(document).on('click', '.mark-redo-btn', function () {
                const imageId = $(this).data('image-id');
                const redoValue = $(this).data('redo');
                const button = $(this);

                // Show confirmation based on action type
                const confirmMessage = redoValue === '1' || redoValue === 1
                    ? 'Are you sure you want to mark this image for redo?'
                    : 'Cancel redo request for this image?';

                if (confirm(confirmMessage)) {
                    // Update button UI to show loading state
                    button.html('<i class="fas fa-spinner fa-spin"></i>');
                    button.prop('disabled', true);

                    // Make AJAX call to update redo status
                    $.ajax({
                        url: 'controllers/edit_project_ajax.php',
                        type: 'POST',
                        data: {
                            action: 'update_image_redo',
                            image_id: imageId,
                            redo_value: redoValue
                        },
                        success: function (response) {
                            try {
                                const data = JSON.parse(response);

                                if (data.status === 'success') {
                                    toastr.success(data.message || 'Image updated successfully');

                                    // Update button appearance and data attributes based on new state
                                    if (redoValue === '1' || redoValue === 1) {
                                        // Going from normal to redo state
                                        button.removeClass('btn-warning').addClass('btn-danger');
                                        button.data('redo', '0');
                                        button.attr('data-redo', '0');
                                        button.html('<i class="fas fa-spinner fa-spin"></i> Cancel Redo');
                                        button.attr('title', 'Cancel Redo Request');
                                    } else {
                                        // Going from redo to normal state
                                        button.removeClass('btn-danger').addClass('btn-warning');
                                        button.data('redo', '1');
                                        button.attr('data-redo', '1');
                                        button.html('<i class="fas fa-redo-alt"></i> Redo');
                                        button.attr('title', 'Mark for Redo');
                                    }
                                } else {
                                    toastr.error(data.message || 'Error updating image');
                                    // Restore button to previous state
                                    if (redoValue === '1' || redoValue === 1) {
                                        button.html('<i class="fas fa-redo-alt"></i> Redo');
                                    } else {
                                        button.html('<i class="fas fa-spinner fa-spin"></i> Cancel Redo');
                                    }
                                }
                            } catch (e) {
                                console.error('Error parsing response:', e);
                                toastr.error('Error processing server response');
                                // Restore button to previous state
                                if (redoValue === '1' || redoValue === 1) {
                                    button.html('<i class="fas fa-redo-alt"></i> Redo');
                                } else {
                                    button.html('<i class="fas fa-spinner fa-spin"></i> Cancel Redo');
                                }
                            }

                            button.prop('disabled', false);
                        },
                        error: function (xhr, status, error) {
                            console.error('AJAX Error:', xhr, status, error);
                            toastr.error('Server error while updating image');
                            // Restore button to previous state
                            if (redoValue === '1' || redoValue === 1) {
                                button.html('<i class="fas fa-redo-alt"></i> Redo');
                            } else {
                                button.html('<i class="fas fa-spinner fa-spin"></i> Cancel Redo');
                            }
                            button.prop('disabled', false);
                        }
                    });
                }
            });
        });
    });
</script>

<!-- Fix for Redo Button Functionality -->
<script>
    document.addEventListener('DOMContentLoaded', function () {
        // Wait a short time to ensure jQuery and other libraries are fully loaded
        setTimeout(function () {
            if (typeof jQuery !== 'undefined') {
                initRedoFunctionality();
            } else {
                console.error("jQuery not loaded - redo functionality cannot be initialized");
            }
        }, 500);

        function initRedoFunctionality() {
            console.log("Initializing redo functionality...");

            // Use jQuery's noConflict mode to avoid conflicts
            jQuery(function ($) {
                // Handle redo button clicks with new event handler
                $(document).off('click', '.mark-redo-btn').on('click', '.mark-redo-btn', function (e) {
                    e.preventDefault();
                    e.stopPropagation();

                    const button = $(this);
                    const imageId = button.data('image-id');
                    const redoValue = button.data('redo');

                    console.log(`Redo button clicked: image_id=${imageId}, redo_value=${redoValue}`);

                    // Confirmation dialog
                    const confirmMessage = redoValue === '1' || redoValue === 1
                        ? 'Are you sure you want to mark this image for redo?'
                        : 'Cancel redo request for this image?';

                    if (!confirm(confirmMessage)) {
                        return;
                    }

                    // Show loading state
                    const originalHtml = button.html();
                    button.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i>');

                    // AJAX call to update redo status
                    $.ajax({
                        url: 'controllers/edit_project_ajax.php',
                        type: 'POST',
                        data: {
                            action: 'update_image_redo',
                            image_id: imageId,
                            redo_value: redoValue
                        },
                        success: function (response) {
                            try {
                                const data = typeof response === 'string' ? JSON.parse(response) : response;

                                if (data.status === 'success') {
                                    // Update the row styling
                                    const row = button.closest('tr');

                                    if (redoValue === '1' || redoValue === 1) {
                                        // Adding redo status
                                        row.addClass('table-danger');

                                        // Add redo badge if it doesn't exist
                                        if (row.find('.badge-danger:contains("Redo Required")').length === 0) {
                                            row.find('td:first').append('<span class="badge badge-danger ml-2">Redo Required</span>');
                                        }

                                        // Update button
                                        button.removeClass('btn-warning').addClass('btn-danger');
                                        button.data('redo', '0');
                                        button.attr('data-redo', '0');
                                        button.attr('title', 'Cancel Redo Request');
                                        button.html('<i class="fas fa-times"></i> Cancel Redo');
                                    } else {
                                        // Removing redo status
                                        row.removeClass('table-danger');

                                        // Remove redo badge
                                        row.find('.badge-danger:contains("Redo Required")').remove();

                                        // Update button
                                        button.removeClass('btn-danger').addClass('btn-warning');
                                        button.data('redo', '1');
                                        button.attr('data-redo', '1');
                                        button.attr('title', 'Mark for Redo');
                                        button.html('<i class="fas fa-redo-alt"></i> Redo');
                                    }

                                    // Show success message
                                    toastr.success(data.message || 'Redo status updated successfully');
                                } else {
                                    // Show error and restore button
                                    toastr.error(data.message || 'Failed to update redo status');
                                    button.html(originalHtml);
                                }
                            } catch (e) {
                                console.error('Error parsing server response:', e, response);
                                toastr.error('Error processing server response');
                                button.html(originalHtml);
                            }

                            // Re-enable button
                            button.prop('disabled', false);
                        },
                        error: function (xhr, status, error) {
                            console.error('AJAX Request Failed:', status, error);
                            toastr.error('Server error: ' + (error || 'Unknown error'));
                            button.html(originalHtml);
                            button.prop('disabled', false);
                        }
                    });
                });

                console.log("Redo functionality initialized successfully");
            });
        }
    });
</script>