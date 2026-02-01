<?php
// Helper function to create notifications
function createNotification($conn, $user_id, $actor_id, $type, $post_id = null, $comment_id = null)
{
    // Don't notify yourself
    if ($user_id == $actor_id) {
        return false;
    }

    // Check for duplicate recent notifications (within 5 minutes)
    $check_sql = "SELECT notification_id FROM notifications 
              WHERE user_id = ? 
              AND actor_id = ? 
              AND type = ? 
              AND created_at > DATE_SUB(NOW(), INTERVAL 5 MINUTE)
              LIMIT 1";


    if ($check_stmt = mysqli_prepare($conn, $check_sql)) {
        mysqli_stmt_bind_param($check_stmt, "iis", $user_id, $actor_id, $type);
        mysqli_stmt_execute($check_stmt);
        mysqli_stmt_store_result($check_stmt);

        if (mysqli_stmt_num_rows($check_stmt) > 0) {
            mysqli_stmt_close($check_stmt);
            return false; // Duplicate notification, skip
        }
        mysqli_stmt_close($check_stmt);
    }

    // Insert notification
    $sql = "INSERT INTO notifications (user_id, actor_id, type, post_id, comment_id) 
            VALUES (?, ?, ?, ?, ?)";

    if ($stmt = mysqli_prepare($conn, $sql)) {
        mysqli_stmt_bind_param($stmt, "iisii", $user_id, $actor_id, $type, $post_id, $comment_id);
        $result = mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
        return $result;
    }

    return false;
}

// Delete notification (e.g., when unfollowing)
function deleteNotification($conn, $user_id, $actor_id, $type, $post_id = null)
{
    $sql = "DELETE FROM notifications 
            WHERE user_id = ? 
            AND actor_id = ? 
            AND type = ?";

    if ($post_id !== null) {
        $sql .= " AND post_id = ?";
    }

    if ($stmt = mysqli_prepare($conn, $sql)) {
        if ($post_id !== null) {
            mysqli_stmt_bind_param($stmt, "iisi", $user_id, $actor_id, $type, $post_id);
        } else {
            mysqli_stmt_bind_param($stmt, "iis", $user_id, $actor_id, $type);
        }
        $result = mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
        return $result;
    }

    return false;
}

