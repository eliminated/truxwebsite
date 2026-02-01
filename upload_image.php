<?php
ob_start();
require_once "config.php";
ob_end_clean();

header('Content-Type: application/json');


if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$user_id = $_SESSION["user_id"];
$response = ['success' => false, 'message' => ''];

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_FILES["image"])) {

    $upload_type = $_POST['upload_type']; // 'profile' or 'cover'
    $file = $_FILES["image"];

    // Validate file
    $allowed_types = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
    $max_size = 5 * 1024 * 1024; // 5MB

    if (!in_array($file['type'], $allowed_types)) {
        $response['message'] = 'Invalid file type. Only JPG, PNG, and GIF allowed.';
        echo json_encode($response);
        exit;
    }

    if ($file['size'] > $max_size) {
        $response['message'] = 'File too large. Maximum size is 5MB.';
        echo json_encode($response);
        exit;
    }

    // Create uploads directory if it doesn't exist
    $upload_dir = 'uploads/';
    if ($upload_type == 'profile') {
        $upload_dir .= 'profiles/';
    } else {
        $upload_dir .= 'covers/';
    }

    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }

    // Generate unique filename
    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = $user_id . '_' . time() . '.' . $extension;
    $filepath = $upload_dir . $filename;

    // Move uploaded file
    if (move_uploaded_file($file['tmp_name'], $filepath)) {

        // Update database
        if ($upload_type == 'profile') {
            $sql = "UPDATE users SET profile_picture = ? WHERE id = ?";
        } else {
            $sql = "UPDATE users SET cover_photo = ? WHERE id = ?";
        }

        if ($stmt = mysqli_prepare($conn, $sql)) {
            mysqli_stmt_bind_param($stmt, "si", $filepath, $user_id);

            if (mysqli_stmt_execute($stmt)) {
                $response['success'] = true;
                $response['message'] = 'Image uploaded successfully!';
                $response['image_url'] = $filepath;
            } else {
                $response['message'] = 'Database error.';
            }
            mysqli_stmt_close($stmt);
        }
    } else {
        $response['message'] = 'Failed to upload file.';
    }
} else {
    $response['message'] = 'No file uploaded.';
}

echo json_encode($response);
?>
