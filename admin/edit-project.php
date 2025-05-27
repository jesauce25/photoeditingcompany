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
                                    <button type="button" class="btn btn-primary" data-toggle="modal"
                                        data-target="#addImagesModal">
                                        <i class="fas fa-plus"></i> Add Images
                                    </button>
                                </div>
                                <div class="card-body">
                                    <!-- Batch Actions (initially hidden) -->
                                    <div class="row mb-3" id="batchActions" style="display: none;">
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

                                    <!-- Images Table -->
                                    <div class="table-responsive">
                                        <table class="table table-bordered table-hover" id="imagesTable">
                                            <thead>
                                                <tr>
                                                    <th style="width: 40px;"><input type="checkbox"
                                                            id="selectAllImages"></th>
                                                    <th>Image</th>
                                                    <th>Assignee</th>
                                                    <th>Time</th>
                                                    <th>Role</th>
                                                    <th>Status</th>
                                                    <th>Actions</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php if (empty($images)): ?>
                                                    <tr>
                                                        <td colspan="7" class="text-center">No images uploaded yet.</td>
                                                    </tr>
                                                <?php else: ?>
                                                    <?php foreach ($images as $image): ?>
                                                        <?php
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

                                                        // Determine image status
                                                        $imageStatus = isset($image['status_image']) ? $image['status_image'] : 'available';

                                                        // Determine row class based on redo status and assignment
                                                        $rowClass = '';
                                                        if (isset($image['redo']) && $image['redo'] == '1') {
                                                            $rowClass = 'table-danger';
                                                            // Add a debug comment to verify redo status
                                                            // echo "<!-- Redo status found: " . $image['redo'] . " -->";
                                                        } elseif (isset($image['assignment_id']) && $image['assignment_id'] > 0) {
                                                            $rowClass = 'table-light';
                                                        }
                                                        ?>
                                                        <tr data-image-id="<?php echo $image['image_id']; ?>"
                                                            class="<?php echo $rowClass; ?>">
                                                            <td class="text-center">
                                                                <input type="checkbox" class="image-select"
                                                                    value="<?php echo $image['image_id']; ?>">
                                                            </td>
                                                            <td>
                                                                <a href="../uploads/projects/<?php echo $project_id; ?>/<?php echo $image['image_path']; ?>"
                                                                    target="_blank" class="image-preview-link"
                                                                    title="View Image">
                                                                    <?php echo $fileName; ?>
                                                                </a>
                                                            </td>
                                                            <td>
                                                                <?php
                                                                // Get assignee name from assignments
                                                                $assigneeName = '';
                                                                $assigneeId = 0;
                                                                foreach ($assignments as $assignment) {
                                                                    if (isset($image['assignment_id']) && $assignment['assignment_id'] == $image['assignment_id']) {
                                                                        $assigneeId = $assignment['user_id'];
                                                                        break;
                                                                    }
                                                                }
                                                                ?>
                                                                <select class="form-control form-control-sm assignee-select"
                                                                    data-image-id="<?php echo $image['image_id']; ?>">
                                                                    <option value="">-- Select Assignee --</option>
                                                                    <?php foreach ($graphicArtists as $artist): ?>
                                                                        <option value="<?php echo $artist['user_id']; ?>" <?php echo ($artist['user_id'] == $assigneeId) ? 'selected' : ''; ?>>
                                                                            <?php echo htmlspecialchars($artist['full_name']); ?>
                                                                        </option>
                                                                    <?php endforeach; ?>
                                                                </select>
                                                            </td>
                                                            <td>
                                                                <div class="input-group input-group-sm">
                                                                    <input type="number"
                                                                        class="form-control form-control-sm estimated-hours"
                                                                        value="<?php echo floor(($image['estimated_time'] ?? 0) / 60); ?>"
                                                                        min="0"
                                                                        data-image-id="<?php echo $image['image_id']; ?>"
                                                                        placeholder="Hours" <?php echo (isset($image['assignment_id']) && $image['assignment_id'] > 0) ? '' : ''; ?>>
                                                                    <div class="input-group-append">
                                                                        <span class="input-group-text">h</span>
                                                                    </div>
                                                                    <input type="number"
                                                                        class="form-control form-control-sm estimated-minutes ml-1"
                                                                        value="<?php echo ($image['estimated_time'] ?? 0) % 60; ?>"
                                                                        min="0" max="59"
                                                                        data-image-id="<?php echo $image['image_id']; ?>"
                                                                        placeholder="Min" <?php echo (isset($image['assignment_id']) && $image['assignment_id'] > 0) ? '' : ''; ?>>
                                                                    <div class="input-group-append">
                                                                        <span class="input-group-text">m</span>
                                                                    </div>
                                                                </div>
                                                            </td>
                                                            <td>
                                                                <?php if (!empty($image['image_role']) || (isset($image['assignment_id']) && $image['assignment_id'] > 0)): ?>
                                                                    <select class="form-control form-control-sm role-select"
                                                                        data-image-id="<?php echo $image['image_id']; ?>">
                                                                        <option value="">Select Role</option>
                                                                        <option value="Clipping Path" <?php echo ($image['image_role'] == 'Clipping Path') ? 'selected' : ''; ?>>Clipping Path</option>
                                                                        <option value="Color Correction" <?php echo ($image['image_role'] == 'Color Correction') ? 'selected' : ''; ?>>Color Correction</option>
                                                                        <option value="Retouch" <?php echo ($image['image_role'] == 'Retouch') ? 'selected' : ''; ?>>
                                                                            Retouch</option>
                                                                        <option value="Final" <?php echo ($image['image_role'] == 'Final') ? 'selected' : ''; ?>>
                                                                            Final</option>
                                                                        <option value="Retouch to Final" <?php echo ($image['image_role'] == 'Retouch to Final') ? 'selected' : ''; ?>>Retouch to Final</option>
                                                                    </select>
                                                                <?php else: ?>
                                                                    <select class="form-control form-control-sm image-role-select"
                                                                        data-image-id="<?php echo $image['image_id']; ?>" <?php echo (isset($image['assignment_id']) && $image['assignment_id'] > 0) ? 'disabled' : ''; ?>>
                                                                        <option value="">Select Role</option>
                                                                        <option value="Clipping Path" <?php echo ($image['image_role'] == 'Clipping Path') ? 'selected' : ''; ?>>Clipping Path</option>
                                                                        <option value="Color Correction" <?php echo ($image['image_role'] == 'Color Correction') ? 'selected' : ''; ?>>Color Correction</option>
                                                                        <option value="Retouch" <?php echo ($image['image_role'] == 'Retouch') ? 'selected' : ''; ?>>
                                                                            Retouch</option>
                                                                        <option value="Final" <?php echo ($image['image_role'] == 'Final') ? 'selected' : ''; ?>>
                                                                            Final</option>
                                                                        <option value="Retouch to Final" <?php echo ($image['image_role'] == 'Retouch to Final') ? 'selected' : ''; ?>>Retouch to Final</option>
                                                                    </select>
                                                                <?php endif; ?>
                                                            </td>
                                                            <td>
                                                                <?php if (isset($image['assignment_id']) && $image['assignment_id'] > 0): ?>
                                                                    <?php
                                                                    // Define the timeline steps for image status
                                                                    $timelineSteps = ['available', 'assigned', 'in_progress', 'finish', 'completed'];
                                                                    $currentStatus = $imageStatus;

                                                                    // Determine the current step index
                                                                    $currentStepIndex = array_search($currentStatus, $timelineSteps);
                                                                    if ($currentStepIndex === false)
                                                                        $currentStepIndex = 0;
                                                                    ?>
                                                                    <div class="status-timeline d-flex align-items-center"
                                                                        style="font-size: 0.7rem;">
                                                                        <?php foreach ($timelineSteps as $index => $step):
                                                                            // Skip the "available" status from display
                                                                            if ($step === 'available')
                                                                                continue;

                                                                            $isActive = ($step === 'assigned') || ($index <= $currentStepIndex);
                                                                            $isCurrent = $index == $currentStepIndex;

                                                                            // Determine classes
                                                                            $stepClass = 'status-step';
                                                                            if ($isActive)
                                                                                $stepClass .= ' active';
                                                                            if ($isCurrent)
                                                                                $stepClass .= ' current';

                                                                            // Display connector line for steps after the first (excluding available)
                                                                            if ($step !== 'assigned'): ?>
                                                                                <div
                                                                                    class="status-connector <?php echo $index <= $currentStepIndex ? 'active' : ''; ?>">
                                                                                </div>
                                                                            <?php endif; ?>

                                                                            <div class="<?php echo $stepClass; ?>"
                                                                                data-image-id="<?php echo $image['image_id']; ?>"
                                                                                data-status="<?php echo $step; ?>"
                                                                                title="<?php echo ucfirst(str_replace('_', ' ', $step)); ?>">
                                                                                <?php
                                                                                // Display P for assigned, otherwise first letter
                                                                                if ($step === 'assigned') {
                                                                                    echo 'P';
                                                                                } else {
                                                                                    echo substr(ucfirst($step), 0, 1);
                                                                                }
                                                                                ?>
                                                                            </div>
                                                                        <?php endforeach; ?>
                                                                    </div>


                                                                <?php else: ?>
                                                                    <span class="text-muted">Not assigned</span>
                                                                <?php endif; ?>
                                                            </td>
                                                            <td>
                                                                <div class="btn-group btn-group-sm">
                                                                    <button type="button" class="btn btn-danger delete-image"
                                                                        data-image-id="<?php echo $image['image_id']; ?>"
                                                                        title="Delete Image">
                                                                        <i class="fas fa-trash"></i>
                                                                    </button>
                                                                    <?php if (isset($image['redo']) && $image['redo'] == '1'): ?>
                                                                        <span class="redo-badge ml-2">REDO</span>
                                                                    <?php endif; ?>
                                                                </div>
                                                            </td>
                                                        </tr>
                                                    <?php endforeach; ?>
                                                <?php endif; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <style>
                        /* Aggressive Table Compression CSS - Minimizes row height to absolute minimum */

                        /* Set extremely small table cell padding */
                        #imagesTable td,
                        #imagesTable th {
                            padding: 1px !important;
                            vertical-align: middle !important;
                            height: auto !important;
                            line-height: 1 !important;
                        }

                        /* Force minimal row height */
                        #imagesTable tr {
                            height: 20px !important;
                            max-height: 20px !important;
                            line-height: 1 !important;
                        }

                        /* Reduce font size for the entire table */
                        #imagesTable {
                            font-size: 0.7rem !important;
                            line-height: 1 !important;
                            border-collapse: collapse !important;
                            border-spacing: 0 !important;
                        }

                        /* Extremely compress form controls */
                        #imagesTable .form-control,
                        #imagesTable .form-control-sm {
                            height: 18px !important;
                            min-height: 18px !important;
                            padding: 0 0.2rem !important;
                            font-size: 0.7rem !important;
                            line-height: 1 !important;
                            margin: 0 !important;
                        }

                        /* Extremely compress select dropdowns */
                        #imagesTable select.form-control,
                        #imagesTable select.form-control-sm {
                            height: 18px !important;
                            min-height: 18px !important;
                            padding: 0 0.2rem !important;
                            font-size: 0.7rem !important;
                            line-height: 1 !important;
                            margin: 0 !important;
                        }

                        /* Extremely compress input groups */
                        #imagesTable .input-group {
                            height: 18px !important;
                            min-height: 18px !important;
                            line-height: 1 !important;
                        }

                        #imagesTable .input-group-sm>.form-control,
                        #imagesTable .input-group-sm>.input-group-append>.input-group-text {
                            height: 18px !important;
                            min-height: 18px !important;
                            padding: 0 0.2rem !important;
                            font-size: 0.7rem !important;
                            line-height: 1 !important;
                            margin: 0 !important;
                        }

                        /* Extremely compress buttons */
                        #imagesTable .btn,
                        #imagesTable .btn-sm {
                            padding: 0 0.2rem !important;
                            font-size: 0.7rem !important;
                            line-height: 1 !important;
                            height: 18px !important;
                            min-height: 18px !important;
                            margin: 0 !important;
                        }

                        /* Extremely compress status timeline */
                        #imagesTable .status-timeline {
                            height: 16px !important;
                            font-size: 0.6rem !important;
                            line-height: 1 !important;
                            margin: 0 !important;
                            padding: 0 !important;
                        }

                        #imagesTable .status-step {
                            width: 12px !important;
                            height: 12px !important;
                            line-height: 1 !important;
                            font-size: 0.6rem !important;
                            margin: 0 !important;
                            padding: 0 !important;
                        }

                        #imagesTable .status-connector {
                            height: 1px !important;
                            width: 4px !important;
                            margin: 0 !important;
                            padding: 0 !important;
                        }

                        /* Extremely compress icons */
                        #imagesTable .fas {
                            font-size: 0.7rem !important;
                            line-height: 1 !important;
                            margin: 0 !important;
                            padding: 0 !important;
                        }

                        /* Compress card header and body */
                        .card-header {
                            padding: 0.25rem 0.5rem !important;
                            line-height: 1 !important;
                        }

                        .card-body {
                            padding: 0.25rem !important;
                            line-height: 1 !important;
                        }

                        /* Compress badges */
                        #imagesTable .badge {
                            padding: 0.1em 0.3em !important;
                            font-size: 0.65em !important;
                            line-height: 1 !important;
                            margin: 0 !important;
                        }

                        /* Compress button groups */
                        #imagesTable .btn-group-sm>.btn {
                            padding: 0 0.2rem !important;
                            font-size: 0.7rem !important;
                            line-height: 1 !important;
                            height: 18px !important;
                            min-height: 18px !important;
                            margin: 0 !important;
                        }

                        /* Remove all margins between elements */
                        #imagesTable .mb-3,
                        #imagesTable .my-3 {
                            margin-bottom: 0 !important;
                        }

                        #imagesTable .mt-3,
                        #imagesTable .my-3 {
                            margin-top: 0 !important;
                        }

                        #imagesTable .ml-3,
                        #imagesTable .mx-3 {
                            margin-left: 0 !important;
                        }

                        #imagesTable .mr-3,
                        #imagesTable .mx-3 {
                            margin-right: 0 !important;
                        }

                        /* Compress checkboxes */
                        #imagesTable input[type="checkbox"] {
                            width: 12px !important;
                            height: 12px !important;
                            margin: 0 !important;
                            padding: 0 !important;
                        }

                        /* Remove any extra spacing */
                        #imagesTable * {
                            margin-top: 0 !important;
                            margin-bottom: 0 !important;
                        }

                        /* Force table to use minimal spacing */
                        #imagesTable.table-bordered {
                            border-collapse: collapse !important;
                        }

                        /* Ensure images don't add extra height */
                        #imagesTable img {
                            max-height: 18px !important;
                            height: auto !important;
                        }

                        /* Ensure links don't add extra height */
                        #imagesTable a {
                            line-height: 1 !important;
                        }

                        /* Ensure div containers don't add extra height */
                        #imagesTable div {
                            line-height: 1 !important;
                            margin: 0 !important;
                            padding: 0 !important;
                        }

                        /* Remove any Bootstrap spacing classes that might be adding height */
                        #imagesTable .py-1,
                        #imagesTable .py-2,
                        #imagesTable .py-3,
                        #imagesTable .pt-1,
                        #imagesTable .pt-2,
                        #imagesTable .pt-3,
                        #imagesTable .pb-1,
                        #imagesTable .pb-2,
                        #imagesTable .pb-3 {
                            padding-top: 0 !important;
                            padding-bottom: 0 !important;
                        }

                        /* Ensure table is as compact as possible */
                        #imagesTable.table-responsive {
                            padding: 0 !important;
                            margin: 0 !important;
                        }
                    </style>
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
                                                                <?php
                                                                // New logic: Get all image roles from this assignment's images
                                                                $assignmentId = $assignment['assignment_id'];
                                                                $sql = "SELECT DISTINCT image_role FROM tbl_project_images 
                                                                       WHERE assignment_id = ? AND image_role IS NOT NULL AND image_role != ''";
                                                                $stmt = $conn->prepare($sql);
                                                                $stmt->bind_param("i", $assignmentId);
                                                                $stmt->execute();
                                                                $result = $stmt->get_result();

                                                                $imageRoles = [];
                                                                while ($row = $result->fetch_assoc()) {
                                                                    if (!empty($row['image_role'])) {
                                                                        $imageRoles[] = $row['image_role'];
                                                                    }
                                                                }

                                                                // If no image roles found, fall back to the assignment role_task
                                                                if (empty($imageRoles) && !empty($assignment['role_task'])) {
                                                                    $imageRoles[] = $assignment['role_task'];
                                                                }

                                                                // Display roles as badges
                                                                if (!empty($imageRoles)) {
                                                                    echo '<div class="d-flex flex-wrap">';
                                                                    foreach ($imageRoles as $role) {
                                                                        $roleClass = '';
                                                                        $roleAbbr = '';

                                                                        // Assign color classes based on role type
                                                                        switch ($role) {
                                                                            case 'Clipping Path':
                                                                                $roleClass = 'badge-primary';
                                                                                $roleAbbr = 'CP';
                                                                                break;
                                                                            case 'Color Correction':
                                                                                $roleClass = 'badge-warning';
                                                                                $roleAbbr = 'CC';
                                                                                break;
                                                                            case 'Retouch':
                                                                                $roleClass = 'badge-success';
                                                                                $roleAbbr = 'R';
                                                                                break;
                                                                            case 'Final':
                                                                                $roleClass = 'badge-info';
                                                                                $roleAbbr = 'F';
                                                                                break;
                                                                            case 'Retouch to Final':
                                                                                $roleClass = 'badge-secondary';
                                                                                $roleAbbr = 'RF';
                                                                                break;
                                                                            default:
                                                                                $roleClass = 'badge-dark';
                                                                                $roleAbbr = substr($role, 0, 2);
                                                                                break;
                                                                        }

                                                                        echo '<span class="badge ' . $roleClass . ' mr-1 mb-1 p-2" title="' . htmlspecialchars($role) . '">' . $roleAbbr . '</span>';
                                                                    }
                                                                    echo '</div>';
                                                                } else {
                                                                    echo '<span class="text-muted">No roles assigned</span>';
                                                                }
                                                                ?>
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
                                                                </div>
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
                                                                            title="<?php echo ucfirst(str_replace('_', ' ', $step)); ?> (View Only)">
                                                                            <?php echo substr(ucfirst($step), 0, 1); ?>
                                                                        </div>
                                                                    <?php endforeach; ?>
                                                                </div>

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

                                                                // Check if assignment is marked as understandable
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
                            <option value="Retouch to Final">Retouch to Final</option>
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


<!-- Add toastr for notifications -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css">
<script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>


<!-- First define global variables that components will need -->
<script>
    window.projectId = <?php echo json_encode($project_id); ?>;

    window.logging = {
        debug: function(message, data = null) {
            console.debug(`[DEBUG][${new Date().toISOString()}] ${message}`, data || '');
        },
        info: function(message, data = null) {
            console.info(`[INFO][${new Date().toISOString()}] ${message}`, data || '');
        },
        warning: function(message, data = null) {
            console.warn(`[WARNING][${new Date().toISOString()}] ${message}`, data || '');
        },
        error: function(message, data = null) {
            console.error(`[ERROR][${new Date().toISOString()}] ${message}`, data || '');
        },
        interaction: function(action, data = null) {
            console.log(`[USER ACTION][${new Date().toISOString()}] ${action}`, data || '');
            if (window.navigator.sendBeacon) {
                try {
                    const logData = {
                        action: action,
                        data: data || {},
                        timestamp: new Date().toISOString(),
                        page: 'edit-project',
                        projectId: window.projectId
                    };
                    navigator.sendBeacon('controllers/log_client_action.php', JSON.stringify(logData));
                } catch (e) {
                    console.error('Error sending beacon log:', e);
                }
            }
        },
        ajax: function(method, url, data = null) {
            console.log(`[AJAX REQUEST][${new Date().toISOString()}] ${method} ${url}`, data || '');
        },
        ajaxSuccess: function(method, url, response = null) {
            console.log(`[AJAX SUCCESS][${new Date().toISOString()}] ${method} ${url}`, response || '');
        },
        ajaxError: function(method, url, error = null) {
            console.error(`[AJAX ERROR][${new Date().toISOString()}] ${method} ${url}`, error || '');
        }
    };

    // Function to get all selected image IDs - make it global so all components can use it  
    window.getSelectedImageIds = function() {
        const selectedImages = [];
        $('.image-select:checked').each(function() {
            selectedImages.push($(this).val());
        });
        return selectedImages;
    };
</script>
<!-- Now include component scripts -->
<script src="assets/components/edit-project/project-details.js"></script>
<script src="assets/components/edit-project/project-images.js"></script>
<script src="assets/components/edit-project/team-assignment.js"></script>
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

<style>
    /* Status Timeline Styles - Match with view-task.php */
    .status-timeline-container {
        padding: 10px 0;
    }

    .status-timeline {
        display: flex;
        align-items: center;
        position: relative;
    }

    .status-step {
        width: 28px;
        height: 28px;
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
        font-size: 0.9rem;
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

    /* Completed status styling */
    .status-step[data-status="completed"] {
        background-color: #007bff;
        /* Default blue color */
        color: white;
    }

    /* Only make completed status green when it's the current status */
    .status-step[data-status="completed"].current {
        background-color: #28a745;
        /* Green color for current completed status */
        border: 2px solid #1e7e34;
    }

    .redo-badge {
        background-color: #dc3545;
        color: white;
        padding: 2px 6px;
        border-radius: 3px;
        font-size: 0.7rem;
        font-weight: bold;
        display: inline-block;
        margin-left: 5px;
        vertical-align: middle;
    }
</style>