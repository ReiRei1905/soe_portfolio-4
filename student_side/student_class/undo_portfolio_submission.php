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

$updateSql = 'UPDATE class_portfolio_submissions
              SET status = "undone",
                  undone_at = NOW(),
                  updated_at = NOW()
              WHERE class_id = ?
                AND student_id = ?
                AND status = "submitted"';

$updateStmt = $conn->prepare($updateSql);
if (!$updateStmt) {
    json_response(500, ['success' => false, 'message' => 'Failed to undo portfolio submission.']);
}

$updateStmt->bind_param('ii', $classId, $studentId);
$updateStmt->execute();
$affected = $updateStmt->affected_rows;
$updateStmt->close();

if ($affected <= 0) {
    json_response(404, ['success' => false, 'message' => 'No submitted portfolio found to undo.']);
}

$studentDisplayName = get_student_display_name($conn, $studentId);
$className = get_class_name_by_id($conn, $classId);
notify_assigned_professor_for_portfolio_event($conn, $classId, $studentDisplayName, $className, true);

json_response(200, [
    'success' => true,
    'status' => 'undone',
    'message' => 'Portfolio submission has been undone.'
]);
