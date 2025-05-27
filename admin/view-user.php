<?php
include("includes/header.php");
?>
<div class="wrapper">
    <?php include("includes/nav.php"); ?>

    <!-- Content Wrapper -->
    <div class="content-wrapper">
        <!-- Content Header -->
        <section class="content-header">
            <div class="container-fluid">
                <div class="row mb-2">
                    <div class="col-sm-6">
                        <h1>
                            <i class="fas fa-user mr-2"></i>
                            User Details
                        </h1>
                    </div>
                    <div class="col-sm-6">
                        <ol class="breadcrumb float-sm-right">
                            <li class="breadcrumb-item"><a href="home.php">Home</a></li>
                            <li class="breadcrumb-item"><a href="user-list.php">User List</a></li>
                            <li class="breadcrumb-item active">View User</li>
                        </ol>
                    </div>
                </div>
            </div>
        </section>

        <!-- Main content -->
        <section class="content">
            <div class="container-fluid">
                <!-- Error alert container -->
                <div id="alertMessages"></div>

                <div id="userDetails" style="display: none;">
                    <div class="row">


                        <!-- User Details -->
                        <div class="col-md-12">
                            <div class="card">
                                <div class="card-header p-2">
                                    <ul class="nav nav-pills">
                                        <li class="nav-item">
                                            <a class="nav-link active text-white" href="#details" data-toggle="tab">
                                                <i class="fas fa-info-circle mr-1"></i> User Details
                                            </a>
                                        </li>
                                        <li class="nav-item">
                                            <a class="nav-link text-white" href="#userTasks" data-toggle="tab">
                                                <i class="fas fa-tasks mr-1"></i> User Tasks
                                            </a>
                                        </li>
                                    </ul>
                                </div>
                                <div class="card-body">
                                    <div class="tab-content">
                                        <!-- Details Tab -->
                                        <div class="active tab-pane" id="details">
                                            <h6 class="border-bottom pb-2 mb-3">Personal Information</h6>
                                            <div class="row mb-2">
                                                <div class="col-md-4 font-weight-bold">Full Name:</div>
                                                <div class="col-md-8" id="detailFullName">John Doe</div>
                                            </div>
                                            <div class="row mb-2">
                                                <div class="col-md-4 font-weight-bold">Email:</div>
                                                <div class="col-md-8" id="email">john@example.com</div>
                                            </div>
                                            <div class="row mb-2">
                                                <div class="col-md-4 font-weight-bold">Contact Number:</div>
                                                <div class="col-md-8" id="contactNum">+1 (555) 123-4567</div>
                                            </div>
                                            <div class="row mb-2">
                                                <div class="col-md-4 font-weight-bold">Birth Date:</div>
                                                <div class="col-md-8" id="birthDate">1990-01-15</div>
                                            </div>
                                            <div class="row mb-2">
                                                <div class="col-md-4 font-weight-bold">Address:</div>
                                                <div class="col-md-8" id="address">123 Main St, Anytown, USA</div>
                                            </div>

                                            <h6 class="border-bottom pb-2 mb-3 mt-4">Account Information</h6>
                                            <div class="row mb-2">
                                                <div class="col-md-4 font-weight-bold">Role:</div>
                                                <div class="col-md-8" id="detailRole">Project Manager</div>
                                            </div>
                                            <div class="row mb-2">
                                                <div class="col-md-4 font-weight-bold">Created On:</div>
                                                <div class="col-md-8" id="createdDate">2023-01-10 09:30:45</div>
                                            </div>
                                        </div>

                                        <!-- User Tasks Tab -->
                                        <div class="tab-pane" id="userTasks">
                                            <!-- Filter Options -->
                                            <div class="card card-outline card-primary mb-3">
                                                <div class="card-header">
                                                    <h3 class="card-title">
                                                        <i class="fas fa-filter mr-1"></i> Filter Tasks
                                                    </h3>
                                                    <div class="card-tools">
                                                        <button type="button" class="btn btn-tool"
                                                            data-card-widget="collapse">
                                                            <i class="fas fa-minus"></i>
                                                        </button>
                                                    </div>
                                                </div>
                                                <div class="card-body">
                                                    <div class="row">
                                                        <div class="col-12 text-center">
                                                            <div
                                                                class="d-inline-flex flex-wrap justify-content-center align-items-center">
                                                                <div class="p-1">
                                                                    <select id="statusFilter" class="form-control"
                                                                        style="width: 180px;">
                                                                        <option value="">All Status</option>
                                                                        <option value="pending">Pending</option>
                                                                        <option value="in_progress">In Progress</option>
                                                                        <option value="finish">Finish</option>
                                                                        <option value="qa">QA</option>
                                                                        <option value="completed">Completed</option>
                                                                        <option value="delayed">Delayed</option>
                                                                    </select>
                                                                </div>

                                                                <div class="p-1">
                                                                    <select id="priorityFilter" class="form-control"
                                                                        style="width: 180px;">
                                                                        <option value="">All Priorities</option>
                                                                        <option value="low">Low</option>
                                                                        <option value="medium">Medium</option>
                                                                        <option value="high">High</option>
                                                                        <option value="urgent">Urgent</option>
                                                                    </select>
                                                                </div>

                                                                <div class="p-1">
                                                                    <select id="deadlineFilter" class="form-control"
                                                                        style="width: 180px;">
                                                                        <option value="">All Deadlines</option>
                                                                        <option value="upcoming">Upcoming</option>
                                                                        <option value="overdue">Overdue</option>
                                                                    </select>
                                                                </div>

                                                                <div class="p-1">
                                                                    <button id="applyFilter" class="btn btn-info">
                                                                        <i class="fas fa-filter mr-1"></i> Apply Filters
                                                                    </button>
                                                                </div>

                                                                <div class="p-1">
                                                                    <button id="resetFilter" class="btn btn-secondary">
                                                                        <i class="fas fa-undo mr-1"></i> Reset
                                                                    </button>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>

                                            <!-- Tasks Table -->
                                            <div class="card">
                                                <div class="card-header">
                                                    <h3 class="card-title">
                                                        <i class="fas fa-list mr-1"></i> User Tasks
                                                    </h3>
                                                    <div class="card-tools">
                                                        <button type="button" id="toggleHistory"
                                                            class="btn btn-secondary btn-sm mr-2">
                                                            <i class="fas fa-history mr-1"></i> History
                                                        </button>
                                                        <button type="button" class="btn btn-tool"
                                                            data-card-widget="collapse">
                                                            <i class="fas fa-minus"></i>
                                                        </button>
                                                    </div>
                                                </div>
                                                <div class="card-body">
                                                    <div class="position-relative">
                                                        <div class="loading-overlay">
                                                            <div class="loading-spinner"></div>
                                                        </div>

                                                        <div class="table-responsive">
                                                            <table id="taskTable"
                                                                class="table table-bordered table-hover">
                                                                <thead>
                                                                    <tr>
                                                                        <th class="d-none">ID</th>
                                                                        <th>Project Details</th>
                                                                        <th>Status</th>
                                                                        <th>Date Arrived</th>
                                                                        <th>Total Images / Assigned</th>
                                                                        <th>Deadline / Task Deadline</th>
                                                                        <th>Role</th>
                                                                        <th class="text-center">Actions</th>
                                                                    </tr>
                                                                </thead>
                                                                <tbody>
                                                                    <!-- Task data will be loaded here dynamically -->
                                                                </tbody>
                                                            </table>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="card-footer">
                                                    <div class="float-left text-muted">
                                                        <span id="totalTasks">0</span> tasks found
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                    </div>
                    <!-- User Profile Image -->
                    <div class="col-md-12">
                        <!-- Profile Card -->
                        <div class="card card-primary card-outline">
                            <div class="card-body box-profile">
                                <div class="text-center">
                                    <?php
                                    $profile_img_path = '../dist/img/user-default.jpg';
                                    if (!empty($user['profile_img'])) {
                                        // Check multiple possible locations
                                        $possible_locations = [
                                            '../uploads/profile_pictures/' . basename($user['profile_img']),
                                            '../uploads/profile_pictures/' . $user['profile_img'],
                                            'assets/img/profile/' . $user['profile_img'],
                                            'profiles/' . $user['profile_img']
                                        ];

                                        foreach ($possible_locations as $location) {
                                            if (file_exists($location)) {
                                                $profile_img_path = $location;
                                                break;
                                            }
                                        }

                                        // If profile_img already contains a path prefix
                                        if (strpos($user['profile_img'], 'uploads/profile_pictures/') === 0) {
                                            $direct_path = '../' . $user['profile_img'];
                                            if (file_exists($direct_path)) {
                                                $profile_img_path = $direct_path;
                                            }
                                        }
                                    }
                                    ?>
                                    <img class="profile-user-img img-fluid img-circle"
                                        src="<?php echo $profile_img_path; ?>" alt="User profile picture">
                                </div>
                                <h3 class="profile-username text-center" id="fullName">User Name</h3>
                                <p class="text-muted text-center" id="userRole">Role</p>

                                <ul class="list-group list-group-unbordered mb-3">
                                    <li class="list-group-item">
                                        <b>Status</b> <span class="float-right badge" id="userStatus">Status</span>
                                    </li>
                                    <li class="list-group-item">
                                        <b>Username</b> <span class="float-right" id="username">username</span>
                                    </li>
                                    <li class="list-group-item">
                                        <b>Joined</b> <span class="float-right" id="dateJoined">Date</span>
                                    </li>
                                </ul>

                                <a href="#" id="editUserLink" class="btn btn-primary btn-block">
                                    <i class="fas fa-edit mr-1"></i> Edit User
                                </a>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Loading indicator -->
                <div id="loadingIndicator" class="text-center p-5">
                    <i class="fas fa-spinner fa-spin fa-3x mb-3"></i>
                    <p>Loading user details...</p>
                </div>

                <!-- User not found message -->
                <div id="userNotFound" class="text-center p-5" style="display: none;">
                    <div class="card">
                        <div class="card-body">
                            <i class="fas fa-exclamation-circle fa-3x text-warning mb-3"></i>
                            <h4>User Not Found</h4>
                            <p>The requested user could not be found or may have been deleted.</p>
                            <a href="user-list.php" class="btn btn-primary">
                                <i class="fas fa-arrow-left mr-1"></i> Back to User List
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </div>
</div>

<!-- Modal for viewing task images -->
<div class="modal fade" id="taskImagesModal" tabindex="-1" role="dialog" aria-labelledby="taskImagesModalLabel"
    aria-hidden="true">
    <div class="modal-dialog modal-xl" style="max-width: 90%;" role="document">
        <div class="modal-content">
            <div class="modal-header bg-info">
                <h5 class="modal-title" id="taskImagesModalLabel">
                    <i class="fas fa-images mr-2"></i> Task Images
                </h5>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <!-- Image list will be loaded here -->
                <div id="modalImagesContainer"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<?php include("includes/footer.php"); ?>

<!-- Page specific script -->
<script>
    $(document).ready(function() {
        // Get user ID from URL
        const urlParams = new URLSearchParams(window.location.search);
        const userId = urlParams.get('id');

        // If no user ID is provided, show error and redirect
        if (!userId) {
            showAlert('error', 'No user ID provided');
            setTimeout(function() {
                window.location.href = 'user-list.php';
            }, 2000);
            return;
        }

        // Set edit user link
        $('#editUserLink').attr('href', 'edit-user.php?id=' + userId);

        // Initialize loading overlay
        $('.loading-overlay').hide();

        // Initialize variables
        let taskTable;
        let showingHistory = false;

        // Toggle history button click
        $('#toggleHistory').on('click', function() {
            showingHistory = !showingHistory;
            $(this).toggleClass('btn-secondary btn-primary');

            if (showingHistory) {
                $(this).html('<i class="fas fa-tasks mr-1"></i> Current Tasks');
                $('#totalTasks').closest('.card-footer').find('.text-muted').html('<span id="totalTasks">0</span> hidden tasks found');
            } else {
                $(this).html('<i class="fas fa-history mr-1"></i> History');
                $('#totalTasks').closest('.card-footer').find('.text-muted').html('<span id="totalTasks">0</span> tasks found');
            }

            loadUserTasks(userId);
        });

        // Initialize the tasks when the user details tab is shown
        $('a[href="#userTasks"]').on('shown.bs.tab', function(e) {
            if (!taskTable) {
                initializeTaskTable();
                loadUserTasks(userId);
            }
        });

        // Load user details
        loadUserDetails(userId);

        // Apply filters button click
        $('#applyFilter').on('click', function() {
            applyFilters();
        });

        // Reset filters button click
        $('#resetFilter').on('click', function() {
            $('#statusFilter').val('');
            $('#priorityFilter').val('');
            $('#deadlineFilter').val('');
            if (taskTable) {
                taskTable.search('').columns().search('').draw();
                // Reload tasks without filters
                loadUserTasks(userId);
            }
        });

        // Function to initialize the task table
        function initializeTaskTable() {
            console.log('Initializing task table');

            // Destroy existing DataTable if it exists
            if ($.fn.DataTable.isDataTable('#taskTable')) {
                $('#taskTable').DataTable().destroy();
            }

            // Initialize DataTable
            taskTable = $('#taskTable').DataTable({
                "responsive": true,
                "lengthChange": true,
                "autoWidth": false,
                "pageLength": 1000,
                "searching": true,
                "ordering": true,
                "info": true,
                "dom": '<"row align-items-center"<"col-sm-6"l><"col-sm-6 d-flex justify-content-end"f>><"row"<"col-sm-12"tr>><"row"<"col-sm-5"i><"col-sm-7 d-flex justify-content-end"p>>',
                "createdRow": function(row, data, dataIndex) {
                    // The row is created but styling may be lost, so we need to 
                    // handle it here based on the deadline in the data
                    const deadlineText = $(row).find('td:nth-child(6)').text();
                    if (deadlineText.includes('Overdue') || deadlineText.includes('Deadline Today')) {
                        $(row).addClass('project-overdue');
                    } else if (deadlineText.includes('Due Tomorrow')) {
                        $(row).addClass('project-tomorrow');
                    }
                }
            });
        }

        // Function to apply filters
        function applyFilters() {
            if (!taskTable) return;

            const status = $('#statusFilter').val();
            const priority = $('#priorityFilter').val();
            const deadline = $('#deadlineFilter').val();

            // Clear existing searches and custom filtering functions
            taskTable.search('').columns().search('').draw();
            $.fn.dataTable.ext.search.pop();

            // Add custom filter function
            $.fn.dataTable.ext.search.push(function(settings, data, dataIndex) {
                const row = taskTable.row(dataIndex).node();

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
                    } else if (deadline === 'upcoming' && (hasOverdueBadge || hasTodayBadge || hasTomorrowBadge)) {
                        return false;
                    }
                }

                return true;
            });

            // Apply filters
            taskTable.draw();
        }

        // Function to load user details from the server
        function loadUserDetails(userId) {
            $.ajax({
                url: 'controllers/user_controller.php',
                type: 'GET',
                data: {
                    user_id: userId
                },
                dataType: 'json',
                success: function(response) {
                    // Hide loading indicator
                    $('#loadingIndicator').hide();

                    if (response.success && response.user) {
                        // Format dates for display
                        const createdDate = new Date(response.user.date_added);
                        const formattedCreatedDate = createdDate.toLocaleString();

                        const birthDate = new Date(response.user.birth_date);
                        const formattedBirthDate = birthDate.toLocaleDateString();

                        // Set profile image with proper path checking
                        let profileImgPath;
                        if (response.user.profile_img) {
                            // Option 1: Check if it's already a full path
                            if (response.user.profile_img.startsWith('../') ||
                                response.user.profile_img.startsWith('/')) {
                                profileImgPath = response.user.profile_img;
                            }
                            // Option 2: Check if it has a known directory prefix
                            else if (response.user.profile_img.startsWith('uploads/') ||
                                response.user.profile_img.startsWith('dist/') ||
                                response.user.profile_img.startsWith('assets/')) {
                                profileImgPath = '../' + response.user.profile_img;
                            }
                            // Option 3: Check different possible locations
                            else {
                                // Use the profile_img as is, server should have provided the correct path
                                profileImgPath = '../uploads/profile_pictures/' + response.user.profile_img;
                                console.log('Using profile path:', profileImgPath);
                            }
                        } else {
                            profileImgPath = '../dist/img/user-default.jpg';
                        }

                        // Set the profile image - target the correct element with class profile-user-img
                        $('.profile-user-img').attr('src', profileImgPath);

                        // Set user status with appropriate badge color
                        const statusBadgeClass = response.user.status === 'Active' ? 'badge-success' : 'badge-danger';
                        $('#userStatus').text(response.user.status).addClass(statusBadgeClass);

                        // Set basic user information
                        $('#fullName, #detailFullName').text(response.user.full_name);
                        $('#userRole, #detailRole').text(response.user.role);
                        $('#username').text(response.user.username);
                        $('#dateJoined').text(formattedCreatedDate);

                        // Set detailed user information
                        $('#email').text(response.user.email_address);
                        $('#contactNum').text(response.user.contact_num);
                        $('#birthDate').text(formattedBirthDate);
                        $('#address').text(response.user.address);
                        $('#createdDate').text(formattedCreatedDate);

                        // Show user details
                        $('#userDetails').show();

                        // Initialize task table when on user details tab
                        if ($('a[href="#userTasks"]').parent().hasClass('active')) {
                            initializeTaskTable();
                            loadUserTasks(userId);
                        }
                    } else {
                        // Show user not found message
                        $('#userNotFound').show();
                        showAlert('error', response.message || 'User not found');
                    }
                },
                error: function(xhr, status, error) {
                    // Hide loading indicator and show error
                    $('#loadingIndicator').hide();
                    $('#userNotFound').show();

                    // Parse error response
                    let errorMsg = 'Error loading user details.';
                    try {
                        const response = JSON.parse(xhr.responseText);
                        if (response && response.message) {
                            errorMsg = response.message;
                        }
                    } catch (e) {
                        console.error('Error parsing error response:', e);
                    }

                    showAlert('error', errorMsg + '<br>Technical details: ' + error);
                }
            });
        }

        // Function to load user tasks
        function loadUserTasks(userId) {
            console.log('Loading tasks for user ID:', userId);

            // Show loading indicator
            $('.loading-overlay').show();

            // Initialize table if not already done
            if (!taskTable) {
                initializeTaskTable();
            }

            // Clear the existing table data
            taskTable.clear();

            // Make the AJAX request
            $.ajax({
                url: 'controllers/task_controller.php',
                type: 'GET',
                data: {
                    action: 'get_user_tasks',
                    user_id: userId,
                    show_hidden: showingHistory ? 1 : 0
                },
                dataType: 'json',
                success: function(response) {
                    console.log('Tasks loaded:', response);

                    // Hide loading indicator
                    $('.loading-overlay').hide();

                    if (response.success && response.tasks && response.tasks.length > 0) {
                        // Update total tasks count
                        $('#totalTasks').text(response.tasks.length);

                        // Add tasks to the table
                        response.tasks.forEach(function(task) {
                            // Format status with appropriate badge
                            let statusBadge, priorityBadge = '';

                            // Status badge
                            switch (task.status_assignee.toLowerCase()) {
                                case 'pending':
                                    statusBadge = '<span class="badge badge-secondary">Pending</span>';
                                    break;
                                case 'in_progress':
                                    statusBadge = '<span class="badge badge-info">In Progress</span>';
                                    break;
                                case 'finish':
                                    statusBadge = '<span class="badge badge-primary">Finish</span>';
                                    break;
                                case 'qa':
                                    statusBadge = '<span class="badge badge-warning">QA</span>';
                                    break;
                                case 'completed':
                                    statusBadge = '<span class="badge badge-success">Completed</span>';
                                    break;
                                case 'delayed':
                                    statusBadge = '<span class="badge badge-danger">Delayed</span>';
                                    break;
                                default:
                                    statusBadge = '<span class="badge badge-secondary">' + task.status_assignee + '</span>';
                            }

                            // Add priority badge if available
                            if (task.priority) {
                                switch (task.priority.toLowerCase()) {
                                    case 'low':
                                        priorityBadge = '<div class="mt-1"><span class="badge badge-success">Low</span></div>';
                                        break;
                                    case 'medium':
                                        priorityBadge = '<div class="mt-1"><span class="badge badge-info">Medium</span></div>';
                                        break;
                                    case 'high':
                                        priorityBadge = '<div class="mt-1"><span class="badge badge-warning">High</span></div>';
                                        break;
                                    case 'urgent':
                                        priorityBadge = '<div class="mt-1"><span class="badge badge-danger">Urgent</span></div>';
                                        break;
                                }
                            }

                            // Format dates
                            const assignedDate = new Date(task.assigned_date);
                            const formattedAssignedDate = assignedDate.toLocaleDateString();

                            let deadlineDate = 'N/A';
                            let deadlineClass = '';
                            let deadlineBadge = '';
                            let rowClass = '';

                            if (task.deadline) {
                                const deadline = new Date(task.deadline);
                                deadlineDate = deadline.toLocaleDateString();

                                // Check if overdue, due today or tomorrow
                                const today = new Date();
                                today.setHours(0, 0, 0, 0);

                                const tomorrow = new Date();
                                tomorrow.setDate(tomorrow.getDate() + 1);
                                tomorrow.setHours(0, 0, 0, 0);

                                deadline.setHours(0, 0, 0, 0);

                                if (deadline < today) {
                                    deadlineClass = 'text-danger';
                                    deadlineBadge = '<span class="badge badge-danger ml-1">Overdue</span>';
                                    rowClass = 'project-overdue'; // Red background for overdue (matching project-list.php)
                                } else if (deadline.getTime() === today.getTime()) {
                                    deadlineClass = 'text-danger';
                                    deadlineBadge = '<span class="badge badge-warning ml-1">Deadline Today</span>';
                                    rowClass = 'project-overdue'; // Red background for due today (matching project-list.php)
                                } else if (deadline.getTime() === tomorrow.getTime()) {
                                    deadlineClass = '';
                                    deadlineBadge = '<span class="badge badge-warning ml-1">Due Tomorrow</span>';
                                    rowClass = 'project-tomorrow'; // Orange background (matching project-list.php)
                                }
                            }

                            // Format dates (project deadline if available)
                            let projectDeadline = task.project_deadline ? new Date(task.project_deadline).toLocaleDateString() : 'N/A';

                            // Create project details HTML
                            const projectDetails = `
                                <div class="project-info">
                                    <div class="project-title">
                                        ${task.project_title || 'Untitled Project'}
                                    </div>
                                    <div class="project-client">
                                        <i class="fas fa-building mr-1"></i>
                                        ${task.company_name || 'N/A'}
                                    </div>
                                </div>
                            `;

                            // Total / assigned images
                            const imagesCount = `
                                <div class="task-count">
                                    <div class="images-count">
                                        <i class="fas fa-images mr-1"></i>
                                        <span>${task.total_images || 0} / ${task.assigned_images || 0}</span>
                                    </div>
                                </div>
                            `;

                            // Deadline display
                            const deadlineDisplay = `
                                <div class="deadlines">
                                    <div class="project-deadline">
                                        <strong>Project:</strong>
                                        ${projectDeadline}
                                    </div>
                                    <div class="task-deadline ${deadlineClass}">
                                        <strong>Task:</strong> ${deadlineDate}
                                        ${deadlineBadge}
                                    </div>
                                </div>
                            `;

                            // Create role badge with acronyms from image_roles instead of role_task
                            let roleBadge = '';
                            if (task.image_roles) {
                                // Split multiple roles if they exist
                                const roles = task.image_roles.split(',').map(role => role.trim());
                                const uniqueRoles = [...new Set(roles)]; // Filter out duplicates

                                roleBadge = `<div class="d-flex flex-wrap">`;
                                uniqueRoles.forEach(role => {
                                    // Convert to acronym
                                    let acronym = '';
                                    switch (role.toLowerCase()) {
                                        case 'retouch':
                                            acronym = 'R';
                                            break;
                                        case 'clipping path':
                                            acronym = 'CP';
                                            break;
                                        case 'color correction':
                                            acronym = 'CC';
                                            break;
                                        case 'final':
                                            acronym = 'F';
                                            break;
                                        case 'retouch to final':
                                            acronym = 'RT';
                                            break;
                                        default:
                                            // For other roles, use first letter or first 2 letters
                                            const words = role.split(' ');
                                            if (words.length > 1) {
                                                acronym = words[0].substring(0, 1).toUpperCase() + words[1].substring(0, 1).toUpperCase();
                                            } else {
                                                acronym = role.substring(0, 2).toUpperCase();
                                            }
                                    }

                                    roleBadge += `
                                        <div class="role-badge" title="${role}" 
                                            style="display:inline-block; padding:3px 8px; margin-right:5px; margin-bottom:3px; background-color:#17a2b8; color:white; border-radius:3px; font-size:0.8rem;">
                                            ${acronym}
                                        </div>`;
                                });
                                roleBadge += `</div>`;
                            } else if (task.role_task) {
                                // Fallback to role_task if image_roles is not available
                                roleBadge = `<span class="badge badge-info">${task.role_task}</span>`;
                            } else {
                                roleBadge = '<span class="badge badge-secondary">Not Assigned</span>';
                            }

                            // Create action button
                            const actionButton = `
                                <button type="button" class="btn btn-info btn-sm view-task-btn" data-id="${task.assignment_id}">
                                    <i class="fas fa-eye mr-1"></i> View
                                </button>
                            `;

                            // Add row to DataTable without drawing immediately
                            const rowNode = taskTable.row.add([
                                task.assignment_id, // Hidden column
                                projectDetails,
                                statusBadge + priorityBadge,
                                formattedAssignedDate,
                                imagesCount,
                                deadlineDisplay,
                                roleBadge,
                                actionButton
                            ]).node();

                            // Apply the row class for styling before drawing
                            if (rowClass) {
                                $(rowNode).addClass(rowClass);
                            }
                        });

                        // Draw the table after adding all rows
                        taskTable.draw();
                        console.log('Table drawn with data');

                        // Ensure row styles are applied after draw
                        $('#taskTable tbody tr').each(function() {
                            // Check for both old and new class names for compatibility
                            if ($(this).hasClass('table-danger') || $(this).hasClass('project-overdue')) {
                                // Remove old class if exists and add the new one
                                $(this).removeClass('table-danger').addClass('project-overdue');
                            } else if ($(this).hasClass('table-warning') || $(this).hasClass('project-tomorrow')) {
                                // Remove old class if exists and add the new one
                                $(this).removeClass('table-warning').addClass('project-tomorrow');
                            }
                        });
                    } else {
                        // No tasks found
                        $('#totalTasks').text('0');
                        taskTable.draw();
                        console.log('No tasks found');
                    }
                },
                error: function(xhr, status, error) {
                    // Hide loading indicator
                    $('.loading-overlay').hide();

                    console.error('Error loading tasks:', error);

                    // Try to get more detailed error information
                    let errorMsg = 'Failed to load user tasks.';

                    try {
                        // Check if the response is HTML (likely PHP error)
                        if (xhr.responseText && xhr.responseText.trim().startsWith('<')) {
                            errorMsg += ' Server returned an error.';
                            console.error('Server error response:', xhr.responseText);
                        }
                        // Try to parse as JSON
                        else if (xhr.responseJSON && xhr.responseJSON.message) {
                            errorMsg += ' ' + xhr.responseJSON.message;
                        }
                    } catch (e) {
                        console.error('Error parsing error details:', e);
                    }

                    // Display a user-friendly error message
                    showAlert('error', errorMsg);
                }
            });
        }

        // Function to load task images when a task is viewed
        $(document).on('click', '.view-task-btn', function() {
            const assignmentId = $(this).data('id');
            const row = $(this).closest('tr');
            const projectTitle = row.find('.project-title').text().trim();

            console.log('Viewing task:', assignmentId, 'Project:', projectTitle);

            // Set the modal title
            $('#taskImagesModalLabel').html(`<i class="fas fa-images mr-2"></i> Images for: ${projectTitle}`);

            // Show loading in the modal
            $('#modalImagesContainer').html('<div class="text-center p-5"><i class="fas fa-spinner fa-spin fa-3x"></i><p class="mt-3">Loading images...</p></div>');

            // Show the modal
            $('#taskImagesModal').modal('show');

            // Load images for this task
            $.ajax({
                url: 'controllers/task_controller.php',
                type: 'GET',
                data: {
                    action: 'get_task_images',
                    assignment_id: assignmentId
                },
                dataType: 'json',
                success: function(response) {
                    console.log('Images loaded:', response);
                    if (response.success && response.images && response.images.length > 0) {
                        // Create table to display images
                        let imagesHtml = `
                            <div class="table-responsive">
                                <table class="table table-bordered table-hover" style="margin-bottom: 0;">
                                    <thead>
                                        <tr class="bg-light">
                                            <th style="width: 3%;">#</th>
                                            <th style="width: 35%;">Image Name</th>
                                            <th style="width: 25%;">Role</th>
                                            <th style="width: 20%;">Status</th>
                                            <th style="width: 17%;">Estimated Time</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                        `;

                        // Add CSS for modal image name truncation
                        imagesHtml += `
                            <style>
                                #modalImagesContainer td:nth-child(2) {
                                    max-width: 300px;
                                    white-space: nowrap;
                                    overflow: hidden;
                                    text-overflow: ellipsis;
                                }
                                #modalImagesContainer td:nth-child(2):hover {
                                    white-space: normal;
                                    overflow: visible;
                                    word-break: break-all;
                                }
                            </style>
                        `;

                        response.images.forEach(function(image, index) {
                            // Format status with appropriate badge
                            let statusBadge;
                            const status = (image.status_image || 'available').toLowerCase();

                            switch (status) {
                                case 'available':
                                    // Display as "pending" in UI (without changing backend)
                                    statusBadge = '<span class="badge badge-secondary">Pending</span>';
                                    break;
                                case 'in_progress':
                                    statusBadge = '<span class="badge badge-info">In Progress</span>';
                                    break;
                                case 'finish':
                                    statusBadge = '<span class="badge badge-primary">Finished</span>';
                                    break;
                                case 'qa':
                                    statusBadge = '<span class="badge badge-warning">QA</span>';
                                    break;
                                case 'completed':
                                    statusBadge = '<span class="badge badge-success">Completed</span>';
                                    break;
                                default:
                                    statusBadge = '<span class="badge badge-secondary">Pending</span>';
                            }

                            // Format estimated time
                            let estimatedTime = 'Not set';
                            if (image.estimated_time) {
                                const time = parseInt(image.estimated_time);
                                if (time >= 60) {
                                    const hours = Math.floor(time / 60);
                                    const minutes = time % 60;
                                    estimatedTime = hours + 'hr' + (minutes > 0 ? ' ' + minutes + 'min' : '');
                                } else {
                                    estimatedTime = time + ' min';
                                }
                            }

                            // Redo badge if needed
                            const redoBadge = image.redo === '1' ? '<span class="badge badge-danger ml-2">Redo Required</span>' : '';

                            // Format role badges with acronyms
                            let roleBadges = '';
                            if (image.image_role) {
                                // Split multiple roles if they exist
                                const roles = image.image_role.split(',').map(role => role.trim());

                                roleBadges = `<div class="d-flex flex-wrap">`;
                                roles.forEach(role => {
                                    roleBadges += `
                                        <div class="badge badge-info" 
                                            style="display:inline-block; margin-right:5px; margin-bottom:3px; font-size:0.85rem;">
                                            ${role}
                                        </div>`;
                                });
                                roleBadges += `</div>`;
                            } else {
                                roleBadges = '<span class="text-muted">Not set</span>';
                            }

                            // Add row to table with minimal padding to save space
                            imagesHtml += `
                                <tr class="${image.redo === '1' ? 'table-danger' : ''}" style="line-height: 1.2;">
                                    <td style="padding: 4px 8px; vertical-align: middle;">${index + 1}</td>
                                    <td style="padding: 4px 8px; vertical-align: middle;">
                                        ${image.image_path}
                                        ${redoBadge}
                                    </td>
                                    <td style="padding: 4px 8px; vertical-align: middle;">${roleBadges}</td>
                                    <td style="padding: 4px 8px; vertical-align: middle;">${statusBadge}</td>
                                    <td style="padding: 4px 8px; vertical-align: middle;">${estimatedTime}</td>
                                </tr>
                            `;
                        });

                        imagesHtml += `
                                    </tbody>
                                </table>
                            </div>
                        `;

                        $('#modalImagesContainer').html(imagesHtml);
                    } else {
                        $('#modalImagesContainer').html('<div class="alert alert-info">No images found for this task.</div>');
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Error loading task images:', error);

                    let errorMsg = 'Failed to load images. Please try again.';

                    // Try to get more detailed error information
                    try {
                        // Check if the response is HTML (likely PHP error)
                        if (xhr.responseText && xhr.responseText.trim().startsWith('<')) {
                            console.error('Server error response:', xhr.responseText);
                        }
                        // Try to parse as JSON
                        else if (xhr.responseJSON && xhr.responseJSON.message) {
                            errorMsg += ' ' + xhr.responseJSON.message;
                        }
                    } catch (e) {
                        console.error('Error parsing error details:', e);
                    }

                    $('#modalImagesContainer').html(`<div class="alert alert-danger">${errorMsg}</div>`);
                }
            });
        });

        // Function to show alert messages
        function showAlert(type, message) {
            const alertClass = type === 'success' ? 'alert-success' : 'alert-danger';
            const alertIcon = type === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle';

            const alertHtml = `
            <div class="alert ${alertClass} alert-dismissible fade show" role="alert">
                <i class="fas ${alertIcon} mr-2"></i> ${message}
                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
        `;

            $('#alertMessages').html(alertHtml);

            // Auto dismiss after 5 seconds
            setTimeout(function() {
                $('.alert').alert('close');
            }, 5000);
        }
    });
</script>

<style>
    /* Task table styling */
    .project-title {
        font-weight: bold;
        font-size: 1rem;
    }

    .project-client {
        font-size: 0.8rem;
        opacity: 0.8;
    }

    .company-info {
        font-size: 0.8rem;
        opacity: 0.8;
    }

    .deadline-warning {
        display: block;
        margin-top: 5px;
    }

    .task-count {
        font-size: 1rem;
        font-weight: bold;
        color: #343a40;
    }

    /* Loading overlay */
    .loading-overlay {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background-color: rgba(255, 255, 255, 0.8);
        z-index: 2;
        display: flex;
        justify-content: center;
        align-items: center;
    }

    .loading-spinner {
        border: 5px solid #f3f3f3;
        border-radius: 50%;
        border-top: 5px solid #3498db;
        width: 50px;
        height: 50px;
        -webkit-animation: spin 1s linear infinite;
        animation: spin 1s linear infinite;
    }

    @-webkit-keyframes spin {
        0% {
            -webkit-transform: rotate(0deg);
        }

        100% {
            -webkit-transform: rotate(360deg);
        }
    }

    @keyframes spin {
        0% {
            transform: rotate(0deg);
        }

        100% {
            transform: rotate(360deg);
        }
    }

    /* Hide first column (ID) */
    #taskTable th:first-child,
    #taskTable td:first-child {
        display: none;
    }

    /* Project row highlighting - matching project-list.php styling */
    .project-overdue,
    .table-danger,
    table.dataTable tbody tr.project-overdue,
    table.dataTable tbody tr.table-danger,
    #taskTable tbody tr.project-overdue,
    #taskTable tbody tr.table-danger {
        background-color: rgba(255, 37, 37, 0.59) !important;
    }

    /* Override DataTables hover */
    table.dataTable tbody tr.project-overdue:hover,
    table.dataTable tbody tr.table-danger:hover,
    #taskTable tbody tr.project-overdue:hover,
    #taskTable tbody tr.table-danger:hover {
        background-color: rgba(255, 37, 37, 0.59) !important;
    }

    .project-tomorrow,
    .table-warning,
    table.dataTable tbody tr.project-tomorrow,
    table.dataTable tbody tr.table-warning,
    #taskTable tbody tr.project-tomorrow,
    #taskTable tbody tr.table-warning {
        background-color: #fff3cd !important;
    }



    .project-tomorrow td,
    .table-warning td,
    table.dataTable tbody tr.project-tomorrow td,
    table.dataTable tbody tr.table-warning td,
    #taskTable tbody tr.project-tomorrow td,
    #taskTable tbody tr.table-warning td {
        background-color: #fff3cd !important;
    }

    /* When printing, ensure colors are visible */
    @media print {

        .project-overdue,
        .table-danger {
            background-color: #ffcccc !important;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }

        .project-tomorrow,
        .table-warning {
            background-color: #fff3cd !important;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }
    }

    /* Compact table styling */
    #taskTable td {
        vertical-align: middle;
    }

    /* Badge styling */
    .badge {
        font-size: 0.8rem;
        padding: 0.35em 0.65em;
    }

    /* When printing, ensure colors are visible */
    @media print {
        .table-danger {
            background-color: #ffcccc !important;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }

        .table-warning {
            background-color: #fff3cd !important;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }
    }
</style>