<?php
include("includes/header.php");
// Include required controllers
require_once "controllers/unified_project_controller.php";

// Get companies for dropdown
$companies = getCompaniesForDropdown();

// Check for error or success messages
$error_messages = $_SESSION['error_messages'] ?? [];
$success_message = $_SESSION['success_message'] ?? '';

// Get form data for repopulation if any
$form_data = $_SESSION['form_data'] ?? [];

// Clear session variables
unset($_SESSION['error_messages']);
unset($_SESSION['success_message']);
unset($_SESSION['form_data']);
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
                        <h1><i class="fas fa-project-diagram mr-2"></i>Add New Project</h1>
                    </div>
                    <div class="col-sm-6">
                        <ol class="breadcrumb float-sm-right">
                            <li class="breadcrumb-item"><a href="#">Home</a></li>
                            <li class="breadcrumb-item">Projects</li>
                            <li class="breadcrumb-item active">Add Project</li>
                        </ol>
                    </div>
                </div>
            </div><!-- /.container-fluid -->
        </section>

        <!-- Display error messages if any -->
        <?php if (!empty($error_messages)): ?>
            <div class="alert alert-danger alert-dismissible fade show mx-3" role="alert">
                <strong>Error!</strong>
                <ul class="mb-0">
                    <?php foreach ($error_messages as $error): ?>
                        <li><?php echo htmlspecialchars($error); ?></li>
                    <?php endforeach; ?>
                </ul>
                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
        <?php endif; ?>

        <!-- Display success message if any -->
        <?php if (!empty($success_message)): ?>
            <div class="alert alert-success alert-dismissible fade show mx-3" role="alert">
                <strong>Success!</strong> <?php echo htmlspecialchars($success_message); ?>
                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
        <?php endif; ?>

        <!-- Main content -->
        <section class="content">
            <div class="container-fluid">
                <div class="row">
                    <div class="col-md-8">
                        <div class="card card-success card-outline  theme-green ">
                            <div class="card-header  ">
                                <h3 class="card-title">Project Information</h3>
                                <div class="card-tools">
                                    <button type="button" class="btn btn-tool" data-card-widget="collapse">
                                        <i class="fas fa-minus"></i>
                                    </button>
                                </div>
                            </div>
                            <div class="card-body">
                                <form action="process-add-project.php" method="post" id="projectForm"
                                    enctype="multipart/form-data">
                                    <div class="row">
                                        <div class="col-md-12">
                                            <div class="form-group">
                                                <label for="companyName">Company Name <span
                                                        class="text-danger">*</span></label>
                                                <div class="input-group">
                                                    <div class="input-group-prepend">
                                                        <span class="input-group-text"><i
                                                                class="fas fa-building"></i></span>
                                                    </div>
                                                    <select class="form-control select2" id="companyName"
                                                        name="companyName" required style="height: 100%;">
                                                        <option value="" selected disabled>Select a company</option>
                                                        <?php foreach ($companies as $company): ?>
                                                            <option value="<?php echo $company['company_id']; ?>" <?php echo (isset($form_data['companyName']) && $form_data['companyName'] == $company['company_id']) ? 'selected' : ''; ?>>
                                                                <?php echo htmlspecialchars($company['company_name']); ?>
                                                            </option>
                                                        <?php endforeach; ?>
                                                    </select>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="projectName">Project Name <span
                                                        class="text-info">(Optional)</span></label>
                                                <div class="input-group">
                                                    <div class="input-group-prepend">
                                                        <span class="input-group-text"><i
                                                                class="fas fa-clipboard-list"></i></span>
                                                    </div>
                                                    <input type="text" class="form-control" id="projectName"
                                                        name="projectName" placeholder="Enter project name"
                                                        value="<?php echo htmlspecialchars($form_data['projectName'] ?? ''); ?>">
                                                </div>
                                            </div>
                                            <div class="form-group">
                                                <label for="dateArrived">Date Arrived <span
                                                        class="text-danger">*</span></label>
                                                <div class="input-group date">
                                                    <div class="input-group-prepend">
                                                        <span class="input-group-text"><i
                                                                class="fas fa-calendar-check"></i></span>
                                                    </div>
                                                    <input type="date" class="form-control" id="dateArrived"
                                                        name="dateArrived"
                                                        value="<?php echo htmlspecialchars($form_data['dateArrived'] ?? ''); ?>"
                                                        required>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="numImages">Number of Images <small
                                                        class="text-muted">(Auto-calculated from selected
                                                        files)</small></label>
                                                <div class="input-group">
                                                    <div class="input-group-prepend">
                                                        <span class="input-group-text"><i
                                                                class="fas fa-images"></i></span>
                                                    </div>
                                                    <input type="number" class="form-control" id="total_images"
                                                        name="total_images" min="0" placeholder="Auto-calculated"
                                                        value="0" readonly>
                                                </div>
                                            </div>
                                            <div class="form-group">
                                                <label for="deadline">Deadline <span
                                                        class="text-danger">*</span></label>
                                                <div class="input-group date">
                                                    <div class="input-group-prepend">
                                                        <span class="input-group-text"><i
                                                                class="fas fa-hourglass-end"></i></span>
                                                    </div>
                                                    <input type="date" class="form-control" id="deadline"
                                                        name="deadline"
                                                        value="<?php echo htmlspecialchars($form_data['deadline'] ?? ''); ?>"
                                                        required>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <label for="projectName">Project Description <span
                                                class="text-info">(Optional)</span></label> <textarea
                                            class="form-control" id="description" name="description" rows="3"
                                            placeholder="Enter project details"><?php echo htmlspecialchars($form_data['description'] ?? ''); ?></textarea>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-4">
                        <div class="card card-success card-outline theme-green ">
                            <div class="card-header">
                                <h3 class="card-title"><i class="fas fa-tasks mr-2"></i>Project Status</h3>
                            </div>
                            <div class="card-body">
                                <div class="form-group">
                                    <label for="priority">Priority</label>
                                    <select class="form-control" id="priority" name="priority" form="projectForm"
                                        style="height: 100%;">
                                        <option value="low" <?php echo (isset($form_data['priority']) && $form_data['priority'] == 'low') ? 'selected' : ''; ?>>Low</option>
                                        <option value="medium" <?php echo (!isset($form_data['priority']) || $form_data['priority'] == 'medium') ? 'selected' : ''; ?>>Medium</option>
                                        <option value="high" <?php echo (isset($form_data['priority']) && $form_data['priority'] == 'high') ? 'selected' : ''; ?>>High</option>
                                        <option value="urgent" <?php echo (isset($form_data['priority']) && $form_data['priority'] == 'urgent') ? 'selected' : ''; ?>>Urgent</option>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label for="status">Status</label>
                                    <select class="form-control" id="status" name="status" form="projectForm"
                                        style="height: 100%;">
                                        <option value="pending" <?php echo (!isset($form_data['status']) || $form_data['status'] == 'pending') ? 'selected' : ''; ?>>Pending</option>
                                        <option value="in_progress" <?php echo (isset($form_data['status']) && $form_data['status'] == 'in_progress') ? 'selected' : ''; ?>>In Progress
                                        </option>
                                        <option value="review" <?php echo (isset($form_data['status']) && $form_data['status'] == 'review') ? 'selected' : ''; ?>>Under Review</option>
                                        <option value="completed" <?php echo (isset($form_data['status']) && $form_data['status'] == 'completed') ? 'selected' : ''; ?>>Completed</option>
                                    </select>
                                </div>

                            </div>
                        </div>
                    </div>
                </div>

                <!-- Image Upload Section -->
                <div class="row mt-3">
                    <div class="col-12">
                        <div class="card card-success card-outline theme-green">
                            <div class="card-header">
                                <h3 class="card-title"><i class="fas fa-upload mr-2"></i>Upload Project Files</h3>
                            </div>
                            <div class="card-body">
                                <div class="alert alert-info">
                                    <i class="fas fa-info-circle mr-2"></i> Only file names will be stored in the
                                    database.
                                </div>
                                <div class="image-upload-container">
                                    <div class="dropzone-wrapper project-dropzone" id="dropzone">
                                        <div class="dropzone-desc">
                                            <i class="fas fa-cloud-upload-alt fa-3x"></i>
                                            <p>Drag & drop files here or click to browse</p>
                                            <p class="small text-muted">You can upload multiple files</p>
                                        </div>
                                        <input type="file" class="dropzone" id="projectImages" multiple>
                                        <!-- Hidden input to store file names -->
                                        <input type="hidden" id="fileNames" name="fileNames" form="projectForm"
                                            value="">
                                    </div>
                                    <div class="mt-3">
                                        <label><strong>Selected Files:</strong></label>
                                        <div id="selectedFilesList" class="list-group">
                                            <div class="list-group-item text-muted">No files selected</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Action Buttons -->
                <div class="row mt-3 mb-5">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-body">
                                <button type="submit" form="projectForm" class="btn btn-primary float-right ml-2">
                                    <i class="fas fa-save mr-2"></i> Save Project
                                </button>
                                <button type="submit" form="projectForm" class="btn btn-success float-right ml-2"
                                    id="saveAndAddAnother" name="saveAndAddAnother" value="1">
                                    <i class="fas fa-plus-circle mr-2"></i> Save & Add Another
                                </button>
                                <button type="reset" form="projectForm" class="btn btn-secondary">
                                    <i class="fas fa-undo mr-2"></i> Reset
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </div>
</div>

<?php include("includes/footer.php"); ?>

<!-- Custom Styles -->
<style>
    .dropzone-wrapper {
        border: 2px dashed #28a745;
        border-radius: 5px;
        padding: 20px;
        text-align: center;
        cursor: pointer;
        transition: all 0.3s ease;
    }

    .dropzone-wrapper:hover {
        background-color: rgba(40, 167, 69, 0.1);
    }

    .dropzone-wrapper.dragover {
        background-color: rgba(40, 167, 69, 0.2);
    }

    .dropzone-desc {
        color: #666;
    }

    .dropzone-desc i {
        color: #28a745;
        margin-bottom: 10px;
    }

    .file-item {
        display: flex;
        align-items: center;
        padding: 12px 15px;
        border-bottom: 1px solid #eee;
        transition: background-color 0.2s;
    }

    .file-item:hover {
        background-color: #f8f9fa;
    }

    .file-preview-container {
        width: 50px;
        height: 50px;
        display: flex;
        align-items: center;
        justify-content: center;
        margin-right: 15px;
        background-color: #f8f9fa;
        border-radius: 4px;
        overflow: hidden;
    }

    .file-preview {
        width: 100%;
        height: 100%;
        object-fit: cover;
        border-radius: 4px;
    }

    .file-icon {
        width: 50px;
        height: 50px;
        display: flex;
        align-items: center;
        justify-content: center;
        margin-right: 15px;
        background-color: #f8f9fa;
        border-radius: 4px;
        font-size: 24px;
        color: #6c757d;
    }

    .file-info {
        flex-grow: 1;
    }

    .file-name {
        font-weight: 500;
        margin-bottom: 4px;
        color: #495057;
        word-break: break-all;
    }

    .file-details {
        display: flex;
        align-items: center;
        gap: 12px;
    }

    .file-size,
    .file-type {
        font-size: 0.8em;
        color: #6c757d;
        display: flex;
        align-items: center;
        gap: 5px;
        background-color: #f1f3f5;
        border-radius: 4px;
        padding: 2px 6px;
    }

    .remove-file {
        background: none;
        border: none;
        color: #dc3545;
        cursor: pointer;
        padding: 8px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: background-color 0.2s;
    }

    .remove-file:hover {
        background-color: rgba(220, 53, 69, 0.1);
        color: #c82333;
    }

    #selectedFilesList {
        max-height: 300px;
        overflow-y: auto;
        border: 1px solid #dee2e6;
        border-radius: 4px;
    }
</style>

<!-- Custom JavaScript -->
<script>
    $(document).ready(function () {
        const dropzone = document.getElementById('dropzone');
        const fileInput = document.getElementById('projectImages');
        const fileList = document.getElementById('selectedFilesList');
        const totalImagesInput = document.getElementById('total_images');
        const fileNamesInput = document.getElementById('fileNames');

        // Array to store file information
        let selectedFiles = [];

        // Handle file selection
        fileInput.addEventListener('change', function (e) {
            const files = this.files;

            // Store file information
            selectedFiles = Array.from(files).map(file => ({
                name: file.name,
                type: file.type,
                size: file.size
            }));

            // Update UI
            updateFileList();

            // Update hidden inputs
            updateHiddenInputs();
        });

        // Click to browse
        dropzone.addEventListener('click', function (e) {
            if (e.target !== fileInput) {
                e.preventDefault();
                fileInput.click();
            }
        });

        // Handle drag and drop
        ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
            dropzone.addEventListener(eventName, preventDefaults, false);
        });

        function preventDefaults(e) {
            e.preventDefault();
            e.stopPropagation();
        }

        ['dragenter', 'dragover'].forEach(eventName => {
            dropzone.addEventListener(eventName, highlight, false);
        });

        ['dragleave', 'drop'].forEach(eventName => {
            dropzone.addEventListener(eventName, unhighlight, false);
        });

        function highlight() {
            dropzone.classList.add('dragover');
        }

        function unhighlight() {
            dropzone.classList.remove('dragover');
        }

        dropzone.addEventListener('drop', handleDrop, false);

        function handleDrop(e) {
            const dt = e.dataTransfer;
            const files = dt.files;

            // Store file information
            selectedFiles = Array.from(files).map(file => ({
                name: file.name,
                type: file.type,
                size: file.size
            }));

            // Update UI
            updateFileList();

            // Update hidden inputs
            updateHiddenInputs();
        }

        // Update the file list display
        function updateFileList() {
            fileList.innerHTML = '';

            if (selectedFiles.length === 0) {
                fileList.innerHTML = '<div class="list-group-item text-muted">No files selected</div>';
                return;
            }

            selectedFiles.forEach((file, index) => {
                const fileItem = document.createElement('div');
                fileItem.className = 'list-group-item file-item';

                let fileIconClass = 'fa-file';
                if (file.type.startsWith('image/')) {
                    fileIconClass = 'fa-file-image';
                } else if (file.type.includes('pdf')) {
                    fileIconClass = 'fa-file-pdf';
                } else if (file.type.includes('word')) {
                    fileIconClass = 'fa-file-word';
                } else if (file.type.includes('excel') || file.type.includes('sheet')) {
                    fileIconClass = 'fa-file-excel';
                } else if (file.type.includes('zip') || file.type.includes('compressed')) {
                    fileIconClass = 'fa-file-archive';
                }

                const fileSize = formatFileSize(file.size);

                fileItem.innerHTML = `
                    <div class="file-icon"><i class="fas ${fileIconClass}"></i></div>
                    <div class="file-info">
                        <div class="file-name">${file.name}</div>
                        <div class="file-details">
                            <span class="file-size">
                                <i class="fas fa-hdd"></i> ${fileSize}
                            </span>
                            <span class="file-type">
                                <i class="fas fa-file-alt"></i> ${file.type || 'Unknown type'}
                            </span>
                        </div>
                    </div>
                    <button type="button" class="remove-file" data-index="${index}">
                        <i class="fas fa-times"></i>
                    </button>
                `;

                fileList.appendChild(fileItem);
            });

            // Add event listeners to remove buttons
            document.querySelectorAll('.remove-file').forEach(button => {
                button.addEventListener('click', function () {
                    const index = parseInt(this.getAttribute('data-index'));
                    removeFile(index);
                });
            });
        }

        // Update hidden inputs with file information
        function updateHiddenInputs() {
            // Update total images count
            if (totalImagesInput) {
                totalImagesInput.value = selectedFiles.length;
            }

            // Update file names (convert to JSON string)
            if (fileNamesInput) {
                fileNamesInput.value = JSON.stringify(selectedFiles);
            }
        }

        // Remove a file
        function removeFile(index) {
            // Remove file from array
            selectedFiles.splice(index, 1);

            // Update UI
            updateFileList();

            // Update hidden inputs
            updateHiddenInputs();
        }
    });

    function formatFileSize(bytes) {
        if (bytes === 0) return '0 Bytes';
        const k = 1024;
        const sizes = ['Bytes', 'KB', 'MB', 'GB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
    }
</script>