<?php
include("includes/header.php");
// Include required controllers
require_once "controllers/unified_project_controller.php";


// Add server-side logging function for enhanced logging
function log_server_action($action, $data = null)
{
    $log_message = "[" . date('Y-m-d H:i:s') . "] [DELAYED-PROJECTS] " . $action;
    if ($data !== null) {
        $log_message .= ": " . json_encode($data);
    }
    error_log($log_message);
}

// Log page access
log_server_action("Delayed projects page accessed", array("user" => $_SESSION['username'] ?? 'Unknown'));

// Get all companies for filter dropdown
$companies = getCompaniesForDropdown();

// Get all delayed projects
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$filters = [];

// Handle filters passed via GET parameters
if (isset($_GET['company_id']) && !empty($_GET['company_id'])) {
    $filters['company_id'] = $_GET['company_id'];
}

if (isset($_GET['priority']) && !empty($_GET['priority'])) {
    $filters['priority'] = $_GET['priority'];
}

$projects = getDelayedProjects($search, $filters);
log_server_action("Fetched delayed projects", array(
    "count" => count($projects),
    "search" => $search,
    "filters" => $filters
));

// Count overdue assignees for logging
$overdue_assignee_count = 0;
$overdue_project_count = 0;
foreach ($projects as $project) {
    $project_deadline = new DateTime($project['deadline']);
    $today = new DateTime();
    if ($project_deadline < $today) {
        $overdue_project_count++;
    }

    $assignees = getProjectAssignee($project['project_id']);
    foreach ($assignees as $assignee) {
        if (isset($assignee['deadline'])) {
            $assignee_deadline = new DateTime($assignee['deadline']);
            if ($assignee_deadline < $today) {
                $overdue_assignee_count++;
                break; // Count projects with at least one overdue assignee
            }
        }
    }
}

log_server_action("Overdue statistics", array(
    "overdue_projects" => $overdue_project_count,
    "projects_with_overdue_assignees" => $overdue_assignee_count
));

// Check for success message
$success_message = isset($_SESSION['success_message']) ? $_SESSION['success_message'] : '';
unset($_SESSION['success_message']);
?>

<div class="wrapper">
    <?php include("includes/nav.php"); ?>

    <!-- Content Wrapper. Contains page content -->
    <div class="content-wrapper">
        <!-- Content Header (Page header) -->
        <section class="content-header">
            <div class="container-fluid">
                <div class="row mb-2">
                    <div class="col-sm-6">
                        <h1><i class="fas fa-exclamation-triangle mr-2 text-danger"></i>Delayed Projects</h1>
                    </div>
                    <div class="col-sm-6">
                        <ol class="breadcrumb float-sm-right">
                            <li class="breadcrumb-item"><a href="#">Home</a></li>
                            <li class="breadcrumb-item">Projects</li>
                            <li class="breadcrumb-item active">Delayed Projects</li>
                        </ol>
                    </div>
                </div>
            </div>
        </section>

        <!-- Display success message if any -->
        <?php if (!empty($success_message)): ?>
            <div class="alert alert-success alert-dismissible fade show mx-3" role="alert">
                <strong>Success!</strong> <?php echo htmlspecialchars($success_message); ?>
                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
        <?php endif; ?>

        <!-- Main content -->
        <section class="content">
            <div class="container-fluid theme-red ">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="fas fa-list mr-2"></i>Delayed Project Records
                        </h3>
                        <div class="card-tools">
                            <a href="add-project.php" class="btn btn-danger btn-sm">
                                <i class="fas fa-plus mr-1"></i> Add New Project
                            </a>
                        </div>
                        <div class="d-flex justify-content-center align-items-center ">
                            <div id="companyFilterContainer" class="filter-option">
                                <select id="companySelect" class="form-control form-control-sm mr-2"
                                    style="width: 150px; height: 100%;">
                                    <option value="">All Companies</option>
                                    <?php foreach ($companies as $company): ?>
                                        <option value="<?php echo $company['company_id']; ?>" <?php echo (isset($_GET['company_id']) && $_GET['company_id'] == $company['company_id']) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($company['company_name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <select id="prioritySelect" class="form-control form-control-sm mr-2"
                                style="width: 150px; height: 100%;">
                                <option value="">All Priorities</option>
                                <option value="low" <?php echo (isset($_GET['priority']) && $_GET['priority'] == 'low') ? 'selected' : ''; ?>>Low</option>
                                <option value="medium" <?php echo (isset($_GET['priority']) && $_GET['priority'] == 'medium') ? 'selected' : ''; ?>>Medium</option>
                                <option value="high" <?php echo (isset($_GET['priority']) && $_GET['priority'] == 'high') ? 'selected' : ''; ?>>High</option>
                                <option value="urgent" <?php echo (isset($_GET['priority']) && $_GET['priority'] == 'urgent') ? 'selected' : ''; ?>>Urgent</option>
                            </select>
                            <button id="applyFilter" class="btn btn-info btn-sm">
                                <i class="fas fa-filter mr-1"></i> Filter
                            </button>
                            <button id="resetFilter" class="btn btn-secondary btn-sm ml-1">
                                <i class="fas fa-undo mr-1"></i> Reset
                            </button>
                        </div>
                    </div>
                    <div class="card-body">
                        <!-- Table controls -->
                        <div class="row mb-4">
                            <!-- Left Group: Export buttons -->
                            <!-- <div class="col-md-4">
                                <div class="btn-group">
                                    <button type="button" class="btn btn-success btn-sm export-excel"
                                        title="Export to Excel">
                                        <i class="fas fa-file-excel mr-1"></i> Excel
                                    </button>
                                    <button type="button" class="btn btn-danger btn-sm export-pdf"
                                        title="Export to PDF">
                                        <i class="fas fa-file-pdf mr-1"></i> PDF
                                    </button>
                                    <button type="button" class="btn btn-info btn-sm export-print" title="Print">
                                        <i class="fas fa-print mr-1"></i> Print
                                    </button>
                                </div>
                            </div> -->

                            <!-- Right Group: Search box -->
                            <!-- <div class="col-md-4">
                                <div class="search-box float-right" style="width: 250px;">
                                    <input type="text" id="searchInput" class="form-control form-control-sm"
                                        placeholder="Search projects..."
                                        value="<?php echo htmlspecialchars($search); ?>">
                                    <button type="button" class="btn" id="searchButton">
                                        <i class="fas fa-search"></i>
                                    </button>
                                </div>
                            </div> -->
                        </div>

                        <!-- Table with loading overlay -->
                        <div class="position-relative">
                            <div class="loading-overlay">
                                <div class="loading-spinner"></div>
                            </div>
                            <div class="table-responsive">
                                <table id="delayedProjectTable" class="table table-bordered table-hover">
                                    <thead>
                                        <tr>
                                            <th width="0%" class="d-none">#</th>
                                            <th width="10%">Project Details</th>
                                            <th width="8%">Status</th>
                                            <th width="7%">Date Arrived</th>
                                            <th width="7%">Total Images</th>
                                            <th width="8%">Deadline</th>
                                            <th width="30%">Assignee</th>
                                            <th width="8%" class="text-center">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (empty($projects)): ?>
                                            <tr>
                                                <td colspan="8" class="text-center">No delayed projects found</td>
                                            </tr>
                                        <?php else: ?>
                                            <?php foreach ($projects as $project): ?>
                                                <?php
                                                // Check if project deadline is passed
                                                $project_deadline = new DateTime($project['deadline']);
                                                $today = new DateTime();
                                                $is_project_overdue = $project_deadline < $today;

                                                // Check if project is due tomorrow
                                                $tomorrow = new DateTime('tomorrow');
                                                $is_due_tomorrow = $project_deadline->format('Y-m-d') === $tomorrow->format('Y-m-d');

                                                // Add project-overdue class if deadline is passed
                                                // Add project-tomorrow class if deadline is tomorrow
                                                $row_class = '';
                                                if ($is_project_overdue) {
                                                    $row_class = 'project-overdue';
                                                } elseif ($is_due_tomorrow) {
                                                    $row_class = 'project-tomorrow';
                                                }
                                                ?>
                                                <tr class="<?php echo $row_class; ?>">
                                                    <td class="d-none"><?php echo $project['project_id']; ?></td>
                                                    <td>
                                                        <div class="project-title">
                                                            <?php echo htmlspecialchars($project['project_title']); ?>
                                                        </div>
                                                        <div class="project-company">
                                                            <i class="fas fa-building mr-1"></i>
                                                            <?php echo htmlspecialchars($project['company_name'] ?? ''); ?>
                                                        </div>
                                                    </td>
                                                    <td>
                                                        <?php
                                                        $statusClass = 'status-' . $project['status_project'];
                                                        $statusText = ucfirst(str_replace('_', ' ', $project['status_project']));
                                                        $priorityClass = 'priority-' . $project['priority'];
                                                        $priorityText = ucfirst($project['priority']);
                                                        ?>
                                                        <span class="project-status <?php echo $statusClass; ?>">
                                                            <?php if ($project['status_project'] == 'in_progress'): ?>
                                                                <i class="fas fa-spinner fa-spin mr-1"></i>
                                                            <?php elseif ($project['status_project'] == 'pending'): ?>
                                                                <i class="fas fa-clock mr-1"></i>
                                                            <?php elseif ($project['status_project'] == 'review'): ?>
                                                                <i class="fas fa-search mr-1"></i>
                                                            <?php elseif ($project['status_project'] == 'completed'): ?>
                                                                <i class="fas fa-check-circle mr-1"></i>
                                                            <?php elseif ($project['status_project'] == 'delayed'): ?>
                                                                <i class="fas fa-exclamation-triangle mr-1"></i>
                                                            <?php endif; ?>
                                                            <?php echo $statusText; ?>
                                                        </span>
                                                        <div class="mt-1">
                                                            <span class="priority-badge <?php echo $priorityClass; ?>"
                                                                title="<?php echo $priorityText; ?> Priority"></span>
                                                            <small><?php echo $priorityText; ?> Priority</small>
                                                        </div>
                                                    </td>
                                                    <td>
                                                        <div class="deadline-text">
                                                            <i class="far fa-calendar-alt mr-1"></i>
                                                            <?php echo date('Y-m-d', strtotime($project['date_arrived'])); ?>
                                                        </div>
                                                        <small class="text-muted">
                                                            <i class="fas fa-history mr-1"></i>
                                                            <?php
                                                            $date_arrived = new DateTime($project['date_arrived']);
                                                            $now = new DateTime();
                                                            $interval = $date_arrived->diff($now);
                                                            if ($interval->days == 0) {
                                                                echo 'Today';
                                                            } elseif ($interval->days == 1) {
                                                                echo 'Yesterday';
                                                            } else {
                                                                echo $interval->days . ' days ago';
                                                            }
                                                            ?>
                                                        </small>
                                                    </td>
                                                    <td>
                                                        <div class="total-images-display">
                                                            <div class="total-count">
                                                                <i class="fas fa-images"></i>
                                                                <span><?php echo count(getProjectImages($project['project_id'])); ?></span>
                                                            </div>
                                                        </div>
                                                    </td>
                                                    <td>
                                                        <div class="deadline-text">
                                                            <i class="far fa-clock mr-1"></i>
                                                            <?php echo date('Y-m-d', strtotime($project['deadline'])); ?>
                                                        </div>
                                                        <?php
                                                        $deadline = new DateTime($project['deadline']);
                                                        $deadline->setTime(23, 59, 59);  // Set to end of day
                                                
                                                        $now = new DateTime();

                                                        // Calculate days difference
                                                        $interval = $now->diff($deadline);
                                                        $days_left = $interval->days;
                                                        $is_negative = $interval->invert;

                                                        if ($is_negative) {
                                                            echo '<small class="deadline-warning text-danger">';
                                                            echo '<i class="fas fa-exclamation-triangle mr-1"></i> Overdue by ' . $days_left . ' days';
                                                            echo '</small>';
                                                        } elseif ($days_left == 0) {
                                                            echo '<small class="deadline-warning text-warning">';
                                                            echo '<i class="fas fa-exclamation-circle mr-1"></i> Due today';
                                                            echo '</small>';
                                                        } elseif ($days_left == 1) {
                                                            echo '<small class="deadline-warning text-warning">';
                                                            echo '<i class="fas fa-exclamation-circle mr-1"></i> Due tomorrow';
                                                            echo '</small>';
                                                        } elseif ($days_left <= 3) {
                                                            echo '<small class="deadline-warning text-warning">';
                                                            echo '<i class="fas fa-exclamation-triangle mr-1"></i> ' . $days_left . ' days left';
                                                            echo '</small>';
                                                        } else {
                                                            echo '<small class="deadline-info text-info">';
                                                            echo '<i class="fas fa-info-circle mr-1"></i> ' . $days_left . ' days left';
                                                            echo '</small>';
                                                        }
                                                        ?>
                                                    </td>
                                                    <td>
                                                        <?php
                                                        $assignees = getProjectAssignee($project['project_id']);
                                                        if (!empty($assignees)) {
                                                            $totalAssignees = count($assignees);

                                                            echo '<div class="assignee-container">';

                                                            foreach ($assignees as $assignee) {
                                                                $initials = substr($assignee['first_name'], 0, 1) . substr($assignee['last_name'], 0, 1);

                                                                // Check if this assignee has an overdue task
                                                                $is_assignee_overdue = false;
                                                                $is_acceptable = false;

                                                                if (isset($assignee['deadline'])) {
                                                                    $assignee_deadline = new DateTime($assignee['deadline']);
                                                                    $is_assignee_overdue = $assignee_deadline < $today;

                                                                    // Check if the delay is marked as acceptable
                                                                    $is_acceptable = isset($assignee['delay_acceptable']) && $assignee['delay_acceptable'] == 1;

                                                                    // Add server-side logging for overdue assignee
                                                                    if ($is_assignee_overdue) {
                                                                        error_log("Overdue assignee detected: {$assignee['first_name']} {$assignee['last_name']} for project {$project['project_title']} (ID: {$project['project_id']})");
                                                                    }
                                                                }

                                                                // Add overdue class if needed
                                                                $assignee_class = $is_assignee_overdue ? 'assignee-overdue' : '';

                                                                // Avatar initial styling based on status
                                                                $avatar_class = '';
                                                                if ($is_assignee_overdue && $is_acceptable) {
                                                                    $avatar_class = 'acceptable-delay';
                                                                }

                                                                echo '<div class="assignee-item ' . $assignee_class . '">';
                                                                echo '<div class="assignee-avatar ' . $avatar_class . '">' . strtoupper($initials) . '</div>';
                                                                echo '<span>' . htmlspecialchars($assignee['first_name']) . '</span>';
                                                                echo '</div>';
                                                            }

                                                            echo '</div>';
                                                        } else {
                                                            echo '<span class="text-muted">Not assigned</span>';
                                                        }
                                                        ?>
                                                    </td>
                                                    <td>
                                                        <div class="action-buttons text-center">
                                                            <a href="view-project.php?id=<?php echo $project['project_id']; ?>"
                                                                class="btn btn-info btn-sm" title="View Project">
                                                                <i class="fas fa-eye"></i>
                                                            </a>
                                                            <a href="edit-project.php?id=<?php echo $project['project_id']; ?>"
                                                                class="btn btn-primary btn-sm" title="Edit Project">
                                                                <i class="fas fa-edit"></i>
                                                            </a>
                                                            <form action="process-delete-project.php" method="post"
                                                                style="display: inline;">
                                                                <input type="hidden" name="project_id"
                                                                    value="<?php echo $project['project_id']; ?>">
                                                                <button type="submit" class="btn btn-danger btn-sm"
                                                                    title="Delete Project"
                                                                    onclick="return confirm('Are you sure you want to delete this project?');">
                                                                    <i class="fas fa-trash"></i>
                                                                </button>
                                                            </form>
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
                </div>
            </div>
        </section>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div class="modal fade" id="deleteModal" tabindex="-1" role="dialog" aria-labelledby="deleteModalLabel"
    aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title" id="deleteModalLabel">
                    <i class="fas fa-exclamation-triangle"></i> Delete Confirmation
                </h5>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="text-center mb-3">
                    <i class="fas fa-trash-alt fa-4x text-danger mb-3"></i>
                    <h4>Are you absolutely sure?</h4>
                    <p class="text-muted">You are about to delete project <strong id="projectNameToDelete"></strong></p>
                    <p class="text-danger"><small>This action cannot be undone!</small></p>
                </div>
                <div class="alert alert-warning">
                    <i class="fas fa-info-circle"></i> This will permanently delete:
                    <ul class="mb-0 mt-2">
                        <li>Project information and settings</li>
                        <li>All associated files and images</li>
                        <li>Team assignments and progress data</li>
                    </ul>
                </div>
            </div>
            <div class="modal-footer justify-content-center">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">
                    <i class="fas fa-times"></i> Cancel
                </button>
                <form action="process-delete-project.php" method="post" id="deleteProjectForm">
                    <input type="hidden" name="project_id" id="projectIdToDelete">
                    <button type="submit" class="btn btn-danger" id="confirmDelete">
                        <i class="fas fa-trash"></i> Yes, Delete Project
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener("DOMContentLoaded", function () {

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
                            page: 'delayed-project-list'
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

        // Log page load and scan for overdue items
        logging.info('Delayed Project List page loaded');

        // Log overdue projects and assignees on page loads
        $('.project-overdue').each(function () {
            const projectId = $(this).find('td:first').text();
            const projectTitle = $(this).find('.project-title').text().trim();
            logging.warning('Overdue project detected', { projectId, projectTitle });
        });

        $('.assignee-overdue').each(function () {
            const assigneeName = $(this).text().trim();
            const projectTitle = $(this).closest('tr').find('.project-title').text().trim();
            logging.warning('Overdue assignee detected', { assignee: assigneeName, project: projectTitle });
        });

        // Initialize DataTable with enhanced features
        var table = $('#delayedProjectTable').DataTable({
            "responsive": true,
            "lengthChange": true,
            "autoWidth": false,
            "pageLength": 1000,
            "searching": true,
            "ordering": true,
            "info": true,
            "dom": '<"row align-items-center"<"col-sm-6"l><"col-sm-6 d-flex justify-content-end"f>><"row"<"col-sm-12"tr>><"row"<"col-sm-5"i><"col-sm-7 d-flex justify-content-end"p>>',
            "buttons": [{
                extend: 'excel',
                className: 'hidden-button'
            },
            {
                extend: 'pdf',
                className: 'hidden-button'
            },
            {
                extend: 'print',
                className: 'hidden-button'
            }
            ],
            "language": {
                "lengthMenu": "Show _MENU_ entries",
                "search": "",
                "searchPlaceholder": "Search projects...",
                "info": "Showing _START_ to _END_ of _TOTAL_ delayed projects",
                "infoEmpty": "No delayed projects found",
                "infoFiltered": "(filtered from _MAX_ total delayed projects)",
                "zeroRecords": "No matching delayed projects found"
            },
            "initComplete": function () {
                $('#searchInput').on('keyup', function () {
                    table.search(this.value).draw();
                });
            }
        });

        // Custom export buttons
        $('.export-excel').on('click', function () {
            logging.interaction('Export to Excel clicked');
            $('.loading-overlay').fadeIn();
            setTimeout(function () {
                table.button('.buttons-excel').trigger();
                $('.loading-overlay').fadeOut();
                logging.info('Excel export completed');
            }, 500);
        });

        $('.export-pdf').on('click', function () {
            logging.interaction('Export to PDF clicked');
            $('.loading-overlay').fadeIn();
            setTimeout(function () {
                table.button('.buttons-pdf').trigger();
                $('.loading-overlay').fadeOut();
                logging.info('PDF export completed');
            }, 500);
        });

        $('.export-print').on('click', function () {
            logging.interaction('Print clicked');
            $('.loading-overlay').fadeIn();
            setTimeout(function () {
                table.button('.buttons-print').trigger();
                $('.loading-overlay').fadeOut();
                logging.info('Print completed');
            }, 500);
        });

        // Filter functionality
        $('#applyFilter').click(function () {
            logging.interaction('Apply filter button clicked');
            applyFilters();
        });

        $('#resetFilter').click(function () {
            logging.interaction('Reset filter button clicked');
            // Clear all filter selects
            $('#companySelect').val('');
            $('#prioritySelect').val('');
            $('#searchInput').val('');
            // Reload page without filters
            window.location.href = 'delayed-project-list.php';
        });

        $('#searchButton').click(function () {
            logging.interaction('Search button clicked');
            applyFilters();
        });

        $('#searchInput').keypress(function (e) {
            if (e.which == 13) {
                logging.interaction('Search input: Enter key pressed');
                applyFilters();
            }
        });

        // Enhanced filter functionality copied from project-list.php
        function applyFilters() {
            console.log('=== Starting Filter Application ===');
            $('.loading-overlay').fadeIn();

            var company = $('#companySelect').val();
            var priority = $('#prioritySelect').val();
            var search = $('#searchInput').val();

            console.log('Current Filter Values:', {
                'Company ID': company,
                'Priority': priority,
                'Search Term': search
            });

            // Build the URL with all selected filters
            var url = 'delayed-project-list.php?';
            var params = [];

            if (company) {
                params.push('company_id=' + encodeURIComponent(company));
                console.log('Adding company filter:', company);
            }
            if (priority) {
                params.push('priority=' + encodeURIComponent(priority));
                console.log('Adding priority filter:', priority);
            }
            if (search) {
                params.push('search=' + encodeURIComponent(search));
                console.log('Adding search filter:', search);
            }

            if (params.length > 0) {
                url += params.join('&');
                console.log('Final URL to navigate:', url);
                // Use a timeout to ensure UI remains responsive
                setTimeout(function () {
                    window.location.href = url;
                }, 100);
            } else {
                console.log('No filters applied, reloading base page');
                // Use a timeout to ensure UI remains responsive
                setTimeout(function () {
                    window.location.href = 'delayed-project-list.php';
                }, 100);
            }
        }

        // Monitor select changes for debugging
        $('#companySelect, #prioritySelect').on('change', function () {
            console.log('Select changed:', {
                'Element': $(this).attr('id'),
                'New Value': $(this).val()
            });
        });

        // Delete functionality
        $(document).on('click', '.delete-btn', function () {
            var projectId = $(this).data('id');
            var projectName = $(this).data('name');
            $('#projectNameToDelete').text(projectName);
            $('#projectIdToDelete').val(projectId);
            $('#deleteModal').modal('show');
        });
    });
</script>

<style>
    /* Add additional styling on top of AdminLTE */
    .project-details-badge {
        display: inline-flex;
        align-items: center;
        padding: 5px 10px;
        background-color: #f8f9fa;
        border-radius: 5px;
        margin-right: 5px;
        margin-bottom: 5px;
        font-size: 0.85rem;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
    }

    .project-details-badge i {
        margin-right: 5px;
    }

    .priority-badge {
        display: inline-block;
        width: 10px;
        height: 10px;
        border-radius: 50%;
        margin-right: 5px;
    }

    .priority-low {
        background-color: #28a745;
    }

    .priority-medium {
        background-color: #17a2b8;
    }

    .priority-high {
        background-color: #ffc107;
    }

    .priority-urgent {
        background-color: #dc3545;
    }

    .project-status {
        display: inline-block;
        padding: 5px 10px;
        border-radius: 4px;
        font-size: 12px;
        font-weight: 600;
    }

    .status-pending {
        background-color: #ffeeba;
        color: #856404;
    }

    .status-in-progress {
        background-color: #bee5eb;
        color: #0c5460;
    }

    .status-review {
        background-color: #b8daff;
        color: #004085;
    }

    .status-completed {
        background-color: #c3e6cb;
        color: #155724;
    }

    .status-delayed {
        background-color: #f5c6cb;
        color: #721c24;
    }

    .total-images-display {
        display: flex;
        align-items: center;
        justify-content: center;
        background: linear-gradient(135deg, #007bff, #00bcd4);
        padding: 6px 10px;
        border-radius: 6px;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        font-size: 0.9rem;
    }

    .total-count {
        display: flex;
        align-items: center;
        gap: 6px;
        color: white;
        font-weight: 600;
    }

    .total-count i {
        font-size: 1rem;
    }

    .total-count span {
        font-size: 1rem;
    }

    /* Assignee styling - horizontal layout */
    .assignee-container {
        display: flex;
        flex-wrap: wrap;
        gap: 6px;
        align-items: center;
    }

    .assignee-item {
        display: flex;
        align-items: center;
        background-color: #f8f9fa;
        border-radius: 20px;
        padding: 4px 10px;
        margin-bottom: 3px;
        border: 1px solid #e9ecef;
        transition: all 0.2s;
    }

    .assignee-item:hover {
        background-color: #e9ecef;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
    }

    .assignee-avatar {
        width: 24px;
        height: 24px;
        background-color: #6c757d;
        color: white;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 0.7rem;
        margin-right: 6px;
    }

    /* Style for acceptable delay - green avatar initials */
    .assignee-avatar.acceptable-delay {
        background-color: #28a745;
        color: white;
        font-weight: bold;
    }

    .assignee-more {
        background-color: #e9ecef;
        border-radius: 20px;
        padding: 4px 10px;
        color: #6c757d;
        font-size: 0.8rem;
        display: inline-flex;
        align-items: center;
    }

    .assignee-more i {
        margin-right: 4px;
    }

    /* Animation for alerts */
    .auto-fade-alert {
        animation: fadeInAlert 0.5s ease-in-out;
    }

    @keyframes fadeInAlert {
        from {
            opacity: 0;
            transform: translateY(-20px);
        }

        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    /* Loading overlay */
    .loading-overlay {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background-color: rgba(255, 255, 255, 0.7);
        display: none;
        justify-content: center;
        align-items: center;
        z-index: 1000;
        border-radius: 0.25rem;
    }

    .loading-spinner {
        width: 40px;
        height: 40px;
        border: 4px solid #f3f3f3;
        border-top: 4px solid #3498db;
        border-radius: 50%;
        animation: spin 1s linear infinite;
    }

    @keyframes spin {
        0% {
            transform: rotate(0deg);
        }

        100% {
            transform: rotate(360deg);
        }
    }

    /* Custom styles for overdue indicators */
    .project-overdue {
        background-color: rgba(255, 0, 0, 0.1) !important;
    }

    /* Projects due tomorrow */
    .project-tomorrow {
        background-color: #fff3cd !important;
        /* Light orange/yellow background */
    }

    /* Overdue assignee (strong red background) */
    .assignee-overdue {
        background-color: rgb(255, 37, 37) !important;
        color: white !important;
        border-radius: 20px;
        border: 1px solid #f5c6cb;
    }

    /* When printing, ensure colors are visible */
    @media print {
        .project-overdue {
            background-color: rgba(255, 37, 37, 0.59) !important;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }

        .project-tomorrow {
            background-color: #fff3cd !important;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }

        .assignee-overdue {
            background-color: #ff0000 !important;
            color: white !important;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }
    }
</style>

<?php include("includes/footer.php"); ?>