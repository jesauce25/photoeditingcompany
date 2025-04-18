$(document).ready(function () {
  // Initialize tooltips
  $('[data-toggle="tooltip"]').tooltip();

  // Handle user status toggle
  $('.toggle-status').on('click', function() {
    const userId = $(this).data('user-id');
    const currentStatus = $(this).data('status');
    const newStatus = currentStatus === 'Active' ? 'Blocked' : 'Active';
    const actionText = currentStatus === 'Active' ? 'block' : 'unblock';
    
    // Update modal content
    $('#toggleStatusModal .modal-body').html(
      `Are you sure you want to ${actionText} this user?`
    );
    
    // Show modal
    $('#toggleStatusModal').modal('show');
    
    // Handle confirmation
    $('#confirmToggleStatus').off('click').on('click', function() {
      const $btn = $(this);
      const originalText = $btn.html();
      
      // Disable button and show loading state
      $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-1"></i> Processing...');
      
      $.ajax({
        url: 'controllers/user_controller.php',
        type: 'POST',
        data: {
          action: 'update_status',
          user_id: userId,
          new_status: newStatus
        },
        dataType: 'json',
        success: function(response) {
          if (response.success) {
            // Update UI
            $(`#user-${userId} .status-badge`)
              .removeClass('badge-success badge-danger')
              .addClass(newStatus === 'Active' ? 'badge-success' : 'badge-danger')
              .text(newStatus);
            
            $(`#user-${userId} .toggle-status`)
              .data('status', newStatus)
              .attr('title', `Click to ${actionText} user`);
            
            // Show success message
            showToast('success', response.message);
            
            // Close modal
            $('#toggleStatusModal').modal('hide');
          } else {
            showToast('error', response.message);
          }
        },
        error: function(xhr, status, error) {
          console.error('AJAX Error:', error);
          showToast('error', 'An error occurred while updating user status');
        },
        complete: function() {
          // Re-enable button and restore original text
          $btn.prop('disabled', false).html(originalText);
        }
      });
    });
  });

  // Handle user deletion
  $('.delete-user').on('click', function() {
    const userId = $(this).data('user-id');
    const userName = $(this).data('user-name');
    
    // Update modal content
    $('#deleteUserModal .modal-body').html(
      `Are you sure you want to delete user "${userName}"? This action cannot be undone.`
    );
    
    // Show modal
    $('#deleteUserModal').modal('show');
    
    // Handle confirmation
    $('#confirmDeleteUser').off('click').on('click', function() {
      const $btn = $(this);
      const originalText = $btn.html();
      
      // Disable button and show loading state
      $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-1"></i> Deleting...');
      
      $.ajax({
        url: 'controllers/user_controller.php',
        type: 'POST',
        data: {
          action: 'delete',
          user_id: userId
        },
        dataType: 'json',
        success: function(response) {
          if (response.success) {
            // Remove user row from table
            $(`#user-${userId}`).fadeOut(400, function() {
              $(this).remove();
              
              // Check if table is empty
              if ($('#userTableBody tr').length === 0) {
                $('#userTableBody').html(
                  '<tr><td colspan="6" class="text-center">No users found</td></tr>'
                );
              }
            });
            
            // Show success message
            showToast('success', response.message);
            
            // Close modal
            $('#deleteUserModal').modal('hide');
          } else {
            showToast('error', response.message);
          }
        },
        error: function(xhr, status, error) {
          console.error('AJAX Error:', error);
          showToast('error', 'An error occurred while deleting user');
        },
        complete: function() {
          // Re-enable button and restore original text
          $btn.prop('disabled', false).html(originalText);
        }
      });
    });
  });

  // Handle filters
  $('#roleFilter, #statusFilter').on('change', function() {
    applyFilters();
  });

  $('#searchInput').on('keyup', function() {
    applyFilters();
  });

  function applyFilters() {
    const roleFilter = $('#roleFilter').val().toLowerCase();
    const statusFilter = $('#statusFilter').val().toLowerCase();
    const searchText = $('#searchInput').val().toLowerCase();

    $('.user-row').each(function() {
      const role = $(this).find('.role-badge').text().toLowerCase();
      const status = $(this).find('.status-badge').text().toLowerCase();
      const name = $(this).find('.user-name').text().toLowerCase();
      const email = $(this).find('.user-email').text().toLowerCase();

      const matchesRole = roleFilter === 'all' || role === roleFilter;
      const matchesStatus = statusFilter === 'all' || status === statusFilter;
      const matchesSearch = searchText === '' || 
                          name.includes(searchText) || 
                          email.includes(searchText);

      $(this).toggle(matchesRole && matchesStatus && matchesSearch);
    });
  }

  // Toast notification function
  function showToast(type, message) {
    const toast = $(`
      <div class="toast" role="alert" aria-live="assertive" aria-atomic="true">
        <div class="toast-header ${type === 'success' ? 'bg-success text-white' : 'bg-danger text-white'}">
          <i class="fas fa-${type === 'success' ? 'check-circle' : 'exclamation-circle'} mr-2"></i>
          <strong class="mr-auto">${type === 'success' ? 'Success' : 'Error'}</strong>
          <button type="button" class="ml-2 mb-1 close text-white" data-dismiss="toast" aria-label="Close">
            <span aria-hidden="true">&times;</span>
          </button>
        </div>
        <div class="toast-body">
          ${message}
        </div>
      </div>
    `);

    $('#toastContainer').append(toast);
    toast.toast({ delay: 3000 }).toast('show');

    toast.on('hidden.bs.toast', function() {
      toast.remove();
    });
  }
});
