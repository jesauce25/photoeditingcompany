/**
 * team-assignment.js
 * Handles team assignments, image assignments to team members, and status management
 */

// Note: projectId is defined in parent page
$(document).ready(function() {
    const projectId = window.projectId || $('input[name="project_id"]').val();

    // Initialize assignment status timeline
    initializeTimeline();

    // Handle team member selection change
    $(document).on('change', '.team-member-select', function() {
        const assignmentId = $(this).data('assignment-id');
        const userId = $(this).val();
        
        if (!userId) {
            showToast('error', 'Please select a team member');
            return;
        }
        
        updateAssignmentTeamMember(assignmentId, userId);
    });

    // Handle deadline change
    $(document).on('change', '.deadline-input', function() {
        const assignmentId = $(this).data('assignment-id');
        const deadline = $(this).val();
        
        if (!deadline) {
            showToast('error', 'Please select a valid deadline');
            return;
        }
        
        updateAssignmentDeadline(assignmentId, deadline);
    });

    // Delete assignment
    $(document).on('click', '.delete-assignment', function() {
        const assignmentId = $(this).data('assignment-id');
        
        if (confirm('Are you sure you want to delete this assignment? All assigned images will be unassigned.')) {
            deleteAssignment(assignmentId);
        }
    });

    // View assigned images
    $(document).on('click', '.view-assigned-images', function() {
        const assignmentId = $(this).data('assignment-id');
        loadAssignedImages(assignmentId);
    });

    // Mark delay as acceptable
    $(document).on('click', '.mark-acceptable-btn', function() {
        const assignmentId = $(this).data('assignment-id');
        markDelayAcceptable(assignmentId);
    });

    // Handle save assignment from the modal
    $('#saveAssignment').on('click', function() {
        // Get values from the form
        const userId = $('#assigneeSelect').val();
        const role = $('#roleSelect').val();
        const deadline = $('#assignmentDeadline').val();
        
        // Get selected image IDs (defined in project-images.js)
        let selectedImageIds = [];
        if (typeof window.getSelectedImageIds === 'function') {
            selectedImageIds = window.getSelectedImageIds();
        } else {
            // Fallback - get selected images from checkboxes
            $('.image-select:checked').each(function() {
                selectedImageIds.push($(this).val());
            });
        }
        
        // Validate inputs
        if (!userId) {
            showToast('error', 'Please select an assignee');
            return;
        }
        
        if (!role) {
            showToast('error', 'Please select a role/task');
            return;
        }
        
        if (!deadline) {
            showToast('error', 'Please select a deadline');
            return;
        }
        
        if (selectedImageIds.length === 0) {
            showToast('error', 'Please select at least one image to assign');
            return;
        }
        
        // Check if any selected images are already assigned
        let alreadyAssigned = false;
        let assigneeNames = [];
        
        selectedImageIds.forEach(imageId => {
            const row = $('tr[data-image-id="' + imageId + '"]');
            const assigneeSelect = row.find('.assignee-select');
            
            if (assigneeSelect.length && assigneeSelect.val()) {
                alreadyAssigned = true;
                const assigneeName = assigneeSelect.find('option:selected').text();
                if (assigneeNames.indexOf(assigneeName) === -1) {
                    assigneeNames.push(assigneeName);
                }
            }
        });
        
        // If any images are already assigned, show confirmation
        if (alreadyAssigned) {
            const assigneeText = assigneeNames.join(', ');
            const newAssigneeName = $('#assigneeSelect option:selected').text();
            
            if (!confirm('Some selected images are already assigned to ' + assigneeText + '. Do you want to reassign them to ' + newAssigneeName + '?')) {
                return; // User cancelled
            }
        }
        
        // Show loading indicator
        const saveBtn = $(this);
        const originalText = saveBtn.html();
        saveBtn.html('<i class="fas fa-spinner fa-spin"></i> Saving...');
        saveBtn.prop('disabled', true);
        
        // Send AJAX request to create assignment
        $.ajax({
            url: 'controllers/edit_project_ajax.php',
            type: 'POST',
            data: {
                action: 'create_assignment',
                project_id: projectId,
                user_id: userId,
                role_task: role,
                deadline: deadline,
                image_ids: JSON.stringify(selectedImageIds)
            },
            success: function(response) {
                try {
                    const data = JSON.parse(response);
                    if (data.status === 'success') {
                        let successMsg = 'Assignment created successfully';
                        
                        // Add info about reassigned images if any
                        if (data.reassigned_images && data.reassigned_from) {
                            successMsg += `. ${data.reassigned_images} images were reassigned from ${data.reassigned_from.join(', ')}.`;
                        }
                        
                        showToast('success', successMsg);
                        
                        // Close modal and reload page to show the new assignment
                        $('#addAssignmentModal').modal('hide');
                        window.location.reload();
                    } else {
                        showToast('error', data.message || 'Error creating assignment');
                    }
                } catch (e) {
                    console.error('Error parsing response:', e);
                    showToast('error', 'Error processing server response');
                }
                
                // Reset button state
                saveBtn.html(originalText);
                saveBtn.prop('disabled', false);
            },
            error: function(xhr, status, error) {
                console.error('AJAX error:', error);
                showToast('error', 'Server error while creating assignment');
                
                // Reset button state
                saveBtn.html(originalText);
                saveBtn.prop('disabled', false);
            }
        });
    });

    // Initialize status timeline functionality
    function initializeTimeline() {
        // Make timeline view-only by removing click handlers
        // Status will now only be updated through artist/view-task.php
        
        // Add a click handler to show appropriate message
        $('.status-timeline .status-step').on('click', function(e) {
            e.preventDefault(); // Prevent default action
            showToast('warning', 'Only the assigned Graphic Artist can manipulate their task status.');
        });
        
        // Display current status by highlighting the appropriate timeline step
        $('.status-timeline .status-step').each(function() {
            const currentStatus = $(this).closest('tr').find('.current-status').val();
            const stepStatus = $(this).data('status');
            const timelineSteps = ['pending', 'in_progress', 'finish', 'qa', 'approved', 'completed'];
            
            const currentStepIndex = timelineSteps.indexOf(currentStatus);
            const thisStepIndex = timelineSteps.indexOf(stepStatus);
            
            if (thisStepIndex <= currentStepIndex) {
                $(this).addClass('active');
                if (thisStepIndex > 0) {
                    $(this).prev('.status-connector').addClass('active');
                }
            }
            
            if (thisStepIndex === currentStepIndex) {
                $(this).addClass('current');
            }
        });
    }

    // Function to update assignment team member
    function updateAssignmentTeamMember(assignmentId, userId) {
        $.ajax({
            url: 'controllers/edit_project_ajax.php',
            type: 'POST',
            data: {
                action: 'update_assignment_assignee',
                assignment_id: assignmentId,
                user_id: userId
            },
            success: function(response) {
                try {
                    const data = JSON.parse(response);
                    if (data.status === 'success') {
                        showToast('success', 'Team member updated successfully');
                    } else {
                        showToast('error', data.message);
                    }
                } catch (e) {
                    showToast('error', 'Error processing server response');
                }
            },
            error: function() {
                showToast('error', 'Server error while updating team member');
            }
        });
    }

    // Function to update assignment deadline
    function updateAssignmentDeadline(assignmentId, deadline) {
        $.ajax({
            url: 'controllers/edit_project_ajax.php',
            type: 'POST',
            data: {
                action: 'update_assignment_deadline',
                assignment_id: assignmentId,
                deadline: deadline
            },
            success: function(response) {
                try {
                    const data = JSON.parse(response);
                    if (data.status === 'success') {
                        showToast('success', 'Deadline updated successfully');
                        
                        // Update deadline status badge if provided in response
                        if (data.deadline_status) {
                            const row = $(`tr[data-assignment-id="${assignmentId}"]`);
                            let badgeClass = 'badge-info';
                            let statusText = '';
                            
                            switch (data.deadline_status) {
                                case 'today':
                                    badgeClass = 'badge-warning';
                                    statusText = 'Today';
                                    break;
                                case 'overdue':
                                    badgeClass = 'badge-danger';
                                    statusText = 'Overdue';
                                    break;
                            }
                            
                            if (statusText) {
                                // Update or create status badge
                                const deadlineContainer = row.find('.deadline-container');
                                const existingBadge = deadlineContainer.find('.badge');
                                
                                if (existingBadge.length > 0) {
                                    existingBadge.removeClass().addClass(`badge ${badgeClass} mr-2 mb-1`).text(statusText);
                                } else {
                                    deadlineContainer.find('.d-flex').prepend(`<span class="badge ${badgeClass} mr-2 mb-1">${statusText}</span>`);
                                }
                            }
                        }
                    } else {
                        showToast('error', data.message);
                    }
                } catch (e) {
                    showToast('error', 'Error processing server response');
                }
            },
            error: function() {
                showToast('error', 'Server error while updating deadline');
            }
        });
    }

    // Function to update assignment status
    // function updateAssignmentStatus(assignmentId, newStatus) {
    //     $.ajax({
    //         url: 'controllers/edit_project_ajax.php',
    //         type: 'POST',
    //         data: {
    //             action: 'update_assignment_status',
    //             assignment_id: assignmentId,
    //             status: newStatus
    //         },
    //         success: function(response) {
    //             try {
    //                 const data = JSON.parse(response);
    //                 if (data.status === 'success') {
    //                     showToast('success', 'Assignment status updated to ' + newStatus);
                        
    //                     // Update the UI
    //                     const row = $('tr[data-assignment-id="' + assignmentId + '"]');
                        
    //                     // Update timeline visualization
    //                     const timelineSteps = ['pending', 'in_progress', 'finish', 'qa', 'approved', 'completed'];
    //                     const newStatusIndex = timelineSteps.indexOf(newStatus);
                        
    //                     row.find('.status-step').each(function(index) {
    //                         if (index <= newStatusIndex) {
    //                             $(this).addClass('active');
    //                             if (index > 0) {
    //                                 $(this).prev('.status-connector').addClass('active');
    //                             }
    //                         } else {
    //                             $(this).removeClass('active current');
    //                             if (index > 0) {
    //                                 $(this).prev('.status-connector').removeClass('active');
    //                             }
    //                         }
                            
    //                         // Mark current step
    //                         if (index === newStatusIndex) {
    //                             $(this).addClass('current');
    //                         } else {
    //                             $(this).removeClass('current');
    //                         }
    //                     });
                        
    //                     // Update hidden status
    //                     row.find('.current-status').val(newStatus);
                        
    //                     // If status changed to completed, check if all assignments are now completed
    //                     if (newStatus === 'completed') {
    //                         checkAllAssignmentsCompleted();
    //                     }
    //                 } else {
    //                     showToast('error', data.message);
    //                 }
    //             } catch (e) {
    //                 showToast('error', 'Error processing server response');
    //             }
    //         },
    //         error: function() {
    //             showToast('error', 'Server error while updating status');
    //         }
    //     });
    // }

    // Function to delete assignment
    function deleteAssignment(assignmentId) {
        $.ajax({
            url: 'controllers/edit_project_ajax.php',
            type: 'POST',
            data: {
                action: 'delete_assignment',
                assignment_id: assignmentId,
                project_id: projectId
            },
            success: function(response) {
                try {
                    const data = JSON.parse(response);
                    if (data.status === 'success') {
                        showToast('success', data.message);
                        
                        // Remove assignment row
                        $('tr[data-assignment-id="' + assignmentId + '"]').remove();
                        
                        // Refresh project stats
                        if (typeof refreshProjectStats === 'function') {
                            refreshProjectStats();
                        }
                    } else {
                        showToast('error', data.message);
                    }
                } catch (e) {
                    showToast('error', 'Error processing server response');
                }
            },
            error: function() {
                showToast('error', 'Server error while deleting assignment');
            }
        });
    }

    // Function to load assigned images
    function loadAssignedImages(assignmentId) {
        $.ajax({
            url: 'controllers/edit_project_ajax.php',
            type: 'POST',
            data: {
                action: 'get_assigned_images',
                project_id: projectId,
                assignment_id: assignmentId
            },
            success: function(response) {
                try {
                    const data = JSON.parse(response);
                    if (data.status === 'success') {
                        // Display the assigned images in a modal
                        showAssignedImagesModal(data.images, data.assignment);
                    } else {
                        showToast('error', data.message || 'Error loading assigned images');
                    }
                } catch (e) {
                    showToast('error', 'Error processing server response');
                }
            },
            error: function() {
                showToast('error', 'Server error while loading assigned images');
            }
        });
    }

    // Function to display assigned images modal
    function showAssignedImagesModal(images, assignment) {
        // Check if there are any images in finish status that can be approved
        const hasFinishedImages = images.some(image => image.status_image === 'finish');
        
        // Create modal HTML
        let modalHtml = `
            <div class="modal fade" id="assignedImagesModal" tabindex="-1" role="dialog">
                <div class="modal-dialog modal-xl" role="document" style="max-width: 95%;">
                    <div class="modal-content">
                        <div class="modal-header bg-primary text-white">
                            <h5 class="modal-title">
                                Assigned Images for ${assignment.role_task || ''} Assignment
                            </h5>
                            ${hasFinishedImages ? `
                            <button type="button" class="btn btn-success ml-auto mr-2 approve-all-btn">
                                <i class="fas fa-check-circle"></i> Approve All
                            </button>
                            ` : ''}
                            <button type="button" class="close text-white" data-dismiss="modal">
                                <span>&times;</span>
                            </button>
                        </div>
                        <div class="modal-body">
                            <div class="assignment-info mb-3">
                                <div class="row">
                                    <div class="col-md-4">
                                        <strong>Team Member:</strong> ${assignment.full_name || 'Not assigned'}
                                    </div>
                                    <div class="col-md-4">
                                        <strong>Status:</strong> ${assignment.status_assignee || 'Pending'}
                                    </div>
                                    <div class="col-md-4">
                                        <strong>Deadline:</strong> ${assignment.deadline || 'Not set'}
                                    </div>
                                </div>
                            </div>
                            <div class="table-responsive">
                                <table class="table table-bordered table-hover" id="assignedImagesTable" style="table-layout: fixed; width: 100%;">
                                    <thead>
                                        <tr>
                                            <th style="width: 40%;">Image</th>
                                            <th style="width: 15%;">Role</th>
                                            <th style="width: 15%;">Estimated Time</th>
                                            <th style="width: 15%;">Status</th>
                                            <th style="width: 15%;">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>`;
        
        if (images.length === 0) {
            modalHtml += `
                <tr>
                    <td colspan="5" class="text-center">No images assigned yet.</td>
                </tr>`;
        } else {
            // Add each image row
            images.forEach(image => {
                // Format estimated time
                let estimatedTimeDisplay = '';
                if (image.estimated_time) {
                    const hours = Math.floor(image.estimated_time / 60);
                    const minutes = image.estimated_time % 60;
                    
                    if (hours > 0) {
                        estimatedTimeDisplay += hours + 'h ';
                    }
                    if (minutes > 0 || hours === 0) {
                        estimatedTimeDisplay += minutes + 'm';
                    }
                } else {
                    estimatedTimeDisplay = 'Not set';
                }
                
                // Get file name for display
                const fileName = image.image_path.split('/').pop();
                
                // Determine status badge class
                let statusBadgeClass = 'badge-secondary';
                let statusIcon = 'fa-clock';
                
                switch (image.status_image) {
                    case 'in_progress':
                        statusBadgeClass = 'badge-primary';
                        statusIcon = 'fa-spinner fa-spin';
                        break;
                    case 'finish':
                        statusBadgeClass = 'badge-info';
                        statusIcon = 'fa-check';
                        break;
                    case 'completed':
                        statusBadgeClass = 'badge-success';
                        statusIcon = 'fa-check-circle';
                        break;
                }
                
                // Apply proper row class for redo status
                const rowClass = image.redo === '1' ? 'class="table-danger"' : '';
                
                // Add the image row
                modalHtml += `
                    <tr data-image-id="${image.image_id}" ${rowClass}>
                        <td>
                            <a href="../uploads/projects/${projectId}/${image.image_path}" target="_blank" class="image-preview-link" style="display: block; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                                ${fileName}
                            </a>
                        </td>
                        <td>
                            <select class="form-control form-control-sm role-select" data-image-id="${image.image_id}">
                                <option value="">Select Role</option>
                                <option value="Clipping Path" ${image.image_role == 'Clipping Path' ? 'selected' : ''}>Clipping Path</option>
                                <option value="Color Correction" ${image.image_role == 'Color Correction' ? 'selected' : ''}>Color Correction</option>
                                <option value="Retouch" ${image.image_role == 'Retouch' ? 'selected' : ''}>Retouch</option>
                                <option value="Final" ${image.image_role == 'Final' ? 'selected' : ''}>Final</option>
                                <option value="Retouch to Final" ${image.image_role == 'Retouch to Final' ? 'selected' : ''}>Retouch to Final</option>
                            </select>
                        </td>
                        <td>
                            <div class="input-group input-group-sm">
                                <input type="number"
                                    class="form-control form-control-sm estimated-hours"
                                    value="${Math.floor((image.estimated_time || 0) / 60)}"
                                    min="0"
                                    data-image-id="${image.image_id}"
                                    placeholder="Hours">
                                <div class="input-group-append">
                                    <span class="input-group-text">h</span>
                                </div>
                                <input type="number"
                                    class="form-control form-control-sm estimated-minutes ml-1"
                                    value="${(image.estimated_time || 0) % 60}"
                                    min="0" max="59"
                                    data-image-id="${image.image_id}"
                                    placeholder="Min">
                                <div class="input-group-append">
                                    <span class="input-group-text">m</span>
                                </div>
                            </div>
                        </td>
                        <td>
                            <span class="badge ${statusBadgeClass} p-2">
                                <i class="fas ${statusIcon} mr-1"></i> 
                                ${image.status_image ? image.status_image.charAt(0).toUpperCase() + image.status_image.slice(1) : 'Pending'}
                            </span>
                            ${image.status_image === 'finish' ? `
                            <button type="button" class="btn btn-sm btn-success mt-2 approve-image-btn" data-image-id="${image.image_id}">
                                <i class="fas fa-check-circle"></i> Approve
                            </button>` : ''}
                        </td>
                        <td>
                            <div class="btn-group btn-group-sm">
                                <button type="button" class="btn btn-sm btn-warning mark-redo-btn" data-image-id="${image.image_id}" data-redo="${image.redo === '1' ? '0' : '1'}" title="${image.redo === '1' ? 'Cancel Redo Request' : 'Mark for Redo'}">
                                    <i class="fas ${image.redo === '1' ? 'fa-times' : 'fa-redo-alt'}"></i>
                                </button>
                                ${image.redo === '1' ? `<span class="redo-badge ml-2">REDO</span>` : ''}
                            </div>
                        </td>
                    </tr>`;
            });
        }
        
        modalHtml += `
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                        </div>
                    </div>
                </div>
            </div>`;
        
        // Remove any existing modal
        $('#assignedImagesModal').remove();
        
        // Add to the DOM and show
        $('body').append(modalHtml);
        $('#assignedImagesModal').modal('show');

        // Bind event handler for approve all button
        $(document).on('click', '.approve-all-btn', function() {
            // Get all image IDs that are in finish status
            const finishedImageIds = [];
            
            $('#assignedImagesTable tr[data-image-id]').each(function() {
                const row = $(this);
                const statusBadge = row.find('td:eq(3) .badge');
                
                // Check if status is "finish" by looking for approve button
                if (row.find('.approve-image-btn').length > 0) {
                    finishedImageIds.push(row.data('image-id'));
                }
            });
            
            if (finishedImageIds.length === 0) {
                showToast('warning', 'No images in finish status to approve');
                return;
            }
            
            // Confirm before approving all
            if (confirm(`Are you sure you want to approve all ${finishedImageIds.length} finished images?`)) {
                // Disable button to prevent multiple clicks
                const btn = $(this);
                btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Processing...');
                
                // Process each image sequentially
                let processed = 0;
                
                function approveNext() {
                    if (processed >= finishedImageIds.length) {
                        // All done
                        showToast('success', `Successfully approved ${processed} images`);
                        btn.prop('disabled', false).html('<i class="fas fa-check-circle"></i> Approve All');
                        return;
                    }
                    
                    const imageId = finishedImageIds[processed];
                    
                    $.ajax({
                        url: 'controllers/edit_project_ajax.php',
                        type: 'POST',
                        data: {
                            action: 'update_image_status',
                            image_id: imageId,
                            status: 'completed'
                        },
                        success: function(response) {
                            try {
                                const data = JSON.parse(response);
                                if (data.status === 'success') {
                                    // Update the row in the UI
                                    const row = $(`tr[data-image-id="${imageId}"]`);
                                    
                                    // Update status badge
                                    row.find('td:eq(3) .badge')
                                       .removeClass('badge-info')
                                       .addClass('badge-success')
                                       .html('<i class="fas fa-check-circle mr-1"></i> Completed');
                                    
                                    // Remove approve button
                                    row.find('.approve-image-btn').remove();
                                    
                                    processed++;
                                    approveNext();
                                } else {
                                    showToast('error', `Error approving image: ${data.message}`);
                                    btn.prop('disabled', false).html('<i class="fas fa-check-circle"></i> Approve All');
                                }
                            } catch (e) {
                                showToast('error', 'Error processing server response');
                                btn.prop('disabled', false).html('<i class="fas fa-check-circle"></i> Approve All');
                            }
                        },
                        error: function() {
                            showToast('error', 'Server error while approving images');
                            btn.prop('disabled', false).html('<i class="fas fa-check-circle"></i> Approve All');
                        }
                    });
                }
                
                // Start the approval process
                approveNext();
            }
        });
        
        // Clean up event handler when modal is closed
        $('#assignedImagesModal').on('hidden.bs.modal', function() {
            $(document).off('click', '.approve-all-btn');
        });
    }

    
    // Function to check if all assignments are completed
    function checkAllAssignmentsCompleted() {
        $.ajax({
            url: 'controllers/edit_project_ajax.php',
            type: 'POST',
            data: {
                action: 'check_all_assignments_completed',
                project_id: projectId
            },
            success: function(response) {
                try {
                    const data = JSON.parse(response);
                    if (data.status === 'success' && data.all_completed) {
                        // Update project status in UI if needed
                        $('#status_project').val('completed');
                        
                        // Refresh project stats
                        if (typeof refreshProjectStats === 'function') {
                            refreshProjectStats();
                        }
                    }
                } catch (e) {
                    console.error('Error checking project completion', e);
                }
            }
        });
    }

    // Function to mark delay as acceptable
    function markDelayAcceptable(assignmentId) {
        $.ajax({
            url: 'controllers/edit_project_ajax.php',
            type: 'POST',
            data: {
                action: 'mark_delay_acceptable',
                assignment_id: assignmentId
            },
            success: function(response) {
                try {
                    const data = JSON.parse(response);
                    if (data.status === 'success') {
                        showToast('success', 'Delay marked as acceptable');
                        
                        // Update UI - change badge color to success
                        const row = $('tr[data-assignment-id="' + assignmentId + '"]');
                        row.find('.badge-danger').removeClass('badge-danger').addClass('badge-success');
                        
                        // Hide the mark as acceptable button
                        row.find('.mark-acceptable-btn').hide();
                    } else {
                        showToast('error', data.message);
                    }
                } catch (e) {
                    showToast('error', 'Error processing server response');
                }
            },
            error: function() {
                showToast('error', 'Server error while marking delay as acceptable');
            }
        });
    }

    // Helper function to show toast notifications
    function showToast(type, message) {
        let bgClass = 'bg-info';
        
        switch (type) {
            case 'success':
                bgClass = 'bg-success';
                break;
            case 'error':
                bgClass = 'bg-danger';
                break;
            case 'warning':
                bgClass = 'bg-warning';
                break;
        }
        
        $(document).Toasts('create', {
            class: bgClass,
            title: type.charAt(0).toUpperCase() + type.slice(1),
            body: message,
            autohide: true,
            delay: 3000
        });
    }

    // Handle role change in the View Assigned Images modal
    $(document).on('change', '.role-select', function() {
        const imageId = $(this).data('image-id');
        const role = $(this).val();
        
        if (!role) {
            showToast('error', 'Please select a role');
            return;
        }
        
        // Use the existing update_image_details endpoint via AJAX
        $.ajax({
            url: 'controllers/edit_project_ajax.php',
            type: 'POST',
            data: {
                action: 'update_image_details',
                image_id: imageId,
                image_role: role
            },
            success: function(response) {
                try {
                    const data = JSON.parse(response);
                    if (data.status === 'success') {
                        showToast('success', 'Role updated successfully');
                    } else {
                        showToast('error', data.message || 'Error updating role');
                    }
                } catch (e) {
                    showToast('error', 'Error processing server response');
                }
            },
            error: function() {
                showToast('error', 'Server error while updating role');
            }
        });
    });
});
