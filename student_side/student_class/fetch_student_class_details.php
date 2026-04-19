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

$classStudentsExists = (bool) $conn->query("SHOW TABLES LIKE 'class_students'")->fetch_assoc();
if (!$classStudentsExists) {
    json_response(500, ['success' => false, 'message' => 'Table class_students is missing. Apply SQL migration first.']);
}

$memberStmt = $conn->prepare('SELECT status FROM class_students WHERE class_id = ? AND student_id = ? LIMIT 1');
$memberStmt->bind_param('ii', $classId, $studentId);
$memberStmt->execute();
$memberRow = $memberStmt->get_result()->fetch_assoc();
$memberStmt->close();

if (!$memberRow || (string) ($memberRow['status'] ?? '') !== 'approved') {
    json_response(403, ['success' => false, 'message' => 'You are not enrolled in this class.']);
}

$sql = 'SELECT
            c.class_id,
            c.class_name,
            c.term_number,
            c.start_year,
            c.end_year,
            c.deadline_at,
            co.course_name,
            p.program_name,
            CONCAT(COALESCE(fp.first_name, up.first_name), " ", COALESCE(fp.last_name, up.last_name)) AS professor_name
        FROM classes c
        INNER JOIN courses co ON co.course_id = c.course_id
        INNER JOIN programs p ON p.program_id = co.program_id
        LEFT JOIN class_professor_assignments cpa ON cpa.class_id = c.class_id
        LEFT JOIN users up ON up.user_id = cpa.professor_user_id
        LEFT JOIN faculty fp ON fp.user_id = up.user_id
        WHERE c.class_id = ?
        LIMIT 1';

$stmt = $conn->prepare($sql);
if (!$stmt) {
    json_response(500, ['success' => false, 'message' => 'Failed to prepare class detail query.']);
}

$stmt->bind_param('i', $classId);
$stmt->execute();
$details = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$details) {
    json_response(404, ['success' => false, 'message' => 'Class not found.']);
}

json_response(200, ['success' => true, 'details' => $details]);
