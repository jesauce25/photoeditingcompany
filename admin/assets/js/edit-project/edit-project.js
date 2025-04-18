// Optimize image handling functions
function addImages(personIndex) {
  $("#addImagesModal").modal("show");
  $("#addImagesModal").data("personIndex", personIndex);
  $("#imagePreview").empty();
  $("#imageUpload").val("");
}

// Optimize image upload preview
$("#imageUpload").change(function () {
  const files = this.files;
  const preview = $("#imagePreview");
  preview.empty();

  Array.from(files).forEach((file, i) => {
    const reader = new FileReader();
    reader.onload = function (e) {
      preview.append(`
            <div class="col-md-4 mb-3">
                <div class="card">
                    <img src="${e.target.result}" class="card-img-top" alt="Preview">
                    <div class="card-footer p-2">
                        <input type="text" class="form-control form-control-sm" 
                               placeholder="Image caption">
                    </div>
                </div>
            </div>
        `);
    };
    reader.readAsDataURL(file);
  });
});
