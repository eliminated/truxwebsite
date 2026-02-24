<?php
require_once "config.php";
require_once "notification_helper.php";

header('Content-Type: application/json');

// Enable error reporting to catch hidden SQL errors
error_reporting(E_ALL);
ini_set('display_errors', 0);

if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit;
}

$current_user_id = $_SESSION["user_id"];
$response = ['success' => false, 'message' => ''];

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && isset($_POST['user_id'])) {

    $action = $_POST['action'];
    $target_user_id = intval($_POST['user_id']);

    if ($target_user_id == $current_user_id) {
        echo json_encode(['success' => false, 'message' => 'You cannot follow yourself']);
        exit;
    }

    // 1. CRITICAL: Check Privacy Setting
    $target_is_private = 0;
    $check_sql = "SELECT is_private FROM users WHERE id = ?";

    if ($stmt = mysqli_prepare($conn, $check_sql)) {
        mysqli_stmt_bind_param($stmt, "i", $target_user_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);

        if ($row = mysqli_fetch_assoc($result)) {
            $target_is_private = intval($row['is_private']); // Force integer
        } else {
            echo json_encode(['success' => false, 'message' => 'Target user not found']);
            exit;
        }
        mysqli_stmt_close($stmt);
    } else {
        // If SQL fails (e.g., column missing), report it instead of failing silently
        echo json_encode(['success' => false, 'message' => 'Database Error: ' . mysqli_error($conn)]);
        exit;
    }

    // --- FOLLOW LOGIC ---
    if ($action == 'follow') {

        // Prevent duplicate follows
        $dup_check = mysqli_query($conn, "SELECT follow_id FROM follows WHERE follower_id=$current_user_id AND following_id=$target_user_id");
        if (mysqli_num_rows($dup_check) > 0) {
            echo json_encode(['success' => false, 'message' => 'Already following']);
            exit;
        }

        // Prevent duplicate requests
        $req_check = mysqli_query($conn, "SELECT request_id FROM follow_requests WHERE follower_id=$current_user_id AND following_id=$target_user_id");
        if (mysqli_num_rows($req_check) > 0) {
            echo json_encode(['success' => false, 'message' => 'Request already pending']);
            exit;
        }

        // BRANCHING LOGIC
        if ($target_is_private === 1) {
            // ---> PRIVATE: Send Request
            $sql = "INSERT INTO follow_requests (follower_id, following_id) VALUES (?, ?)";
            $response_action = 'requested';
            $notif_type = 'follow_request';
        } else {
            // ---> PUBLIC: Direct Follow
            $sql = "INSERT INTO follows (follower_id, following_id) VALUES (?, ?)";
            $response_action = 'followed';
            $notif_type = 'follow';
        }

        if ($stmt = mysqli_prepare($conn, $sql)) {
            mysqli_stmt_bind_param($stmt, "ii", $current_user_id, $target_user_id);
            if (mysqli_stmt_execute($stmt)) {
                // Send appropriate notification
                createNotification($conn, $target_user_id, $current_user_id, $notif_type);

                $response['success'] = true;
                $response['action'] = $response_action;
                $response['message'] = ($target_is_private === 1) ? 'Request sent' : 'Following';
            } else {
                $response['message'] = 'Database Insert Error';
            }
            mysqli_stmt_close($stmt);
        }

        // --- UNFOLLOW LOGIC ---
    } elseif ($action == 'unfollow') {
        // Delete from BOTH tables to be safe
        mysqli_query($conn, "DELETE FROM follows WHERE follower_id=$current_user_id AND following_id=$target_user_id");
        mysqli_query($conn, "DELETE FROM follow_requests WHERE follower_id=$current_user_id AND following_id=$target_user_id");

        // Remove notification
        deleteNotification($conn, $target_user_id, $current_user_id, 'follow');

        $response['success'] = true;
        $response['action'] = 'unfollowed';
        $response['message'] = 'Unfollowed';
    }

    // Update count
    if ($response['success']) {
        $count_res = mysqli_query($conn, "SELECT COUNT(*) as c FROM follows WHERE following_id=$target_user_id");
        $row = mysqli_fetch_assoc($count_res);
        $response['follower_count'] = $row['c'];
    }

} else {
    $response['message'] = 'Invalid request';
}

echo json_encode($response);
?>