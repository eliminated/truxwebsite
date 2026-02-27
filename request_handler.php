<?php
require_once "config.php";
require_once "notification_helper.php";

header('Content-Type: application/json');

if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit;
}

$current_user_id = $_SESSION["user_id"];

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && isset($_POST['requester_id'])) {

    $action = $_POST['action']; // 'approve' or 'reject'
    $requester_id = intval($_POST['requester_id']); // The person who asked to follow YOU

    // Verify the request actually exists
    $check_sql = "SELECT request_id FROM follow_requests WHERE follower_id = ? AND following_id = ?";
    if ($stmt = mysqli_prepare($conn, $check_sql)) {
        mysqli_stmt_bind_param($stmt, "ii", $requester_id, $current_user_id);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_store_result($stmt);

        if (mysqli_stmt_num_rows($stmt) == 0) {
            echo json_encode(['success' => false, 'message' => 'Request not found or already processed']);
            exit;
        }
        mysqli_stmt_close($stmt);
    }

    if ($action == 'approve') {
        // 1. Insert into 'follows' table
        $insert_sql = "INSERT INTO follows (follower_id, following_id) VALUES (?, ?)";
        if ($stmt = mysqli_prepare($conn, $insert_sql)) {
            mysqli_stmt_bind_param($stmt, "ii", $requester_id, $current_user_id);
            if (mysqli_stmt_execute($stmt)) {

                // 2. Delete from 'follow_requests'
                $delete_sql = "DELETE FROM follow_requests WHERE follower_id = ? AND following_id = ?";
                if ($del_stmt = mysqli_prepare($conn, $delete_sql)) {
                    mysqli_stmt_bind_param($del_stmt, "ii", $requester_id, $current_user_id);
                    mysqli_stmt_execute($del_stmt);
                    mysqli_stmt_close($del_stmt);
                }

                // 3. Notify the requester that you accepted
                // Note: We need to make sure 'system' or 'follow' type handles this text in your frontend
                // For now we use 'system' type or a new 'request_accepted' type if you prefer.
                // Let's use 'follow' type but the text will be handled by the frontend knowing it's a "follow back" or just an accept.
                // Actually, standard practice is to notify them:
                createNotification($conn, $requester_id, $current_user_id, 'system', null, null);
                // You might want to update notification_helper.php later to support specific "accepted" text

                echo json_encode(['success' => true, 'message' => 'Request approved']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Error approving request']);
            }
            mysqli_stmt_close($stmt);
        }

    } elseif ($action == 'reject') {
        // Just delete the request
        $delete_sql = "DELETE FROM follow_requests WHERE follower_id = ? AND following_id = ?";
        if ($stmt = mysqli_prepare($conn, $delete_sql)) {
            mysqli_stmt_bind_param($stmt, "ii", $requester_id, $current_user_id);
            if (mysqli_stmt_execute($stmt)) {
                echo json_encode(['success' => true, 'message' => 'Request rejected']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Error rejecting request']);
            }
            mysqli_stmt_close($stmt);
        }
    }

} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
}
?>