<?php
// truxwebsite/settings.php
require_once "config.php";

// Check login
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: login.php");
    exit;
}

$user_id = $_SESSION["user_id"];
$message = "";
$message_type = "";

// 1. Handle Privacy Toggle Submission
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] == 'update_privacy') {
    $is_private = isset($_POST['is_private']) ? 1 : 0;

    $sql = "UPDATE users SET is_private = ? WHERE id = ?";
    if ($stmt = mysqli_prepare($conn, $sql)) {
        mysqli_stmt_bind_param($stmt, "ii", $is_private, $user_id);
        if (mysqli_stmt_execute($stmt)) {
            $message = "Privacy settings updated!";
            $message_type = "success";
        } else {
            $message = "Error updating settings.";
            $message_type = "error";
        }
        mysqli_stmt_close($stmt);
    }
}

// 2. Fetch Current Settings
$current_privacy = 0;
$sql = "SELECT is_private FROM users WHERE id = ?";
if ($stmt = mysqli_prepare($conn, $sql)) {
    mysqli_stmt_bind_param($stmt, "i", $user_id);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_bind_result($stmt, $current_privacy);
    mysqli_stmt_fetch($stmt);
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
    <style>
        .settings-container { max-width: 800px; margin: 30px auto; padding: 20px; }
        .settings-card { background: white; padding: 30px; border-radius: 15px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); }
        .settings-section { margin-bottom: 30px; padding-bottom: 20px; border-bottom: 1px solid #eee; }
        .toggle-switch { position: relative; display: inline-block; width: 60px; height: 34px; vertical-align: middle; }
        .toggle-switch input { opacity: 0; width: 0; height: 0; }
        .slider { position: absolute; cursor: pointer; top: 0; left: 0; right: 0; bottom: 0; background-color: #ccc; transition: .4s; border-radius: 34px; }
        .slider:before { position: absolute; content: ""; height: 26px; width: 26px; left: 4px; bottom: 4px; background-color: white; transition: .4s; border-radius: 50%; }
        input:checked + .slider { background-color: #667eea; }
        input:checked + .slider:before { transform: translateX(26px); }
        .alert { padding: 15px; margin-bottom: 20px; border-radius: 8px; }
        .alert-success { background: #d4edda; color: #155724; }
        .alert-error { background: #f8d7da; color: #721c24; }
    </style>
</head>
<body>

    <?php include "navbar.php"; ?>

    <div class="settings-container">
        <h1>Settings</h1>

        <?php if (!empty($message)): ?>
            <div class="alert alert-<?php echo $message_type; ?>">
                <?php echo $message; ?>
            </div>
        <?php endif; ?>

        <div class="settings-card">
            
            <div class="settings-section">
                <h2>🔒 Privacy & Safety</h2>
                <p>Control who can see your profile and posts.</p>
                
                <form method="POST" action="">
                    <input type="hidden" name="action" value="update_privacy">
                    
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 20px;">
                        <div>
                            <strong>Private Account</strong>
                            <p style="font-size: 0.9em; color: #666; margin-top: 5px;">
                                When your account is private, only people you approve can see your posts. 
                                Existing followers won't be affected.
                            </p>
                        </div>
                        <label class="toggle-switch">
                            <input type="checkbox" name="is_private" value="1" <?php echo ($current_privacy == 1) ? 'checked' : ''; ?> onchange="this.form.submit()">
                            <span class="slider"></span>
                        </label>
                    </div>
                </form>
            </div>

            <div class="settings-section">
                <h2>🔔 Notifications</h2>
                <p style="color: #999;">Coming soon...</p>
            </div>

            <div class="settings-section" style="border-bottom: none;">
                <h2>🛡️ Security</h2>
                <a href="#" class="btn-secondary">Change Password</a>
            </div>

        </div>
    </div>

</body>
</html>