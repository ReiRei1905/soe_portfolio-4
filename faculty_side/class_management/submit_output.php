<?php
session_start();
header('Content-Type: application/json');

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

function json_error_and_exit(int $statusCode, string $message): void
{
    http_response_code($statusCode);
    echo json_encode(['success' => false, 'error' => $message]);
    exit;
}

register_shutdown_function(function () {
    $error = error_get_last();
    if ($error && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR], true)) {
        if (!headers_sent()) {
            http_response_code(500);
            header('Content-Type: application/json');
        }
        echo json_encode(['success' => false, 'error' => 'PHP runtime error: ' . $error['message']]);
    }
});

try {
    $conn = new mysqli("localhost", "root", "", "soe_portfolio");
} catch (Throwable $e) {
    json_error_and_exit(500, 'DB connection failed: ' . $e->getMessage());
}

function resolve_student_id_for_testing(mysqli $conn): array
{
    if (!empty($_SESSION['student_id'])) {
        return ['id' => intval($_SESSION['student_id']), 'isDevFallback' => false];
    }

    // Optional override for local/manual testing, but still ensure it exists in students.
    if (!empty($_POST['test_student_id'])) {
        $candidateId = intval($_POST['test_student_id']);
        $checkStmt = $conn->prepare('SELECT student_id FROM students WHERE student_id = ? LIMIT 1');
        $checkStmt->bind_param('i', $candidateId);
        $checkStmt->execute();
        $row = $checkStmt->get_result()->fetch_assoc();
        $checkStmt->close();
        if (!empty($row['student_id'])) {
            return ['id' => intval($row['student_id']), 'isDevFallback' => true];
        }
    }

    // Local dev fallback: pick any existing student id to satisfy FK constraints.
    $firstStmt = $conn->prepare('SELECT student_id FROM students ORDER BY student_id ASC LIMIT 1');
    $firstStmt->execute();
    $first = $firstStmt->get_result()->fetch_assoc();
    $firstStmt->close();

    if (!empty($first['student_id'])) {
        return ['id' => intval($first['student_id']), 'isDevFallback' => true];
    }

    return ['id' => 0, 'isDevFallback' => true];
}

$studentContext = resolve_student_id_for_testing($conn);
$student_id = intval($studentContext['id']);
$isDevFallbackStudent = !empty($studentContext['isDevFallback']);

if ($student_id <= 0) {
    json_error_and_exit(401, 'No valid student found for testing. Add at least one row in students table or pass an existing test_student_id.');
}

$output_id = isset($_POST['output_id']) ? intval($_POST['output_id']) : 0;
$student_score = isset($_POST['student_score']) ? floatval($_POST['student_score']) : null;

if (!isset($_FILES['attached_output']) || !is_uploaded_file($_FILES['attached_output']['tmp_name'])) {
    json_error_and_exit(400, 'Please attach the required output file');
}

$uploadedFileName = $_FILES['attached_output']['name'] ?? '';
$uploadedTmpPath = $_FILES['attached_output']['tmp_name'] ?? '';
$uploadedMime = $_FILES['attached_output']['type'] ?? 'application/octet-stream';
$uploadedSize = isset($_FILES['attached_output']['size']) ? intval($_FILES['attached_output']['size']) : 0;

if ($output_id <= 0 || $student_score === null) {
    json_error_and_exit(400, 'Missing parameters');
}

// 1) Validate output exists and get total_score, class_id, and required format
$stmt = $conn->prepare('SELECT class_id, total_score, required_file_format FROM class_outputs WHERE output_id = ? LIMIT 1');
$stmt->bind_param('i', $output_id);
$stmt->execute();
$res = $stmt->get_result();
if (!$row = $res->fetch_assoc()) {
    json_error_and_exit(404, 'Output not found');
}
$class_id = intval($row['class_id']);
$total_score = floatval($row['total_score']);
$requiredFormat = strtolower(trim((string) ($row['required_file_format'] ?? '')));
$stmt->close();

$uploadedExtension = strtolower(pathinfo($uploadedFileName, PATHINFO_EXTENSION));

$formatRules = [
    '.docx' => ['docx'],
    '.pdf' => ['pdf'],
    '.xlsx' => ['xlsx'],
    '.png/.jpg' => ['png', 'jpg', 'jpeg']
];

if (!isset($formatRules[$requiredFormat])) {
    json_error_and_exit(500, 'Required format configuration is invalid for this output');
}

if (!in_array($uploadedExtension, $formatRules[$requiredFormat], true)) {
    json_error_and_exit(400, 'Uploaded file format does not match the required format');
}

// 2) Optional: check deadline on classes table
$stmt = $conn->prepare('SELECT deadline_at FROM classes WHERE class_id = ? LIMIT 1');
$stmt->bind_param('i', $class_id);
$stmt->execute();
$res = $stmt->get_result();
if (!$isDevFallbackStudent && ($c = $res->fetch_assoc())) {
    if (!empty($c['deadline_at']) && $c['deadline_at'] !== '0000-00-00 00:00:00') {
        $deadline = $c['deadline_at'];
        $now = date('Y-m-d H:i:s');
        if ($now > $deadline) {
            json_error_and_exit(403, 'Submission deadline has passed');
        }
    }
}
$stmt->close();

// 3) Validate enrollment if class_students table exists
$enrolled = true; // default allow
$check = $conn->query("SHOW TABLES LIKE 'class_students'");
if (!$isDevFallbackStudent && $check && $check->num_rows > 0) {
    $stmt = $conn->prepare('SELECT 1 FROM class_students WHERE class_id = ? AND student_id = ? LIMIT 1');
    $stmt->bind_param('ii', $class_id, $student_id);
    $stmt->execute();
    $res = $stmt->get_result();
    if (!$res->fetch_assoc()) {
        json_error_and_exit(403, 'Student not enrolled in class');
    }
    $stmt->close();
}

// 4) Validate score bounds
if ($student_score < 0 || $student_score > $total_score) {
    json_error_and_exit(400, 'Invalid score');
}

// 5) Upsert submission (insert or update existing)
$storageRoot = rtrim(sys_get_temp_dir(), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'soe_portfolio_class_submissions';
$studentFolder = $storageRoot . DIRECTORY_SEPARATOR . $student_id;

if (!is_dir($studentFolder) && !mkdir($studentFolder, 0775, true) && !is_dir($studentFolder)) {
    json_error_and_exit(500, 'Failed to prepare submission storage');
}

$randomToken = '';
try {
    $randomToken = bin2hex(random_bytes(6));
} catch (Throwable $e) {
    $randomToken = substr(md5(uniqid((string) mt_rand(), true)), 0, 12);
}

$storedFileName = sprintf('output_%d_%d_%s.%s', $output_id, $student_id, $randomToken, $uploadedExtension);
$storedAbsolutePath = $studentFolder . DIRECTORY_SEPARATOR . $storedFileName;

if (!move_uploaded_file($uploadedTmpPath, $storedAbsolutePath)) {
    json_error_and_exit(500, 'Failed to save attached file');
}

$sql = "INSERT INTO output_submissions (output_id, student_id, student_score, status, submitted_at, created_at, updated_at, submitted_file_name, submitted_file_path, submitted_file_mime, submitted_file_size)
        VALUES (?, ?, ?, 'submitted', NOW(), NOW(), NOW(), ?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE
          student_score = VALUES(student_score),
          status = 'submitted',
          submitted_at = NOW(),
          submitted_file_name = VALUES(submitted_file_name),
          submitted_file_path = VALUES(submitted_file_path),
          submitted_file_mime = VALUES(submitted_file_mime),
          submitted_file_size = VALUES(submitted_file_size),
          updated_at = NOW()";
$stmt = $conn->prepare($sql);
$stmt->bind_param('iidsssi', $output_id, $student_id, $student_score, $uploadedFileName, $storedAbsolutePath, $uploadedMime, $uploadedSize);
$ok = $stmt->execute();
if ($ok) {
    echo json_encode(['success' => true]);
} else {
    json_error_and_exit(500, 'DB error');
}
$stmt->close();
$conn->close();

?>
