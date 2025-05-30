/**
 * project-images.js
 * Handles project image management (upload, selection, deletion)
 */

// Document ready function for image related functionality
$(document).ready(function () {
  // Check for global projectId (should be set in parent page)
  if (typeof window.projectId === "undefined") {
    console.error(
      "ERROR: projectId not defined in parent page. Components will not function correctly."
    );
    return; // Exit early if projectId isn't available
  }

  console.log(
    "Project images component loaded for project ID:",
    window.projectId
  );

  // Helper function for safe logging
  function safeLog(method, ...args) {
    if (
      typeof window.logging !== "undefined" &&
      typeof window.logging[method] === "function"
    ) {
      window.logging[method](...args);
    } else {
      // Fallback to console
      if (method === "error") {
        console.error(...args);
      } else if (method === "warning") {
        console.warn(...args);
      } else {
        console.log(`[${method.toUpperCase()}]`, ...args);
      }
    }
  }

  safeLog(
    "info",
    `Project images component loaded for project ID: ${window.projectId}`
  );

  // Add AJAX setup for proper headers
  $.ajaxSetup({
    headers: {
      "X-Requested-With": "XMLHttpRequest",
    },
  });

  // Global variables
  const projectId = window.projectId || $('input[name="project_id"]').val();
  let selectedImages = [];

  // Initialize select all checkbox
  $("#selectAllImages").on("change", function () {
    const isChecked = $(this).prop("checked");
    $(".image-select").prop("checked", isChecked);
    updateSelectedImages();
  });

  // Initialize individual image checkboxes
  $(document).on("change", ".image-select", function () {
    updateSelectedImages();

    // Update the "select all" checkbox state
    const totalCheckboxes = $(".image-select").length;
    const checkedCheckboxes = $(".image-select:checked").length;
    $("#selectAllImages").prop(
      "checked",
      totalCheckboxes === checkedCheckboxes
    );
  });

  // Function to update selected images array and UI
  function updateSelectedImages() {
    selectedImages = [];
    $(".image-select:checked").each(function () {
      selectedImages.push($(this).val());
    });

    // Update selected count text
    $("#selectedCount").text(selectedImages.length + " images selected");

    // Toggle batch actions visibility
    if (selectedImages.length > 0) {
      $("#batchActions").show();
    } else {
      $("#batchActions").hide();
    }
  }

  // Delete selected images
  $("#deleteSelected").on("click", function () {
    if (selectedImages.length === 0) {
      showToast("error", "Please select at least one image to delete");
      return;
    }

    if (
      confirm(
        "Are you sure you want to delete " +
          selectedImages.length +
          " selected images? This action cannot be undone."
      )
    ) {
      deleteImages(selectedImages);
    }
  });

  // Delete single image
  $(document).on("click", ".delete-image", function () {
    const imageId = $(this).data("image-id");
    if (
      confirm(
        "Are you sure you want to delete this image? This action cannot be undone."
      )
    ) {
      deleteImages([imageId]);
    }
  });

  // Function to delete images via AJAX
  function deleteImages(imageIds) {
    $.ajax({
      url: "controllers/edit_project_ajax.php",
      type: "POST",
      data: {
        action: "delete_images",
        project_id: projectId,
        image_ids: JSON.stringify(imageIds),
      },
      success: function (response) {
        try {
          const data = JSON.parse(response);
          if (data.status === "success") {
            showToast("success", data.message);

            // Remove deleted images from DOM
            imageIds.forEach((id) => {
              $('tr[data-image-id="' + id + '"]').remove();
            });

            // Reset selection
            selectedImages = [];
            updateSelectedImages();

            // Refresh stats if needed
            if (typeof refreshProjectStats === "function") {
              refreshProjectStats();
            }
          } else {
            showToast("error", data.message);
          }
        } catch (e) {
          showToast("error", "Error processing server response");
        }
      },
      error: function () {
        showToast("error", "Server error while deleting images");
      },
    });
  }

  // Update the redo badge and styling when JSON response received for mark-redo-btn
  function updateRedoUI(row, isRedo) {
    // Ensure row has correct class
    if (isRedo) {
      row.addClass("table-danger");
    } else {
      row.removeClass("table-danger");
    }

    // Update or add the redo badge
    if (isRedo) {
      if (row.find(".redo-badge").length === 0) {
        row
          .find("td:eq(1) a")
          .after('<span class="redo-badge ml-2">REDO</span>');
      }
    } else {
      row.find(".redo-badge").remove();
    }
  }

  // Mark image for redo
  $(document).on("click", ".mark-redo-btn", function () {
    const imageId = $(this).data("image-id");
    const redoValue = $(this).data("redo");
    const btn = $(this);

    $.ajax({
      url: "controllers/edit_project_ajax.php",
      type: "POST",
      data: {
        action: "update_image_redo",
        image_id: imageId,
        redo_value: redoValue,
      },
      success: function (response) {
        try {
          const data = JSON.parse(response);
          if (data.status === "success") {
            // Show only one toast message
            showToast(
              "success",
              redoValue === "1"
                ? "Image marked for redo"
                : "Redo status removed"
            );

            // Update the button appearance and data attribute
            if (data.new_redo_value === "1") {
              btn.removeClass("btn-warning").addClass("btn-danger");
              btn.find("i").removeClass("fa-redo-alt").addClass("fa-times");
              btn.data("redo", "0");
              btn.attr("data-redo", "0");
              btn.attr("title", "Cancel Redo Request");

              // Add redo badge to image row
              const row = btn.closest("tr");
              updateRedoUI(row, true);
            } else {
              btn.removeClass("btn-danger").addClass("btn-warning");
              btn.find("i").removeClass("fa-times").addClass("fa-redo-alt");
              btn.data("redo", "1");
              btn.attr("data-redo", "1");
              btn.attr("title", "Mark for Redo");

              // Remove redo badge from image row
              const row = btn.closest("tr");
              updateRedoUI(row, false);
            }

            // If this is in the main table, also find and update the corresponding row
            if (!btn.closest("#assignedImagesModal").length) {
              // In main table, we're done
              return;
            }

            // If we're in the modal, also update the main table row
            const mainTableRow = $(
              '#imagesTable tr[data-image-id="' + imageId + '"]'
            );
            if (mainTableRow.length) {
              updateRedoUI(mainTableRow, data.new_redo_value === "1");
            }
          } else {
            showToast("error", data.message);
          }
        } catch (e) {
          showToast("error", "Error processing server response");
        }
      },
      error: function () {
        showToast("error", "Server error while updating redo status");
      },
    });
  });

  // Modified JavaScript code to fix unassignment issue
  $(document).on("change", ".assignee-select", function () {
    const imageId = $(this).data("image-id");
    const userId = $(this).val();
    const row = $(this).closest("tr");
    const selectElement = $(this);

    // Get current assignee info
    const currentAssigneeText = selectElement.find("option:selected").text();
    const previousValue = selectElement.data("previous-value");
    const previousText = previousValue
      ? selectElement.find('option[value="' + previousValue + '"]').text()
      : "";

    // If selecting empty option (unassigning) and there was a previous value (meaning it was assigned)
    if (userId === "" && previousValue) {
      // Show confirmation dialog for unassigning - only once
      if (
        !confirm(
          "This image is already assigned to " +
            previousText +
            ". Do you want to reassign it to --Select Assignee--?"
        )
      ) {
        // User clicked Cancel, reset to previous value
        selectElement.val(previousValue);
        return;
      }
      // User confirmed unassigning - set previous value to empty to prevent future confirmation loops
      selectElement.data("previous-value", "");
      // Continue with unassigning the image
    } else if (userId) {
      // Check if selected user is in team assignments
      const isUserInTeam = checkIfUserInTeamAssignments(userId);
      if (!isUserInTeam) {
        showToast(
          "error",
          'The selected assignee does not exist in the TEAM ASSIGNMENT. Please use "Assign Selected" to create a new assignment.'
        );
        // Reset to previous value
        selectElement.val(previousValue);
        return;
      }
    }

    // If image is already assigned and user selected a different assignee (and not unassigning)
    if (previousValue && previousValue != userId && userId !== "") {
      // Show confirmation dialog
      if (
        !confirm(
          "This image is already assigned to " +
            previousText +
            ". Do you want to reassign it to " +
            currentAssigneeText +
            "?"
        )
      ) {
        // User clicked Cancel, reset to previous value
        selectElement.val(previousValue);
        return;
      }
    }

    // Store the current selection as previous value for next time
    selectElement.data("previous-value", userId);

    // Create data object for AJAX request
    let requestData = {
      action: "update_image_assignee",
      image_id: imageId,
      project_id: projectId,
    };

    // Only include user_id if it's not empty (unassignment)
    if (userId !== "") {
      requestData.user_id = userId;
    }

    $.ajax({
      url: "controllers/edit_project_ajax.php",
      type: "POST",
      data: requestData,
      success: function (response) {
        try {
          const data = JSON.parse(response);
          if (data.status === "success") {
            showToast("success", "Assignee updated successfully");
          } else {
            showToast("error", data.message);
            // Reset to previous value on error
            selectElement.val(previousValue);
          }
        } catch (e) {
          showToast("error", "Error processing server response");
          // Reset to previous value on error
          selectElement.val(previousValue);
        }
      },
      error: function () {
        showToast("error", "Server error while updating assignee");
        // Reset to previous value on error
        selectElement.val(previousValue);
      },
    });
  });

  // Store initial values of assignee selects when page loads
  $(document).ready(function () {
    $(".assignee-select").each(function () {
      $(this).data("previous-value", $(this).val());
    });
  });

  // Function to check if a user exists in team assignments
  function checkIfUserInTeamAssignments(userId) {
    let exists = false;
    $(".team-member-select option:selected").each(function () {
      if ($(this).val() == userId) {
        exists = true;
        return false; // Break the loop
      }
    });
    return exists;
  }

  // Fix for the TIME + ROLE sync bug
  // Store the current values to prevent resetting
  let lastTimeValues = {};
  let lastRoleValues = {};

  // Update image details (estimated time, role) - FIXED VERSION
  $(document).on("change", ".estimated-hours, .estimated-minutes", function () {
    const imageId = $(this).data("image-id");
    const row = $(this).closest("tr");

    // Get values from the row
    const hours = parseInt(row.find(".estimated-hours").val()) || 0;
    const minutes = parseInt(row.find(".estimated-minutes").val()) || 0;

    // Store time values
    lastTimeValues[imageId] = { hours, minutes };

    // Get the role value (either from our cache or the element)
    let role = "";
    if (row.find(".image-role-select").length) {
      role =
        lastRoleValues[imageId] || row.find(".image-role-select").val() || "";
    } else if (row.find(".role-select").length) {
      role = lastRoleValues[imageId] || row.find(".role-select").val() || "";
    }

    updateImageDetails(imageId, role, hours, minutes);
  });

  // Handle all role select changes in a single event handler
  $(document).on("change", ".image-role-select, .role-select", function () {
    const imageId = $(this).data("image-id");
    const role = $(this).val();
    const row = $(this).closest("tr");

    // Store role value
    lastRoleValues[imageId] = role;

    // Validate role when that's the changed element
    if (!role) {
      showToast("error", "Please select a role");
      return;
    }

    // Get time values (either from our cache or the elements)
    const timeValues = lastTimeValues[imageId] || {};
    const hours =
      timeValues.hours !== undefined
        ? timeValues.hours
        : parseInt(row.find(".estimated-hours").val()) || 0;
    const minutes =
      timeValues.minutes !== undefined
        ? timeValues.minutes
        : parseInt(row.find(".estimated-minutes").val()) || 0;

    updateImageDetails(imageId, role, hours, minutes);
  });

  // Function to update image details via AJAX
  function updateImageDetails(imageId, role, hours, minutes) {
    $.ajax({
      url: "controllers/edit_project_ajax.php",
      type: "POST",
      data: {
        action: "update_image_details",
        image_id: imageId,
        image_role: role,
        estimated_hours: hours,
        estimated_minutes: minutes,
      },
      success: function (response) {
        try {
          const data = JSON.parse(response);
          if (data.status === "success") {
            showToast("success", "Image details updated");

            // Make sure the UI reflects the saved values
            const row = $('tr[data-image-id="' + imageId + '"]');

            // Only update if values are different to avoid triggering change events
            const hourField = row.find(".estimated-hours");
            const minuteField = row.find(".estimated-minutes");
            const roleField = row.find(".role-select, .image-role-select");

            if (parseInt(hourField.val()) !== hours) {
              hourField.val(hours);
            }

            if (parseInt(minuteField.val()) !== minutes) {
              minuteField.val(minutes);
            }

            if (roleField.val() !== role) {
              roleField.val(role);
            }
          } else {
            showToast("error", data.message);
          }
        } catch (e) {
          showToast("error", "Error processing server response");
        }
      },
      error: function () {
        showToast("error", "Server error while updating image details");
      },
    });
  }

  // Image status timeline click functionality
  $(document).on("click", ".status-step", function () {
    const imageId = $(this).data("image-id");
    const newStatus = $(this).data("status");

    // Check if this is in the Project Images table or View Assigned Images modal
    const isInProjectImagesTable =
      $(this).closest("table").attr("id") === "imagesTable";

    if (isInProjectImagesTable) {
      // In the main Project Images table, allow status changes
      updateImageStatus(imageId, newStatus);
    } else {
      // In the assigned images view or other places, show the message about only artists can update
    }
  });

  // Approve image button click - this is always allowed for admins
  $(document).on("click", ".approve-image-btn", function () {
    const imageId = $(this).data("image-id");
    updateImageStatus(imageId, "completed");
  });

  // Function to update image status(can manipulate status from admin )--------------------------------------------------------------------------------------------------------
  // function updateImageStatus(imageId, newStatus) {
  //     $.ajax({
  //         url: 'controllers/edit_project_ajax.php',
  //         type: 'POST',
  //         data: {
  //             action: 'update_image_status',
  //             image_id: imageId,
  //             status: newStatus
  //         },
  //         success: function(response) {
  //             try {
  //                 const data = JSON.parse(response);
  //                 if (data.status === 'success') {
  //                     showToast('success', 'Image status updated to ' + newStatus);

  //                     // Update the UI
  //                     const row = $('tr[data-image-id="' + imageId + '"]');

  //                     // Update timeline visualization
  //                     const timelineSteps = ['available', 'assigned', 'in_progress', 'finish', 'completed'];
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

  //                     // Show/hide approve button
  //                     if (newStatus === 'finish') {
  //                         if (row.find('.approve-image-btn').length === 0) {
  //                             row.find('.status-timeline').after('<button type="button" class="btn btn-sm btn-success mt-2 approve-image-btn" data-image-id="' + imageId + '"><i class="fas fa-check-circle"></i> Approve</button>');
  //                         }
  //                     } else {
  //                         row.find('.approve-image-btn').remove();
  //                     }

  //                     // Check if all images with the same assignment are completed
  //                     // and update assignment status if needed
  //                     if (newStatus === 'completed') {
  //                         checkAssignmentCompletion(row.data('assignment-id'));
  //                     }
  //                 } else {
  //                     showToast('error', data.message);
  //                 }
  //             } catch (e) {
  //                 showToast('error', 'Error processing server response');
  //             }
  //         },
  //         error: function() {
  //             showToast('error', 'Server error while updating image status');
  //         }
  //     });
  // }

  // Function to check if all images are completed for an assignment
  // function checkAssignmentCompletion(assignmentId) {
  //     if (!assignmentId) return;

  //     $.ajax({
  //         url: 'controllers/edit_project_ajax.php',
  //         type: 'POST',
  //         data: {
  //             action: 'check_assignment_completion',
  //             assignment_id: assignmentId
  //         },
  //         success: function(response) {
  //             try {
  //                 const data = JSON.parse(response);
  //                 if (data.status === 'success' && data.all_completed) {
  //                     // If all images are completed, update the assignment's status in the UI
  //                     const assignmentRow = $('tr[data-assignment-id="' + assignmentId + '"]');
  //                     const timelineSteps = ['pending', 'in_progress', 'finish', 'qa', 'approved', 'completed'];

  //                     assignmentRow.find('.status-step').each(function(index) {
  //                         if (index <= timelineSteps.indexOf('completed')) {
  //                             $(this).addClass('active');
  //                             if (index > 0) {
  //                                 $(this).prev('.status-connector').addClass('active');
  //                             }
  //                         }

  //                         // Mark current step
  //                         if ($(this).data('status') === 'completed') {
  //                             $(this).addClass('current');
  //                         } else {
  //                             $(this).removeClass('current');
  //                         }
  //                     });

  //                     assignmentRow.find('.current-status').val('completed');

  //                     // Refresh project stats
  //                     if (typeof refreshProjectStats === 'function') {
  //                         refreshProjectStats();
  //                     }
  //                 }
  //             } catch (e) {
  //                 console.error('Error checking assignment completion', e);
  //             }
  //         }
  //     });
  // }

  // Helper function to show toast notifications
  function showToast(type, message) {
    let bgClass = "bg-info";

    switch (type) {
      case "success":
        bgClass = "bg-success";
        break;
      case "error":
        bgClass = "bg-danger";
        break;
      case "warning":
        bgClass = "bg-warning";
        break;
    }

    $(document).Toasts("create", {
      class: bgClass,
      title: type.charAt(0).toUpperCase() + type.slice(1),
      body: message,
      autohide: true,
      delay: 3000,
    });
  }

  // Image preview on selection
  $("#projectImages").change(function () {
    const files = this.files;
    $("#imagePreviewContainer").empty();

    safeLog("interaction", "Images selected for upload preview", {
      count: files.length,
    });

    for (let i = 0; i < files.length; i++) {
      const file = files[i];
      const reader = new FileReader();

      reader.onload = function (e) {
        const preview = `
                    <div class="col-md-3 mb-3">
                        <div class="card">
                            <img src="${e.target.result}" alt="Preview">
                            <div class="card-body p-2">
                                <small>${file.name}</small>
                            </div>
                        </div>
                    </div>
                `;
        $("#imagePreviewContainer").append(preview);
      };

      reader.readAsDataURL(file);
    }
  });

  // Handle the "Upload Images" button click
  $("#saveImages").on("click", function () {
    const files = $("#projectImages")[0].files;

    if (files.length === 0) {
      showToast("error", "Please select at least one image to upload");
      return;
    }

    // Create FormData object
    const formData = new FormData();
    formData.append("action", "upload_images");
    formData.append("project_id", projectId);

    // Add all selected files
    for (let i = 0; i < files.length; i++) {
      formData.append("images[]", files[i]);
    }

    // Show loading indicator
    const uploadBtn = $(this);
    const originalText = uploadBtn.html();
    uploadBtn.html('<i class="fas fa-spinner fa-spin"></i> Uploading...');
    uploadBtn.prop("disabled", true);

    // Send AJAX request
    $.ajax({
      url: "controllers/edit_project_ajax.php",
      type: "POST",
      data: formData,
      contentType: false,
      processData: false,
      success: function (response) {
        try {
          const data = JSON.parse(response);
          if (data.status === "success") {
            showToast(
              "success",
              data.message || "Images uploaded successfully"
            );

            // Reset form and close modal
            $("#uploadImagesForm")[0].reset();
            $("#imagePreviewContainer").empty();
            $("#addImagesModal").modal("hide");

            // Reload page to show new images
            window.location.reload();
          } else {
            showToast("error", data.message || "Error uploading images");
          }
        } catch (e) {
          console.error("Error parsing response:", e);
          showToast("error", "Error processing server response");
        }

        // Reset button state
        uploadBtn.html(originalText);
        uploadBtn.prop("disabled", false);
      },
      error: function (xhr, status, error) {
        console.error("AJAX error:", error);
        showToast("error", "Server error while uploading images");

        // Reset button state
        uploadBtn.html(originalText);
        uploadBtn.prop("disabled", false);
      },
    });
  });

  // Make sure batch actions appear when images are selected
  $(document).ready(function () {
    // Initialize batchActions visibility
    updateSelectedImages();

    // If no selected images, hide the actions
    if ($(".image-container.selected").length === 0) {
      $("#batchActions").hide();
    }
  });

  // Initialize search functionality
  $(document).ready(function () {
    // Add search functionality
    $("#imageTableSearch").on("keyup", function () {
      const searchText = $(this).val().toLowerCase().trim();

      $("#imagesTable tbody tr").each(function () {
        const $row = $(this);
        const fileName = $row
          .find("td:nth-child(2)")
          .text()
          .toLowerCase()
          .trim();

        // Show the row if the filename contains the search text
        // If search text is empty, show all rows
        const isMatch = searchText === "" || fileName.includes(searchText);

        $row.toggle(isMatch);
      });

      // Update select all checkbox state after filtering
      updateSelectAllCheckbox();
    });
  });

  // Function to update select all checkbox state
  function updateSelectAllCheckbox() {
    const visibleCheckboxes = $("#imagesTable tbody tr:visible .image-select");
    const checkedVisibleCheckboxes = $(
      "#imagesTable tbody tr:visible .image-select:checked"
    );

    $("#selectAllImages").prop(
      "checked",
      visibleCheckboxes.length > 0 &&
        visibleCheckboxes.length === checkedVisibleCheckboxes.length
    );
  }
});
