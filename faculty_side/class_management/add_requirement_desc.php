<?php

declare(strict_types=1);

require_once __DIR__ . '/../faculty_access_common.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    faculty_send_json(['success' => false, 'error' => 'Method not allowed.'], 405);
}

$sessionUser = faculty_require_verified_faculty($conn);
$classId = isset($_POST['class_id']) ? (int) $_POST['class_id'] : 0;
$desc = trim((string) ($_POST['requirement_desc'] ?? ''));

if ($classId <= 0 || $desc === '') {
    faculty_send_json(['success' => false, 'error' => 'Missing data'], 400);
}

if (!faculty_can_handle_class($conn, $sessionUser, $classId)) {
    faculty_send_json(['success' => false, 'error' => 'You are not allowed to add requirements for this class.'], 403);
}

$stmt = $conn->prepare('INSERT INTO requirements (class_id, requirement_desc, created_at) VALUES (?, ?, NOW())');
if (!$stmt) {
    faculty_send_json(['success' => false, 'error' => 'Failed to prepare requirement insert.'], 500);
}

$stmt->bind_param('is', $classId, $desc);
$ok = $stmt->execute();
$requirementId = (int) $stmt->insert_id;
$stmt->close();

if (!$ok) {
    faculty_send_json(['success' => false, 'error' => 'Insert failed'], 500);
}

faculty_send_json(['success' => true, 'requirement_id' => $requirementId]);
