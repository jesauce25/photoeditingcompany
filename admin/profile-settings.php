<?php
// Start output buffering at the very beginning of the file
ob_start();

include("includes/header.php");
require_once 'controllers/db_connection_passthrough.php';

// Get the current user ID from session
$user_id = $_SESSION['user_id'] ?? 0;

// Initialize user data array
$user = [
    'first_name' => '',
    'mid_name' => '',
    'last_name' => '',
    'email_address' => '',
    'contact_num' => '',
    'birth_date' => '',
    'address' => '',
    'username' => '',
    'profile_img' => '',
    'password' => '' // Added for current password
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

            // Fetch password from database (separate query for security best practices)
            $passwordQuery = "SELECT password FROM tbl_accounts WHERE user_id = ?";
            $passwordStmt = $conn->prepare($passwordQuery);
            if ($passwordStmt) {
                $passwordStmt->bind_param("i", $user_id);
                $passwordStmt->execute();
                $passwordResult = $passwordStmt->get_result();

                if ($passwordResult->num_rows > 0) {
                    $passwordRow = $passwordResult->fetch_assoc();
                    $user['password'] = $passwordRow['password'];
                }
            }
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
    $mid_name = $_POST['middleName'] ?? '';
    $last_name = $_POST['lastName'] ?? '';
    $email = $_POST['email'] ?? '';
    $contact_num = $_POST['contactNumber'] ?? '';
    $birth_date = $_POST['birthDate'] ?? '';
    $address = $_POST['address'] ?? '';

    // Check if file was uploaded
    $profile_img = $user['profile_img']; // Default to current profile image

    if (isset($_FILES['profileImage']) && $_FILES['profileImage']['size'] > 0) {
        $target_dir = "../uploads/profile_pictures/";
        // Create directory if it doesn't exist
        if (!file_exists($target_dir)) {
            mkdir($target_dir, 0755, true);
        }

        $file_extension = pathinfo($_FILES['profileImage']['name'], PATHINFO_EXTENSION);
        $profile_img_filename = 'profile_' . uniqid() . '.' . $file_extension;
        $target_file = $target_dir . $profile_img_filename;

        if (move_uploaded_file($_FILES['profileImage']['tmp_name'], $target_file)) {
            $profile_img = 'uploads/profile_pictures/' . $profile_img_filename;
        } else {
            $_SESSION['error_message'] = "Error uploading profile image";
        }
    }

    // Update user data in database
    $updateQuery = "UPDATE tbl_users SET 
                    first_name = ?,
                    mid_name = ?,
                    last_name = ?,
                    email_address = ?,
                    contact_num = ?,
                    birth_date = ?,
                    address = ?,
                    profile_img = ?
                    WHERE user_id = ?";

    $updateStmt = $conn->prepare($updateQuery);
    if ($updateStmt) {
        $updateStmt->bind_param("ssssssssi", $first_name, $mid_name, $last_name, $email, $contact_num, $birth_date, $address, $profile_img, $user_id);
        if ($updateStmt->execute()) {
            // Set success message
            $_SESSION['success_message'] = "Profile updated successfully!";

            // Redirect - will use the buffer flushing at the end
            header("Location: profile-settings.php");
            ob_end_flush();
            exit;
        } else {
            $_SESSION['error_message'] = "Error updating profile: " . $updateStmt->error;
        }
    } else {
        $_SESSION['error_message'] = "Error preparing update: " . $conn->error;
    }
}

// Handle security form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['updateSecurity'])) {
    $username = $_POST['username'] ?? '';
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
                if (password_verify($current_password, $stored_hash) || $current_password === $stored_hash) {
                    // Hash new password
                    $new_hash = password_hash($new_password, PASSWORD_DEFAULT);

                    // Update username and password in database
                    $updateSecurityQuery = "UPDATE tbl_accounts SET 
                                           username = ?,
                                           password = ? 
                                           WHERE user_id = ?";
                    $updateSecurityStmt = $conn->prepare($updateSecurityQuery);

                    if ($updateSecurityStmt) {
                        $updateSecurityStmt->bind_param("ssi", $username, $new_hash, $user_id);

                        if ($updateSecurityStmt->execute()) {
                            $_SESSION['success_message'] = "Security information updated successfully!";
                        } else {
                            $_SESSION['error_message'] = "Error updating security information: " . $updateSecurityStmt->error;
                        }
                    } else {
                        $_SESSION['error_message'] = "Error preparing security update: " . $conn->error;
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

    // Redirect - will use the buffer flushing at the end
    header("Location: profile-settings.php");
    ob_end_flush();
    exit;
}

// Get profile image path
$profile_img_path = '../dist/img/user-default.jpg';
if (!empty($user['profile_img'])) {
    // Check multiple possible locations
    $possible_locations = [
        '../assets/img/profile/' . basename($user['profile_img']),
        '../uploads/profile_pictures/' . basename($user['profile_img']),
        '../profiles/' . basename($user['profile_img'])
    ];

    foreach ($possible_locations as $location) {
        if (file_exists($location)) {
            $profile_img_path = $location;
            break;
        }
    }

    // Direct path check - for absolute paths stored in database
    if (
        strpos($user['profile_img'], 'assets/') === 0 ||
        strpos($user['profile_img'], 'uploads/') === 0
    ) {
        $direct_path = '../' . $user['profile_img'];
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
                    <div class="col-md-3">
                        <!-- Profile Image -->
                        <div class="card card-primary card-outline">
                            <div class="card-body box-profile">
                                <div class="text-center">
                                    <img class="profile-user-img img-fluid img-circle"
                                        src="<?php echo $profile_img_path; ?>" alt="User profile picture">
                                </div>

                                <h3 class="profile-username text-center">
                                    <?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?>
                                </h3>

                                <p class="text-muted text-center"><?php echo htmlspecialchars($user['role'] ?? ''); ?>
                                </p>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-9">
                        <div class="card">
                            <div class="card-header p-2">
                                <ul class="nav nav-pills">
                                    <li class="nav-item">
                                        <a class="nav-link active text-white" href="#settings" data-toggle="tab">
                                            <i class="fas fa-user-edit mr-1"></i> Profile
                                        </a>
                                    </li>
                                    <li class="nav-item ">
                                        <a class="nav-link text-white" href="#security" data-toggle="tab">
                                            <i class="fas fa-shield-alt mr-1"></i> Security
                                        </a>
                                    </li>
                                </ul>
                            </div>

                            <div class="card-body">
                                <div class="tab-content">
                                    <!-- Profile Tab -->
                                    <div class="active tab-pane" id="settings">
                                        <form class="form-horizontal" method="post" action=""
                                            enctype="multipart/form-data">
                                            <div class="form-group row">
                                                <label class="col-sm-2 col-form-label">Profile Image</label>
                                                <div class="col-sm-10">
                                                    <input type="file" class="form-control" name="profileImage"
                                                        accept="image/*">
                                                    <small class="form-text text-muted">Upload a new profile image (JPG,
                                                        PNG, or GIF)</small>
                                                </div>
                                            </div>
                                            <div class="form-group row">
                                                <label class="col-sm-2 col-form-label">First Name</label>
                                                <div class="col-sm-10">
                                                    <input type="text" class="form-control" name="firstName"
                                                        placeholder="First Name"
                                                        value="<?php echo htmlspecialchars($user['first_name'] ?? ''); ?>">
                                                </div>
                                            </div>
                                            <div class="form-group row">
                                                <label class="col-sm-2 col-form-label">Middle Name</label>
                                                <div class="col-sm-10">
                                                    <input type="text" class="form-control" name="middleName"
                                                        placeholder="Middle Name"
                                                        value="<?php echo htmlspecialchars($user['mid_name'] ?? ''); ?>">
                                                </div>
                                            </div>
                                            <div class="form-group row">
                                                <label class="col-sm-2 col-form-label">Last Name</label>
                                                <div class="col-sm-10">
                                                    <input type="text" class="form-control" name="lastName"
                                                        placeholder="Last Name"
                                                        value="<?php echo htmlspecialchars($user['last_name'] ?? ''); ?>">
                                                </div>
                                            </div>
                                            <div class="form-group row">
                                                <label class="col-sm-2 col-form-label">Birth Date</label>
                                                <div class="col-sm-10">
                                                    <input type="date" class="form-control" name="birthDate"
                                                        value="<?php echo htmlspecialchars($user['birth_date'] ?? ''); ?>">
                                                </div>
                                            </div>
                                            <div class="form-group row">
                                                <label class="col-sm-2 col-form-label">Address</label>
                                                <div class="col-sm-10">
                                                    <input type="text" class="form-control" name="address"
                                                        placeholder="Complete Address"
                                                        value="<?php echo htmlspecialchars($user['address'] ?? ''); ?>">
                                                </div>
                                            </div>
                                            <div class="form-group row">
                                                <label class="col-sm-2 col-form-label">Contact Number</label>
                                                <div class="col-sm-10">
                                                    <input type="text" class="form-control" name="contactNumber"
                                                        placeholder="Contact Number"
                                                        value="<?php echo htmlspecialchars($user['contact_num'] ?? ''); ?>">
                                                </div>
                                            </div>
                                            <div class="form-group row">
                                                <label class="col-sm-2 col-form-label">Email</label>
                                                <div class="col-sm-10">
                                                    <input type="email" class="form-control" name="email"
                                                        placeholder="Email"
                                                        value="<?php echo htmlspecialchars($user['email_address'] ?? ''); ?>">
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
                                                <label>Username</label>
                                                <input type="text" class="form-control" name="username"
                                                    placeholder="Username"
                                                    value="<?php echo htmlspecialchars($user['username'] ?? ''); ?>">
                                            </div>
                                            <div class="form-group">
                                                <label>Current Password</label>
                                                <div class="input-group">
                                                    <input type="password" class="form-control" name="currentPassword"
                                                        id="currentPassword" placeholder="Current Password"
                                                        value="<?php echo !empty($user['password']) ? '••••••••' : ''; ?>"
                                                        required>
                                                    <div class="input-group-append">
                                                        <button class="btn btn-outline-secondary toggle-password"
                                                            type="button" data-target="currentPassword">
                                                            <i class="fa fa-eye"></i>
                                                        </button>
                                                    </div>
                                                </div>
                                                <small class="form-text text-muted">Enter your current password to
                                                    confirm your identity</small>
                                            </div>
                                            <div class="form-group">
                                                <label>New Password</label>
                                                <div class="input-group">
                                                    <input type="password" class="form-control" name="newPassword"
                                                        id="newPassword" placeholder="New Password" required>
                                                    <div class="input-group-append">
                                                        <button class="btn btn-outline-secondary toggle-password"
                                                            type="button" data-target="newPassword">
                                                            <i class="fa fa-eye"></i>
                                                        </button>
                                                    </div>
                                                </div>
                                                <small class="form-text text-muted">Choose a strong password with at
                                                    least 8 characters</small>
                                            </div>
                                            <div class="form-group">
                                                <label>Confirm New Password</label>
                                                <div class="input-group">
                                                    <input type="password" class="form-control" name="confirmPassword"
                                                        id="confirmPassword" placeholder="Confirm New Password"
                                                        required>
                                                    <div class="input-group-append">
                                                        <button class="btn btn-outline-secondary toggle-password"
                                                            type="button" data-target="confirmPassword">
                                                            <i class="fa fa-eye"></i>
                                                        </button>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="form-group">
                                                <button type="submit" name="updateSecurity" class="btn btn-primary">
                                                    <i class="fas fa-key mr-1"></i> Update Security Information
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

    <?php include("includes/footer.php"); ?>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Handle password toggle buttons
        document.querySelectorAll('.toggle-password').forEach(function(button) {
            button.addEventListener('click', function() {
                const targetId = this.getAttribute('data-target');
                const passwordInput = document.getElementById(targetId);

                // Skip toggling if the input is readonly or disabled
                if (passwordInput.readOnly || passwordInput.disabled) {
                    return;
                }

                // Toggle password visibility
                if (passwordInput.type === 'password') {
                    passwordInput.type = 'text';
                    this.querySelector('i').classList.remove('fa-eye');
                    this.querySelector('i').classList.add('fa-eye-slash');
                } else {
                    passwordInput.type = 'password';
                    this.querySelector('i').classList.remove('fa-eye-slash');
                    this.querySelector('i').classList.add('fa-eye');
                }
            });
        });

        // Special handling for the current password field
        const currentPasswordInput = document.getElementById('currentPassword');
        if (currentPasswordInput) {
            // Allow editing the current password field
            currentPasswordInput.readOnly = false;

            // Clear placeholder value when focused
            currentPasswordInput.addEventListener('focus', function() {
                if (this.value === '••••••••') {
                    this.value = '';
                }
            });
        }
    });
</script>
</body>

</html>