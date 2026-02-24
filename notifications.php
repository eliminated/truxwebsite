<?php
ob_start();
require_once "config.php";
ob_end_clean();

header('Content-Type: application/json');

if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit;
}

$current_user_id = $_SESSION["user_id"];
$response = ['success' => false, 'message' => ''];

// Handle different actions
$action = $_GET['action'] ?? '';

switch ($action) {
    case 'fetch':
        $limit = isset($_GET['limit']) ? intval($_GET['limit']) : 20;

        // Modified query to fetch details including actor info
        $sql = "SELECT n.*,
                       u.username as actor_username,
                       u.profile_picture as actor_profile_pic,
                       u.user_role as actor_role,
                       p.content as post_content
                FROM notifications n
                JOIN users u ON n.actor_id = u.id
                LEFT JOIN posts p ON n.post_id = p.post_id
                WHERE n.user_id = ?
                ORDER BY n.created_at DESC
                LIMIT ?";

        if ($stmt = mysqli_prepare($conn, $sql)) {
            mysqli_stmt_bind_param($stmt, "ii", $current_user_id, $limit);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);

            $notifications = [];
            while ($row = mysqli_fetch_assoc($result)) {

                // Special check for Follow Requests:
                // Is the request still pending? If not, we shouldn't show the buttons anymore.
                $is_pending = false;
                if ($row['type'] == 'follow_request') {
                    $check_req = "SELECT request_id FROM follow_requests WHERE follower_id = ? AND following_id = ?";
                    $req_stmt = mysqli_prepare($conn, $check_req);
                    mysqli_stmt_bind_param($req_stmt, "ii", $row['actor_id'], $current_user_id);
                    mysqli_stmt_execute($req_stmt);
                    mysqli_stmt_store_result($req_stmt);
                    if (mysqli_stmt_num_rows($req_stmt) > 0) {
                        $is_pending = true;
                    }
                    mysqli_stmt_close($req_stmt);
                }

                $notifications[] = [
                    'id' => (int) $row['notification_id'], // Ensure this matches your DB column name (id or notification_id)
                    'type' => $row['type'],
                    'actor_id' => (int) $row['actor_id'], // Needed for Approve/Reject
                    'actor_username' => $row['actor_username'],
                    'actor_profile_pic' => $row['actor_profile_pic'],
                    'post_id' => $row['post_id'],
                    'is_read' => (bool) $row['is_read'],
                    'is_pending' => $is_pending, // Send this to JS
                    'time_ago' => getTimeAgo($row['created_at'])
                ];
            }
            $response['success'] = true;
            $response['notifications'] = $notifications;
            mysqli_stmt_close($stmt);
        }
        break;

    case 'count':
        // Get unread count
        $sql = "SELECT COUNT(*) as unread_count FROM notifications WHERE user_id = ? AND is_read = 0";
        if ($stmt = mysqli_prepare($conn, $sql)) {
            mysqli_stmt_bind_param($stmt, "i", $current_user_id);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            $row = mysqli_fetch_assoc($result);

            $response['success'] = true;
            $response['unread_count'] = (int) $row['unread_count'];
            mysqli_stmt_close($stmt);
        } else {
            $response['message'] = 'Database error: ' . mysqli_error($conn);
        }
        break;

    case 'mark_read':
        // Mark notification(s) as read
        if (isset($_POST['notification_id'])) {
            $notification_id = intval($_POST['notification_id']);
            $sql = "UPDATE notifications SET is_read = 1 WHERE notification_id = ? AND user_id = ?";
            if ($stmt = mysqli_prepare($conn, $sql)) {
                mysqli_stmt_bind_param($stmt, "ii", $notification_id, $current_user_id);
                if (mysqli_stmt_execute($stmt)) {
                    $response['success'] = true;
                    $response['message'] = 'Marked as read';
                } else {
                    $response['message'] = 'Failed to mark as read';
                }
                mysqli_stmt_close($stmt);
            }
        } else {
            $response['message'] = 'Missing notification_id';
        }
        break;

    case 'mark_all_read':
        // Mark all notifications as read
        $sql = "UPDATE notifications SET is_read = 1 WHERE user_id = ? AND is_read = 0";
        if ($stmt = mysqli_prepare($conn, $sql)) {
            mysqli_stmt_bind_param($stmt, "i", $current_user_id);
            if (mysqli_stmt_execute($stmt)) {
                $response['success'] = true;
                $response['message'] = 'All marked as read';
            } else {
                $response['message'] = 'Failed to mark all as read';
            }
            mysqli_stmt_close($stmt);
        }
        break;

    case 'delete':
        // Delete a notification
        if (isset($_POST['notification_id'])) {
            $notification_id = intval($_POST['notification_id']);
            $sql = "DELETE FROM notifications WHERE notification_id = ? AND user_id = ?";
            if ($stmt = mysqli_prepare($conn, $sql)) {
                mysqli_stmt_bind_param($stmt, "ii", $notification_id, $current_user_id);
                if (mysqli_stmt_execute($stmt)) {
                    $response['success'] = true;
                    $response['message'] = 'Notification deleted';
                } else {
                    $response['message'] = 'Failed to delete notification';
                }
                mysqli_stmt_close($stmt);
            }
        } else {
            $response['message'] = 'Missing notification_id';
        }
        break;

    default:
        $response['message'] = 'Invalid action';
}

echo json_encode($response);
exit;

// Helper function for time ago
function getTimeAgo($timestamp)
{
    $time_ago = strtotime($timestamp);
    $current_time = time();
    $time_difference = $current_time - $time_ago;

    $seconds = $time_difference;
    $minutes = round($seconds / 60);
    $hours = round($seconds / 3600);
    $days = round($seconds / 86400);
    $weeks = round($seconds / 604800);
    $months = round($seconds / 2629440);
    $years = round($seconds / 31553280);

    if ($seconds <= 60) {
        return "Just now";
    } else if ($minutes <= 60) {
        return "$minutes min ago";
    } else if ($hours <= 24) {
        return "$hours hour" . ($hours > 1 ? "s" : "") . " ago";
    } else if ($days <= 7) {
        return "$days day" . ($days > 1 ? "s" : "") . " ago";
    } else if ($weeks <= 4.3) {
        return "$weeks week" . ($weeks > 1 ? "s" : "") . " ago";
    } else if ($months <= 12) {
        return "$months month" . ($months > 1 ? "s" : "") . " ago";
    } else {
        return "$years year" . ($years > 1 ? "s" : "") . " ago";
    }
}
