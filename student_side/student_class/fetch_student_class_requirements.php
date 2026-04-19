<?php

declare(strict_types=1);

require_once __DIR__ . '/../api/common.php';

$conn = db_connect();

if (!isset($_SESSION['email']) || trim((string) $_SESSION['email']) === '') {
    json_response(401, ['success' => false, 'message' => 'Unauthorized. Please log in.']);
}

$classId = isset($_GET['class_id']) ? (int) $_GET['class_id'] : 0;
$studentId = current_student_id($conn);

if ($classId <= 0) {
    json_response(400, ['success' => false, 'message' => 'Invalid class reference.']);
}

$membershipStmt = $conn->prepare('SELECT status FROM class_students WHERE class_id = ? AND student_id = ? LIMIT 1');
if (!$membershipStmt) {
    json_response(500, ['success' => false, 'message' => 'Failed to validate class membership.']);
}
$membershipStmt->bind_param('ii', $classId, $studentId);
$membershipStmt->execute();
$membershipRow = $membershipStmt->get_result()->fetch_assoc();
$membershipStmt->close();

if (!$membershipRow || (string) ($membershipRow['status'] ?? '') !== 'approved') {
    json_response(403, ['success' => false, 'message' => 'You are not enrolled in this class.']);
}

$stmt = $conn->prepare('SELECT requirement_id, requirement_desc FROM requirements WHERE class_id = ? ORDER BY created_at ASC');
if (!$stmt) {
    json_response(500, ['success' => false, 'message' => 'Failed to load requirements.']);
}

$stmt->bind_param('i', $classId);
$stmt->execute();
$result = $stmt->get_result();

$requirements = [];
while ($row = $result->fetch_assoc()) {
    $requirements[] = [
        'requirementId' => (int) ($row['requirement_id'] ?? 0),
        'requirementDesc' => (string) ($row['requirement_desc'] ?? '')
    ];
}
$stmt->close();

json_response(200, ['success' => true, 'requirements' => $requirements]);
