<?php

declare(strict_types=1);

require_once __DIR__ . '/../faculty_access_common.php';

$sessionUser = faculty_require_verified_faculty($conn);
$classId = isset($_GET['class_id']) ? (int) $_GET['class_id'] : 0;

if ($classId <= 0) {
    faculty_send_json(['success' => false, 'error' => 'Missing class_id'], 400);
}

if (!faculty_can_handle_class($conn, $sessionUser, $classId)) {
    faculty_send_json(['success' => false, 'error' => 'You are not allowed to access requirements for this class.'], 403);
}

$stmt = $conn->prepare('SELECT requirement_id, requirement_desc FROM requirements WHERE class_id = ?');
if (!$stmt) {
    faculty_send_json(['success' => false, 'error' => 'Failed to prepare requirement query.'], 500);
}

$stmt->bind_param('i', $classId);
$stmt->execute();
$result = $stmt->get_result();
$requirements = [];
while ($row = $result->fetch_assoc()) {
    $requirements[] = $row;
}
$stmt->close();

faculty_send_json(['success' => true, 'requirements' => $requirements]);
