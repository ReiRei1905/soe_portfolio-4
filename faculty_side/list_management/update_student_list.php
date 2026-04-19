<?php

declare(strict_types=1);

require_once __DIR__ . '/list_access_common.php';

$sessionUser = list_require_access($conn);
$schema = list_require_student_lists_schema($conn);
$yearColumn = list_resolve_student_lists_year_column($conn, $schema);

$listId = isset($_POST['listId']) ? (int) $_POST['listId'] : 0;
$batchName = trim((string) ($_POST['batchName'] ?? ''));

if ($listId <= 0 || $batchName === '') {
    faculty_send_json(['success' => false, 'message' => 'List ID and batch name are required.'], 400);
}

$metaStmt = $conn->prepare('SELECT ' . $schema['programId'] . ' AS program_id, ' . $yearColumn . ' AS year_of_enrollment FROM student_lists WHERE ' . $schema['listId'] . ' = ? LIMIT 1');
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
$yearOfEnrollment = (int) ($meta['year_of_enrollment'] ?? 0);

if (!list_can_manage_program($conn, $sessionUser, $programId)) {
    faculty_send_json(['success' => false, 'message' => 'You are not allowed to edit this list.'], 403);
}

$duplicateStmt = $conn->prepare(
    'SELECT ' . $schema['listId'] . ' AS list_id FROM student_lists WHERE ' . $schema['programId'] . ' = ? AND ' . $schema['batchName'] . ' = ? AND ' . $yearColumn . ' = ? AND ' . $schema['listId'] . ' <> ? LIMIT 1'
);
if (!$duplicateStmt) {
    faculty_send_json(['success' => false, 'message' => 'Failed to validate list name.'], 500);
}

$duplicateStmt->bind_param('isii', $programId, $batchName, $yearOfEnrollment, $listId);
$duplicateStmt->execute();
$duplicateResult = $duplicateStmt->get_result();
$duplicateFound = (bool) ($duplicateResult && $duplicateResult->fetch_assoc());
$duplicateStmt->close();

if ($duplicateFound) {
    faculty_send_json(['success' => false, 'message' => 'Another list already uses this batch name for the same year.'], 409);
}

$updateStmt = $conn->prepare('UPDATE student_lists SET ' . $schema['batchName'] . ' = ?' . (list_find_student_lists_column($conn, ['updated_at']) ? ', updated_at = NOW()' : '') . ' WHERE ' . $schema['listId'] . ' = ?');
if (!$updateStmt) {
    faculty_send_json(['success' => false, 'message' => 'Failed to prepare list update.'], 500);
}

$updateStmt->bind_param('si', $batchName, $listId);
$ok = $updateStmt->execute();
$updateStmt->close();

if (!$ok) {
    faculty_send_json(['success' => false, 'message' => 'Failed to update list.'], 500);
}

faculty_send_json([
    'success' => true,
    'message' => 'List renamed successfully.'
]);
