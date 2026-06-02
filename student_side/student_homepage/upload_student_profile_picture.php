<?php
session_start();
header('Content-Type: application/json; charset=utf-8');

require_once '../../user_info_V3/connect.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'User not logged in.']);
    exit;
}

$userId = (int)$_SESSION['user_id'];

if (!isset($_FILES['profile_picture']) || $_FILES['profile_picture']['error'] !== UPLOAD_ERR_OK) {
    echo json_encode(['success' => false, 'message' => 'No file uploaded or upload error.']);
    exit;
}

$file = $_FILES['profile_picture'];
$fileExt = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

// Point directly to the root images directory
$uploadDir = '../../images/user_images/'; 

// Create the directory if it does not exist
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0777, true);
}

$newFileName = 'student_' . $userId . '_' . time() . '.' . $fileExt;
$targetPath = $uploadDir . $newFileName;

// This is the correct relative path for the database so JS can render it correctly
$databasePath = 'images/user_images/' . $newFileName;

if (move_uploaded_file($file['tmp_name'], $targetPath)) {
    
    // Step 1: Get student_id AND their actual names to satisfy the NOT NULL database constraint
    $studentStmt = $conn->prepare("SELECT student_id, first_name, last_name FROM students WHERE user_id = ?");
    $studentStmt->bind_param("i", $userId);
    $studentStmt->execute();
    $studentResult = $studentStmt->get_result();
    
    if ($studentResult->num_rows > 0) {
        $studentRow = $studentResult->fetch_assoc();
        $studentId = $studentRow['student_id'];
        
        // Generate a fallback display name in case the row doesn't exist yet
        $firstName = $studentRow['first_name'] ?? '';
        $lastName = $studentRow['last_name'] ?? '';
        $displayName = trim($firstName . ' ' . $lastName);
        if (empty($displayName)) {
            $displayName = 'Student';
        }
        
        // Step 2: Insert or Update. We pass the display name to prevent SQL crashes on new profiles
        $stmt = $conn->prepare("INSERT INTO student_homepage_profiles (student_id, display_name, profile_picture_path) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE profile_picture_path = ?");
        
        if ($stmt) {
            $stmt->bind_param("isss", $studentId, $displayName, $databasePath, $databasePath);
            if ($stmt->execute()) {
                // UPDATE the 'users' table so global session context doesn't serve the old picture
                $userUpdateStmt = $conn->prepare("UPDATE users SET profile_picture = ? WHERE user_id = ?");
                if ($userUpdateStmt) {
                    $userUpdateStmt->bind_param("si", $databasePath, $userId);
                    $userUpdateStmt->execute();
                    $userUpdateStmt->close();
                }
                
                echo json_encode(['success' => true, 'message' => 'Profile picture saved successfully!', 'path' => $databasePath]);
            } else {
                echo json_encode(['success' => false, 'message' => 'File uploaded, but database update failed! Error: ' . $stmt->error]);
            }
            $stmt->close();
        } else {
             echo json_encode(['success' => false, 'message' => 'Database error preparing statement.']);
        }
    } else {
         echo json_encode(['success' => false, 'message' => 'Student record not found for this user.']);
    }
    $studentStmt->close();
    
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to move file. Check folder permissions.']);
}
?>