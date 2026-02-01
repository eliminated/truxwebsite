// User Search Functionality
const searchInput = document.getElementById('userSearchInput');
const searchResults = document.getElementById('searchResults');
const searchFilterPanel = document.getElementById('searchFilterPanel');
let searchTimeout;
let searchFilters = {
    verified: false,
    role: ''
};

// Debounced search function
function performSearch() {
    const query = searchInput.value.trim();

    if (query.length < 2) {
        searchResults.style.display = 'none';
        return;
    }

    // Build URL with filters
    const params = new URLSearchParams({
        query: query,
        verified: searchFilters.verified,
        role: searchFilters.role
    });

    fetch(`search_users.php?${params.toString()}`)
        .then(response => {
            // Check if response is OK
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            return response.text(); // Get as text first
        })
        .then(text => {
            try {
                const data = JSON.parse(text); // Parse manually
                displaySearchResults(data);
            } catch (e) {
                console.error('JSON Parse Error:', e);
                console.error('Response:', text);
                searchResults.innerHTML = '<div class="search-error">Search error occurred</div>';
                searchResults.style.display = 'block';
            }
        })
        .catch(error => {
            console.error('Search Error:', error);
            searchResults.innerHTML = '<div class="search-error">Something went wrong</div>';
            searchResults.style.display = 'block';
        });
}

// Display search results
function displaySearchResults(data) {
    if (!data.success) {
        searchResults.innerHTML = `<div class="search-error">${data.message}</div>`;
        searchResults.style.display = 'block';
        return;
    }

    if (data.users.length === 0) {
        searchResults.innerHTML = '<div class="search-empty">No users found</div>';
        searchResults.style.display = 'block';
        return;
    }

    let html = '';
    data.users.forEach(user => {
        const initial = user.username.charAt(0).toUpperCase();
        const avatarHtml = user.profile_picture
            ? `<div class="search-avatar" style="background-image: url('${user.profile_picture}'); background-size: cover; background-position: center;"></div>`
            : `<div class="search-avatar">${initial}</div>`;

        const verifiedBadge = user.is_verified
            ? '<span class="verified-check">✓</span>'
            : '';

        const roleBadge = getUserRoleBadgeHTML(user.user_role);

        const followBtn = user.is_following
            ? `<button class="search-follow-btn following" onclick="toggleFollowFromSearch(${user.id}, this, event)">Following</button>`
            : `<button class="search-follow-btn" onclick="toggleFollowFromSearch(${user.id}, this, event)">Follow</button>`;

        html += `
            <div class="search-result-item">
                <a href="profile.php?user=${encodeURIComponent(user.username)}" class="search-result-link">
                    ${avatarHtml}
                    <div class="search-info">
                        <div class="search-name">
                            <span class="search-username">${escapeHtml(user.username)}</span>
                            ${verifiedBadge}
                            ${roleBadge}
                        </div>
                        ${user.bio ? `<div class="search-bio">${escapeHtml(user.bio)}</div>` : ''}
                        <div class="search-followers">${user.follower_count} followers</div>
                    </div>
                </a>
                ${followBtn}
            </div>
        `;
    });

    searchResults.innerHTML = html;
    searchResults.style.display = 'block';
}

// Helper function to get role badge HTML
function getUserRoleBadgeHTML(role) {
    const badges = {
        'admin': '<span class="role-badge" style="background: #e74c3c;">👑 Admin</span>',
        'moderator': '<span class="role-badge" style="background: #f39c12;">🛡️ Moderator</span>',
        'developer': '<span class="role-badge" style="background: #9b59b6;">👨‍💻 Developer</span>'
    };
    return badges[role] || '';
}

// Escape HTML to prevent XSS
function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

// Search input event listener
searchInput.addEventListener('input', function () {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(performSearch, 300);
});

// Close search results when clicking outside
document.addEventListener('click', function (e) {
    if (!e.target.closest('.search-container')) {
        searchResults.style.display = 'none';
        searchFilterPanel.style.display = 'none';
    }
});

// Show/hide search filters
function showSearchFilters() {
    searchFilterPanel.style.display = searchFilterPanel.style.display === 'none' ? 'block' : 'none';
}

// Toggle search filter
function toggleSearchFilter(filterType, value) {
    if (filterType === 'verified') {
        searchFilters.verified = value;
        const btn = document.getElementById('filterVerified');
        if (value) {
            btn.classList.add('active');
            btn.textContent = '✓ Verified Only';
        } else {
            btn.classList.remove('active');
            btn.textContent = 'Verified Only';
        }
    } else if (filterType === 'role') {
        // Toggle role filter
        if (searchFilters.role === value) {
            searchFilters.role = '';
            document.querySelectorAll('.filter-role-btn').forEach(btn => btn.classList.remove('active'));
        } else {
            searchFilters.role = value;
            document.querySelectorAll('.filter-role-btn').forEach(btn => btn.classList.remove('active'));
            document.querySelector(`.filter-role-btn[data-role="${value}"]`).classList.add('active');
        }
    }

    // Re-perform search with new filters
    performSearch();
}

// Follow/unfollow from search
async function toggleFollowFromSearch(userId, button, event) {
    event.preventDefault();
    event.stopPropagation();

    if (button.disabled) return;

    button.disabled = true;
    const wasFollowing = button.classList.contains('following');

    try {
        const response = await fetch('follower_handler.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `action=${wasFollowing ? 'unfollow' : 'follow'}&user_id=${userId}`
        });

        const data = await response.json();

        if (data.success) {
            if (wasFollowing) {
                button.classList.remove('following');
                button.textContent = 'Follow';
            } else {
                button.classList.add('following');
                button.textContent = 'Following';
            }
        } else {
            alert(data.message || 'Something went wrong');
        }
    } catch (error) {
        console.error('Follow Error:', error);
        alert('Failed to update follow status');
    } finally {
        button.disabled = false;
    }
}
