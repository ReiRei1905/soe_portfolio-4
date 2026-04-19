<?php

declare(strict_types=1);

require_once __DIR__ . '/../faculty_access_common.php';

$sessionUser = faculty_require_verified_faculty($conn);
$classId = isset($_GET['class_id']) ? (int) $_GET['class_id'] : 0;

if ($classId <= 0) {
    faculty_send_json(['success' => false, 'message' => 'Invalid class ID.'], 400);
}

if (!faculty_can_handle_class($conn, $sessionUser, $classId)) {
    faculty_send_json(['success' => false, 'message' => 'You are not allowed to access this class.'], 403);
}

$sql = 'SELECT 
            c.class_id, c.class_name, c.term_number, c.start_year, c.end_year,
            co.course_id, co.course_name,
            p.program_id, p.program_name,
            c.deadline_at
        FROM classes c
        INNER JOIN courses co ON c.course_id = co.course_id
        INNER JOIN programs p ON co.program_id = p.program_id
        WHERE c.class_id = ?';

$stmt = $conn->prepare($sql);
if (!$stmt) {
    faculty_send_json(['success' => false, 'message' => 'Failed to prepare class detail query.'], 500);
}

$stmt->bind_param('i', $classId);
$stmt->execute();
$result = $stmt->get_result();
$row = $result ? $result->fetch_assoc() : null;
$stmt->close();

if (!$row) {
    faculty_send_json(['success' => false, 'message' => 'Class not found.'], 404);
}

$row['can_manage_class'] = faculty_can_manage_class($conn, $sessionUser, $classId);
$row['is_assigned_professor'] = faculty_is_assigned_professor($conn, $classId, (int) ($sessionUser['user_id'] ?? 0));

faculty_send_json(['success' => true, 'details' => $row]);
