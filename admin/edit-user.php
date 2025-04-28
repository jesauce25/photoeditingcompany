<?php
// Start output buffering before anything else
ob_start();

// Include required files
require_once('includes/header.php');
require_once('controllers/user_controller.php');

// Initialize variables
$error_message = null;
$success_message = null;

// Check if user ID is provided
if (!isset($_GET['id'])) {
    // Store the message in session to display after redirect
    $_SESSION['flash_message'] = [
        'type' => 'error',
        'message' => 'User ID is required'
    ];
    // Use JavaScript redirect instead of header() since output has started
    echo "<script>window.location.href='user-list.php';</script>";
    exit();
}

$user_id = $_GET['id'];
$user = getUserById($user_id);

if (!$user) {
    // Store the message in session to display after redirect
    $_SESSION['flash_message'] = [
        'type' => 'error',
        'message' => 'User not found'
    ];
    // Use JavaScript redirect instead of header() since output has started
    echo "<script>window.location.href='user-list.php';</script>";
    exit();
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update') {
    $userData = array(
        'firstName' => $_POST['firstName'],
        'midName' => $_POST['midName'],
        'lastName' => $_POST['lastName'],
        'birthDate' => $_POST['birthDate'],
        'address' => $_POST['address'],
        'contactNum' => $_POST['contactNum'],
        'emailAddress' => $_POST['emailAddress'],
        'username' => $_POST['username'],
        'role' => $_POST['role'],
        'status' => $_POST['status']
    );

    // Add profile image if uploaded
    if (isset($_FILES['profileImg']) && $_FILES['profileImg']['size'] > 0) {
        $userData['profileImg'] = $_FILES['profileImg'];
    }

    $response = updateUser($user_id, $userData);
    if ($response['success']) {
        // Store success message in session
        $_SESSION['flash_message'] = [
            'type' => 'success',
            'message' => 'User updated successfully'
        ];
        // Use JavaScript for redirect
        echo "<script>window.location.href='user-list.php';</script>";
        exit();
    } else {
        $error_message = $response['message'];
    }
}
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
                            <i class="fas fa-user-edit mr-2"></i>
                            Edit User
                        </h1>
                    </div>
                    <div class="col-sm-6">
                        <ol class="breadcrumb float-sm-right">
                            <li class="breadcrumb-item"><a href="home.php">Home</a></li>
                            <li class="breadcrumb-item"><a href="user-list.php">User List</a></li>
                            <li class="breadcrumb-item active">Edit User</li>
                        </ol>
                    </div>
                </div>
            </div>
        </section>

        <!-- Main content -->
        <section class="content">
            <div class="container-fluid">
                <?php if ($error_message): ?>
                    <div class="alert alert-danger alert-dismissible">
                        <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
                        <h5><i class="icon fas fa-ban"></i> Error!</h5>
                        <?php echo $error_message; ?>
                    </div>
                <?php endif; ?>

                <?php if ($success_message): ?>
                    <div class="alert alert-success alert-dismissible">
                        <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
                        <h5><i class="icon fas fa-check"></i> Success!</h5>
                        <?php echo $success_message; ?>
                    </div>
                <?php endif; ?>

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
                                    <img class="profile-user-img img-fluid img-circle"
                                        src="<?php echo $profile_img_path; ?>" alt="User profile picture">
                                </div>
                                <h3 class="profile-username text-center">
                                    <?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?>
                                </h3>
                                <p class="text-muted text-center"><?php echo htmlspecialchars($user['role']); ?></p>

                                <ul class="list-group list-group-unbordered mb-3">
                                    <li class="list-group-item">
                                        <b>Status</b>
                                        <span
                                            class="float-right badge <?php echo $user['status'] === 'Active' ? 'badge-success' : 'badge-danger'; ?>">
                                            <?php echo htmlspecialchars($user['status']); ?>
                                        </span>
                                    </li>
                                    <li class="list-group-item">
                                        <b>Username</b>
                                        <span
                                            class="float-right"><?php echo htmlspecialchars($user['username']); ?></span>
                                    </li>
                                    <li class="list-group-item">
                                        <b>Joined</b>
                                        <span
                                            class="float-right"><?php echo date('M d, Y', strtotime($user['date_added'])); ?></span>
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>

                    <!-- User Details Form -->
                    <div class="col-md-8">
                        <div class="card">
                            <div class="card-header p-2">
                                <ul class="nav nav-pills">
                                    <li class="nav-item">
                                        <a class="nav-link active" href="#details" data-toggle="tab">
                                            <i class="fas fa-info-circle mr-1"></i> Edit Details
                                        </a>
                                    </li>
                                </ul>
                            </div>
                            <div class="card-body">
                                <div class="tab-content">
                                    <!-- Details Tab -->
                                    <div class="active tab-pane" id="details">
                                        <form id="editUserForm" method="POST" enctype="multipart/form-data">
                                            <input type="hidden" name="action" value="update">
                                            <h6 class="border-bottom pb-2 mb-3">Personal Information</h6>
                                            <div class="row">
                                                <div class="col-md-12 mb-3">
                                                    <div class="form-group">
                                                        <label for="profileImg">Profile Picture</label>
                                                        <input type="file" class="form-control" id="profileImg"
                                                            name="profileImg" accept="image/*">
                                                        <small class="form-text text-muted">Upload a new profile picture
                                                            (JPG, PNG, or GIF). Max size: 2MB</small>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="row">
                                                <div class="col-md-4">
                                                    <div class="form-group">
                                                        <label for="firstName">First Name</label>
                                                        <input type="text" class="form-control" id="firstName"
                                                            name="firstName"
                                                            value="<?php echo htmlspecialchars($user['first_name']); ?>"
                                                            required>
                                                    </div>
                                                </div>
                                                <div class="col-md-4">
                                                    <div class="form-group">
                                                        <label for="midName">Middle Name</label>
                                                        <input type="text" class="form-control" id="midName"
                                                            name="midName"
                                                            value="<?php echo htmlspecialchars($user['mid_name']); ?>">
                                                    </div>
                                                </div>
                                                <div class="col-md-4">
                                                    <div class="form-group">
                                                        <label for="lastName">Last Name</label>
                                                        <input type="text" class="form-control" id="lastName"
                                                            name="lastName"
                                                            value="<?php echo htmlspecialchars($user['last_name']); ?>"
                                                            required>
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="row">
                                                <div class="col-md-6">
                                                    <div class="form-group">
                                                        <label for="birthDate">Birth Date</label>
                                                        <input type="date" class="form-control" id="birthDate"
                                                            name="birthDate" value="<?php echo $user['birth_date']; ?>"
                                                            required>
                                                    </div>
                                                </div>
                                                <div class="col-md-6">
                                                    <div class="form-group">
                                                        <label for="contactNum">Contact Number</label>
                                                        <input type="text" class="form-control" id="contactNum"
                                                            name="contactNum"
                                                            value="<?php echo htmlspecialchars($user['contact_num']); ?>"
                                                            required>
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="form-group">
                                                <label for="address">Address</label>
                                                <textarea class="form-control" id="address" name="address" rows="2"
                                                    required><?php echo htmlspecialchars($user['address']); ?></textarea>
                                            </div>

                                            <h6 class="border-bottom pb-2 mb-3 mt-4">Account Information</h6>
                                            <div class="row">
                                                <div class="col-md-6">
                                                    <div class="form-group">
                                                        <label for="emailAddress">Email Address</label>
                                                        <input type="email" class="form-control" id="emailAddress"
                                                            name="emailAddress"
                                                            value="<?php echo htmlspecialchars($user['email_address']); ?>"
                                                            required>
                                                    </div>
                                                </div>
                                                <div class="col-md-6">
                                                    <div class="form-group">
                                                        <label for="username">Username</label>
                                                        <input type="text" class="form-control" id="username"
                                                            name="username"
                                                            value="<?php echo htmlspecialchars($user['username']); ?>"
                                                            required>
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="row">
                                                <div class="col-md-6">
                                                    <div class="form-group">
                                                        <label for="role">Role</label>
                                                        <select class="form-control" id="role" name="role" required>
                                                            <option value="Admin" <?php echo $user['role'] === 'Admin' ? 'selected' : ''; ?>>Admin</option>
                                                            <option value="Project Manager" <?php echo $user['role'] === 'Project Manager' ? 'selected' : ''; ?>>
                                                                Project Manager</option>
                                                            <option value="Graphic Artist" <?php echo $user['role'] === 'Graphic Artist' ? 'selected' : ''; ?>>
                                                                Graphic Artist</option>
                                                        </select>
                                                    </div>
                                                </div>
                                                <div class="col-md-6">
                                                    <div class="form-group">
                                                        <label for="status">Status</label>
                                                        <select class="form-control" id="status" name="status" required>
                                                            <option value="Active" <?php echo $user['status'] === 'Active' ? 'selected' : ''; ?>>Active</option>
                                                            <option value="Blocked" <?php echo $user['status'] === 'Blocked' ? 'selected' : ''; ?>>
                                                                Blocked</option>
                                                        </select>
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="row mt-4">
                                                <div class="col-12">
                                                    <button type="submit" class="btn btn-primary">
                                                        <i class="fas fa-save mr-1"></i> Save Changes
                                                    </button>
                                                    <a href="user-list.php" class="btn btn-secondary">
                                                        <i class="fas fa-times mr-1"></i> Cancel
                                                    </a>
                                                </div>
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


<!-- Toast Container -->
<div id="toastContainer" class="toast-container position-fixed bottom-0 right-0 p-3"></div>

<!-- Add this script at the bottom of the file -->
<script>
    $(document).ready(function () {
        // Let the form submit normally - no AJAX
        // This ensures the form is handled by PHP directly

        function showToast(type, message) {
            const toast = $(`
            <div class="toast" role="alert" aria-live="assertive" aria-atomic="true">
                <div class="toast-header ${type === 'success' ? 'bg-success text-white' : 'bg-danger text-white'}">
                    <i class="fas fa-${type === 'success' ? 'check-circle' : 'exclamation-circle'} mr-2"></i>
                    <strong class="mr-auto">${type === 'success' ? 'Success' : 'Error'}</strong>
                    <button type="button" class="ml-2 mb-1 close text-white" data-dismiss="toast" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="toast-body">
                    ${message}
                </div>
            </div>
        `);

            $('#toastContainer').append(toast);
            toast.toast({ delay: 3000 }).toast('show');

            toast.on('hidden.bs.toast', function () {
                toast.remove();
            });
        }
    });
</script>

<?php
// Include footer after all content is output
include("includes/footer.php");

// Flush output buffer at the very end
ob_end_flush();
?>