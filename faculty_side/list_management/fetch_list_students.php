<?php

declare(strict_types=1);

require_once __DIR__ . '/list_access_common.php';

$sessionUser = list_require_access($conn);
$schema = list_require_student_lists_schema($conn);
$yearColumn = list_resolve_student_lists_year_column($conn, $schema);

$listId = isset($_GET['listId']) ? (int) $_GET['listId'] : 0;
if ($listId <= 0) {
    faculty_send_json(['success' => false, 'message' => 'Invalid list ID.'], 400);
}

$listStmt = $conn->prepare(
    'SELECT sl.' . $schema['listId'] . ' AS list_id, sl.' . $schema['programId'] . ' AS program_id, sl.' . $schema['batchName'] . ' AS batch_name, sl.' . $yearColumn . ' AS year_of_enrollment, p.program_name
     FROM student_lists sl
     INNER JOIN programs p ON p.program_id = sl.' . $schema['programId'] . '
     WHERE sl.' . $schema['listId'] . ' = ?
     LIMIT 1'
);
if (!$listStmt) {
    faculty_send_json(['success' => false, 'message' => 'Failed to prepare list query.'], 500);
}

$listStmt->bind_param('i', $listId);
$listStmt->execute();
$listResult = $listStmt->get_result();
$listRow = $listResult ? $listResult->fetch_assoc() : null;
$listStmt->close();

if (!$listRow) {
    faculty_send_json(['success' => false, 'message' => 'List not found.'], 404);
}

$programId = (int) ($listRow['program_id'] ?? 0);
$role = list_normalize_role((string) ($sessionUser['faculty_role'] ?? ''));
$isExd = str_contains($role, 'executive director') || faculty_is_executive_director($sessionUser);

// EXD can view all. Standard Program Directors can only view what they manage.
if (!$isExd && !list_can_manage_program($conn, $sessionUser, $programId)) {
    faculty_send_json(['success' => false, 'message' => 'You are not allowed to access this list.'], 403);
}

$yearOfEnrollment = (int) ($listRow['year_of_enrollment'] ?? 0);

$studentStmt = $conn->prepare(
    'SELECT s.student_id,
            s.id_number,
            COALESCE(s.first_name, u.first_name) AS first_name,
            COALESCE(s.last_name, u.last_name) AS last_name,
                        COALESCE(s.email, u.email) AS email,
                        u.created_at AS joined_at
     FROM students s
     LEFT JOIN users u ON u.user_id = s.user_id
     WHERE s.program_id = ?
       AND s.year_of_enrollment = ?
     ORDER BY COALESCE(s.last_name, u.last_name) ASC, COALESCE(s.first_name, u.first_name) ASC'
);

if (!$studentStmt) {
    faculty_send_json(['success' => false, 'message' => 'Failed to prepare students query.'], 500);
}

$studentStmt->bind_param('ii', $programId, $yearOfEnrollment);
$studentStmt->execute();
$studentResult = $studentStmt->get_result();

$students = [];
while ($studentResult && ($row = $studentResult->fetch_assoc())) {
    $students[] = [
        'studentId' => (int) ($row['student_id'] ?? 0),
        'idNumber' => trim((string) ($row['id_number'] ?? '')),
        'firstName' => trim((string) ($row['first_name'] ?? '')),
        'lastName' => trim((string) ($row['last_name'] ?? '')),
        'email' => trim((string) ($row['email'] ?? '')),
        'joinedAt' => trim((string) ($row['joined_at'] ?? ''))
    ];
}
$studentStmt->close();

faculty_send_json([
    'success' => true,
    'list' => [
        'listId' => (int) ($listRow['list_id'] ?? 0),
        'programId' => $programId,
        'programName' => trim((string) ($listRow['program_name'] ?? '')),
        'batchName' => trim((string) ($listRow['batch_name'] ?? '')),
        'yearOfEnrollment' => $yearOfEnrollment
    ],
    'students' => $students
]);
