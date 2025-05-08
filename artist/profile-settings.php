<?php
include("includes/header.php");
require_once "../includes/db_connection.php";

// Initialize variables for alert messages
$alert_type = '';
$alert_message = '';

// Get current user ID from session
$user_id = $_SESSION['user_id'] ?? 0;

// Fetch current user data
$user_data = [];
$account_data = [];

try {
    // Fetch user data from tbl_users
    $user_query = "SELECT * FROM tbl_users WHERE user_id = ?";
    $user_stmt = $conn->prepare($user_query);
    $user_stmt->bind_param("i", $user_id);
    $user_stmt->execute();
    $user_result = $user_stmt->get_result();

    if ($user_result->num_rows > 0) {
        $user_data = $user_result->fetch_assoc();
    } else {
        throw new Exception("User not found");
    }

    // Fetch account data from tbl_accounts
    $account_query = "SELECT * FROM tbl_accounts WHERE user_id = ?";
    $account_stmt = $conn->prepare($account_query);
    $account_stmt->bind_param("i", $user_id);
    $account_stmt->execute();
    $account_result = $account_stmt->get_result();

    if ($account_result->num_rows > 0) {
        $account_data = $account_result->fetch_assoc();
    } else {
        throw new Exception("Account not found");
    }
} catch (Exception $e) {
    $alert_type = 'danger';
    $alert_message = 'Error: ' . $e->getMessage();
}

// Process form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $form_type = $_POST['form_type'] ?? '';

    try {
        switch ($form_type) {
            case 'personal_info':
                // Validate and process personal info
                $first_name = trim($_POST['first_name'] ?? '');
                $mid_name = trim($_POST['mid_name'] ?? '');
                $last_name = trim($_POST['last_name'] ?? '');
                $birth_date = $_POST['birth_date'] ?? '';

                // Basic validation
                if (empty($first_name) || empty($last_name)) {
                    throw new Exception("First name and last name are required");
                }

                if (!empty($birth_date)) {
                    $birth_date_obj = new DateTime($birth_date);
                    $today = new DateTime();
                    if ($birth_date_obj > $today) {
                        throw new Exception("Birth date cannot be in the future");
                    }
                    $birth_date = $birth_date_obj->format('Y-m-d H:i:s');
                }

                // Update database
                $update_query = "UPDATE tbl_users SET 
                                first_name = ?, 
                                mid_name = ?, 
                                last_name = ?, 
                                birth_date = ? 
                                WHERE user_id = ?";
                $update_stmt = $conn->prepare($update_query);
                $update_stmt->bind_param("ssssi", $first_name, $mid_name, $last_name, $birth_date, $user_id);

                if ($update_stmt->execute()) {
                    $_SESSION['profile_updated'] = true;
                    $_SESSION['profile_message'] = 'Personal information updated successfully';

                    // Update local data to reflect changes
                    $user_data['first_name'] = $first_name;
                    $user_data['mid_name'] = $mid_name;
                    $user_data['last_name'] = $last_name;
                    $user_data['birth_date'] = $birth_date;

                    // Redirect to refresh the page
                    header("Location: " . $_SERVER['PHP_SELF']);
                    exit;
                } else {
                    throw new Exception("Failed to update personal information: " . $conn->error);
                }
                break;

            case 'contact_info':
                // Validate and process contact info
                $address = trim($_POST['address'] ?? '');
                $contact_num = trim($_POST['contact_num'] ?? '');
                $email_address = trim($_POST['email_address'] ?? '');

                // Basic validation
                if (empty($email_address)) {
                    throw new Exception("Email address is required");
                }

                if (!filter_var($email_address, FILTER_VALIDATE_EMAIL)) {
                    throw new Exception("Invalid email address format");
                }

                // Update database
                $update_query = "UPDATE tbl_users SET 
                                address = ?, 
                                contact_num = ?, 
                                email_address = ? 
                                WHERE user_id = ?";
                $update_stmt = $conn->prepare($update_query);
                $update_stmt->bind_param("sssi", $address, $contact_num, $email_address, $user_id);

                if ($update_stmt->execute()) {
                    $_SESSION['profile_updated'] = true;
                    $_SESSION['profile_message'] = 'Contact information updated successfully';

                    // Update local data to reflect changes
                    $user_data['address'] = $address;
                    $user_data['contact_num'] = $contact_num;
                    $user_data['email_address'] = $email_address;
                } else {
                    throw new Exception("Failed to update contact information: " . $conn->error);
                }
                break;

            case 'account_settings':
                // Validate and process account info
                $username = trim($_POST['username'] ?? '');
                $password = $_POST['password'] ?? '';
                $role = $_POST['role'] ?? '';

                // Basic validation
                if (empty($username)) {
                    throw new Exception("Username is required");
                }

                // Update username and role
                $update_query = "UPDATE tbl_accounts SET username = ?";
                $params = array($username);
                $types = "s";

                // Only update password if it's not empty
                if (!empty($password)) {
                    // Hash the password for security
                    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                    $update_query .= ", password = ?";
                    $params[] = $hashed_password;
                    $types .= "s";
                }

                // Only update role if it's allowed
                if (!empty($role) && in_array($role, ['User', 'Admin', 'Graphic Artist'])) {
                    $update_query .= ", role = ?";
                    $params[] = $role;
                    $types .= "s";
                }

                $update_query .= " WHERE user_id = ?";
                $params[] = $user_id;
                $types .= "i";

                $update_stmt = $conn->prepare($update_query);
                $update_stmt->bind_param($types, ...$params);

                if ($update_stmt->execute()) {
                    $_SESSION['profile_updated'] = true;
                    $_SESSION['profile_message'] = 'Account settings updated successfully';

                    // Update local data to reflect changes
                    $account_data['username'] = $username;
                    if (!empty($role)) {
                        $account_data['role'] = $role;
                    }
                } else {
                    throw new Exception("Failed to update account settings: " . $conn->error);
                }
                break;

            case 'profile_image':
                // Process profile image upload
                if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] === UPLOAD_ERR_OK) {
                    $file = $_FILES['profile_image'];

                    // Validate file
                    $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
                    $max_size = 5 * 1024 * 1024; // 5MB

                    if (!in_array($file['type'], $allowed_types)) {
                        throw new Exception("Only JPEG, PNG and GIF images are allowed");
                    }

                    if ($file['size'] > $max_size) {
                        throw new Exception("File size must be less than 5MB");
                    }

                    // Create directory if it doesn't exist
                    $upload_dir = "../uploads/profiles/";
                    if (!is_dir($upload_dir)) {
                        mkdir($upload_dir, 0777, true);
                    }

                    // Generate a unique filename
                    $file_ext = pathinfo($file['name'], PATHINFO_EXTENSION);
                    $new_filename = 'profile_' . $user_id . '_' . time() . '.' . $file_ext;

                    // Move the file
                    if (move_uploaded_file($file['tmp_name'], $upload_dir . $new_filename)) {
                        // Update database
                        $update_query = "UPDATE tbl_users SET profile_img = ? WHERE user_id = ?";
                        $update_stmt = $conn->prepare($update_query);
                        $update_stmt->bind_param("si", $new_filename, $user_id);

                        if ($update_stmt->execute()) {
                            $_SESSION['profile_updated'] = true;
                            $_SESSION['profile_message'] = 'Profile image updated successfully';

                            // Update local data
                            $user_data['profile_img'] = $new_filename;
                        } else {
                            throw new Exception("Failed to update profile image in database: " . $conn->error);
                        }
                    } else {
                        throw new Exception("Failed to upload image");
                    }
                } else {
                    throw new Exception("No image was uploaded or there was an error");
                }
                break;

            default:
                throw new Exception("Invalid form submission");
        }
    } catch (Exception $e) {
        $alert_type = 'danger';
        $alert_message = 'Error: ' . $e->getMessage();
    }
}
?>

<?php include("includes/nav.php"); ?>

<?php /* Hide the old alert - now using notifications
if (!empty($alert_message)): ?>
<div class="alert alert-<?php echo $alert_type; ?> alert-dismissible fade show" role="alert">
<?php echo $alert_message; ?>
<button type="button" class="close" data-dismiss="alert" aria-label="Close">
  <span aria-hidden="true">&times;</span>
</button>
</div>
<?php endif; */ ?>

<div class="profile-settings-container">
    <div class="profile-grid">
        <div class="profile-column left-column">
            <div class="profile-card glass">
                <div class="text-center position-relative">
                    <?php
                    $profile_img = $user_data['profile_img'] ?? '';
                    $img_src = !empty($profile_img)
                        ? (file_exists("../uploads/profiles/" . $profile_img)
                            ? "../uploads/profiles/" . $profile_img
                            : "https://images.pexels.com/photos/614810/pexels-photo-614810.jpeg?auto=compress&cs=tinysrgb&w=600")
                        : "https://images.pexels.com/photos/614810/pexels-photo-614810.jpeg?auto=compress&cs=tinysrgb&w=600";
                    ?>
                    <img src="<?php echo $img_src; ?>" alt="Profile" class="profile-img floating" />
                    <form method="post" enctype="multipart/form-data" id="profileImageForm">
                        <input type="hidden" name="form_type" value="profile_image">
                        <input type="file" id="profile_image" name="profile_image" accept="image/*"
                            style="display: none;">
                        <button type="button" id="uploadProfileBtn" class="btn btn-sm btn-primary position-absolute"
                            style="bottom: 0; right: 35%; border-radius: 50%; width: 32px; height: 32px; padding: 0;">
                            <i class="fas fa-camera"></i>
                        </button>
                    </form>
                </div>
                <h3 class="profile-username text-center">
                    <?php
                    // Add safety check for first_name and last_name
                    $firstName = isset($user_data['first_name']) ? $user_data['first_name'] : 'User';
                    $lastName = isset($user_data['last_name']) ? $user_data['last_name'] : '';
                    echo htmlspecialchars($firstName . ' ' . $lastName);
                    ?>
                </h3>
                <p class="text-muted text-center"><?php echo htmlspecialchars($account_data['role'] ?? 'User'); ?></p>

                <!-- Skills section -->
                <!-- <div class="input-group">
                    <label for="skill_1">Skill 1</label>
                    <input type="text" id="skill_1" name="skills[]" placeholder="Enter your skill" value="Clip Pathing">
                </div>
                <div class="input-group">
                    <label for="skill_2">Skill 2</label>
                    <input type="text" id="skill_2" name="skills[]" placeholder="Enter your skill" value="Retouching">
                </div>
                <div class="input-group">
                    <label for="skill_3">Skill 3</label>
                    <input type="text" id="skill_3" name="skills[]" placeholder="Enter your skill" value="Final">
                </div> -->
            </div>
        </div>

        <div class="profile-column right-column">
            <div class="profile-card glass profile-settings">
                <!-- Tab Navigation with Spans -->
                <div class="profile-tabs">
                    <div class="tab-nav">
                        <span class="tab-link active" data-target="#personal-info">
                            <i class="fas fa-user mr-1"></i> Personal Info
                        </span>
                        <span class="tab-link" data-target="#contact-info">
                            <i class="fas fa-address-book mr-1"></i> Contact
                        </span>
                        <span class="tab-link" data-target="#account-settings">
                            <i class="fas fa-cog mr-1"></i> Account
                        </span>
                    </div>
                </div>

                <!-- Tab Content -->
                <div class="tab-content ">
                    <!-- Personal Information Tab -->
                    <div class="active tab-pane" id="personal-info">
                        <form class="profile-edit-form" method="post" action="">
                            <input type="hidden" name="form_type" value="personal_info">
                            <div class="profile-edit-grid">
                                <div class="container-fluid personal-info" style="grid-column: 1 / -1;">
                                    <div class="input-group ">
                                        <label for="first_name">First Name</label>
                                        <input type="text" id="first_name" name="first_name"
                                            placeholder="Enter first name"
                                            value="<?php echo htmlspecialchars($user_data['first_name'] ?? ''); ?>"
                                            required>
                                    </div>
                                    <div class=" input-group ">
                                        <label for="mid_name">Middle Name</label>
                                        <input type="text" id="mid_name" name="mid_name" placeholder="Enter middle name"
                                            value="<?php echo htmlspecialchars($user_data['mid_name'] ?? ''); ?>">
                                    </div>
                                    <div class=" input-group ">
                                        <label for="last_name">Last Name</label>
                                        <input type="text" id="last_name" name="last_name" placeholder="Enter last name"
                                            value="<?php echo htmlspecialchars($user_data['last_name'] ?? ''); ?>"
                                            required>
                                    </div>
                                    <div class=" input-group ">
                                        <label for="birth_date">Birth Date</label>
                                        <input type="date" id="birth_date" name="birth_date"
                                            value="<?php echo isset($user_data['birth_date']) ? date('Y-m-d', strtotime($user_data['birth_date'])) : ''; ?>">
                                    </div>
                                </div>
                            </div>
                            <div class=" form-actions">
                                <button type="submit" class="btn btn-primary btn-save">Save Personal Info</button>
                            </div>
                        </form>
                    </div>

                    <!-- Contact Information Tab -->
                    <div class="tab-pane" id="contact-info">
                        <form class="profile-edit-form" method="post" action="">
                            <input type="hidden" name="form_type" value="contact_info">
                            <div class="profile-edit-grid">
                                <div class="form-section contact-info" style="grid-column: 1 / -1;">
                                    <div class="input-group">
                                        <label for="address">Address</label>
                                        <input type="text" id="address" name="address" placeholder="Enter your address"
                                            value="<?php echo htmlspecialchars($user_data['address'] ?? ''); ?>">
                                    </div>
                                    <div class="input-group">
                                        <label for="contact_num">Contact Number</label>
                                        <input type="tel" id="contact_num" name="contact_num"
                                            placeholder="Enter contact number"
                                            value="<?php echo htmlspecialchars($user_data['contact_num'] ?? ''); ?>">
                                    </div>
                                    <div class="input-group">
                                        <label for="email_address">Email Address</label>
                                        <input type="email" id="email_address" name="email_address"
                                            placeholder="Enter email address"
                                            value="<?php echo htmlspecialchars($user_data['email_address'] ?? ''); ?>"
                                            required>
                                    </div>
                                </div>
                            </div>
                            <div class="form-actions">
                                <button type="submit" class="btn btn-primary btn-save">Save Contact Info</button>
                            </div>
                        </form>
                    </div>

                    <!-- Account Settings Tab -->
                    <div class="tab-pane" id="account-settings">
                        <form class="profile-edit-form" method="post" action="">
                            <input type="hidden" name="form_type" value="account_settings">
                            <div class="profile-edit-grid">
                                <div class="form-section account-info" style="grid-column: 1 / -1;">
                                    <div class="input-group">
                                        <label for="username">Username</label>
                                        <input type="text" id="username" name="username" placeholder="Choose username"
                                            value="<?php echo htmlspecialchars($account_data['username'] ?? ''); ?>"
                                            required>
                                    </div>
                                    <div class="input-group">
                                        <label for="password">Password</label>
                                        <input type="password" id="password" name="password"
                                            placeholder="Change password">
                                        <small class="form-text text-muted">Leave blank to keep current password</small>
                                    </div>

                                </div>
                            </div>
                            <div class="form-actions">
                                <button type="submit" class="btn btn-primary btn-save">Save Account Settings</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    /* Notification styling */
    #notificationContainer {
        position: fixed;
        bottom: 20px;
        right: 20px;
        z-index: 9999;
        max-width: 350px;
    }

    .notification {
        background-color: #fff;
        border-left: 4px solid #28a745;
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        border-radius: 4px;
        padding: 15px 20px;
        margin-bottom: 10px;
        animation: slideIn 0.3s ease-out;
    }

    @keyframes slideIn {
        from {
            transform: translateX(100%);
            opacity: 0;
        }

        to {
            transform: translateX(0);
            opacity: 1;
        }
    }

    .notification-success {
        border-left-color: #28a745;
    }

    .notification-error {
        border-left-color: #dc3545;
    }

    .notification-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 10px;
    }

    .notification-title {
        font-weight: bold;
        margin: 0;
        display: flex;
        align-items: center;
    }

    .notification-title i {
        margin-right: 8px;
    }

    .notification-body {
        color: #555;
    }

    .notification-close {
        background: none;
        border: none;
        cursor: pointer;
        font-size: 16px;
        color: #aaa;
    }

    .notification-close:hover {
        color: #555;
    }
</style>

<!-- Add this notification container at the end of the body before the footer -->
<div id="notificationContainer"></div>

<script>
    // Tab navigation functionality
    document.addEventListener('DOMContentLoaded', function () {
        const tabLinks = document.querySelectorAll('.tab-link');
        const tabPanes = document.querySelectorAll('.tab-pane');

        tabLinks.forEach(link => {
            link.addEventListener('click', function () {
                // Remove active class from all tabs
                tabLinks.forEach(t => t.classList.remove('active'));
                tabPanes.forEach(p => p.classList.remove('active'));

                // Add active class to clicked tab
                this.classList.add('active');

                // Show corresponding content
                const target = this.getAttribute('data-target');
                document.querySelector(target).classList.add('active');
            });
        });

        // Form validation
        const forms = document.querySelectorAll('.profile-edit-form');
        forms.forEach(form => {
            form.addEventListener('submit', function (e) {
                const requiredFields = form.querySelectorAll('[required]');
                let isValid = true;

                requiredFields.forEach(field => {
                    if (!field.value.trim()) {
                        isValid = false;
                        field.classList.add('is-invalid');
                    } else {
                        field.classList.remove('is-invalid');
                    }
                });

                if (!isValid) {
                    e.preventDefault();
                    alert('Please fill in all required fields');
                }
            });
        });
    });

    document.addEventListener('DOMContentLoaded', function () {
        // Profile image upload handling
        const uploadBtn = document.getElementById('uploadProfileBtn');
        const fileInput = document.getElementById('profile_image');
        const profileForm = document.getElementById('profileImageForm');

        if (uploadBtn && fileInput) {
            uploadBtn.addEventListener('click', function () {
                fileInput.click();
            });

            fileInput.addEventListener('change', function () {
                if (fileInput.files.length > 0) {
                    // Show loading indicator or disable button
                    uploadBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
                    uploadBtn.disabled = true;

                    // Submit the form
                    profileForm.submit();
                }
            });
        }
    });

    $(document).ready(function () {
        // Profile update success notification handling
        <?php if (isset($_SESSION['profile_updated']) && $_SESSION['profile_updated']): ?>
            showNotification('success', 'Profile Updated', '<?php echo $_SESSION['profile_message']; ?>');
            <?php unset($_SESSION['profile_updated']);
            unset($_SESSION['profile_message']); ?>
        <?php endif; ?>

        // Function to display notifications
        function showNotification(type, title, message) {
            const icon = type === 'success' ? 'check-circle' : 'exclamation-circle';
            const notification = `
                <div class="notification notification-${type}">
                    <div class="notification-header">
                        <h5 class="notification-title">
                            <i class="fas fa-${icon}"></i> ${title}
                        </h5>
                        <button type="button" class="notification-close">&times;</button>
                    </div>
                    <div class="notification-body">
                        ${message}
                    </div>
                </div>
            `;

            $('#notificationContainer').append(notification);

            // Auto remove after 5 seconds
            setTimeout(function () {
                $('.notification').first().fadeOut(300, function () {
                    $(this).remove();
                });
            }, 5000);

            // Close button functionality
            $('.notification-close').click(function () {
                $(this).closest('.notification').fadeOut(300, function () {
                    $(this).remove();
                });
            });
        }
    });
</script>

<?php include("includes/footer.php"); ?>
</body>

</html>