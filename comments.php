<?php
ob_start();
require_once "config.php";
require_once "notification_helper.php";
ob_end_clean();

header('Content-Type: application/json');


if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit;
}

$user_id = $_SESSION["user_id"];
$username = $_SESSION["username"];
$action = isset($_POST['action']) ? $_POST['action'] : 'get';

// GET COMMENTS
if ($action == 'get') {
    $post_id = isset($_GET['post_id']) ? intval($_GET['post_id']) : 0;

    $sql = "SELECT c.*, u.username, u.email 
            FROM comments c 
            JOIN users u ON c.user_id = u.id 
            WHERE c.post_id = ? 
            ORDER BY c.created_at ASC";

    if ($stmt = mysqli_prepare($conn, $sql)) {
        mysqli_stmt_bind_param($stmt, "i", $post_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);

        $comments = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $row['is_own'] = ($row['user_id'] == $user_id);
            $row['first_letter'] = strtoupper(substr($row['username'], 0, 1));
            $row['time_ago'] = timeAgo($row['created_at']);
            $comments[] = $row;
        }

        echo json_encode(['success' => true, 'comments' => $comments]);
        mysqli_stmt_close($stmt);
    }
}

// ADD COMMENT
elseif ($action == 'add') {
    $post_id = isset($_POST['post_id']) ? intval($_POST['post_id']) : 0;
    $comment_text = isset($_POST['comment_text']) ? trim($_POST['comment_text']) : '';
    $parent_comment_id = isset($_POST['parent_comment_id']) && !empty($_POST['parent_comment_id']) ? intval($_POST['parent_comment_id']) : null;

    if (empty($comment_text)) {
        echo json_encode(['success' => false, 'message' => 'Comment cannot be empty']);
        exit;
    }

    if (strlen($comment_text) > 1000) {
        echo json_encode(['success' => false, 'message' => 'Comment too long']);
        exit;
    }

    $sql = "INSERT INTO comments (post_id, user_id, username, parent_comment_id, comment_text) VALUES (?, ?, ?, ?, ?)";
    if ($stmt = mysqli_prepare($conn, $sql)) {
        mysqli_stmt_bind_param($stmt, "iisis", $post_id, $user_id, $username, $parent_comment_id, $comment_text);

        if (mysqli_stmt_execute($stmt)) {
            $comment_id = mysqli_insert_id($conn);

            // 🔔 CREATE NOTIFICATION (FIXED: $user_id instead of $current_user_id)
            $owner_sql = "SELECT user_id FROM posts WHERE post_id = ?";
            if ($owner_stmt = mysqli_prepare($conn, $owner_sql)) {
                mysqli_stmt_bind_param($owner_stmt, "i", $post_id);
                mysqli_stmt_execute($owner_stmt);
                $owner_result = mysqli_stmt_get_result($owner_stmt);

                if ($owner_row = mysqli_fetch_assoc($owner_result)) {
                    $post_owner_id = $owner_row['user_id'];

                    // Create notification for post owner (won't notify if commenting on own post)
                    createNotification($conn, $post_owner_id, $user_id, 'comment', $post_id, $comment_id);
                }
                mysqli_stmt_close($owner_stmt);
            }

            echo json_encode(['success' => true, 'message' => 'Comment added', 'comment_id' => $comment_id]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to add comment']);
        }
        mysqli_stmt_close($stmt);
    }
}

// DELETE COMMENT
elseif ($action == 'delete') {
    $comment_id = isset($_POST['comment_id']) ? intval($_POST['comment_id']) : 0;

    // Verify ownership
    $check_sql = "SELECT user_id, post_id FROM comments WHERE comment_id = ? AND user_id = ?";
    if ($stmt = mysqli_prepare($conn, $check_sql)) {
        mysqli_stmt_bind_param($stmt, "ii", $comment_id, $user_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);

        if (mysqli_num_rows($result) == 1) {
            $row = mysqli_fetch_assoc($result);
            $post_id = $row['post_id'];

            // Delete comment
            $delete_sql = "DELETE FROM comments WHERE comment_id = ?";
            if ($delete_stmt = mysqli_prepare($conn, $delete_sql)) {
                mysqli_stmt_bind_param($delete_stmt, "i", $comment_id);

                if (mysqli_stmt_execute($delete_stmt)) {
                    // 🔔 DELETE NOTIFICATION (ADDED)
                    $owner_sql = "SELECT user_id FROM posts WHERE post_id = ?";
                    if ($owner_stmt = mysqli_prepare($conn, $owner_sql)) {
                        mysqli_stmt_bind_param($owner_stmt, "i", $post_id);
                        mysqli_stmt_execute($owner_stmt);
                        $owner_result = mysqli_stmt_get_result($owner_stmt);

                        if ($owner_row = mysqli_fetch_assoc($owner_result)) {
                            $post_owner_id = $owner_row['user_id'];
                            deleteNotification($conn, $post_owner_id, $user_id, 'comment', $post_id);
                        }
                        mysqli_stmt_close($owner_stmt);
                    }

                    echo json_encode(['success' => true, 'message' => 'Comment deleted']);
                }
                mysqli_stmt_close($delete_stmt);
            }
        } else {
            echo json_encode(['success' => false, 'message' => 'Comment not found or unauthorized']);
        }
        mysqli_stmt_close($stmt);
    }
}

mysqli_close($conn);

// Helper function for time ago
function timeAgo($timestamp)
{
    $time = strtotime($timestamp);
    $diff = time() - $time;

    if ($diff < 60)
        return 'just now';
    if ($diff < 3600)
        return floor($diff / 60) . ' min ago';
    if ($diff < 86400)
        return floor($diff / 3600) . ' hr ago';
    if ($diff < 604800)
        return floor($diff / 86400) . ' day ago';
    return date('M d, Y', $time);
}

