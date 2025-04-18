<?php
include("includes/header.php");
?>
<style>

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
              <i class="fas fa-history mr-2"></i>Completed Tasks History
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
                  <input type="text" id="searchInput" class="form-control form-control-sm" placeholder="Search history...">
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
                  <!-- Example data -->
                  <tr class="table-success">
                    <td>
                      <div class="d-flex align-items-center">
                        <div class="ml-2">
                          <div class="project-title">Website Redesign</div>
                          <div class="project-company">Technicore Solutions</div>
                        </div>
                      </div>
                    </td>
                    <td>
                      <span class="deadline-text">
                        <i class="far fa-calendar-alt mr-1"></i> March 15, 2023
                      </span>
                    </td>
                    <td>
                      <span class="completion-date">
                        <i class="fas fa-check-circle mr-1"></i> March 12, 2023
                      </span>
                    </td>
                    <td>
                      <span class="project-status status-completed">Completed</span>
                      <small class="d-block text-muted">
                        <i class="far fa-clock mr-1"></i> Completed 3 days early
                      </small>
                    </td>
                    <td>
                      <div class="action-buttons text-center">
                        <a href="view-task.php?id=1" class="btn btn-info btn-sm" title="View Task">
                          <i class="fas fa-eye"></i> View
                        </a>
                      </div>
                    </td>
                  </tr>
                  <tr class="table-danger">
                    <td>
                      <div class="d-flex align-items-center">
                        <div class="ml-2">
                          <div class="project-title">Mobile App Development</div>
                          <div class="project-company">Innovatech Inc.</div>
                        </div>
                      </div>
                    </td>
                    <td>
                      <span class="deadline-text">
                        <i class="far fa-calendar-alt mr-1"></i> February 28, 2023
                      </span>
                    </td>
                    <td>
                      <span class="completion-date">
                        <i class="fas fa-check-circle mr-1"></i> March 15, 2023


                        <small class="d-block text-muted text-danger">
                          <i class="fas fa-exclamation-circle mr-1"></i> 15 days overdue
                        </small>
                      </span>
                    </td>
                    <td>
                      <span class="project-status status-completed">
                        Completed
                      </span>
                      <small class="d-block text-muted text-danger">
                        <i class="fas fa-exclamation-circle mr-1"></i> Delayed
                      </small>
                    </td>
                    <td>
                      <div class="action-buttons text-center">
                        <a href="view-task.php?id=1" class="btn btn-info btn-sm" title="View Task">
                          <i class="fas fa-eye"></i> View
                        </a>
                      </div>
                    </td>
                  </tr>

                </tbody>
              </table>
            </div>


          </div>
        </div>
      </div>
    </section>
  </div>


  <script>
    $(document).ready(function() {
      // Initialize tooltips
      $('[data-toggle="tooltip"]').tooltip();

      // Search functionality
      $('#searchInput').on('keyup', function() {
        var value = $(this).val().toLowerCase();
        $('#historyTable tbody tr').filter(function() {
          $(this).toggle($(this).text().toLowerCase().indexOf(value) > -1);
        });
      });

      // Filter functionality
      $('#applyFilter').click(function() {
        var month = $('#monthFilter').val();
        showLoading();
        // Simulate filtering delay
        setTimeout(function() {
          if (month) {
            $('#historyTable tbody tr').hide();
            $('#historyTable tbody tr').each(function() {
              if ($(this).text().indexOf(month) > -1) {
                $(this).show();
              }
            });
          } else {
            $('#historyTable tbody tr').show();
          }
          hideLoading();
        }, 600);
      });

      // Export buttons functionality
      $('.export-excel').click(function() {
        alert('Exporting to Excel...');
        // Implementation would go here
      });

      $('.export-pdf').click(function() {
        alert('Exporting to PDF...');
        // Implementation would go here
      });

      $('.export-print').click(function() {
        window.print();
      });

      // View details functionality
      $('.view-details').click(function() {
        // In a real implementation, you would fetch the task details
        // For now, we'll just show the modal with sample data
      });

      // Loading indicator functions
      function showLoading() {
        $('.loading-overlay').css('display', 'flex');
      }

      function hideLoading() {
        $('.loading-overlay').css('display', 'none');
      }
    });
  </script>

  <?php include("includes/footer.php"); ?>