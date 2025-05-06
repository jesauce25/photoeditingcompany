<?php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include necessary files
require_once 'includes/db_connection.php';

// Check if project ID is provided
if (!isset($_GET['id'])) {
    header("Location: project-status.php");
    exit();
}

$project_id = intval($_GET['id']);

// Function to get status and priority classes
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

// Fetch project data
$query = "SELECT p.*, c.company_name 
          FROM tbl_projects p 
          LEFT JOIN tbl_companies c ON p.company_id = c.company_id 
          WHERE p.project_id = ?";

$stmt = $conn->prepare($query);
$stmt->bind_param("i", $project_id);
$stmt->execute();
$result = $stmt->get_result();
$project = $result->fetch_assoc();

if (!$project) {
    header("Location: project-status.php");
    exit();
}

// Get project images
$images_query = "SELECT pi.*, pa.assignment_id, u.first_name AS assignee_first_name, u.last_name AS assignee_last_name
                FROM tbl_project_images pi
                LEFT JOIN tbl_project_assignments pa ON pi.assignment_id = pa.assignment_id
                LEFT JOIN tbl_users u ON pa.user_id = u.user_id
                WHERE pi.project_id = ?";

$images_stmt = $conn->prepare($images_query);
$images_stmt->bind_param("i", $project_id);
$images_stmt->execute();
$images_result = $images_stmt->get_result();
$images = $images_result->fetch_all(MYSQLI_ASSOC);

// Get project assignments
$assignments_query = "SELECT pa.*, 
                      (SELECT COUNT(*) FROM tbl_project_images WHERE assignment_id = pa.assignment_id) as assigned_images
                      FROM tbl_project_assignments pa 
                      WHERE pa.project_id = ? AND pa.status_assignee != 'deleted'";

$assignments_stmt = $conn->prepare($assignments_query);
$assignments_stmt->bind_param("i", $project_id);
$assignments_stmt->execute();
$assignments_result = $assignments_stmt->get_result();
$assignments = $assignments_result->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Project - RafaelItServices</title>

    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.1/css/all.min.css">

    <!-- Custom Styles -->
    <style>
        body {
            font-family: 'Arial', sans-serif;
            background-color: #000000;
            color: #f7f7f7;
        }

        /* Enhanced Glass Effect */
        .glass-card {
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            background-color: rgba(30, 30, 30, 0.6);
            border-radius: 16px;
            box-shadow: 0 4px 30px rgba(0, 0, 0, 0.3);
            border: 1px solid rgba(80, 80, 80, 0.4);
            transition: all 0.3s ease;
        }

        .card {
            background-color: rgba(30, 30, 30, 0.6);
            border: 1px solid rgba(80, 80, 80, 0.4);
            border-radius: 16px;
            transition: all 0.3s ease;
            margin-bottom: 1.5rem;
        }

        .card-header {
            background-color: rgba(40, 40, 40, 0.7);
            border-bottom: 1px solid rgba(80, 80, 80, 0.4);
            color: #ffb22e;
            border-top-left-radius: 16px !important;
            border-top-right-radius: 16px !important;
        }

        .card-header .card-title {
            color: #ffb22e;
            font-weight: 600;
        }

        .card-body {
            color: #f7f7f7;
        }

        .form-control-static {
            color: #f7f7f7;
            background-color: rgba(50, 50, 50, 0.3);
            padding: 0.5rem 0.75rem;
            border-radius: 4px;
            border-left: 3px solid #864937;
        }

        .content-wrapper {
            background-color: transparent;
        }

        .table td,
        .table th {
            vertical-align: middle;
            color: #f7f7f7;
            border-top: 1px solid rgba(80, 80, 80, 0.4);
        }

        .table thead th {
            background-color: rgba(20, 20, 20, 0.7);
            font-weight: 600;
            color: #ffb22e;
        }

        /* Badge styling */
        .badge-primary {
            background-color: #ffb22e !important;
            color: #000000 !important;
        }

        .badge-info {
            background-color: #864937 !important;
            color: #f7f7f7 !important;
        }

        .badge-secondary {
            background-color: #6c757d !important;
            color: #f7f7f7 !important;
        }

        .badge-success {
            background-color: #28a745 !important;
        }

        .badge-warning {
            background-color: #ffb22e !important;
            color: #000000 !important;
        }

        .badge-danger {
            background-color: #dc3545 !important;
        }

        /* Button styling */
        .btn-primary {
            background-color: #ffb22e !important;
            border-color: #ffb22e !important;
            color: #000000 !important;
        }

        .btn-primary:hover {
            background-color: #ffa500 !important;
            border-color: #ffa500 !important;
        }

        .btn-info {
            background-color: #864937 !important;
            border-color: #864937 !important;
            color: #f7f7f7 !important;
        }

        .btn-info:hover {
            background-color: #754027 !important;
            border-color: #754027 !important;
        }

        .btn-secondary {
            background-color: #555555 !important;
            border-color: #666666 !important;
        }

        /* Breadcrumb styling */
        .breadcrumb {
            background-color: rgba(30, 30, 30, 0.6);
        }

        .breadcrumb-item.active {
            color: #f7f7f7;
        }

        .breadcrumb-item a {
            color: #ffb22e;
        }

        /* Text color fixes for better contrast */
        .text-info {
            color: #4dd0e1 !important;
            /* Brighter cyan for better contrast */
        }

        .text-warning {
            color: #ffca28 !important;
            /* Brighter yellow for warning text */
        }

        .text-danger {
            color: #ff6b6b !important;
            /* Brighter red for danger text */
        }

        .text-muted {
            color: #9e9e9e !important;
            /* Lighter gray for muted text */
        }

        /* Label styling */
        label {
            color: #ffb22e;
            font-weight: 500;
        }

        /* Team member styling */
        .team-member-col {
            background-color: rgba(50, 50, 50, 0.3);
            padding: 0.5rem;
            border-radius: 4px;
            border-left: 3px solid #864937;
        }

        /* Total images display */
        .total-images-display {
            background: linear-gradient(135deg, #864937, #754027);
            padding: 8px 15px;
            border-radius: 8px;
            display: inline-block;
        }

        /* Custom scrollbar for the dark theme */
        ::-webkit-scrollbar {
            width: 8px;
            height: 8px;
        }

        ::-webkit-scrollbar-track {
            background: rgba(30, 30, 30, 0.6);
        }

        ::-webkit-scrollbar-thumb {
            background: #864937;
            border-radius: 4px;
        }

        ::-webkit-scrollbar-thumb:hover {
            background: #ffb22e;
        }

        /* Image container styling */
        .image-container {
            background-color: rgba(40, 40, 40, 0.7) !important;
            border: 1px solid rgba(80, 80, 80, 0.4) !important;
        }

        /* Progress bar styling */
        .progress {
            background-color: rgba(50, 50, 50, 0.5);
        }

        /* Alert styling */
        .alert-warning {
            background-color: rgba(255, 178, 46, 0.2);
            border-color: rgba(255, 178, 46, 0.3);
            color: #ffca28;
        }

        .alert-info {
            background-color: rgba(77, 208, 225, 0.2);
            border-color: rgba(77, 208, 225, 0.3);
            color: #4dd0e1;
        }

        /* Modal styling */
        .modal-content {
            background-color: rgba(30, 30, 30, 0.9);
            border: 1px solid rgba(80, 80, 80, 0.4);
        }

        .modal-header {
            border-bottom: 1px solid rgba(80, 80, 80, 0.4);
        }

        .modal-footer {
            border-top: 1px solid rgba(80, 80, 80, 0.4);
        }

        /* Fix contrast for badges on hover */
        a:hover .badge {
            opacity: 0.9;
        }
    </style>
</head>

<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-light bg-white">
        <div class="container">
            <a class="navbar-brand font-weight-bold" href="index.php">
                <i class="fas fa-image mr-2 text-primary"></i>RafaelItServices
            </a>
            <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarNav"
                aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ml-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="project-status.php">
                            <i class="fas fa-project-diagram mr-1"></i> Project Status
                        </a>
                    </li>
                    <?php if (isset($_SESSION['user_logged_in']) && $_SESSION['user_logged_in']): ?>
                        <li class="nav-item">
                            <a class="nav-link" href="logout.php">
                                <i class="fas fa-sign-out-alt mr-1"></i> Logout
                            </a>
                        </li>
                    <?php else: ?>
                        <li class="nav-item">
                            <a class="nav-link" href="login.php">
                                <i class="fas fa-sign-in-alt mr-1"></i> Login
                            </a>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="container py-4">
        <!-- Page Heading -->
        <div class="d-sm-flex align-items-center justify-content-between mb-4">
            <h1 class="h3 mb-0 text-gray-800">
                <i class="fas fa-eye mr-2"></i>Project Details
            </h1>
            <ol class="breadcrumb bg-transparent p-0 mb-0">
                <li class="breadcrumb-item"><a href="index.php">Home</a></li>
                <li class="breadcrumb-item"><a href="project-status.php">Project Status</a></li>
                <li class="breadcrumb-item active">View Project</li>
            </ol>
        </div>

        <!-- Project Overview Row -->
        <div class="row mb-4">
            <!-- Project Details Card -->
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

            <!-- Project Status Card -->
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
                                    <?php echo ucfirst(str_replace('_', ' ', $project['status_project'] ?? 'Unknown')); ?>
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

        <!-- Progress Summary Card -->
        <div class="card mb-4">
            <div class="card-header bg-info text-white">
                <h5 class="card-title mb-0">
                    <i class="fas fa-chart-pie mr-2"></i>Project Progress Summary
                </h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <!-- Total Images -->
                    <div class="col-md-3 mb-3">
                        <div class="card border-left-primary shadow h-100 py-2">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                            Total Images</div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800">
                                            <?php echo count($images); ?>
                                        </div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-images fa-2x text-gray-300"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Team Members -->
                    <div class="col-md-3 mb-3">
                        <div class="card border-left-success shadow h-100 py-2">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                            Team Members</div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800">
                                            <?php echo count($assignments); ?>
                                        </div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-users fa-2x text-gray-300"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Progress Status -->
                    <div class="col-md-3 mb-3">
                        <div class="card border-left-info shadow h-100 py-2">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="text-xs font-weight-bold text-info text-uppercase mb-1">Overall
                                            Progress
                                        </div>
                                        <?php
                                        // Calculate percentage based on project status
                                        $progressMap = [
                                            'pending' => 10,
                                            'in_progress' => 40,
                                            'review' => 75,
                                            'completed' => 100,
                                            'delayed' => 30
                                        ];
                                        $progressPercent = $progressMap[$project['status_project']] ?? 0;
                                        ?>
                                        <div class="row no-gutters align-items-center">
                                            <div class="col-auto">
                                                <div class="h5 mb-0 mr-3 font-weight-bold text-gray-800">
                                                    <?php echo $progressPercent; ?>%
                                                </div>
                                            </div>
                                            <div class="col">
                                                <div class="progress progress-sm mr-2">
                                                    <div class="progress-bar bg-info" role="progressbar"
                                                        style="width: <?php echo $progressPercent; ?>%"
                                                        aria-valuenow="<?php echo $progressPercent; ?>"
                                                        aria-valuemin="0" aria-valuemax="100"></div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-clipboard-list fa-2x text-gray-300"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Days Until Deadline -->
                    <div class="col-md-3 mb-3">
                        <div class="card border-left-warning shadow h-100 py-2">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                            <?php echo $is_past ? 'Overdue By' : 'Days Until Deadline'; ?>
                                        </div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800">
                                            <?php echo $is_past ? $days_diff . ' days' : $days_diff . ' days'; ?>
                                        </div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-calendar fa-2x text-gray-300"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Project Images Section -->
        <div class="card mb-4">
            <div class="card-header bg-primary text-white">
                <h5 class="card-title mb-0">
                    <i class="fas fa-images mr-2"></i>Project Images (Total: <?php echo count($images); ?>)
                </h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <?php if (empty($images)): ?>
                        <div class="col-12 text-center py-4">
                            <p class="text-muted mb-0">
                                <i class="fas fa-info-circle mr-2"></i>No images uploaded yet.
                            </p>
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
                            <div class="col-md-6 col-lg-3 mb-3">
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

        <!-- Team Assignments Section -->
        <div class="card mb-4">
            <div class="card-header bg-primary text-white">
                <h5 class="card-title mb-0">
                    <i class="fas fa-users mr-2"></i>Team Assignments
                </h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered table-hover">
                        <thead class="thead-light">
                            <tr>
                                <th>Team Member</th>
                                <th>Role</th>
                                <th>Assigned Images</th>
                                <th>Status</th>
                                <th>Deadline</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($assignments)): ?>
                                <tr>
                                    <td colspan="5" class="text-center">No team assignments found.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($assignments as $assignment): ?>
                                    <tr>
                                        <td>
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
                                        </td>
                                        <td>
                                            <span class="badge badge-info">
                                                <?php echo $assignment['role_task'] ? htmlspecialchars($assignment['role_task']) : 'Not Set'; ?>
                                            </span>
                                        </td>
                                        <td>
                                            <span class="badge badge-primary">
                                                <?php echo $assignment['assigned_images']; ?> Images
                                            </span>
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
                                                <div class="progress-bar bg-<?php echo $statusClass; ?>" role="progressbar"
                                                    style="width: <?php echo $progressPercent; ?>%"
                                                    aria-valuenow="<?php echo $progressPercent; ?>" aria-valuemin="0"
                                                    aria-valuemax="100"></div>
                                            </div>
                                        </td>
                                        <td>
                                            <?php
                                            $deadline_date = new DateTime($assignment['deadline']);
                                            $today = new DateTime('today');
                                            $deadline_status = '';
                                            $badge_class = '';

                                            if ($today > $deadline_date) {
                                                // Calculate days overdue
                                                $interval = $deadline_date->diff($today);
                                                $days_overdue = $interval->days;
                                                $deadline_status = 'Overdue by ' . $days_overdue . ' day' . ($days_overdue != 1 ? 's' : '');
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

                                                <?php if ($today > $deadline_date && !empty($assignment['delay_acceptable'])): ?>
                                                    <span class="ml-2 badge badge-info">
                                                        <i class="fas fa-check mr-1"></i>Delay Accepted
                                                    </span>
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

        <!-- Back Button -->
        <div class="text-center mb-4">
            <a href="project-status.php" class="btn btn-secondary">
                <i class="fas fa-arrow-left mr-2"></i>Back to Project Status
            </a>
        </div>
    </div>

    <!-- Footer -->
    <footer class="bg-dark text-white py-4 mt-5">
        <div class="container">
            <div class="row">
                <div class="col-md-6">
                    <h5>RafaelItServices</h5>
                    <p>Professional photo editing solutions for businesses worldwide.</p>
                </div>
                <div class="col-md-3">
                    <h5>Quick Links</h5>
                    <ul class="list-unstyled">
                        <li><a href="index.php" class="text-white">Home</a></li>
                        <li><a href="project-status.php" class="text-white">Project Status</a></li>
                    </ul>
                </div>
                <div class="col-md-3">
                    <h5>Contact</h5>
                    <address class="mb-0">
                        <i class="fas fa-envelope mr-2"></i> info@rafaelitservices.com<br>
                        <i class="fas fa-phone mr-2"></i> (123) 456-7890
                    </address>
                </div>
            </div>
            <div class="text-center mt-3">
                <p class="mb-0">&copy; <?php echo date('Y'); ?> RafaelItServices. All rights reserved.</p>
            </div>
        </div>
    </footer>

    <!-- Bootstrap & jQuery JS -->
    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>

    <!-- Add tooltips script at the end, before the include for footer -->
    <script>
        $(document).ready(function () {
            // Add tooltips for better usability
            $('[title]').tooltip({
                placement: 'top',
                container: 'body',
                template: '<div class="tooltip" role="tooltip"><div class="tooltip-arrow"></div><div class="tooltip-inner" style="background-color: rgba(30, 30, 30, 0.95); border: 1px solid #864937; color: #f7f7f7;"></div></div>'
            });
        });
    </script>

    <?php include("includes/footer.php"); ?>
</body>

</html>