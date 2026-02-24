<?php
require_once "config.php";

// CHANGE THIS to the username of the account you are trying to follow
$target_username = 'private_account123';

echo "<h1>Debugging Privacy for: $target_username</h1>";

// TEST 1: Check if the column actually exists
$check_col = mysqli_query($conn, "SHOW COLUMNS FROM users LIKE 'is_private'");
if (mysqli_num_rows($check_col) > 0) {
    echo "<p style='color:green; font-weight:bold;'>✅ Column 'is_private' exists in the 'users' table.</p>";
} else {
    echo "<p style='color:red; font-weight:bold;'>❌ CRITICAL ERROR: Column 'is_private' is MISSING from the database.</p>";
    echo "<p>Run this SQL: <code>ALTER TABLE users ADD COLUMN is_private TINYINT(1) DEFAULT 0;</code></p>";
    exit; // Stop here if column is missing
}

// TEST 2: Check the specific user's value
$sql = "SELECT id, username, is_private FROM users WHERE username = '$target_username'";
$result = mysqli_query($conn, $sql);

if ($row = mysqli_fetch_assoc($result)) {
    echo "<div style='border:1px solid #ccc; padding:10px; margin-top:10px;'>";
    echo "<p><strong>User ID:</strong> " . $row['id'] . "</p>";
    echo "<p><strong>Username:</strong> " . $row['username'] . "</p>";
    echo "<p><strong>Privacy Value (is_private):</strong> <span style='font-size:20px;'>" . $row['is_private'] . "</span></p>";
    echo "</div>";

    if ($row['is_private'] == 1) {
        echo "<h2 style='color:green'>RESULT: Account is PRIVATE.</h2>";
        echo "<p>If the button still acts Public, the bug is in <code>follow_handler.php</code>.</p>";
    } else {
        echo "<h2 style='color:red'>RESULT: Account is PUBLIC (0).</h2>";
        echo "<p><strong>The Fix:</strong> Go to PHPMyAdmin and set <code>is_private</code> to <code>1</code> for this user.</p>";
    }
} else {
    echo "<p style='color:red'>User '$target_username' not found!</p>";
}
?>