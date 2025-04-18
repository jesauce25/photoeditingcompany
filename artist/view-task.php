<?php
include("includes/header.php");

// Assume we're getting the task ID from URL
$task_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Mock data for demonstration - in production, this would come from database
$task = [
    'task_id' => $task_id,
    'project_title' => 'Website Redesign',
    'company_name' => 'Acme Corporation',
    'description' => 'Complete redesign of company website with focus on user experience and modern design principles.',
    'date_arrived' => '2023-04-01',
    'deadline' => '2023-04-30',
    'priority' => 'high',
    'status_assignee' => 'in_progress',
    'total_images' => 35,
    'role_task' => 'Graphic Designer',
    'task_details' => 'Create mockups for homepage, about page, and contact page.'
];

// Function to get status display class
function getStatusClass($status) {
    switch ($status) {
        case 'pending':
            return 'warning';
        case 'in_progress':
            return 'primary';
        case 'in_qa':
        case 'qa':
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

// Mock task images
$images = [];
for ($i = 1; $i <= $task['total_images']; $i++) {
    $images[] = [
        'image_id' => $i,
        'file_name' => "image_{$i}.jpg",
        'image_path' => "uploads/images/image_{$i}.jpg",
        'status_image' => 'available'
    ];
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
                            <li class="breadcrumb-item"><a href="#">Home</a></li>
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
                                        <?php echo htmlspecialchars($task['project_title'] ?? ''); ?>
                                    </p>
                                </div>
                                <div class="form-group">
                                    <label>Company</label>
                                    <p class="form-control-static">
                                        <?php echo htmlspecialchars($task['company_name'] ?? ''); ?>
                                    </p>
                                </div>
                                <div class="form-group">
                                    <label>Description</label>
                                    <p class="form-control-static">
                                        <?php echo nl2br(htmlspecialchars($task['description'] ?? '')); ?>
                                    </p>
                                </div>
                                <div class="form-group">
                                    <label>Role</label>
                                    <p class="form-control-static">
                                        <span class="badge badge-info p-2">
                                            <?php echo htmlspecialchars($task['role_task'] ?? ''); ?>
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
                                        <span class="badge badge-<?php echo getStatusClass($task['status_assignee']); ?> p-2">
                                            <?php 
                                            $statusText = ucfirst(str_replace('_', ' ', $task['status_assignee'] ?? 'Unknown'));
                                            $statusIcon = '';
                                            switch($task['status_assignee']) {
                                                case 'in_progress':
                                                    $statusIcon = '<i class="fas fa-spinner fa-spin mr-1"></i>';
                                                    break;
                                                case 'pending':
                                                    $statusIcon = '<i class="fas fa-clock mr-1"></i>';
                                                    break;
                                                case 'in_qa':
                                                case 'qa':
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
                                        <span class="badge badge-<?php echo getPriorityClass($task['priority']); ?> p-2">
                                            <?php echo ucfirst($task['priority'] ?? 'Not set'); ?>
                                        </span>
                                    </p>
                                </div>
                                <div class="form-group">
                                    <label>Date Received</label>
                                    <p class="form-control-static">
                                        <i class="far fa-calendar-alt mr-1"></i>
                                        <?php echo date('Y-m-d', strtotime($task['date_arrived'])); ?>
                                    </p>
                                </div>
                                <div class="form-group">
                                    <label>Deadline</label>
                                    <p class="form-control-static">
                                        <i class="far fa-calendar-check mr-1"></i>
                                        <?php echo date('Y-m-d', strtotime($task['deadline'])); ?>
                                        <?php
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
                                        ?>
                                    </p>
                                </div>
                                <div class="form-group">
                                    <label>Total Images</label>
                                    <p class="form-control-static">
                                        <span class="badge badge-primary p-2">
                                            <i class="fas fa-images mr-1"></i> <?php echo $task['total_images']; ?> images
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
                                                            <?php
                                                            // Extract filename from path
                                                            $fileName = '';
                                                            if (isset($image['file_name']) && !empty($image['file_name'])) {
                                                                $fileName = $image['file_name'];
                                                            } else if (isset($image['image_path']) && !empty($image['image_path'])) {
                                                                $path_parts = pathinfo($image['image_path']);
                                                                $fileName = $path_parts['basename'];
                                                            } else {
                                                                $fileName = 'Image ' . $image['image_id'];
                                                            }
                                                            ?>
                                                            <div class="col-md-3 mb-3">
                                                                <div class="card image-item h-100">
                                                                    <div class="card-body p-3">
                                                                        <div class="d-flex justify-content-between align-items-center">
                                                                            <!-- Image Name on Left -->
                                                                            <div class="image-name-container">
                                                                                <div class="d-flex align-items-center">
                                                                                    <i class="fas fa-file-image text-primary mr-2"></i>
                                                                                    <span class="image-name font-weight-bold text-truncate" 
                                                                                        title="<?php echo htmlspecialchars($fileName); ?>">
                                                                                        <?php echo htmlspecialchars($fileName); ?>
                                                                                    </span>
                                                                                </div>
                                                                            </div>

                                                                            <!-- Status on Right -->
                                                                            <div class="image-actions">
                                                                                <span class="badge badge-<?php echo $image['status_image'] == 'available' ? 'success' : 'info'; ?> py-1 px-2">
                                                                                    <?php echo ucfirst($image['status_image']); ?>
                                                                                </span>
                                                                            </div>
                                                                        </div>
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

                <!-- Action buttons -->
                <div class="row mb-4">
                    <div class="col-12 text-center">
                        <a href="task.php" class="btn btn-secondary mr-2">
                            <i class="fas fa-arrow-left mr-1"></i> Back to Tasks
                        </a>
                        
                        <?php if ($task['status_assignee'] === 'pending'): ?>
                            <button class="btn btn-primary" id="startTaskBtn">
                                <i class="fas fa-play mr-1"></i> Start Task
                            </button>
                        <?php elseif ($task['status_assignee'] === 'in_progress'): ?>
                            <button class="btn btn-success" id="completeTaskBtn">
                                <i class="fas fa-check mr-1"></i> Mark as Completed
                            </button>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </section>
    </div>
</div>

<script>
$(document).ready(function() {
    console.log("View task page loaded.");
    
    // Handle start task button
    $("#startTaskBtn").on('click', function() {
        if (confirm("Are you sure you want to start this task?")) {
            // In production, this would be an AJAX call to update the task status
            alert("Task started successfully!");
            // Redirect to refresh the page or update the UI
            window.location.reload();
        }
    });
    
    // Handle complete task button
    $("#completeTaskBtn").on('click', function() {
        if (confirm("Are you sure you want to mark this task as completed?")) {
            // In production, this would be an AJAX call to update the task status
            alert("Task marked as completed successfully!");
            // Redirect to refresh the page or update the UI
            window.location.reload();
        }
    });
});
</script>

<?php include("includes/footer.php"); ?>