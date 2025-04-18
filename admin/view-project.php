<?php
include("includes/header.php");
require_once 'controllers/project_controller.php';

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

// Get project images
$images = getProjectImages($project_id);

// Get project assignments
$assignments = getProjectAssignments($project_id);

// Functions to get status and priority display classes
function getStatusClass($status) {
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

function getAssignmentStatusClass($status) {
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
                                            <?php echo htmlspecialchars($member['name'] ?? ''); ?>
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
                                        <span class="badge badge-<?php echo getStatusClass($project['status_project']); ?> p-2">
                                            <?php 
                                            $statusText = ucfirst(str_replace('_', ' ', $project['status_project'] ?? 'Unknown'));
                                            $statusIcon = '';
                                            switch($project['status_project']) {
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
                                        <span class="badge badge-<?php echo getPriorityClass($project['priority']); ?> p-2">
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
                            <div class="card-header bg-primary text-white">
                                <h5 class="card-title mb-0">
                                    <i class="fas fa-images mr-2"></i>
                                    Project Images
                                    <span class="badge badge-light ml-2"><?php echo count($images); ?></span>
                                </h5>
                            </div>
                            <div class="card-body p-0">
                                <div class="p-3">
                                    <div class="image-grid-container border-0 shadow-none">
                                        <div class="row" id="projectImagesList">
                                            <?php if (empty($images)): ?>
                                                <div class="col-12">
                                                    <div class="alert alert-info">
                                                        <i class="fas fa-info-circle mr-2"></i> No images have been uploaded for this project yet.
                                                    </div>
                                                </div>
                                            <?php else: ?>
                                                <div class="col-12">
                                                    <div class="row">
                                                        <?php foreach ($images as $index => $image): ?>
                                                            <?php
                                                            $availability = "available";
                                                            $assignment_id = null;
                                                            $assignee_name = "";

                                                            // Check if this image is assigned to anyone
                                                            if (isset($image['assignment_id']) && $image['assignment_id'] > 0) {
                                                                $assignment_id = $image['assignment_id'];

                                                                // Find assignee name by assignment_id
                                                                foreach ($assignments as $assign) {
                                                                    if ($assign['assignment_id'] == $assignment_id) {
                                                                        $assignee_name = $assign['first_name'] ?? 'Unknown';
                                                                        $availability = $assignee_name;
                                                                        break;
                                                                    }
                                                                }
                                                            }

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

                                                                            <!-- Availability on Right -->
                                                                            <div class="image-actions">
                                                                                <span class="badge badge-<?php echo $availability == 'available' ? 'success' : 'info'; ?> py-1 px-2">
                                                                                    <?php echo $availability == 'available' ? 'Available' : '<i class="fas fa-user-circle mr-1"></i>' . ucfirst($availability); ?>
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

                <!-- Assigned Tasks Section -->
                <div class="card theme-purple">
                    <?php
                    // Check if any assignment is overdue
                    $hasOverdueAssignment = false;
                    foreach ($assignments as $assignment) {
                        $deadline = isset($assignment['deadline']) ? new DateTime($assignment['deadline']) : null;
                        $today = new DateTime('today');
                        if ($deadline && $deadline < $today) {
                            $hasOverdueAssignment = true;
                            break;
                        }
                    }
                    ?>
                    <div class="card-header <?php echo $hasOverdueAssignment ? 'bg-danger' : 'bg-purple'; ?> text-white">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-user-friends mr-1"></i> TEAM:
                            <?php if ($hasOverdueAssignment): ?>
                                <span class="badge badge-warning ml-2"><i class="fas fa-exclamation-triangle"></i> Has Overdue Tasks</span>
                            <?php endif; ?>
                        </h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($assignments)): ?>
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle mr-2"></i> No team members have been assigned to this project yet.
                            </div>
                        <?php else: ?>
                            <!-- Task Tabs -->
                            <ul class="nav nav-tabs" id="assignedTasksTabs" role="tablist">
                                <?php foreach ($assignments as $index => $assignment): ?>
                                    <?php
                                    // Check if task is overdue or due today
                                    $deadline = isset($assignment['deadline']) ? new DateTime($assignment['deadline']) : null;
                                    $today = new DateTime('today');
                                    $isOverdue = $deadline && $deadline < $today;
                                    $isDueToday = $deadline && $deadline->format('Y-m-d') === $today->format('Y-m-d');

                                    // Set tab class based on status
                                    $tabStatusClass = '';
                                    if ($isOverdue) {
                                        $tabStatusClass = 'text-danger';
                                    } elseif ($isDueToday) {
                                        $tabStatusClass = 'text-warning';
                                    }
                                    ?>
                                    <li class="nav-item">
                                        <a class="nav-link <?php echo $index === 0 ? 'active' : ''; ?> <?php echo $tabStatusClass; ?>"
                                           id="person-<?php echo $index; ?>-tab" data-toggle="tab"
                                           href="#person-<?php echo $index; ?>" role="tab">
                                            <?php echo $assignment['first_name'] ?? 'Unknown'; ?>
                                            <?php if ($isOverdue || $isDueToday): ?>
                                                <i class="fas fa-exclamation-circle ml-1"
                                                   title="<?php echo $isOverdue ? 'Overdue' : 'Due today'; ?>"></i>
                                            <?php endif; ?>
                                        </a>
                                    </li>
                                <?php endforeach; ?>
                            </ul>

                            <!-- Tab Content -->
                            <div class="tab-content mt-3">
                                <?php foreach ($assignments as $index => $assignment): ?>
                                    <div class="tab-pane fade <?php echo $index === 0 ? 'show active' : ''; ?>"
                                         id="person-<?php echo $index; ?>" role="tabpanel">
                                        <div class="row">
                                            <!-- Right Column: Task Details -->
                                            <div class="col-md-4">
                                                <div class="card">
                                                    <div class="card-header">
                                                        <h6 class="mb-0">Task Details</h6>
                                                    </div>
                                                    <div class="card-body">
                                                        <div class="form-group">
                                                            <label>Assignee Name</label>
                                                            <p class="form-control-static">
                                                                <?php echo htmlspecialchars(($assignment['first_name'] ?? '') . ' ' . ($assignment['last_name'] ?? '')); ?>
                                                            </p>
                                                        </div>
                                                        <div class="form-group">
                                                            <label>Role</label>
                                                            <p class="form-control-static">
                                                                <?php echo htmlspecialchars($assignment['role_task'] ?? ''); ?>
                                                            </p>
                                                        </div>
                                                        <div class="form-group">
                                                            <label>Task Deadline</label>
                                                            <p class="form-control-static">
                                                                <?php 
                                                                echo isset($assignment['deadline']) ? date('Y-m-d', strtotime($assignment['deadline'])) : 'Not set';
                                                                
                                                                // Show deadline status
                                                                if ($deadline) {
                                                                    $days_left = $today->diff($deadline)->format("%R%a");
                                                                    
                                                                    if ($days_left < 0) {
                                                                        echo ' <span class="badge badge-danger">Overdue by ' . abs($days_left) . ' days</span>';
                                                                    } elseif ($days_left == 0) {
                                                                        echo ' <span class="badge badge-warning">Due today</span>';
                                                                    } elseif ($days_left <= 3) {
                                                                        echo ' <span class="badge badge-warning">' . $days_left . ' days left</span>';
                                                                    } else {
                                                                        echo ' <span class="badge badge-info">' . $days_left . ' days left</span>';
                                                                    }
                                                                }
                                                                ?>
                                                            </p>
                                                        </div>
                                                        <div class="form-group">
                                                            <label>Task Status</label>
                                                            <p class="form-control-static">
                                                                <span class="badge badge-<?php echo getAssignmentStatusClass($assignment['status_assignee'] ?? 'pending'); ?> p-2">
                                                                    <?php echo ucfirst($assignment['status_assignee'] ?? 'pending'); ?>
                                                                </span>
                                                            </p>
                                                        </div>
                                                        <div class="form-group">
                                                            <label>Assigned Images</label>
                                                            <p class="form-control-static">
                                                                <span class="badge badge-info p-2">
                                                                    <?php echo $assignment['assigned_images'] ?? 0; ?> images
                                                                </span>
                                                            </p>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            <!-- Left Column: Assigned Images -->
                                            <div class="col-md-8">
                                                <div class="card">
                                                    <div class="card-header">
                                                        <h6 class="mb-0">Assigned Images</h6>
                                                    </div>
                                                    <div class="card-body">
                                                        <div class="row">
                                                            <?php
                                                            // Get assigned images for this assignment
                                                            $assignedImages = []; // Default empty array
                                                            // Check if there are assigned images for this assignment
                                                            foreach ($images as $image) {
                                                                if (isset($image['assignment_id']) && $image['assignment_id'] == $assignment['assignment_id']) {
                                                                    $assignedImages[] = $image;
                                                                }
                                                            }

                                                            if (!empty($assignedImages)):
                                                                ?>
                                                                <?php foreach ($assignedImages as $imageIndex => $image): ?>
                                                                    <?php
                                                                    // Extract filename
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
                                                                    <div class="col-md-4 mb-3">
                                                                        <div class="card h-100 shadow-sm">
                                                                            <div class="card-body p-2">
                                                                                <h6 class="card-title mb-2 text-truncate font-weight-bold">
                                                                                    <?php echo htmlspecialchars($fileName); ?>
                                                                                </h6>
                                                                                <small class="text-muted">
                                                                                    <i class="fas fa-info-circle"></i> 
                                                                                    ID: <?php echo $image['image_id']; ?>
                                                                                </small>
                                                                            </div>
                                                                        </div>
                                                                    </div>
                                                                <?php endforeach; ?>
                                                            <?php else: ?>
                                                                <div class="col-12">
                                                                    <div class="alert alert-info">
                                                                        <i class="fas fa-info-circle mr-2"></i> No images assigned to this team member.
                                                                    </div>
                                                                </div>
                                                            <?php endif; ?>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
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

<script>
// Add some basic JavaScript to handle tab switching
$(document).ready(function() {
    console.log("View project page loaded.");
    
    // Log when tabs are clicked
    $('#assignedTasksTabs a').on('click', function (e) {
        console.log("Tab clicked: " + $(this).text().trim());
    });
});
</script>
<?php include("includes/footer.php"); ?>