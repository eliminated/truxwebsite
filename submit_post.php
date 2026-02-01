<?php
require_once "config.php";

header('Content-Type: application/json');

if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit;
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $user_id = $_SESSION["user_id"];
    $username = $_SESSION["username"];
    $content = isset($_POST['content']) ? trim($_POST['content']) : '';
    $media_type = 'none';
    $media_path = null;
    $link_url = null;
    $code_snippet = null;
    $code_language = null;

    // Validate content length
    if (strlen($content) > 5000) {
        echo json_encode(['success' => false, 'message' => 'Content exceeds 5000 characters']);
        exit;
    }

    // Handle image upload
    if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
        $media_type = 'image';

        // Validate file type using finfo
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime_type = finfo_file($finfo, $_FILES['image']['tmp_name']);
        finfo_close($finfo);

        $allowed_types = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];

        if (!in_array($mime_type, $allowed_types)) {
            echo json_encode(['success' => false, 'message' => 'Invalid image type. Only JPG, PNG, GIF allowed.']);
            exit;
        }

        // Validate file size (5MB max)
        if ($_FILES['image']['size'] > 5 * 1024 * 1024) {
            echo json_encode(['success' => false, 'message' => 'Image size must be less than 5MB']);
            exit;
        }

        // Generate unique filename
        $extension = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
        $filename = uniqid('img_', true) . '.' . $extension;
        $upload_path = 'uploads/images/' . $filename;

        if (move_uploaded_file($_FILES['image']['tmp_name'], $upload_path)) {
            $media_path = $upload_path;
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to upload image']);
            exit;
        }
    }

    // Handle video upload
    elseif (isset($_FILES['video']) && $_FILES['video']['error'] == 0) {
        $media_type = 'video';

        // Validate file type
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime_type = finfo_file($finfo, $_FILES['video']['tmp_name']);
        finfo_close($finfo);

        $allowed_types = ['video/mp4', 'video/webm', 'video/quicktime'];

        if (!in_array($mime_type, $allowed_types)) {
            echo json_encode(['success' => false, 'message' => 'Invalid video type. Only MP4, WEBM, MOV allowed.']);
            exit;
        }

        // Validate file size (50MB max)
        if ($_FILES['video']['size'] > 50 * 1024 * 1024) {
            echo json_encode(['success' => false, 'message' => 'Video size must be less than 50MB']);
            exit;
        }

        // Generate unique filename
        $extension = pathinfo($_FILES['video']['name'], PATHINFO_EXTENSION);
        $filename = uniqid('vid_', true) . '.' . $extension;
        $upload_path = 'uploads/videos/' . $filename;

        if (move_uploaded_file($_FILES['video']['tmp_name'], $upload_path)) {
            $media_path = $upload_path;
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to upload video']);
            exit;
        }
    }

    // Handle link
    elseif (isset($_POST['link']) && !empty($_POST['link'])) {
        $media_type = 'link';
        $link_url = filter_var($_POST['link'], FILTER_VALIDATE_URL);

        if ($link_url === false) {
            echo json_encode(['success' => false, 'message' => 'Invalid URL']);
            exit;
        }
    }

    // Handle code snippet
    elseif (isset($_POST['codeSnippet']) && !empty($_POST['codeSnippet'])) {
        $media_type = 'code';
        $code_snippet = $_POST['codeSnippet'];
        $code_language = isset($_POST['codeLanguage']) ? $_POST['codeLanguage'] : 'text';
    }

    // Validate that there's content or media
    if (empty($content) && $media_type == 'none') {
        echo json_encode(['success' => false, 'message' => 'Post cannot be empty']);
        exit;
    }

    // Insert post into database
    $sql = "INSERT INTO posts (user_id, username, content, media_type, media_path, link_url, code_snippet, code_language) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";

    if ($stmt = mysqli_prepare($conn, $sql)) {
        mysqli_stmt_bind_param($stmt, "isssssss", $user_id, $username, $content, $media_type, $media_path, $link_url, $code_snippet, $code_language);

        if (mysqli_stmt_execute($stmt)) {
            $post_id = mysqli_insert_id($conn);
            echo json_encode(['success' => true, 'message' => 'Post created successfully', 'post_id' => $post_id]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Error creating post']);
        }

        mysqli_stmt_close($stmt);
    }

    mysqli_close($conn);
}
?>
