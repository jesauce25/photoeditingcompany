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

// Direct form handling - if it's a POST request, process it right here
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['project_id'])) {
    // Include the update project controller
    require_once 'controllers/update_project.php';
    // The update_project.php script will handle the form submission and exit
    exit;
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
<!-- 
<script>
    // Override Sparkline plugin to handle empty data gracefully
    $(document).ready(function () {
        if ($.fn.sparkline) {
            var originalSparkline = $.fn.sparkline;
            $.fn.sparkline = function (data, options) {
                var element = this;
                // Handle empty or invalid data case
                if (!data || (Array.isArray(data) && data.length === 0) || data === '0,0') {
                    console.log("Empty sparkline data detected, using fallback");
                    // Draw a flat line at the bottom
                    data = [0, 0];
                    options = options || {};
                    options = {
                        ...options,
                        disableHighlight: true,
                        disableTooltips: true
                    };
                }
                return new originalSparkline(element, options);
            };
        }
    });

    </script> -->

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
                        <?php if (!empty($assignments)): ?>
                                    <div class="mt-2 d-flex align-items-center">
                                        <span class="text-muted mr-2">Team: </span>
                                        <div class="d-flex flex-wrap">
                                            <?php
                                            // Extract first names of all assignees for this project
                                            $team = array();
                                            foreach ($assignments as $assignment) {
                                                if (isset($assignment['first_name'])) {
                                                    $is_overdue = false;
                                                    if (isset($assignment['deadline'])) {
                                                        $deadline = new DateTime($assignment['deadline']);
                                                        $today = new DateTime();
                                                        $is_overdue = $deadline < $today;
                                                    }
                                                    $team[] = array(
                                                        'name' => $assignment['first_name'],
                                                        'is_overdue' => $is_overdue
                                                    );
                                                }
                                            }

                                                    // Display team members with badges
                                                    foreach ($team as $member):
                                                        $badge_class = $member['is_overdue'] ? 'badge-danger' : 'badge-primary';
                                                        ?>
                                                        <span class="badge <?php echo $badge_class; ?> mr-1 mb-1 p-2">
                                                            <i class="fas fa-user-circle mr-1"></i>
                                                            <?php echo htmlspecialchars($member['name']); ?>
                                                        </span>
                                                    <?php endforeach; ?>
                                        </div>
                                    </div>
                        <?php endif; ?>
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
                                            value="<?php echo htmlspecialchars($project['project_title']); ?>">
                                    </div>
                                    <div class="form-group">
                                        <label for="company">Company</label>
                                        <select class="form-control" id="company" name="company" required>
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
                                        <textarea class="form-control" id="description" name="description"
                                            rows="4"><?php echo htmlspecialchars($project['description']); ?></textarea>
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
                                        <select class="form-control" id="priority" name="priority" required>
                                            <option value="High" <?php echo ($project['priority'] == 'High') ? 'selected' : ''; ?>>High</option>
                                            <option value="Medium" <?php echo ($project['priority'] == 'Medium') ? 'selected' : ''; ?>>Medium</option>
                                            <option value="Low" <?php echo ($project['priority'] == 'Low') ? 'selected' : ''; ?>>Low</option>
                                        </select>
                                    </div>
                                    <div class="form-group">
                                        <label for="dateArrived">Start Date</label>
                                        <input type="date" class="form-control" id="dateArrived" name="dateArrived"
                                            value="<?php echo $project['date_arrived']; ?>" required>
                                    </div>
                                    <div class="form-group">
                                        <label for="deadline">Deadline</label>
                                        <input type="date" class="form-control" id="deadline" name="deadline"
                                            value="<?php echo $project['deadline']; ?>" required>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Project Images Section -->
                    <div class="row mb-4">
                        <div class="col-12">
                            <div class="card">
                                <div
                                    class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                                    <h3 class="card-title">
                                        <i class="fas fa-images mr-2"></i>
                                        Project Images
                                        <span class="badge badge-light ml-2"><?php echo count($images); ?></span>
                                    </h3>
                                    <div class="ml-auto">
                                        <button type="button" class="btn btn-sm btn-light shadow-sm float-right"
                                            data-toggle="modal" data-target="#addImagesModal">
                                            <i class="fas fa-plus mr-1"></i> Add New Images
                                        </button>
                                    </div>
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
                                                                            <div class="image-card-body p-1">
                                                                                <div class="image-file-name small text-truncate" title="<?php echo htmlspecialchars($fileName); ?>">
                                                                                    <?php echo htmlspecialchars($fileName); ?>
                                                                                </div>
                                                                                <div class="image-actions mt-1">
                                                                                    <button type="button"
                                                                                        class="btn btn-sm btn-outline-danger delete-image btn-sm"
                                                                                        data-image-id="<?php echo $image['image_id']; ?>">
                                                                                        <i class="fas fa-trash-alt fa-xs"></i>
                                                                                    </button>
                                                                                    <span
                                                                                        class="badge <?php echo $statusClass; ?> image-status">
                                                                                        <?php echo $statusText; ?>
                                                                                    </span>
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

                    <!-- Team Assignments Section -->
                    <div class="row mb-4">
                        <div class="col-12">
                            <div class="card">
                                <div
                                    class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                                    <h3 class="card-title">
                                        <i class="fas fa-users mr-2"></i>
                                        Team Assignments
                                    </h3>
                                    <div class="ml-auto">
                                        <button type="button" class="btn btn-sm btn-light shadow-sm float-right"
                                            data-toggle="modal" data-target="#addAssignmentModal">
                                            <i class="fas fa-plus mr-1"></i> Add New Assignment
                                        </button>
                                    </div>
                                </div>
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table class="table table-bordered table-hover">
                                            <thead class="thead-light">
                                                <tr>
                                                    <th style="width: 20%">Team Member</th>
                                                    <th style="width: 15%">Role</th>
                                                    <th style="width: 20%">Assigned Images</th>
                                                    <th style="width: 15%">Status</th>
                                                    <th style="width: 15%">Deadline</th>
                                                    <th style="width: 15%">Actions</th>
                                                </tr>
                                            </thead>
                                            <tbody id="assignmentsTable">
                                                <?php if (empty($assignments)): ?>
                                                            <tr>
                                                                <td colspan="6" class="text-center">No team members assigned yet.
                                                                </td>
                                                            </tr>
                                                <?php else: ?>
                                                            <?php foreach ($assignments as $assignment): ?>
                                                                        <tr data-assignment-id="<?php echo $assignment['assignment_id']; ?>">
                                                                            <td>
                                                                                <div class="d-flex align-items-center">
                                                                                    <i class="fas fa-user-circle text-primary mr-2"></i>
                                                                                    <select class="form-control form-control-sm assignee-select"
                                                                                        data-assignment-id="<?php echo $assignment['assignment_id']; ?>"
                                                                                        data-original-value="<?php echo $assignment['user_id']; ?>">
                                                                                        <?php foreach ($graphicArtists as $artist): ?>
                                                                                                    <option value="<?php echo $artist['user_id']; ?>" <?php echo ($assignment['user_id'] == $artist['user_id']) ? 'selected' : ''; ?>>
                                                                                                        <?php echo htmlspecialchars($artist['full_name']); ?>
                                                                                                    </option>
                                                                                        <?php endforeach; ?>
                                                                                    </select>
                                                                                </div>
                                                                            </td>
                                                                            <td>
                                                                                <select class="form-control form-control-sm role-select"
                                                                                    data-assignment-id="<?php echo $assignment['assignment_id']; ?>"
                                                                                    data-original-value="<?php echo htmlspecialchars($assignment['role_task']); ?>">
                                                                                    <option value="Clipping path" <?php echo ($assignment['role_task'] == 'Clipping path') ? 'selected' : ''; ?>>Clipping path</option>
                                                                                    <option value="Color Correction" <?php echo ($assignment['role_task'] == 'Color Correction') ? 'selected' : ''; ?>>Color Correction</option>
                                                                                    <option value="Retouch" <?php echo ($assignment['role_task'] == 'Retouch') ? 'selected' : ''; ?>>Retouch</option>
                                                                                    <option value="Final" <?php echo ($assignment['role_task'] == 'Final') ? 'selected' : ''; ?>>Final</option>
                                                                                </select>
                                                                            </td>
                                                                            <td>
                                                                                <div class="d-flex align-items-center">
                                                                                    <span class="badge badge-info badge-pill mr-2">
                                                                                        <?php echo isset($assignment['assigned_image_count']) ? $assignment['assigned_image_count'] : 0; ?>
                                                                                    </span>
                                                                                    <button type="button"
                                                                                        class="btn btn-sm btn-outline-primary view-assigned-images"
                                                                                        data-assignment-id="<?php echo $assignment['assignment_id']; ?>"
                                                                                        data-toggle="tooltip" title="View assigned images">
                                                                                        <i class="fas fa-images"></i> View
                                                                                    </button>
                                                                                    <button type="button"
                                                                                        class="btn btn-sm btn-outline-success ml-1 add-more-images"
                                                                                        data-assignment-id="<?php echo $assignment['assignment_id']; ?>"
                                                                                        data-toggle="tooltip" title="Add more images">
                                                                                        <i class="fas fa-plus"></i>
                                                                                    </button>
                                                                                </div>
                                                                            </td>
                                                                            <td>
                                                                                <select class="form-control form-control-sm assignment-status-select"
                                                                                    data-assignment-id="<?php echo $assignment['assignment_id']; ?>">
                                                                                    <option value="pending" <?php echo ($assignment['status'] == 'pending') ? 'selected' : ''; ?>>Pending</option>
                                                                                    <option value="in_progress" <?php echo ($assignment['status'] == 'in_progress') ? 'selected' : ''; ?>>
                                                                                        <?php echo isset($assignment['first_name']) ? $assignment['first_name'] : 'In Progress'; ?>
                                                                                    </option>
                                                                                    <option value="completed" <?php echo ($assignment['status'] == 'completed') ? 'selected' : ''; ?>>Completed</option>
                                                                                    <option value="review" <?php echo ($assignment['status'] == 'review') ? 'selected' : ''; ?>>Review</option>
                                                                                </select>
                                                                            </td>
                                                                            <td>
                                                                                <input type="date"
                                                                                    class="form-control form-control-sm deadline-input"
                                                                                    data-assignment-id="<?php echo $assignment['assignment_id']; ?>"
                                                                                    value="<?php echo $assignment['deadline']; ?>">
                                                                                <?php if (isset($assignment['deadline_status']) && $assignment['deadline_status'] === 'today'): ?>
                                                                                            <span class="badge badge-warning mt-1 w-100"><?php echo $assignment['deadline_text']; ?></span>
                                                                                <?php elseif (isset($assignment['deadline_status']) && $assignment['deadline_status'] === 'overdue'): ?>
                                                                                            <span class="badge badge-danger mt-1 w-100"><?php echo $assignment['deadline_text']; ?></span>
                                                                                <?php elseif (isset($assignment['deadline_status']) && $assignment['deadline_status'] === 'upcoming' && !empty($assignment['deadline_text'])): ?>
                                                                                            <span class="badge badge-info mt-1 w-100"><?php echo $assignment['deadline_text']; ?></span>
                                                                                <?php endif; ?>
                                                                            </td>
                                                                            <td>
                                                                                <button type="button"
                                                                                    class="btn btn-sm btn-danger delete-assignment"
                                                                                    data-assignment-id="<?php echo $assignment['assignment_id']; ?>"
                                                                                    data-toggle="tooltip" title="Delete assignment">
                                                                                    <i class="fas fa-trash"></i>
                                                                                </button>
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

                    <!-- Submit Button -->
                    <div class="row mb-4">
                        <div class="col-12">
                            <button type="submit" class="btn btn-primary btn-lg">
                                <i class="fas fa-save mr-2"></i> Save Project
                            </button>
                            <a href="project-list.php" class="btn btn-secondary btn-lg ml-2">
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
                            <option value="Clipping path">Clipping path</option>
                            <option value="Color Correction">Color Correction</option>
                            <option value="Retouch">Retouch</option>
                            <option value="Final">Final</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="assignmentDeadline">Deadline</label>
                        <input type="date" class="form-control" id="assignmentDeadline" name="deadline"
                            min="<?php echo date('Y-m-d'); ?>"
                            value="<?php echo date('Y-m-d', strtotime('+7 days')); ?>" required>
                        <small class="form-text text-muted">Select a deadline no earlier than today</small>
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
                    logging.error('Error parsing JSON response', { error: e, response });
                    alert("An error occurred while processing the response.");
                }
            },
            error: function (xhr, status, error) {
                logging.error('AJAX Error', { status, error });
                alert('An error occurred while deleting images: ' + error);
            }
        });
    }

    $(document).ready(function () {
        // Log page load
        logging.info(`Edit project page loaded for project ID: ${projectId}`);

        // Add AJAX setup for proper headers
        $.ajaxSetup({
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        });

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
                logging.interaction('Single image delete requested', { imageId });
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
                logging.interaction('Batch image delete requested', { count: selectedImages.length });
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
                        logging.error('Error parsing JSON response', { error: e, response });
                        alert("An error occurred while processing the response.");
                    }
                },
                error: function (xhr, status, error) {
                    logging.error('AJAX Error', { status, error });
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

            logging.interaction('Unassigning images', { count: selectedImages.length });

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
                        logging.error('Error parsing JSON response', { error: e, response });
                        alert("An error occurred while processing the response.");
                    }
                },
                error: function (xhr, status, error) {
                    logging.error('AJAX Error', { status, error });
                    alert('An error occurred while unassigning images: ' + error);
                }
            });
        });

        // Handle assignment status change
        $('.status-select').change(function () {
            const assignmentId = $(this).data('assignment-id');
            const newStatus = $(this).val();
            const selectElement = $(this);
            
            logging.interaction('Status change', { assignmentId, newStatus });

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
                        logging.error('Error parsing JSON response', { error: e, response });
                        alert("An error occurred while processing the response.");
                    }
                },
                error: function (xhr, status, error) {
                    logging.error('AJAX Error', { status, error });
                    alert('An error occurred while updating status: ' + error);
                }
            });
        });

        // Handle assignment status change for assignment-status-select class
        $('.assignment-status-select').change(function () {
            const assignmentId = $(this).data('assignment-id');
            const newStatus = $(this).val();
            const selectElement = $(this);

            logging.interaction('Status change (assignment-status-select)', { assignmentId, newStatus });

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
                        logging.error('Error parsing JSON response', { error: e, response });
                        alert("An error occurred while processing the response.");
                    }
                },
                error: function (xhr, status, error) {
                    logging.error('AJAX Error', { status, error });
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

            logging.interaction('Deleting assignment', { assignmentId });

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
                        logging.error('Error parsing JSON response', { error: e, response });
                        alert("An error occurred while processing the response.");
                    }
                },
                error: function (xhr, status, error) {
                    logging.error('AJAX Error', { status, error });
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
                                            logging.error('Error parsing assign image response', { error: e });
                                            // Still reload to show at least the new assignment
                                            location.reload();
                                        }
                                    },
                                    error: function (xhr, status, error) {
                                        logging.error('AJAX Error while assigning images', { status, error });
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
                        logging.error('Error parsing JSON response', { error: e, response });
                        console.error('JSON parse error:', e);
                        console.log('Response that failed to parse:', response);
                        alert("An error occurred while processing the response.");
                    }
                },
                error: function (xhr, status, error) {
                    logging.error('AJAX Error', { status, error });
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

            logging.interaction('Uploading images', { count: fileInput.files.length });
            console.log(`Preparing to upload ${fileInput.files.length} images for project ID ${projectId}`);

            // Create a form data object and append all needed fields
            const formData = new FormData();
            formData.append('project_id', projectId);
            formData.append('action', 'upload_project_images');

            // Append all selected files
            let fileDetails = [];
            for (let i = 0; i < fileInput.files.length; i++) {
                formData.append('projectImages[]', fileInput.files[i]);
                logging.debug(`Adding file to upload: ${fileInput.files[i].name}`);
                fileDetails.push({
                    name: fileInput.files[i].name,
                    size: fileInput.files[i].size,
                    type: fileInput.files[i].type
                });
            }
            console.log('Files to upload:', fileDetails);

            // Show a loading indicator
            const loadingHtml = `
                <div id="uploadSpinner" class="text-center my-3">
                    <div class="spinner-border text-primary" role="status">
                        <span class="sr-only">Uploading...</span>
                    </div>
                    <div class="mt-2">Uploading images, please wait...</div>
                </div>
            `;
            $('#imagePreviewContainer').append(loadingHtml);
            $('#saveImages').prop('disabled', true).text('Uploading...');

            // Send the AJAX request
            $.ajax({
                url: 'controllers/edit_project_ajax.php',
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function (response) {
                    console.log('Raw upload response:', response);
                    try {
                        const data = JSON.parse(response);
                        console.log('Parsed upload response:', data);
                        if (data.status === 'success') {
                            logging.info('Images uploaded successfully', { count: fileInput.files.length });
                            location.reload();
                        } else {
                            logging.error('Failed to upload images', data.message);
                            $('#uploadSpinner').remove();
                            $('#saveImages').prop('disabled', false).text('Upload Images');
                            alert('Error: ' + data.message);
                        }
                    } catch (e) {
                        logging.error('Error parsing JSON response', { error: e, response });
                        console.error('JSON parse error:', e);
                        console.log('Response that failed to parse:', response);
                        $('#uploadSpinner').remove();
                        $('#saveImages').prop('disabled', false).text('Upload Images');
                        alert("An error occurred while processing the server response.");
                    }
                },
                error: function (xhr, status, error) {
                    logging.error('AJAX Error during upload', { status, error });
                    console.error('Upload error details:', xhr.responseText);
                    $('#uploadSpinner').remove();
                    $('#saveImages').prop('disabled', false).text('Upload Images');
                    alert('Error uploading images: ' + error);
                }
            });
        });

        // Image preview on selection
        $('#projectImages').change(function () {
            const files = this.files;
            $('#imagePreviewContainer').empty();

            logging.interaction('Images selected for upload preview', { count: files.length });

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
                assignmentId, userId, roleTask, status, deadline
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
                        logging.error('Error parsing JSON response', { error: e, response });
                        alert("An error occurred while processing the response.");
                    }
                },
                error: function (xhr, status, error) {
                    logging.error('AJAX Error', { status, error });
                    alert('An error occurred while updating the assignment: ' + error);
                }
            });
        });

        // View assigned images
        $('.view-assigned-images').click(function () {
            const assignmentId = $(this).data('assignment-id');
            logging.interaction('Viewing assigned images', { assignmentId });

            // AJAX call to get assigned images
            $.ajax({
                url: 'controllers/edit_project_ajax.php',
                type: 'POST',
                data: {
                    action: 'get_assigned_images',
                    assignment_id: assignmentId,
                    project_id: projectId
                },
                success: function (response) {
                    try {
                        const data = JSON.parse(response);
                        if (data.status === 'success') {
                            logging.info('Retrieved assigned images', { count: data.images.length });

                            // Create modal to display assigned images
                            let imagesHtml = '';
                            if (data.images.length > 0) {
                                imagesHtml = '<div class="row">';
                                data.images.forEach(image => {
                                    const fileName = image.image_path.split('/').pop();
                                    const statusBadge = image.status_image === 'completed' ?
                                        '<span class="badge badge-success mt-1">Completed</span>' :
                                        '<span class="badge badge-primary mt-1">Assigned</span>';

                                    imagesHtml += `
                                        <div class="col-md-4 mb-3">
                                            <div class="card">
                                                <img src="../../uploads/project_images/${image.image_path}" 
                                                     class="card-img-top" 
                                                     alt="Image" 
                                                     onerror="this.src='data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAIAAAABOCAYAAADo6LyvAAAABHNCSVQICAgIfAhkiAAAAAlwSFlzAAADsQAAA7EB9YPtSQAAABl0RVh0U29mdHdhcmUAd3d3Lmlua3NjYXBlLm9yZ5vuPBoAAAL9SURBVHic7dxNSFRRGMbx/51xGh2dpIjCIKIIWkQQEkkoQS0KghbRI7Ro6UJatYqCQCVaFC2iaNUqCFpEqzZRkoWFVEYQFYCbiCisqJlP43jus5jMLpwFOu9zzpzpPD/4L2bmnvO+L3PvmTuQIYQQQgghhBBCCCGEEEIIIYQQQgghhBBCCCGEEEIIIYQQQgghhBBCCCGEEEIIIYQQQqyVP9uWq1VzL6veAAAAAElFTkSuQmCC'">
                                                <div class="card-body p-2">
                                                    <small class="text-truncate d-block" title="${fileName}">${fileName}</small>
                                                    ${statusBadge}
                                                </div>
                                            </div>
                                        </div>
                                    `;
                                });
                                imagesHtml += '</div>';
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

                            // Add modal to body
                            $('body').append(modalHtml);
                            $('#viewAssignedImagesModal').modal('show');

                            // Remove modal from DOM when hidden
                            $('#viewAssignedImagesModal').on('hidden.bs.modal', function () {
                                $(this).remove();
                            });
                        } else {
                            logging.error('Failed to retrieve assigned images', data.message);
                            alert('Error: ' + data.message);
                        }
                    } catch (e) {
                        logging.error('Error parsing JSON response', { error: e, response });
                        alert("An error occurred while processing the response.");
                    }
                },
                error: function (xhr, status, error) {
                    logging.error('AJAX Error', { status, error });
                    alert('An error occurred while retrieving assigned images: ' + error);
                }
            });
        });

        // Add more images to assignment
        $('.add-more-images').click(function () {
            const assignmentId = $(this).data('assignment-id');
            logging.interaction('Adding more images to assignment', { assignmentId });

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
                            logging.info('Retrieved available images', { count: data.images.length });

                            if (data.images.length === 0) {
                                alert('No available images to assign. Please upload more images first.');
                                return;
                            }

                            // Create modal to display available images for selection
                            let imagesHtml = '<div class="row">';
                            data.images.forEach(image => {
                                const fileName = image.image_path.split('/').pop();

                                imagesHtml += `
                                    <div class="col-md-4 mb-3">
                                        <div class="card selectable-image" data-image-id="${image.image_id}">
                                            <img src="../../uploads/project_images/${image.image_path}" 
                                                 class="card-img-top" 
                                                 alt="Image" 
                                                 onerror="this.src='data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAIAAAABOCAYAAADo6LyvAAAABHNCSVQICAgIfAhkiAAAAAlwSFlzAAADsQAAA7EB9YPtSQAAABl0RVh0U29mdHdhcmUAd3d3Lmlua3NjYXBlLm9yZ5vuPBoAAAL9SURBVHic7dxNSFRRGMbx/51xGh2dpIjCIKIIWkQQEkkoQS0KghbRI7Ro6UJatYqCQCVaFC2iaNUqCFpEqzZRkoWFVEYQFYCbiCisqJlP43jus5jMLpwFOu9zzpzpPD/4L2bmnvO+L3PvmTuQIYQQQgghhBBCCCGEEEIIIYQQQgghhBBCCCGEEEIIIYQQQgghhBBCCCGEEEIIIYQQQqyVP9uWq1VzL6veAAAAAElFTkSuQmCC'">
                                            <div class="card-body p-2">
                                                <small class="text-truncate d-block" title="${fileName}">${fileName}</small>
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
                                            logging.error('Error parsing JSON response', { error: e, response });
                                            alert("An error occurred while processing the response.");
                                        }
                                    },
                                    error: function (xhr, status, error) {
                                        logging.error('AJAX Error', { status, error });
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
                        logging.error('Error parsing JSON response', { error: e, response });
                        alert("An error occurred while processing the response.");
                    }
                },
                error: function (xhr, status, error) {
                    logging.error('AJAX Error', { status, error });
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
    });
</script>

<?php include("includes/footer.php"); ?>