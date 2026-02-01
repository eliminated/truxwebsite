<?php
require_once "config.php";

header('Content-Type: application/json');

if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit;
}

$post_id = isset($_GET['post_id']) ? intval($_GET['post_id']) : 0;

// Get current post content
$current_sql = "SELECT content, created_at FROM posts WHERE post_id = ?";
if ($stmt = mysqli_prepare($conn, $current_sql)) {
    mysqli_stmt_bind_param($stmt, "i", $post_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $current = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);
}

// Get edit history
$history_sql = "SELECT eh.*, u.username 
                FROM post_edit_history eh
                JOIN users u ON eh.edited_by = u.id
                WHERE eh.post_id = ?
                ORDER BY eh.edited_at ASC";

$history = [];
if ($stmt = mysqli_prepare($conn, $history_sql)) {
    mysqli_stmt_bind_param($stmt, "i", $post_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    while ($row = mysqli_fetch_assoc($result)) {
        $history[] = $row;
    }
    mysqli_stmt_close($stmt);
}

// Build response
$response = [
    'success' => true,
    'original' => [
        'content' => $current ? $current['content'] : '',
        'timestamp' => $current ? $current['created_at'] : ''
    ],
    'edits' => $history
];

echo json_encode($response);
mysqli_close($conn);
?>
