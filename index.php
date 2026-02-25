<?php
require_once "config.php";
require_once "includes/badge_functions.php";


// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}


$current_user_id = $_SESSION['user_id'];
$current_username = $_SESSION["username"];
// Fetch current user's profile picture
$current_user_profile_pic = '';
$profile_sql = "SELECT profile_picture FROM users WHERE id = ?";
if ($stmt = mysqli_prepare($conn, $profile_sql)) {
    mysqli_stmt_bind_param($stmt, "i", $current_user_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    if ($row = mysqli_fetch_assoc($result)) {
        $current_user_profile_pic = $row['profile_picture'];
    }
    mysqli_stmt_close($stmt);
}




// Fetch posts with reaction counts and user's reaction
// Modified posts query
$posts_query = "
    SELECT 
        p.*,
        u.username,
        u.profile_picture,
        u.is_verified,
        u.user_role,
        COALESCE(r.reaction_count, 0) as reaction_count,
        COALESCE(c.comment_count, 0) as comment_count,
        ur.reaction_type as user_reaction,
        CASE WHEN sp.user_id IS NOT NULL THEN 1 ELSE 0 END as is_saved
    FROM posts p
    LEFT JOIN users u ON p.user_id = u.id
    LEFT JOIN (
        SELECT post_id, COUNT(*) as reaction_count 
        FROM reactions 
        GROUP BY post_id
    ) r ON p.post_id = r.post_id
    LEFT JOIN (
        SELECT post_id, COUNT(*) as comment_count 
        FROM comments 
        GROUP BY post_id
    ) c ON p.post_id = c.post_id
    LEFT JOIN reactions ur ON p.post_id = ur.post_id AND ur.user_id = ?
    LEFT JOIN saved_posts sp ON p.post_id = sp.post_id AND sp.user_id = ?
    ORDER BY p.created_at DESC
";

// Execute as prepared statement
$stmt = mysqli_prepare($conn, $posts_query);
mysqli_stmt_bind_param($stmt, "ii", $current_user_id, $current_user_id);
mysqli_stmt_execute($stmt);
$posts_result = mysqli_stmt_get_result($stmt);
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Home - TruX</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <!-- Custom Context Menu -->
    <div id="customContextMenu" class="context-menu" style="display:none;">
        <ul id="contextMenuItems"></ul>
    </div>

    <!-- Reaction Popup -->
    <div id="reactionPopup" class="reaction-popup" style="display:none;">
        <div class="reaction-option" data-reaction="like">👍 Like</div>
        <div class="reaction-option" data-reaction="love">❤️ Love</div>
        <div class="reaction-option" data-reaction="haha">😂 Haha</div>
        <div class="reaction-option" data-reaction="wow">😮 Wow</div>
        <div class="reaction-option" data-reaction="sad">😢 Sad</div>
        <div class="reaction-option" data-reaction="angry">😡 Angry</div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div id="deleteModal" class="modal">
        <div class="modal-content">
            <h3>Delete Post?</h3>
            <p>This action cannot be undone.</p>
            <div class="modal-buttons">
                <button class="btn-cancel" onclick="closeDeleteModal()">Cancel</button>
                <button class="btn-delete" onclick="confirmDelete()">Delete</button>
            </div>
        </div>
    </div>

    <!-- Edit Post Modal -->
    <div id="editModal" class="modal">
        <div class="modal-content edit-modal">
            <div class="modal-header">
                <h3>Edit Post</h3>
                <button class="close-btn" onclick="closeEditModal()">&times;</button>
            </div>
            <form id="editPostForm">
                <input type="hidden" id="editPostId">
                <textarea id="editPostContent" maxlength="5000" placeholder="What's on your mind?"></textarea>
                <div class="char-counter">
                    <span id="editCharCount">0</span>/5000 characters
                </div>
                <div class="modal-buttons">
                    <button type="button" class="btn-cancel" onclick="closeEditModal()">Cancel</button>
                    <button type="submit" class="btn-primary">Save Changes</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Comment Modal -->
    <div id="commentModal" class="modal">
        <div class="modal-content comment-modal">
            <div class="modal-header">
                <h3>Comments</h3>
                <button class="close-btn" onclick="closeCommentModal()">&times;</button>
            </div>
            <div id="commentsContainer" class="comments-container"></div>
            <form id="addCommentForm" class="add-comment-form">
                <input type="hidden" id="commentPostId">
                <input type="hidden" id="commentParentId" value="">
                <textarea id="commentText" placeholder="Write a comment..." rows="2" maxlength="1000"></textarea>
                <button type="submit" class="btn-primary">Post Comment</button>
            </form>
        </div>
    </div>

    <!-- Navigation -->
    <nav class="navbar">
        <div class="nav-container">
            <div class="nav-left">
                <a href="index.php" class="logo">TruX</a>
                <a href="index.php" class="nav-link">Home</a>
                <a href="community.php" class="nav-link">Community</a>
                <a href="chat.php" class="nav-link">Chat</a>
                <a href="support.php" class="nav-link">Supports</a>
            </div>
        
            <!-- Search Bar -->
            <div class="search-container">
                <div class="search-input-wrapper">
                    <span class="search-icon">🔍</span>
                    <input type="text" 
                           id="userSearchInput" 
                           class="search-input" 
                           placeholder="Search users..." 
                           autocomplete="off">
                    <button class="search-filter-btn" onclick="showSearchFilters()" title="Filters">
                        ⚙️
                    </button>
                </div>
            
                <!-- Search Filters Panel -->
                <div id="searchFilterPanel" class="search-filter-panel" style="display: none;">
                    <div class="filter-section">
                        <label class="filter-label">Filters</label>
                        <button id="filterVerified" 
                                class="filter-btn" 
                                onclick="toggleSearchFilter('verified', !searchFilters.verified)">
                            ✓ Verified Only
                        </button>
                    </div>
                    <div class="filter-section">
                        <label class="filter-label">Role</label>
                        <button class="filter-role-btn" data-role="admin" onclick="toggleSearchFilter('role', 'admin')">
                            🛡️ Admin
                        </button>
                        <button class="filter-role-btn" data-role="moderator" onclick="toggleSearchFilter('role', 'moderator')">
                            ⚔️ Moderator
                        </button>
                        <button class="filter-role-btn" data-role="developer" onclick="toggleSearchFilter('role', 'developer')">
                            👨‍💻 Developer
                        </button>
                    </div>
                </div>
            
                <!-- Search Results Dropdown -->
                <div id="searchResults" class="search-results"></div>
            </div>

            <div class="nav-right">
                <!-- Notification Bell -->
                <div class="notification-bell">
                    <button class="bell-btn" onclick="toggleNotifications()">
                        <span class="bell-icon">🔔</span>
                        <span class="notification-badge" id="notificationBadge" style="display: none;">0</span>
                    </button>
        
<<<<<<< Updated upstream
                    <!-- Notification Dropdown -->
                    <div class="notification-dropdown" id="notificationDropdown">
=======
                    <div class="notification-dropdown" id="notifDropdown">
>>>>>>> Stashed changes
                        <div class="notification-header">
                            <h3>Notifications</h3>
                            <button class="mark-all-read-btn" onclick="markAllAsRead()">Mark all read</button>
                        </div>
                        <div class="notification-list" id="notificationList">
                            <div class="notification-loading">Loading...</div>
                        </div>
                    </div>
                </div>
    
                <!-- Existing User Profile Dropdown -->
                <div class="user-profile" onclick="toggleProfileMenu(event)">
                    <?php if (!empty($current_user_profile_pic)): ?>
                        <div class="username-display" style="background-image: url('<?php echo htmlspecialchars($current_user_profile_pic); ?>'); background-size: cover; background-position: center;"></div>
                    <?php else: ?>
                        <div class="username-display">
                            <span class="username-initial"><?php echo strtoupper(substr($current_username, 0, 1)); ?></span>
                        </div>
                    <?php endif; ?>
                    <div class="user-dropdown" id="profileDropdown">
                        <a href="profile.php">My Profile</a>
                        <a href="settings.php">Settings</a>
                        <a href="logout.php">Logout</a>
                    </div>
                </div>



            </div>

        </div>
    </nav>


    <!-- Main Content -->
    <div class="container">
        <div class="content-wrapper">
            <!-- Create Post Section -->
            <div class="create-post-card">
                <h3>What's on your mind?</h3>
                <form id="postForm" enctype="multipart/form-data">
                    <textarea id="postContent" name="content" placeholder="Share your thoughts... (max 5000 characters)" maxlength="5000"></textarea>
                    <div class="char-counter">
                        <span id="charCount">0</span>/5000 characters
                    </div>
                    
                    <div class="post-options">
                        <div class="post-buttons">
                            <button type="button" class="option-btn" onclick="document.getElementById('imageUpload').click()">
                                <span>📷</span> Photo
                            </button>
                            <button type="button" class="option-btn" onclick="document.getElementById('videoUpload').click()">
                                <span>🎥</span> Video
                            </button>
                            <button type="button" class="option-btn" onclick="toggleLinkInput()">
                                <span>🔗</span> Link
                            </button>
                            <button type="button" class="option-btn" onclick="toggleCodeInput()">
                                <span>💻</span> Code
                            </button>
                        </div>
                        <input type="file" id="imageUpload" name="image" accept="image/jpeg,image/jpg,image/png,image/gif" style="display:none" onchange="handleFileUpload(this, 'image')">
                        <input type="file" id="videoUpload" name="video" accept="video/mp4,video/webm,video/mov" style="display:none" onchange="handleFileUpload(this, 'video')">
                    </div>

                    <div id="linkInputContainer" style="display:none;" class="additional-input">
                        <input type="url" id="linkInput" name="link" placeholder="Enter URL...">
                    </div>

                    <div id="codeInputContainer" style="display:none;" class="additional-input">
                        <select id="codeLanguage" name="codeLanguage">
                            <option value="javascript">JavaScript</option>
                            <option value="python">Python</option>
                            <option value="php">PHP</option>
                            <option value="html">HTML</option>
                            <option value="css">CSS</option>
                            <option value="sql">SQL</option>
                            <option value="java">Java</option>
                            <option value="csharp">C#</option>
                        </select>
                        <textarea id="codeSnippet" name="codeSnippet" placeholder="Paste your code here..."></textarea>
                    </div>

                    <div id="previewArea" class="preview-area"></div>

                    <button type="submit" class="btn-submit">Post</button>
                    <div id="postMessage" class="post-message"></div>
                </form>
            </div>

            <!-- Posts Feed -->
            <div id="postsFeed" class="posts-feed">
                <?php
                if (mysqli_num_rows($posts_result) > 0) {
                    while ($post = mysqli_fetch_assoc($posts_result)) {
                        $first_letter = strtoupper(substr($post['username'], 0, 1));
                        $post_time = date('d/m/Y, g:i:s a', strtotime($post['created_at']));
                        $is_own_post = ($post['user_id'] == $current_user_id);
                        $is_pinned = $post['is_pinned'] == 1;
                        $is_saved = $post['is_saved'] > 0;

                        echo '<div class="post-card' . ($is_pinned ? ' pinned-post' : '') . '" data-post-id="' . $post['post_id'] . '" data-user-id="' . $post['user_id'] . '" data-username="' . htmlspecialchars($post['username']) . '">';

                        echo '<div class="post-header">';
                        // Profile picture or initial
                        if (!empty($post['profile_picture'])) {
                            echo '<a href="profile.php?user=' . urlencode($post['username']) . '" class="post-avatar-link">';
                            echo '<div class="post-avatar" style="background-image: url(\'' . htmlspecialchars($post['profile_picture']) . '\'); background-size: cover; background-position: center;"></div>';
                            echo '</a>';
                        } else {
                            echo '<a href="profile.php?user=' . urlencode($post['username']) . '" class="post-avatar-link">';
                            echo '<div class="post-avatar">' . $first_letter . '</div>';
                            echo '</a>';
                        }
                        echo '<div class="post-info">';

                        echo '<a href="profile.php?user=' . urlencode($post['username']) . '" class="post-author-link">';
                        echo '<div class="post-author-wrapper">';
                        echo '<div class="post-author" data-username="' . htmlspecialchars($post['username']) . '">' . htmlspecialchars($post['username']) . '</div>';
                        echo displayVerifiedCheck($post['is_verified']);
                        echo displayRoleBadge(getUserRoleBadge($post['user_role']));
                        echo '</div>';
                        echo '</a>';
                        echo '<div class="post-time">' . $post_time;

                        // Show simple edited badge if post was edited
                        if (!empty($post['edited_at'])) {
                            echo ' <span class="edited-badge" title="Edited">✏️ Edited</span>';
                        }

                        echo '</div>';

                        echo '</div>';


                        if ($is_own_post) {
                            echo '<div class="post-menu">';
                            echo '<button class="menu-btn" onclick="togglePostMenu(' . $post['post_id'] . ')">⋮</button>';
                            echo '<div class="post-dropdown" id="menu-' . $post['post_id'] . '">';
                            echo '<a href="#" onclick="editPost(' . $post['post_id'] . ', event)">✏️ Edit</a>';
                            // PIN FUNCTION REMOVED - WILL BE IMPLEMETING INTO PROFILE SECTION
                            echo '<a href="#" onclick="deletePost(' . $post['post_id'] . ', event)" class="danger">🗑️ Delete</a>';
                            echo '</div>';
                            echo '</div>';
                        }

                        echo '</div>';

                        if (!empty($post['content'])) {
                            echo '<div class="post-content" data-content="' . htmlspecialchars($post['content']) . '">' . nl2br(htmlspecialchars($post['content'])) . '</div>';
                        }

                        // Display media
                        if ($post['media_type'] == 'image' && !empty($post['media_path'])) {
                            echo '<div class="post-media"><img src="' . htmlspecialchars($post['media_path']) . '" alt="Post image" onclick="openImageLightbox(this.src)"></div>';
                        } elseif ($post['media_type'] == 'video' && !empty($post['media_path'])) {
                            echo '<div class="post-media"><video src="' . htmlspecialchars($post['media_path']) . '" controls></video></div>';
                        } elseif ($post['media_type'] == 'link' && !empty($post['link_url'])) {
                            echo '<a href="' . htmlspecialchars($post['link_url']) . '" target="_blank" class="post-link">🔗 ' . htmlspecialchars($post['link_url']) . '</a>';
                        } elseif ($post['media_type'] == 'code' && !empty($post['code_snippet'])) {
                            echo '<div class="post-code">';
                            echo '<span class="code-language">' . htmlspecialchars($post['code_language']) . '</span>';
                            echo '<pre>' . htmlspecialchars($post['code_snippet']) . '</pre>';
                            echo '</div>';
                        }

                        // Interaction buttons
                        echo '<div class="post-actions">';

                        // Reaction button
                        $user_reaction = $post['user_reaction'];
                        $reaction_icons = [
                            'like' => '👍',
                            'love' => '❤️',
                            'haha' => '😂',
                            'wow' => '😮',
                            'sad' => '😢',
                            'angry' => '😡'
                        ];
                        $reaction_icon = $user_reaction ? $reaction_icons[$user_reaction] : '👍';
                        $reaction_class = $user_reaction ? ' reacted' : '';

                        echo '<button class="action-btn reaction-btn' . $reaction_class . '" data-post-id="' . $post['post_id'] . '" onmouseenter="showReactionPopup(this)" onmouseleave="hideReactionPopup()">';
                        echo '<span class="reaction-icon">' . $reaction_icon . '</span> ';
                        echo '<span class="reaction-text">' . ($user_reaction ? ucfirst($user_reaction) : 'Like') . '</span>';
                        if ($post['reaction_count'] > 0) {
                            echo ' <span class="count">(' . $post['reaction_count'] . ')</span>';
                        }
                        echo '</button>';

                        // Comment button
                        echo '<button class="action-btn" onclick="openComments(' . $post['post_id'] . ')">';
                        echo '💬 Comment';
                        if ($post['comment_count'] > 0) {
                            echo ' <span class="count">(' . $post['comment_count'] . ')</span>';
                        }
                        echo '</button>';

                        // Share button
                        echo '<button class="action-btn" onclick="sharePost(' . $post['post_id'] . ')">';
                        echo '📤 Share</button>';

                        // Save button
                        echo '<button class="action-btn save-btn' . ($is_saved ? ' saved' : '') . '" onclick="toggleSavePost(' . $post['post_id'] . ', this)">';
                        echo '🔖 ' . ($is_saved ? 'Saved' : 'Save') . '</button>';

                        echo '</div>';

                        echo '</div>';
                    }
                } else {
                    echo '<div class="no-posts">No posts yet. Be the first to share something!</div>';
                }
                ?>
            </div>
        </div>
    </div>
      <!-- Edit History Modal -->
      <!--Temporary removed for a while-->  


    <!-- Image Lightbox -->
    <div id="imageLightbox" class="lightbox" onclick="closeLightbox()">
        <span class="lightbox-close">&times;</span>
        <img id="lightboxImage" src="" alt="Enlarged image">
    </div>

    <script>
        const currentUserId = <?php echo $current_user_id; ?>;
        const currentUsername = "<?php echo $current_username; ?>";
    </script>
    <script src="script.js"></script>
    <script src="search.js"></script>
    <script src="notifications.js"></script>
</body>
</html>
