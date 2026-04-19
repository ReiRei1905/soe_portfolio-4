<?php

declare(strict_types=1);

require_once __DIR__ . '/../faculty_access_common.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    faculty_send_json(['success' => false, 'error' => 'Method not allowed.'], 405);
}

$sessionUser = faculty_require_verified_faculty($conn);
$classId = isset($_POST['class_id']) ? (int) $_POST['class_id'] : 0;
$deadlineAt = trim((string) ($_POST['deadline_at'] ?? ''));

if ($classId <= 0 || $deadlineAt === '') {
    faculty_send_json(['success' => false, 'error' => 'Missing data'], 400);
}

if (!faculty_can_handle_class($conn, $sessionUser, $classId)) {
    faculty_send_json(['success' => false, 'error' => 'You are not allowed to set deadlines for this class.'], 403);
}

$stmt = $conn->prepare('UPDATE classes SET deadline_at = ? WHERE class_id = ?');
if (!$stmt) {
    faculty_send_json(['success' => false, 'error' => 'Failed to prepare update statement.'], 500);
}

$stmt->bind_param('si', $deadlineAt, $classId);
$ok = $stmt->execute();
$stmt->close();

if (!$ok) {
    faculty_send_json(['success' => false, 'error' => 'Update failed'], 500);
}

faculty_send_json(['success' => true]);
