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
              (SELECT COUNT(pi.image_id) FROM tbl_project_images pi WHERE pi.assignment_id = pa.assignment_id) as assigned_image_count,
              (SELECT GROUP_CONCAT(DISTINCT pi.image_role SEPARATOR ', ') FROM tbl_project_images pi WHERE pi.assignment_id = pa.assignment_id AND pi.image_role IS NOT NULL AND pi.image_role != '') as all_roles
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

<style>
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
</style>
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
            <div class="d-flex justify-content-center align-items-center flex-wrap" style="width: 100%;">
              <select id="statusFilter" class="form-control form-control-sm mr-2 mb-2" style="width: 150px;">
                <option value="">All Status</option>
                <option value="pending">Pending</option>
                <option value="in_progress">In Progress</option>
                <option value="finish">Finish</option>
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
                <option value="due_tomorrow">Due Tomorrow</option>
                <option value="overdue">Overdue</option>
              </select>


              <button id="applyFilter" class="btn btn-info btn-sm">
                <i class="fas fa-filter mr-1"></i> Apply Filters
              </button>

              <button id="resetFilter" class="btn btn-info btn-sm ml-2">
                <i class="fas fa-undo mr-1"></i> Reset
              </button>
            </div>
          </div>
          <div class="card-body">
            <!-- Table controls -->

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

              <div class="table-responsive" style="max-height: 600px; overflow-y: scroll;">
                <table id="taskTable" class="table table-bordered table-hover" style="margin-bottom: 0;">
                  <thead>
                    <tr style="line-height: 1;">
                      <th width="5%" class="d-none">#</th>
                      <th width="12%" style="padding: 15px;">Project Details</th>
                      <th width="10%" style="padding: 15px;">Status</th>
                      <th width="10%" style="padding: 15px;">Date Arrived</th>
                      <th width="15%" style="padding: 15px;">Total Images / Assigned</th>
                      <th width="15%" style="padding: 15px;">Deadline / Task Deadline</th>
                      <th width="10%" style="padding: 15px;">Role</th>
                      <th width="10%" class="text-center" style="padding: 15px;">Actions</th>
                    </tr>
                  </thead>
                  <tbody>
                    <?php if (empty($tasks)): ?>
                      <tr>
                        <td colspan="8" class="text-center" style="padding: 4px;">No tasks assigned to you yet</td>
                      </tr>
                    <?php else: ?>
                      <?php foreach ($tasks as $task):
                        // Check if the task is overdue, due today, or due tomorrow
                        $today = date('Y-m-d');
                        $tomorrow = date('Y-m-d', strtotime('+1 day'));
                        $task_deadline = date('Y-m-d', strtotime($task['deadline']));

                        $is_task_overdue = strtotime($task_deadline) < strtotime($today);
                        $is_task_due_today = ($task_deadline == $today);
                        $is_task_due_tomorrow = ($task_deadline == $tomorrow);

                        // Use is_locked flag directly from the database
                        $is_task_locked = isset($task['is_locked']) && $task['is_locked'] == 1;

                        // Status and priority classes
                        $statusClass = getStatusClass($task['status_assignee']);
                        $priorityClass = getPriorityClass($task['priority']);

                        // Set row background color
                        $rowClass = '';
                        if ($is_task_overdue) {
                          $rowClass = 'table-danger';
                        } elseif ($is_task_due_today) {
                          $rowClass = 'table-danger'; // Red background for due today
                        } elseif ($is_task_due_tomorrow) {
                          $rowClass = 'table-warning'; // Orange background for due tomorrow
                        }
                      ?>
                        <tr class="<?php echo $rowClass; ?>" style="line-height: 1;">
                          <td class="d-none"><?php echo $task['assignment_id']; ?></td>
                          <td style="padding: 4px;">
                            <div style="margin: 0;">
                              <div style="margin: 0;"><?php echo htmlspecialchars($task['project_title']); ?>
                                <?php if ($is_task_locked): ?>
                                  <span class="text-danger" title="This task is locked due to overdue tasks">
                                    <i class="fas fa-lock"></i>
                                  </span>
                                <?php endif; ?>
                              </div>
                              <div style="margin: 0; font-size: 0.85rem;">
                                <i class="fas fa-building mr-1"></i>
                                <?php echo htmlspecialchars($task['company_name'] ?? 'N/A'); ?>
                              </div>
                            </div>
                          </td>
                          <td style="padding: 4px;">
                            <span class="badge badge-<?php echo $statusClass; ?>" style="padding: 2px 5px; font-size: 0.8rem;">
                              <?php echo ucfirst(str_replace('_', ' ', $task['status_assignee'])); ?>
                            </span>
                            <span class="badge badge-<?php echo $priorityClass; ?>" style="padding: 2px 5px; font-size: 0.8rem; display: inline-block; margin-top: 2px;">
                              <?php echo ucfirst($task['priority']); ?>
                            </span>
                          </td>
                          <td style="padding: 4px;"><?php echo date('M d, Y', strtotime($task['date_arrived'])); ?></td>
                          <td style="padding: 4px;">
                            <div style="margin: 0;">
                              <i class="fas fa-images mr-1"></i>
                              <span><?php echo $task['total_images'] ?? 0; ?> / <?php echo $task['assigned_image_count']; ?></span>
                            </div>
                          </td>
                          <td style="padding: 4px;">
                            <div style="margin: 0; line-height: 1.1;">
                              <span><strong>Project:</strong> <?php echo date('M d, Y', strtotime($task['project_deadline'])); ?></span>
                              <div class="<?php echo ($is_task_overdue) ? 'text-danger' : ($is_task_due_today ? 'text-danger' : ''); ?>" style="margin: 0;">
                                <strong>Task:</strong> <?php echo date('M d, Y', strtotime($task['deadline'])); ?>
                                <?php if ($is_task_overdue): ?>
                                  <span class="badge badge-danger" style="padding: 1px 3px; font-size: 0.75rem;">Overdue</span>
                                <?php elseif ($is_task_due_today): ?>
                                  <span class="badge badge-warning" style="padding: 1px 3px; font-size: 0.75rem;">Deadline Today</span>
                                <?php elseif ($is_task_due_tomorrow): ?>
                                  <span class="badge badge-warning" style="padding: 1px 3px; font-size: 0.75rem;">Due Tomorrow</span>
                                <?php endif; ?>
                              </div>
                            </div>
                          </td>
                          <td style="padding: 4px;">
                            <?php if (!empty($task['all_roles'])): ?>
                              <div class="d-flex flex-wrap" style="gap: 2px; margin: 0;">
                                <?php
                                $roles = explode(', ', $task['all_roles']);
                                foreach ($roles as $role):
                                  // Convert to acronym
                                  $acronym = '';
                                  // Define color for each role
                                  $bgColor = '';
                                  $textColor = 'white';

                                  switch (strtolower($role)) {
                                    case 'retouch':
                                      $acronym = 'R';
                                      $bgColor = '#28a745'; // Orange-red
                                      break;
                                    case 'clipping path':
                                      $acronym = 'Cp';
                                      $bgColor = '#007bff'; // Blue
                                      break;
                                    case 'color correction':
                                      $acronym = 'Cc';
                                      $bgColor = '#ffc107'; // Yellow
                                      $textColor = '#333333'; // Darker text for better contrast on yellow
                                      break;
                                    case 'final':
                                      $acronym = 'F';
                                      $bgColor = '#17a2b8'; // Green
                                      break;
                                    case 'retouch to final':
                                      $acronym = 'RF';
                                      $bgColor = '#6c757d'; // Purple
                                      break;
                                    default:
                                      // For other roles, use first letter or first 2 letters
                                      $words = explode(' ', $role);
                                      if (count($words) > 1) {
                                        $acronym = strtoupper(substr($words[0], 0, 1) . substr($words[1], 0, 1));
                                      } else {
                                        $acronym = strtoupper(substr($role, 0, 2));
                                      }
                                      $bgColor = '#95A5A6'; // Gray for default
                                  }
                                ?>
                                  <div title="<?php echo htmlspecialchars($role); ?>"
                                    style="display:inline-block; padding:1px 4px; background-color:<?php echo $bgColor; ?>; color:<?php echo $textColor; ?>; border-radius:3px; font-size:0.75rem;">
                                    <?php echo $acronym; ?>
                                  </div>
                                <?php endforeach; ?>
                              </div>
                            <?php else: ?>
                              <span class="badge badge-info" style="padding: 2px 5px; font-size: 0.8rem;">
                                <?php echo htmlspecialchars($task['role_task'] ?? 'Not Assigned'); ?>
                              </span>
                            <?php endif; ?>
                          </td>

                          <td class="text-center" style="padding: 4px;">
                            <?php if ($is_task_locked): ?>
                              <button type="button" class="btn btn-secondary btn-sm task-locked" data-toggle="modal"
                                data-target="#accessBlockedModal" style="padding: 2px 5px; font-size: 0.8rem;"
                                data-reason="Please complete your earliest overdue task before accessing other tasks.">
                                <i class="fas fa-lock mr-1"></i> Locked
                              </button>
                            <?php else: ?>
                              <a href="view-task.php?id=<?php echo $task['assignment_id']; ?>" class="btn btn-info btn-sm" style="padding: 2px 5px; font-size: 0.8rem;">
                                <i class="fas fa-eye mr-1"></i> View
                              </a>
                              <?php if ($task['status_assignee'] === 'completed'): ?>
                                <button type="button" class="btn btn-secondary btn-sm hide-task-btn" style="padding: 2px 5px; font-size: 0.8rem; margin-top: 2px;"
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

</style>

<?php include("includes/footer.php"); ?>

<script>
  // Debug jQuery loading
  console.log("jQuery version: " + (typeof jQuery !== 'undefined' ? jQuery.fn.jquery : 'not loaded'));

  $(document).ready(function() {
    console.log("jQuery document ready fired successfully");

    // Initialize DataTable
    var table = $('#taskTable').DataTable({
      "responsive": true,
      "lengthChange": true,
      "autoWidth": false,
      "pageLength": 1000,
      "searching": true,
      "ordering": true,
      "info": true,
      "dom": '<"row align-items-center"<"col-sm-6"l><"col-sm-6 d-flex justify-content-end"f>><"row"<"col-sm-12"tr>><"row"<"col-sm-5"i><"col-sm-7 d-flex justify-content-end"p>>'
    });



    // Handle hide task button click
    $(document).on('click', '.hide-task-btn', function() {
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
          success: function(response) {
            if (response.status === 'success') {
              // Remove the row from the table
              row.fadeOut(300, function() {
                $(this).remove();
              });

              // Show success message
              toastr.success('Task has been hidden and moved to History');
            } else {
              toastr.error('Error: ' + response.message);
            }
          },
          error: function(xhr, status, error) {
            console.error('AJAX Error:', error);
            toastr.error('An error occurred while hiding the task');
          }
        });
      }
    });

    // Filter functionality
    $('#applyFilter').on('click', function() {
      applyFilters();
    });

    // Reset filters
    $('#resetFilter').on('click', function() {
      $('#statusFilter').val('');
      $('#priorityFilter').val('');
      $('#deadlineFilter').val('');
      $('#searchInput').val('');
      table.search('').columns().search('').draw();
    });

    function applyFilters() {
      const status = $('#statusFilter').val();
      const priority = $('#priorityFilter').val();
      const deadline = $('#deadlineFilter').val();

      // Clear existing searches
      table.search('').columns().search('').draw();

      // Clear any custom filtering functions
      $.fn.dataTable.ext.search.pop();

      // Add custom filter function
      $.fn.dataTable.ext.search.push(function(settings, data, dataIndex) {
        const row = table.row(dataIndex).node();

        // Status filtering
        if (status && status !== '') {
          const statusText = $(row).find('td:nth-child(3) .badge:first').text().trim().toLowerCase();
          if (!statusText.includes(status.toLowerCase())) {
            return false;
          }
        }

        // Priority filtering
        if (priority && priority !== '') {
          const priorityText = $(row).find('td:nth-child(3) .badge:last').text().trim().toLowerCase();
          if (!priorityText.includes(priority.toLowerCase())) {
            return false;
          }
        }

        // Deadline filtering
        if (deadline && deadline !== '') {
          const hasOverdueBadge = $(row).find('.badge-danger:contains("Overdue")').length > 0;
          const hasTodayBadge = $(row).find('.badge-warning:contains("Deadline Today")').length > 0;
          const hasTomorrowBadge = $(row).find('.badge-warning:contains("Due Tomorrow")').length > 0;

          if (deadline === 'overdue' && !hasOverdueBadge) {
            return false;
          } else if (deadline === 'urgent' && !hasTodayBadge && !hasTomorrowBadge) {
            return false;
          } else if (deadline === 'due_tomorrow' && !hasTomorrowBadge) {
            return false;
          } else if (deadline === 'upcoming' && (hasOverdueBadge || hasTodayBadge || hasTomorrowBadge)) {
            return false;
          }
        }

        return true;
      });

      // Apply filters
      table.draw();
    }

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
  $(document).ready(function() {
    $('#accessBlockedModal').on('show.bs.modal', function(event) {
      var button = $(event.relatedTarget);
      var reason = button.data('reason');
      var modal = $(this);
      modal.find('#blockReasonText').text(reason);
    });
  });
</script>
</body>

</html>