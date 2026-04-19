<?php

declare(strict_types=1);

require_once __DIR__ . '/../api/common.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(405, ['success' => false, 'message' => 'Method not allowed.']);
}

$conn = db_connect();

if (!isset($_SESSION['email']) || trim((string) $_SESSION['email']) === '') {
    json_response(401, ['success' => false, 'message' => 'Unauthorized. Please log in.']);
}

$studentId = current_student_id($conn);
$outputId = isset($_POST['output_id']) ? (int) $_POST['output_id'] : 0;
$studentScore = isset($_POST['student_score']) ? (float) $_POST['student_score'] : null;

if ($outputId <= 0 || $studentScore === null) {
    json_response(400, ['success' => false, 'message' => 'Missing submission data.']);
}

if (!isset($_FILES['attached_output']) || !is_uploaded_file($_FILES['attached_output']['tmp_name'])) {
    json_response(400, ['success' => false, 'message' => 'Please attach the required output file.']);
}

$outputStmt = $conn->prepare('SELECT o.class_id, o.total_score, o.required_file_format, c.deadline_at
                              FROM class_outputs o
                              INNER JOIN classes c ON c.class_id = o.class_id
                              WHERE o.output_id = ?
                              LIMIT 1');
if (!$outputStmt) {
    json_response(500, ['success' => false, 'message' => 'Failed to validate output.']);
}
$outputStmt->bind_param('i', $outputId);
$outputStmt->execute();
$outputRow = $outputStmt->get_result()->fetch_assoc();
$outputStmt->close();

if (!$outputRow) {
    json_response(404, ['success' => false, 'message' => 'Output not found.']);
}

$classId = (int) ($outputRow['class_id'] ?? 0);
$totalScore = (float) ($outputRow['total_score'] ?? 0);
$requiredFormat = strtolower(trim((string) ($outputRow['required_file_format'] ?? '')));
$deadlineAt = trim((string) ($outputRow['deadline_at'] ?? ''));

$memberStmt = $conn->prepare('SELECT status FROM class_students WHERE class_id = ? AND student_id = ? LIMIT 1');
if (!$memberStmt) {
    json_response(500, ['success' => false, 'message' => 'Failed to validate class membership.']);
}
$memberStmt->bind_param('ii', $classId, $studentId);
$memberStmt->execute();
$memberRow = $memberStmt->get_result()->fetch_assoc();
$memberStmt->close();

if (!$memberRow || (string) ($memberRow['status'] ?? '') !== 'approved') {
    json_response(403, ['success' => false, 'message' => 'You are not enrolled in this class.']);
}

if ($deadlineAt !== '' && $deadlineAt !== '0000-00-00 00:00:00') {
    $now = date('Y-m-d H:i:s');
    if ($now > $deadlineAt) {
        json_response(403, ['success' => false, 'message' => 'Submission deadline has passed.']);
    }
}

if ($studentScore < 0 || $studentScore > $totalScore) {
    json_response(400, ['success' => false, 'message' => 'Score must be between 0 and total score.']);
}

$uploadedFileName = $_FILES['attached_output']['name'] ?? '';
$uploadedTmpPath = $_FILES['attached_output']['tmp_name'] ?? '';
$uploadedMime = $_FILES['attached_output']['type'] ?? 'application/octet-stream';
$uploadedSize = isset($_FILES['attached_output']['size']) ? (int) $_FILES['attached_output']['size'] : 0;
$uploadedExtension = strtolower(pathinfo((string) $uploadedFileName, PATHINFO_EXTENSION));

$formatRules = [
    '.docx' => ['docx'],
    '.pdf' => ['pdf'],
    '.xlsx' => ['xlsx'],
    '.png/.jpg' => ['png', 'jpg', 'jpeg']
];

if (!isset($formatRules[$requiredFormat])) {
    json_response(500, ['success' => false, 'message' => 'Invalid required format configuration.']);
}

if (!in_array($uploadedExtension, $formatRules[$requiredFormat], true)) {
    json_response(400, ['success' => false, 'message' => 'Uploaded file format does not match required format.']);
}

$storageRoot = rtrim(sys_get_temp_dir(), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'soe_portfolio_class_submissions';
$studentFolder = $storageRoot . DIRECTORY_SEPARATOR . $studentId;
if (!is_dir($studentFolder) && !mkdir($studentFolder, 0775, true) && !is_dir($studentFolder)) {
    json_response(500, ['success' => false, 'message' => 'Failed to prepare submission storage.']);
}

try {
    $randomToken = bin2hex(random_bytes(6));
} catch (Throwable $e) {
    $randomToken = substr(md5(uniqid((string) mt_rand(), true)), 0, 12);
}

$storedFileName = sprintf('output_%d_%d_%s.%s', $outputId, $studentId, $randomToken, $uploadedExtension);
$storedAbsolutePath = $studentFolder . DIRECTORY_SEPARATOR . $storedFileName;

if (!move_uploaded_file($uploadedTmpPath, $storedAbsolutePath)) {
    json_response(500, ['success' => false, 'message' => 'Failed to save attached file.']);
}

$upsertSql = 'INSERT INTO output_submissions (
                output_id, student_id, student_score, status,
                submitted_at, created_at, updated_at,
                submitted_file_name, submitted_file_path, submitted_file_mime, submitted_file_size
              ) VALUES (?, ?, ?, \'submitted\', NOW(), NOW(), NOW(), ?, ?, ?, ?)
              ON DUPLICATE KEY UPDATE
                student_score = VALUES(student_score),
                status = \'submitted\',
                submitted_at = NOW(),
                undone_at = NULL,
                submitted_file_name = VALUES(submitted_file_name),
                submitted_file_path = VALUES(submitted_file_path),
                submitted_file_mime = VALUES(submitted_file_mime),
                submitted_file_size = VALUES(submitted_file_size),
                updated_at = NOW()';

$upsertStmt = $conn->prepare($upsertSql);
if (!$upsertStmt) {
    json_response(500, ['success' => false, 'message' => 'Failed to save submission.']);
}

$upsertStmt->bind_param('iidsssi', $outputId, $studentId, $studentScore, $uploadedFileName, $storedAbsolutePath, $uploadedMime, $uploadedSize);
$ok = $upsertStmt->execute();
$upsertStmt->close();

if (!$ok) {
    json_response(500, ['success' => false, 'message' => 'Failed to save submission.']);
}

json_response(200, ['success' => true]);
