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
$username = $_SESSION["username"];
$post_id = isset($_POST['post_id']) ? intval($_POST['post_id']) : 0;
$reaction_type = isset($_POST['reaction_type']) ? $_POST['reaction_type'] : 'like';

$allowed_reactions = ['like', 'love', 'haha', 'wow', 'sad', 'angry'];
if (!in_array($reaction_type, $allowed_reactions)) {
    echo json_encode(['success' => false, 'message' => 'Invalid reaction type']);
    exit;
}

// Check if user already reacted
$check_sql = "SELECT reaction_id, reaction_type FROM reactions WHERE post_id = ? AND user_id = ?";
if ($stmt = mysqli_prepare($conn, $check_sql)) {
    mysqli_stmt_bind_param($stmt, "ii", $post_id, $user_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    if (mysqli_num_rows($result) == 1) {
        // User already reacted - update or remove
        $row = mysqli_fetch_assoc($result);

        if ($row['reaction_type'] == $reaction_type) {
            // Same reaction - remove it
            $delete_sql = "DELETE FROM reactions WHERE post_id = ? AND user_id = ?";
            if ($delete_stmt = mysqli_prepare($conn, $delete_sql)) {
                mysqli_stmt_bind_param($delete_stmt, "ii", $post_id, $user_id);

                if (mysqli_stmt_execute($delete_stmt)) {
                    // 🔔 DELETE NOTIFICATION (ADDED HERE)
                    $owner_sql = "SELECT user_id FROM posts WHERE post_id = ?";
                    if ($owner_stmt = mysqli_prepare($conn, $owner_sql)) {
                        mysqli_stmt_bind_param($owner_stmt, "i", $post_id);
                        mysqli_stmt_execute($owner_stmt);
                        $owner_result = mysqli_stmt_get_result($owner_stmt);

                        if ($owner_row = mysqli_fetch_assoc($owner_result)) {
                            $post_owner_id = $owner_row['user_id'];
                            deleteNotification($conn, $post_owner_id, $user_id, 'reaction', $post_id);
                        }
                        mysqli_stmt_close($owner_stmt);
                    }

                    echo json_encode(['success' => true, 'action' => 'removed']);
                }
                mysqli_stmt_close($delete_stmt);
            }
        } else {
            // Different reaction - update it (notification already exists, no need to recreate)
            $update_sql = "UPDATE reactions SET reaction_type = ? WHERE post_id = ? AND user_id = ?";
            if ($update_stmt = mysqli_prepare($conn, $update_sql)) {
                mysqli_stmt_bind_param($update_stmt, "sii", $reaction_type, $post_id, $user_id);
                mysqli_stmt_execute($update_stmt);
                echo json_encode(['success' => true, 'action' => 'updated', 'reaction' => $reaction_type]);
                mysqli_stmt_close($update_stmt);
            }
        }
    } else {
        // No reaction yet - insert new
        $insert_sql = "INSERT INTO reactions (post_id, user_id, username, reaction_type) VALUES (?, ?, ?, ?)";
        if ($insert_stmt = mysqli_prepare($conn, $insert_sql)) {
            mysqli_stmt_bind_param($insert_stmt, "iiss", $post_id, $user_id, $username, $reaction_type);

            if (mysqli_stmt_execute($insert_stmt)) {
                // 🔔 CREATE NOTIFICATION (ADDED HERE)
                $owner_sql = "SELECT user_id FROM posts WHERE post_id = ?";
                if ($owner_stmt = mysqli_prepare($conn, $owner_sql)) {
                    mysqli_stmt_bind_param($owner_stmt, "i", $post_id);
                    mysqli_stmt_execute($owner_stmt);
                    $owner_result = mysqli_stmt_get_result($owner_stmt);

                    if ($owner_row = mysqli_fetch_assoc($owner_result)) {
                        $post_owner_id = $owner_row['user_id'];
                        createNotification($conn, $post_owner_id, $user_id, 'reaction', $post_id);
                    }
                    mysqli_stmt_close($owner_stmt);
                }

                echo json_encode(['success' => true, 'action' => 'added', 'reaction' => $reaction_type]);
            }
            mysqli_stmt_close($insert_stmt);
        }
    }

    mysqli_stmt_close($stmt);
}

mysqli_close($conn);
?>
