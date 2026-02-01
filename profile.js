// Tab switching
function showTab(tabName) {
    // Hide all tabs
    document.getElementById('postsTab').style.display = 'none';
    document.getElementById('mediaTab').style.display = 'none';
    document.getElementById('likesTab').style.display = 'none';

    // Remove active class from all buttons
    document.querySelectorAll('.tab-btn').forEach(btn => btn.classList.remove('active'));

    // Show selected tab
    if (tabName === 'posts') {
        document.getElementById('postsTab').style.display = 'block';
        document.querySelectorAll('.tab-btn')[0].classList.add('active');
    } else if (tabName === 'media') {
        document.getElementById('mediaTab').style.display = 'block';
        document.querySelectorAll('.tab-btn')[1].classList.add('active');
    } else if (tabName === 'likes') {
        document.getElementById('likesTab').style.display = 'block';
        document.querySelectorAll('.tab-btn')[2].classList.add('active');
    }
}

// Image lightbox
function openImageLightbox(src) {
    document.getElementById('lightboxImage').src = src;
    document.getElementById('imageLightbox').style.display = 'flex';
}

function closeLightbox() {
    document.getElementById('imageLightbox').style.display = 'none';
}

// Placeholder functions (will implement later)
function uploadProfilePicture(input) {
    alert('Profile picture upload coming in next step!');
}

function uploadCoverPhoto(input) {
    alert('Cover photo upload coming in next step!');
}

function toggleFollow(userId) {
    alert('Follow functionality coming in next step!');
}

function sendMessage(userId) {
    alert('Messaging feature coming in Phase 5!');
}

function showFollowers(userId) {
    alert('Followers list coming in next step!');
}

function showFollowing(userId) {
    alert('Following list coming in next step!');
}
