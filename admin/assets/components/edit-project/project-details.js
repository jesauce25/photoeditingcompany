/**
 * project-details.js
 * Handles project metadata (name, description, status), notifications, and AJAX updates
 */

// Document ready function
$(document).ready(function () {
    // Check for global projectId (should be set in parent page)
    if (typeof window.projectId === 'undefined') {
        console.error('ERROR: projectId not defined in parent page. Components will not function correctly.');
        return; // Exit early if projectId isn't available
    }
    
    // Log that component is loaded
    console.log('Project details component loaded for project ID:', window.projectId);
    
    if (typeof window.logging !== 'undefined') {
        window.logging.info(`Project details component loaded for project ID: ${window.projectId}`);
    }

    // Add event listeners for project details fields
    $('#projectName, #description, #priority, #dateArrived, #deadline').on('change', function () {
        const fieldName = $(this).attr('id');
        const value = $(this).val();
        updateProjectField(fieldName, value);
    });

    // Add event listener for company field (select2)
    $('#company').on('change', function () {
        const value = $(this).val();
        updateProjectField('company', value);
    });

    // Add event listeners for status buttons
    $('.status-btn').on('click', function () {
        const status = $(this).data('status');
        updateProjectStatus(status);
    });
});

// Function to update project field via AJAX
function updateProjectField(fieldName, value) {
    console.log(`Updating project field: ${fieldName}`, {value});
    
    if (typeof window.logging !== 'undefined') {
        window.logging.info(`Updating project field: ${fieldName}`, {value});
    }

    $.ajax({
        url: 'controllers/edit_project_ajax.php',
        type: 'POST',
        data: {
            action: 'update_project_field',
            project_id: window.projectId,
            field_name: fieldName,
            field_value: value
        },
        dataType: 'json',
        success: function (response) {
            console.log('Update response received', response);
            
            if (response.status === 'success') {
                showNotification('success', `Project ${fieldName} updated successfully`);
            } else {
                showNotification('error', `Error: ${response.message}`);
            }
        },
        error: function (xhr, status, error) {
            console.error('AJAX error', {xhr, status, error});
            showNotification('error', 'An error occurred while updating the project');
        }
    });
}

// Function to update project status
function updateProjectStatus(status) {
    console.log(`Updating project status to: ${status}`);
    
    if (typeof window.logging !== 'undefined') {
        window.logging.info(`Updating project status to: ${status}`);
    }

    $.ajax({
        url: 'controllers/edit_project_ajax.php',
        type: 'POST',
        data: {
            action: 'update_project_status',
            project_id: window.projectId,
            status: status
        },
        dataType: 'json',
        success: function (response) {
            console.log('Status update response received', response);

            if (response.status === 'success') {
                showNotification('success', 'Project status updated successfully');
                // Update status badge in UI
                updateStatusBadgeUI(status);
            } else {
                showNotification('error', `Error: ${response.message}`);
            }
        },
        error: function (xhr, status, error) {
            console.error('AJAX error', {xhr, status, error});
            showNotification('error', 'An error occurred while updating the project status');
        }
    });
}

// Function to update status badge in UI
function updateStatusBadgeUI(status) {
    const statusBadge = $('#statusBadge');

    // Remove all existing classes
    statusBadge.removeClass('badge-primary badge-warning badge-success badge-danger badge-info');

    // Add appropriate class based on status
    switch (status) {
        case 'pending':
            statusBadge.addClass('badge-warning').text('Pending');
            break;
        case 'in_progress':
            statusBadge.addClass('badge-primary').text('In Progress');
            break;
        case 'review':
            statusBadge.addClass('badge-info').text('Review');
            break;
        case 'completed':
            statusBadge.addClass('badge-success').text('Completed');
            break;
        case 'delayed':
            statusBadge.addClass('badge-danger').text('Delayed');
            break;
    }
}

// Function to show notification (fallback in case parent function is missing)
function showNotification(type, message) {
    // If the parent page has a showNotification function and it's not this function,
    // use that to avoid recursion
    if (typeof window.showNotification === 'function' && 
        window.showNotification !== showNotification) {
        window.showNotification(type, message);
        return;
    }
    
    // Otherwise, create our own notification
    const alertClass = type === 'success' ? 'alert-success' : 'alert-danger';
    const icon = type === 'success' ? 'fas fa-check-circle' : 'fas fa-exclamation-circle';

    const alertHtml = `
    <div class="alert ${alertClass} alert-dismissible fade show" role="alert" id="tempAlert">
        <i class="${icon} mr-2"></i> ${message}
        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
            <span aria-hidden="true">&times;</span>
        </button>
    </div>
    `;

    // Remove any existing alerts
    $('#tempAlert').remove();

    // Add the new alert at the top of the content
    $('.content-header').after(alertHtml);

    // Auto-dismiss after 3 seconds
    setTimeout(() => {
        $('#tempAlert').alert('close');
    }, 3000);
}
