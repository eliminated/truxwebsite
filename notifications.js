// Notification System
let notificationInterval;

// Initialize notifications on page load
document.addEventListener('DOMContentLoaded', function () {
    updateNotificationCount();
    startNotificationPolling();
});

// Toggle notification dropdown
function toggleNotifications() {
    const dropdown = document.getElementById('notificationDropdown');
    const isVisible = dropdown.classList.contains('show');

    if (!isVisible) {
        dropdown.classList.add('show');
        loadNotifications();
    } else {
        dropdown.classList.remove('show');
    }
}

// Load notifications
async function loadNotifications() {
    const listElement = document.getElementById('notificationList');
    listElement.innerHTML = '<div class="notification-loading">Loading...</div>';

    try {
        const response = await fetch('notifications.php?action=fetch');
        const data = await response.json();

        if (data.success && data.notifications.length > 0) {
            let html = '';
            data.notifications.forEach(notif => {
                html += renderNotification(notif);
            });
            listElement.innerHTML = html;
        } else {
            listElement.innerHTML = '<div class="notification-empty">No notifications yet</div>';
        }
    } catch (error) {
        console.error('Error loading notifications:', error);
        listElement.innerHTML = '<div class="notification-empty">Failed to load notifications</div>';
    }
}

// Render a single notification
function renderNotification(notif) {
    const unreadClass = notif.is_read ? '' : 'unread';
    const initial = notif.actor_username.charAt(0).toUpperCase();
    const avatarHtml = notif.actor_profile_pic
        ? `<div class="notification-avatar" style="background-image: url('${notif.actor_profile_pic}'); background-size: cover;"></div>`
        : `<div class="notification-avatar">${initial}</div>`;

    let message = '';
    let link = `profile.php?user=${encodeURIComponent(notif.actor_username)}`;

    switch (notif.type) {
        case 'follow':
            message = `<strong>${notif.actor_username}</strong> started following you`;
            break;
        case 'follow_request':
            message = `<strong>${notif.actor_username}</strong> requested to follow you`;
            link = 'profile.php'; // Link to pending requests
            break;
        case 'follow_accepted':
            message = `<strong>${notif.actor_username}</strong> accepted your follow request`;
            break;
        case 'comment':
            message = `<strong>${notif.actor_username}</strong> commented on your post`;
            link = `index.php#post-${notif.post_id}`;
            break;
        case 'reaction':
            message = `<strong>${notif.actor_username}</strong> reacted to your post`;
            link = `index.php#post-${notif.post_id}`;
            break;
    }

    return `
        <div class="notification-item ${unreadClass}" onclick="handleNotificationClick(${notif.id}, '${link}')">
            ${avatarHtml}
            <div class="notification-content">
                <div class="notification-text">${message}</div>
                <div class="notification-time">${notif.time_ago}</div>
            </div>
        </div>
    `;
}

// Handle notification click
async function handleNotificationClick(notificationId, link) {
    // Mark as read
    await markAsRead(notificationId);

    // Navigate to link
    window.location.href = link;
}

// Mark single notification as read
async function markAsRead(notificationId) {
    try {
        const formData = new FormData();
        formData.append('notification_id', notificationId);

        await fetch('notifications.php?action=mark_read', {
            method: 'POST',
            body: formData
        });

        updateNotificationCount();
    } catch (error) {
        console.error('Error marking notification as read:', error);
    }
}

// Mark all as read
async function markAllAsRead() {
    try {
        await fetch('notifications.php?action=mark_all_read', {
            method: 'POST'
        });

        // Reload notifications
        loadNotifications();
        updateNotificationCount();
    } catch (error) {
        console.error('Error marking all as read:', error);
    }
}

// Update notification count badge
async function updateNotificationCount() {
    try {
        const response = await fetch('notifications.php?action=count');
        const data = await response.json();

        if (data.success) {
            const badge = document.getElementById('notificationBadge');
            if (data.unread_count > 0) {
                badge.textContent = data.unread_count > 99 ? '99+' : data.unread_count;
                badge.style.display = 'block';
            } else {
                badge.style.display = 'none';
            }
        }
    } catch (error) {
        console.error('Error updating notification count:', error);
    }
}

// Start polling for new notifications every 30 seconds
function startNotificationPolling() {
    notificationInterval = setInterval(() => {
        updateNotificationCount();
    }, 30000); // 30 seconds
}

// Close notification dropdown when clicking outside
document.addEventListener('click', function (e) {
    const dropdown = document.getElementById('notificationDropdown');
    const bell = document.querySelector('.notification-bell');

    if (dropdown && !bell.contains(e.target)) {
        dropdown.classList.remove('show');
    }
});


// Profile dropdown toggle
function toggleProfileMenu(event) {
    event.stopPropagation();
    const dropdown = document.getElementById('profileDropdown');
    dropdown.classList.toggle('show');
}

// Close profile dropdown when clicking outside
document.addEventListener('click', function (e) {
    const profileDropdown = document.getElementById('profileDropdown');
    const profileMenu = document.querySelector('.user-profile');

    if (profileDropdown && !profileMenu.contains(e.target)) {
        profileDropdown.classList.remove('show');
    }
});

// Close all dropdowns when clicking outside
document.addEventListener('click', function (e) {
    // Close notification dropdown
    const notifDropdown = document.getElementById('notificationDropdown');
    const notifBell = document.querySelector('.notification-bell');
    if (notifDropdown && !notifBell.contains(e.target)) {
        notifDropdown.classList.remove('show');
    }

    // Close profile dropdown
    const profileDropdown = document.getElementById('profileDropdown');
    const profileMenu = document.querySelector('.user-profile');
    if (profileDropdown && !profileMenu.contains(e.target)) {
        profileDropdown.classList.remove('show');
    }
});
