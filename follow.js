// Follow/Unfollow functionality
function toggleFollow(userId) {
    const followBtn = document.getElementById('followBtn');
    if (!followBtn) return;

    const isFollowing = followBtn.getAttribute('data-following') === '1';
    const action = isFollowing ? 'unfollow' : 'follow';

    // Disable button during request
    followBtn.disabled = true;
    const originalText = followBtn.textContent;
    followBtn.textContent = 'Loading...';

    // Send AJAX request
    fetch('follow_handler.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `action=${action}&user_id=${userId}`
    })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Update button state
                if (data.action === 'followed') {
                    followBtn.textContent = 'Following';
                    followBtn.classList.add('following');
                    followBtn.setAttribute('data-following', '1');
                } else {
                    followBtn.textContent = 'Follow';
                    followBtn.classList.remove('following');
                    followBtn.setAttribute('data-following', '0');
                }

                // Update follower count
                if (data.follower_count !== undefined) {
                    const followerCount = document.querySelector('.stat-item:nth-child(2) .stat-number');
                    if (followerCount) {
                        followerCount.textContent = data.follower_count;
                    }
                }

                // Show success message (optional)
                showNotification(data.message, 'success');
            } else {
                alert(data.message);
                followBtn.textContent = originalText;
            }

            followBtn.disabled = false;
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Something went wrong. Please try again.');
            followBtn.textContent = originalText;
            followBtn.disabled = false;
        });
}

// Simple notification function
function showNotification(message, type = 'info') {
    // Create notification element
    const notification = document.createElement('div');
    notification.className = `notification notification-${type}`;
    notification.textContent = message;

    // Add to page
    document.body.appendChild(notification);

    // Show notification
    setTimeout(() => notification.classList.add('show'), 100);

    // Remove after 3 seconds
    setTimeout(() => {
        notification.classList.remove('show');
        setTimeout(() => notification.remove(), 300);
    }, 3000);
}
