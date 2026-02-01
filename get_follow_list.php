<?php
require_once "config.php";

header('Content-Type: application/json');

if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit;
}

$current_user_id = $_SESSION["user_id"];
$response = ['success' => false, 'users' => [], 'message' => ''];

if ($_SERVER["REQUEST_METHOD"] == "GET" && isset($_GET['user_id']) && isset($_GET['type'])) {

    $target_user_id = intval($_GET['user_id']);
    $type = $_GET['type']; // 'followers' or 'following'

    if ($target_user_id <= 0) {
        $response['message'] = 'Invalid user ID';
        echo json_encode($response);
        exit;
    }

    if ($type === 'followers') {
        // Get users who follow the target user
        $sql = "SELECT u.id, u.username, u.email, u.profile_picture, u.bio, u.is_verified, u.user_role,
                (SELECT COUNT(*) FROM follows WHERE follower_id = ? AND following_id = u.id) as is_following
                FROM users u
                INNER JOIN follows f ON u.id = f.follower_id
                WHERE f.following_id = ?
                ORDER BY f.created_at DESC";

        if ($stmt = mysqli_prepare($conn, $sql)) {
            mysqli_stmt_bind_param($stmt, "ii", $current_user_id, $target_user_id);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);

            $users = [];
            while ($row = mysqli_fetch_assoc($result)) {
                $users[] = [
                    'id' => $row['id'],
                    'username' => $row['username'],
                    'email' => $row['email'],
                    'profile_picture' => $row['profile_picture'],
                    'bio' => $row['bio'],
                    'is_verified' => $row['is_verified'],
                    'user_role' => $row['user_role'],
                    'is_following' => ($row['is_following'] > 0),
                    'is_current_user' => ($row['id'] == $current_user_id)
                ];
            }

            $response['success'] = true;
            $response['users'] = $users;
            $response['type'] = 'followers';
            mysqli_stmt_close($stmt);
        }

    } elseif ($type === 'following') {
        // Get users that the target user follows
        $sql = "SELECT u.id, u.username, u.email, u.profile_picture, u.bio, u.is_verified, u.user_role,
                (SELECT COUNT(*) FROM follows WHERE follower_id = ? AND following_id = u.id) as is_following
                FROM users u
                INNER JOIN follows f ON u.id = f.following_id
                WHERE f.follower_id = ?
                ORDER BY f.created_at DESC";

        if ($stmt = mysqli_prepare($conn, $sql)) {
            mysqli_stmt_bind_param($stmt, "ii", $current_user_id, $target_user_id);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);

            $users = [];
            while ($row = mysqli_fetch_assoc($result)) {
                $users[] = [
                    'id' => $row['id'],
                    'username' => $row['username'],
                    'email' => $row['email'],
                    'profile_picture' => $row['profile_picture'],
                    'bio' => $row['bio'],
                    'is_verified' => $row['is_verified'],
                    'user_role' => $row['user_role'],
                    'is_following' => ($row['is_following'] > 0),
                    'is_current_user' => ($row['id'] == $current_user_id)
                ];
            }

            $response['success'] = true;
            $response['users'] = $users;
            $response['type'] = 'following';
            mysqli_stmt_close($stmt);
        }

    } else {
        $response['message'] = 'Invalid type parameter';
    }

} else {
    $response['message'] = 'Invalid request';
}

echo json_encode($response);
?>
