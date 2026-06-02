<?php
session_start();
header('Content-Type: application/json');

include '../../user_info_V3/connect.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'User not logged in']);
    exit();
}

$user_id = $_SESSION['user_id'];

// Get student data
$query = "SELECT s.email, s.password, s.id_number, p.program_name, sp.profile_picture_path
          FROM students s
          LEFT JOIN programs p ON s.program_id = p.program_id
          LEFT JOIN student_homepage_profiles sp ON s.student_id = sp.student_id
          WHERE s.user_id = ?";

$stmt = $conn->prepare($query);
if (!$stmt) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $conn->error]);
    exit();
}

$stmt->bind_param('i', $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();
    
    $response = [
        'success' => true,
        'email' => $row['email'],
        'password' => $row['password'],
        'id_number' => $row['id_number'],
        'program' => $row['program_name'] ?: 'Not assigned',
        'profile_picture_path' => $row['profile_picture_path'] ?: null
    ];
    
    echo json_encode($response);
} else {
    echo json_encode(['success' => false, 'message' => 'Student record not found']);
}

$stmt->close();
$conn->close();
?>
