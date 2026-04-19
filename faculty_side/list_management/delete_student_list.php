<?php

declare(strict_types=1);

require_once __DIR__ . '/list_access_common.php';

$sessionUser = list_require_access($conn);
$schema = list_require_student_lists_schema($conn);

$listId = isset($_POST['listId']) ? (int) $_POST['listId'] : 0;
if ($listId <= 0) {
    faculty_send_json(['success' => false, 'message' => 'Invalid list ID.'], 400);
}

$metaStmt = $conn->prepare('SELECT ' . $schema['programId'] . ' AS program_id FROM student_lists WHERE ' . $schema['listId'] . ' = ? LIMIT 1');
if (!$metaStmt) {
    faculty_send_json(['success' => false, 'message' => 'Failed to load list details.'], 500);
}

$metaStmt->bind_param('i', $listId);
$metaStmt->execute();
$metaResult = $metaStmt->get_result();
$meta = $metaResult ? $metaResult->fetch_assoc() : null;
$metaStmt->close();

if (!$meta) {
    faculty_send_json(['success' => false, 'message' => 'List not found.'], 404);
}

$programId = (int) ($meta['program_id'] ?? 0);
if (!list_can_manage_program($conn, $sessionUser, $programId)) {
    faculty_send_json(['success' => false, 'message' => 'You are not allowed to delete this list.'], 403);
}

$deleteStmt = $conn->prepare('DELETE FROM student_lists WHERE ' . $schema['listId'] . ' = ?');
if (!$deleteStmt) {
    faculty_send_json(['success' => false, 'message' => 'Failed to prepare list deletion.'], 500);
}

$deleteStmt->bind_param('i', $listId);
$ok = $deleteStmt->execute();
$deleteStmt->close();

if (!$ok) {
    faculty_send_json(['success' => false, 'message' => 'Failed to delete list.'], 500);
}

faculty_send_json([
    'success' => true,
    'message' => 'List deleted successfully.'
]);
