<?php
include("includes/header.php");
include("../includes/db_connection.php"); // Add direct database connection include

// Check if company ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    $_SESSION['error_message'] = 'Company ID is required.';
    header("Location: company-list.php");
    exit();
}

$companyId = intval($_GET['id']);

// Get company data
$query = "SELECT * FROM tbl_companies WHERE company_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $companyId);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows !== 1) {
    $_SESSION['error_message'] = 'Company not found.';
    header("Location: company-list.php");
    exit();
}

$company = $result->fetch_assoc();
$stmt->close();

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
                        <h1><i class="fas fa-edit mr-2"></i>Edit Company</h1>
                    </div>
                    <div class="col-sm-6">
                        <ol class="breadcrumb float-sm-right">
                            <li class="breadcrumb-item"><a href="#">Home</a></li>
                            <li class="breadcrumb-item"><a href="company-list.php">Company List</a></li>
                            <li class="breadcrumb-item active">Edit Company</li>
                        </ol>
                    </div>
                </div>
            </div><!-- /.container-fluid -->
        </section>

        <!-- Display success message if exists -->
        <?php if (!empty($successMessage)) : ?>
        <div class="alert alert-success alert-dismissible fade show mx-3">
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
            <h5><i class="icon fas fa-check"></i> Success!</h5>
            <?php echo $successMessage; ?>
        </div>
        <?php endif; ?>

        <!-- Display error message if exists -->
        <?php if (!empty($errorMessage)) : ?>
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
                                <form action="process-edit-company.php" method="post" id="companyForm"
                                    enctype="multipart/form-data">
                                    <input type="hidden" name="company_id" value="<?php echo $company['company_id']; ?>">
                                    <input type="hidden" name="current_logo_path" value="<?php echo $company['logo_path']; ?>">
                                    
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
                                                        name="companyName" placeholder="Enter company name"
                                                        value="<?php echo htmlspecialchars($company['company_name']); ?>" required>
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
                                                        name="dateSignedUp" 
                                                        value="<?php echo $company['date_signed_up']; ?>" required>
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
                                                        placeholder="Enter complete address" required><?php echo htmlspecialchars($company['address']); ?></textarea>
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
                                                        <option value="" disabled>Select a country</option>
                                                        <option value="USA" <?php echo ($company['country'] === 'USA') ? 'selected' : ''; ?>>United States</option>
                                                        <option value="UK" <?php echo ($company['country'] === 'UK') ? 'selected' : ''; ?>>United Kingdom</option>
                                                        <option value="Canada" <?php echo ($company['country'] === 'Canada') ? 'selected' : ''; ?>>Canada</option>
                                                        <option value="Australia" <?php echo ($company['country'] === 'Australia') ? 'selected' : ''; ?>>Australia</option>
                                                        <option value="Philippines" <?php echo ($company['country'] === 'Philippines') ? 'selected' : ''; ?>>Philippines</option>
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
                                                        placeholder="Enter company email" 
                                                        value="<?php echo htmlspecialchars($company['email']); ?>" required>
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
                                                        name="incharge" placeholder="Enter person in charge" 
                                                        value="<?php echo htmlspecialchars($company['person_in_charge']); ?>" required>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </form>
                            </div>
                            <div class="card-footer">
                                <button type="submit" form="companyForm" class="btn btn-primary float-right ml-2" name="save_company">
                                    <i class="fas fa-save mr-2"></i> Save Changes
                                </button>
                                <button type="submit" form="companyForm" class="btn btn-success float-right" name="save_and_list">
                                    <i class="fas fa-list-alt mr-2"></i> Save and View List
                                </button>
                                <a href="company-list.php" class="btn btn-secondary">
                                    <i class="fas fa-arrow-left mr-2"></i> Back to List
                                </a>
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
                                        <img src="<?php echo !empty($company['logo_path']) ? '../' . $company['logo_path'] : '../dist/img/company-placeholder.png'; ?>" 
                                             alt="Company Logo" class="img-fluid preview-image" id="logoPreview">
                                        <input type="file" class="dropzone" id="companyLogo" name="companyLogo"
                                            form="companyForm" accept="image/*">
                                    </div>
                                </div>
                                <div class="logo-info">
                                    <div class="alert alert-info p-2">
                                        <small><i class="fas fa-info-circle mr-1"></i> Recommended size: 200x200px. Max
                                            file size: 2MB</small>
                                    </div>
                                    <div id="logoFileName" class="text-muted mb-2">
                                        <?php echo !empty($company['logo_path']) ? basename($company['logo_path']) : 'No file selected'; ?>
                                    </div>
                                    <button type="button" id="removeLogo" class="btn btn-sm btn-outline-danger"
                                        <?php echo empty($company['logo_path']) ? 'style="display:none;"' : ''; ?>>
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
                                            placeholder="https://example.com" form="companyForm"
                                            value="<?php echo isset($company['website']) ? htmlspecialchars($company['website']) : ''; ?>">
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label for="phone">Phone Number</label>
                                    <div class="input-group">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text"><i class="fas fa-phone"></i></span>
                                        </div>
                                        <input type="text" class="form-control" id="phone" name="phone"
                                            placeholder="Enter phone number" form="companyForm"
                                            value="<?php echo isset($company['phone']) ? htmlspecialchars($company['phone']) : ''; ?>">
                                    </div>
                                </div>
                                <div class="form-group mb-0">
                                    <label for="notes">Notes</label>
                                    <textarea class="form-control" id="notes" name="notes" rows="3"
                                        placeholder="Any additional notes about this company"
                                        form="companyForm"><?php echo isset($company['notes']) ? htmlspecialchars($company['notes']) : ''; ?></textarea>
                                </div>
                            </div>
                        </div>
                        
                        <div class="card card-danger card-outline">
                            <div class="card-header">
                                <h3 class="card-title"><i class="fas fa-history mr-2"></i>Company History</h3>
                            </div>
                            <div class="card-body p-0">
                                <table class="table table-sm m-0">
                                    <tr>
                                        <td><strong>Date Created:</strong></td>
                                        <td><?php echo date('M d, Y H:i', strtotime($company['date_created'])); ?></td>
                                    </tr>
                                    <?php if (!empty($company['date_updated'])): ?>
                                    <tr>
                                        <td><strong>Last Updated:</strong></td>
                                        <td><?php echo date('M d, Y H:i', strtotime($company['date_updated'])); ?></td>
                                    </tr>
                                    <?php endif; ?>
                                </table>
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
            if (confirm('Are you sure you want to remove the company logo?')) {
                $('#companyLogo').val('');
                $('#logoPreview').attr('src', '../dist/img/company-placeholder.png');
                $('#logoFileName').text('No file selected');
                $(this).hide();
                $('input[name="current_logo_path"]').val(''); // Clear current logo path
            }
        });

        // Initialize Select2
        $('.select2').select2({
            theme: 'bootstrap4'
        });
    });
</script>