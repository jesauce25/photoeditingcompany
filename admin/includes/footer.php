<!-- jQuery -->
<script src="../plugins/jquery/jquery.min.js"></script>
<!-- jQuery UI 1.11.4 -->
<script src="../plugins/jquery-ui/jquery-ui.min.js"></script>
<!-- jQuery Validation Plugin -->
<script>
    // Check if local jQuery validation is available, otherwise use CDN
    var jQueryValidationScript = document.createElement('script');
    jQueryValidationScript.src = "../plugins/jquery-validation/jquery.validate.min.js";
    jQueryValidationScript.onerror = function () {
        console.log("Local jQuery validation not found, using CDN");
        var cdnScript = document.createElement('script');
        cdnScript.src = "https://cdn.jsdelivr.net/npm/jquery-validation@1.19.5/dist/jquery.validate.min.js";
        document.head.appendChild(cdnScript);

        // Additional methods
        var additionalScript = document.createElement('script');
        additionalScript.src = "https://cdn.jsdelivr.net/npm/jquery-validation@1.19.5/dist/additional-methods.min.js";
        document.head.appendChild(additionalScript);
    };
    document.head.appendChild(jQueryValidationScript);

    // Try loading additional methods
    var additionalMethodsScript = document.createElement('script');
    additionalMethodsScript.src = "../plugins/jquery-validation/additional-methods.min.js";
    additionalMethodsScript.onerror = function () {
        console.log("Local jQuery validation additional methods not found, using CDN if needed");
    };
    document.head.appendChild(additionalMethodsScript);
</script>
<!-- Resolve conflict in jQuery UI tooltip with Bootstrap tooltip -->
<script>
    $.widget.bridge('uibutton', $.ui.button)
</script>
<!-- Bootstrap 4 -->
<script src="../plugins/bootstrap/js/bootstrap.bundle.min.js"></script>
<!-- ChartJS -->
<script src="../plugins/chart.js/Chart.min.js"></script>
<!-- Sparkline -->
<script src="../plugins/sparklines/sparkline.js"></script>
<!-- JQVMap -->
<script src="../plugins/jqvmap/jquery.vmap.min.js"></script>
<script src="../plugins/jqvmap/maps/jquery.vmap.usa.js"></script>
<!-- jQuery Knob Chart -->
<script src="../plugins/jquery-knob/jquery.knob.min.js"></script>
<!-- daterangepicker -->
<script src="../plugins/moment/moment.min.js"></script>
<script src="../plugins/daterangepicker/daterangepicker.js"></script>
<!-- Tempusdominus Bootstrap 4 -->
<script src="../plugins/tempusdominus-bootstrap-4/js/tempusdominus-bootstrap-4.min.js"></script>
<!-- Summernote -->
<script src="../plugins/summernote/summernote-bs4.min.js"></script>
<!-- overlayScrollbars -->
<script src="../plugins/overlayScrollbars/js/jquery.overlayScrollbars.min.js"></script>
<!-- AdminLTE App -->
<script src="../dist/js/adminlte.js?v=3.2.0"></script>
<!-- AdminLTE for demo purposes -->
<!-- <script src="../dist/js/demo.js"></script> -->
<!-- AdminLTE dashboard demo (This is only for demo purposes) -->
<script src="../dist/js/pages/dashboard.js"></script>

<!-- SweetAlert2 -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<!-- Add DataTables & Export Plugins -->
<script src="../plugins/datatables/jquery.dataTables.min.js"></script>
<script src="../plugins/datatables-bs4/js/dataTables.bootstrap4.min.js"></script>
<script src="../plugins/datatables-responsive/js/dataTables.responsive.min.js"></script>
<script src="../plugins/datatables-buttons/js/dataTables.buttons.min.js"></script>
<script src="../plugins/jszip/jszip.min.js"></script>
<script src="../plugins/pdfmake/pdfmake.min.js"></script>
<script src="../plugins/pdfmake/vfs_fonts.js"></script>
<script src="../plugins/datatables-buttons/js/buttons.html5.min.js"></script>
<script src="../plugins/datatables-buttons/js/buttons.print.min.js"></script>

<script src="assets/js/edit-project/edit-project.js"></script>
<script src="assets/js/user-list/user-list.js"></script>

<!-- Notification System JavaScript -->
<script>
    $(document).ready(function () {
        // Handle notification click - mark as read
        $('.dropdown-menu a[data-notification-id]').on('click', function (e) {
            e.preventDefault();
            const notificationId = $(this).data('notification-id');
            const notificationLink = $(this).attr('href');
            const isValidLink = notificationLink && notificationLink !== '#';

            // Mark notification as read via AJAX
            $.ajax({
                url: 'controllers/notification_ajax.php',
                type: 'POST',
                data: {
                    action: 'mark_read',
                    notification_id: notificationId
                },
                success: function (response) {
                    try {
                        const data = JSON.parse(response);
                        if (data.status === 'success') {
                            // Update UI - mark as read visually
                            $(`a[data-notification-id="${notificationId}"]`).removeClass('font-weight-bold').addClass('text-muted');

                            // Update notification count
                            let currentCount = parseInt($('#notification-bell .navbar-badge').text());
                            currentCount--;

                            if (currentCount <= 0) {
                                $('#notification-bell .navbar-badge').remove();
                            } else {
                                $('#notification-bell .navbar-badge').text(currentCount);
                                $('.dropdown-item.dropdown-header').text(currentCount + ' Notifications');
                            }

                            // Navigate to the link if it's valid
                            if (isValidLink) {
                                window.location.href = notificationLink;
                            }
                        }
                    } catch (error) {
                        console.error('Error parsing AJAX response:', error);
                        // Navigate even if there's an error parsing the response
                        if (isValidLink) {
                            window.location.href = notificationLink;
                        }
                    }
                },
                error: function (xhr, status, error) {
                    console.error('AJAX request failed:', status, error);
                    // Navigate even if the AJAX request fails
                    if (isValidLink) {
                        window.location.href = notificationLink;
                    }
                }
            });
        });

        // Handle clear all notifications
        $('#clear-notifications').on('click', function (e) {
            e.preventDefault();

            // Confirm before clearing
            if (confirm('Are you sure you want to clear all notifications?')) {
                // Clear all notifications via AJAX
                $.ajax({
                    url: 'controllers/notification_ajax.php',
                    type: 'POST',
                    data: {
                        action: 'clear_all'
                    },
                    success: function (response) {
                        try {
                            const data = JSON.parse(response);
                            if (data.status === 'success') {
                                // Update UI - remove all notifications
                                $('#notification-bell .navbar-badge').remove();
                                $('.dropdown-item[data-notification-id]').remove();
                                $('.dropdown-item.dropdown-header').text('0 Notifications');
                                // Add a message
                                $('.dropdown-menu').append('<a class="dropdown-item text-center"><i class="fas fa-check-circle text-success mr-2"></i>All notifications cleared</a>');
                            }
                        } catch (error) {
                            console.error('Error parsing AJAX response:', error);
                        }
                    },
                    error: function (xhr, status, error) {
                        console.error('AJAX request failed:', status, error);
                    }
                });
            }
        });
    });
</script>

<!-- Custom JavaScript for dynamic filters -->
<script>
    $(document).ready(function () {
        // Initialize DataTable
        var table = $('#companyTable').DataTable({
            "responsive": true,
            "lengthChange": false,
            "autoWidth": false,
            "pageLength": 10,
            "searching": false, // This disables the search feature
            "dom": 'Brtip', // Changed from 'Bfrtip' to 'Brtip' to remove one pagination
            "buttons": [{
                extend: 'excel',
                text: 'Excel',
                exportOptions: {
                    columns: [0, 1, 2, 3, 4, 5]
                },
                className: 'hidden-button'
            },
            {
                extend: 'pdf',
                text: 'PDF',
                exportOptions: {
                    columns: [0, 1, 2, 3, 4, 5]
                },
                className: 'hidden-button'
            },
            {
                extend: 'print',
                text: 'Print',
                exportOptions: {
                    columns: [0, 1, 2, 3, 4, 5]
                },
                className: 'hidden-button'
            }
            ],

        });

        // Hide the default DataTables buttons
        $('.hidden-button').hide();

        // Custom export buttons
        $('.export-excel').on('click', function () {
            table.button('.buttons-excel').trigger();
        });

        $('.export-pdf').on('click', function () {
            table.button('.buttons-pdf').trigger();
        });

        $('.export-print').on('click', function () {
            table.button('.buttons-print').trigger();
        });

        // Initialize filter state - Country is default
        initializeFilters();

        // Filter type change handler
        $('#filterType').change(function () {
            updateFilterVisibility();
        });

        // Date filter type change handler
        $('#dateFilterType').change(function () {
            updateDateFilterVisibility();
        });

        // Apply filter button click handler
        $('#applyFilter').click(function () {
            applyCurrentFilter();
        });

        // Initialize month-year picker with current date
        var today = new Date();
        var month = today.getMonth() + 1; // getMonth() returns 0-11
        month = month < 10 ? '0' + month : month;
        var year = today.getFullYear();
        $('#monthYearPicker').val(year + '-' + month);

        // Function to initialize filters
        function initializeFilters() {
            // Show country filter by default (matches the default selected option)
            $('#countryFilterContainer').show();
            $('#dateTypeContainer').hide();
            $('#yearFilterContainer').hide();
            $('#monthYearContainer').hide();
        }

        // Function to update filter visibility based on filter type
        function updateFilterVisibility() {
            var selectedFilter = $('#filterType').val();

            // Hide all filter options first
            $('#countryFilterContainer').hide();
            $('#dateTypeContainer').hide();
            $('#yearFilterContainer').hide();
            $('#monthYearContainer').hide();

            if (selectedFilter === 'country') {
                // Show country filter
                $('#countryFilterContainer').show();
            } else if (selectedFilter === 'dateSignedUp') {
                // Show date type filter
                $('#dateTypeContainer').show();

                // Update date filter visibility based on selected date type
                updateDateFilterVisibility();
            }
        }

        // Function to update date filter visibility
        function updateDateFilterVisibility() {
            var dateFilterType = $('#dateFilterType').val();

            // Hide both date filter options
            $('#yearFilterContainer').hide();
            $('#monthYearContainer').hide();

            // Show the appropriate one
            if (dateFilterType === 'year') {
                $('#yearFilterContainer').show();
            } else if (dateFilterType === 'month-year') {
                $('#monthYearContainer').show();
            }
        }

        // Function to apply the current filter
        function applyCurrentFilter() {
            var filterType = $('#filterType').val();

            // Clear any existing filters first
            table.columns().search('').draw();

            if (filterType === 'country') {
                var country = $('#countrySelect').val();
                if (country) {
                    // Search in the country column (index 2)
                    table.column(2).search(country).draw();
                }
            } else if (filterType === 'dateSignedUp') {
                var dateFilterType = $('#dateFilterType').val();

                if (dateFilterType === 'year') {
                    var year = $('#yearSelect').val();
                    if (year) {
                        // Search for the year in the date column (index 3)
                        table.column(3).search(year).draw();
                    }
                } else if (dateFilterType === 'month-year') {
                    var monthYear = $('#monthYearPicker').val();
                    if (monthYear) {
                        // Format month-year for searching (YYYY-MM to match date format)
                        table.column(3).search(monthYear).draw();
                    }
                }
            }
        }

        // Delete button click handler
        $(document).on('click', '.delete-btn', function () {
            var companyId = $(this).data('id');
            var companyName = $(this).data('name');

            $('#companyNameToDelete').text(companyName);
            $('#confirmDelete').data('id', companyId);
            $('#deleteModal').modal('show');
        });

        // Confirm delete button click handler
        $('#confirmDelete').click(function () {
            var companyId = $(this).data('id');

            // Here you would make an AJAX call to delete the company
            // For demo purposes, we'll just close the modal and show a success message
            $('#deleteModal').modal('hide');

            // Show success message (requires toastr.js)
            if (typeof toastr !== 'undefined') {
                toastr.success('Company has been deleted successfully');
            } else {
                alert('Company has been deleted successfully');
            }

            // Remove the row from the table
            table.row($('button.delete-btn[data-id="' + companyId + '"]').closest('tr')).remove().draw();
        });

        // Email button click handler
        $(document).on('click', '.email-btn', function () {
            var email = $(this).data('email');
            $('#emailTo').val(email);
            $('#emailSubject').val('');
            $('#emailBody').val('');
            $('#emailModal').modal('show');
        });

        // Send email button click handler
        $('#sendEmail').click(function () {
            var emailTo = $('#emailTo').val();
            var subject = $('#emailSubject').val();
            var body = $('#emailBody').val();

            if (!subject || !body) {
                if (typeof toastr !== 'undefined') {
                    toastr.error('Please fill in all required fields');
                } else {
                    alert('Please fill in all required fields');
                }
                return;
            }

            // Here you would make an AJAX call to send the email
            // For demo purposes, we'll just close the modal and show a success message
            $('#emailModal').modal('hide');

            // Show success message
            if (typeof toastr !== 'undefined') {
                toastr.success('Email has been sent to ' + emailTo);
            } else {
                alert('Email has been sent to ' + emailTo);
            }
        });

        // Add CSS for filter container
        $('<style>.filter-container { display: flex; align-items: center; } .filter-option { transition: all 0.3s ease; }</style>').appendTo('head');
    });
</script>

<!-- Add SweetAlert2 library -->
<script src="../plugins/sweetalert2/sweetalert2.all.min.js"></script>
<link rel="stylesheet" href="../plugins/sweetalert2-theme-bootstrap-4/bootstrap-4.min.css">

</body>

</html>