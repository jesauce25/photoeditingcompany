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
                            <i class="fas fa-user-plus mr-2"></i>
                            Register Staff
                        </h1>
                    </div>
                    <div class="col-sm-6">
                        <ol class="breadcrumb float-sm-right">
                            <li class="breadcrumb-item"><a href="home.php">Home</a></li>
                            <li class="breadcrumb-item active">Register Staff</li>
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
                                <form id="registerStaffForm" method="post" enctype="multipart/form-data">
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
                                                        <input type="text" class="form-control" id="firstName" name="firstName" required>
                                                    </div>
                                                    <div class="form-group">
                                                        <label for="midName">Middle Name</label>
                                                        <input type="text" class="form-control" id="midName" name="midName">
                                                    </div>
                                                    <div class="form-group">
                                                        <label for="lastName">Last Name</label>
                                                        <input type="text" class="form-control" id="lastName" name="lastName" required>
                                                    </div>
                                                    <div class="form-group">
                                                        <label for="birthDate">Birth Date</label>
                                                        <input type="date" class="form-control" id="birthDate" name="birthDate" required>
                                                    </div>
                                                    <div class="form-group">
                                                        <label for="address">Address</label>
                                                        <input type="text" class="form-control" id="address" name="address" required>
                                                    </div>
                                                    <div class="form-group">
                                                        <label for="contactNum">Contact Number</label>
                                                        <input type="text" class="form-control" id="contactNum" name="contactNum" required>
                                                    </div>
                                                    <div class="form-group">
                                                        <label for="emailAddress">Email Address</label>
                                                        <input type="email" class="form-control" id="emailAddress" name="emailAddress" required>
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
                                                        <input type="text" class="form-control" id="username" name="username" required>
                                                    </div>
                                                    <div class="form-group">
                                                        <label for="password">Password</label>
                                                        <input type="password" class="form-control" id="password" name="password" required>
                                                    </div>
                                                    <div class="form-group">
                                                        <label for="confirmPassword">Confirm Password</label>
                                                        <input type="password" class="form-control" id="confirmPassword" name="confirmPassword" required>
                                                    </div>
                                                    <div class="form-group">
                                                        <label for="role">Role</label>
                                                        <select class="form-control" id="role" name="role" required>
                                                            <option value="">Select Role</option>
                                                            <option value="Project Manager">Project Manager</option>
                                                            <option value="Graphic Artist">Graphic Artist</option>
                                                        </select>
                                                    </div>
                                                    <div class="form-group">
                                                        <label for="profileImg">Profile Image</label>
                                                        <div class="custom-file">
                                                            <input type="file" class="custom-file-input" id="profileImg" name="profileImg" accept="image/*">
                                                            <label class="custom-file-label" for="profileImg">Choose file</label>
                                                        </div>
                                                    </div>
                                                    <div class="mt-3" id="imagePreview" style="display: none;">
                                                        <img src="" alt="Profile Preview" class="img-thumbnail" style="max-width: 200px;">
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
$(document).ready(function() {
    // Initialize custom file input
    bsCustomFileInput.init();

    // Image preview
    $('#profileImg').change(function() {
        const file = this.files[0];
        if (file) {
            const reader = new FileReader();
            reader.onload = function(e) {
                $('#imagePreview img').attr('src', e.target.result);
                $('#imagePreview').show();
            }
            reader.readAsDataURL(file);
        }
    });

    // Form validation and submission
    $('#registerStaffForm').submit(function(e) {
        e.preventDefault();

        // Password validation
        const password = $('#password').val();
        const confirmPassword = $('#confirmPassword').val();

        if (password !== confirmPassword) {
            alert('Passwords do not match!');
            return;
        }

        // Create FormData object
        const formData = new FormData(this);

        // Submit form using AJAX
        $.ajax({
            url: 'process_registration.php',
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                try {
                    const result = JSON.parse(response);
                    if (result.success) {
                        alert('Staff registered successfully!');
                        window.location.reload();
                    } else {
                        alert('Error: ' + result.message);
                    }
                } catch (e) {
                    alert('An error occurred while processing the registration.');
                }
            },
            error: function() {
                alert('An error occurred while submitting the form.');
            }
        });
    });
});
</script>

<?php include("includes/footer.php"); ?> 