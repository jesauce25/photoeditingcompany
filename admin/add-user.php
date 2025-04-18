<?php
// Include role check
require_once('includes/check_admin_role.php');

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
                            <i class="fas fa-user-plus mr-2"></i>
                            Add User
                        </h1>
                    </div>
                    <div class="col-sm-6">
                        <ol class="breadcrumb float-sm-right">
                            <li class="breadcrumb-item"><a href="home.php">Home</a></li>
                            <li class="breadcrumb-item active">Add User</li>
                        </ol>
                    </div>
                </div>
            </div>
        </section>

        <!-- Main content -->
        <section class="content">
            <div class="container-fluid">
                <div class="row">
                    <div class="col-md-12">
                        <div class="card">
                            <div class="card-header">
                                <h3 class="card-title">Staff Registration Form</h3>
                            </div>
                            <div class="card-body">
                                <?php
                                // Display session messages
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

                                if (isset($_SESSION['registration_error'])) {
                                    echo '<div class="alert alert-danger alert-dismissible fade show" role="alert">
                                            <i class="fas fa-exclamation-circle mr-2"></i> ' . $_SESSION['registration_message'] . '
                                            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                                <span aria-hidden="true">&times;</span>
                                            </button>
                                          </div>';
                                    // Clear the session variables
                                    unset($_SESSION['registration_error']);
                                    unset($_SESSION['registration_message']);
                                }
                                ?>
                                <form id="registerStaffForm" method="post" action="process_registration.php"
                                    enctype="multipart/form-data">
                                    <div class="row">
                                        <!-- Personal Information -->
                                        <div class="col-md-6">
                                            <div class="card">
                                                <div class="card-header">
                                                    <h4 class="card-title">Personal Information</h4>
                                                </div>
                                                <div class="card-body">
                                                    <div class="form-group">
                                                        <label for="firstName">First Name</label>
                                                        <input type="text" class="form-control" id="firstName"
                                                            name="firstName" required>
                                                    </div>
                                                    <div class="form-group">
                                                        <label for="midName">Middle Name</label>
                                                        <input type="text" class="form-control" id="midName"
                                                            name="midName">
                                                    </div>
                                                    <div class="form-group">
                                                        <label for="lastName">Last Name</label>
                                                        <input type="text" class="form-control" id="lastName"
                                                            name="lastName" required>
                                                    </div>
                                                    <div class="form-group">
                                                        <label for="birthDate">Birth Date</label>
                                                        <input type="date" class="form-control" id="birthDate"
                                                            name="birthDate" required>
                                                    </div>
                                                    <div class="form-group">
                                                        <label for="address">Address</label>
                                                        <input type="text" class="form-control" id="address"
                                                            name="address" required>
                                                    </div>
                                                    <div class="form-group">
                                                        <label for="contactNum">Contact Number</label>
                                                        <input type="text" class="form-control" id="contactNum"
                                                            name="contactNum" required>
                                                    </div>
                                                    <div class="form-group">
                                                        <label for="emailAddress">Email Address</label>
                                                        <input type="email" class="form-control" id="emailAddress"
                                                            name="emailAddress" required>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Account Information -->
                                        <div class="col-md-6">
                                            <div class="card">
                                                <div class="card-header">
                                                    <h4 class="card-title">Account Information</h4>
                                                </div>
                                                <div class="card-body">
                                                    <div class="form-group">
                                                        <label for="username">Username</label>
                                                        <input type="text" class="form-control" id="username"
                                                            name="username" required>
                                                    </div>
                                                    <div class="form-group">
                                                        <label for="password">Password</label>
                                                        <input type="password" class="form-control" id="password"
                                                            name="password" required>
                                                    </div>
                                                    <div class="form-group">
                                                        <label for="confirmPassword">Confirm Password</label>
                                                        <input type="password" class="form-control" id="confirmPassword"
                                                            name="confirmPassword" required>
                                                    </div>
                                                    <div class="form-group">
                                                        <label for="role">Role</label>
                                                        <select class="form-control" id="role" name="role" required>
                                                            <option value="">Select Role</option>
                                                            <option value="Admin">Admin</option>
                                                            <option value="Project Manager">Project Manager</option>
                                                            <option value="Graphic Artist">Graphic Artist</option>
                                                        </select>
                                                    </div>
                                                    <div class="form-group">
                                                        <label for="profileImg">Profile Image</label>
                                                        <div class="custom-file">
                                                            <input type="file" class="custom-file-input" id="profileImg"
                                                                name="profileImg" accept="image/*">
                                                            <label class="custom-file-label" for="profileImg">Choose
                                                                file</label>
                                                        </div>
                                                    </div>
                                                    <div class="mt-3" id="imagePreview" style="display: none;">
                                                        <div class="position-relative">
                                                            <img src="" alt="Profile Preview" class="img-thumbnail"
                                                                style="max-width: 200px; max-height: 200px;">
                                                            <button type="button"
                                                                class="btn btn-sm btn-danger position-absolute"
                                                                style="top: 0; right: 0;" id="removeImage">
                                                                <i class="fas fa-times"></i>
                                                            </button>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Form Buttons -->
                                    <div class="row mt-4">
                                        <div class="col-12">
                                            <button type="submit" class="btn btn-primary float-right ml-2">
                                                <i class="fas fa-save"></i> Register Staff
                                            </button>
                                            <button type="reset" class="btn btn-secondary float-right">
                                                <i class="fas fa-undo"></i> Reset
                                            </button>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        // Add alert container if it doesn't exist
        if (!document.getElementById('alertContainer')) {
            const alertContainer = document.createElement('div');
            alertContainer.id = 'alertContainer';
            document.querySelector('.card-body').prepend(alertContainer);
        }

        // Add debug info container
        if (!document.getElementById('debugInfo')) {
            const debugInfo = document.createElement('div');
            debugInfo.id = 'debugInfo';
            debugInfo.style.display = 'none';
            debugInfo.className = 'mt-3 p-3 border rounded bg-light';
            document.querySelector('.card-body').appendChild(debugInfo);
        }

        // Toggle debug info button
        const toggleDebugBtn = document.createElement('button');
        toggleDebugBtn.type = 'button';
        toggleDebugBtn.className = 'btn btn-sm btn-info float-right';
        toggleDebugBtn.innerHTML = '<i class="fas fa-bug"></i> Debug Info';
        toggleDebugBtn.addEventListener('click', function () {
            const debugInfo = document.getElementById('debugInfo');
            debugInfo.style.display = debugInfo.style.display === 'none' ? 'block' : 'none';
        });
        document.querySelector('.card-header').appendChild(toggleDebugBtn);

        // Preview profile image when selected
        document.getElementById('profileImg').addEventListener('change', function () {
            const file = this.files[0];
            if (file) {
                // Check file size (max 5MB)
                if (file.size > 5 * 1024 * 1024) {
                    showAlert('error', 'File is too large. Maximum size is 5MB.');
                    this.value = ''; // Clear the file input
                    return;
                }

                // Check file type
                const fileType = file.type;
                if (!fileType.match('image.*')) {
                    showAlert('error', 'Please select an image file (jpg, jpeg, png, gif).');
                    this.value = ''; // Clear the file input
                    return;
                }

                console.log('Reading file:', file.name);
                const reader = new FileReader();
                reader.onload = function (e) {
                    console.log('File loaded successfully');
                    document.getElementById('imagePreview').style.display = 'block';
                    document.querySelector('#imagePreview img').src = e.target.result;
                    document.querySelector('.custom-file-label').textContent = file.name;
                };
                reader.onerror = function (e) {
                    console.error('Error reading file:', e);
                    showAlert('error', 'Error reading the file. Please try again.');
                    this.value = ''; // Clear the file input
                };
                reader.readAsDataURL(file);
            }
        });

        // Remove selected image
        document.getElementById('removeImage').addEventListener('click', function (e) {
            e.preventDefault(); // Prevent form submission
            console.log('Removing image');
            document.getElementById('profileImg').value = '';
            document.getElementById('imagePreview').style.display = 'none';
            document.querySelector('#imagePreview img').src = '';
            document.querySelector('.custom-file-label').textContent = 'Choose file';
        });

        // Replace the form submission handler with a simpler version
        document.getElementById('registerStaffForm').addEventListener('submit', function (e) {
            // Allow the form to submit normally - don't prevent default
            // Just do basic validation

            let isValid = true;
            let errorMessage = '';

            // Required fields validation
            const requiredFields = [
                { id: 'firstName', name: 'First Name' },
                { id: 'lastName', name: 'Last Name' },
                { id: 'birthDate', name: 'Birth Date' },
                { id: 'address', name: 'Address' },
                { id: 'contactNum', name: 'Contact Number' },
                { id: 'emailAddress', name: 'Email Address' },
                { id: 'username', name: 'Username' },
                { id: 'password', name: 'Password' },
                { id: 'confirmPassword', name: 'Confirm Password' },
                { id: 'role', name: 'Role' }
            ];

            // Check each required field
            requiredFields.forEach(field => {
                const fieldElement = document.getElementById(field.id);
                if (!fieldElement.value.trim()) {
                    isValid = false;
                    errorMessage += `${field.name} is required.\n`;
                    fieldElement.classList.add('is-invalid');
                } else {
                    fieldElement.classList.remove('is-invalid');
                }
            });

            // Password match validation
            const passwordField = document.getElementById('password');
            const confirmPasswordField = document.getElementById('confirmPassword');
            if (passwordField.value !== confirmPasswordField.value) {
                isValid = false;
                errorMessage += 'Passwords do not match.\n';
                passwordField.classList.add('is-invalid');
                confirmPasswordField.classList.add('is-invalid');
            }

            // If validation fails, prevent form submission and show error
            if (!isValid) {
                e.preventDefault(); // Prevent form submission
                alert(errorMessage); // Simple alert instead of custom showAlert
                return false;
            }

            // If validation passes, show loading message and submit
            const submitBtn = this.querySelector('button[type="submit"]');
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...';
            submitBtn.disabled = true;

            return true; // Allow form submission
        });

        // Function to show alert messages (simplified)
        function showAlert(type, message) {
            alert(message);
        }
    });
</script>

<?php include("includes/footer.php"); ?>