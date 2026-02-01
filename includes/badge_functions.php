<?php
// Get user's badges
function getUserBadges($user_id, $conn)
{
    $badges = [];

    $sql = "SELECT b.* FROM badges b
        JOIN user_badges ub ON b.badge_id = ub.badge_id
        WHERE ub.user_id = ?";



    if ($stmt = mysqli_prepare($conn, $sql)) {
        mysqli_stmt_bind_param($stmt, "i", $user_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);

        while ($row = mysqli_fetch_assoc($result)) {
            $badges[] = $row;
        }
        mysqli_stmt_close($stmt);
    }

    return $badges;
}

// Get user's role badge
function getUserRoleBadge($user_role)
{
    $role_badges = [
        'developer' => ['icon' => '👨‍💻', 'label' => 'Developer', 'color' => '#667eea'],
        'admin' => ['icon' => '🛡️', 'label' => 'Admin', 'color' => '#e74c3c'],
        'moderator' => ['icon' => '⚔️', 'label' => 'Moderator', 'color' => '#f39c12']
    ];

    return isset($role_badges[$user_role]) ? $role_badges[$user_role] : null;
}

// Display badge HTML
function displayBadge($badge)
{
    return '<span class="badge" style="background-color: ' . htmlspecialchars($badge['badge_color']) . ';" title="' . htmlspecialchars($badge['badge_description']) . '">'
        . $badge['badge_icon'] . ' ' . htmlspecialchars($badge['badge_name']) . '</span>';
}

// Display role badge HTML
function displayRoleBadge($role_badge)
{
    if (!$role_badge)
        return '';

    return '<span class="role-badge" style="background-color: ' . $role_badge['color'] . ';" title="' . $role_badge['label'] . '">'
        . $role_badge['icon'] . ' ' . $role_badge['label'] . '</span>';
}

// Display verified checkmark
function displayVerifiedCheck($is_verified)
{
    if ($is_verified) {
        return '<span class="verified-check" title="Verified Account">✓</span>';
    }
    return '';
}
?>
