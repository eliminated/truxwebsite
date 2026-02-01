<?php
require_once "config.php";

header('Content-Type: application/json');

if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit;
}

$user_id = $_SESSION["user_id"];
$response = ['success' => false, 'message' => ''];

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action'])) {
    $action = $_POST['action'];

    if ($action === 'toggle_privacy') {
        $is_private = isset($_POST['is_private']) ? intval($_POST['is_private']) : 0;

        // Validate input (should be 0 or 1)
        if (!in_array($is_private, [0, 1])) {
            echo json_encode(['success' => false, 'message' => 'Invalid value']);
            exit;
        }

        $sql = "UPDATE users SET is_private = ? WHERE id = ?";
        if ($stmt = mysqli_prepare($conn, $sql)) {
            mysqli_stmt_bind_param($stmt, "ii", $is_private, $user_id);

            if (mysqli_stmt_execute($stmt)) {
                $response['success'] = true;
                $response['message'] = $is_private ? 'Account is now private' : 'Account is now public';
                $response['is_private'] = $is_private;
            } else {
                $response['message'] = 'Failed to update privacy setting';
            }
            mysqli_stmt_close($stmt);
        }
    } else {
        $response['message'] = 'Invalid action';
    }
} else {
    $response['message'] = 'Invalid request';
}

echo json_encode($response);
mysqli_close($conn);
?>
