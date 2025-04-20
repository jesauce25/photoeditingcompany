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
function getStatusClass($status) {
    switch ($status) {
        case 'pending':
            return 'warning';
        case 'in_progress':
            return 'primary';
        case 'in_qa':
        case 'qa':
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

// Function to get priority display class
function getPriorityClass($priority) {
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
                                            <span class="badge badge-<?php echo getStatusClass($task['status_assignee'] ?? 'Unknown'); ?> p-2">
                                                <?php 
                                                $statusText = ucfirst(str_replace('_', ' ', $task['status_assignee'] ?? 'Unknown'));
                                                $statusIcon = '';
                                                switch($task['status_assignee'] ?? 'Unknown') {
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
                                            <span class="badge badge-<?php echo getPriorityClass($task['priority'] ?? 'Not set'); ?> p-2">
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
                    <div class="row mb-4">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-header bg-primary text-white">
                                    <h5 class="card-title mb-0">
                                        <i class="fas fa-images mr-2"></i>
                                        Task Images
                                        <span class="badge badge-light ml-2"><?php echo count($images); ?></span>
                                    </h5>
                                </div>
                                <div class="card-body p-0">
                                    <div class="p-3">
                                        <div class="image-grid-container border-0 shadow-none">
                                            <div class="row" id="taskImagesList">
                                                <?php if (empty($images)): ?>
                                                    <div class="col-12">
                                                        <div class="alert alert-info">
                                                            <i class="fas fa-info-circle mr-2"></i> No images have been assigned to this task yet.
                                                        </div>
                                                    </div>
                                                <?php else: ?>
                                                    <div class="col-12">
                                                        <div class="row">
                                                            <?php foreach ($images as $index => $image): ?>
                                                                <div class="col-md-3 col-sm-6 mb-4">
                                                                    <div class="card h-100">
                                                                        <div class="image-container">
                                                                            <img src="<?php echo $image['image_url']; ?>" 
                                                                                alt="Task Image" 
                                                                                class="img-fluid card-img-top task-image"
                                                                                data-toggle="modal"
                                                                                data-target="#imageModal"
                                                                                data-img-src="<?php echo $image['image_url']; ?>"
                                                                                style="cursor: pointer; height: 200px; object-fit: cover;">
                                                                        </div>
                                                                        <div class="card-body p-2 text-center">
                                                                            <h6 class="card-title mb-1">
                                                                                Image #<?php echo $index + 1; ?>
                                                                            </h6>
                                                                            <p class="card-text small text-muted mb-1">
                                                                                <?php echo $image['file_type']; ?>
                                                                            </p>
                                                                            <span class="badge badge-info">
                                                                                <?php echo $image['status_image']; ?>
                                                                            </span>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            <?php endforeach; ?>
                                                        </div>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
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
                                                        <i class="fas fa-check mr-2"></i> Mark as Completed
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
                                                <select class="form-control" id="statusUpdate" 
                                                        data-id="<?php echo $task['assignment_id'] ?? ''; ?>">
                                                    <option value="">-- Select Status --</option>
                                                    <option value="pending" <?php echo ($task['status_assignee'] ?? '') == 'pending' ? 'selected' : ''; ?>>Pending</option>
                                                    <option value="in_progress" <?php echo ($task['status_assignee'] ?? '') == 'in_progress' ? 'selected' : ''; ?>>In Progress</option>
                                                    <option value="review" <?php echo ($task['status_assignee'] ?? '') == 'review' ? 'selected' : ''; ?>>In Review</option>
                                                    <option value="completed" <?php echo ($task['status_assignee'] ?? '') == 'completed' ? 'selected' : ''; ?>>Completed</option>
                                                    <option value="delayed" <?php echo ($task['status_assignee'] ?? '') == 'delayed' ? 'selected' : ''; ?>>Delayed</option>
                                                </select>
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
<div class="modal fade" id="imageModal" tabindex="-1" role="dialog" aria-labelledby="imageModalLabel" aria-hidden="true">
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
document.addEventListener('DOMContentLoaded', function() {
    // Image modal functionality
    $('#imageModal').on('show.bs.modal', function (event) {
        var button = $(event.relatedTarget);
        var imgSrc = button.data('img-src');
        var modal = $(this);
        modal.find('#modalImage').attr('src', imgSrc);
    });
    
    // Start task button event
    $('.start-task-btn').on('click', function() {
        const assignmentId = $(this).data('id');
        updateTaskStatus(assignmentId, 'in_progress');
    });
    
    // Complete task button event
    $('.complete-task-btn').on('click', function() {
        const assignmentId = $(this).data('id');
        updateTaskStatus(assignmentId, 'completed');
    });
    
    // Status dropdown change event
    $('#statusUpdate').on('change', function() {
        const assignmentId = $(this).data('id');
        const newStatus = $(this).val();
        
        if (newStatus) {
            updateTaskStatus(assignmentId, newStatus);
        }
    });
    
    // Function to update task status via AJAX
    function updateTaskStatus(assignmentId, status) {
        // Show loading indicator or disable buttons
        
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
            }
        })
        .catch(error => {
            console.error('Error updating status:', error);
            $('#toastMessage').text('Error updating status. Please try again.');
            $('#statusToast').toast('show');
        });
    }
});
</script>

<?php include("includes/footer.php"); ?>