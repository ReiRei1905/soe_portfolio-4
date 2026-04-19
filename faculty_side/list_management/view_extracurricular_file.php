<?php

declare(strict_types=1);

require_once __DIR__ . '/list_access_common.php';

$sessionUser = list_require_access($conn);
$listId = isset($_GET['listId']) ? (int) $_GET['listId'] : 0;
$studentId = isset($_GET['studentId']) ? (int) $_GET['studentId'] : 0;
$fileId = isset($_GET['fileId']) ? (int) $_GET['fileId'] : 0;
$download = isset($_GET['download']) && (string) $_GET['download'] === '1';

if ($listId <= 0 || $studentId <= 0 || $fileId <= 0) {
    http_response_code(422);
    header('Content-Type: text/plain; charset=utf-8');
    echo 'Invalid file access reference.';
    exit;
}

if (!faculty_table_exists($conn, 'extracurricular_portfolio_files') || !faculty_table_exists($conn, 'files')) {
    http_response_code(500);
    header('Content-Type: text/plain; charset=utf-8');
    echo 'Required extracurricular file tables are missing.';
    exit;
}

$schema = list_require_student_lists_schema($conn);
$yearColumn = list_resolve_student_lists_year_column($conn, $schema);

$listStmt = $conn->prepare(
    'SELECT sl.' . $schema['programId'] . ' AS program_id, sl.' . $yearColumn . ' AS year_of_enrollment
     FROM student_lists sl
     WHERE sl.' . $schema['listId'] . ' = ?
     LIMIT 1'
);
if (!$listStmt) {
    http_response_code(500);
    header('Content-Type: text/plain; charset=utf-8');
    echo 'Unable to prepare list query.';
    exit;
}

$listStmt->bind_param('i', $listId);
$listStmt->execute();
$listRow = $listStmt->get_result()->fetch_assoc();
$listStmt->close();

if (!$listRow) {
    http_response_code(404);
    header('Content-Type: text/plain; charset=utf-8');
    echo 'List not found.';
    exit;
}

$programId = (int) ($listRow['program_id'] ?? 0);
$yearOfEnrollment = (int) ($listRow['year_of_enrollment'] ?? 0);

if (!list_can_manage_program($conn, $sessionUser, $programId)) {
    http_response_code(403);
    header('Content-Type: text/plain; charset=utf-8');
    echo 'Not allowed to access this list.';
    exit;
}

$studentStmt = $conn->prepare('SELECT student_id FROM students WHERE student_id = ? AND program_id = ? AND year_of_enrollment = ? LIMIT 1');
if (!$studentStmt) {
    http_response_code(500);
    header('Content-Type: text/plain; charset=utf-8');
    echo 'Unable to validate student.';
    exit;
}

$studentStmt->bind_param('iii', $studentId, $programId, $yearOfEnrollment);
$studentStmt->execute();
$studentRow = $studentStmt->get_result()->fetch_assoc();
$studentStmt->close();

if (!$studentRow) {
    http_response_code(403);
    header('Content-Type: text/plain; charset=utf-8');
    echo 'Student is outside list scope.';
    exit;
}

$fileStmt = $conn->prepare(
    'SELECT f.original_file_name, f.mime_type, f.file_path
     FROM extracurricular_portfolio_files epf
     INNER JOIN files f ON f.file_id = epf.file_id
     WHERE f.file_id = ?
       AND f.student_id = ?
     LIMIT 1'
);
if (!$fileStmt) {
    http_response_code(500);
    header('Content-Type: text/plain; charset=utf-8');
    echo 'Unable to prepare file query.';
    exit;
}

$fileStmt->bind_param('ii', $fileId, $studentId);
$fileStmt->execute();
$file = $fileStmt->get_result()->fetch_assoc();
$fileStmt->close();

if (!$file) {
    http_response_code(404);
    header('Content-Type: text/plain; charset=utf-8');
    echo 'File not found.';
    exit;
}

$filePath = (string) ($file['file_path'] ?? '');
$absolutePath = '';
if ($filePath !== '') {
    if (preg_match('/^[A-Za-z]:\\\\|^\//', $filePath) === 1) {
        $absolutePath = $filePath;
    } else {
        $absolutePath = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $filePath);
    }
}

if ($absolutePath === '' || !is_file($absolutePath)) {
    http_response_code(404);
    header('Content-Type: text/plain; charset=utf-8');
    echo 'File content is unavailable.';
    exit;
}

$safeName = basename((string) ($file['original_file_name'] ?? 'downloaded_file'));
$mimeType = (string) ($file['mime_type'] ?? 'application/octet-stream');

header('Content-Type: ' . $mimeType);
header('Content-Length: ' . filesize($absolutePath));

$disposition = $download ? 'attachment' : 'inline';
header("Content-Disposition: {$disposition}; filename=\"" . addslashes($safeName) . "\"");
header('X-Content-Type-Options: nosniff');

readfile($absolutePath);
exit;
