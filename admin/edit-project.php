<?php

/**
 * Edit Project (Refactored)
 * A more maintainable version of the edit project page
 */

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

// Fetch project data from database
$project = getProjectById($project_id);

if (!$project) {
    $_SESSION['error_message'] = "Project not found";
    header("Location: project-list.php");
    exit();
}

// Get companies for dropdown
$companies = getCompaniesForDropdown();

// Get all project images
$images = getProjectImages($project_id);
error_log("Found " . count($images) . " images for project ID " . $project_id);

// Get project assignments
$assignments = getProjectAssignments($project_id);
error_log("Found " . count($assignments) . " assignments for project ID " . $project_id);

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
                console.debug(`[DEBUG] ${message}`, data || '');
            },
            info: function (message, data = null) {
                console.info(`[INFO] ${message}`, data || '');
            },
            warn: function (message, data = null) {
                console.warn(`[WARNING] ${message}`, data || '');
            },
            error: function (message, data = null) {
                console.error(`[ERROR] ${message}`, data || '');
            },
            interaction: function (action, data = null) {
                console.log(`[USER ACTION] ${action}`, data || '');
            },
            ajax: function (type, url, data = null) {
                console.log(`[AJAX ${type}] ${url}`, data || '');
            },
            ajaxSuccess: function (type, url, response = null) {
                console.log(`[AJAX ${type} SUCCESS] ${url}`, response || '');
            },
            ajaxError: function (type, url, error = null) {
                console.error(`[AJAX ${type} ERROR] ${url}`, error || '');
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

    .card-img-top {
        height: 120px;
        object-fit: cover;
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
                                <div class="progress">
                                    <div class="progress-bar bg-success" role="progressbar"
                                        style="width: <?php echo $projectProgress['percent_complete']; ?>%"
                                        aria-valuenow="<?php echo $projectProgress['percent_complete']; ?>"
                                        aria-valuemin="0" aria-valuemax="100">
                                        <?php echo $projectProgress['percent_complete']; ?>% Complete
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
                                    <h5 class="mb-0">Project Images</h5>
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
                                                ?>
                                                <div class="col-md-2 col-sm-3 col-4 mb-2">
                                                    <div class="image-container"
                                                        data-image-id="<?php echo $image['image_id']; ?>">
                                                        <div class="card h-100">
                                                            <div class="image-selection-indicator">
                                                                <i class="fas fa-check-circle"></i>
                                                            </div>
                                                            <?php
                                                            $imagePath = '../../uploads/project_images/' . $image['image_path'];
                                                            $imageExists = @getimagesize($imagePath) ? true : false;

                                                            if ($imageExists) {
                                                                // Display actual image if it exists
                                                                echo '<img src="' . $imagePath . '" class="card-img-top" alt="Project Image">';
                                                            } else {
                                                                // Display a placeholder with nice styling if image doesn't exist
                                                                echo '<div class="card-img-top d-flex align-items-center justify-content-center bg-light" style="height: 120px;">';
                                                                echo '<i class="fas fa-file-image text-primary" style="font-size: 2rem;"></i>';
                                                                echo '</div>';
                                                            }
                                                            ?>
                                                            <div class="card-body p-2">
                                                                <?php if (!empty($image['image_role']) || !empty($image['estimated_time'])): ?>
                                                                    <div class="image-details mb-1">
                                                                        <?php if (!empty($image['image_role'])): ?>
                                                                            <span class="badge badge-info mr-1">
                                                                                <?php echo htmlspecialchars($image['image_role']); ?>
                                                                            </span>
                                                                        <?php endif; ?>

                                                                        <?php if (!empty($image['estimated_time'])): ?>
                                                                            <span class="badge badge-secondary">
                                                                                <i class="far fa-clock mr-1"></i>
                                                                                <?php
                                                                                $time = intval($image['estimated_time']);
                                                                                if ($time >= 60) {
                                                                                    $hours = floor($time / 60);
                                                                                    $minutes = $time % 60;
                                                                                    echo $hours . 'hr' . ($minutes > 0 ? ' ' . $minutes . 'min' : '');
                                                                                } else {
                                                                                    echo $time . ' min';
                                                                                }
                                                                                ?>
                                                                            </span>
                                                                        <?php endif; ?>
                                                                    </div>
                                                                <?php endif; ?>

                                                                <small class="text-truncate d-block"
                                                                    title="<?php echo $fileName; ?>">
                                                                    <?php echo $fileName; ?>
                                                                </small>

                                                                <div
                                                                    class="d-flex justify-content-between align-items-center mt-1">
                                                                    <span class="badge <?php echo $statusClass; ?>">
                                                                        <?php echo $statusText; ?>
                                                                    </span>
                                                                    <button class="btn btn-xs btn-danger delete-image"
                                                                        data-id="<?php echo $image['image_id']; ?>">
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
                                                                                <?php echo $artist['full_name']; ?>
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
                                                                <?php if ($assignment['assigned_images'] > 0): ?>
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
                                                                        <button type="button"
                                                                            class="btn btn-sm btn-outline-danger remove-assigned-images"
                                                                            data-assignment-id="<?php echo $assignment['assignment_id']; ?>"
                                                                            title="Remove Assigned Images" <?php echo $assignment['status_assignee'] != 'pending' ? 'disabled' : ''; ?>>
                                                                            <i class="fas fa-trash-alt"></i>
                                                                        </button>
                                                                    </div>
                                                                    <?php if ($assignment['status_assignee'] != 'pending'): ?>
                                                                        <div class="mt-1">
                                                                            <small class="text-muted">
                                                                                <i class="fas fa-lock mr-1"></i> Some actions locked
                                                                            </small>
                                                                        </div>
                                                                    <?php endif; ?>
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
                                                                    $deadline_status = 'Overdue';
                                                                    $badge_class = 'badge-danger';
                                                                }
                                                                ?>
                                                                <div class="deadline-container">
                                                                    <input type="date" class="form-control deadline-input"
                                                                        value="<?php echo $assignment['deadline']; ?>"
                                                                        data-assignment-id="<?php echo $assignment['assignment_id']; ?>"
                                                                        <?php echo $assignment['status_assignee'] != 'pending' ? 'disabled' : ''; ?>>
                                                                    <?php if (!empty($deadline_status)): ?>
                                                                        <span class="badge <?php echo $badge_class; ?> ml-2">
                                                                            <?php echo $deadline_status; ?>
                                                                        </span>
                                                                    <?php endif; ?>
                                                                    <?php if ($assignment['status_assignee'] != 'pending'): ?>
                                                                        <div class="mt-1">
                                                                            <span class="badge badge-info">
                                                                                <i class="fas fa-lock mr-1"></i> Locked
                                                                            </span>
                                                                        </div>
                                                                    <?php endif; ?>
                                                                </div>
                                                            </td>
                                                            <td>
                                                                <button class="btn btn-sm btn-danger delete-assignment"
                                                                    data-assignment-id="<?php echo $assignment['assignment_id']; ?>"
                                                                    <?php echo $assignment['status_assignee'] != 'pending' ? 'disabled' : ''; ?>>
                                                                    <i class="fas fa-trash"></i>
                                                                </button>
                                                                <?php if ($assignment['status_assignee'] != 'pending'): ?>
                                                                    <div class="mt-1">
                                                                        <span class="badge badge-info">
                                                                            <i class="fas fa-lock mr-1"></i> Locked
                                                                        </span>
                                                                    </div>
                                                                <?php endif; ?>
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

    <!-- Footer -->
    <?php include("includes/footer.php"); ?>
</div>

<!-- Add toastr for notifications -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css">
<script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>

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
            console.debug(`[DEBUG] ${message}`, data || '');
        },
        info: function (message, data = null) {
            console.info(`[INFO] ${message}`, data || '');
        },
        warn: function (message, data = null) {
            console.warn(`[WARNING] ${message}`, data || '');
        },
        error: function (message, data = null) {
            console.error(`[ERROR] ${message}`, data || '');
        },
        interaction: function (action, details = null) {
            console.log(`[USER ACTION] ${action}`, details || '');
        },
        ajax: function (type, url, data = null) {
            console.log(`[AJAX ${type}] ${url}`, data || '');
        },
        ajaxSuccess: function (type, url, response = null) {
            console.log(`[AJAX ${type} SUCCESS] ${url}`, response || '');
        },
        ajaxError: function (type, url, error = null) {
            console.error(`[AJAX ${type} ERROR] ${url}`, error || '');
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
                                <img src="${e.target.result}" class="card-img-top" alt="Preview">
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
                                <div class="table-responsive">
                                    <table class="table table-bordered table-hover" id="assigned-images-table">
                                        <thead>
                                            <tr>
                                                <th>Image Name</th>
                                                <th>Estimated Time</th>
                                                <th>Role</th>
                                                <th>Actions</th>
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
                                        <tr data-image-id="${image.image_id}">
                                            <td>
                                                <a href="${imageUrl}" target="_blank" class="image-preview-link">
                                                    ${fileName}
                                                </a>
                                            </td>
                                            <td>
                                                <div class="row">
                                                    <div class="col-5">
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
                                                    <div class="col-5">
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
                                            <td>
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
                                            <td>
                                                ${statusEditable ? `
                                                <button type="button" class="btn btn-sm btn-primary save-image-details" 
                                                        data-image-id="${image.image_id}">
                                                    <i class="fas fa-save"></i> Save
                                                </button>` :
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
                                    <div class="modal-dialog modal-lg" role="document">
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
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            `;

                            // Add modal to body and show it
                            $('body').append(modalHtml);
                            $('#viewAssignedImagesModal').modal('show');

                            // Handle save image details click
                            $('.save-image-details').click(function () {
                                const imageId = $(this).data('image-id');
                                const row = $(`tr[data-image-id="${imageId}"]`);
                                const estimatedHours = row.find('.estimated-hours-input').val();
                                const estimatedMinutes = row.find('.estimated-minutes-input').val();
                                const imageRole = row.find('.image-role-select').val();

                                $.ajax({
                                    url: 'controllers/edit_project_ajax.php',
                                    type: 'POST',
                                    data: {
                                        action: 'update_image_details',
                                        image_id: imageId,
                                        estimated_hours: estimatedHours,
                                        estimated_minutes: estimatedMinutes,
                                        image_role: imageRole
                                    },
                                    success: function (updateResponse) {
                                        try {
                                            const updateData = JSON.parse(updateResponse);
                                            if (updateData.status === 'success') {
                                                showToast('success', 'Image details updated successfully');
                                            } else {
                                                showToast('error', updateData.message || 'Failed to update image details');
                                            }
                                        } catch (e) {
                                            console.error('Error parsing update response:', e);
                                            showToast('error', 'Error updating image details');
                                        }
                                    },
                                    error: function () {
                                        showToast('error', 'Server error while updating image details');
                                    }
                                });
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
                        // Update the assigned images count without removing the buttons
                        // Find the cell that contains the image count and update only that text
                        var imageCountElement = button.closest('tr').find('td:nth-child(3)');
                        imageCountElement.text('0 Images');

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

            // Get the index of the current and new status
            const timelineSteps = ['pending', 'in_progress', 'finish', 'qa', 'approved', 'completed'];
            const currentIndex = timelineSteps.indexOf(currentStatus);
            const newIndex = timelineSteps.indexOf(newStatus);

            // Only allow moving to the next step or going backwards
            if (newIndex > currentIndex + 1) {
                toastr.warning('You can only advance one step at a time in the workflow');
                return;
            }

            // Update the assignment status
            updateAssignmentStatus(assignmentId, newStatus);
        });

        // Handle approve task button click
        $(document).on('click', '.approve-task-btn', function () {
            const assignmentId = $(this).data('assignment-id');

            // Update to 'completed' status
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
    });
</script>

<?php include("includes/footer.php"); ?>