<?php

declare(strict_types=1);

require_once __DIR__ . '/list_access_common.php';

$sessionUser = list_require_access($conn);
$listId = isset($_GET['listId']) ? (int) $_GET['listId'] : 0;
$studentId = isset($_GET['studentId']) ? (int) $_GET['studentId'] : 0;

if ($listId <= 0 || $studentId <= 0) {
    faculty_send_json(['success' => false, 'message' => 'Invalid list or student reference.'], 400);
}

if (!faculty_table_exists($conn, 'extracurricular_portfolios') || !faculty_table_exists($conn, 'extracurricular_portfolio_files')) {
    faculty_send_json(['success' => false, 'message' => 'Extracurricular portfolio tables are missing.'], 500);
}

if (!faculty_table_exists($conn, 'files') || !faculty_table_exists($conn, 'students')) {
    faculty_send_json(['success' => false, 'message' => 'Required student file tables are missing.'], 500);
}

$schema = list_require_student_lists_schema($conn);
$yearColumn = list_resolve_student_lists_year_column($conn, $schema);

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
$listRow = $listStmt->get_result()->fetch_assoc();
$listStmt->close();

if (!$listRow) {
    faculty_send_json(['success' => false, 'message' => 'List not found.'], 404);
}

$programId = (int) ($listRow['program_id'] ?? 0);
$yearOfEnrollment = (int) ($listRow['year_of_enrollment'] ?? 0);

$role = list_normalize_role((string) ($sessionUser['faculty_role'] ?? ''));
$isExd = str_contains($role, 'executive director') || faculty_is_executive_director($sessionUser);

if (!$isExd && !list_can_manage_program($conn, $sessionUser, $programId)) {
    faculty_send_json(['success' => false, 'message' => 'You are not allowed to access this list.'], 403);
}

$studentStmt = $conn->prepare(
    'SELECT s.student_id,
            s.id_number,
            s.program_id,
            s.year_of_enrollment,
            COALESCE(s.first_name, u.first_name) AS first_name,
            COALESCE(s.last_name, u.last_name) AS last_name,
            COALESCE(s.email, u.email) AS email,
            u.created_at AS joined_at
     FROM students s
     LEFT JOIN users u ON u.user_id = s.user_id
     WHERE s.student_id = ?
     LIMIT 1'
);
if (!$studentStmt) {
    faculty_send_json(['success' => false, 'message' => 'Failed to prepare student query.'], 500);
}

$studentStmt->bind_param('i', $studentId);
$studentStmt->execute();
$studentRow = $studentStmt->get_result()->fetch_assoc();
$studentStmt->close();

if (!$studentRow) {
    faculty_send_json(['success' => false, 'message' => 'Student not found.'], 404);
}

if ((int) ($studentRow['program_id'] ?? 0) !== $programId || (int) ($studentRow['year_of_enrollment'] ?? 0) !== $yearOfEnrollment) {
    faculty_send_json(['success' => false, 'message' => 'This student is not part of the selected list scope.'], 403);
}

$portfolioStmt = $conn->prepare(
    'SELECT ep.portfolio_id,
            ep.portfolio_key,
            ep.title,
            ep.sort_order,
            ep.created_at,
            ep.updated_at
     FROM extracurricular_portfolios ep
     WHERE ep.student_id = ?
     ORDER BY ep.sort_order ASC, ep.portfolio_id ASC'
);
if (!$portfolioStmt) {
    faculty_send_json(['success' => false, 'message' => 'Failed to prepare extracurricular portfolios query.'], 500);
}

$portfolioStmt->bind_param('i', $studentId);
$portfolioStmt->execute();
$portfolioResult = $portfolioStmt->get_result();

$fileStmt = $conn->prepare(
    'SELECT
        f.file_id,
        f.original_file_name,
        f.mime_type,
        f.created_at,
        f.updated_at,
        epf.created_at AS attached_at
     FROM extracurricular_portfolio_files epf
     INNER JOIN files f ON f.file_id = epf.file_id
     WHERE epf.portfolio_id = ?
       AND f.student_id = ?
     ORDER BY epf.created_at ASC, f.file_id ASC'
);

if (!$fileStmt) {
    $portfolioStmt->close();
    faculty_send_json(['success' => false, 'message' => 'Failed to prepare extracurricular file query.'], 500);
}

$portfolios = [];
while ($portfolioResult && ($portfolioRow = $portfolioResult->fetch_assoc())) {
    $portfolioId = (int) ($portfolioRow['portfolio_id'] ?? 0);

    $fileStmt->bind_param('ii', $portfolioId, $studentId);
    $fileStmt->execute();
    $fileResult = $fileStmt->get_result();

    $files = [];
    while ($fileResult && ($fileRow = $fileResult->fetch_assoc())) {
        $fileId = (int) ($fileRow['file_id'] ?? 0);
        $fileName = trim((string) ($fileRow['original_file_name'] ?? ''));

        $files[] = [
            'fileId' => $fileId,
            'fileName' => $fileName,
            'mimeType' => trim((string) ($fileRow['mime_type'] ?? 'application/octet-stream')),
            'createdAt' => $fileRow['created_at'] ?? null,
            'updatedAt' => $fileRow['updated_at'] ?? null,
            'attachedAt' => $fileRow['attached_at'] ?? null,
            'viewUrl' => sprintf('view_extracurricular_file.php?listId=%d&studentId=%d&fileId=%d', $listId, $studentId, $fileId)
        ];
    }

    $portfolios[] = [
        'portfolioId' => $portfolioId,
        'portfolioKey' => trim((string) ($portfolioRow['portfolio_key'] ?? '')),
        'title' => trim((string) ($portfolioRow['title'] ?? 'Untitled portfolio')),
        'sortOrder' => (int) ($portfolioRow['sort_order'] ?? 0),
        'createdAt' => $portfolioRow['created_at'] ?? null,
        'updatedAt' => $portfolioRow['updated_at'] ?? null,
        'files' => $files
    ];
}

$fileStmt->close();
$portfolioStmt->close();

faculty_send_json([
    'success' => true,
    'student' => [
        'studentId' => (int) ($studentRow['student_id'] ?? 0),
        'firstName' => trim((string) ($studentRow['first_name'] ?? '')),
        'lastName' => trim((string) ($studentRow['last_name'] ?? '')),
        'idNumber' => trim((string) ($studentRow['id_number'] ?? '')),
        'email' => trim((string) ($studentRow['email'] ?? '')),
        'joinedAt' => $studentRow['joined_at'] ?? null,
        'programName' => trim((string) ($listRow['program_name'] ?? '')),
        'yearOfEnrollment' => $yearOfEnrollment
    ],
    'list' => [
        'listId' => (int) ($listRow['list_id'] ?? 0),
        'batchName' => trim((string) ($listRow['batch_name'] ?? '')),
        'programName' => trim((string) ($listRow['program_name'] ?? '')),
        'yearOfEnrollment' => $yearOfEnrollment
    ],
    'extracurricularPortfolios' => $portfolios
]);
