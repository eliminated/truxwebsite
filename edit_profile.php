<?php
require_once "config.php";

if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: login.php");
    exit;
}

$user_id = $_SESSION["user_id"];
$username = $_SESSION["username"];

// Fetch current user data
$sql = "SELECT * FROM users WHERE id = ?";
if ($stmt = mysqli_prepare($conn, $sql)) {
    mysqli_stmt_bind_param($stmt, "i", $user_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $user = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);
}

// Handle form submission
$success_message = "";
$error_message = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $bio = trim($_POST["bio"]);
    $location = trim($_POST["location"]);
    $website = trim($_POST["website"]);

    // Validate website URL (optional)
    if (!empty($website) && !filter_var($website, FILTER_VALIDATE_URL)) {
        $error_message = "Please enter a valid website URL";
    } else {
        // Update user profile
        $update_sql = "UPDATE users SET bio = ?, location = ?, website = ? WHERE id = ?";
        if ($stmt = mysqli_prepare($conn, $update_sql)) {
            mysqli_stmt_bind_param($stmt, "sssi", $bio, $location, $website, $user_id);

            if (mysqli_stmt_execute($stmt)) {
                $success_message = "Profile updated successfully!";
                // Refresh user data
                $user['bio'] = $bio;
                $user['location'] = $location;
                $user['website'] = $website;
            } else {
                $error_message = "Something went wrong. Please try again.";
            }
            mysqli_stmt_close($stmt);
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Edit Profile - TruX</title>
    <link rel="stylesheet" href="style.css" />
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
                    <?php if (!empty($user['profile_picture'])): ?>
                        <div class="username-display" style="background-image: url('<?php echo htmlspecialchars($user['profile_picture']); ?>'); background-size: cover; background-position: center;"></div>
                    <?php else: ?>
                        <div class="username-display">
                            <span class="username-initial"><?php echo strtoupper(substr($username, 0, 1)); ?></span>
                        </div>
                    <?php endif; ?>
                    <div class="user-dropdown">
                        <a href="profile.php">My Profile</a>
                        <a href="logout.php">Logout</a>
                    </div>
                </div>
            </div>
        </div>
    </nav>

    <div class="container edit-profile-container">
        <div class="edit-profile-card">
            <div class="edit-profile-header">
                <h2>✏️ Edit Profile</h2>
                <a href="profile.php" class="btn-secondary">Cancel</a>
            </div>

            <?php if ($success_message): ?>
                <div class="alert alert-success">
                    ✓ <?php echo $success_message; ?>
                </div>
            <?php endif; ?>

            <?php if ($error_message): ?>
                <div class="alert alert-error">
                    ✗ <?php echo $error_message; ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" class="edit-profile-form">

                <!-- Profile Picture Section -->
                <div class="form-section">
                    <h3>Profile Picture</h3>
                    <div class="profile-pic-edit">
                        <div class="current-profile-pic" style="background-image: url('<?php echo !empty($user['profile_picture']) ? htmlspecialchars($user['profile_picture']) : ''; ?>');">
                            <?php if (empty($user['profile_picture'])): ?>
                                <span class="profile-initials-large"><?php echo strtoupper(substr($username, 0, 1)); ?></span>
                            <?php endif; ?>
                        </div>
                        <div class="pic-edit-actions">
                            <button type="button" class="btn-upload" onclick="openImageUpload('profile')"> 📷 Change Photo
                            </button>

                            <p class="help-text">JPG, PNG or GIF. Max size 5MB.</p>
                        </div>
                    </div>
                </div>

                <hr class="form-divider" />

                <!-- Cover Photo Section -->
                <div class="form-section">
                    <h3>Cover Photo</h3>
                    <div class="cover-photo-edit" style="background-image: url('<?php echo !empty($user['cover_photo']) ? htmlspecialchars($user['cover_photo']) : 'https://via.placeholder.com/800x200/667eea/ffffff?text=Cover+Photo'; ?>');">
                        <button type="button" class="btn-upload-cover" onclick="openImageUpload('cover')"> 🖼️ Change Cover
                        </button>
                    </div>
                    <p class="help-text">Recommended size: 1200x300 pixels</p>
                </div>

                <hr class="form-divider" />

                <!-- Bio Section -->
                <div class="form-section">
                    <label for="bio">Bio</label>
                    <textarea id="bio" name="bio" rows="4" maxlength="500" placeholder="Tell us about yourself..."><?php echo htmlspecialchars($user['bio'] ?? ''); ?></textarea>
                    <p class="help-text">
                        <span id="bioCount"><?php echo strlen($user['bio'] ?? ''); ?></span>/500 characters
                    </p>
                </div>

                <!-- Location Section -->
                <div class="form-section">
                    <label for="location">Location</label>
                    <input type="text" id="location" name="location" maxlength="100" placeholder="e.g. New York, USA" value="<?php echo htmlspecialchars($user['location'] ?? ''); ?>" />
                </div>

                <!-- Website Section -->
                <div class="form-section">
                    <label for="website">Website</label>
                    <input type="url" id="website" name="website" maxlength="255" placeholder="https://yourwebsite.com" value="<?php echo htmlspecialchars($user['website'] ?? ''); ?>" />
                    <p class="help-text">Please include http:// or https://</p>
                </div>

                <hr class="form-divider" />

                <!-- Action Buttons -->
                <div class="form-actions">
                    <button type="submit" class="btn-primary">💾 Save Changes</button>
                    <a href="profile.php" class="btn-cancel">Cancel</a>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Character counter for bio
        const bioTextarea = document.getElementById('bio');
        const bioCount = document.getElementById('bioCount');

        bioTextarea.addEventListener('input', function () {
            bioCount.textContent = this.value.length;
        });
    </script>
    <script src="image_upload.js"></script>
</body>
</html>
