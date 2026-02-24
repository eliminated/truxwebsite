<?php
require_once "config.php";

// CHANGE THIS to the exact username you are testing
$target_username = 'private_account123';

echo "<h1>Debugging User: $target_username</h1>";

// 1. Check if column exists
$check_col = mysqli_query($conn, "SHOW COLUMNS FROM users LIKE 'is_private'");
if (mysqli_num_rows($check_col) > 0) {
    echo "<p style='color:green'>✅ Column 'is_private' EXISTS.</p>";
} else {
    echo "<p style='color:red'>❌ Column 'is_private' is MISSING.</p>";
}

// 2. Check the user's value
$sql = "SELECT id, username, is_private FROM users WHERE username = '$target_username'";
$result = mysqli_query($conn, $sql);

if ($row = mysqli_fetch_assoc($result)) {
    echo "<p>User ID: " . $row['id'] . "</p>";
    echo "<p>Privacy Setting: <strong>" . $row['is_private'] . "</strong></p>";

    if ($row['is_private'] == 1) {
        echo "<h2 style='color:green'>This account is PRIVATE.</h2>";
    } else {
        echo "<h2 style='color:red'>This account is PUBLIC (0).</h2>";
        echo "<p>Go to PHPMyAdmin and set 'is_private' to 1 for this user.</p>";
    }
} else {
    echo "<p style='color:red'>User '$target_username' not found!</p>";
}
?>