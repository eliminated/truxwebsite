<?php
require_once "config.php";

echo "Session Debug:<br>";
echo "loggedin: " . (isset($_SESSION["loggedin"]) ? "YES" : "NO") . "<br>";
echo "user_id: " . (isset($_SESSION["user_id"]) ? $_SESSION["user_id"] : "NOT SET") . "<br>";
echo "id: " . (isset($_SESSION["user_id"]) ? $_SESSION["user_id"] : "NOT SET") . "<br>";
echo "username: " . (isset($_SESSION["username"]) ? $_SESSION["username"] : "NOT SET") . "<br>";
?>
