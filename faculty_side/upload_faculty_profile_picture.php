<?php
session_start();
header('Content-Type: application/json');
include '../user_info_V3/connect.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'User not logged in']);
    exit();
}

$user_id = $_SESSION['user_id'];

if (!isset($_FILES['profile_picture']) || $_FILES['profile_picture']['error'] !== UPLOAD_ERR_OK) {
    echo json_encode(['success' => false, 'message' => 'No file uploaded or upload error']);
    exit();
}

$file = $_FILES['profile_picture'];
$allowed_types = ['image/png', 'image/jpeg'];

if (!in_array($file['type'], $allowed_types)) {
    echo json_encode(['success' => false, 'message' => 'Only PNG and JPG allowed']);
    exit();
}

// Generate unique filename
$ext = pathinfo($file['name'], PATHINFO_EXTENSION);
$new_filename = 'faculty_' . $user_id . '_' . time() . '.' . $ext;
$upload_path = '../images/user_images/' . $new_filename;

if (move_uploaded_file($file['tmp_name'], $upload_path)) {
    // Get old picture to delete it
    $stmt = $conn->prepare("SELECT profile_picture FROM users WHERE user_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        $old_pic = $row['profile_picture'];
        if ($old_pic && file_exists('../images/user_images/' . $old_pic)) {
            unlink('../images/user_images/' . $old_pic);
        }
    }
    $stmt->close();

    // Update users table
    $update_stmt = $conn->prepare("UPDATE users SET profile_picture = ? WHERE user_id = ?");
    $update_stmt->bind_param("si", $new_filename, $user_id);
    if ($update_stmt->execute()) {
        echo json_encode([
            'success' => true,
            'message' => 'Uploaded successfully',
            'profile_picture' => $new_filename,
            'path' => 'images/user_images/' . $new_filename
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Database update failed']);
    }
    $update_stmt->close();
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to move file']);
}