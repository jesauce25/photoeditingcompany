<?php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include necessary files
require_once 'includes/db_connection.php';

// Include helper functions for formatting
function getStatusClass($status)
{
    switch ($status) {
        case 'pending':
            return 'pending';
        case 'in_progress':
            return 'in_progress';
        case 'review':
            return 'review';
        case 'completed':
            return 'completed';
        case 'delayed':
            return 'delayed';
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
        default:
            return 'secondary';
    }
}

// Function to get project assignees
function getProjectAssignee($project_id)
{
    global $conn;

    $query = "SELECT pa.*, u.first_name, u.last_name, u.user_id 
              FROM tbl_project_assignments pa 
              LEFT JOIN tbl_users u ON pa.user_id = u.user_id 
              WHERE pa.project_id = ? AND pa.status_assignee != 'deleted'";

    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $project_id);
    $stmt->execute();
    $result = $stmt->get_result();

    return $result->fetch_all(MYSQLI_ASSOC);
}

// Filters
$company_filter = isset($_GET['company']) ? intval($_GET['company']) : 0;
$status_filter = isset($_GET['status']) ? $_GET['status'] : '';
$overdue_filter = isset($_GET['overdue']) ? $_GET['overdue'] : '';

// Get all companies for filter dropdown
$company_query = "SELECT * FROM tbl_companies ORDER BY company_name ASC";
$company_result = $conn->query($company_query);
$companies = $company_result->fetch_all(MYSQLI_ASSOC);

// Build query to get all projects with filter support
$query = "SELECT p.*, c.company_name, 
          (SELECT COUNT(*) FROM tbl_project_images WHERE project_id = p.project_id) as total_images,
          CASE WHEN p.deadline < CURDATE() THEN 1 ELSE 0 END as is_overdue
          FROM tbl_projects p
          LEFT JOIN tbl_companies c ON p.company_id = c.company_id
          WHERE 1=1";

// Add filters
$params = [];
$param_types = "";

if ($company_filter > 0) {
    $query .= " AND p.company_id = ?";
    $params[] = $company_filter;
    $param_types .= "i";
}

if (!empty($status_filter)) {
    $query .= " AND p.status_project = ?";
    $params[] = $status_filter;
    $param_types .= "s";
}

if (!empty($overdue_filter)) {
    if ($overdue_filter === 'overdue') {
        $query .= " AND p.deadline < CURDATE()";
    } else if ($overdue_filter === 'upcoming') {
        $query .= " AND p.deadline >= CURDATE()";
    }
}

// Order by project_id in ascending order (first projects at top)
$query .= " ORDER BY p.project_id ASC";

$stmt = $conn->prepare($query);

// Bind parameters if any
if (!empty($params)) {
    $stmt->bind_param($param_types, ...$params);
}

$stmt->execute();
$result = $stmt->get_result();
$projects = $result->fetch_all(MYSQLI_ASSOC);
?>
<?php include("includes/header.php"); ?>
<?php include("includes/nav.php"); ?>


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
        border: none;
        transition: all 0.3s ease;
    }

    .card-header {
        background-color: rgba(40, 40, 40, 0.7);
        border-bottom: 1px solid rgba(80, 80, 80, 0.4);
        color: #ffb22e;
    }

    .table th {
        font-weight: 500;
        font-size: 0.9rem;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        color: #ffb22e;
    }

    .table td,
    .table th {
        vertical-align: middle;
        color: #f7f7f7;
        border-top: 1px solid rgba(80, 80, 80, 0.4);
    }

    .table-hover tbody tr:hover {
        background-color: rgba(255, 178, 46, 0.1);
    }

    /* Assignee styling */
    .assignee-avatar {
        width: 30px;
        height: 30px;
        border-radius: 50%;
        background-color: rgba(134, 73, 55, 0.8);
        color: white;
        display: flex;
        justify-content: center;
        align-items: center;
        font-size: 1rem;
        margin-right: 5px;
        margin-bottom: 3px;
    }

    /* Assignee overdue styles */
    .assignee-overdue {
        background-color: rgb(166, 0, 17) !important;
    }

    .assignee-acceptable {
        background-color: rgb(166, 0, 17) !important;
        border: 2px solid rgb(0, 255, 60) !important;
    }

    /* Role styling */
    .role-badge {
        background-color: rgba(134, 73, 55, 0.8);
        color: #f7f7f7;
        font-size: 1.1rem !important;
        padding: 4px 8px !important;
        border-radius: 4px;
        margin-right: 4px;
        margin-bottom: 4px;
        display: inline-block;
    }

    /* Fullscreen Mode */
    .fullscreen-btn {
        position: absolute;
        top: 0px;
        right: 0px;
        z-index: 100;
        background-color: rgba(255, 178, 46, 0.2);
        border: 1px solid rgba(255, 178, 46, 0.5);
        color: #ffb22e;
        width: 40px;
        height: 40px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        transition: all 0.3s ease;
    }

    .fullscreen-btn:hover {
        background-color: rgba(255, 178, 46, 0.3);
        transform: scale(1.1);
    }

    /* Fullscreen Mode */
    body.fullscreen-mode {
        padding: 0 !important;
        overflow: hidden !important;
    }

    body.fullscreen-mode .navbar,
    body.fullscreen-mode .py-4>.d-sm-flex,
    body.fullscreen-mode .card:not(#projectTableCard),
    body.fullscreen-mode footer {
        display: none !important;
    }

    body.fullscreen-mode #projectTableCard {
        position: fixed !important;
        top: 0 !important;
        left: 0 !important;
        right: 0 !important;
        bottom: 0 !important;
        z-index: 9999 !important;
        margin: 0 !important;
        border-radius: 0 !important;
        backdrop-filter: blur(20px) !important;
        -webkit-backdrop-filter: blur(20px) !important;
        background-color: rgba(30, 30, 30, 0.9) !important;
        overflow: hidden !important;
    }

    body.fullscreen-mode .card-body {
        padding: 0 !important;
        height: 100vh !important;
        overflow: hidden !important;
    }

    body.fullscreen-mode .table-responsive {
        height: 100vh !important;
        max-height: 100vh !important;
        overflow: auto !important;
        padding: 20px !important;
    }

    body.fullscreen-mode .table-responsive table {
        transform: scale(1.1) !important;
        transform-origin: top center !important;
        margin-top: 20px !important;
    }

    body.fullscreen-mode .fullscreen-btn {
        position: fixed !important;
        top: 10px !important;
        right: 10px !important;
        z-index: 10000 !important;
    }

    body.fullscreen-mode .fullscreen-btn i.fa-expand {
        display: none;
    }

    body.fullscreen-mode .fullscreen-btn i.fa-compress {
        display: inline-block;
    }

    .fullscreen-btn i.fa-compress {
        display: none;
    }

    /* Enhanced table appearance */
    .table {
        background-color: rgba(40, 40, 40, 0.5);
        backdrop-filter: blur(5px);
        -webkit-backdrop-filter: blur(5px);
    }



    footer {
        backdrop-filter: blur(10px);
        -webkit-backdrop-filter: blur(10px);
        background-color: rgba(30, 30, 30, 0.8);
    }

    /* DataTables styling */
    .dataTables_wrapper .dataTables_info,
    .dataTables_wrapper .dataTables_length,
    .dataTables_wrapper .dataTables_filter,
    .dataTables_wrapper .dataTables_paginate {
        color: #f7f7f7 !important;
    }

    .dataTables_wrapper .dataTables_paginate .paginate_button {
        color: #f7f7f7 !important;
    }

    .dataTables_wrapper .dataTables_paginate .paginate_button.current,
    .dataTables_wrapper .dataTables_paginate .paginate_button.current:hover {
        background: rgba(255, 178, 46, 0.3) !important;
        color: #f7f7f7 !important;
        border-color: #864937 !important;
    }

    .dataTables_wrapper .dataTables_paginate .paginate_button:hover {
        background: rgba(255, 178, 46, 0.2) !important;
        color: #ffb22e !important;
        border-color: #864937 !important;
    }

    .dataTables_wrapper .dataTables_length select,
    .dataTables_wrapper .dataTables_filter input {
        background-color: rgba(40, 40, 40, 0.7) !important;
        color: #f7f7f7 !important;
        border: 1px solid #864937 !important;
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

    /* Form control styling */
    .form-control {
        background-color: rgba(40, 40, 40, 0.7) !important;
        color: #f7f7f7 !important;
        border: 1px solid #864937 !important;
        height: 30px !important;
        min-height: 30px !important;
        line-height: 30px !important;
    }

    /* Ensure dropdown text is visible */
    select.form-control {
        color: #f7f7f7 !important;
        background-color: #333333 !important;
        height: 30px !important;
        min-height: 30px !important;
        line-height: 30px !important;
        padding: 0 8px !important;
        padding-right: 25px !important;
        background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 12 12'%3E%3Cpath fill='%23ffb22e' d='M6 8.825L1.175 4 2.05 3.125 6 7.075 9.95 3.125 10.825 4z'/%3E%3C/svg%3E") !important;
        background-repeat: no-repeat !important;
        background-position: right 8px center !important;
        background-size: 12px !important;
        appearance: none !important;
        -webkit-appearance: none !important;
        -moz-appearance: none !important;
    }

    select.form-control option {
        color: #f7f7f7 !important;
        background-color: #333333 !important;
        padding: 8px !important;
        height: auto !important;
        min-height: 30px !important;
    }

    /* Ensure proper contrast for dropdown items */
    select.form-control option {
        background-color: #333333 !important;
    }

    /* Ensure focus doesn't hide text */
    .form-control:focus {
        color: #f7f7f7 !important;
        border-color: #ffb22e !important;
        box-shadow: 0 0 0 0.2rem rgba(255, 178, 46, 0.25) !important;
    }

    /* Add these improved contrast styles */
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

    /* Ensure DataTables elements have proper contrast */
    .dataTables_wrapper .dataTables_paginate .paginate_button {
        color: #f7f7f7 !important;
        background: rgba(60, 60, 60, 0.4) !important;
        border: 1px solid rgba(100, 100, 100, 0.4) !important;
    }

    .dataTables_wrapper .dataTables_paginate .paginate_button:hover:not(.disabled) {
        background: rgba(255, 178, 46, 0.3) !important;
        color: #ffffff !important;
    }

    .paginate_button.disabled {
        opacity: 0.5;
    }

    /* Make table header text more prominent */
    .table thead th {
        background-color: rgba(20, 20, 20, 0.7);
        font-weight: 600;
        color: #ffb22e;
    }

    /* Fix any table borders */
    .table-bordered td,
    .table-bordered th {
        border-color: rgba(100, 100, 100, 0.4) !important;
    }

    /* Badge contrast improvements */
    .badge {
        font-weight: 600;
        padding: 0.35em 0.65em;
    }

    /* Make small text more readable */
    .small {
        font-weight: 500;
    }

    /* Fix tooltip display */
    .tooltip-inner {
        background-color: rgba(30, 30, 30, 0.95);
        border: 1px solid #864937;
        color: #f7f7f7;
    }

    /* Fix card header with better contrast */
    .card-header h5 {
        color: #ffb22e;
        font-weight: 600;
    }

    /* Improve table hover for better visibility */
    .table-hover tbody tr:hover {
        background-color: rgba(255, 178, 46, 0.15) !important;
    }

    /* Make the All Projects container full width */
    #projectTableCard {
        width: 100vw;
        max-width: 100vw;
        position: relative;
        left: 50%;
        right: 50%;
        margin-left: -50vw;
        margin-right: -50vw;
        border-radius: 0 !important;
        min-height: 75vh;
    }
</style>

<style>
    /* Action */

    /* Stronger red for overdue with pulse effect */
    .table-danger,
    table.table tr.table-danger,
    #projectTable tbody tr.table-danger,
    table.dataTable#projectTable tbody tr.table-danger {
        background-color: rgb(255, 0, 25) !important;
        animation: pulse-red 2s infinite !important;
        color: #ffffff !important;
    }

    .table-danger td,
    table.table tr.table-danger td,
    #projectTable tbody tr.table-danger td,
    table.dataTable#projectTable tbody tr.table-danger td {
        background-color: transparent !important;
        color: #ffffff !important;
    }

    @keyframes pulse-red {
        0% {
            transform: scaleY(1);
            background-color: #e63131 !important;
            box-shadow: 0 0 0 0 rgba(255, 0, 0, 0.7);
        }

        70% {
            transform: scaleY(1.05);
            background-color: #ff0000 !important;
            box-shadow: 0 0 0 10px rgba(255, 0, 0, 0);
        }

        100% {
            transform: scaleY(1);
            background-color: #e63131 !important;
            box-shadow: 0 0 0 0 rgba(255, 0, 0, 0);
        }
    }

    /* Stronger orange for tomorrow deadline with dark text */
    .table-warning,
    table.table tr.table-warning,
    #projectTable tbody tr.table-warning,
    table.dataTable#projectTable tbody tr.table-warning {
        background-color: rgb(255, 140, 0) !important;
        color: #000000 !important;
    }

    .table-warning td,
    table.table tr.table-warning td,
    #projectTable tbody tr.table-warning td,
    table.dataTable#projectTable tbody tr.table-warning td {
        background-color: transparent !important;
        color: #000000 !important;
    }


    /* Fix any contrast issues with orange background */
    .table-warning .text-info,
    .table-warning .text-muted,
    .table-warning .text-warning {
        color: #000000 !important;
        font-weight: 600;
    }



    /* Custom status colors */
    .badge-pending {
        background-color: #ffc107 !important;
        color: #000000 !important;
    }

    .badge-in-progress,
    .badge-in_progress {
        background-color: #17a2b8 !important;
        color: #ffffff !important;
    }

    .badge-review {
        background-color: #6f42c1 !important;
        color: #ffffff !important;
    }

    .badge-completed {
        background-color: #28a745 !important;
        color: #ffffff !important;
    }

    .badge-delayed {
        background-color: #dc3545 !important;
        color: #ffffff !important;
    }

    /* Fix datatable container width */
    .dataTables_wrapper {
        width: 100%;
        overflow-x: auto;
    }

    /* Enhanced text styling for specific columns */
    #projectTable td:nth-child(1) {
        font-weight: bold !important;
        letter-spacing: 0.05em !important;
    }

    #projectTable td:nth-child(1),

    #projectTable td:nth-child(3),
    #projectTable td:nth-child(4),
    #projectTable td:nth-child(5) {
        font-size: 1.1rem !important;
        font-weight: 900 !important;
    }

    /* Compact table rows with reduced padding */
    .table td,
    .table th {
        padding: 0.1rem 1rem !important;
        vertical-align: middle;
        font-size: 0.9rem;
    }
</style>

<style>
    /* Tooltip hover fixes */
    .assignee-avatar,
    .role-badge {
        cursor: pointer !important;
    }

    /* Ensure tooltips are visible in fullscreen */
    .tooltip {
        z-index: 10001 !important;
        opacity: 1 !important;
    }

    body.fullscreen-mode .tooltip {
        z-index: 11000 !important;
        opacity: 1 !important;
        visibility: visible !important;
        pointer-events: auto !important;
    }

    body.fullscreen-mode .tooltip-inner {
        background-color: rgba(30, 30, 30, 0.95) !important;
        border: 1px solid #864937 !important;
        color: #f7f7f7 !important;
        font-size: 14px !important;
        padding: 8px 12px !important;
        max-width: 300px !important;
    }

    /* Fix for fullscreen hover */
    body.fullscreen-mode * {
        cursor: auto !important;
    }

    body.fullscreen-mode .role-badge,
    body.fullscreen-mode .assignee-avatar {
        cursor: pointer !important;
    }
</style>

<div class="background"></div>
<div class="floating-shapes"></div>
<div class="black-covers"></div>
<!-- Main Content -->
<div class="container py-4">

    <!-- Move the filters section inside the projectTableCard - replace the existing card structure -->
    <div class="card glass-card" id="projectTableCard">
        <div class="card-header position-relative d-flex justify-content-between align-items-center"
            style="padding: 6px !important;">
            <h5 class="mb-0 mr-2"><i class="fas fa-table mr-2"></i>All Projects</h5>

            <!-- Filters in the center of header -->
            <div class="d-flex flex-grow-1 justify-content-center">
                <form method="get" action="project-status.php" class="d-flex align-items-center flex-row">
                    <div class="mr-2">
                        <select class="form-control form-control-sm" id="company" name="company"
                            style="width: 140px; height: 30px !important;">
                            <option value="0">All Companies</option>
                            <?php foreach ($companies as $company): ?>
                                <option value="<?php echo $company['company_id']; ?>" <?php echo ($company_filter == $company['company_id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($company['company_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mr-2">
                        <select class="form-control form-control-sm" id="status" name="status"
                            style="width: 120px; height: 30px !important;">
                            <option value="">All Statuses</option>
                            <option value="pending" <?php echo ($status_filter == 'pending') ? 'selected' : ''; ?>>Pending
                            </option>
                            <option value="in_progress" <?php echo ($status_filter == 'in_progress') ? 'selected' : ''; ?>>In Progress</option>
                            </option>
                            <option value="completed" <?php echo ($status_filter == 'completed') ? 'selected' : ''; ?>>
                                Completed</option>
                            </option>
                        </select>
                    </div>
                    <div class="mr-2">
                        <select class="form-control form-control-sm" id="overdue" name="overdue"
                            style="width: 120px; height: 30px !important;">
                            <option value="">All Projects</option>
                            <option value="overdue" <?php echo ($overdue_filter == 'overdue') ? 'selected' : ''; ?>>
                                Overdue</option>
                            <option value="upcoming" <?php echo ($overdue_filter == 'upcoming') ? 'selected' : ''; ?>>
                                Upcoming</option>
                        </select>
                    </div>
                    <div class="d-flex">
                        <button type="submit" class="btn btn-xs btn-primary mr-1"
                            style="height: 30px; padding: 0 10px !important;">
                            <i class="fas fa-search fa-xs"></i> Apply
                        </button>

                        <a href="project-status.php" class="btn btn-xs btn-primary d-flex align-items-center"
                            style="height: 30px; padding: 0 10px !important;">
                            <i class="fas fa-undo fa-xs mr-1"></i><span>Reset</span>
                        </a>


                    </div>
                </form>
            </div>

            <!-- Fullscreen button on the right -->
            <div class="fullscreen-btn" id="fullscreenToggle">
                <i class="fas fa-expand"></i>
                <i class="fas fa-compress"></i>
            </div>
        </div>
        <div class="card-body">
            <!-- Table section remains the same -->
            <div class="table-responsive table-container">
                <table class="table table-hover" id="projectTable" width="100%" cellspacing="0">
                    <thead>
                        <tr>
                            <th>Company</th>
                            <th>Status</th>
                            <th>Date Arrived</th>
                            <th>Images</th>
                            <th>Deadline</th>
                            <th>DELAYED</th>
                            <th>Assignees</th>
                            <th>Roles</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($projects)): ?>
                            <tr>
                                <td colspan="9" class="text-center">No projects found</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($projects as $project): ?>
                                <?php
                                // Calculate days until deadline
                                $deadline = new DateTime($project['deadline']);
                                $today = new DateTime();
                                $is_overdue = $deadline < $today;

                                if ($is_overdue) {
                                    $interval = $today->diff($deadline);
                                    $days_diff = $interval->days;
                                    $deadline_status = 'Overdue by ' . $days_diff . ' days';
                                    $row_class = 'table-danger';
                                } else {
                                    $interval = $today->diff($deadline);
                                    $days_left = $interval->days;
                                    $deadline_status = $days_left . ' days left';
                                    $row_class = ($days_left <= 3) ? 'table-warning' : '';
                                }

                                // Get assignees
                                $assignees = getProjectAssignee($project['project_id']);
                                ?>
                                <tr class="<?php echo $row_class; ?>">
                                    <td>
                                        <?php echo htmlspecialchars($project['company_name'] ?? 'N/A'); ?>
                                    </td>
                                    <td>
                                        <span class="badge badge-<?php echo getStatusClass($project['status_project']); ?> p-2">
                                            <?php echo ucfirst(str_replace('_', ' ', $project['status_project'])); ?>
                                        </span>
                                    </td>
                                    <td><?php echo date('M d', strtotime($project['date_arrived'])); ?></td>
                                    <td>
                                        <?php echo $project['total_images']; ?>
                                    </td>
                                    <td>
                                        <?php echo date('M d', strtotime($project['deadline'])); ?>
                                    </td>
                                    <td>
                                        <?php if ($is_overdue): ?>
                                            <span class="badge badge-danger p-2">
                                                <?php echo $days_diff; ?> days
                                            </span>
                                        <?php else: ?>
                                            <span class="badge badge-success p-2">
                                                On Time
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if (!empty($assignees)): ?>
                                            <div class="d-flex flex-wrap">
                                                <?php foreach ($assignees as $assignee):
                                                    $initials = substr($assignee['first_name'] ?? '', 0, 1) . substr($assignee['last_name'] ?? '', 0, 1);

                                                    // Check if assignee has overdue task
                                                    $is_assignee_overdue = false;
                                                    $is_acceptable = false;

                                                    if (isset($assignee['deadline'])) {
                                                        $assignee_deadline = new DateTime($assignee['deadline']);
                                                        $is_assignee_overdue = $assignee_deadline < $today;

                                                        // Check if delay is acceptable
                                                        $is_acceptable = isset($assignee['delay_acceptable']) && $assignee['delay_acceptable'] == 1;
                                                    }

                                                    // Determine avatar class based on status
                                                    $avatar_class = '';
                                                    if ($is_assignee_overdue) {
                                                        $avatar_class = $is_acceptable ? 'assignee-acceptable' : 'assignee-overdue';
                                                    }
                                                    ?>
                                                    <div class="assignee-avatar <?php echo $avatar_class; ?>"
                                                        title="<?php echo htmlspecialchars($assignee['first_name'] . ' ' . $assignee['last_name']); ?><?php echo $is_assignee_overdue ? ($is_acceptable ? ' - Acceptable Delay' : ' - Overdue') : ''; ?>">
                                                        <?php echo strtoupper($initials); ?>
                                                    </div>
                                                <?php endforeach; ?>
                                            </div>
                                        <?php else: ?>
                                            <span class="text-muted">None</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if (!empty($assignees)): ?>
                                            <div class="d-flex flex-wrap">
                                                <?php
                                                $roles = array_map(function ($assignee) {
                                                    return isset($assignee['role_task']) ? $assignee['role_task'] : null;
                                                }, $assignees);
                                                $roles = array_filter($roles);
                                                $roles = array_unique($roles);

                                                foreach ($roles as $role):
                                                    // Convert to acronym
                                                    $acronym = '';
                                                    switch (strtolower($role)) {
                                                        case 'retouch':
                                                            $acronym = 'R';
                                                            break;
                                                        case 'clipping path':
                                                            $acronym = 'CP';
                                                            break;
                                                        case 'color correction':
                                                            $acronym = 'Cc';
                                                            break;
                                                        case 'final':
                                                            $acronym = 'F';
                                                            break;
                                                        default:
                                                            // For other roles, use first letter or first 2 letters
                                                            $words = explode(' ', $role);
                                                            if (count($words) > 1) {
                                                                $acronym = strtoupper(substr($words[0], 0, 1) . substr($words[1], 0, 1));
                                                            } else {
                                                                $acronym = strtoupper(substr($role, 0, 2));
                                                            }
                                                    }
                                                    ?>
                                                    <div class="role-badge" title="<?php echo htmlspecialchars($role); ?>">
                                                        <?php echo $acronym; ?>
                                                    </div>
                                                <?php endforeach; ?>
                                            </div>
                                        <?php else: ?>
                                            <span class="text-muted">None</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center">
                                        <a href="view-project.php?id=<?php echo $project['project_id']; ?>"
                                            class="btn btn-info btn-sm" title="View Project">
                                            <i class="fas fa-eye"></i>
                                        </a>
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

<?php
include("includes/footer.php");
?>

<script>
    $(document).ready(function () {
        // Initialize DataTable with responsive settings
        var dataTable = $('#projectTable').DataTable({
            "pageLength": 10000,
            "lengthMenu": [[1000, 10000, 100000, 1000000], [1000, 10000, 100000, 1000000]],
            "orderCellsTop": true,
            "order": [], // Remove default ordering
            "language": {
                "emptyTable": "No projects found",
                "zeroRecords": "No matching projects found",
                "lengthMenu": "Show _MENU_ entries",
                "info": "Showing _START_ to _END_ of _TOTAL_ entries",
                "infoEmpty": "Showing 0 to 0 of 0 entries",
                "infoFiltered": "(filtered from _MAX_ total entries)"
            },
            "responsive": true,
            "scrollX": false, // Disable horizontal scrolling
            "scrollY": false, // Disable vertical scrolling
            "autoWidth": true,
            "scrollCollapse": false,
            "drawCallback": function () {
                setupEnhancedTooltips();
            },
            "initComplete": function () {
                this.api().columns.adjust();
                setupEnhancedTooltips();
            }
        });

        // Function to set up enhanced tooltips
        function setupEnhancedTooltips() {
            console.log("Setting up tooltips");
            // Remove any existing tooltips first
            $('.tooltip').remove();

            // Dispose existing tooltips
            $('[title]').tooltip('dispose');
            $('.role-badge').tooltip('dispose');
            $('.assignee-avatar').tooltip('dispose');

            // Setup tooltips with better configuration
            $('[title]').tooltip({
                placement: 'top',
                container: 'body',
                trigger: 'hover',
                html: true,
                template: '<div class="tooltip" role="tooltip"><div class="arrow"></div><div class="tooltip-inner"></div></div>'
            });

            // Enhanced role badge tooltips with forced display
            $('.role-badge').tooltip({
                title: function () {
                    return $(this).attr('title') || "Role";
                },
                placement: 'top',
                container: 'body',
                trigger: 'hover',
                html: true,
                delay: { show: 0, hide: 0 }
            }).on('click', function () {
                // Force show tooltip on click in fullscreen mode
                if (document.fullscreenElement) {
                    $(this).tooltip('show');
                    setTimeout(() => $(this).tooltip('hide'), 2000);
                }
            });

            // Enhanced assignee tooltips with forced display
            $('.assignee-avatar').tooltip({
                title: function () {
                    return $(this).attr('title') || "Team Member";
                },
                placement: 'top',
                container: 'body',
                trigger: 'hover',
                html: true,
                delay: { show: 0, hide: 0 }
            }).on('click', function () {
                // Force show tooltip on click in fullscreen mode
                if (document.fullscreenElement) {
                    $(this).tooltip('show');
                    setTimeout(() => $(this).tooltip('hide'), 2000);
                }
            });
        }

        // Fullscreen toggle functionality
        $('#fullscreenToggle').on('click', function () {
            const projectTableCard = document.getElementById('projectTableCard');

            if (!document.fullscreenElement) {
                // Enter fullscreen
                if (projectTableCard.requestFullscreen) {
                    projectTableCard.requestFullscreen();
                } else if (projectTableCard.webkitRequestFullscreen) {
                    projectTableCard.webkitRequestFullscreen();
                } else if (projectTableCard.msRequestFullscreen) {
                    projectTableCard.msRequestFullscreen();
                }

                $('.card-header').hide();
                $('#projectTableCard').css({
                    'background-color': 'rgba(30, 30, 30, 0.9)'
                });

                $('.card-body').css({
                    'padding': '0',
                    'height': '100vh',
                    'overflow': 'hidden'
                });

                $('.table-responsive').css({
                    'height': '100vh',
                    'max-height': '100vh',
                    'overflow': 'auto',
                    'padding': '20px'
                });

                $(this).css({
                    'position': 'fixed',
                    'top': '10px',
                    'right': '10px',
                    'z-index': '10000'
                });

                // Re-initialize tooltips after entering fullscreen
                setTimeout(function () {
                    setupEnhancedTooltips();

                    // Add click event to show tooltip in fullscreen 
                    $('.role-badge, .assignee-avatar').off('mouseenter mouseleave').on('click', function () {
                        $(this).tooltip('show');
                        setTimeout(() => $(this).tooltip('hide'), 2000);
                    });
                }, 500);
            } else {
                // Exit fullscreen
                if (document.exitFullscreen) {
                    document.exitFullscreen();
                } else if (document.webkitExitFullscreen) {
                    document.webkitExitFullscreen();
                } else if (document.msExitFullscreen) {
                    document.msExitFullscreen();
                }

                $('.card-header').show();
                $('#projectTableCard').removeAttr('style');
                $('.card-body').removeAttr('style');
                $('.table-responsive').removeAttr('style');
                $(this).removeAttr('style');
            }

            // Refresh DataTable when entering/exiting fullscreen
            setTimeout(function () {
                dataTable.columns.adjust().draw();
                setupEnhancedTooltips();
            }, 500);
        });

        // Handle fullscreen change events
        document.addEventListener('fullscreenchange', handleFullscreenChange);
        document.addEventListener('webkitfullscreenchange', handleFullscreenChange);
        document.addEventListener('mozfullscreenchange', handleFullscreenChange);
        document.addEventListener('MSFullscreenChange', handleFullscreenChange);

        function handleFullscreenChange() {
            if (!document.fullscreenElement &&
                !document.webkitFullscreenElement &&
                !document.mozFullScreenElement &&
                !document.msFullscreenElement) {
                // Exited fullscreen
                $('.card-header').show();
                $('#projectTableCard').removeAttr('style');
                $('.card-body').removeAttr('style');
                $('.table-responsive').removeAttr('style');
                $('#fullscreenToggle').removeAttr('style');

                // Reset tooltip behavior when exiting fullscreen
                $('.role-badge, .assignee-avatar').off('click');

                // Refresh DataTable
                setTimeout(function () {
                    dataTable.columns.adjust().draw();
                    setupEnhancedTooltips();
                }, 500);
            } else {
                // Re-initialize tooltips after entering fullscreen
                setTimeout(function () {
                    setupEnhancedTooltips();

                    // Add click event to show tooltip in fullscreen 
                    $('.role-badge, .assignee-avatar').off('mouseenter mouseleave').on('click', function () {
                        $(this).tooltip('show');
                        setTimeout(() => $(this).tooltip('hide'), 2000);
                    });
                }, 500);
            }
        }

        // Also toggle fullscreen when pressing ESC key
        $(document).on('keydown', function (e) {
            if (e.key === "Escape" && document.fullscreenElement) {
                if (document.exitFullscreen) {
                    document.exitFullscreen();
                } else if (document.webkitExitFullscreen) {
                    document.webkitExitFullscreen();
                } else if (document.msExitFullscreen) {
                    document.msExitFullscreen();
                }
            }
        });

        // Fix for filter dropdowns
        setTimeout(function () {
            $('#company').val('<?php echo $company_filter; ?>');
            $('#status').val('<?php echo $status_filter; ?>');
            $('#overdue').val('<?php echo $overdue_filter; ?>');
        }, 100);

        // Setup enhanced tooltips initially
        setupEnhancedTooltips();
    });
</script>