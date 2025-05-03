<?php
// Start output buffering to prevent header issues
ob_start();

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

// First check if user has admin protection (recently unblocked)
$protectionQuery = "SELECT last_unblocked_at FROM tbl_accounts WHERE user_id = ?";
$protectionStmt = $conn->prepare($protectionQuery);
$protectionStmt->bind_param("i", $user_id);
$protectionStmt->execute();
$protectionResult = $protectionStmt->get_result();
$protectionData = $protectionResult->fetch_assoc();

$hasProtection = false;
if ($protectionData && !empty($protectionData['last_unblocked_at'])) {
  $unblockTime = new DateTime($protectionData['last_unblocked_at']);
  $now = new DateTime();

  if ($unblockTime > $now) {
    $hasProtection = true;
    $timeRemaining = $now->diff($unblockTime);
    error_log("TASK PAGE: User ID $user_id has admin protection for " .
      $timeRemaining->format('%h hours, %i minutes') .
      " until " . $unblockTime->format('Y-m-d H:i:s'));

    // If user has protection, ensure they are active and tasks are unlocked
    $updateAccountQuery = "UPDATE tbl_accounts SET status = 'Active', has_overdue_tasks = 0 WHERE user_id = ?";
    $updateAccountStmt = $conn->prepare($updateAccountQuery);
    $updateAccountStmt->bind_param("i", $user_id);
    $updateAccountStmt->execute();

    $unlockTasksQuery = "UPDATE tbl_project_assignments SET is_locked = 0 WHERE user_id = ?";
    $unlockTasksStmt = $conn->prepare($unlockTasksQuery);
    $unlockTasksStmt->bind_param("i", $user_id);
    $unlockTasksStmt->execute();
  }
}

// Get current user status before blocking check
$currentStatusQuery = "SELECT status, has_overdue_tasks FROM tbl_accounts WHERE user_id = ?";
$currentStatusStmt = $conn->prepare($currentStatusQuery);
$currentStatusStmt->bind_param("i", $user_id);
$currentStatusStmt->execute();
$beforeStatus = $currentStatusStmt->get_result()->fetch_assoc();

error_log("TASK PAGE - BEFORE: User ID $user_id status: " .
  ($beforeStatus ? "'{$beforeStatus['status']}', has_overdue_tasks: {$beforeStatus['has_overdue_tasks']}" : "unknown") .
  ", Has protection: " . ($hasProtection ? "YES" : "NO"));

// Only perform the block check if there's no protection period active
if (!$hasProtection) {
  // DIRECT APPROACH: Force check for overdue tasks and block/unblock as needed
  // This ensures all three states are perfectly synchronized - no more issues!
  $blockResult = forceBlockUserByOverdue($user_id);
  $has_overdue_tasks = $blockResult['has_overdue'];

  // Log the full result for debugging
  error_log("TASK PAGE: Block check result: " . json_encode($blockResult));

  // Check if status was actually updated
  if ($blockResult['has_overdue'] && !$blockResult['status_updated']) {
    // If we have overdue tasks but status wasn't updated, force it directly
    error_log("TASK PAGE: Overdue detected but status not updated! Forcing status update...");
    $forceQuery = "UPDATE tbl_accounts SET status = 'Blocked', has_overdue_tasks = 1 WHERE user_id = ?";
    $forceStmt = $conn->prepare($forceQuery);
    $forceStmt->bind_param("i", $user_id);
    $forceResult = $forceStmt->execute();
    error_log("TASK PAGE: Force update result: " . ($forceResult ? "SUCCESS" : "FAILED"));

    // Also make sure tasks are locked, except for in_progress, finish, and completed tasks
    error_log("TASK PAGE: Ensuring tasks are locked (except in_progress, finish, completed)...");
    $lockTasksQuery = "UPDATE tbl_project_assignments 
                      SET is_locked = 1 
                      WHERE user_id = ? 
                        AND status_assignee NOT IN ('completed', 'in_progress', 'finish')";
    $lockTasksStmt = $conn->prepare($lockTasksQuery);
    $lockTasksStmt->bind_param("i", $user_id);
    $lockTasksResult = $lockTasksStmt->execute();
    $tasksLocked = $lockTasksStmt->affected_rows;
    error_log("TASK PAGE: Force lock tasks result: " . ($lockTasksResult ? "SUCCESS ($tasksLocked tasks locked)" : "FAILED"));
  }
} else {
  // If user has protection, set has_overdue_tasks to 0 to prevent UI showing blocking message
  $has_overdue_tasks = 0;
  error_log("TASK PAGE: User has admin protection - skipping overdue checks - Protection remaining: " .
    ($protectionData && !empty($protectionData['last_unblocked_at']) ?
      (new DateTime($protectionData['last_unblocked_at']))->diff(new DateTime())->format('%h hours, %i minutes, %s seconds')
      : 'unknown'));
}

// Get status AFTER blocking check to confirm changes
$afterStatusQuery = "SELECT status, has_overdue_tasks FROM tbl_accounts WHERE user_id = ?";
$afterStatusStmt = $conn->prepare($afterStatusQuery);
$afterStatusStmt->bind_param("i", $user_id);
$afterStatusStmt->execute();
$afterStatus = $afterStatusStmt->get_result()->fetch_assoc();

error_log("TASK PAGE - AFTER: User ID $user_id status: " .
  ($afterStatus ? "'{$afterStatus['status']}', has_overdue_tasks: {$afterStatus['has_overdue_tasks']}" : "unknown") .
  " - Has protection: " . ($hasProtection ? "YES" : "NO"));

// Set overdue reason for display if needed
$overdue_reason = $has_overdue_tasks ?
  "You have overdue tasks. Please complete your current tasks before accessing others." :
  "";

// Initialize empty array for tasks
$tasks = [];

try {
  // Log query for debugging
  error_log("Task.php - Querying tasks for user ID: $user_id");

  // Fetch tasks assigned to the current user
  $query = "SELECT pa.*, p.project_title, c.company_name, p.deadline as project_deadline, 
              p.date_arrived, p.priority, p.status_project, p.total_images,
              pa.is_locked, 
              (SELECT COUNT(pi.image_id) FROM tbl_project_images pi WHERE pi.assignment_id = pa.assignment_id) as assigned_image_count
              FROM tbl_project_assignments pa
              JOIN tbl_projects p ON pa.project_id = p.project_id
              LEFT JOIN tbl_companies c ON p.company_id = c.company_id
              WHERE pa.user_id = ? AND pa.status_assignee != 'deleted' AND (pa.is_hidden = 0 OR pa.is_hidden IS NULL)
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
  $error_message = "There was an error loading your tasks. Please contact support. Error: " . $e->getMessage();
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

              <?php if (isset($_SESSION['error_message'])): ?>
                <div class="alert alert-danger">
                  <i class="fas fa-exclamation-circle mr-2"></i> <?php echo $_SESSION['error_message']; ?>
                </div>
                <?php unset($_SESSION['error_message']); ?>
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

                        // Use is_locked flag directly from the database
                        $is_task_locked = isset($task['is_locked']) && $task['is_locked'] == 1;

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
                                <i class="fas fa-building mr-1"></i>
                                <?php echo htmlspecialchars($task['company_name'] ?? 'N/A'); ?>
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
                                <span><?php echo $task['total_images'] ?? 0; ?> /
                                  <?php echo $task['assigned_image_count']; ?></span>
                              </div>
                            </div>
                          </td>
                          <td>
                            <div class="deadlines">
                              <div class="project-deadline">
                                <strong>Project:</strong>
                                <?php echo date('M d, Y', strtotime($task['project_deadline'])); ?>
                              </div>
                              <div
                                class="task-deadline <?php echo (strtotime($task['deadline']) < time()) ? 'text-danger' : ''; ?>">
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
                            <?php if ($is_task_locked): ?>
                              <button type="button" class="btn btn-secondary btn-sm task-locked" data-toggle="modal"
                                data-target="#accessBlockedModal"
                                data-reason="Please complete your earliest overdue task before accessing other tasks.">
                                <i class="fas fa-lock mr-1"></i> Locked
                              </button>
                            <?php else: ?>
                              <a href="view-task.php?id=<?php echo $task['assignment_id']; ?>" class="btn btn-info btn-sm">
                                <i class="fas fa-eye mr-1"></i> View
                              </a>
                              <?php if ($task['status_assignee'] === 'completed'): ?>
                                <button type="button" class="btn btn-secondary btn-sm hide-task-btn mt-1"
                                  data-id="<?php echo $task['assignment_id']; ?>">
                                  <i class="fas fa-eye-slash mr-1"></i> Hide
                                </button>
                              <?php endif; ?>
                            <?php endif; ?>
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

  /* First overdue task styling */
  .first-overdue-indicator {
    background-color: #fff3cd;
    color: #856404;
    padding: 2px 5px;
    border-radius: 3px;
    font-weight: bold;
    display: inline-block;
    margin-left: 5px;
  }

  /* Override DataTables styling */
  .dataTables_wrapper .dataTables_filter {
    margin-bottom: 15px;
  }
</style>

<?php include("includes/footer.php"); ?>

<script>
  // Debug jQuery loading
  console.log("jQuery version: " + (typeof jQuery !== 'undefined' ? jQuery.fn.jquery : 'not loaded'));

  $(document).ready(function () {
    console.log("jQuery document ready fired successfully");

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

    // Handle hide task button click
    $(document).on('click', '.hide-task-btn', function () {
      var taskId = $(this).data('id');
      var button = $(this);
      var row = button.closest('tr');

      if (confirm('Are you sure you want to hide this task? It will be moved to your History page.')) {
        $.ajax({
          url: 'controllers/task_controller.php',
          type: 'POST',
          data: {
            action: 'hide_task',
            assignment_id: taskId
          },
          dataType: 'json',
          success: function (response) {
            if (response.status === 'success') {
              // Remove the row from the table
              row.fadeOut(300, function () {
                $(this).remove();
              });

              // Show success message
              toastr.success('Task has been hidden and moved to History');
            } else {
              toastr.error('Error: ' + response.message);
            }
          },
          error: function (xhr, status, error) {
            console.error('AJAX Error:', error);
            toastr.error('An error occurred while hiding the task');
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

<!-- Access Blocked Modal -->
<div class="modal fade" id="accessBlockedModal" tabindex="-1" role="dialog" aria-labelledby="accessBlockedModalLabel"
  aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered" role="document">
    <div class="modal-content">
      <div class="modal-header bg-danger text-white">
        <h5 class="modal-title" id="accessBlockedModalLabel">
          <i class="fas fa-exclamation-triangle mr-2"></i>
          Access Blocked
        </h5>
        <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body">
        <div class="text-center mb-4">
          <i class="fas fa-lock fa-4x text-danger mb-3"></i>
          <h4>Your account is currently blocked</h4>
          <p class="text-muted" id="blockReasonText"></p>
        </div>
        <div class="alert alert-warning">
          <i class="fas fa-info-circle"></i> What you need to do:
          <ul class="mb-0 mt-2">
            <li>Complete your earliest overdue task as soon as possible</li>
            <li>If your delay is justifiable, contact your supervisor</li>
            <li>Once resolved, you'll be able to access new tasks</li>
          </ul>
        </div>
      </div>
      <div class="modal-footer justify-content-center">
        <button type="button" class="btn btn-secondary" data-dismiss="modal">
          <i class="fas fa-times mr-1"></i> Close
        </button>
      </div>
    </div>
  </div>
</div>

<script>
  // Handle showing the access blocked modal with the correct reason
  $(document).ready(function () {
    $('#accessBlockedModal').on('show.bs.modal', function (event) {
      var button = $(event.relatedTarget);
      var reason = button.data('reason');
      var modal = $(this);
      modal.find('#blockReasonText').text(reason);
    });
  });
</script>
</body>

</html>