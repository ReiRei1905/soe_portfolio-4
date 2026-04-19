<?php

declare(strict_types=1);

require_once __DIR__ . '/../faculty_access_common.php';

$sessionUser = faculty_require_verified_faculty($conn);
$classId = isset($_GET['class_id']) ? (int) $_GET['class_id'] : 0;
$studentId = isset($_GET['student_id']) ? (int) $_GET['student_id'] : 0;
$outputId = isset($_GET['output_id']) ? (int) $_GET['output_id'] : 0;

if ($classId <= 0 || $studentId <= 0 || $outputId <= 0) {
    http_response_code(400);
    echo 'Invalid file reference.';
    exit;
}

if (!faculty_can_handle_class($conn, $sessionUser, $classId)) {
    http_response_code(403);
    echo 'Not allowed.';
    exit;
}

$sql = 'SELECT
            os.submitted_file_name,
            os.submitted_file_path,
            os.submitted_file_mime
        FROM output_submissions os
        INNER JOIN class_outputs o ON o.output_id = os.output_id
        WHERE os.output_id = ?
          AND os.student_id = ?
          AND o.class_id = ?
          AND os.status = "submitted"
        LIMIT 1';

$stmt = $conn->prepare($sql);
if (!$stmt) {
    http_response_code(500);
    echo 'Unable to load file.';
    exit;
}

$stmt->bind_param('iii', $outputId, $studentId, $classId);
$stmt->execute();
$row = $stmt->get_result()->fetch_assoc();
$stmt->close();

$filePath = trim((string) ($row['submitted_file_path'] ?? ''));
$fileName = trim((string) ($row['submitted_file_name'] ?? 'output-file'));
$fileMime = trim((string) ($row['submitted_file_mime'] ?? 'application/octet-stream'));

if ($filePath === '' || !is_file($filePath)) {
    http_response_code(404);
    echo 'File not found.';
    exit;
}

header('Content-Type: ' . $fileMime);
header('Content-Length: ' . (string) filesize($filePath));
header('Content-Disposition: inline; filename="' . basename($fileName) . '"');
readfile($filePath);
exit;
