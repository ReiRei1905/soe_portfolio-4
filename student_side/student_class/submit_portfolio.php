<?php

declare(strict_types=1);

require_once __DIR__ . '/portfolio_submission_common.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(405, ['success' => false, 'message' => 'Method not allowed.']);
}

$conn = db_connect();

if (!isset($_SESSION['email']) || trim((string) $_SESSION['email']) === '') {
    json_response(401, ['success' => false, 'message' => 'Unauthorized. Please log in.']);
}

$classId = isset($_POST['class_id']) ? (int) $_POST['class_id'] : 0;
$studentId = current_student_id($conn);

if ($classId <= 0) {
    json_response(400, ['success' => false, 'message' => 'Invalid class reference.']);
}

assert_portfolio_submission_table_exists($conn);
require_approved_student_in_class($conn, $classId, $studentId);

$completionSql = 'SELECT
                    COUNT(*) AS total_outputs,
                    SUM(CASE WHEN os.status = "submitted" THEN 1 ELSE 0 END) AS submitted_outputs
                  FROM class_outputs o
                  LEFT JOIN output_submissions os
                    ON os.output_id = o.output_id
                   AND os.student_id = ?
                  WHERE o.class_id = ?';

$completionStmt = $conn->prepare($completionSql);
if (!$completionStmt) {
    json_response(500, ['success' => false, 'message' => 'Failed to validate output completion.']);
}

$completionStmt->bind_param('ii', $studentId, $classId);
$completionStmt->execute();
$completionRow = $completionStmt->get_result()->fetch_assoc();
$completionStmt->close();

$totalOutputs = (int) ($completionRow['total_outputs'] ?? 0);
$submittedOutputs = (int) ($completionRow['submitted_outputs'] ?? 0);

if ($totalOutputs <= 0) {
    json_response(422, ['success' => false, 'message' => 'No required outputs found for this class yet.']);
}

if ($submittedOutputs < $totalOutputs) {
    json_response(422, [
        'success' => false,
        'message' => 'Submit all required outputs first before submitting your portfolio.',
        'submitted_outputs' => $submittedOutputs,
        'total_outputs' => $totalOutputs
    ]);
}

$upsertSql = 'INSERT INTO class_portfolio_submissions (
                class_id, student_id, status, submitted_at, undone_at, created_at, updated_at
              ) VALUES (?, ?, "submitted", NOW(), NULL, NOW(), NOW())
              ON DUPLICATE KEY UPDATE
                status = "submitted",
                submitted_at = NOW(),
                undone_at = NULL,
                updated_at = NOW()';

$upsertStmt = $conn->prepare($upsertSql);
if (!$upsertStmt) {
    json_response(500, ['success' => false, 'message' => 'Failed to save portfolio submission status.']);
}

$upsertStmt->bind_param('ii', $classId, $studentId);
$ok = $upsertStmt->execute();
$upsertStmt->close();

if (!$ok) {
    json_response(500, ['success' => false, 'message' => 'Failed to save portfolio submission status.']);
}

$studentDisplayName = get_student_display_name($conn, $studentId);
$className = get_class_name_by_id($conn, $classId);
notify_assigned_professor_for_portfolio_event($conn, $classId, $studentDisplayName, $className, false);

json_response(200, [
    'success' => true,
    'status' => 'submitted',
    'message' => 'Portfolio submitted successfully.'
]);
