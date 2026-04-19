<?php

declare(strict_types=1);

require_once __DIR__ . '/../faculty_access_common.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    faculty_send_json(['success' => false, 'message' => 'Method not allowed.'], 405);
}

$sessionUser = faculty_require_verified_faculty($conn);
$classId = isset($_POST['classId']) ? (int) $_POST['classId'] : 0;
$newClassName = trim((string) ($_POST['newClassName'] ?? ''));

if ($classId <= 0 || $newClassName === '') {
    faculty_send_json(['success' => false, 'message' => 'Invalid input.'], 400);
}

if (!faculty_can_manage_class($conn, $sessionUser, $classId)) {
    faculty_send_json(['success' => false, 'message' => 'You are not allowed to edit this class.'], 403);
}

$stmt = $conn->prepare('UPDATE classes SET class_name = ?, updated_at = NOW() WHERE class_id = ?');
if (!$stmt) {
    faculty_send_json(['success' => false, 'message' => 'Failed to prepare statement.'], 500);
}

$stmt->bind_param('si', $newClassName, $classId);
$ok = $stmt->execute();
$stmt->close();

if (!$ok) {
    faculty_send_json(['success' => false, 'message' => 'Failed to update class name.'], 500);
}

faculty_send_json(['success' => true, 'message' => 'Class name updated successfully.']);
