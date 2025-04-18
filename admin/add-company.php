<?php
include("includes/header.php");

// Check for success or error messages in session
$successMessage = isset($_SESSION['success_message']) ? $_SESSION['success_message'] : '';
$errorMessage = isset($_SESSION['error_message']) ? $_SESSION['error_message'] : '';

// Clear messages from session
unset($_SESSION['success_message']);
unset($_SESSION['error_message']);
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
                        <h1><i class="fas fa-building mr-2"></i>Add New Company</h1>
                    </div>
                    <div class="col-sm-6">
                        <ol class="breadcrumb float-sm-right">
                            <li class="breadcrumb-item"><a href="#">Home</a></li>
                            <li class="breadcrumb-item">Company</li>
                            <li class="breadcrumb-item active">Add Company</li>
                        </ol>
                    </div>
                </div>
            </div><!-- /.container-fluid -->
        </section>

        <!-- Display success message if exists -->
        <?php if (!empty($successMessage)): ?>
            <div class="alert alert-success alert-dismissible fade show mx-3">
                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
                <h5><i class="icon fas fa-check"></i> Success!</h5>
                <?php echo $successMessage; ?>
            </div>
        <?php endif; ?>

        <!-- Display error message if exists -->
        <?php if (!empty($errorMessage)): ?>
            <div class="alert alert-danger alert-dismissible fade show mx-3">
                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
                <h5><i class="icon fas fa-ban"></i> Error!</h5>
                <?php echo $errorMessage; ?>
            </div>
        <?php endif; ?>

        <!-- Main content -->
        <section class="content">
            <div class="container-fluid">
                <div class="row">
                    <div class="col-md-8">
                        <div class="card card-primary card-outline">
                            <div class="card-header">
                                <h3 class="card-title">Company Information</h3>
                                <div class="card-tools">
                                    <button type="button" class="btn btn-tool" data-card-widget="collapse">
                                        <i class="fas fa-minus"></i>
                                    </button>
                                </div>
                            </div>
                            <div class="card-body">
                                <form action="process-add-company.php" method="post" id="companyForm"
                                    enctype="multipart/form-data">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="companyName">Company Name <span
                                                        class="text-danger">*</span></label>
                                                <div class="input-group">
                                                    <div class="input-group-prepend">
                                                        <span class="input-group-text"><i
                                                                class="fas fa-building"></i></span>
                                                    </div>
                                                    <input type="text" class="form-control" id="companyName"
                                                        name="companyName" placeholder="Enter company name" required>
                                                </div>
                                            </div>
                                            <div class="form-group">
                                                <label for="dateSignedUp">Date Signed Up <span
                                                        class="text-danger">*</span></label>
                                                <div class="input-group date" id="datepicker">
                                                    <div class="input-group-prepend">
                                                        <span class="input-group-text"><i
                                                                class="fas fa-calendar-alt"></i></span>
                                                    </div>
                                                    <input type="date" class="form-control" id="dateSignedUp"
                                                        name="dateSignedUp" required>
                                                </div>
                                            </div>
                                            <div class="form-group">
                                                <label for="address">Address <span class="text-danger">*</span></label>
                                                <div class="input-group">
                                                    <div class="input-group-prepend">
                                                        <span class="input-group-text"><i
                                                                class="fas fa-map-marker-alt"></i></span>
                                                    </div>
                                                    <textarea class="form-control" id="address" name="address" rows="3"
                                                        placeholder="Enter complete address" required></textarea>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="country">Country <span class="text-danger">*</span></label>
                                                <div class="input-group">
                                                    <div class="input-group-prepend">
                                                        <span class="input-group-text"><i
                                                                class="fas fa-globe"></i></span>
                                                    </div>
                                                    <select class="form-control select2" id="country" name="country"
                                                        required>
                                                        <option value="" selected disabled>Select a country</option>
                                                        <option value="USA">United States</option>
                                                        <option value="UK">United Kingdom</option>
                                                        <option value="Canada">Canada</option>
                                                        <option value="Australia">Australia</option>
                                                        <option value="Philippines">Philippines</option>
                                                        <!-- Add more countries as needed -->
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="form-group">
                                                <label for="email">Email <span class="text-danger">*</span></label>
                                                <div class="input-group">
                                                    <div class="input-group-prepend">
                                                        <span class="input-group-text"><i
                                                                class="fas fa-envelope"></i></span>
                                                    </div>
                                                    <input type="email" class="form-control" id="email" name="email"
                                                        placeholder="Enter company email" required>
                                                </div>
                                            </div>
                                            <div class="form-group">
                                                <label for="incharge">Person In Charge <span
                                                        class="text-danger">*</span></label>
                                                <div class="input-group">
                                                    <div class="input-group-prepend">
                                                        <span class="input-group-text"><i
                                                                class="fas fa-user-tie"></i></span>
                                                    </div>
                                                    <input type="text" class="form-control" id="incharge"
                                                        name="incharge" placeholder="Enter person in charge" required>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </form>
                            </div>
                            <div class="card-footer">
                                <button type="submit" form="companyForm" class="btn btn-primary float-right ml-2"
                                    name="save_company">
                                    <i class="fas fa-save mr-2"></i> Save Company
                                </button>
                                <button type="submit" form="companyForm" class="btn btn-success float-right"
                                    name="save_and_list">
                                    <i class="fas fa-list-alt mr-2"></i> Save and View List
                                </button>
                                <button type="reset" form="companyForm" class="btn btn-secondary">
                                    <i class="fas fa-undo mr-2"></i> Reset
                                </button>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card card-info card-outline">
                            <div class="card-header">
                                <h3 class="card-title"><i class="fas fa-image mr-2"></i>Company Logo</h3>
                            </div>
                            <div class="card-body text-center">
                                <div class="logo-preview mb-3">
                                    <div class="dropzone-wrapper">
                                        <div class="dropzone-desc">
                                            <i class="fas fa-cloud-upload-alt fa-2x"></i>
                                            <p>Drop your logo here or click to upload</p>
                                        </div>
                                        <img src="../dist/img/company-placeholder.png" alt="Company Logo"
                                            class="img-fluid preview-image" id="logoPreview">
                                        <input type="file" class="dropzone" id="companyLogo" name="companyLogo"
                                            form="companyForm" accept="image/*">
                                    </div>
                                </div>
                                <div class="logo-info">
                                    <div class="alert alert-info p-2">
                                        <small><i class="fas fa-info-circle mr-1"></i> Recommended size: 200x200px. Max
                                            file size: 2MB</small>
                                    </div>
                                    <div id="logoFileName" class="text-muted mb-2">No file selected</div>
                                    <button type="button" id="removeLogo" class="btn btn-sm btn-outline-danger"
                                        style="display:none;">
                                        <i class="fas fa-trash mr-1"></i> Remove Logo
                                    </button>
                                </div>
                            </div>
                        </div>

                        <div class="card card-success card-outline">
                            <div class="card-header">
                                <h3 class="card-title">Additional Information</h3>
                            </div>
                            <div class="card-body">
                                <div class="form-group">
                                    <label for="website">Website</label>
                                    <div class="input-group">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text"><i class="fas fa-globe"></i></span>
                                        </div>
                                        <input type="url" class="form-control" id="website" name="website"
                                            placeholder="https://example.com" form="companyForm">
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label for="phone">Phone Number</label>
                                    <div class="input-group">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text"><i class="fas fa-phone"></i></span>
                                        </div>
                                        <input type="text" class="form-control" id="phone" name="phone"
                                            placeholder="Enter phone number" form="companyForm">
                                    </div>
                                </div>
                                <div class="form-group mb-0">
                                    <label for="notes">Notes</label>
                                    <textarea class="form-control" id="notes" name="notes" rows="3"
                                        placeholder="Any additional notes about this company"
                                        form="companyForm"></textarea>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </div>
</div>

<?php include("includes/footer.php"); ?>

<script>
    $(document).ready(function () {
        // Logo preview
        $('#companyLogo').change(function () {
            const file = this.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function (e) {
                    $('#logoPreview').attr('src', e.target.result);
                    $('#logoFileName').text(file.name);
                    $('#removeLogo').show();
                }
                reader.readAsDataURL(file);
            }
        });

        // Remove logo button
        $('#removeLogo').click(function () {
            $('#companyLogo').val('');
            $('#logoPreview').attr('src', '../dist/img/company-placeholder.png');
            $('#logoFileName').text('No file selected');
            $(this).hide();
        });

        // Initialize Select2
        $('.select2').select2({
            theme: 'bootstrap4'
        });
    });
</script>