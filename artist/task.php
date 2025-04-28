<?php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
  session_start();
}

// Log session details for debugging
error_log("Task.php - Session status: " . (isset($_SESSION['user_logged_in']) ? 'logged in' : 'not logged in'));
error_log("Task.php - User ID: " . ($_SESSION['user_id'] ?? 'not set'));
error_log("Task.php - Role: " . ($_SESSION['role'] ?? 'not set'));

// Include necessary files
require_once '../includes/db_connection.php';
require_once 'includes/auth_check.php';
require_once 'includes/task_block_check.php';
require_once 'includes/helper_functions.php';

// Get the current user's ID from session
$user_id = $_SESSION['user_id'] ?? 0;

// Ensure we have a valid user ID
if (!$user_id) {
  error_log("Error: No valid user ID in session, redirecting to login");
  header("Location: ../login.php");
  exit;
}

// Check for overdue tasks
$blockCheck = checkArtistOverdueTasks($user_id);
$has_overdue_tasks = $blockCheck['blocked'];
$overdue_reason = $blockCheck['reason'];
$overdue_tasks = $blockCheck['overdue_tasks'] ?? [];

// Initialize empty array for tasks
$tasks = [];

try {
  // Log query for debugging
  error_log("Task.php - Querying tasks for user ID: $user_id");

  // Fetch tasks assigned to the current user
  $query = "SELECT pa.*, p.project_title, c.company_name, p.deadline as project_deadline, 
              p.date_arrived, p.priority, p.status_project, p.total_images,
              COUNT(pi.image_id) as assigned_image_count
              FROM tbl_project_assignments pa
              JOIN tbl_projects p ON pa.project_id = p.project_id
              LEFT JOIN tbl_companies c ON p.company_id = c.company_id
              LEFT JOIN tbl_project_images pi ON pi.assignment_id = pa.assignment_id
              WHERE pa.user_id = ? AND pa.status_assignee != 'deleted'
              GROUP BY pa.assignment_id
              ORDER BY pa.deadline ASC";

  $stmt = $conn->prepare($query);

  if (!$stmt) {
    throw new Exception("Query preparation failed: " . $conn->error);
  }

  $stmt->bind_param("i", $user_id);

  if (!$stmt->execute()) {
    throw new Exception("Query execution failed: " . $stmt->error);
  }

  $result = $stmt->get_result();
  $tasks = $result->fetch_all(MYSQLI_ASSOC);

  error_log("Task.php - Found " . count($tasks) . " tasks for user ID: $user_id");

} catch (Exception $e) {
  // Log the error
  error_log("Error in artist/task.php: " . $e->getMessage());
  error_log("SQL Error: " . ($conn->error ?? 'None'));

  // Create a friendly error message for display
  $error_message = "There was an error loading your tasks. Please contact support.";
}

// Include header after processing to avoid redirect issues
include("includes/header.php");
?>

<div class="main-container">
  <?php include("includes/nav.php"); ?>

  <!-- Content Wrapper. Contains page content -->
  <div class="content-wrapper container-fluid">
    <!-- Content Header (Page header) -->
    <section class="content-header">
      <div class="container-fluid">
        <div class="row mb-2">
          <div class="col-sm-6">
            <h1><i class="fas fa-tasks mr-2"></i> My Tasks</h1>
          </div>
          <div class="col-sm-6">
            <ol class="breadcrumb float-sm-right">
              <li class="breadcrumb-item"><a href="home.php">Home</a></li>
              <li class="breadcrumb-item active">Tasks</li>
            </ol>
          </div>
        </div>
      </div>
    </section>

    <!-- Main content -->
    <section class="content mt-5">
      <div class="container-fluid">
        <div class="card">
          <div class="card-header">
            <h3 class="card-title">
              <i class="fas fa-list mr-2"></i>Assigned Tasks
            </h3>
          </div>
          <div class="card-body">
            <!-- Table controls -->
            <div class="row mb-4">
              <!-- Left Group: Export buttons -->
              <div class="col-md-4">
                <div class="btn-group">
                  <button type="button" class="btn btn-success btn-sm export-excel" title="Export to Excel">
                    <i class="fas fa-file-excel mr-1"></i> Excel
                  </button>
                  <button type="button" class="btn btn-danger btn-sm export-pdf" title="Export to PDF">
                    <i class="fas fa-file-pdf mr-1"></i> PDF
                  </button>
                  <button type="button" class="btn btn-info btn-sm export-print" title="Print">
                    <i class="fas fa-print mr-1"></i> Print
                  </button>
                </div>
              </div>
              <!-- Center Group: Filter options -->
              <div class="col-md-4">
                <div class="filter-container" style="width: 100%;">
                  <div class="d-flex justify-content-center align-items-center flex-wrap" style="width: 100%;">
                    <select id="statusFilter" class="form-control form-control-sm mr-2 mb-2" style="width: 150px;">
                      <option value="">All Status</option>
                      <option value="pending">Pending</option>
                      <option value="in_progress">In Progress</option>
                      <option value="qa">QA</option>
                      <option value="completed">Completed</option>
                      <option value="delayed">Delayed</option>
                    </select>

                    <select id="priorityFilter" class="form-control form-control-sm mr-2 mb-2" style="width: 150px;">
                      <option value="">All Priorities</option>
                      <option value="low">Low</option>
                      <option value="medium">Medium</option>
                      <option value="high">High</option>
                      <option value="urgent">Urgent</option>
                    </select>

                    <select id="deadlineFilter" class="form-control form-control-sm mr-2 mb-2" style="width: 150px;">
                      <option value="">All Deadlines</option>
                      <option value="upcoming">Upcoming</option>
                      <option value="urgent">Urgent (< 5 days)</option>
                      <option value="overdue">Overdue</option>
                    </select>

                    <button id="applyFilter" class="btn btn-info btn-sm">
                      <i class="fas fa-filter mr-1"></i> Apply Filters
                    </button>

                    <button id="resetFilter" class="btn btn-outline-secondary btn-sm ml-2">
                      <i class="fas fa-undo mr-1"></i> Reset
                    </button>
                  </div>
                </div>
              </div>

              <!-- Right Group: Search box -->
              <div class="col-md-4">
                <div class="search-box float-right" style="width: 250px;">
                  <input type="text" id="searchInput" class="form-control form-control-sm"
                    placeholder="Search tasks...">
                  <button type="button" class="btn">
                    <i class="fas fa-search"></i>
                  </button>
                </div>
              </div>
            </div>

            <!-- Table with loading overlay -->
            <div class="position-relative">
              <div class="loading-overlay">
                <div class="loading-spinner"></div>
              </div>

              <?php if (isset($error_message)): ?>
                <div class="alert alert-danger">
                  <i class="fas fa-exclamation-circle mr-2"></i> <?php echo $error_message; ?>
                </div>
              <?php endif; ?>

              <?php if ($has_overdue_tasks): ?>
              <div class="alert alert-danger">
                <div class="d-flex align-items-center">
                  <i class="fas fa-exclamation-triangle fa-2x mr-3"></i>
                  <div>
                    <h5 class="alert-heading mb-1">Your account is currently restricted</h5>
                    <p class="mb-1"><?php echo htmlspecialchars($overdue_reason); ?></p>
                    <p class="mb-0 small">You can still view and work on your existing tasks, but new tasks will be locked until you complete your overdue ones.</p>
                  </div>
                </div>
                <?php if (!empty($overdue_tasks)): ?>
                <hr>
                <p class="mb-1"><strong>Overdue tasks:</strong></p>
                <ul class="mb-0">
                  <?php foreach ($overdue_tasks as $overdue): ?>
                  <li>
                    <a href="view-task.php?id=<?php echo $overdue['assignment_id']; ?>" class="text-danger">
                      <?php echo htmlspecialchars($overdue['project_title']); ?> (<?php echo htmlspecialchars($overdue['role_task']); ?>)
                      - Due: <?php echo date('M d, Y', strtotime($overdue['deadline'])); ?>
                    </a>
                  </li>
                  <?php endforeach; ?>
                </ul>
                <?php endif; ?>
              </div>
              <?php endif; ?>

              <div class="table-responsive">
                <table id="taskTable" class="table table-bordered table-hover">
                  <thead>
                    <tr>
                      <th width="5%" class="d-none">#</th>
                      <th width="12%">Project Details</th>
                      <th width="10%">Status</th>
                      <th width="10%">Date Arrived</th>
                      <th width="15%">Total Images / Assigned</th>
                      <th width="15%">Deadline / Task Deadline</th>
                      <th width="10%">Role</th>
                      <th width="10%" class="text-center">Actions</th>
                    </tr>
                  </thead>
                  <tbody>
                    <?php if (empty($tasks)): ?>
                      <tr>
                        <td colspan="8" class="text-center">No tasks assigned to you yet</td>
                      </tr>
                    <?php else: ?>
                      <?php foreach ($tasks as $task): 
                          // Check if the task is overdue
                          $is_task_overdue = strtotime($task['deadline']) < time();
                          
                          // If artist has overdue tasks, check if this is one of them or is delay acceptable
                          $is_task_locked = false;
                          $delay_acceptable = isset($task['delay_acceptable']) && $task['delay_acceptable'] == '1';
                          
                          if ($has_overdue_tasks && !$is_task_overdue && !$delay_acceptable) {
                              $is_task_locked = true;
                          }
                          
                          // Status and priority classes
                          $statusClass = getStatusClass($task['status_assignee']);
                          $priorityClass = getPriorityClass($task['priority']);
                      ?>
                        <tr class="<?php echo $is_task_overdue ? 'table-danger' : ''; ?>">
                          <td class="d-none"><?php echo $task['assignment_id']; ?></td>
                          <td>
                            <div class="project-info">
                              <div class="project-title">
                                <?php echo htmlspecialchars($task['project_title']); ?>
                                <?php if ($is_task_locked): ?>
                                <span class="ml-1 text-danger" title="This task is locked due to overdue tasks">
                                  <i class="fas fa-lock"></i>
                                </span>
                                <?php endif; ?>
                              </div>
                              <div class="project-client">
                                <i class="fas fa-building mr-1"></i> <?php echo htmlspecialchars($task['company_name'] ?? 'N/A'); ?>
                              </div>
                            </div>
                          </td>
                          <td>
                            <span class="badge badge-<?php echo $statusClass; ?>">
                              <?php echo ucfirst(str_replace('_', ' ', $task['status_assignee'])); ?>
                            </span>
                            <div class="mt-1">
                              <span class="badge badge-<?php echo $priorityClass; ?>">
                                <?php echo ucfirst($task['priority']); ?>
                              </span>
                            </div>
                          </td>
                          <td><?php echo date('M d, Y', strtotime($task['date_arrived'])); ?></td>
                          <td>
                            <div class="task-count">
                              <div class="images-count">
                                <i class="fas fa-images mr-1"></i>
                                <span><?php echo $task['assigned_image_count'] ?? 0; ?> / <?php echo $task['assigned_images']; ?></span>
                              </div>
                            </div>
                          </td>
                          <td>
                            <div class="deadlines">
                              <div class="project-deadline">
                                <strong>Project:</strong> <?php echo date('M d, Y', strtotime($task['project_deadline'])); ?>
                              </div>
                              <div class="task-deadline <?php echo (strtotime($task['deadline']) < time()) ? 'text-danger' : ''; ?>">
                                <strong>Task:</strong> <?php echo date('M d, Y', strtotime($task['deadline'])); ?>
                                <?php if (strtotime($task['deadline']) < time()): ?>
                                  <span class="badge badge-danger ml-1">Overdue</span>
                                <?php endif; ?>
                              </div>
                            </div>
                          </td>
                          <td>
                            <span class="badge badge-info">
                              <?php echo htmlspecialchars($task['role_task']); ?>
                            </span>
                          </td>
                          <td class="text-center">
                            <a href="view-task.php?id=<?php echo $task['assignment_id']; ?>" 
                               class="btn btn-info btn-sm <?php echo $is_task_locked ? 'task-locked' : ''; ?>"
                               <?php if ($is_task_locked): ?>
                               title="This task is locked due to overdue tasks. Complete your overdue tasks first."
                               <?php endif; ?>>
                              <i class="fas <?php echo $is_task_locked ? 'fa-lock' : 'fa-eye'; ?> mr-1"></i>
                              <?php echo $is_task_locked ? 'Locked' : 'View'; ?>
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
      </div>
    </section>
  </div>
</div>

<style>
  /* Task table styling */
  .project-title {
    font-weight: bold;
    font-size: 1rem;
  }

  .company-info {
    font-size: 0.8rem;
    opacity: 0.8;
  }

  .deadline-warning {
    display: block;
    margin-top: 5px;
  }

  .task-locked {
    background-color: #6c757d;
    border-color: #6c757d;
    cursor: not-allowed;
  }

  .task-count {
    font-size: 1rem;
    font-weight: bold;
    color: #343a40;
  }
  
  /* Override DataTables styling */
  .dataTables_wrapper .dataTables_filter {
    margin-bottom: 15px;
  }
</style>

<script>
  $(document).ready(function () {
    // Initialize DataTable
    var table = $('#taskTable').DataTable({
      "responsive": true,
      "lengthChange": true,
      "autoWidth": false,
      "pageLength": 10,
      "searching": true,
      "ordering": true,
      "info": true
    });

    // Handle start task button click
    $('.start-task-btn').on('click', function () {
      var taskId = $(this).data('id');
      var button = $(this);
      if (confirm('Are you sure you want to start this task?')) {
        $.ajax({
          url: 'controllers/task_controller.php',
          type: 'POST',
          data: {
            action: 'start_task',
            assignment_id: taskId
          },
          dataType: 'json',
          success: function (response) {
            if (response.status === 'success') {
              // Update UI without reloading
              var row = button.closest('tr');
              row.find('.project-status').html('<i class="fas fa-spinner fa-spin mr-1"></i> In Progress');
              row.find('.project-status').removeClass('status-pending').addClass('status-in_progress');

              // Replace start button with mark done button
              button.replaceWith('<button type="button" class="btn btn-success btn-sm mark-done-btn" ' +
                'data-id="' + taskId + '" ' +
                'data-status="in_progress" ' +
                'title="Mark as Finished">' +
                '<i class="fas fa-check"></i>' +
                '</button>');

              // Show success message
              toastr.success('Task started successfully');
            } else {
              toastr.error('Error: ' + response.message);
            }
          },
          error: function () {
            toastr.error('Error occurred while starting the task');
          }
        });
      }
    });

    // Handle mark done button click
    $('.mark-done-btn').on('click', function () {
      var taskId = $(this).data('id');
      var button = $(this);
      if (confirm('Are you sure you want to mark this task as finished? This will send it to QA for review.')) {
        $.ajax({
          url: 'controllers/task_controller.php',
          type: 'POST',
          data: {
            action: 'complete_task',
            assignment_id: taskId
          },
          dataType: 'json',
          success: function (response) {
            if (response.status === 'success') {
              // Update UI without reloading
              var row = button.closest('tr');
              row.find('.project-status').html('<i class="fas fa-check mr-1"></i> Finished');
              row.find('.project-status').removeClass('status-in_progress').addClass('status-finish');

              // Remove the button
              button.remove();

              // Show success message
              toastr.success('Task marked as finished and sent to QA');
            } else {
              toastr.error('Error: ' + response.message);
            }
          },
          error: function () {
            toastr.error('Error occurred while completing the task');
          }
        });
      }
    });

    // Filter functionality
    $('#applyFilter').on('click', function () {
      var status = $('#statusFilter').val();
      var priority = $('#priorityFilter').val();
      var deadline = $('#deadlineFilter').val();
      var search = $('#searchInput').val();

      table.search(search).draw();

      // Custom filtering for status
      if (status) {
        table.columns(2).search(status).draw();
      }

      // Custom filtering for priority
      if (priority) {
        $.fn.dataTable.ext.search.push(function (settings, data, dataIndex) {
          var rowPriority = $(table.row(dataIndex).node()).find('.priority-badge').attr('title');
          return !priority || rowPriority.toLowerCase().includes(priority.toLowerCase());
        });
        table.draw();
        $.fn.dataTable.ext.search.pop();
      }

      // Custom filtering for deadline
      if (deadline) {
        $.fn.dataTable.ext.search.push(function (settings, data, dataIndex) {
          var daysLeft = parseInt($(table.row(dataIndex).node()).find('.deadline-info, .deadline-warning').text());

          if (deadline === 'upcoming' && daysLeft > 3) {
            return true;
          } else if (deadline === 'urgent' && daysLeft >= 0 && daysLeft <= 3) {
            return true;
          } else if (deadline === 'overdue' && daysLeft < 0) {
            return true;
          }
          return false;
        });
        table.draw();
        $.fn.dataTable.ext.search.pop();
      }
    });

    // Reset filters
    $('#resetFilter').on('click', function () {
      $('#statusFilter').val('');
      $('#priorityFilter').val('');
      $('#deadlineFilter').val('');
      $('#searchInput').val('');
      table.search('').columns().search('').draw();
    });

    // Initialize loading states
    $('.loading-overlay').hide();

    // Use toastr for notifications if available
    if (typeof toastr !== 'undefined') {
      toastr.options = {
        "closeButton": true,
        "progressBar": true,
        "positionClass": "toast-top-right",
        "showDuration": "300",
        "hideDuration": "1000",
        "timeOut": "3000"
      };
    }
  });
</script>

<?php include("includes/footer.php"); ?>