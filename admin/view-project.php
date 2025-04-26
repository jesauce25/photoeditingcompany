<?php
include("includes/header.php");
require_once 'controllers/unified_project_controller.php';


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

// Add logging function for both server and client side
function log_action($action, $data = null)
{
    // Server-side logging
    $log_message = "[" . date('Y-m-d H:i:s') . "] [VIEW-PROJECT] " . $action;
    if ($data !== null) {
        $log_message .= ": " . json_encode($data);
    }
    error_log($log_message);
}

// Log view project access
log_action("View project accessed", array("project_id" => $project_id, "user" => $_SESSION['username'] ?? 'Unknown'));

// Get project images
$images = getProjectImages($project_id);
log_action("Retrieved images", array("count" => count($images), "project_id" => $project_id));

// Get project assignments
$assignments = getProjectAssignments($project_id);
log_action("Retrieved assignments", array("count" => count($assignments), "project_id" => $project_id));

// Functions to get status and priority display classes
function getStatusClass($status)
{
    switch ($status) {
        case 'pending':
            return 'warning';
        case 'in_progress':
            return 'primary';
        case 'review':
            return 'info';
        case 'completed':
            return 'success';
        case 'delayed':
            return 'danger';
        default:
            return 'secondary';
    }
}

function getPriorityClass($priority)
{
    switch (strtolower($priority)) {
        case 'high':
            return 'danger';
        case 'medium':
            return 'warning';
        case 'low':
            return 'success';
        case 'urgent':
            return 'dark';
        default:
            return 'secondary';
    }
}

function getAssignmentStatusClass($status)
{
    switch ($status) {
        case 'pending':
            return 'warning';
        case 'in_progress':
            return 'primary';
        case 'review':
        case 'in_qa':
            return 'info';
        case 'completed':
            return 'success';
        default:
            return 'secondary';
    }
}
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
                            <i class="fas fa-eye mr-2"></i>
                            View Project
                        </h1>

                    </div>
                    <div class="col-sm-6">
                        <ol class="breadcrumb float-sm-right">
                            <li class="breadcrumb-item"><a href="#">Home</a></li>
                            <li class="breadcrumb-item"><a href="project-list.php">Projects</a></li>
                            <li class="breadcrumb-item active">View Project</li>
                        </ol>
                    </div>
                </div>
            </div>
        </section>

        <!-- Main content -->
        <section class="content">
            <div class="container-fluid">
                <!-- Project Overview Section -->
                <div class="row mb-4">
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header bg-primary text-white">
                                <h5 class="card-title mb-0">
                                    <i class="fas fa-info-circle mr-2"></i>Project Details
                                </h5>
                            </div>
                            <div class="card-body">
                                <div class="form-group">
                                    <label>Project Name</label>
                                    <p class="form-control-static">
                                        <?php echo htmlspecialchars($project['project_title'] ?? ''); ?>
                                    </p>
                                </div>
                                <div class="form-group">
                                    <label>Company</label>
                                    <p class="form-control-static">
                                        <?php echo htmlspecialchars($project['company_name'] ?? ''); ?>
                                    </p>
                                </div>
                                <div class="form-group">
                                    <label>Description</label>
                                    <p class="form-control-static">
                                        <?php echo nl2br(htmlspecialchars($project['description'] ?? '')); ?>
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header bg-primary text-white">
                                <h5 class="card-title mb-0">
                                    <i class="fas fa-calendar-alt mr-2"></i>Project Status
                                </h5>
                            </div>
                            <div class="card-body">
                                <div class="form-group">
                                    <label>Project Status</label>
                                    <p class="form-control-static">
                                        <?php
                                        $statusText = ucfirst(str_replace('_', ' ', $project['status_project'] ?? 'Unknown'));
                                        echo $statusText;
                                        ?>
                                    </p>
                                </div>
                                <div class="form-group">
                                    <label>Priority</label>
                                    <p class="form-control-static">
                                        <span
                                            class="badge badge-<?php echo getPriorityClass($project['priority']); ?> p-2">
                                            <?php echo ucfirst($project['priority'] ?? 'Not set'); ?>
                                        </span>
                                    </p>
                                </div>
                                <div class="form-group">
                                    <label>Start Date</label>
                                    <p class="form-control-static">
                                        <i class="far fa-calendar-alt mr-1"></i>
                                        <?php echo date('Y-m-d', strtotime($project['date_arrived'])); ?>
                                    </p>
                                </div>
                                <div class="form-group">
                                    <label>Deadline</label>
                                    <p class="form-control-static">
                                        <i class="far fa-calendar-check mr-1"></i>
                                        <?php echo date('Y-m-d', strtotime($project['deadline'])); ?>
                                        <?php
                                        $deadline = new DateTime($project['deadline']);
                                        $now = new DateTime();
                                        $interval = $now->diff($deadline);
                                        $days_diff = $interval->days;
                                        $is_past = $interval->invert;

                                        if ($is_past) {
                                            // Overdue
                                            echo '<span class="badge badge-danger ml-2">Overdue by ' .
                                                $days_diff . ' ' . ($days_diff > 1 ? 'days' : 'day') . '</span>';
                                        } elseif ($days_diff == 0) {
                                            echo '<span class="badge badge-warning ml-2">Due today</span>';
                                        } elseif ($days_diff == 1) {
                                            echo '<span class="badge badge-warning ml-2">Due tomorrow</span>';
                                        } elseif ($days_diff <= 3) {
                                            echo '<span class="badge badge-warning ml-2">' . $days_diff . ' days left</span>';
                                        } else {
                                            echo '<span class="badge badge-info ml-2">' . $days_diff . ' days left</span>';
                                        }
                                        ?>
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Total Images Section -->
                <div class="row mb-4">
                    <div class="col-12">
                        <div class="card">
                            <div
                                class="card-header d-flex justify-content-between align-items-center bg-primary text-white">
                                <h5 class="card-title mb-0">
                                    <i class="fas fa-images mr-2"></i>
                                    Project Images (Total: <?php echo count($images); ?>)
                                </h5>
                            </div>
                            <div class="card-body">
                                <!-- Images Grid -->
                                <div class="row" id="projectImagesList">
                                    <?php if (empty($images)): ?>
                                        <div class="col-12 text-center py-5">
                                            <p class="text-muted">No images uploaded yet.</p>
                                        </div>
                                    <?php else: ?>
                                        <?php foreach ($images as $index => $image): ?>
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
                                            $fileName = '';
                                            if (isset($image['file_name']) && !empty($image['file_name'])) {
                                                $fileName = $image['file_name'];
                                            } else if (isset($image['image_path']) && !empty($image['image_path'])) {
                                                $fileName = pathinfo($image['image_path'], PATHINFO_BASENAME);
                                            } else {
                                                $fileName = 'Image ' . $image['image_id'];
                                            }

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
                                                <div class="image-container card shadow-sm">
                                                    <div class="card-body p-2">
                                                        <div class="d-flex align-items-center">


                                                            <!-- Image details -->
                                                            <div class="flex-grow-1">
                                                                <h6 class="mb-0 text-truncate"
                                                                    title="<?php echo htmlspecialchars($fileName); ?>">
                                                                    <?php echo htmlspecialchars($fileName); ?>
                                                                </h6>
                                                                <div class="d-flex flex-wrap mt-1">
                                                                    <!-- Assignee badge -->
                                                                    <span class="badge <?php echo $statusClass; ?> mr-1 mb-1">
                                                                        <i class="fas fa-user mr-1"></i>
                                                                        <?php echo $statusText; ?>
                                                                    </span>

                                                                    <!-- Role badge - always show, even if empty -->
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

                <!-- Assigned Tasks Section -->
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
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php if (count($assignments) > 0): ?>
                                                <?php foreach ($assignments as $assignment): ?>
                                                    <tr data-assignment-id="<?php echo $assignment['assignment_id']; ?>">
                                                        <td>
                                                            <div class="team-member-col">
                                                                <?php
                                                                $assigneeName = "Not Assigned";
                                                                // Get the user's name directly from the database
                                                                if (!empty($assignment['user_id'])) {
                                                                    $userQuery = "SELECT CONCAT(first_name, ' ', last_name) as full_name FROM tbl_users WHERE user_id = ?";
                                                                    $userStmt = $conn->prepare($userQuery);
                                                                    if ($userStmt) {
                                                                        $userStmt->bind_param("i", $assignment['user_id']);
                                                                        $userStmt->execute();
                                                                        $userResult = $userStmt->get_result();
                                                                        if ($userResult && $userRow = $userResult->fetch_assoc()) {
                                                                            $assigneeName = $userRow['full_name'];
                                                                        }
                                                                    }
                                                                }
                                                                echo $assigneeName;
                                                                ?>
                                                                <?php if ($assignment['status_assignee'] != 'pending'): ?>
                                                                    <div class="mt-2">
                                                                        <span class="badge badge-info">
                                                                            <i class="fas fa-info-circle mr-1"></i> Status:
                                                                            <?php echo ucfirst($assignment['status_assignee']); ?>
                                                                        </span>
                                                                    </div>
                                                                <?php endif; ?>
                                                            </div>
                                                        </td>
                                                        <td>
                                                            <?php echo $assignment['role_task'] ? $assignment['role_task'] : 'Not Set'; ?>
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
                                                            // Define status mapping for display
                                                            $statusDisplayMap = [
                                                                'pending' => 'Pending',
                                                                'in_progress' => 'In Progress',
                                                                'finish' => 'Finished',
                                                                'qa' => 'QA Review',
                                                                'approved' => 'Approved',
                                                                'completed' => 'Completed'
                                                            ];

                                                            $currentStatus = $assignment['status_assignee'];
                                                            $statusDisplay = $statusDisplayMap[$currentStatus] ?? ucfirst($currentStatus);
                                                            $statusClass = getAssignmentStatusClass($currentStatus);
                                                            ?>
                                                            <span class="badge badge-<?php echo $statusClass; ?> p-2">
                                                                <?php echo $statusDisplay; ?>
                                                            </span>

                                                            <!-- Add a small progress indicator below -->
                                                            <div class="progress mt-2" style="height: 5px;">
                                                                <?php
                                                                // Calculate progress percentage based on status
                                                                $timelineSteps = ['pending', 'in_progress', 'finish', 'qa', 'approved', 'completed'];
                                                                $currentStepIndex = array_search($currentStatus, $timelineSteps);
                                                                if ($currentStepIndex === false)
                                                                    $currentStepIndex = 0;
                                                                $progressPercent = ($currentStepIndex / (count($timelineSteps) - 1)) * 100;
                                                                ?>
                                                                <div class="progress-bar bg-<?php echo $statusClass; ?>"
                                                                    role="progressbar"
                                                                    style="width: <?php echo $progressPercent; ?>%"
                                                                    aria-valuenow="<?php echo $progressPercent; ?>"
                                                                    aria-valuemin="0" aria-valuemax="100"></div>
                                                            </div>
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
                                                            ?>
                                                            <div>
                                                                <?php echo date('Y-m-d', strtotime($assignment['deadline'])); ?>
                                                                <span class="badge <?php echo $badge_class; ?> ml-2">
                                                                    <?php echo $deadline_status; ?>
                                                                </span>
                                                            </div>
                                                        </td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            <?php else: ?>
                                                <tr>
                                                    <td colspan="5" class="text-center">No team assignments found.</td>
                                                </tr>
                                            <?php endif; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Action buttons -->
                <div class="mt-4 mb-4 text-center">
                    <a href="project-list.php" class="btn btn-secondary mr-2">
                        <i class="fas fa-arrow-left mr-1"></i> Back to List
                    </a>
                    <a href="edit-project.php?id=<?php echo $project_id; ?>" class="btn btn-primary">
                        <i class="fas fa-edit mr-1"></i> Edit Project
                    </a>
                </div>
            </div>
        </section>
    </div>
</div>

<!-- Add script for client-side logging -->
<style>
    /* Styles for assigned images modal */
    .swal-wide {
        width: 80% !important;
    }

    .swal-large-content {
        max-height: 80vh !important;
        overflow-y: auto !important;
    }

    .cursor-pointer {
        cursor: pointer;
    }

    .image-preview-link:hover {
        text-decoration: underline;
        color: #007bff;
    }

    #assigned-images-table img {
        transition: transform 0.2s ease;
        border: 1px solid #ddd;
        padding: 3px;
        background: #fff;
    }

    #assigned-images-table img:hover {
        transform: scale(1.1);
        box-shadow: 0 0 10px rgba(0, 0, 0, 0.2);
    }
</style>
<script>
    document.addEventListener("DOMContentLoaded", function () {
        // Wait for jQuery to be available
        function checkJQuery() {
            if (window.jQuery) {
                // jQuery is loaded, initialize everything
                initializePage();
            } else {
                // Check again in 100ms
                setTimeout(checkJQuery, 100);
            }
        }

        checkJQuery();

        function initializePage() {
            // Logging functions
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
                                page: 'view-project',
                                projectId: <?php echo $project_id; ?>
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

            // Log page load
            logging.info('View Project page loaded', { projectId: <?php echo $project_id; ?> });

            // Log info about project data
            logging.info('Project data loaded', {
                projectTitle: '<?php echo addslashes($project['project_title'] ?? ''); ?>',
                status: '<?php echo addslashes($project['status_project'] ?? ''); ?>',
                imageCount: <?php echo count($images); ?>,
                assignmentsCount: <?php echo count($assignments); ?>
            });

            // Log user interactions
            $('.nav-link').click(function () {
                const tabId = $(this).attr('href');
                logging.interaction('Changed team member tab', { tab: tabId });
            });



            // Handle image thumbnail clicks
            $('.image-container').click(function () {
                const imageId = $(this).data('image-id');
                logging.interaction('Image card clicked', { imageId });
            });

            // Handle view assigned images
            $('.view-assigned-images').click(function () {
                const assignmentId = $(this).data('assignment-id');

                logging.interaction('Viewing assigned images', {
                    assignmentId
                });

                // Show loading indicator
                const loadingModal = Swal.fire({
                    title: 'Loading...',
                    html: '<div class="text-center"><i class="fas fa-spinner fa-spin fa-3x"></i></div>',
                    showConfirmButton: false,
                    allowOutsideClick: false,
                    allowEscapeKey: false,
                    allowEnterKey: false
                });

                // AJAX call to get assigned images
                $.ajax({
                    url: 'controllers/edit_project_ajax.php',
                    type: 'POST',
                    data: {
                        action: 'get_assigned_images',
                        project_id: <?php echo $project_id; ?>,
                        assignment_id: assignmentId
                    },
                    success: function (response) {
                        try {
                            // Close loading indicator
                            loadingModal.close();

                            const data = JSON.parse(response);
                            if (data.status === 'success') {
                                let imagesHtml = '';
                                let assignmentInfo = '';

                                // Add assignment information if available
                                if (data.assignment) {
                                    const assignmentData = data.assignment;
                                    const deadlineDate = assignmentData.deadline ? new Date(assignmentData.deadline) : null;
                                    const formattedDeadline = deadlineDate ? deadlineDate.toLocaleDateString() : 'Not set';

                                    assignmentInfo = `
                                    <div class="alert alert-info mb-3">
                                        <h5>Assignment Details</h5>
                                        <p><strong>Assignee:</strong> ${assignmentData.full_name || 'Unknown'}</p>
                                        <p><strong>Role/Task:</strong> ${assignmentData.role_task || 'Not specified'}</p>
                                        <p><strong>Status:</strong> ${assignmentData.status_assignee || 'Pending'}</p>
                                        <p><strong>Deadline:</strong> ${formattedDeadline}</p>
                                    </div>
                                    `;
                                }

                                if (data.images && data.images.length > 0) {
                                    // Create a table for image details
                                    imagesHtml = `
                                    ${assignmentInfo}
                                    <div class="table-responsive" style="max-height: 500px; overflow-y: auto;">
                                        <table class="table table-bordered table-hover table-sm" id="assigned-images-table">
                                            <thead class="thead-light sticky-top" style="position: sticky; top: 0; z-index: 1;">
                                                <tr>
                                                    <th>Image Preview</th>
                                                    <th>Image Name</th>
                                                    <th>Estimated Time</th>
                                                    <th>Role</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                    `;

                                    data.images.forEach(image => {
                                        const fileName = image.image_path ? image.image_path.split('/').pop() : 'Unknown';
                                        const imageUrl = image.image_url || '';
                                        const estimatedHours = Math.floor((image.estimated_time || 0) / 60);
                                        const estimatedMinutes = (image.estimated_time || 0) % 60;
                                        const imageRole = image.image_role || 'Not specified';

                                        // Create a row for each image with preview
                                        imagesHtml += `
                                            <tr>
                                                <td class="text-center">
                                                    <img src="${imageUrl}" alt="${fileName}" style="max-height: 80px; max-width: 100%;" 
                                                        onclick="window.open('${imageUrl}', '_blank')" class="cursor-pointer">
                                                </td>
                                                <td>
                                                    <a href="${imageUrl}" target="_blank" class="image-preview-link">
                                                        ${fileName}
                                                    </a>
                                                </td>
                                                <td>
                                                    ${estimatedHours}hr ${estimatedMinutes}min
                                                </td>
                                                <td>
                                                    ${imageRole}
                                                </td>
                                            </tr>
                                        `;
                                    });

                                    imagesHtml += `
                                            </tbody>
                                        </table>
                                    </div>
                                    `;
                                } else {
                                    imagesHtml = assignmentInfo + '<div class="alert alert-info">No images assigned to this team member.</div>';
                                }

                                // Create and show modal with SweetAlert2
                                Swal.fire({
                                    title: `<i class="fas fa-images mr-2"></i>Assigned Images (${data.images ? data.images.length : 0})`,
                                    html: imagesHtml,
                                    width: '80%',
                                    confirmButtonText: 'Close',
                                    confirmButtonColor: '#3085d6',
                                    showCloseButton: true,
                                    customClass: {
                                        container: 'swal-wide',
                                        popup: 'swal-large-content'
                                    }
                                });

                                // Add custom style for large content
                                $('<style>.swal-large-content { max-height: 80vh; overflow-y: auto; } .cursor-pointer { cursor: pointer; }</style>').appendTo('head');
                            } else {
                                Swal.fire({
                                    icon: 'error',
                                    title: 'Error',
                                    text: data.message || 'Failed to load assigned images'
                                });
                            }
                        } catch (e) {
                            console.error('Error parsing JSON response', e);
                            Swal.fire({
                                icon: 'error',
                                title: 'Error',
                                text: 'An error occurred while processing the response'
                            });
                        }
                    },
                    error: function (xhr, status, error) {
                        // Close loading indicator
                        loadingModal.close();

                        console.error('AJAX Error', {
                            status,
                            error
                        });
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: 'An error occurred while loading assigned images: ' + error
                        });
                    }
                });
            });
        }
    });
</script>

<?php include("includes/footer.php"); ?>