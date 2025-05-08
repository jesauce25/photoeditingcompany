<?php
include("includes/header.php");

// Make sure we have a database connection
if (!isset($conn)) {
  require_once '../includes/db_connection.php';
}

// Get the current user's ID from session
$user_id = $_SESSION['user_id'] ?? 0;

// Ensure we have a valid user ID
if (!$user_id) {
  error_log("Error: No valid user ID in session, redirecting to login");
  header("Location: ../login.php");
  exit;
}

// Fetch hidden tasks
$tasks = [];

try {
  // Query to get hidden tasks
  $query = "SELECT pa.*, p.project_title, c.company_name, p.deadline as project_deadline, 
            p.date_arrived, p.priority, p.status_project, p.total_images,
            (SELECT COUNT(pi.image_id) FROM tbl_project_images pi WHERE pi.assignment_id = pa.assignment_id) as assigned_image_count
            FROM tbl_project_assignments pa
            JOIN tbl_projects p ON pa.project_id = p.project_id
            LEFT JOIN tbl_companies c ON p.company_id = c.company_id
            WHERE pa.user_id = ? AND pa.status_assignee = 'completed' AND pa.is_hidden = 1
            ORDER BY pa.last_updated DESC";

  $stmt = $conn->prepare($query);
  $stmt->bind_param("i", $user_id);
  $stmt->execute();
  $result = $stmt->get_result();
  $tasks = $result->fetch_all(MYSQLI_ASSOC);

  error_log("History.php - Found " . count($tasks) . " hidden tasks for user ID: $user_id");

} catch (Exception $e) {
  error_log("Error in artist/history.php: " . $e->getMessage());
  $error_message = "There was an error loading your task history. Please contact support.";
}
?>
<style>
  /* Fix for content scrolling */
  html,
  body {
    height: 100%;
    overflow: auto;
  }

  .main-container {
    min-height: 100%;
    position: relative;
    overflow: hidden;
  }

  .content-wrapper {
    min-height: calc(100vh - 50px);
    height: auto;
    overflow-y: auto;
    padding-bottom: 60px;
  }

  /* Make table responsive but not cut off */
  .table-responsive {
    overflow-x: auto;
    max-height: none;
  }

  /* Loading overlay styling */
  .loading-overlay {
    display: none;
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(255, 255, 255, 0.7);
    z-index: 1000;
  }

  .loading-spinner {
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    width: 40px;
    height: 40px;
    border: 4px solid #f3f3f3;
    border-top: 4px solid #3498db;
    border-radius: 50%;
    animation: spin 1s linear infinite;
  }

  @keyframes spin {
    0% {
      transform: translate(-50%, -50%) rotate(0deg);
    }

    100% {
      transform: translate(-50%, -50%) rotate(360deg);
    }
  }
</style>

<div class="main-container">
  <?php include("includes/nav.php"); ?>

  <!-- Content Wrapper. Contains page content -->
  <div class="content-wrapper container-fluid">

    <!-- Content Header (Page header) -->
    <section class="content-header" style="opacity: 0; margin-top:30px;">
      <div class="container-fluid history-theme">
        <div class="row mb-2">
          <div class="col-sm-6">
            <h1><i class="fas fa-history mr-2"></i> Task History</h1>
          </div>
          <div class="col-sm-6">
            <ol class="breadcrumb float-sm-right">
              <li class="breadcrumb-item"><a href="home.php">Home</a></li>
              <li class="breadcrumb-item active">History</li>
            </ol>
          </div>
        </div>
      </div>
    </section>

    <!-- Main content -->
    <section class="content mt-3">
      <div class="container-fluid history-theme">
        <div class="card">
          <div class="card-header">
            <h3 class="card-title">
              <i class="fas fa-history mr-2"></i>Hidden Completed Tasks
            </h3>
          </div>
          <div class="card-body">
            <!-- Table controls -->
            <div class="row mb-4">
              <!-- Left Group: Export buttons -->
              <div class="col-md-4">
                <div class="btn-group">
                  <button type="button" class="btn btn-primary btn-sm export-excel" title="Export to Excel">
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
                    <select id="monthFilter" class="form-control form-control-sm mr-2 mb-2" style="width: 150px;">
                      <option value="">All Months</option>
                      <option value="January">January</option>
                      <option value="February">February</option>
                      <option value="March">March</option>
                      <option value="April">April</option>
                      <option value="May">May</option>
                      <option value="June">June</option>
                      <option value="July">July</option>
                      <option value="August">August</option>
                      <option value="September">September</option>
                      <option value="October">October</option>
                      <option value="November">November</option>
                      <option value="December">December</option>
                    </select>
                    <select id="yearFilter" class="form-control form-control-sm mr-2 mb-2" style="width: 100px;">
                      <option value="">All Years</option>
                      <?php
                      $currentYear = date('Y');
                      for ($i = $currentYear; $i >= $currentYear - 5; $i--) {
                        echo "<option value=\"$i\">$i</option>";
                      }
                      ?>
                    </select>
                    <select id="statusFilter" class="form-control form-control-sm mr-2 mb-2" style="width: 120px;">
                      <option value="">All Status</option>
                      <option value="ontime">On Time</option>
                      <option value="delayed">Delayed</option>
                    </select>
                    <button id="applyFilter" class="btn btn-primary btn-sm">
                      <i class="fas fa-filter mr-1"></i> Filter
                    </button>
                  </div>
                </div>
              </div>

              <!-- Right Group: Search -->
              <div class="col-md-4">
                <div class="search-box float-right">
                  <input type="text" id="searchInput" class="form-control form-control-sm"
                    placeholder="Search history...">
                  <button class="btn">
                    <i class="fas fa-search"></i>
                  </button>
                </div>
              </div>
            </div>

            <!-- Main table -->
            <div class="table-responsive position-relative">
              <!-- Loading overlay -->
              <div class="loading-overlay">
                <div class="loading-spinner"></div>
              </div>

              <table class="table table-bordered table-hover" id="historyTable">
                <thead>
                  <tr>
                    <th>Project</th>
                    <th>Due Date</th>
                    <th>Completion Date</th>
                    <th>Status</th>
                    <th>Details</th>
                  </tr>
                </thead>
                <tbody>
                  <?php if (empty($tasks)): ?>
                    <tr>
                      <td colspan="5" class="text-center">No hidden tasks found</td>
                    </tr>
                  <?php else: ?>
                    <?php foreach ($tasks as $task):
                      // Check if the task was completed on time
                      $was_overdue = strtotime($task['last_updated']) > strtotime($task['deadline']);
                      $status_class = $was_overdue ? 'table-danger' : 'table-success';
                      ?>
                      <tr class="<?php echo $status_class; ?>">
                        <td>
                          <div class="d-flex align-items-center">
                            <div class="ml-2">
                              <div class="project-title"><?php echo htmlspecialchars($task['project_title']); ?></div>
                              <div class="project-company"><?php echo htmlspecialchars($task['company_name'] ?? 'N/A'); ?>
                              </div>
                            </div>
                          </div>
                        </td>
                        <td>
                          <span class="deadline-text">
                            <i class="far fa-calendar-alt mr-1"></i>
                            <?php echo date('F d, Y', strtotime($task['deadline'])); ?>
                          </span>
                        </td>
                        <td>
                          <span class="completion-date">
                            <i class="fas fa-check-circle mr-1"></i>
                            <?php echo date('F d, Y', strtotime($task['last_updated'])); ?>
                            <?php if ($was_overdue): ?>
                              <small class="d-block text-muted text-danger">
                                <i class="fas fa-exclamation-circle mr-1"></i>
                                <?php
                                $days_late = floor((strtotime($task['last_updated']) - strtotime($task['deadline'])) / (60 * 60 * 24));
                                echo $days_late . ' day' . ($days_late > 1 ? 's' : '') . ' overdue';
                                ?>
                              </small>
                            <?php else: ?>
                              <small class="d-block text-muted">
                                <i class="far fa-clock mr-1"></i> Completed on time
                              </small>
                            <?php endif; ?>
                          </span>
                        </td>
                        <td>
                          <span class="project-status status-completed">Completed</span>
                          <small class="d-block text-muted <?php echo $was_overdue ? 'text-danger' : ''; ?>">
                            <i
                              class="<?php echo $was_overdue ? 'fas fa-exclamation-circle' : 'far fa-check-circle'; ?> mr-1"></i>
                            <?php echo $was_overdue ? 'Delayed' : 'On Time'; ?>
                          </small>
                        </td>
                        <td>
                          <div class="action-buttons text-center">
                            <a href="view-task.php?id=<?php echo $task['assignment_id']; ?>" class="btn btn-info btn-sm"
                              title="View Task">
                              <i class="fas fa-eye"></i> View
                            </a>
                            <button type="button" class="btn btn-warning btn-sm unhide-task-btn mt-1"
                              data-id="<?php echo $task['assignment_id']; ?>">
                              <i class="fas fa-eye mr-1"></i> Unhide
                            </button>
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
    </section>
  </div>


  <?php include("includes/footer.php"); ?>
  <script>
    $(document).ready(function () {
      // Initialize tooltips
      $('[data-toggle="tooltip"]').tooltip();

      // Search functionality
      $('#searchInput').on('keyup', function () {
        var value = $(this).val().toLowerCase();
        $('#historyTable tbody tr').filter(function () {
          $(this).toggle($(this).text().toLowerCase().indexOf(value) > -1);
        });
      });

      // Unhide task button
      $(document).on('click', '.unhide-task-btn', function () {
        var taskId = $(this).data('id');
        var button = $(this);
        var row = button.closest('tr');

        if (confirm('Are you sure you want to unhide this task? It will be moved back to your Tasks page.')) {
          $.ajax({
            url: 'controllers/task_controller.php',
            type: 'POST',
            data: {
              action: 'unhide_task',
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
                toastr.success('Task has been unhidden and moved back to Tasks');
              } else {
                toastr.error('Error: ' + response.message);
              }
            },
            error: function (xhr, status, error) {
              console.error('AJAX Error:', error);
              toastr.error('An error occurred while unhiding the task');
            }
          });
        }
      });

      // Filter functionality
      $('#applyFilter').click(function () {
        var month = $('#monthFilter').val();
        var year = $('#yearFilter').val();
        var status = $('#statusFilter').val();

        showLoading();

        // Simulate filtering delay
        setTimeout(function () {
          $('#historyTable tbody tr').show(); // Reset visibility

          // Apply selected filters
          $('#historyTable tbody tr').each(function () {
            var rowVisible = true;
            var rowText = $(this).text();

            // Month filter
            if (month && !rowText.includes(month)) {
              rowVisible = false;
            }

            // Year filter
            if (year && !rowText.includes(year)) {
              rowVisible = false;
            }

            // Status filter (ontime or delayed)
            if (status) {
              // Check for status specifically in the Status column
              var statusCell = $(this).find('td:nth-child(4)').text().toLowerCase();
              if (status === 'ontime' && !statusCell.includes('on time')) {
                rowVisible = false;
              } else if (status === 'delayed' && !statusCell.includes('delayed')) {
                rowVisible = false;
              }
            }

            // Show or hide row based on combined filter results
            $(this).toggle(rowVisible);
          });

          hideLoading();
        }, 600);
      });

      // Reset filter button
      $('#resetFilter').click(function () {
        $('#monthFilter').val('');
        $('#yearFilter').val('');
        $('#statusFilter').val('');
        $('#searchInput').val('');
        $('#historyTable tbody tr').show();
      });

      // Loading indicator functions
      function showLoading() {
        $('.loading-overlay').show();
      }

      function hideLoading() {
        $('.loading-overlay').hide();
      }
    });
  </script>