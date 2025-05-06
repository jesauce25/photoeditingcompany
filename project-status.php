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

// Order by most recent first
$query .= " ORDER BY p.date_arrived DESC";

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
        font-size: 12px;
        margin-right: 5px;
        margin-bottom: 3px;
    }

    /* Assignee overdue styles */
    .assignee-overdue {
        background-color: rgba(220, 53, 69, 0.8) !important;
    }

    .assignee-acceptable {
        background-color: rgba(220, 53, 69, 0.8) !important;
        border: 2px solid #28a745 !important;
    }

    /* Role styling */
    .role-badge {
        background-color: rgba(134, 73, 55, 0.8);
        color: #f7f7f7;
        font-size: 0.7rem;
        padding: 3px 6px;
        border-radius: 3px;
        margin-right: 3px;
        margin-bottom: 3px;
        display: inline-block;
    }

    /* Fullscreen Mode */
    .fullscreen-btn {
        position: absolute;
        top: 10px;
        right: 10px;
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
    body.fullscreen-mode .navbar,
    body.fullscreen-mode .py-4>.d-sm-flex,
    body.fullscreen-mode .card:not(#projectTableCard),
    body.fullscreen-mode footer {
        display: none !important;
    }

    body.fullscreen-mode {
        padding: 0;
        overflow: hidden;
    }

    body.fullscreen-mode #projectTableCard {
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        z-index: 9999;
        margin: 0;
        border-radius: 0;
        backdrop-filter: blur(20px);
        -webkit-backdrop-filter: blur(20px);
        background-color: rgba(30, 30, 30, 0.9);
        overflow: auto;
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

    .table-danger {
        background-color: rgba(255, 0, 25, 0.25) !important;
    }

    .table-warning {
        background-color: rgba(255, 178, 46, 0.15) !important;
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
    }

    .form-control:focus {
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
</style>

<style>
    .tiny-label {
        font-size: 11px;
        font-weight: 500;
        color: #ffb22e;
        margin-bottom: 2px;
        display: block;
    }

    .filter-item {
        display: flex;
        flex-direction: column;
    }

    .compact-filters .form-control-sm {
        height: calc(1.5rem + 2px);
        padding: 0.1rem 0.3rem;
        font-size: 0.875rem;
    }

    .single-row {
        white-space: nowrap;
    }

    .btn-xs {
        font-size: 0.75rem;
        line-height: 1.2;
    }

    #projectTableCard {
        min-height: 75vh;
    }

    .table-container {
        height: 65vh;
        overflow-y: auto;
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
</style>

<style>
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

    /* Adjust the table to be more compact */
    .table td,
    .table th {
        padding: 0.4rem !important;
        vertical-align: middle;
        font-size: 0.9rem;
    }

    /* Make rows more compact */
    .table tr {
        line-height: 1.1;
    }

    /* Custom column widths to fit more content */
    #projectTable th:nth-child(1) {
        width: 12%;
    }

    /* Company */
    #projectTable th:nth-child(2) {
        width: 8%;
    }

    /* Status */
    #projectTable th:nth-child(3) {
        width: 10%;
    }

    /* Date Arrived */
    #projectTable th:nth-child(4) {
        width: 6%;
    }

    /* Images */
    #projectTable th:nth-child(5) {
        width: 15%;
    }

    /* Deadline */
    #projectTable th:nth-child(6) {
        width: 20%;
    }

    /* Assignees */
    #projectTable th:nth-child(7) {
        width: 20%;
    }

    /* Roles */
    #projectTable th:nth-child(8) {
        width: 9%;
    }

    /* Action */

    /* Stronger red for overdue with pulse effect */
    .table-danger {
        background-color: rgba(255, 0, 25, 0.4) !important;
        animation: pulse-red 2s infinite;
    }

    @keyframes pulse-red {
        0% {
            background-color: rgba(255, 0, 25, 0.4);
        }

        50% {
            background-color: rgba(255, 0, 25, 0.7);
        }

        100% {
            background-color: rgba(255, 0, 25, 0.4);
        }
    }

    /* Stronger orange for tomorrow deadline with dark text */
    .table-warning {
        background-color: rgba(255, 140, 0, 0.5) !important;
        color: #000000 !important;
    }

    .table-warning td {
        color: #000000 !important;
    }

    /* Fix any contrast issues with orange background */
    .table-warning .text-info,
    .table-warning .text-muted,
    .table-warning .text-warning {
        color: #000000 !important;
        font-weight: 600;
    }

    /* Tooltip styles for acronyms */
    .role-badge {
        cursor: help;
        position: relative;
    }

    .assignee-avatar {
        cursor: help;
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

    /* Improve scrollbars for the table container */
    .table-container {
        height: 70vh;
        overflow-y: auto;
        width: 100%;
    }

    /* Add space for the container to breathe */
    .container {
        max-width: 100% !important;
        padding: 0 15px !important;
    }

    .py-4 {
        padding-left: 0 !important;
        padding-right: 0 !important;
    }

    /* Make badges more compact */
    .badge {
        padding: 0.25em 0.5em;
        font-size: 0.8em;
    }

    /* Hover effect enhancements */
    [data-tooltip] {
        position: relative;
        cursor: help;
    }

    /* Hide the role-column images when not showing all */
    .role-badge {
        display: inline-block;
        margin-right: 3px;
        margin-bottom: 3px;
    }
</style>

<style>
    /* Make the project table container full width */
    #projectTableCard {
        width: 100vw;
        max-width: 100vw;
        position: relative;
        left: 50%;
        right: 50%;
        margin-left: -50vw;
        margin-right: -50vw;
        border-radius: 0 !important;
    }

    /* Compact table rows with reduced padding */
    .table td,
    .table th {
        padding: 0.4rem !important;
        vertical-align: middle;
        font-size: 0.9rem;
    }

    /* Make rows more compact */
    .table tr {
        line-height: 1.1;
    }

    /* Custom column widths to fit more content */
    #projectTable th:nth-child(1) {
        width: 3%;
    }

    /* ID */
    #projectTable th:nth-child(2) {
        width: 15%;
    }

    /* Company */
    #projectTable th:nth-child(3) {
        width: 8%;
    }

    /* Status */
    #projectTable th:nth-child(4) {
        width: 8%;
    }

    /* Date Arrived */
    #projectTable th:nth-child(5) {
        width: 5%;
    }

    /* Images */
    #projectTable th:nth-child(6) {
        width: 10%;
    }

    /* Deadline */
    #projectTable th:nth-child(7) {
        width: 20%;
    }

    /* Assignee */
    #projectTable th:nth-child(8) {
        width: 20%;
    }

    /* Roles */
    #projectTable th:nth-child(9) {
        width: 8%;
    }

    /* Action */

    /* Stronger red for overdue with pulse effect */
    .table-danger {
        background-color: rgba(255, 0, 25, 0.4) !important;
        animation: pulse-red 2s infinite;
    }

    @keyframes pulse-red {
        0% {
            background-color: rgba(255, 0, 25, 0.4);
        }

        50% {
            background-color: rgba(255, 0, 25, 0.7);
        }

        100% {
            background-color: rgba(255, 0, 25, 0.4);
        }
    }

    /* Stronger orange for tomorrow deadline with dark text */
    .table-warning {
        background-color: rgba(255, 140, 0, 0.6) !important;
        color: #000000 !important;
    }

    .table-warning td {
        color: #000000 !important;
    }

    /* Fix contrast issues with orange background */
    .table-warning .text-info,
    .table-warning .text-muted,
    .table-warning .text-warning {
        color: #000000 !important;
        font-weight: 600;
    }

    /* Enhanced tooltip styles for roles and assignees */
    .role-badge {
        cursor: help;
        position: relative;
        margin-right: 3px;
        margin-bottom: 3px;
        display: inline-block;
    }

    .role-badge:after {
        display: none !important;
        /* Completely remove the question mark */
    }

    .assignee-avatar {
        cursor: help;
    }

    /* Custom tooltip style */
    .tooltip .tooltip-inner {
        max-width: 250px;
        padding: 8px 12px;
        background-color: rgba(0, 0, 0, 0.85);
        font-size: 0.9rem;
        border-radius: 4px;
    }

    /* Improve DataTables container */
    .dataTables_wrapper {
        width: 100%;
        overflow-x: auto;
    }

    /* Add space for the container to breathe */
    .container-fluid {
        max-width: 100% !important;
        padding: 0 !important;
    }

    .card-body {
        padding: 1rem !important;
    }
</style>

<style>
    /* Remove the question mark from role badges */
    .role-badge:after {
        display: none !important;
        /* Completely remove the question mark */
    }

    /* Ensure tooltips have proper z-index in fullscreen mode */
    .tooltip {
        z-index: 9999 !important;
    }

    /* Improve tooltip content visibility */
    .tooltip-inner {
        font-size: 0.9rem !important;
        padding: 8px 12px !important;
        max-width: 300px !important;
    }

    /* Enhanced styling for the role badges */
    .role-badge {
        padding: 3px 6px !important;
        border-radius: 4px !important;
        cursor: help !important;
    }

    /* Enhanced styling for assignee avatars */
    .assignee-avatar {
        transition: transform 0.2s !important;
    }

    .assignee-avatar:hover {
        transform: scale(1.1) !important;
    }
</style>

<style>
    /* Add enhanced CSS for overdue and due tomorrow styling */
    .table-danger {
        background-color: rgba(255, 0, 25, 0.4) !important;
        animation: pulse-red 2s infinite !important;
    }

    /* Strong pulse effect animation */
    @keyframes pulse-red {
        0% {
            background-color: rgba(255, 0, 25, 0.4);
        }

        50% {
            background-color: rgba(255, 0, 25, 0.8);
        }

        100% {
            background-color: rgba(255, 0, 25, 0.4);
        }
    }

    /* Stronger orange for due tomorrow with better contrast */
    .table-warning {
        background-color: rgba(255, 140, 0, 0.7) !important;
    }

    /* Text color for due tomorrow rows */
    .table-warning td {
        color: #86371F !important;
        /* Dark orange for better contrast */
        font-weight: 600 !important;
    }

    /* Make sure deadlines in warning rows stand out */
    .table-warning .small .text-warning {
        color: #FF4500 !important;
        /* Bright orange-red */
        font-weight: 700 !important;
    }

    /* Make sure filters in header are compact */
    .card-header .compact-filters select {
        height: 30px !important;
        padding: 2px 5px !important;
        font-size: 0.85rem !important;
    }

    .card-header .filter-actions .btn-xs {
        height: 30px !important;
        font-size: 0.8rem !important;
        padding: 2px 8px !important;
    }

    /* Make sure table cell content is visible on various backgrounds */
    .table-danger td,
    .table-warning td {
        position: relative !important;
        z-index: 1 !important;
    }
</style>

<style>
    /* Add enhanced styling for table columns to make content bolder and clearer */
    /* Company column */
    #projectTable td:nth-child(1) {
        font-weight: 700 !important;
        font-size: 1.1rem !important;
    }

    /* Date Arrived column */
    #projectTable td:nth-child(3) {
        font-weight: 700 !important;
        font-size: 1.05rem !important;
    }

    /* Images column */
    #projectTable td:nth-child(4) {
        font-weight: 700 !important;
        font-size: 1.1rem !important;
        text-align: center !important;
    }

    /* Deadline column */
    #projectTable td:nth-child(5) {
        font-weight: 700 !important;
        font-size: 1.05rem !important;
    }

    /* Roles column */
    #projectTable td:nth-child(7) .role-badge {
        font-weight: 700 !important;
        font-size: 1rem !important;
        padding: 4px 8px !important;
        margin-right: 5px !important;
        margin-bottom: 5px !important;
    }

    /* Ensure strong background colors */
    .table-danger {
        background-color: rgba(255, 0, 25, 0.5) !important;
        animation: pulse-red 2s infinite !important;
    }

    @keyframes pulse-red {
        0% {
            background-color: rgba(255, 0, 25, 0.5) !important;
        }

        50% {
            background-color: rgba(255, 0, 25, 0.9) !important;
        }

        100% {
            background-color: rgba(255, 0, 25, 0.5) !important;
        }
    }

    .table-warning {
        background-color: rgba(255, 140, 0, 0.8) !important;
    }

    /* Ensure styles apply even with DataTables */
    .dataTable .table-danger,
    .dataTable .table-warning,
    table.dataTable tbody tr.table-danger,
    table.dataTable tbody tr.table-warning {
        background-color: inherit !important;
    }

    /* Remove margins between date and status */
    td .small {
        display: none !important;
        /* Hide the status text completely */
    }
</style>

<style>
    /* Fix the filter layout in card header */
    .card-header {
        padding: 0.5rem !important;
    }

    .card-header .filter-actions {
        display: flex !important;
        flex-direction: row !important;
        align-items: center !important;
    }

    /* Force background colors with higher priority */
    .table-danger {
        background-color: #FF2D2D !important;
        animation: none !important;
        /* First remove any animations */
    }

    .table-warning {
        background-color: #FF8C00 !important;
    }

    /* Fix filters layout */
    .compact-filters {
        display: flex !important;
        flex-direction: row !important;
        align-items: center !important;
        justify-content: center !important;
        width: 100% !important;
    }
</style>

<!-- Add this to head for guaranteed animation -->
<style id="animation-styles">
    @keyframes pulseRed {
        0% {
            background-color: #FF1E1E !important;
        }

        50% {
            background-color: #FF0000 !important;
        }

        100% {
            background-color: #FF1E1E !important;
        }
    }

    .table-danger {
        animation: pulseRed 2s infinite !important;
    }
</style>

<div class="background"></div>
<div class="floating-shapes"></div>
<div class="black-covers"></div>
<!-- Navbar -->
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
                            <option value="review" <?php echo ($status_filter == 'review') ? 'selected' : ''; ?>>In Review
                            </option>
                            <option value="completed" <?php echo ($status_filter == 'completed') ? 'selected' : ''; ?>>
                                Completed</option>
                            <option value="delayed" <?php echo ($status_filter == 'delayed') ? 'selected' : ''; ?>>Delayed
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
                        <a href="project-status.php" class="btn btn-xs btn-primary"
                            style="height: 30px; padding: 0 10px !important;">
                            <i class="fas fa-undo fa-xs"></i> Reset
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
                            <th>Assignees</th>
                            <th>Roles</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($projects)): ?>
                            <tr>
                                <td colspan="8" class="text-center">No projects found</td>
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
        // Force apply the pulse effect directly to overdue rows
        function forceAnimations() {
            // Force overdue row animations
            $('.table-danger').each(function () {
                $(this).css({
                    'background-color': 'rgba(255, 0, 25, 0.4)',
                    'animation': 'pulse-red 2s infinite'
                });
            });

            // Force due tomorrow styling
            $('.table-warning').each(function () {
                $(this).css({
                    'background-color': 'rgba(255, 140, 0, 0.7)',
                    'font-weight': '600'
                });
                $(this).find('td').css('color', '#86371F');
                $(this).find('.small .text-warning').css({
                    'color': '#FF4500',
                    'font-weight': '700'
                });
            });
        }

        // Apply immediately
        forceAnimations();

        // Apply after DataTable operations
        $('#projectTable').on('draw.dt', function () {
            forceAnimations();
            setupEnhancedTooltips();
        });

        // Also re-apply every 2 seconds to ensure persistence
        setInterval(forceAnimations, 2000);

        // Enhance tooltip functionality to work better in fullscreen mode
        function setupEnhancedTooltips() {
            // Reset any existing tooltips
            $('.tooltip').remove();

            // Setup tooltips with better configuration
            $('[title]').tooltip('dispose').tooltip({
                placement: 'top',
                container: 'body',
                trigger: 'hover',
                template: '<div class="tooltip" role="tooltip"><div class="arrow"></div><div class="tooltip-inner"></div></div>'
            });

            // Enhanced role badge tooltips
            $('.role-badge').tooltip('dispose').tooltip({
                title: function () {
                    return $(this).attr('title') || "Role";
                },
                placement: 'top',
                container: 'body',
                trigger: 'hover',
                delay: { show: 100, hide: 100 }
            });

            // Enhanced assignee tooltips
            $('.assignee-avatar').tooltip('dispose').tooltip({
                title: function () {
                    return $(this).attr('title') || "Team Member";
                },
                placement: 'top',
                container: 'body',
                trigger: 'hover',
                delay: { show: 100, hide: 100 }
            });
        }

        // Initialize DataTable with responsive settings
        $('#projectTable').DataTable({
            "pageLength": 25,
            "order": [],
            "language": {
                "emptyTable": "No projects found",
                "zeroRecords": "No matching projects found"
            },
            "responsive": true,
            "scrollX": true,
            "scrollY": "60vh",
            "scrollCollapse": true
        });

        // Setup enhanced tooltips initially
        setupEnhancedTooltips();

        // Fullscreen toggle functionality
        $('#fullscreenToggle').on('click', function () {
            $('body').toggleClass('fullscreen-mode');

            // Refresh DataTable when entering/exiting fullscreen
            setTimeout(function () {
                $('#projectTable').DataTable().columns.adjust().draw();
                // Re-initialize tooltips after fullscreen toggle
                setupEnhancedTooltips();
            }, 300);
        });

        // Also toggle fullscreen when pressing ESC key
        $(document).on('keydown', function (e) {
            if (e.key === "Escape" && $('body').hasClass('fullscreen-mode')) {
                $('body').removeClass('fullscreen-mode');

                // Refresh DataTable when exiting fullscreen
                setTimeout(function () {
                    $('#projectTable').DataTable().columns.adjust().draw();
                    // Re-initialize tooltips after exiting fullscreen
                    setupEnhancedTooltips();
                }, 300);
            }
        });
    });
</script>

<script>
    // Direct force application of styles
    (function () {
        // Apply immediately without waiting
        forceStyles();

        // Keep reapplying several times to ensure it works
        setTimeout(forceStyles, 100);
        setTimeout(forceStyles, 500);
        setTimeout(forceStyles, 1000);

        // Also reapply every second
        setInterval(forceStyles, 1000);

        function forceStyles() {
            console.log("Forcing row styles application");

            // Get all overdue rows
            document.querySelectorAll('.table-danger').forEach(function (row) {
                // Apply inline styles directly
                row.style.backgroundColor = "#FF2D2D";
                row.style.animation = "pulseRed 2s infinite";

                // Also apply to all cells inside
                row.querySelectorAll('td').forEach(function (cell) {
                    cell.style.color = "white";
                    cell.style.fontWeight = "bold";
                });
            });

            // Get all due tomorrow rows
            document.querySelectorAll('.table-warning').forEach(function (row) {
                // Apply inline styles directly
                row.style.backgroundColor = "#FF8C00";

                // Also apply to all cells inside
                row.querySelectorAll('td').forEach(function (cell) {
                    cell.style.color = "#86371F";
                    cell.style.fontWeight = "bold";
                });
            });
        }
    })();
</script>