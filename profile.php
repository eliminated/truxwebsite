<?php
require_once "config.php";
require_once "includes/badge_functions.php";


if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: login.php");
    exit;
}

$current_user_id = $_SESSION["user_id"];
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


// Get username from URL or show current user's profile
$profile_username = isset($_GET['user']) ? $_GET['user'] : $current_username;

// Fetch profile user data
$profile_sql = "SELECT * FROM users WHERE username = ?";
if ($stmt = mysqli_prepare($conn, $profile_sql)) {
    mysqli_stmt_bind_param($stmt, "s", $profile_username);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    if (mysqli_num_rows($result) == 1) {
        $profile_user = mysqli_fetch_assoc($result);
        $profile_user_id = $profile_user['id'];
        $is_own_profile = ($profile_user_id == $current_user_id);
    } else {
        // User not found
        header("location: index.php");
        exit;
    }
    mysqli_stmt_close($stmt);
}

// Record profile view (don't count own views)
if (!$is_own_profile) {
    $view_sql = "INSERT INTO profile_views (profile_id, viewer_id) VALUES (?, ?)";
    if ($view_stmt = mysqli_prepare($conn, $view_sql)) {
        mysqli_stmt_bind_param($view_stmt, "ii", $profile_user_id, $current_user_id);
        mysqli_stmt_execute($view_stmt);
        mysqli_stmt_close($view_stmt);
    }

    // Update view count
    $update_sql = "UPDATE users SET profile_views = profile_views + 1 WHERE id = ?";
    if ($update_stmt = mysqli_prepare($conn, $update_sql)) {
        mysqli_stmt_bind_param($update_stmt, "i", $profile_user_id);
        mysqli_stmt_execute($update_stmt);
        mysqli_stmt_close($update_stmt);
    }
}

// Count followers
$followers_sql = "SELECT COUNT(*) as count FROM follows WHERE following_id = ?";
if ($stmt = mysqli_prepare($conn, $followers_sql)) {
    mysqli_stmt_bind_param($stmt, "i", $profile_user_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $followers_count = mysqli_fetch_assoc($result)['count'];
    mysqli_stmt_close($stmt);
}

// Count following
$following_sql = "SELECT COUNT(*) as count FROM follows WHERE follower_id = ?";
if ($stmt = mysqli_prepare($conn, $following_sql)) {
    mysqli_stmt_bind_param($stmt, "i", $profile_user_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $following_count = mysqli_fetch_assoc($result)['count'];
    mysqli_stmt_close($stmt);
}

// Count posts
$posts_sql = "SELECT COUNT(*) as count FROM posts WHERE user_id = ?";
if ($stmt = mysqli_prepare($conn, $posts_sql)) {
    mysqli_stmt_bind_param($stmt, "i", $profile_user_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $posts_count = mysqli_fetch_assoc($result)['count'];
    mysqli_stmt_close($stmt);
}

// Check if viewing someone else's profile
$is_own_profile = ($profile_user_id == $current_user_id);


// Check if target user's account is private
$is_private_account = isset($user_is_private) ? $user_is_private : 0;


// Check if current user follows the target user
$is_following = false;
if (!$is_own_profile) {
    $follow_check_sql = "SELECT follow_id FROM follows WHERE follower_id = ? AND following_id = ?";
    if ($stmt = mysqli_prepare($conn, $follow_check_sql)) {
        mysqli_stmt_bind_param($stmt, "ii", $current_user_id, $user_id);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_store_result($stmt);
        $is_following = (mysqli_stmt_num_rows($stmt) > 0);
        mysqli_stmt_close($stmt);
    }
}

// Fetch user's posts
$user_posts_sql = "SELECT p.*, u.username, u.email, u.profile_picture, u.user_role, u.is_verified,
                   COUNT(DISTINCT r.reaction_id) as reaction_count,
                   COUNT(DISTINCT c.comment_id) as comment_count
                   FROM posts p 
                   JOIN users u ON p.user_id = u.id 
                   LEFT JOIN reactions r ON p.post_id = r.post_id
                   LEFT JOIN comments c ON p.post_id = c.comment_id
                   WHERE p.user_id = ?
                   GROUP BY p.post_id
                   ORDER BY p.created_at DESC 
                   LIMIT 50";




// Determine if content should be visible
$can_view_content = $is_own_profile || !$is_private_account || $is_following;



if ($stmt = mysqli_prepare($conn, $user_posts_sql)) {
    mysqli_stmt_bind_param($stmt, "i", $profile_user_id);
    mysqli_stmt_execute($stmt);
    $posts_result = mysqli_stmt_get_result($stmt);
    mysqli_stmt_close($stmt);
}

$first_letter = strtoupper(substr($profile_user['username'], 0, 1));
$join_date = date('F Y', strtotime($profile_user['created_at']));
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($profile_user['username']); ?> - TruX</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar">
        <div class="nav-container">
            <div class="nav-left">
                <a href="index.php" class="logo">TruX</a>
                <a href="index.php" class="nav-link">Home</a>
                <a href="#" class="nav-link">Community</a>
                <a href="#" class="nav-link">Chat</a>
                <a href="#" class="nav-link">Supports</a>
            </div>
            <div class="nav-right">
                <div class="user-profile">
                    <?php if (!empty($current_user_profile_pic)): ?>
                        <span class="username-display" style="background-image: url('<?php echo htmlspecialchars($current_user_profile_pic); ?>'); background-size: cover; background-position: center;"></span>
                    <?php else: ?>
                        <span class="username-display"><?php echo strtoupper(substr($username, 0, 1)); ?></span>
                    <?php endif; ?>
                    <div class="user-dropdown">
                        <a href="profile.php">My Profile</a>
                        <a href="logout.php">Logout</a>
                    </div>
                </div>
            </div>
        </div>
    </nav>

    <div class="container profile-container">
        <!-- Profile Header -->
        <div class="profile-header">
            <!-- Cover Photo -->
            <div class="cover-photo" style="background-image: url('<?php echo !empty($profile_user['cover_photo']) ? htmlspecialchars($profile_user['cover_photo']) : 'https://via.placeholder.com/1200x300/667eea/ffffff?text=Cover+Photo'; ?>');">
                <?php if ($is_own_profile): ?>
                <button class="change-cover-btn" onclick="openImageUpload('cover')">
                    📷 Change Cover
                </button>
                <?php endif; ?>
            </div>

            <!-- Profile Info -->
            <div class="profile-info-section">
                <div class="profile-picture-container">
                    <div class="profile-picture" style="background-image: url('<?php echo !empty($profile_user['profile_picture']) ? htmlspecialchars($profile_user['profile_picture']) : ''; ?>');">
                        <?php if (empty($profile_user['profile_picture'])): ?>
                            <span class="profile-initials"><?php echo $first_letter; ?></span>
                        <?php endif; ?>
                    </div>
                    <?php if ($is_own_profile): ?>
                    <button class="change-profile-pic-btn" onclick="openImageUpload('profile')">
                        📷
                    </button>
                    <?php endif; ?>
                </div>

                <div class="profile-details">
                    <h1 class="profile-name">
                        <?php echo htmlspecialchars($profile_user['username']); ?>
                        <?php echo displayVerifiedCheck($profile_user['is_verified']); ?>
                    </h1>
                    <?php
                    $role_badge = getUserRoleBadge($profile_user['user_role']);
                    if ($role_badge) {
                        echo displayRoleBadge($role_badge);
                    }
                    ?>

                    <p class="profile-email"><?php echo htmlspecialchars($profile_user['email']); ?></p>
                    
                    <?php if (!empty($profile_user['bio'])): ?>
                    <p class="profile-bio"><?php echo nl2br(htmlspecialchars($profile_user['bio'])); ?></p>
                    <?php endif; ?>

                    <?php if (!empty($profile_user['location'])): ?>
                    <p class="profile-location">📍 <?php echo htmlspecialchars($profile_user['location']); ?></p>
                    <?php endif; ?>

                    <?php if (!empty($profile_user['website'])): ?>
                    <p class="profile-website">🔗 <a href="<?php echo htmlspecialchars($profile_user['website']); ?>" target="_blank"><?php echo htmlspecialchars($profile_user['website']); ?></a></p>
                    <?php endif; ?>
                    <!-- User Badges -->
                    <?php
                    $user_badges = getUserBadges($profile_user_id, $conn);
                    if (count($user_badges) > 0):
                        ?>
                    <div class="profile-badges">
                        <?php foreach ($user_badges as $badge): ?>
                            <?php echo displayBadge($badge); ?>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>


                    <p class="profile-joined">📅 Joined <?php echo $join_date; ?></p>
                </div>

                <div class="profile-actions">
                    <?php if ($is_own_profile): ?>
                        <button class="btn-primary" onclick="window.location.href='edit_profile.php'">✏️ Edit Profile</button>
                    <?php else: ?>
                        <button class="btn-primary follow-btn <?php echo $is_following ? 'following' : ''; ?>>" 
                                id="followBtn" 
                                data-user-id="<?php echo $profile_user_id; ?>"
                                data-following="<?php echo $is_following ? '1' : '0'; ?>"
                                onclick="toggleFollow(<?php echo $profile_user_id; ?>)">
                            <?php echo $is_following ? 'Following' : 'Follow'; ?>
                        </button>


                        <button class="btn-secondary" onclick="sendMessage(<?php echo $profile_user_id; ?>)">💬 Message</button>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Profile Stats -->
            <div class="profile-stats">
                <div class="stat-item">
                    <span class="stat-number"><?php echo $posts_count; ?></span>
                    <span class="stat-label">Posts</span>
                </div>
                <div class="stat-item" onclick="showFollowers(<?php echo $profile_user_id; ?>)">
                    <span class="stat-number"><?php echo $followers_count; ?></span>
                    <span class="stat-label">Followers</span>
                </div>
                <div class="stat-item" onclick="showFollowing(<?php echo $profile_user_id; ?>)">
                    <span class="stat-number"><?php echo $following_count; ?></span>
                    <span class="stat-label">Following</span>
                </div>
                <div class="stat-item">
                    <span class="stat-number"><?php echo $profile_user['profile_views']; ?></span>
                    <span class="stat-label">Views</span>
                </div>
            </div>
        </div>

        <!-- Profile Content Tabs -->
        <div class="profile-tabs">
            <button class="tab-btn active" onclick="showTab('posts')">Posts</button>
            <button class="tab-btn" onclick="showTab('media')">Media</button>
            <button class="tab-btn" onclick="showTab('likes')">Likes</button>
        </div>

        <!-- Posts Section -->
        <div class="profile-posts" id="postsTab">
            <?php if ($can_view_content): ?>
                <!-- User can see content -->
                <?php if (mysqli_num_rows($posts_result) > 0): ?>
                    <?php while ($post = mysqli_fetch_assoc($posts_result)):
                        $post_time = date('d/m/Y, g:i:s a', strtotime($post['created_at']));
                        ?>
                    <div class="post-card" data-post-id="<?php echo $post['post_id']; ?>">
                        <div class="post-header">
                            <?php if (!empty($profile_user['profile_picture'])): ?>
                                <div class="post-avatar" style="background-image: url('<?php echo htmlspecialchars($profile_user['profile_picture']); ?>'); background-size: cover; background-position: center;"></div>
                            <?php else: ?>
                                <div class="post-avatar"><?php echo $first_letter; ?></div>
                            <?php endif; ?>
                    
                            <div class="post-info">
                                <div class="post-author"><?php echo htmlspecialchars($post['username']); ?></div>
                                <div class="post-time"><?php echo $post_time; ?></div>
                            </div>
                        </div>
                
                        <div class="post-content" data-content="<?php echo htmlspecialchars($post['content']); ?>"><?php echo nl2br(htmlspecialchars($post['content'])); ?></div>
                
                        <?php if (!empty($post['media_path'])): ?>
                            <div class="post-media">
                                <?php if ($post['media_type'] == 'image'): ?>
                                    <img src="<?php echo htmlspecialchars($post['media_path']); ?>" alt="Post image" onclick="openImageLightbox('<?php echo htmlspecialchars($post['media_path']); ?>')">
                                <?php elseif ($post['media_type'] == 'video'): ?>
                                    <video controls src="<?php echo htmlspecialchars($post['media_path']); ?>"></video>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                
                        <div class="post-stats">
                            <span><?php echo $post['reaction_count']; ?> reactions</span>
                            <span><?php echo $post['comment_count']; ?> comments</span>
                        </div>
                    </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div class="no-posts">
                        <p>No posts yet</p>
                    </div>
                <?php endif; ?>
        
            <?php else: ?>
                <!-- Private account message -->
                <div class="private-account-message">
                    <div class="private-icon">🔒</div>
                    <h2>This Account is Private</h2>
                    <p>Follow this account to see their posts and activity.</p>
                    <button class="follow-btn" onclick="toggleFollow(<?php echo $user_id; ?>, this)">
                        Follow
                    </button>
                </div>
            <?php endif; ?>
        </div>


        <!-- Media Tab (placeholder) -->
        <div class="profile-posts" id="mediaTab" style="display:none;">
            <p>Media content coming soon...</p>
        </div>

        <!-- Likes Tab (placeholder) -->
        <div class="profile-posts" id="likesTab" style="display:none;">
            <p>Liked posts coming soon...</p>
        </div>
    </div>

    <!-- Image Lightbox -->
    <div id="imageLightbox" class="lightbox" onclick="closeLightbox()">
        <span class="lightbox-close">&times;</span>
        <img id="lightboxImage" src="" alt="Lightbox">
    </div>


    <!-- Follow List Modal -->
    <div id="followListModal" class="follow-modal">
        <div class="follow-modal-content">
            <div class="follow-modal-header">
                <h2 id="followModalTitle">Followers</h2>
                <button class="follow-modal-close" onclick="closeFollowModal()">&times;</button>
            </div>
            <div class="follow-modal-body">
                <div id="followUserList" class="follow-user-list">
                    <!-- User list will be loaded here -->
                </div>
            </div>
        </div>
    </div>

    <script src="profile.js"></script>
    <script src="follow.js"></script>
    <script src="follow_list.js"></script>
    <script>
        const currentUserId = <?php echo $current_user_id; ?>;
        const profileUserId = <?php echo $profile_user_id; ?>;
        const isOwnProfile = <?php echo $is_own_profile ? 'true' : 'false'; ?>;
    </script>
    <script src="image_upload.js"></script>
</body>
</html>

