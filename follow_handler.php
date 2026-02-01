<?php
require_once "config.php";
require_once "notification_helper.php";

header('Content-Type: application/json');

// Enable error logging (for debugging)
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit;
}

$current_user_id = $_SESSION["user_id"];
$response = ['success' => false, 'message' => ''];

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && isset($_POST['user_id'])) {

    $action = $_POST['action'];
    $target_user_id = intval($_POST['user_id']);

    // Prevent following yourself
    if ($target_user_id == $current_user_id) {
        $response['message'] = 'You cannot follow yourself';
        echo json_encode($response);
        exit;
    }

    // Validate target user ID
    if ($target_user_id <= 0) {
        $response['message'] = 'Invalid user ID';
        echo json_encode($response);
        exit;
    }

    // Check if target user exists
    $check_sql = "SELECT id FROM users WHERE id = ?";
    if ($stmt = mysqli_prepare($conn, $check_sql)) {
        mysqli_stmt_bind_param($stmt, "i", $target_user_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);

        if (mysqli_num_rows($result) == 0) {
            $response['message'] = 'User not found';
            echo json_encode($response);
            mysqli_stmt_close($stmt);
            exit;
        }
        mysqli_stmt_close($stmt);
    }

    if ($action == 'follow') {
        // Check if already following
        $check_follow = "SELECT follow_id FROM follows WHERE follower_id = ? AND following_id = ?";
        if ($stmt = mysqli_prepare($conn, $check_follow)) {
            mysqli_stmt_bind_param($stmt, "ii", $current_user_id, $target_user_id);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);

            if (mysqli_num_rows($result) > 0) {
                $response['message'] = 'Already following this user';
                echo json_encode($response);
                mysqli_stmt_close($stmt);
                exit;
            }
            mysqli_stmt_close($stmt);
        }

        // Follow user
        $insert_sql = "INSERT INTO follows (follower_id, following_id) VALUES (?, ?)";
        if ($stmt = mysqli_prepare($conn, $insert_sql)) {
            mysqli_stmt_bind_param($stmt, "ii", $current_user_id, $target_user_id);

            if (mysqli_stmt_execute($stmt)) {
                // Create notification (FIXED: use $target_user_id)
                createNotification($conn, $target_user_id, $current_user_id, 'follow');

                $response['success'] = true;
                $response['message'] = 'Successfully followed';
                $response['action'] = 'followed';
            } else {
                $response['message'] = 'Failed to follow user: ' . mysqli_error($conn);
            }
            mysqli_stmt_close($stmt);
        }

    } elseif ($action == 'unfollow') {
        // Unfollow user
        $delete_sql = "DELETE FROM follows WHERE follower_id = ? AND following_id = ?";
        if ($stmt = mysqli_prepare($conn, $delete_sql)) {
            mysqli_stmt_bind_param($stmt, "ii", $current_user_id, $target_user_id);

            if (mysqli_stmt_execute($stmt)) {
                if (mysqli_stmt_affected_rows($stmt) > 0) {
                    // Delete notification (FIXED: use $target_user_id)
                    deleteNotification($conn, $target_user_id, $current_user_id, 'follow');

                    $response['success'] = true;
                    $response['message'] = 'Successfully unfollowed';
                    $response['action'] = 'unfollowed';
                } else {
                    $response['message'] = 'You are not following this user';
                }
            } else {
                $response['message'] = 'Failed to unfollow user: ' . mysqli_error($conn);
            }
            mysqli_stmt_close($stmt);
        }

    } else {
        $response['message'] = 'Invalid action';
    }

    // Get updated follower count
    if ($response['success']) {
        $count_sql = "SELECT COUNT(*) as count FROM follows WHERE following_id = ?";
        if ($stmt = mysqli_prepare($conn, $count_sql)) {
            mysqli_stmt_bind_param($stmt, "i", $target_user_id);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            $row = mysqli_fetch_assoc($result);
            $response['follower_count'] = $row['count'];
            mysqli_stmt_close($stmt);
        }
    }

} else {
    $response['message'] = 'Invalid request';
}

echo json_encode($response);
?>
