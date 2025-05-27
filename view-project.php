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
          WHERE p.project_id = ? AND (p.hidden = 0 OR p.hidden IS NULL)";

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

<?php include("includes/header.php"); ?>
<?php include("includes/nav.php"); ?>

<!-- Custom Styles -->
<style>
    body {
        font-family: 'Arial', sans-serif;
        background-color: #000000;
        color: #f7f7f7;
        overflow-x: hidden;
    }

    /* Glass effect for images table */
    #imagesTable {
        background: rgba(30, 30, 30, 0.4);
        backdrop-filter: blur(10px);
        -webkit-backdrop-filter: blur(10px);
        border: 1px solid rgba(255, 255, 255, 0.1);
    }

    /* Glass effect for assigned rows */
    #imagesTable tr.table-light {
        background: rgba(255, 255, 255, 0.15) !important;
    }

    #imagesTable tr.table-light td {
        color: #000000 !important;
    }

    #imagesTable tr.table-light a {
        color: #000000 !important;
    }

    /* Style for redo items - more vibrant red */
    #imagesTable tr.table-danger {
        background: rgba(255, 40, 40, 0.7) !important;
    }

    #imagesTable tr.table-danger td,
    #imagesTable tr.table-danger a {
        color: #000000 !important;
    }

    /* Hover effects */
    #imagesTable tr.table-light:hover {
        background: rgba(255, 255, 255, 0.25) !important;
    }

    #imagesTable tr.table-danger:hover {
        background: rgba(255, 50, 50, 0.8) !important;
    }

    /* Control Image column width and text truncation */
    #imagesTable td:nth-child(2) {
        max-width: 250px;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    #imagesTable td:nth-child(2) a {
        display: block;
        width: 100%;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .container {
        max-width: 100%;
        padding: 20px;
        overflow-y: auto;
        height: calc(100vh - 60px);
        /* Adjust based on your navbar height */
    }

    .content-wrapper {
        min-height: 100vh;
        overflow-y: auto;
        padding-bottom: 60px;
        /* Space for footer */
    }

    /* Card styles */
    .card {
        margin-bottom: 20px;
        background-color: rgba(30, 30, 30, 0.6);
        border: 1px solid rgba(80, 80, 80, 0.4);
        border-radius: 16px;
    }

    .card-body {
        overflow-y: auto;
        max-height: none;
        /* Remove any max-height restrictions */
    }

    /* Table container styles */
    .table-responsive {
        overflow-y: auto;
        max-height: none;
        /* Remove any max-height restrictions */
    }

    /* Ensure proper spacing for the back button */
    .text-center.mb-4 {
        margin-top: 20px;
        margin-bottom: 40px !important;
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

    .form-control-static {
        color: #f7f7f7;
        background-color: rgba(50, 50, 50, 0.3);
        padding: 0.5rem 0.75rem;
        border-radius: 4px;
        border-left: 3px solid #864937;
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
        background-color: #007bff !important;
        color: rgb(255, 255, 255) !important;
    }

    .badge-info {
        background-color: #17a2b8 !important;
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
        background-color: #ffc107 !important;
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

<div class="background"></div>
<div class="floating-shapes"></div>
<div class="black-covers"></div>
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





    <!-- Project Images Section -->
    <div class="card mb-4">
        <div class="card-header bg-primary text-white">
            <h5 class="card-title mb-0">
                <i class="fas fa-images mr-2"></i>Project Images (Total: <?php echo count($images); ?>)
            </h5>
        </div>
        <div class="card-body">
            <!-- Images Table (View Only) -->
            <div class="table-responsive">
                <table class="table table-bordered table-hover" id="imagesTable">
                    <thead>
                        <tr>
                            <th style="width: 40px;">#</th>
                            <th>Image</th>
                            <th>Assignee</th>
                            <th>Time</th>
                            <th>Role</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($images)): ?>
                            <tr>
                                <td colspan="6" class="text-center">No images uploaded yet.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($images as $index => $image): ?>
                                <?php
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
                                } else {
                                    $estimatedTimeDisplay = 'Not set';
                                }

                                // Determine image status - completely separate from assignee name
                                $statusClass = 'badge-secondary';
                                $statusText = 'Available';

                                if (isset($image['assignment_id']) && $image['assignment_id']) {
                                    $statusClass = 'badge-primary';
                                    $statusText = 'Assigned';
                                }

                                if (isset($image['status_image'])) {
                                    if ($image['status_image'] === 'in_progress') {
                                        $statusClass = 'badge-warning';
                                        $statusText = 'In Progress';
                                    } else if ($image['status_image'] === 'finish') {
                                        $statusClass = 'badge-info';
                                        $statusText = 'Finished';
                                    } else if ($image['status_image'] === 'completed') {
                                        $statusClass = 'badge-success';
                                        $statusText = 'Completed';
                                    }
                                }

                                // Determine row class based on redo status and assignment
                                $rowClass = '';
                                if (isset($image['redo']) && $image['redo'] == '1') {
                                    $rowClass = 'table-danger';
                                } elseif (isset($image['assignment_id']) && $image['assignment_id'] > 0) {
                                    $rowClass = 'table-light';
                                }
                                ?>
                                <tr data-image-id="<?php echo $image['image_id']; ?>" class="<?php echo $rowClass; ?>">
                                    <td class="text-center">
                                        <?php echo $index + 1; ?>
                                    </td>
                                    <td>
                                        <a href="../uploads/projects/<?php echo $project_id; ?>/<?php echo $image['image_path']; ?>"
                                            target="_blank" class="image-preview-link" title="View Image">
                                            <?php echo htmlspecialchars($fileName); ?>
                                        </a>
                                    </td>
                                    <td>
                                        <?php
                                        // Display assignee name
                                        $assigneeName = 'Not Assigned';

                                        // First try to get assignee name from the image data directly
                                        if (isset($image['assignee_first_name']) && !empty($image['assignee_first_name'])) {
                                            $assigneeName = htmlspecialchars($image['assignee_first_name']);
                                            if (isset($image['assignee_last_name']) && !empty($image['assignee_last_name'])) {
                                                $assigneeName .= ' ' . htmlspecialchars($image['assignee_last_name']);
                                            }
                                        }
                                        // If not found, try to get from assignments array
                                        else if (isset($image['assignment_id']) && $image['assignment_id'] > 0) {
                                            foreach ($assignments as $assignment) {
                                                if ($assignment['assignment_id'] == $image['assignment_id']) {
                                                    // Try to get user name from the assignment
                                                    if (isset($assignment['user_first_name']) && !empty($assignment['user_first_name'])) {
                                                        $assigneeName = htmlspecialchars($assignment['user_first_name']);
                                                        if (isset($assignment['user_last_name']) && !empty($assignment['user_last_name'])) {
                                                            $assigneeName .= ' ' . htmlspecialchars($assignment['user_last_name']);
                                                        }
                                                    }
                                                    // If no name in assignment, try to use username
                                                    else if (isset($assignment['username']) && !empty($assignment['username'])) {
                                                        $assigneeName = htmlspecialchars($assignment['username']);
                                                    }
                                                    // If still no name, use user_id as last resort
                                                    else if (isset($assignment['user_id']) && $assignment['user_id'] > 0) {
                                                        $assigneeName = 'User #' . $assignment['user_id'];
                                                    }
                                                    break;
                                                }
                                            }
                                        }

                                        echo $assigneeName;
                                        ?>
                                    </td>
                                    <td>
                                        <?php echo $estimatedTimeDisplay; ?>
                                    </td>
                                    <td>
                                        <?php echo !empty($image['image_role']) ? htmlspecialchars($image['image_role']) : 'Not Set'; ?>
                                    </td>
                                    <td>
                                        <span class="badge <?php echo $statusClass; ?>">
                                            <?php echo $statusText; ?>
                                        </span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
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
                <div class="col-md-4 mb-3">
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
                <div class="col-md-4 mb-3">
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


                <!-- Days Until Deadline -->
                <div class="col-md-4 mb-3">
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
    <!-- Back Button -->
    <div class="text-center mb-4">
        <a href="project-status.php" class="btn btn-secondary">
            <i class="fas fa-arrow-left mr-2"></i>Back to Project Status
        </a>
    </div>
</div>




<?php include("includes/footer.php"); ?>