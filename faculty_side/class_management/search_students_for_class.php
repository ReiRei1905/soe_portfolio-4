<?php

declare(strict_types=1);

require_once __DIR__ . '/../faculty_access_common.php';

$sessionUser = faculty_require_verified_faculty($conn);
$classId = isset($_GET['class_id']) ? (int) $_GET['class_id'] : 0;
$keyword = trim((string) ($_GET['q'] ?? ''));

if ($classId <= 0) {
    faculty_send_json(['success' => false, 'message' => 'Invalid class ID.'], 400);
}

if (!faculty_can_handle_class($conn, $sessionUser, $classId)) {
    faculty_send_json(['success' => false, 'message' => 'You are not allowed to search students for this class.'], 403);
}

if ($keyword === '') {
    faculty_send_json(['success' => true, 'students' => []]);
}

if (!faculty_table_exists($conn, 'class_students')) {
    faculty_send_json([
        'success' => false,
        'message' => 'Table class_students is missing. Apply the SQL migration first.'
    ], 500);
}

$sql = 'SELECT s.student_id, s.id_number,
               COALESCE(s.first_name, u.first_name) AS first_name,
               COALESCE(s.last_name, u.last_name) AS last_name,
               COALESCE(s.email, u.email) AS email,
               cs.status AS current_membership_status
        FROM students s
        LEFT JOIN users u ON u.user_id = s.user_id
        LEFT JOIN class_students cs ON cs.class_id = ? AND cs.student_id = s.student_id
        WHERE (s.id_number LIKE CONCAT(\'%%\', ?, \'%%\')
            OR s.email LIKE CONCAT(\'%%\', ?, \'%%\')
            OR CONCAT(COALESCE(s.first_name, u.first_name), \' \', COALESCE(s.last_name, u.last_name)) LIKE CONCAT(\'%%\', ?, \'%%\'))
        ORDER BY COALESCE(s.last_name, u.last_name) ASC, COALESCE(s.first_name, u.first_name) ASC
        LIMIT 30';

$stmt = $conn->prepare($sql);
if (!$stmt) {
    faculty_send_json(['success' => false, 'message' => 'Failed to prepare student search query.'], 500);
}

$stmt->bind_param('isss', $classId, $keyword, $keyword, $keyword);
$stmt->execute();
$result = $stmt->get_result();

$students = [];
while ($row = $result->fetch_assoc()) {
    $students[] = [
        'studentId' => (int) ($row['student_id'] ?? 0),
        'idNumber' => (string) ($row['id_number'] ?? ''),
        'firstName' => trim((string) ($row['first_name'] ?? '')),
        'lastName' => trim((string) ($row['last_name'] ?? '')),
        'email' => trim((string) ($row['email'] ?? '')),
        'currentMembershipStatus' => $row['current_membership_status']
    ];
}
$stmt->close();

faculty_send_json(['success' => true, 'students' => $students]);
