<?php
require_once "config.php";

if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: login.php");
    exit;
}

$user_id = $_SESSION["user_id"];
$username = $_SESSION["username"];

// Fetch current user data
$sql = "SELECT username, email, is_private FROM users WHERE id = ?";
$current_user_private = 0;

if ($stmt = mysqli_prepare($conn, $sql)) {
    mysqli_stmt_bind_param($stmt, "i", $user_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    if ($row = mysqli_fetch_assoc($result)) {
        $current_username = $row['username'];
        $current_email = $row['email'];
        $current_user_private = $row['is_private'];
    }
    mysqli_stmt_close($stmt);
}

// Get profile picture
$profile_pic_sql = "SELECT profile_picture FROM users WHERE id = ?";
$current_user_profile_pic = '';
if ($stmt = mysqli_prepare($conn, $profile_pic_sql)) {
    mysqli_stmt_bind_param($stmt, "i", $user_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    if ($row = mysqli_fetch_assoc($result)) {
        $current_user_profile_pic = $row['profile_picture'];
    }
    mysqli_stmt_close($stmt);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Settings - TruX</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <!-- Include your navbar here -->
    <?php include 'navbar.php'; // Or copy your navbar from index.php ?>
    
    <div class="container">
        <div class="settings-container">
            <h1>Account Settings</h1>
            
            <!-- Privacy Section -->
            <div class="settings-section">
                <h2>Privacy</h2>
                
                <div class="setting-item">
                    <div class="setting-info">
                        <h3>Private Account</h3>
                        <p>When your account is private, only your followers can see your posts and profile information.</p>
                    </div>
                    <div class="setting-control">
                        <label class="toggle-switch">
                            <input type="checkbox" id="privateToggle" <?php echo $current_user_private ? 'checked' : ''; ?>>
                            <span class="toggle-slider"></span>
                        </label>
                    </div>
                </div>
            </div>
            
            <!-- Account Information Section -->
            <div class="settings-section">
                <h2>Account Information</h2>
                
                <div class="setting-item">
                    <div class="setting-info">
                        <h3>Username</h3>
                        <p><?php echo htmlspecialchars($current_username); ?></p>
                    </div>
                </div>
                
                <div class="setting-item">
                    <div class="setting-info">
                        <h3>Email</h3>
                        <p><?php echo htmlspecialchars($current_email); ?></p>
                    </div>
                </div>
            </div>
            
            <!-- Danger Zone -->
            <div class="settings-section danger-zone">
                <h2>Danger Zone</h2>
                
                <div class="setting-item">
                    <div class="setting-info">
                        <h3>Delete Account</h3>
                        <p>Permanently delete your account and all associated data.</p>
                    </div>
                    <div class="setting-control">
                        <button class="btn-danger" onclick="alert('Feature coming soon!')">Delete Account</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="notifications.js"></script>
    <script>
        // Privacy toggle handler
        document.getElementById('privateToggle').addEventListener('change', async function() {
            const isPrivate = this.checked ? 1 : 0;
            const toggle = this;
            
            try {
                const formData = new FormData();
                formData.append('action', 'toggle_privacy');
                formData.append('is_private', isPrivate);
                
                const response = await fetch('update_privacy.php', {
                    method: 'POST',
                    body: formData
                });
                
                const data = await response.json();
                
                if (data.success) {
                    // Show success message
                    showNotification(data.message, 'success');
                } else {
                    // Revert toggle on error
                    toggle.checked = !toggle.checked;
                    showNotification(data.message || 'Failed to update privacy setting', 'error');
                }
            } catch (error) {
                console.error('Error:', error);
                toggle.checked = !toggle.checked;
                showNotification('Something went wrong', 'error');
            }
        });
        
        // Simple notification function
        function showNotification(message, type) {
            const notification = document.createElement('div');
            notification.className = `notification notification-${type}`;
            notification.textContent = message;
            document.body.appendChild(notification);
            
            setTimeout(() => {
                notification.classList.add('show');
            }, 10);
            
            setTimeout(() => {
                notification.classList.remove('show');
                setTimeout(() => notification.remove(), 300);
            }, 3000);
        }
    </script>
</body>
</html>
