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
                                        <span
                                            class="badge badge-<?php echo getStatusClass($project['status_project']); ?> p-2">
                                            <?php
                                            $statusText = ucfirst(str_replace('_', ' ', $project['status_project'] ?? 'Unknown'));
                                            $statusIcon = '';
                                            switch ($project['status_project']) {
                                                case 'in_progress':
                                                    $statusIcon = '<i class="fas fa-spinner fa-spin mr-1"></i>';
                                                    break;
                                                case 'pending':
                                                    $statusIcon = '<i class="fas fa-clock mr-1"></i>';
                                                    break;
                                                case 'review':
                                                    $statusIcon = '<i class="fas fa-search mr-1"></i>';
                                                    break;
                                                case 'completed':
                                                    $statusIcon = '<i class="fas fa-check-circle mr-1"></i>';
                                                    break;
                                                case 'delayed':
                                                    $statusIcon = '<i class="fas fa-exclamation-triangle mr-1"></i>';
                                                    break;
                                            }
                                            echo $statusIcon . $statusText;
                                            ?>
                                        </span>
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
                                        $days_left = $now->diff($deadline)->format("%R%a");

                                        if ($days_left < 0) {
                                            echo '<span class="badge badge-danger ml-2">Overdue by ' . abs($days_left) . ' days</span>';
                                        } elseif ($days_left == 0) {
                                            echo '<span class="badge badge-warning ml-2">Due today</span>';
                                        } elseif ($days_left <= 3) {
                                            echo '<span class="badge badge-warning ml-2">' . $days_left . ' days left</span>';
                                        } else {
                                            echo '<span class="badge badge-info ml-2">' . $days_left . ' days left</span>';
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
                                                                foreach ($graphicArtists as $artist) {
                                                                    if ($artist['user_id'] == $assignment['user_id']) {
                                                                        $assigneeName = $artist['full_name'];
                                                                        break;
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
                                                                        title="<?php echo ucfirst(str_replace('_', ' ', $step)); ?>">
                                                                        <?php echo substr(ucfirst($step), 0, 1); ?>
                                                                    </div>
                                                                <?php endforeach; ?>
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
                                                                $deadline_status = 'Overdue';
                                                                $badge_class = 'badge-danger';
                                                            }
                                                            ?>
                                                            <div>
                                                                <?php echo date('Y-m-d', strtotime($assignment['deadline'])); ?>
                                                                <?php if (!empty($deadline_status)): ?>
                                                                    <span class="badge <?php echo $badge_class; ?> ml-2">
                                                                        <?php echo $deadline_status; ?>
                                                                    </span>
                                                                <?php endif; ?>
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
<script>
    $(document).ready(function () {
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

        // Check for overdue items
        <?php if ($hasOverdueAssignment): ?>
            logging.warning('Project has overdue assignments', { projectId: <?php echo $project_id; ?> });
        <?php endif; ?>

        // Handle image thumbnail clicks
        $('.image-container').click(function () {
            const imageId = $(this).data('image-id');
            logging.interaction('Image card clicked', { imageId });
        });
    });
</script>

<?php include("includes/footer.php"); ?>