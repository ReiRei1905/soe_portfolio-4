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

if ($outputId <= 0) {
    json_response(400, ['success' => false, 'message' => 'Invalid output reference.']);
}

$membershipStmt = $conn->prepare('SELECT cs.status
                                  FROM class_outputs o
                                  INNER JOIN class_students cs ON cs.class_id = o.class_id AND cs.student_id = ?
                                  WHERE o.output_id = ?
                                  LIMIT 1');
if (!$membershipStmt) {
    json_response(500, ['success' => false, 'message' => 'Failed to validate class membership.']);
}

$membershipStmt->bind_param('ii', $studentId, $outputId);
$membershipStmt->execute();
$membershipRow = $membershipStmt->get_result()->fetch_assoc();
$membershipStmt->close();

if (!$membershipRow || (string) ($membershipRow['status'] ?? '') !== 'approved') {
    json_response(403, ['success' => false, 'message' => 'You are not enrolled in the class for this output.']);
}

$stmt = $conn->prepare('UPDATE output_submissions
                        SET status = \'undone\',
                            undone_at = NOW(),
                            updated_at = NOW()
                        WHERE output_id = ? AND student_id = ?');
if (!$stmt) {
    json_response(500, ['success' => false, 'message' => 'Failed to prepare undo statement.']);
}

$stmt->bind_param('ii', $outputId, $studentId);
$stmt->execute();
$affected = $stmt->affected_rows;
$stmt->close();

if ($affected <= 0) {
    json_response(404, ['success' => false, 'message' => 'No submitted output found to undo.']);
}

json_response(200, ['success' => true]);
