// Image Upload Handler
let currentUploadType = '';

function openImageUpload(type) {
    currentUploadType = type;

    // Create file input
    const input = document.createElement('input');
    input.type = 'file';
    input.accept = 'image/jpeg,image/jpg,image/png,image/gif';

    input.onchange = function (e) {
        const file = e.target.files[0];
        if (file) {
            // Validate file size (5MB)
            if (file.size > 5 * 1024 * 1024) {
                alert('File too large! Maximum size is 5MB.');
                return;
            }

            // Show preview and crop modal
            showImagePreview(file, type);
        }
    };

    input.click();
}

function showImagePreview(file, type) {
    const reader = new FileReader();

    reader.onload = function (e) {
        const imageUrl = e.target.result;

        // Create modal
        const modal = document.createElement('div');
        modal.className = 'image-crop-modal';
        modal.innerHTML = `
            <div class="modal-content">
                <div class="modal-header">
                    <h3>Adjust Your ${type === 'profile' ? 'Profile Picture' : 'Cover Photo'}</h3>
                    <button class="modal-close" onclick="closeImageModal()">&times;</button>
                </div>
                <div class="modal-body">
                    <div class="image-preview-container ${type === 'cover' ? 'cover-preview' : 'profile-preview'}">
                        <img src="${imageUrl}" id="previewImage" alt="Preview">
                    </div>
                    <div class="crop-info">
                        <p>📷 Preview how your ${type === 'profile' ? 'profile picture' : 'cover photo'} will look</p>
                        <p class="help-text">You can upload a different image or click Upload to continue</p>
                    </div>
                </div>
                <div class="modal-footer">
                    <button class="btn-secondary" onclick="closeImageModal()">Cancel</button>
                    <button class="btn-primary" onclick="uploadImage()">
                        <span class="upload-text">Upload Image</span>
                        <span class="upload-loading" style="display:none;">Uploading...</span>
                    </button>
                </div>
            </div>
        `;

        document.body.appendChild(modal);

        // Store file for upload
        window.currentImageFile = file;

        // Show modal with animation
        setTimeout(() => modal.classList.add('show'), 10);
    };

    reader.readAsDataURL(file);
}

function closeImageModal() {
    const modal = document.querySelector('.image-crop-modal');
    if (modal) {
        modal.classList.remove('show');
        setTimeout(() => modal.remove(), 300);
    }
}

function uploadImage() {
    if (!window.currentImageFile) {
        alert('No image selected');
        return;
    }

    const uploadBtn = document.querySelector('.modal-footer .btn-primary');
    const uploadText = uploadBtn.querySelector('.upload-text');
    const uploadLoading = uploadBtn.querySelector('.upload-loading');

    // Show loading state
    uploadBtn.disabled = true;
    uploadText.style.display = 'none';
    uploadLoading.style.display = 'inline';

    // Create form data
    const formData = new FormData();
    formData.append('image', window.currentImageFile);
    formData.append('upload_type', currentUploadType);

    // Upload to server
    fetch('upload_image.php', {
        method: 'POST',
        body: formData
    })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('✓ Image uploaded successfully!');
                closeImageModal();

                // Reload page to show new image
                window.location.reload();
            } else {
                alert('Error: ' + data.message);
                uploadBtn.disabled = false;
                uploadText.style.display = 'inline';
                uploadLoading.style.display = 'none';
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Upload failed. Please try again.');
            uploadBtn.disabled = false;
            uploadText.style.display = 'inline';
            uploadLoading.style.display = 'none';
        });
}
