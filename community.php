<?php
require_once "config.php";
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: login.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Page Title - MyWebsite</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <nav class="navbar">
        <div class="nav-container">
            <div class="nav-left">
                <a href="index.php" class="logo">MyWebsite</a>
                <ul class="nav-menu">
                    <li class="nav-item"><a href="index.php" class="nav-link">Home</a></li>
                    <li class="nav-item"><a href="community.php" class="nav-link">Community</a></li>
                    <li class="nav-item"><a href="chat.php" class="nav-link">Chat</a></li>
                    <li class="nav-item"><a href="support.php" class="nav-link">Supports</a></li>
                </ul>
            </div>
            <div class="nav-right">
                <div class="profile-box">
                    <div class="profile-icon"><?php echo strtoupper(substr($_SESSION["username"], 0, 1)); ?></div>
                    <div class="profile-dropdown">
                        <p class="profile-username"><?php echo htmlspecialchars($_SESSION["username"]); ?></p>
                        <p class="profile-email"><?php echo htmlspecialchars($_SESSION["email"]); ?></p>
                        <a href="profile.php" class="dropdown-link">My Profile</a>
                        <a href="settings.php" class="dropdown-link">Settings</a>
                        <a href="logout.php" class="dropdown-link logout">Logout</a>
                    </div>
                </div>
            </div>
        </div>
    </nav>
    <div class="container">
        <h1>This page is under construction</h1>
    </div>
</body>
</html>
