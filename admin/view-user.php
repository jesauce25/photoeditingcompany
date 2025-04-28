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
                        <!-- User Profile Image -->
                        <div class="col-md-4">
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
                                        <img class="profile-user-img img-fluid img-circle" src="<?php echo $profile_img_path; ?>"
                                            alt="User profile picture">
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

                        <!-- User Details -->
                        <div class="col-md-8">
                            <div class="card">
                                <div class="card-header p-2">
                                    <ul class="nav nav-pills">
                                        <li class="nav-item">
                                            <a class="nav-link active" href="#details" data-toggle="tab">
                                                <i class="fas fa-info-circle mr-1"></i> User Details
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
                                    </div>
                                </div>
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
<?php include("includes/footer.php"); ?>

<!-- Page specific script -->
<script>
    $(document).ready(function () {
        // Get user ID from URL
        const urlParams = new URLSearchParams(window.location.search);
        const userId = urlParams.get('id');

        // If no user ID is provided, show error and redirect
        if (!userId) {
            showAlert('error', 'No user ID provided');
            setTimeout(function () {
                window.location.href = 'user-list.php';
            }, 2000);
            return;
        }

        // Set edit user link
        $('#editUserLink').attr('href', 'edit-user.php?id=' + userId);

        // Load user details
        loadUserDetails(userId);

        // Function to load user details from the server
        function loadUserDetails(userId) {
            $.ajax({
                url: 'controllers/user_controller.php',
                type: 'GET',
                data: { user_id: userId },
                dataType: 'json',
                success: function (response) {
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
                    } else {
                        // Show user not found message
                        $('#userNotFound').show();
                        showAlert('error', response.message || 'User not found');
                    }
                },
                error: function (xhr, status, error) {
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
            setTimeout(function () {
                $('.alert').alert('close');
            }, 5000);
        }
    });
</script>