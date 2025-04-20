<?php
include("includes/header.php");
require_once 'controllers/db_connection_passthrough.php';

// Get the current user ID from session
$user_id = $_SESSION['user_id'] ?? 0;

// Initialize user data array
$user = [
    'first_name' => '',
    'last_name' => '',
    'email' => '',
    'phone' => '',
    'role' => '',
    'bio' => '',
    'profile_img' => ''
];

// Fetch user data from database
if ($user_id > 0) {
    // Query to fetch user data from both tables
    $query = "SELECT u.*, a.* 
              FROM tbl_users u
              LEFT JOIN tbl_accounts a ON u.user_id = a.user_id
              WHERE u.user_id = ?";
    
    $stmt = $conn->prepare($query);
    if ($stmt) {
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $user = $result->fetch_assoc();
            
            // Log that we found user data
            error_log("User data fetched for user ID: $user_id");
        } else {
            error_log("No user data found for user ID: $user_id");
        }
    } else {
        error_log("Error preparing statement: " . $conn->error);
    }
}

// Handle profile form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['updateProfile'])) {
    $first_name = $_POST['firstName'] ?? '';
    $last_name = $_POST['lastName'] ?? '';
    $email = $_POST['email'] ?? '';
    $phone = $_POST['phone'] ?? '';
    $department = $_POST['department'] ?? '';
    $bio = $_POST['bio'] ?? '';
    
    // Update user data in database
    $updateQuery = "UPDATE tbl_users SET 
                    first_name = ?,
                    last_name = ?,
                    email = ?,
                    contact_num = ?
                    WHERE user_id = ?";
                    
    $updateStmt = $conn->prepare($updateQuery);
    if ($updateStmt) {
        $updateStmt->bind_param("ssssi", $first_name, $last_name, $email, $phone, $user_id);
        if ($updateStmt->execute()) {
            // Update account bio if it exists
            $bioQuery = "UPDATE tbl_accounts SET bio = ? WHERE user_id = ?";
            $bioStmt = $conn->prepare($bioQuery);
            if ($bioStmt) {
                $bioStmt->bind_param("si", $bio, $user_id);
                $bioStmt->execute();
            }
            
            // Set success message
            $_SESSION['success_message'] = "Profile updated successfully!";
            
            // Refresh user data
            header("Location: profile-settings.php");
            exit;
        } else {
            $_SESSION['error_message'] = "Error updating profile: " . $updateStmt->error;
        }
    } else {
        $_SESSION['error_message'] = "Error preparing update: " . $conn->error;
    }
}

// Handle password change
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['changePassword'])) {
    $current_password = $_POST['currentPassword'] ?? '';
    $new_password = $_POST['newPassword'] ?? '';
    $confirm_password = $_POST['confirmPassword'] ?? '';
    
    // Validate passwords
    if ($new_password !== $confirm_password) {
        $_SESSION['error_message'] = "New passwords do not match.";
    } else {
        // Get current password hash from database
        $passwordQuery = "SELECT password FROM tbl_accounts WHERE user_id = ?";
        $passwordStmt = $conn->prepare($passwordQuery);
        if ($passwordStmt) {
            $passwordStmt->bind_param("i", $user_id);
            $passwordStmt->execute();
            $passwordResult = $passwordStmt->get_result();
            
            if ($passwordResult->num_rows > 0) {
                $passwordRow = $passwordResult->fetch_assoc();
                $stored_hash = $passwordRow['password'];
                
                // Verify current password
                if (password_verify($current_password, $stored_hash)) {
                    // Hash new password
                    $new_hash = password_hash($new_password, PASSWORD_DEFAULT);
                    
                    // Update password in database
                    $updatePasswordQuery = "UPDATE tbl_accounts SET password = ? WHERE user_id = ?";
                    $updatePasswordStmt = $conn->prepare($updatePasswordQuery);
                    
                    if ($updatePasswordStmt) {
                        $updatePasswordStmt->bind_param("si", $new_hash, $user_id);
                        
                        if ($updatePasswordStmt->execute()) {
                            $_SESSION['success_message'] = "Password updated successfully!";
                        } else {
                            $_SESSION['error_message'] = "Error updating password: " . $updatePasswordStmt->error;
                        }
                    } else {
                        $_SESSION['error_message'] = "Error preparing password update: " . $conn->error;
                    }
                } else {
                    $_SESSION['error_message'] = "Current password is incorrect.";
                }
            } else {
                $_SESSION['error_message'] = "User account not found.";
            }
        } else {
            $_SESSION['error_message'] = "Error preparing password query: " . $conn->error;
        }
    }
    
    // Redirect to refresh the page
    header("Location: profile-settings.php");
    exit;
}

// Get profile image path
$profile_img_path = '../dist/img/user-default.jpg';
if (!empty($user['profile_img'])) {
    // Check multiple possible locations
    $possible_locations = [
        '../assets/img/profile/' . $user['profile_img'],
        '../uploads/profile_images/' . $user['profile_img'],
        '../profiles/' . $user['profile_img']
    ];
    
    foreach ($possible_locations as $location) {
        if (file_exists($location)) {
            $profile_img_path = $location;
            break;
        }
    }
    
    // Direct path check
    if (strpos($user['profile_img'], '/') === 0 || 
        strpos($user['profile_img'], 'assets/') === 0 ||
        strpos($user['profile_img'], 'uploads/') === 0) {
        $direct_path = '../' . ltrim($user['profile_img'], '/');
        if (file_exists($direct_path)) {
            $profile_img_path = $direct_path;
        }
    }
}
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
                        <h1><i class="fas fa-user-cog mr-2"></i>Profile Settings</h1>
                    </div>
                    <div class="col-sm-6">
                        <ol class="breadcrumb float-sm-right">
                            <li class="breadcrumb-item"><a href="#">Home</a></li>
                            <li class="breadcrumb-item active">Profile Settings</li>
                        </ol>
                    </div>
                </div>
            </div>
        </section>

        <!-- Main content -->
        <section class="content">
            <div class="container-fluid">
                <!-- Display messages -->
                <?php if (isset($_SESSION['success_message'])): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="fas fa-check-circle mr-2"></i> <?php echo $_SESSION['success_message']; ?>
                    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <?php unset($_SESSION['success_message']); ?>
                <?php endif; ?>

                <?php if (isset($_SESSION['error_message'])): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="fas fa-exclamation-circle mr-2"></i> <?php echo $_SESSION['error_message']; ?>
                    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <?php unset($_SESSION['error_message']); ?>
                <?php endif; ?>

                <div class="row">
                    <!-- Profile Information -->
                    <div class="col-md-4">
                        <!-- Profile Card -->
                        <div class="card card-primary card-outline">
                            <div class="card-body box-profile">
                                <div class="text-center position-relative">
                                    <img class="profile-user-img img-fluid img-circle"
                                        src="<?php echo $profile_img_path; ?>" alt="User profile picture">
                                    <button type="button" class="btn btn-sm btn-primary position-absolute"
                                        style="bottom: 0; right: 35%; border-radius: 50%; width: 32px; height: 32px; padding: 0;"
                                        data-toggle="modal" data-target="#changeProfilePicModal">
                                        <i class="fas fa-camera"></i>
                                    </button>
                                </div>
                                <h3 class="profile-username text-center">
                                    <?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?>
                                </h3>
                                <p class="text-muted text-center">
                                    <?php echo htmlspecialchars($user['role'] ?? 'User'); ?>
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Settings Tabs -->
                    <div class="col-md-8">
                        <div class="card">
                            <div class="card-header p-2">
                                <ul class="nav nav-pills">
                                    <li class="nav-item">
                                        <a class="nav-link active" href="#settings" data-toggle="tab">
                                            <i class="fas fa-cog mr-1"></i> Settings
                                        </a>
                                    </li>
                                    <li class="nav-item">
                                        <a class="nav-link" href="#security" data-toggle="tab">
                                            <i class="fas fa-shield-alt mr-1"></i> Security
                                        </a>
                                    </li>
                                </ul>
                            </div>
                            <div class="card-body">
                                <div class="tab-content">
                                    <!-- Settings Tab -->
                                    <div class="active tab-pane" id="settings">
                                        <form class="form-horizontal" method="post" action="">
                                            <div class="form-group row">
                                                <label class="col-sm-2 col-form-label">Full Name</label>
                                                <div class="col-sm-5">
                                                    <input type="text" class="form-control" name="firstName" placeholder="First Name"
                                                        value="<?php echo htmlspecialchars($user['first_name'] ?? ''); ?>">
                                                </div>
                                                <div class="col-sm-5">
                                                    <input type="text" class="form-control" name="lastName" placeholder="Last Name"
                                                        value="<?php echo htmlspecialchars($user['last_name'] ?? ''); ?>">
                                                </div>
                                            </div>
                                            <div class="form-group row">
                                                <label class="col-sm-2 col-form-label">Email</label>
                                                <div class="col-sm-10">
                                                    <input type="email" class="form-control" name="email"
                                                        placeholder="Email" 
                                                        value="<?php echo htmlspecialchars($user['email'] ?? ''); ?>">
                                                </div>
                                            </div>
                                            <div class="form-group row">
                                                <label class="col-sm-2 col-form-label">Phone</label>
                                                <div class="col-sm-10">
                                                    <input type="tel" class="form-control" name="phone" 
                                                        placeholder="Phone number"
                                                        value="<?php echo htmlspecialchars($user['contact_num'] ?? ''); ?>">
                                                </div>
                                            </div>
                                            <div class="form-group row">
                                                <label class="col-sm-2 col-form-label">Department</label>
                                                <div class="col-sm-10">
                                                    <input type="text" class="form-control" name="department"
                                                        placeholder="Department" 
                                                        value="<?php echo htmlspecialchars($user['department'] ?? ''); ?>" readonly>
                                                    <small class="text-muted">Department is set by administrators</small>
                                                </div>
                                            </div>
                                            <div class="form-group row">
                                                <label class="col-sm-2 col-form-label">Bio</label>
                                                <div class="col-sm-10">
                                                    <textarea class="form-control" name="bio" rows="3"
                                                        placeholder="Tell something about yourself"><?php echo htmlspecialchars($user['bio'] ?? ''); ?></textarea>
                                                </div>
                                            </div>
                                            <div class="form-group row">
                                                <div class="offset-sm-2 col-sm-10">
                                                    <button type="submit" name="updateProfile" class="btn btn-primary">
                                                        <i class="fas fa-save mr-1"></i> Save Changes
                                                    </button>
                                                </div>
                                            </div>
                                        </form>
                                    </div>

                                    <!-- Security Tab -->
                                    <div class="tab-pane" id="security">
                                        <form method="post" action="">
                                            <div class="form-group">
                                                <label>Current Password</label>
                                                <input type="password" class="form-control" name="currentPassword"
                                                    placeholder="Enter current password" required>
                                            </div>
                                            <div class="form-group">
                                                <label>New Password</label>
                                                <input type="password" class="form-control" name="newPassword"
                                                    placeholder="Enter new password" required>
                                            </div>
                                            <div class="form-group">
                                                <label>Confirm New Password</label>
                                                <input type="password" class="form-control" name="confirmPassword"
                                                    placeholder="Confirm new password" required>
                                            </div>
                                            <div class="form-group">
                                                <button type="submit" name="changePassword" class="btn btn-primary">
                                                    <i class="fas fa-key mr-1"></i> Change Password
                                                </button>
                                            </div>
                                        </form>
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

<!-- Change Profile Picture Modal -->
<div class="modal fade" id="changeProfilePicModal" tabindex="-1" role="dialog" aria-labelledby="changeProfilePicModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="changeProfilePicModalLabel">Change Profile Picture</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form id="profilePicForm" action="controllers/update_profile_pic.php" method="post" enctype="multipart/form-data">
                    <div class="form-group">
                        <label for="profileImage">Select Image</label>
                        <div class="custom-file">
                            <input type="file" class="custom-file-input" id="profileImage" name="profileImage" accept="image/*" required>
                            <label class="custom-file-label" for="profileImage">Choose file</label>
                        </div>
                        <small class="form-text text-muted">Maximum file size: 2MB. Supported formats: JPG, JPEG, PNG.</small>
                    </div>
                    <div id="imagePreview" class="mt-3 text-center" style="display: none;">
                        <img src="" alt="Preview" class="img-fluid rounded-circle" style="max-width: 150px; max-height: 150px;">
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                <button type="submit" form="profilePicForm" class="btn btn-primary">Upload</button>
            </div>
        </div>
    </div>
</div>

<script>
// Preview profile image before upload
$(document).ready(function() {
    $('#profileImage').change(function() {
        const file = this.files[0];
        if (file) {
            const reader = new FileReader();
            
            reader.onload = function(e) {
                $('#imagePreview').show();
                $('#imagePreview img').attr('src', e.target.result);
                $('.custom-file-label').text(file.name);
            }
            
            reader.readAsDataURL(file);
        }
    });
});
</script>

<?php include("includes/footer.php"); ?>