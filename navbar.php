<nav class="navbar">
    <div class="nav-container">
        <div class="nav-left">
            <a href="index.php" class="logo">TruX</a>
            <ul class="nav-menu">
                <li class="nav-item"><a href="index.php" class="nav-link">Home</a></li>
                <li class="nav-item"><a href="community.php" class="nav-link">Community</a></li>
                <li class="nav-item"><a href="chat.php" class="nav-link">Chat</a></li>
                <li class="nav-item"><a href="support.php" class="nav-link">Supports</a></li>
            </ul>
        </div>

        <div class="nav-right">
            
            <div class="nav-item dropdown-wrapper" style="position: relative; margin-right: 20px;">
                <a href="#" id="notifBtn" class="nav-link" style="font-size: 20px; position: relative;">
                    🔔
                    <span id="notifCount" class="badge" style="display: none;">0</span>
                </a>
                
                <div id="notifDropdown" class="dropdown-menu notification-dropdown" style="display: none;">
                    <div class="dropdown-header">
                        <span>Notifications</span>
                        <a href="#" id="markAllRead" style="font-size: 12px;">Mark all read</a>
                    </div>
                    <div id="notifList" class="dropdown-body">
                        <div class="loading">Loading...</div>
                    </div>
                </div>
            </div>

            <div class="profile-box">
                <div class="profile-icon">
                    <?php
                    if (!empty($_SESSION['profile_picture'])) {
                        echo '<div style="width:32px; height:32px; border-radius:50%; background:url(' . htmlspecialchars($_SESSION['profile_picture']) . ') center/cover;"></div>';
                    } else {
                        echo strtoupper(substr($_SESSION["username"], 0, 1));
                    }
                    ?>
                </div>
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