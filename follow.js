// truxwebsite/follow.js
function toggleFollow(userId) {
    const followBtn = document.getElementById('followBtn');
    if (!followBtn) return;

    // State: 0=None, 1=Following, 2=Requested
    const currentState = followBtn.dataset.following;

    // If Requested (2) or Following (1), we want to Unfollow.
    const action = (currentState === '0') ? 'follow' : 'unfollow';

    followBtn.disabled = true;
    const originalText = followBtn.textContent;
    followBtn.textContent = '...';

    console.log(`Sending ${action} request for user ${userId}...`);

    fetch('follow_handler.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: `action=${action}&user_id=${userId}`
    })
        .then(response => response.json())
        .then(data => {
            console.log("Server Response:", data); // Check your Console for this!

            if (data.success) {
                if (data.action === 'followed') {
                    updateButton(followBtn, 'Following', 'following', '1');
                }
                else if (data.action === 'requested') {
                    updateButton(followBtn, 'Requested', 'requested', '2');
                }
                else if (data.action === 'unfollowed') {
                    updateButton(followBtn, 'Follow', '', '0');
                }

                // Update count
                if (data.follower_count !== undefined) {
                    const countEl = document.querySelector('.stat-item:nth-child(2) .stat-number');
                    if (countEl) countEl.textContent = data.follower_count;
                }
            } else {
                alert(data.message);
                followBtn.textContent = originalText;
            }
        })
        .catch(error => {
            console.error('Fetch Error:', error);
            alert("Network error.");
            followBtn.textContent = originalText;
        })
        .finally(() => {
            followBtn.disabled = false;
        });
}

function updateButton(btn, text, className, state) {
    btn.textContent = text;
    // Reset classes first
    btn.className = 'btn-primary follow-btn';
    if (className) btn.classList.add(className);
    btn.dataset.following = state;
}