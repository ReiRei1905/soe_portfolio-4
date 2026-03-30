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

try {
    $conn = new mysqli("localhost", "root", "", "soe_portfolio");
} catch (Throwable $e) {
    json_error_and_exit(500, 'DB connection failed: ' . $e->getMessage());
}

function resolve_student_id_for_testing(mysqli $conn): int
{
    if (!empty($_SESSION['student_id'])) {
        return intval($_SESSION['student_id']);
    }

    // Optional override for local/manual testing, if it exists in students table.
    if (!empty($_POST['test_student_id'])) {
        $candidateId = intval($_POST['test_student_id']);
        $checkStmt = $conn->prepare('SELECT student_id FROM students WHERE student_id = ? LIMIT 1');
        $checkStmt->bind_param('i', $candidateId);
        $checkStmt->execute();
        $row = $checkStmt->get_result()->fetch_assoc();
        $checkStmt->close();
        if (!empty($row['student_id'])) {
            return intval($row['student_id']);
        }
    }

    // Local dev fallback: choose an existing student row.
    $firstStmt = $conn->prepare('SELECT student_id FROM students ORDER BY student_id ASC LIMIT 1');
    $firstStmt->execute();
    $first = $firstStmt->get_result()->fetch_assoc();
    $firstStmt->close();

    return !empty($first['student_id']) ? intval($first['student_id']) : 0;
}

$student_id = resolve_student_id_for_testing($conn);

if ($student_id <= 0) {
    json_error_and_exit(401, 'Not authenticated');
}

$output_id = isset($_POST['output_id']) ? intval($_POST['output_id']) : 0;

if ($output_id <= 0) {
    json_error_and_exit(400, 'Missing output_id');
}

// Optional: check if graded already and disallow undo if graded
$stmt = $conn->prepare('SELECT professor_score FROM output_submissions WHERE output_id = ? AND student_id = ? LIMIT 1');
$stmt->bind_param('ii', $output_id, $student_id);
$stmt->execute();
$res = $stmt->get_result();
if ($row = $res->fetch_assoc()) {
    if (!is_null($row['professor_score'])) {
        json_error_and_exit(403, 'Cannot undo after grading');
    }
}
$stmt->close();

$stmt = $conn->prepare('UPDATE output_submissions SET status = "undone", undone_at = NOW(), submitted_file_name = NULL, submitted_file_path = NULL, submitted_file_mime = NULL, submitted_file_size = NULL, updated_at = NOW() WHERE output_id = ? AND student_id = ?');
$stmt->bind_param('ii', $output_id, $student_id);
$ok = $stmt->execute();
if ($ok) {
    echo json_encode(['success' => true]);
} else {
    json_error_and_exit(500, 'DB error');
}
$stmt->close();
$conn->close();

?>
