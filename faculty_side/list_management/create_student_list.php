<?php

declare(strict_types=1);

require_once __DIR__ . '/list_access_common.php';

$sessionUser = list_require_access($conn);
$schema = list_require_student_lists_schema($conn);
$yearColumn = list_resolve_student_lists_year_column($conn, $schema);

$programId = isset($_POST['programId']) ? (int) $_POST['programId'] : 0;
$batchName = trim((string) ($_POST['batchName'] ?? ''));
$yearOfEnrollment = isset($_POST['yearOfEnrollment']) ? (int) $_POST['yearOfEnrollment'] : 0;

if ($programId <= 0 || $batchName === '' || $yearOfEnrollment <= 0) {
    faculty_send_json(['success' => false, 'message' => 'Program, batch name, and year of enrollment are required.'], 400);
}

if ($yearOfEnrollment < 1900 || $yearOfEnrollment > 2100) {
    faculty_send_json(['success' => false, 'message' => 'Year of enrollment is out of range.'], 400);
}

if (!list_can_manage_program($conn, $sessionUser, $programId)) {
    faculty_send_json(['success' => false, 'message' => 'You are not allowed to create lists for this program.'], 403);
}

$duplicateStmt = $conn->prepare(
    'SELECT ' . $schema['listId'] . ' AS list_id FROM student_lists WHERE ' . $schema['programId'] . ' = ? AND ' . $schema['batchName'] . ' = ? AND ' . $yearColumn . ' = ? LIMIT 1'
);
if (!$duplicateStmt) {
    faculty_send_json(['success' => false, 'message' => 'Failed to check duplicate list.'], 500);
}

$duplicateStmt->bind_param('isi', $programId, $batchName, $yearOfEnrollment);
$duplicateStmt->execute();
$duplicateResult = $duplicateStmt->get_result();
$duplicateFound = (bool) ($duplicateResult && $duplicateResult->fetch_assoc());
$duplicateStmt->close();

if ($duplicateFound) {
    faculty_send_json(['success' => false, 'message' => 'This list already exists for the selected program and year.'], 409);
}

$createdByUserId = (int) ($sessionUser['user_id'] ?? 0);
if ($schema['createdBy']) {
    $insertSql = 'INSERT INTO student_lists (' . $schema['programId'] . ', ' . $schema['batchName'] . ', ' . $yearColumn . ', ' . $schema['createdBy'] . ') VALUES (?, ?, ?, ?)';
} else {
    $insertSql = 'INSERT INTO student_lists (' . $schema['programId'] . ', ' . $schema['batchName'] . ', ' . $yearColumn . ') VALUES (?, ?, ?)';
}

$insertStmt = $conn->prepare($insertSql);
if (!$insertStmt) {
    faculty_send_json(['success' => false, 'message' => 'Failed to prepare list creation.'], 500);
}

if ($schema['createdBy']) {
    $insertStmt->bind_param('isii', $programId, $batchName, $yearOfEnrollment, $createdByUserId);
} else {
    $insertStmt->bind_param('isi', $programId, $batchName, $yearOfEnrollment);
}
$ok = $insertStmt->execute();
$newListId = $ok ? (int) $insertStmt->insert_id : 0;
$insertStmt->close();

if (!$ok || $newListId <= 0) {
    faculty_send_json(['success' => false, 'message' => 'Failed to create list of students.'], 500);
}

faculty_send_json([
    'success' => true,
    'message' => 'List of students created successfully.',
    'listId' => $newListId
]);
