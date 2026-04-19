<?php

declare(strict_types=1);

require_once __DIR__ . '/../faculty_access_common.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    faculty_send_json(['success' => false, 'error' => 'Method not allowed.'], 405);
}

$sessionUser = faculty_require_verified_faculty($conn);
$requirementId = isset($_POST['requirement_id']) ? (int) $_POST['requirement_id'] : 0;

if ($requirementId <= 0) {
    faculty_send_json(['success' => false, 'error' => 'Missing requirement_id'], 400);
}

$classStmt = $conn->prepare('SELECT class_id FROM requirements WHERE requirement_id = ? LIMIT 1');
if (!$classStmt) {
    faculty_send_json(['success' => false, 'error' => 'Failed to validate requirement.'], 500);
}
$classStmt->bind_param('i', $requirementId);
$classStmt->execute();
$classResult = $classStmt->get_result();
$classRow = $classResult ? $classResult->fetch_assoc() : null;
$classStmt->close();

if (!$classRow) {
    faculty_send_json(['success' => false, 'error' => 'Requirement not found.'], 404);
}

$classId = (int) ($classRow['class_id'] ?? 0);
if (!faculty_can_handle_class($conn, $sessionUser, $classId)) {
    faculty_send_json(['success' => false, 'error' => 'You are not allowed to delete requirements for this class.'], 403);
}

$stmt = $conn->prepare('DELETE FROM requirements WHERE requirement_id = ?');
if (!$stmt) {
    faculty_send_json(['success' => false, 'error' => 'Failed to prepare delete statement.'], 500);
}

$stmt->bind_param('i', $requirementId);
$ok = $stmt->execute();
$stmt->close();

if (!$ok) {
    faculty_send_json(['success' => false, 'error' => 'Delete failed'], 500);
}

faculty_send_json(['success' => true]);
