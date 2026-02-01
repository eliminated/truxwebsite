<?php
ob_start();
require_once "config.php";
ob_end_clean();

header('Content-Type: application/json');


if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit;
}

$user_id = $_SESSION["user_id"];
$action = isset($_POST['action']) ? $_POST['action'] : '';

// DELETE POST
if ($action == 'delete') {
    $post_id = isset($_POST['post_id']) ? intval($_POST['post_id']) : 0;

    // Verify ownership
    $check_sql = "SELECT user_id, media_path FROM posts WHERE post_id = ? AND user_id = ?";
    if ($stmt = mysqli_prepare($conn, $check_sql)) {
        mysqli_stmt_bind_param($stmt, "ii", $post_id, $user_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);

        if (mysqli_num_rows($result) == 1) {
            $post = mysqli_fetch_assoc($result);

            // Delete media file if exists
            if (!empty($post['media_path']) && file_exists($post['media_path'])) {
                unlink($post['media_path']);
            }

            // Delete post from database (cascades to reactions and comments)
            $delete_sql = "DELETE FROM posts WHERE post_id = ?";
            if ($delete_stmt = mysqli_prepare($conn, $delete_sql)) {
                mysqli_stmt_bind_param($delete_stmt, "i", $post_id);
                if (mysqli_stmt_execute($delete_stmt)) {
                    echo json_encode(['success' => true, 'message' => 'Post deleted successfully']);
                } else {
                    echo json_encode(['success' => false, 'message' => 'Failed to delete post']);
                }
                mysqli_stmt_close($delete_stmt);
            }
        } else {
            echo json_encode(['success' => false, 'message' => 'Post not found or unauthorized']);
        }
        mysqli_stmt_close($stmt);
    }
}

// EDIT POST
elseif ($action == 'edit') {
    $post_id = isset($_POST['post_id']) ? intval($_POST['post_id']) : 0;
    $content = isset($_POST['content']) ? trim($_POST['content']) : '';

    if (strlen($content) > 5000) {
        echo json_encode(['success' => false, 'message' => 'Content exceeds 5000 characters']);
        exit;
    }

    // Verify ownership and get old content
    $check_sql = "SELECT user_id, content FROM posts WHERE post_id = ? AND user_id = ?";
    if ($stmt = mysqli_prepare($conn, $check_sql)) {
        mysqli_stmt_bind_param($stmt, "ii", $post_id, $user_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);

        if (mysqli_num_rows($result) == 1) {
            $old_post = mysqli_fetch_assoc($result);
            $old_content = $old_post['content'];

            // Save to edit history
            $history_sql = "INSERT INTO post_edit_history (post_id, content_before, content_after, edited_by) VALUES (?, ?, ?, ?)";
            if ($history_stmt = mysqli_prepare($conn, $history_sql)) {
                mysqli_stmt_bind_param($history_stmt, "issi", $post_id, $old_content, $content, $user_id);
                mysqli_stmt_execute($history_stmt);
                mysqli_stmt_close($history_stmt);
            }

            // Update post with new content and timestamp
            $update_sql = "UPDATE posts SET content = ?, edited_at = CURRENT_TIMESTAMP, edit_count = edit_count + 1 WHERE post_id = ?";
            if ($update_stmt = mysqli_prepare($conn, $update_sql)) {
                mysqli_stmt_bind_param($update_stmt, "si", $content, $post_id);
                if (mysqli_stmt_execute($update_stmt)) {
                    echo json_encode(['success' => true, 'message' => 'Post updated successfully']);
                } else {
                    echo json_encode(['success' => false, 'message' => 'Failed to update post']);
                }
                mysqli_stmt_close($update_stmt);
            }
        } else {
            echo json_encode(['success' => false, 'message' => 'Post not found or unauthorized']);
        }
        mysqli_stmt_close($stmt);
    }
}


// PIN POST
elseif ($action == 'pin') {
    $post_id = isset($_POST['post_id']) ? intval($_POST['post_id']) : 0;

    // Verify ownership
    $check_sql = "SELECT user_id FROM posts WHERE post_id = ? AND user_id = ?";
    if ($stmt = mysqli_prepare($conn, $check_sql)) {
        mysqli_stmt_bind_param($stmt, "ii", $post_id, $user_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);

        if (mysqli_num_rows($result) == 1) {
            $update_sql = "UPDATE posts SET is_pinned = 1 WHERE post_id = ?";
            if ($update_stmt = mysqli_prepare($conn, $update_sql)) {
                mysqli_stmt_bind_param($update_stmt, "i", $post_id);
                if (mysqli_stmt_execute($update_stmt)) {
                    echo json_encode(['success' => true, 'message' => 'Post pinned successfully']);
                } else {
                    echo json_encode(['success' => false, 'message' => 'Failed to pin post']);
                }
                mysqli_stmt_close($update_stmt);
            }
        } else {
            echo json_encode(['success' => false, 'message' => 'Post not found or unauthorized']);
        }
        mysqli_stmt_close($stmt);
    }
}

// UNPIN POST
elseif ($action == 'unpin') {
    $post_id = isset($_POST['post_id']) ? intval($_POST['post_id']) : 0;

    // Verify ownership
    $check_sql = "SELECT user_id FROM posts WHERE post_id = ? AND user_id = ?";
    if ($stmt = mysqli_prepare($conn, $check_sql)) {
        mysqli_stmt_bind_param($stmt, "ii", $post_id, $user_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);

        if (mysqli_num_rows($result) == 1) {
            $update_sql = "UPDATE posts SET is_pinned = 0 WHERE post_id = ?";
            if ($update_stmt = mysqli_prepare($conn, $update_sql)) {
                mysqli_stmt_bind_param($update_stmt, "i", $post_id);
                if (mysqli_stmt_execute($update_stmt)) {
                    echo json_encode(['success' => true, 'message' => 'Post unpinned successfully']);
                } else {
                    echo json_encode(['success' => false, 'message' => 'Failed to unpin post']);
                }
                mysqli_stmt_close($update_stmt);
            }
        } else {
            echo json_encode(['success' => false, 'message' => 'Post not found or unauthorized']);
        }
        mysqli_stmt_close($stmt);
    }
}

// SAVE POST
elseif ($action == 'save') {
    $post_id = isset($_POST['post_id']) ? intval($_POST['post_id']) : 0;

    $sql = "INSERT INTO saved_posts (post_id, user_id) VALUES (?, ?)";
    if ($stmt = mysqli_prepare($conn, $sql)) {
        mysqli_stmt_bind_param($stmt, "ii", $post_id, $user_id);
        if (mysqli_stmt_execute($stmt)) {
            echo json_encode(['success' => true, 'message' => 'Post saved']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Already saved or error']);
        }
        mysqli_stmt_close($stmt);
    }
}

// UNSAVE POST
elseif ($action == 'unsave') {
    $post_id = isset($_POST['post_id']) ? intval($_POST['post_id']) : 0;

    $sql = "DELETE FROM saved_posts WHERE post_id = ? AND user_id = ?";
    if ($stmt = mysqli_prepare($conn, $sql)) {
        mysqli_stmt_bind_param($stmt, "ii", $post_id, $user_id);
        if (mysqli_stmt_execute($stmt)) {
            echo json_encode(['success' => true, 'message' => 'Post unsaved']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Error unsaving post']);
        }
        mysqli_stmt_close($stmt);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid action']);
}

mysqli_close($conn);
?>
