<?php
require_once "config.php";

header('Content-Type: application/json');

if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    echo json_encode(['success' => false, 'message' => 'Not authenticated', 'users' => []]);
    exit;
}

$current_user_id = $_SESSION["user_id"];
$response = ['success' => false, 'users' => [], 'message' => ''];

if ($_SERVER["REQUEST_METHOD"] == "GET" && isset($_GET['query'])) {

    $search_query = trim($_GET['query']);
    $filter_verified = isset($_GET['verified']) && $_GET['verified'] === 'true';
    $filter_role = isset($_GET['role']) ? $_GET['role'] : '';

    // Minimum search length
    if (strlen($search_query) < 2) {
        $response['message'] = 'Search query too short';
        echo json_encode($response);
        exit;
    }

    // Build SQL query with filters
    $sql = "SELECT u.id, u.username, u.email, u.profile_picture, u.bio, u.is_verified, u.user_role,
            (SELECT COUNT(*) FROM follows WHERE follower_id = ? AND following_id = u.id) as is_following,
            (SELECT COUNT(*) FROM follows WHERE following_id = u.id) as follower_count
            FROM users u
            WHERE (u.username LIKE ? OR u.email LIKE ? OR u.bio LIKE ?)
            AND u.id != ?";

    // Add verified filter
    if ($filter_verified) {
        $sql .= " AND u.is_verified = 1";
    }

    // Add role filter
    $has_role_filter = !empty($filter_role) && in_array($filter_role, ['admin', 'moderator', 'developer', 'user']);
    if ($has_role_filter) {
        $sql .= " AND u.user_role = ?";
    }

    $sql .= " ORDER BY u.is_verified DESC, follower_count DESC LIMIT 10";

    if ($stmt = mysqli_prepare($conn, $sql)) {
        $search_param = "%{$search_query}%";

        // FIXED: Correct parameter binding
        if ($has_role_filter) {
            // Format: i-sssi-s (6 parameters)
            // 1st i: current_user_id (for is_following check)
            // 3x s: search_param (username, email, bio)
            // 2nd i: current_user_id (for u.id != ?)
            // Last s: filter_role
            mysqli_stmt_bind_param(
                $stmt,
                "isssis",
                $current_user_id,   // i - for follows check
                $search_param,      // s - username LIKE
                $search_param,      // s - email LIKE
                $search_param,      // s - bio LIKE
                $current_user_id,   // i - for u.id != ?
                $filter_role        // s - for u.user_role = ?
            );
        } else {
            // Format: i-sss-i (5 parameters)
            mysqli_stmt_bind_param(
                $stmt,
                "isssi",
                $current_user_id,   // i - for follows check
                $search_param,      // s - username LIKE
                $search_param,      // s - email LIKE
                $search_param,      // s - bio LIKE
                $current_user_id    // i - for u.id != ?
            );
        }

        if (mysqli_stmt_execute($stmt)) {
            $result = mysqli_stmt_get_result($stmt);

            $users = [];
            while ($row = mysqli_fetch_assoc($result)) {
                $users[] = [
                    'id' => $row['id'],
                    'username' => $row['username'],
                    'email' => $row['email'],
                    'profile_picture' => $row['profile_picture'],
                    'bio' => $row['bio'] ?? '',
                    'is_verified' => (bool) $row['is_verified'],
                    'user_role' => $row['user_role'],
                    'is_following' => (bool) $row['is_following'],
                    'follower_count' => (int) $row['follower_count']
                ];
            }

            $response['success'] = true;
            $response['users'] = $users;
            $response['message'] = count($users) > 0 ? 'Users found' : 'No users found';
        } else {
            $response['message'] = 'Query execution failed: ' . mysqli_stmt_error($stmt);
        }

        mysqli_stmt_close($stmt);
    } else {
        $response['message'] = 'Database prepare error: ' . mysqli_error($conn);
    }

} else {
    $response['message'] = 'Invalid request';
}

echo json_encode($response);
?>
