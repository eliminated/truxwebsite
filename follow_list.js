// Follow List Modal Functions

function showFollowers(userId) {
    openFollowModal('followers', userId);
}

function showFollowing(userId) {
    openFollowModal('following', userId);
}

function openFollowModal(type, userId) {
    const modal = document.getElementById('followListModal');
    const modalTitle = document.getElementById('followModalTitle');
    const userList = document.getElementById('followUserList');

    // Set title
    modalTitle.textContent = type === 'followers' ? 'Followers' : 'Following';

    // Show modal
    modal.style.display = 'flex';

    // Show loading state
    userList.innerHTML = '<div class="follow-list-loading">Loading...</div>';

    // Fetch user list
    fetch(`get_follow_list.php?user_id=${userId}&type=${type}`)
        .then(response => response.json())
        .then(data => {
            if (data.success && data.users.length > 0) {
                displayUserList(data.users);
            } else if (data.success && data.users.length === 0) {
                userList.innerHTML = `<div class="follow-list-empty">No ${type} yet</div>`;
            } else {
                userList.innerHTML = `<div class="follow-list-error">Failed to load ${type}</div>`;
            }
        })
        .catch(error => {
            console.error('Error:', error);
            userList.innerHTML = '<div class="follow-list-error">Something went wrong</div>';
        });
}

function displayUserList(users) {
    const userList = document.getElementById('followUserList');
    userList.innerHTML = '';

    users.forEach(user => {
        const userItem = document.createElement('div');
        userItem.className = 'follow-list-item';

        // Profile picture or initial
        const profilePic = user.profile_picture
            ? `<div class="follow-list-avatar" style="background-image: url('${user.profile_picture}'); background-size: cover; background-position: center;"></div>`
            : `<div class="follow-list-avatar"><span>${user.username.charAt(0).toUpperCase()}</span></div>`;

        // Verified badge
        const verifiedBadge = user.is_verified
            ? '<span class="verified-badge" title="Verified">✓</span>'
            : '';

        // Role badge
        let roleBadge = '';
        if (user.user_role === 'admin') {
            roleBadge = '<span class="role-badge admin-badge" title="Admin">🛡️ Admin</span>';
        } else if (user.user_role === 'moderator') {
            roleBadge = '<span class="role-badge mod-badge" title="Moderator">⚔️ Moderator</span>';
        } else if (user.user_role === 'developer') {
            roleBadge = '<span class="role-badge dev-badge" title="Developer">👨‍💻 Developer</span>';
        }

        // Bio
        const bio = user.bio
            ? `<p class="follow-list-bio">${escapeHtml(user.bio.substring(0, 60))}${user.bio.length > 60 ? '...' : ''}</p>`
            : '';

        // Follow button (don't show for current user)
        let followButton = '';
        if (!user.is_current_user) {
            const buttonClass = user.is_following ? 'follow-list-btn following' : 'follow-list-btn';
            const buttonText = user.is_following ? 'Following' : 'Follow';
            followButton = `<button class="${buttonClass}" 
                                    data-user-id="${user.id}" 
                                    data-following="${user.is_following ? '1' : '0'}"
                                    onclick="toggleFollowInModal(${user.id}, this)">
                                ${buttonText}
                            </button>`;
        }

        userItem.innerHTML = `
            ${profilePic}
            <div class="follow-list-info">
                <div class="follow-list-name">
                    <a href="profile.php?user=${user.username}" class="follow-list-username">
                        ${escapeHtml(user.username)}
                    </a>
                    ${verifiedBadge}
                    ${roleBadge}
                </div>
                ${bio}
            </div>
            <div class="follow-list-action">
                ${followButton}
            </div>
        `;

        userList.appendChild(userItem);
    });
}

function toggleFollowInModal(userId, button) {
    const isFollowing = button.getAttribute('data-following') === '1';
    const action = isFollowing ? 'unfollow' : 'follow';

    // Disable button during request
    button.disabled = true;
    const originalText = button.textContent;
    button.textContent = 'Loading...';

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
                    button.textContent = 'Following';
                    button.classList.add('following');
                    button.setAttribute('data-following', '1');
                } else {
                    button.textContent = 'Follow';
                    button.classList.remove('following');
                    button.setAttribute('data-following', '0');
                }
            } else {
                alert(data.message);
                button.textContent = originalText;
            }
            button.disabled = false;
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Something went wrong. Please try again.');
            button.textContent = originalText;
            button.disabled = false;
        });
}

function closeFollowModal() {
    const modal = document.getElementById('followListModal');
    modal.style.display = 'none';
}

// Helper function to escape HTML
function escapeHtml(text) {
    const map = {
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#039;'
    };
    return text.replace(/[&<>"']/g, m => map[m]);
}

// Close modal when clicking outside
window.addEventListener('click', function (event) {
    const modal = document.getElementById('followListModal');
    if (event.target === modal) {
        closeFollowModal();
    }
});
