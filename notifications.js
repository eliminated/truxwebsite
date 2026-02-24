document.addEventListener('DOMContentLoaded', function () {
    // 1. SELECT ELEMENTS
    const notifBtn = document.getElementById('notifBtn');
    const notifDropdown = document.getElementById('notifDropdown');
    const notifList = document.getElementById('notifList');
    const notifCount = document.getElementById('notifCount');
    const markAllReadBtn = document.getElementById('markAllRead');

    // 2. TOGGLE DROPDOWN
    // We check if elements exist to prevent errors on pages without nav
    if (notifBtn && notifDropdown) {
        notifBtn.addEventListener('click', function (e) {
            e.preventDefault();
            e.stopPropagation();

            if (notifDropdown.style.display === 'block') {
                notifDropdown.style.display = 'none';
            } else {
                notifDropdown.style.display = 'block';
                loadNotifications(); // Fetch data when opening
            }
        });

        // Close when clicking outside
        document.addEventListener('click', function (e) {
            if (!notifDropdown.contains(e.target) && !notifBtn.contains(e.target)) {
                notifDropdown.style.display = 'none';
            }
        });
    }

    // 3. AUTO-CHECK NOTIFICATIONS (Every 30 seconds)
    checkUnreadCount();
    setInterval(checkUnreadCount, 30000);


    // --- FUNCTIONS ---
    function checkUnreadCount() {
        fetch('notifications.php?action=count')
            .then(response => response.json())
            .then(data => {
                if (data.success && notifCount) {
                    notifCount.textContent = data.unread_count > 99 ? '99+' : data.unread_count;
                    notifCount.style.display = data.unread_count > 0 ? 'block' : 'none';
                }
            })
            .catch(console.error);
    }

    function loadNotifications() {
        if (!notifList) return;

        notifList.innerHTML = '<div style="padding:10px; text-align:center;">Loading...</div>';

        fetch('notifications.php?action=fetch')
            .then(response => response.json())
            .then(data => {
                notifList.innerHTML = '';
                if (data.success && data.notifications.length > 0) {
                    data.notifications.forEach(n => {
                        notifList.appendChild(createNotificationItem(n));
                    });
                    // Reset badge since we opened the menu
                    if (notifCount) notifCount.style.display = 'none';
                } else {
                    notifList.innerHTML = '<div style="padding:15px; text-align:center; color:#999;">No notifications</div>';
                }
            })
            .catch(err => {
                console.error(err);
                notifList.innerHTML = '<div style="padding:10px; color:red; text-align:center;">Failed to load</div>';
            });
    }

    // RENDER ITEM
    function createNotificationItem(n) {
        const item = document.createElement('div');
        item.className = `notification-item ${n.is_read ? '' : 'unread'}`;

        let avatarHtml = n.actor_profile_pic
            ? `<div class="notif-avatar" style="background-image: url('${n.actor_profile_pic}');"></div>`
            : `<div class="notif-avatar default">${n.actor_username.charAt(0).toUpperCase()}</div>`;

        let contentHtml = '';

        // Follow Request Logic
        if (n.type === 'follow_request') {
            contentHtml = `
                <div class="notif-content">
                    <p><strong>${n.actor_username}</strong> requested to follow you.</p>
                    <div class="notif-time">${n.time_ago}</div>
                    ${n.is_pending ? `
                    <div class="request-actions">
                        <button class="btn-confirm" onclick="handleRequest(${n.actor_id}, 'approve', this)">Confirm</button>
                        <button class="btn-delete" onclick="handleRequest(${n.actor_id}, 'reject', this)">Delete</button>
                    </div>` : `<div style="font-size:12px; color:#999;">Processed</div>`}
                </div>`;
        } else {
            // Standard Notification
            let text = n.type === 'follow' ? 'started following you.' :
                n.type === 'comment' ? 'commented on your post.' :
                    n.type === 'reaction' ? 'liked your post.' : 'interacted with you.';

            contentHtml = `
                <div class="notif-content" onclick="window.location.href='profile.php?user=${n.actor_username}'" style="cursor:pointer;">
                    <p><strong>${n.actor_username}</strong> ${text}</p>
                    <div class="notif-time">${n.time_ago}</div>
                </div>`;
        }

        item.innerHTML = avatarHtml + contentHtml;
        return item;
    }
});

// GLOBAL HANDLER (Must be outside DOMContentLoaded)
function handleRequest(requesterId, action, btnElement) {
    event.stopPropagation();
    const parentDiv = btnElement.parentElement;
    parentDiv.innerHTML = '<span style="color:#666;">Processing...</span>';

    fetch('request_handler.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: `action=${action}&requester_id=${requesterId}`
    })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                parentDiv.innerHTML = action === 'approve'
                    ? '<span style="color:green; font-weight:bold;">Accepted</span>'
                    : '<span style="color:red;">Deleted</span>';
            } else {
                alert(data.message);
            }
        });
}

// PROFILE MENU TOGGLE
function toggleProfileMenu(event) {
    if (event) event.stopPropagation();

    const menu = document.getElementById('profileDropdown');
    const isVisible = menu.style.display === 'block';

    // Close all other dropdowns first
    document.querySelectorAll('.user-dropdown, .notification-dropdown, .post-dropdown').forEach(el => {
        el.style.display = 'none';
    });

    if (!isVisible) {
        menu.style.display = 'block';
    }
}

// Close profile menu when clicking outside
document.addEventListener('click', function (e) {
    // If click is NOT inside the user-profile div
    if (!e.target.closest('.user-profile')) {
        const profileDropdown = document.getElementById('profileDropdown');
        if (profileDropdown) {
            profileDropdown.style.display = 'none';
        }
    }
});