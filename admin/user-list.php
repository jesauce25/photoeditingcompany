<?php
include("includes/header.php");
// Custom CSS for user management loaded in header
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
                            <i class="fas fa-users mr-2"></i>
                            User Management
                        </h1>
                    </div>
                    <div class="col-sm-6">
                        <ol class="breadcrumb float-sm-right">
                            <li class="breadcrumb-item"><a href="home.php">Home</a></li>
                            <li class="breadcrumb-item active">User List</li>
                        </ol>
                    </div>
                </div>
            </div>
        </section>

        <!-- Main content -->
        <section class="content">
            <div class="container-fluid">
                <!-- Error/Success Messages -->
                <div id="alertMessages">
                    <?php
                    // Display session messages - Don't start a new session as header.php already does that
                    if (isset($_SESSION['registration_success'])) {
                        echo '<div class="alert alert-success alert-dismissible fade show" role="alert">
                                <i class="fas fa-check-circle mr-2"></i> ' . $_SESSION['registration_message'] . '
                                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                    <span aria-hidden="true">&times;</span>
                                </button>
                              </div>';
                        // Clear the session variables
                        unset($_SESSION['registration_success']);
                        unset($_SESSION['registration_message']);
                    }

                    // Check for flash messages from edit-user.php
                    if (isset($_SESSION['flash_message'])) {
                        $type = $_SESSION['flash_message']['type'] === 'success' ? 'success' : 'danger';
                        $icon = $type === 'success' ? 'check-circle' : 'exclamation-circle';

                        echo '<div class="alert alert-' . $type . ' alert-dismissible fade show" role="alert">
                                <i class="fas fa-' . $icon . ' mr-2"></i> ' . $_SESSION['flash_message']['message'] . '
                                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                    <span aria-hidden="true">&times;</span>
                                </button>
                              </div>';

                        // Clear the flash message
                        unset($_SESSION['flash_message']);
                    }
                    ?>
                </div>

                <div class="row">
                    <div class="col-md-12">
                        <div class="card">
                            <div class="card-header">
                                <h3 class="card-title">User List</h3>
                                <div class="card-tools">
                                    <a href="add-user.php" class="btn btn-primary btn-sm">
                                        <i class="fas fa-user-plus mr-1"></i> Add New User
                                    </a>
                                </div>
                            </div>
                            <div class="card-body">
                                <!-- Filters -->
                                <div class="row mb-3">
                                    <div class="col-md-3">
                                        <div class="form-group">
                                            <label for="roleFilter">Filter by Role</label>
                                            <select class="form-control form-control-sm" id="roleFilter"
                                                style="height: 100%;">
                                                <option value="">All Roles</option>
                                                <option value="Admin">Super Admin</option>
                                                <option value="Project Manager">Project Manager</option>
                                                <option value="Graphic Artist">Graphic Artist</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="form-group">
                                            <label for="statusFilter">Filter by Status</label>
                                            <select class="form-control form-control-sm" id="statusFilter"
                                                style="height: 100%;">
                                                <option value="">All Status</option>
                                                <option value="Active">Active</option>
                                                <option value="Blocked">Blocked</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="searchInput">Search</label>
                                            <input type="text" class="form-control form-control-sm"
                                                style="height: 100%;" id="searchInput"
                                                placeholder="Search by name, email, username...">
                                        </div>
                                    </div>
                                </div>

                                <!-- Users Table -->
                                <div class="table-responsive">
                                    <table id="usersTable" class="table table-bordered table-striped">
                                        <thead>
                                            <tr>
                                                <th width="25%">User Info</th>
                                                <th width="20%">Username</th>
                                                <th width="15%">Email</th>
                                                <th width="10%">Role</th>
                                                <th width="10%">Status</th>
                                                <th width="20%">Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody id="userTableBody">
                                            <!-- User data will be loaded dynamically -->
                                            <tr>
                                                <td colspan="6" class="text-center">Loading users...</td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
    </div>
    </section>
</div>
</div>

<!-- Toggle Status Modal -->
<div class="modal fade" id="toggleStatusModal" tabindex="-1" role="dialog" aria-labelledby="toggleStatusModalLabel"
    aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="toggleStatusModalLabel">Confirm Status Change</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <!-- Content will be dynamically updated -->
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="confirmToggleStatus">Confirm</button>
            </div>
        </div>
    </div>
</div>

<!-- Delete User Modal -->
<div class="modal fade" id="deleteUserModal" tabindex="-1" role="dialog" aria-labelledby="deleteUserModalLabel"
    aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="deleteUserModalLabel">Confirm Delete</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <!-- Content will be dynamically updated -->
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-danger" id="confirmDeleteUser">Delete</button>
            </div>
        </div>
    </div>
</div>

<!-- Toast Container -->
<div id="toastContainer" class="toast-container position-fixed bottom-0 right-0 p-3"></div>


<?php include("includes/footer.php"); ?>
<!-- Page specific script -->
<script>
    $(document).ready(function () {
        // Show loading state
        $('#userTableBody').html('<tr><td colspan="6" class="text-center"><i class="fas fa-spinner fa-spin"></i> Loading users...</td></tr>');

        // Load users on page load
        loadUsers();

        // Filter functionality
        $('#roleFilter, #statusFilter').change(function () {
            filterUsers();
        });

        // Search functionality
        $('#searchInput').on('keyup', function () {
            filterUsers();
        });

        // Toggle status button click
        $(document).on('click', '.toggle-status', function () {
            const userId = $(this).data('id');
            const status = $(this).data('status');
            const isActive = status.toLowerCase() === 'active';
            const newStatus = isActive ? 'Blocked' : 'Active';

            // Update modal content
            $('#toggleStatusModal .modal-body').html(`
                <p>Are you sure you want to ${isActive ? 'block' : 'activate'} this user?</p>
                <input type="hidden" id="toggleUserId" value="${userId}">
                <input type="hidden" id="toggleNewStatus" value="${newStatus}">
            `);

            // Update modal title and button
            $('#toggleStatusModalLabel').text(isActive ? 'Block User' : 'Activate User');
            $('#confirmToggleStatus').html(isActive ? '<i class="fas fa-ban mr-1"></i> Block' : '<i class="fas fa-check mr-1"></i> Activate');
            $('#confirmToggleStatus').removeClass(isActive ? 'btn-success' : 'btn-warning').addClass(isActive ? 'btn-warning' : 'btn-success');

            $('#toggleStatusModal').modal('show');
        });

        // Confirm toggle status
        $('#confirmToggleStatus').click(function () {
            const userId = $('#toggleUserId').val();
            const newStatus = $('#toggleNewStatus').val();

            $.ajax({
                url: 'controllers/user_controller.php',
                type: 'POST',
                data: {
                    user_id: userId,
                    status: newStatus
                },
                dataType: 'json',
                success: function (response) {
                    if (response.success) {
                        showToast('success', response.message);
                        loadUsers();
                    } else {
                        showToast('error', response.message || 'An error occurred while updating user status');
                        console.error('Error updating status:', response);
                    }
                },
                error: function (xhr, status, error) {
                    console.error('AJAX Error:', xhr.responseText);
                    showToast('error', 'Error: ' + error);
                }
            });

            $('#toggleStatusModal').modal('hide');
        });

        // Delete user button click
        $(document).on('click', '.delete-user', function () {
            const userId = $(this).data('id');

            // Update modal content
            $('#deleteUserModal .modal-body').html(`
                <p>Are you sure you want to delete this user? This action cannot be undone.</p>
                <input type="hidden" id="deleteUserId" value="${userId}">
            `);

            $('#deleteUserModal').modal('show');
        });

        // Confirm delete user
        $('#confirmDeleteUser').click(function () {
            const userId = $('#deleteUserId').val();

            $.ajax({
                url: 'controllers/user_controller.php',
                type: 'POST',
                data: {
                    user_id: userId
                },
                dataType: 'json',
                success: function (response) {
                    if (response.success) {
                        showToast('success', response.message);
                        loadUsers();
                    } else {
                        showToast('error', response.message);
                    }
                },
                error: function (xhr, status, error) {
                    showToast('error', 'Error: ' + error);
                }
            });

            $('#deleteUserModal').modal('hide');
        });

        // Function to load users from the server
        function loadUsers() {
            console.log('Loading users...');
            $.ajax({
                url: 'controllers/user_controller.php',
                type: 'GET',
                dataType: 'json',
                data: {
                    action: 'get_users'  // Specify the action to avoid any ambiguity
                },
                success: function (response) {
                    console.log('Response received:', response);
                    if (response.success) {
                        if (response.users && response.users.length > 0) {
                            displayUsers(response.users);
                        } else {
                            $('#userTableBody').html('<tr><td colspan="6" class="text-center">No users found</td></tr>');
                        }
                    } else {
                        console.error('Error loading users:', response.message);
                        $('#userTableBody').html('<tr><td colspan="6" class="text-center text-danger">Error: ' + response.message + '</td></tr>');
                    }
                },
                error: function (xhr, status, error) {
                    console.error('AJAX Error:', xhr.responseText);
                    console.error('Status:', status);
                    console.error('Error:', error);

                    // Try to parse the response in case it's HTML with an error message
                    try {
                        // Look for specific text patterns in the response that might indicate an error
                        let errorMsg = 'Error loading users';

                        if (xhr.responseText.includes('Fatal error')) {
                            const errorMatch = xhr.responseText.match(/Fatal error:(.*?)<br/);
                            if (errorMatch && errorMatch[1]) {
                                errorMsg = 'PHP Error: ' + errorMatch[1].trim();
                            }
                        } else if (xhr.responseText.includes('Warning')) {
                            const warningMatch = xhr.responseText.match(/Warning:(.*?)<br/);
                            if (warningMatch && warningMatch[1]) {
                                errorMsg = 'PHP Warning: ' + warningMatch[1].trim();
                            }
                        } else if (xhr.responseText.includes('Notice')) {
                            const noticeMatch = xhr.responseText.match(/Notice:(.*?)<br/);
                            if (noticeMatch && noticeMatch[1]) {
                                errorMsg = 'PHP Notice: ' + noticeMatch[1].trim();
                            }
                        }

                        $('#userTableBody').html(`
                            <tr>
                                <td colspan="6" class="text-center text-danger">
                                    <p><i class="fas fa-exclamation-circle mr-2"></i>${errorMsg}</p>
                                    <button id="retryLoadUsers" class="btn btn-sm btn-outline-primary mt-2">
                                        <i class="fas fa-sync mr-1"></i> Retry
                                    </button>
                                </td>
                            </tr>
                        `);

                        // Add event listener for retry button
                        $('#retryLoadUsers').on('click', function () {
                            loadUsers();
                        });

                    } catch (e) {
                        $('#userTableBody').html(`
                            <tr>
                                <td colspan="6" class="text-center text-danger">
                                    <p><i class="fas fa-exclamation-triangle mr-2"></i>Failed to load users. Please try again.</p>
                                    <button id="retryLoadUsers" class="btn btn-sm btn-outline-primary mt-2">
                                        <i class="fas fa-sync mr-1"></i> Retry
                                    </button>
                                </td>
                            </tr>
                        `);

                        // Add event listener for retry button
                        $('#retryLoadUsers').on('click', function () {
                            loadUsers();
                        });
                    }
                }
            });
        }

        function displayUsers(users) {
            console.log('Displaying users:', users);
            if (!users || users.length === 0) {
                $('#userTableBody').html('<tr><td colspan="6" class="text-center">No users found</td></tr>');
                return;
            }

            let tableHtml = '';
            users.forEach(function (user) {
                // Handle profile image path - remove any leading ../ or ./
                let profileImgPath = user.profile_img.replace(/^(\.\.\/|\.\/)/, '');

                tableHtml += `
        <tr>
            <td>
                <div class="d-flex align-items-center">
                    <img src="${profileImgPath}" class="img-circle mr-3" style="width: 40px; height: 40px;" 
                         alt="User Image" onerror="this.src='../dist/img/user-default.jpg';">
                    <div>
                        <strong>${user.full_name}</strong><br>
                        <small class="text-muted">${user.email_address}</small>
                    </div>
                </div>
            </td>
            <td>${user.username}</td>
            <td>${user.email_address}</td>
            <td>${user.role}</td>
            <td>
                <span class="badge ${user.status === 'Active' ? 'badge-success' : 'badge-danger'}">
                    ${user.status}
                </span>
            </td>
            <td>
                <div class="action-buttons text-center">
                    <a href="view-user.php?id=${user.user_id}" class="btn btn-info btn-sm view-user" data-toggle="tooltip" title="View User">
                        <i class="fas fa-eye"></i>
                    </a>
                    <a href="edit-user.php?id=${user.user_id}" class="btn btn-info btn-sm">
                        <i class="fas fa-edit"></i>
                    </a>
                    <button class="btn btn-warning btn-sm toggle-status" data-id="${user.user_id}" data-status="${user.status}">
                        <i class="fas ${user.status === 'Active' ? 'fa-ban' : 'fa-check'}"></i>
                    </button>
                    <button class="btn btn-danger btn-sm delete-user" data-id="${user.user_id}">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
            </td>
        </tr>`;
            });
            $('#userTableBody').html(tableHtml);
        }

        // Function to filter users
        function filterUsers() {
            const roleFilter = $('#roleFilter').val().toLowerCase();
            const statusFilter = $('#statusFilter').val().toLowerCase();
            const searchText = $('#searchInput').val().toLowerCase();

            $.ajax({
                url: 'controllers/user_controller.php',
                type: 'GET',
                dataType: 'json',
                data: {
                    action: 'get_users'  // Specify the action to avoid any ambiguity
                },
                success: function (response) {
                    if (response.success) {
                        let filteredUsers = response.users;

                        // Apply filters
                        if (roleFilter) {
                            filteredUsers = filteredUsers.filter(user => user.role.toLowerCase() === roleFilter);
                        }

                        if (statusFilter) {
                            filteredUsers = filteredUsers.filter(user => user.status.toLowerCase() === statusFilter);
                        }

                        if (searchText) {
                            filteredUsers = filteredUsers.filter(user =>
                                user.full_name.toLowerCase().includes(searchText) ||
                                user.username.toLowerCase().includes(searchText) ||
                                user.email_address.toLowerCase().includes(searchText)
                            );
                        }

                        displayUsers(filteredUsers);
                    } else {
                        console.error('Error loading users:', response.message);
                        $('#userTableBody').html('<tr><td colspan="6" class="text-center text-danger">Error: ' + response.message + '</td></tr>');
                    }
                },
                error: function (xhr, status, error) {
                    console.error('AJAX Error:', xhr.responseText);
                    $('#userTableBody').html(`
                        <tr>
                            <td colspan="6" class="text-center text-danger">
                                <p><i class="fas fa-exclamation-triangle mr-2"></i>Failed to load users. Please try again.</p>
                                <button id="retryFilterUsers" class="btn btn-sm btn-outline-primary mt-2">
                                    <i class="fas fa-sync mr-1"></i> Retry
                                </button>
                            </td>
                        </tr>
                    `);

                    // Add event listener for retry button
                    $('#retryFilterUsers').on('click', function () {
                        filterUsers();
                    });
                }
            });
        }

        // Function to show toast notifications
        function showToast(type, message) {
            const toast = $(`
                <div class="toast" role="alert" aria-live="assertive" aria-atomic="true">
                    <div class="toast-header">
                        <strong class="mr-auto">${type === 'success' ? 'Success' : 'Error'}</strong>
                        <button type="button" class="ml-2 mb-1 close" data-dismiss="toast" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="toast-body">
                        ${message}
                    </div>
                </div>
            `);

            $('#toastContainer').append(toast);
            toast.toast({ delay: 3000 });
            toast.toast('show');
        }
    });
</script>