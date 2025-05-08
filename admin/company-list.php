<?php
include("includes/header.php");


?>

<style>
    .loading-overlay {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background-color: rgba(255, 255, 255, 0.7);
        display: none;
        z-index: 100;
        justify-content: center;
        align-items: center;
    }

    .loading-spinner {
        width: 3rem;
        height: 3rem;
        border: 5px solid #f3f3f3;
        border-top: 5px solid #3498db;
        border-radius: 50%;
        animation: spin 1s linear infinite;
    }

    @keyframes spin {
        0% {
            transform: rotate(0deg);
        }

        100% {
            transform: rotate(360deg);
        }
    }

    .search-box {
        position: relative;
    }

    .search-box button {
        position: absolute;
        right: 0;
        top: 0;
        height: 100%;
        background: transparent;
        border: none;
        color: #6c757d;
    }

    .search-box input:focus+button {
        color: #3498db;
    }

    .country-badge {
        padding: 5px 10px;
        border-radius: 20px;
        font-size: 0.8rem;
        font-weight: 500;
        display: inline-block;
    }

    .usa-badge {
        background-color: #e8f4fd;
        color: #1565C0;
    }

    .uk-badge {
        background-color: #e8f0fd;
        color: #303F9F;
    }

    .canada-badge {
        background-color: #ffebee;
        color: #c62828;
    }

    .australia-badge {
        background-color: #e0f7fa;
        color: #00838F;
    }

    .philippines-badge {
        background-color: #e8f5e9;
        color: #2E7D32;
    }

    /* Style for dynamic country badges */
    .country-badge-dynamic {
        padding: 5px 10px;
        border-radius: 20px;
        font-size: 0.8rem;
        font-weight: 500;
        display: inline-block;
        /* Colors will be applied inline */
    }

    .action-buttons .btn {
        margin: 0 2px;
    }

    .filter-option {
        min-width: 150px;
    }
</style>

<div class="wrapper">
    <?php include("includes/nav.php"); ?>

    <!-- Content Wrapper. Contains page content -->
    <div class="content-wrapper">
        <!-- Content Header (Page header) -->
        <section class="content-header">
            <div class="container-fluid">
                <div class="row mb-2">
                    <div class="col-sm-6">
                        <h1><i class="fas fa-building mr-2"></i>Company List</h1>
                    </div>
                    <div class="col-sm-6">
                        <ol class="breadcrumb float-sm-right">
                            <li class="breadcrumb-item"><a href="#">Home</a></li>
                            <li class="breadcrumb-item">Company</li>
                            <li class="breadcrumb-item active">Company List</li>
                        </ol>
                    </div>
                </div>
            </div>
        </section>

        <!-- Display success message if exists -->
        <?php if (isset($_SESSION['success_message']) && !empty($_SESSION['success_message'])): ?>
            <div class="alert alert-success alert-dismissible fade show mx-3">
                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
                <h5><i class="icon fas fa-check"></i> Success!</h5>
                <?php echo $_SESSION['success_message'];
                unset($_SESSION['success_message']); ?>
            </div>
        <?php endif; ?>

        <!-- Main content -->
        <section class="content">
            <div class="container-fluid">
                <div class="card">
                    <div class="card-header d-flex align-items-center justify-content-between"
                        style="min-height: 60px;">
                        <div class="d-flex align-items-center">
                            <h3 class="card-title m-0 mr-3">
                                <i class="fas fa-list mr-2"></i>Company Records
                            </h3>
                        </div>

                        <div class="d-flex justify-content-center align-items-center flex-grow-1">
                            <select id="filterType" class="form-control form-control-sm mr-2"
                                style="width: 150px; height: 100%;">
                                <option value="country" selected>Country</option>
                                <option value="dateSignedUp">Date Signed Up</option>
                            </select>

                            <div id="countryFilterContainer" class="filter-option">
                                <select id="countrySelect" class="form-control form-control-sm mr-2"
                                    style="width: 150px; height: 100%;">
                                    <option value="">All Countries</option>
                                    <option value="USA">United States</option>
                                    <option value="UK">United Kingdom</option>
                                    <option value="Canada">Canada</option>
                                    <option value="Australia">Australia</option>
                                    <option value="Philippines">Philippines</option>
                                </select>
                            </div>

                            <div id="dateTypeContainer" class="filter-option" style="display: none;">
                                <select id="dateFilterType" class="form-control form-control-sm mr-2"
                                    style="width: 150px; height: 100%;">
                                    <option value="year" selected>Year</option>
                                    <option value="month-year">Month-Year</option>
                                </select>
                            </div>

                            <div id="yearFilterContainer" class="filter-option" style="display: none;">
                                <select id="yearSelect" class="form-control form-control-sm mr-2"
                                    style="width: 150px; height: 100%;">
                                    <option value="">All Years</option>
                                    <?php
                                    for ($year = 2000; $year <= 2025; $year++) {
                                        echo "<option value=\"$year\">$year</option>";
                                    }
                                    ?>
                                </select>
                            </div>

                            <div id="monthYearContainer" class="filter-option" style="display: none;">
                                <input type="month" id="monthYearPicker" class="form-control form-control-sm mr-2"
                                    style="width: 150px; height: 100%;">
                            </div>

                            <button id="applyFilter" class="btn btn-info btn-sm">
                                <i class="fas fa-filter mr-1"></i> Filter
                            </button>
                        </div>

                        <div>
                            <a href="add-company.php" class="btn btn-primary btn-sm">
                                <i class="fas fa-plus mr-1"></i> Add New Company
                            </a>
                        </div>
                    </div>
                    <div class="card-body">
                        <!-- Table controls -->
                        <div class="row mb-4">
                            <!-- Left Group: Export buttons -->
                            <!-- <div class="col-md-4">
                                <div class="btn-group">
                                    <button type="button" class="btn btn-success btn-sm export-excel"
                                        title="Export to Excel">
                                        <i class="fas fa-file-excel mr-1"></i> Excel
                                    </button>
                                    <button type="button" class="btn btn-danger btn-sm export-pdf"
                                        title="Export to PDF">
                                        <i class="fas fa-file-pdf mr-1"></i> PDF
                                    </button>
                                    <button type="button" class="btn btn-info btn-sm export-print" title="Print">
                                        <i class="fas fa-print mr-1"></i> Print
                                    </button>
                                </div>
                            </div> -->



                            <!-- Right Group: Search box -->
                            <!-- <div class="col-md-4">
                                <div class="search-box float-right" style="width: 250px;">
                                    <input type="text" id="searchInput" class="form-control form-control-sm"
                                        placeholder="Search companies...">
                                    <button type="button" class="btn">
                                        <i class="fas fa-search"></i>
                                    </button>
                                </div>
                            </div> -->
                        </div>

                        <!-- Table with loading overlay -->
                        <div class="position-relative">
                            <div class="loading-overlay">
                                <div class="loading-spinner"></div>
                            </div>
                            <div class="table-responsive">
                                <table id="companyTable" class="table table-bordered table-hover">
                                    <thead>
                                        <tr>
                                            <th width="15%">Company Name</th>
                                            <th width="15%">Country</th>
                                            <th width="15%">Date Signed Up</th>
                                            <th width="20%">Contact Person</th>
                                            <th width="15%">Email</th>
                                            <th width="15%" class="text-center">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <!-- Table data will be loaded dynamically via AJAX -->
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div class="modal fade" id="deleteModal" tabindex="-1" role="dialog" aria-labelledby="deleteModalLabel"
    aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title" id="deleteModalLabel">
                    <i class="fas fa-exclamation-triangle"></i> Delete Confirmation
                </h5>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="text-center mb-3">
                    <i class="fas fa-trash-alt fa-4x text-danger mb-3"></i>
                    <h4>Are you absolutely sure?</h4>
                    <p class="text-muted">You are about to delete <strong id="companyNameToDelete"></strong></p>
                    <p class="text-danger"><small>This action cannot be undone!</small></p>
                </div>
                <div class="alert alert-warning">
                    <i class="fas fa-info-circle"></i> This will permanently delete:
                    <ul class="mb-0 mt-2">
                        <li>Company information</li>
                        <li>Associated records</li>
                        <li>Company logo and files</li>
                    </ul>
                </div>
            </div>
            <div class="modal-footer justify-content-center">
                <input type="hidden" id="companyIdToDelete">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">
                    <i class="fas fa-times"></i> Cancel
                </button>
                <button type="button" class="btn btn-danger" id="confirmDelete">
                    <i class="fas fa-trash"></i> Yes, Delete Company
                </button>
            </div>
        </div>
    </div>
</div>

<?php include("includes/footer.php"); ?>

<script>
    $(document).ready(function () {
        // Initialize DataTable only if it's not already initialized
        let table;
        if (!$.fn.dataTable.isDataTable('#companyTable')) {
            table = $('#companyTable').DataTable({
                "responsive": true,
                "lengthChange": true,
                "autoWidth": false,
                "pageLength": 1000,
                "searching": true,
                "ordering": true,
                "info": true,
                "dom": '<"row align-items-center"<"col-sm-6"l><"col-sm-6 d-flex justify-content-end"f>><"row"<"col-sm-12"tr>><"row"<"col-sm-5"i><"col-sm-7 d-flex justify-content-end"p>>',
                "buttons": [
                    { extend: 'excel', className: 'hidden-button' },
                    { extend: 'pdf', className: 'hidden-button' },
                    { extend: 'print', className: 'hidden-button' }
                ],
                "language": {
                    "lengthMenu": "Show _MENU_ entries",
                    "search": "",
                    "searchPlaceholder": "Search companies...",
                    "info": "Showing _START_ to _END_ of _TOTAL_ companies",
                    "infoEmpty": "Showing 0 to 0 of 0 companies",
                    "infoFiltered": "(filtered from _MAX_ total companies)",
                    "zeroRecords": "No matching companies found",
                    "emptyTable": "No companies available",
                    "paginate": {
                        "first": '<i class="fas fa-angle-double-left"></i>',
                        "previous": '<i class="fas fa-angle-left"></i>',
                        "next": '<i class="fas fa-angle-right"></i>',
                        "last": '<i class="fas fa-angle-double-right"></i>'
                    }
                }
            });
        } else {
            table = $('#companyTable').DataTable();
        }

        // Handle custom export buttons
        $('.export-excel').on('click', function () {
            table.button('.buttons-excel').trigger();
        });

        $('.export-pdf').on('click', function () {
            table.button('.buttons-pdf').trigger();
        });

        $('.export-print').on('click', function () {
            table.button('.buttons-print').trigger();
        });

        // Handle custom search box
        $('#searchInput').on('keyup', function () {
            table.search(this.value).draw();
        });

        // Filter type change
        $('#filterType').change(function () {
            const filterType = $(this).val();

            if (filterType === 'country') {
                $('#countryFilterContainer').show();
                $('#dateTypeContainer, #yearFilterContainer, #monthYearContainer').hide();
            } else {
                $('#countryFilterContainer').hide();
                $('#dateTypeContainer').show();

                const dateFilterType = $('#dateFilterType').val();
                if (dateFilterType === 'year') {
                    $('#yearFilterContainer').show();
                    $('#monthYearContainer').hide();
                } else {
                    $('#yearFilterContainer').hide();
                    $('#monthYearContainer').show();
                }
            }
        });

        // Date filter type change
        $('#dateFilterType').change(function () {
            const dateFilterType = $(this).val();

            if (dateFilterType === 'year') {
                $('#yearFilterContainer').show();
                $('#monthYearContainer').hide();
            } else {
                $('#yearFilterContainer').hide();
                $('#monthYearContainer').show();
            }
        });

        // Apply filter button
        $('#applyFilter').click(function () {
            loadCompanies();
        });

        // Function to load companies based on filters
        function loadCompanies() {
            // Show loading overlay
            $('.loading-overlay').css('display', 'flex');

            // Get filter values
            const filterType = $('#filterType').val();
            let params = {};

            if (filterType === 'country') {
                params.country = $('#countrySelect').val();
            } else {
                const dateFilterType = $('#dateFilterType').val();
                if (dateFilterType === 'year') {
                    params.year = $('#yearSelect').val();
                } else {
                    params.monthYear = $('#monthYearPicker').val();
                }
            }

            // Add search parameter if exists
            const searchValue = $('#searchInput').val();
            if (searchValue) {
                params.search = searchValue;
            }

            // AJAX request to load companies
            $.ajax({
                url: 'fetch-companies.php',
                type: 'GET',
                data: params,
                dataType: 'json',
                success: function (response) {
                    if (response.status === 'success') {
                        // Clear existing data
                        table.clear();

                        // Add data to table
                        if (response.data.length > 0) {
                            $.each(response.data, function (index, company) {
                                // Create country badge
                                let countryClass = '';
                                let badgeStyle = '';

                                switch (company.country) {
                                    case 'USA': countryClass = 'usa-badge'; break;
                                    case 'UK': countryClass = 'uk-badge'; break;
                                    case 'Canada': countryClass = 'canada-badge'; break;
                                    case 'Australia': countryClass = 'australia-badge'; break;
                                    case 'Philippines': countryClass = 'philippines-badge'; break;
                                    default:
                                        // Generate a consistent color based on the country name
                                        let hash = 0;
                                        for (let i = 0; i < company.country.length; i++) {
                                            hash = company.country.charCodeAt(i) + ((hash << 5) - hash);
                                        }

                                        // Generate HSL color with good saturation and lightness
                                        const h = hash % 360;
                                        const s = 65 + (hash % 20); // 65-85% saturation
                                        const l = 75 + (hash % 10); // 75-85% lightness

                                        // Create inline style for the badge
                                        const bgColor = `hsl(${h}, ${s}%, ${l}%)`;
                                        const textColor = `hsl(${h}, ${s}%, 25%)`; // Darker text based on bg

                                        countryClass = 'country-badge-dynamic';
                                        badgeStyle = `style="background-color:${bgColor};color:${textColor}"`;
                                }

                                const countryBadge = countryClass ?
                                    `<span class="country-badge ${countryClass}" ${badgeStyle}>${company.country}</span>` :
                                    `<span class="country-badge-dynamic" ${badgeStyle}>${company.country}</span>`;

                                // Create action buttons


                                // add this inside of actions, for the sending email to company
                                // <a href="mailto:${company.email}" class="btn btn-info btn-sm" title="Send Email">
                                //         <i class="fas fa-envelope"></i>
                                //     </a>
                                const actions = `
                                <div class="action-buttons text-center">
                                  
                                    <a href="edit-company.php?id=${company.company_id}" class="btn btn-primary btn-sm" title="Edit Company">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <button type="button" class="btn btn-danger btn-sm delete-btn" 
                                        data-id="${company.company_id}" 
                                        data-name="${company.company_name}" 
                                        title="Delete Company">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                            `;

                                // Add row to table
                                table.row.add([
                                    `<div class="d-flex align-items-center">
                                    <img src="${company.logo_path}" class="img-circle elevation-2 mr-3" width="50" height="50" alt="Company Logo">
                                    <span>${company.company_name}</span>
                                </div>`,
                                    countryBadge,
                                    company.formatted_date,
                                    company.person_in_charge,
                                    company.email,
                                    actions
                                ]);
                            });

                            // Draw the table
                            table.draw();
                        }
                    } else {
                        // Show error message
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: response.message || 'Failed to load companies',
                            confirmButtonText: 'OK'
                        });
                    }
                },
                error: function () {
                    // Show error message
                    Swal.fire({
                        icon: 'error',
                        title: 'Server Error',
                        text: 'An error occurred while loading companies. Please try again later.',
                        confirmButtonText: 'OK'
                    });
                },
                complete: function () {
                    // Hide loading overlay
                    $('.loading-overlay').hide();
                }
            });
        }

        // Initial load of companies
        loadCompanies();

        // Delete button click handler (using event delegation)
        $('#companyTable').on('click', '.delete-btn', function () {
            const companyId = $(this).data('id');
            const companyName = $(this).data('name');

            // Set values in the modal
            $('#companyIdToDelete').val(companyId);
            $('#companyNameToDelete').text(companyName);

            // Show the modal
            $('#deleteModal').modal('show');
        });

        // Update the confirmDelete button click handler
        $('#confirmDelete').click(function () {
            const companyId = $('#companyIdToDelete').val();
            const button = $(this);
            const originalHtml = button.html();

            // Show loading state
            button.html('<i class="fas fa-spinner fa-spin mr-2"></i> Deleting...').prop('disabled', true);

            // Send delete request
            $.ajax({
                url: 'delete-company.php',
                type: 'POST',
                data: { company_id: companyId },
                dataType: 'json',
                success: function (response) {
                    // Close modal regardless of success or failure
                    $('#deleteModal').modal('hide');

                    if (response.status === 'success') {
                        // Just reload companies without showing any alert
                        loadCompanies();
                    } else {
                        // Only show error if something went wrong
                        console.error('Error deleting company:', response.message);
                        // Silent reload
                        loadCompanies();
                    }
                },
                error: function () {
                    // Close modal and reload quietly on error
                    $('#deleteModal').modal('hide');
                    loadCompanies();
                    console.error('Server error during delete operation');
                },
                complete: function () {
                    // Restore button state
                    button.html(originalHtml).prop('disabled', false);
                }
            });
        });
    });
</script>