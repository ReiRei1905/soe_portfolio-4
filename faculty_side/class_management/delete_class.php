<?php

declare(strict_types=1);

require_once __DIR__ . '/../faculty_access_common.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    faculty_send_json(['success' => false, 'message' => 'Method not allowed.'], 405);
}

$sessionUser = faculty_require_verified_faculty($conn);
$classId = isset($_POST['classId']) ? (int) $_POST['classId'] : 0;

if ($classId <= 0) {
    faculty_send_json(['success' => false, 'message' => 'Invalid class ID.'], 400);
}

if (!faculty_can_manage_class($conn, $sessionUser, $classId)) {
    faculty_send_json(['success' => false, 'message' => 'You are not allowed to delete this class.'], 403);
}

$stmt = $conn->prepare('DELETE FROM classes WHERE class_id = ?');
if (!$stmt) {
    faculty_send_json(['success' => false, 'message' => 'Failed to prepare delete statement.'], 500);
}

$stmt->bind_param('i', $classId);
$ok = $stmt->execute();
$stmt->close();

if (!$ok) {
    faculty_send_json(['success' => false, 'message' => 'Failed to delete class.'], 500);
}

faculty_send_json(['success' => true, 'message' => 'Class deleted successfully.']);
