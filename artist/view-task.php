<?php
include("includes/header.php");
require_once '../includes/db_connection.php';

// Get the current user's ID from session
$user_id = $_SESSION['user_id'] ?? 0;

// Get the assignment ID from URL
$assignment_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Initialize variables
$task = null;
$images = [];
$error_message = null;

try {
    // Check if this assignment belongs to the current user (security check)
    $checkQuery = "SELECT pa.assignment_id 
                   FROM tbl_project_assignments pa 
                   WHERE pa.assignment_id = ? AND pa.user_id = ?";

    $checkStmt = $conn->prepare($checkQuery);

    if (!$checkStmt) {
        throw new Exception("Query preparation failed: " . $conn->error);
    }

    $checkStmt->bind_param("ii", $assignment_id, $user_id);
    $checkStmt->execute();
    $checkResult = $checkStmt->get_result();

    if ($checkResult->num_rows === 0) {
        throw new Exception("You don't have permission to view this task or it doesn't exist.");
    }

    // Fetch the assignment details
    $query = "SELECT pa.*, p.project_title, p.description, p.date_arrived, p.priority, 
                     p.status_project, p.deadline as project_deadline, p.total_images,
                     c.company_name
              FROM tbl_project_assignments pa
              JOIN tbl_projects p ON pa.project_id = p.project_id
              LEFT JOIN tbl_companies c ON p.company_id = c.company_id
              WHERE pa.assignment_id = ? AND pa.user_id = ?";

    $stmt = $conn->prepare($query);

    if (!$stmt) {
        throw new Exception("Query preparation failed: " . $conn->error);
    }

    $stmt->bind_param("ii", $assignment_id, $user_id);

    if (!$stmt->execute()) {
        throw new Exception("Query execution failed: " . $stmt->error);
    }

    $result = $stmt->get_result();
    $task = $result->fetch_assoc();

    if (!$task) {
        throw new Exception("Task not found.");
    }

    // Get images assigned to this task
    $imagesQuery = "SELECT pi.* 
                    FROM tbl_project_images pi
                    WHERE pi.assignment_id = ?
                    ORDER BY pi.upload_date DESC";

    $imagesStmt = $conn->prepare($imagesQuery);

    if (!$imagesStmt) {
        throw new Exception("Images query preparation failed: " . $conn->error);
    }

    $imagesStmt->bind_param("i", $assignment_id);

    if (!$imagesStmt->execute()) {
        throw new Exception("Images query execution failed: " . $imagesStmt->error);
    }

    $imagesResult = $imagesStmt->get_result();

    while ($image = $imagesResult->fetch_assoc()) {
        // Add the full path for display
        $image['image_url'] = '../uploads/projects/' . ($task['project_id'] ?? '') . '/' . $image['image_path'];
        $images[] = $image;
    }

} catch (Exception $e) {
    // Log the error
    error_log("Error in view-task.php: " . $e->getMessage());

    // Set user-friendly error message
    $error_message = $e->getMessage();
}

// Function to get status display class
function getStatusClass($status)
{
    switch ($status) {
        case 'pending':
            return 'warning';
        case 'in_progress':
            return 'primary';
        case 'finish':
            return 'info';
        case 'qa':
        case 'review':
            return 'info';
        case 'approved':
            return 'success';
        case 'completed':
            return 'success';
        case 'delayed':
            return 'danger';
        default:
            return 'secondary';
    }
}

// Function to get priority display class
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
                            View Task
                        </h1>
                    </div>
                    <div class="col-sm-6">
                        <ol class="breadcrumb float-sm-right">
                            <li class="breadcrumb-item"><a href="home.php">Home</a></li>
                            <li class="breadcrumb-item"><a href="task.php">Tasks</a></li>
                            <li class="breadcrumb-item active">View Task</li>
                        </ol>
                    </div>
                </div>
            </div>
        </section>

        <!-- Main content -->
        <section class="content">
            <div class="container-fluid">
                <?php if ($error_message): ?>
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-circle mr-2"></i> <?php echo $error_message; ?>
                    </div>
                <?php elseif ($task): ?>
                    <!-- Task Overview Section -->
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header bg-primary text-white">
                                    <h5 class="card-title mb-0">
                                        <i class="fas fa-info-circle mr-2"></i>Task Details
                                    </h5>
                                </div>
                                <div class="card-body">
                                    <div class="form-group">
                                        <label>Project Name</label>
                                        <p class="form-control-static">
                                            <?php echo htmlspecialchars($task['project_title'] ?? 'Untitled Project'); ?>
                                        </p>
                                    </div>
                                    <div class="form-group">
                                        <label>Company</label>
                                        <p class="form-control-static">
                                            <?php echo htmlspecialchars($task['company_name'] ?? 'No Company'); ?>
                                        </p>
                                    </div>
                                    <div class="form-group">
                                        <label>Description</label>
                                        <p class="form-control-static">
                                            <?php echo nl2br(htmlspecialchars($task['description'] ?? 'No description')); ?>
                                        </p>
                                    </div>
                                    <div class="form-group">
                                        <label>Role</label>
                                        <p class="form-control-static">
                                            <span class="badge badge-info p-2">
                                                <?php echo htmlspecialchars($task['role_task'] ?? 'Not Assigned'); ?>
                                            </span>
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header bg-primary text-white">
                                    <h5 class="card-title mb-0">
                                        <i class="fas fa-calendar-alt mr-2"></i>Task Status
                                    </h5>
                                </div>
                                <div class="card-body">
                                    <div class="form-group">
                                        <label>Task Status</label>
                                        <p class="form-control-static">
                                            <span
                                                class="badge badge-<?php echo getStatusClass($task['status_assignee'] ?? 'Unknown'); ?> p-2">
                                                <?php
                                                $statusText = ucfirst(str_replace('_', ' ', $task['status_assignee'] ?? 'Unknown'));
                                                $statusIcon = '';
                                                switch ($task['status_assignee'] ?? 'Unknown') {
                                                    case 'in_progress':
                                                        $statusIcon = '<i class="fas fa-spinner fa-spin mr-1"></i>';
                                                        break;
                                                    case 'pending':
                                                        $statusIcon = '<i class="fas fa-clock mr-1"></i>';
                                                        break;
                                                    case 'finish':
                                                        $statusIcon = '<i class="fas fa-check mr-1"></i>';
                                                        $statusText = 'Finished';
                                                        break;
                                                    case 'qa':
                                                    case 'review':
                                                        $statusIcon = '<i class="fas fa-search mr-1"></i>';
                                                        $statusText = 'In QA Review';
                                                        break;
                                                    case 'approved':
                                                        $statusIcon = '<i class="fas fa-thumbs-up mr-1"></i>';
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
                                                class="badge badge-<?php echo getPriorityClass($task['priority'] ?? 'Not set'); ?> p-2">
                                                <?php echo ucfirst($task['priority'] ?? 'Not set'); ?>
                                            </span>
                                        </p>
                                    </div>
                                    <div class="form-group">
                                        <label>Date Received</label>
                                        <p class="form-control-static">
                                            <i class="far fa-calendar-alt mr-1"></i>
                                            <?php echo isset($task['date_arrived']) ? date('Y-m-d', strtotime($task['date_arrived'])) : 'Not set'; ?>
                                        </p>
                                    </div>
                                    <div class="form-group">
                                        <label>Deadline</label>
                                        <p class="form-control-static">
                                            <i class="far fa-calendar-check mr-1"></i>
                                            <?php echo isset($task['deadline']) ? date('Y-m-d', strtotime($task['deadline'])) : 'Not set'; ?>
                                            <?php
                                            if (isset($task['deadline'])) {
                                                $deadline = new DateTime($task['deadline']);
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
                                            }
                                            ?>
                                        </p>
                                    </div>
                                    <div class="form-group">
                                        <label>Assigned Images</label>
                                        <p class="form-control-static">
                                            <span class="badge badge-primary p-2">
                                                <i class="fas fa-images mr-1"></i> <?php echo count($images); ?> images
                                            </span>
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Task Images Section -->
                    <div class="card card-primary card-outline mt-4">
                        <div class="card-header">
                            <h3 class="card-title">
                                <i class="fas fa-images mr-2"></i>Assigned Images
                                <span class="badge badge-pill badge-primary ml-2"><?php echo count($images); ?></span>
                            </h3>
                        </div>
                        <div class="card-body">
                            <?php if (empty($images)): ?>
                                <div class="text-center text-muted p-4">
                                    <i class="fas fa-image fa-3x mb-3"></i>
                                    <p>No images assigned to this task yet.</p>
                                </div>
                            <?php else: ?>
                                <div class="table-responsive">
                                    <table class="table table-striped">
                                        <thead>
                                            <tr>
                                                <th>#</th>
                                                <th>Image Name</th>
                                                <th>Estimated Time</th>
                                                <th>Role</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($images as $index => $image): ?>
                                                <tr>
                                                    <td><?php echo $index + 1; ?></td>
                                                    <td><?php echo htmlspecialchars($image['image_path']); ?></td>
                                                    <td>
                                                        <?php
                                                        if (!empty($image['estimated_time'])) {
                                                            $time = intval($image['estimated_time']);
                                                            if ($time >= 60) {
                                                                $hours = floor($time / 60);
                                                                $minutes = $time % 60;
                                                                echo $hours . 'hr' . ($minutes > 0 ? ' ' . $minutes . 'min' : '');
                                                            } else {
                                                                echo $time . ' min';
                                                            }
                                                        } else {
                                                            echo '<span class="text-muted">Not set</span>';
                                                        }
                                                        ?>
                                                    </td>
                                                    <td>
                                                        <?php if (!empty($image['image_role'])): ?>
                                                            <span class="badge badge-info">
                                                                <?php echo htmlspecialchars($image['image_role']); ?>
                                                            </span>
                                                        <?php else: ?>
                                                            <span class="text-muted">Not set</span>
                                                        <?php endif; ?>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Task Actions Section -->
                    <div class="row mb-4">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-header bg-primary text-white">
                                    <h5 class="card-title mb-0">
                                        <i class="fas fa-cogs mr-2"></i>
                                        Task Actions
                                    </h5>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="task-action-buttons">
                                                <?php if (($task['status_assignee'] ?? '') == 'pending'): ?>
                                                    <button type="button" class="btn btn-primary start-task-btn"
                                                        data-id="<?php echo $task['assignment_id'] ?? ''; ?>">
                                                        <i class="fas fa-play mr-2"></i> Start Task
                                                    </button>
                                                <?php elseif (($task['status_assignee'] ?? '') == 'in_progress'): ?>
                                                    <button type="button" class="btn btn-success complete-task-btn"
                                                        data-id="<?php echo $task['assignment_id'] ?? ''; ?>">
                                                        <i class="fas fa-check mr-2"></i> Mark as Finished
                                                    </button>
                                                <?php elseif (($task['status_assignee'] ?? '') == 'finish'): ?>
                                                    <button type="button" class="btn btn-info" disabled>
                                                        <i class="fas fa-hourglass-half mr-2"></i> Awaiting QA
                                                    </button>
                                                <?php elseif (($task['status_assignee'] ?? '') == 'qa'): ?>
                                                    <button type="button" class="btn btn-info" disabled>
                                                        <i class="fas fa-clipboard-check mr-2"></i> In QA Review
                                                    </button>
                                                <?php elseif (($task['status_assignee'] ?? '') == 'completed'): ?>
                                                    <button type="button" class="btn btn-success" disabled>
                                                        <i class="fas fa-trophy mr-2"></i> Task Completed
                                                    </button>
                                                <?php endif; ?>

                                                <a href="task.php" class="btn btn-secondary">
                                                    <i class="fas fa-arrow-left mr-2"></i> Back to Tasks
                                                </a>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="statusUpdate">Update Status</label>
                                                <div class="status-timeline-container mt-2">
                                                    <?php
                                                    // Define the timeline steps
                                                    $timelineSteps = ['pending', 'in_progress', 'finish', 'qa', 'completed'];
                                                    $currentStatus = $task['status_assignee'] ?? 'pending';
                                                    
                                                    // Determine the current step index
                                                    $currentStepIndex = array_search($currentStatus, $timelineSteps);
                                                    if ($currentStepIndex === false) $currentStepIndex = 0;
                                                    
                                                    // Display the timeline
                                                    ?>
                                                    <div class="status-timeline d-flex align-items-center">
                                                        <?php foreach ($timelineSteps as $index => $step): 
                                                            $isActive = $index <= $currentStepIndex;
                                                            $isCurrent = $index == $currentStepIndex;
                                                            $canUpdate = false;
                                                            
                                                            // Determine if this step can be updated by the artist
                                                            if (($currentStatus === 'pending' && $step === 'in_progress') || 
                                                                ($currentStatus === 'in_progress' && $step === 'finish')) {
                                                                $canUpdate = true;
                                                            }
                                                            
                                                            // Determine classes
                                                            $stepClass = 'status-step';
                                                            if ($isActive) $stepClass .= ' active';
                                                            if ($isCurrent) $stepClass .= ' current';
                                                            if ($canUpdate) $stepClass .= ' can-update';
                                                            
                                                            // Display connector line for steps after the first
                                                            if ($index > 0): ?>
                                                                <div class="status-connector <?php echo $index <= $currentStepIndex ? 'active' : ''; ?>"></div>
                                                            <?php endif; ?>
                                                            
                                                            <div class="<?php echo $stepClass; ?>" 
                                                                 data-assignment-id="<?php echo $task['assignment_id']; ?>"
                                                                 data-status="<?php echo $step; ?>"
                                                                 title="<?php echo ucfirst(str_replace('_', ' ', $step)); ?>"
                                                                 <?php echo !$canUpdate ? 'data-locked="true"' : ''; ?>>
                                                                <?php echo substr(ucfirst($step), 0, 1); ?>
                                                                <span class="status-label"><?php echo ucfirst(str_replace('_', ' ', $step)); ?></span>
                                                            </div>
                                                        <?php endforeach; ?>
                                                    </div>
                                                    
                                                    <div class="status-info mt-2">
                                                        <?php if ($currentStatus === 'pending'): ?>
                                                            <small class="text-muted">Click "In Progress" when you start working on this task.</small>
                                                        <?php elseif ($currentStatus === 'in_progress'): ?>
                                                            <small class="text-muted">Click "Finish" when your work is complete.</small>
                                                        <?php elseif ($currentStatus === 'finish'): ?>
                                                            <small class="text-muted">Task is awaiting QA by an administrator.</small>
                                                        <?php elseif ($currentStatus === 'qa'): ?>
                                                            <small class="text-muted">Task is in QA. Awaiting approval.</small>
                                                        <?php elseif ($currentStatus === 'completed'): ?>
                                                            <small class="text-success"><i class="fas fa-check-circle"></i> Task has been completed.</small>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </section>
    </div>
</div>

<!-- Image Preview Modal -->
<div class="modal fade" id="imageModal" tabindex="-1" role="dialog" aria-labelledby="imageModalLabel"
    aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="imageModalLabel">Image Preview</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body text-center">
                <img src="" id="modalImage" class="img-fluid" alt="Full Size Image">
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<!-- Status Timeline Styles -->
<style>
    /* Status Timeline Styles */
    .status-timeline-container {
        padding: 10px 0;
    }
    
    .status-timeline {
        display: flex;
        align-items: center;
        position: relative;
        margin-bottom: 15px;
    }
    
    .status-step {
        width: 35px;
        height: 35px;
        border-radius: 50%;
        background-color: #e9ecef;
        color: #6c757d;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: bold;
        position: relative;
        z-index: 2;
        transition: all 0.3s ease;
        margin: 0 5px;
    }
    
    .status-step.active {
        background-color: #28a745;
        color: white;
    }
    
    .status-step.current {
        border: 2px solid #007bff;
    }
    
    .status-step.can-update {
        cursor: pointer;
        box-shadow: 0 2px 5px rgba(0,0,0,0.2);
    }
    
    .status-step.can-update:hover {
        transform: scale(1.1);
        background-color: #17a2b8;
        color: white;
    }
    
    .status-connector {
        height: 3px;
        flex-grow: 1;
        background-color: #e9ecef;
        position: relative;
        z-index: 1;
    }
    
    .status-connector.active {
        background-color: #28a745;
    }
    
    .status-label {
        position: absolute;
        bottom: -25px;
        left: 50%;
        transform: translateX(-50%);
        font-size: 0.7rem;
        white-space: nowrap;
        color: #6c757d;
    }
    
    .status-info {
        padding: 10px 0;
    }
</style>

<!-- Progress update toast -->
<div class="position-fixed bottom-0 right-0 p-3" style="z-index: 999; right: 0; bottom: 0;">
    <div id="statusToast" class="toast hide" role="alert" aria-live="assertive" aria-atomic="true" data-delay="3000">
        <div class="toast-header">
            <i class="fas fa-info-circle mr-2"></i>
            <strong class="mr-auto">Status Update</strong>
            <small>Just now</small>
            <button type="button" class="ml-2 mb-1 close" data-dismiss="toast" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
        <div class="toast-body" id="toastMessage">
            Status updated successfully!
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        // Image modal functionality
        $('#imageModal').on('show.bs.modal', function (event) {
            var button = $(event.relatedTarget);
            var imgSrc = button.data('img-src');
            var modal = $(this);
            modal.find('#modalImage').attr('src', imgSrc);
        });

        // Start task button event
        $('.start-task-btn').on('click', function () {
            const assignmentId = $(this).data('id');
            updateTaskStatus(assignmentId, 'in_progress');
        });

        // Complete task button event
        $('.complete-task-btn').on('click', function () {
            const assignmentId = $(this).data('id');
            updateTaskStatus(assignmentId, 'finish');
        });
        
        // Timeline status step click event
        $(document).on('click', '.status-step.can-update', function() {
            const assignmentId = $(this).data('assignment-id');
            const newStatus = $(this).data('status');
            
            // Show confirmation based on the new status
            let confirmMessage = '';
            if (newStatus === 'in_progress') {
                confirmMessage = 'Are you sure you want to start working on this task?';
            } else if (newStatus === 'finish') {
                confirmMessage = 'Are you sure you want to mark this task as finished? This will send it to QA for review.';
            }
            
            if (confirmMessage && confirm(confirmMessage)) {
                updateTaskStatus(assignmentId, newStatus);
            }
        });

        // Function to update task status via AJAX
        function updateTaskStatus(assignmentId, status) {
            // Show loading state
            $('#toastMessage').text('Updating status...');
            $('#statusToast').toast('show');
            
            // Disable interactive elements
            $('.status-step.can-update').css('pointer-events', 'none');
            $('.start-task-btn, .complete-task-btn').prop('disabled', true);

            fetch('../admin/controllers/edit_project_ajax.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: 'action=update_assignment_status&assignment_id=' + assignmentId + '&status=' + status
            })
                .then(response => response.json())
                .then(data => {
                    if (data.status === 'success') {
                        // Update UI to reflect new status
                        updateTimelineUI(status);
                        
                        // Show success message
                        $('#toastMessage').text('Status updated to ' + status.replace('_', ' '));
                        $('#statusToast').toast('show');

                        // Reload page after a short delay
                        setTimeout(() => {
                            location.reload();
                        }, 1500);
                    } else {
                        // Show error message
                        $('#toastMessage').text('Error: ' + data.message);
                        $('#statusToast').toast('show');
                        
                        // Re-enable interactive elements
                        $('.status-step.can-update').css('pointer-events', 'auto');
                        $('.start-task-btn, .complete-task-btn').prop('disabled', false);
                    }
                })
                .catch(error => {
                    console.error('Error updating status:', error);
                    $('#toastMessage').text('Error updating status. Please try again.');
                    $('#statusToast').toast('show');
                    
                    // Re-enable interactive elements
                    $('.status-step.can-update').css('pointer-events', 'auto');
                    $('.start-task-btn, .complete-task-btn').prop('disabled', false);
                });
        }
        
        // Function to update timeline UI without reloading
        function updateTimelineUI(newStatus) {
            const timelineSteps = ['pending', 'in_progress', 'finish', 'qa', 'completed'];
            const newIndex = timelineSteps.indexOf(newStatus);
            
            // Update the timeline steps
            $('.status-timeline .status-step').each(function(index) {
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
                
                // Update connector status
                if (index > 0) {
                    const connector = $(this).prev('.status-connector');
                    if (index <= newIndex) {
                        connector.addClass('active');
                    } else {
                        connector.removeClass('active');
                    }
                }
            });
            
            // Update status info text
            let infoText = '';
            if (newStatus === 'pending') {
                infoText = 'Click "In Progress" when you start working on this task.';
            } else if (newStatus === 'in_progress') {
                infoText = 'Click "Finish" when your work is complete.';
            } else if (newStatus === 'finish') {
                infoText = 'Task is awaiting QA by an administrator.';
            } else if (newStatus === 'qa') {
                infoText = 'Task is in QA. Awaiting approval.';
            } else if (newStatus === 'completed') {
                infoText = '<i class="fas fa-check-circle"></i> Task has been completed.';
            }
            
            $('.status-info small').html(infoText);
            
            // Remove the action buttons when completed
            if (newStatus === 'finish' || newStatus === 'qa' || newStatus === 'completed') {
                $('.start-task-btn, .complete-task-btn').fadeOut();
            }
        }
    });
</script>

<?php include("includes/footer.php"); ?>