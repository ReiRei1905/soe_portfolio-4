<?php

declare(strict_types=1);

require_once __DIR__ . '/../faculty_access_common.php';

$sessionUser = faculty_require_verified_faculty($conn);
$classId = isset($_GET['class_id']) ? (int) $_GET['class_id'] : 0;

if ($classId <= 0) {
    faculty_send_json(['success' => false, 'message' => 'Invalid class ID.'], 400);
}

if (!faculty_can_handle_class($conn, $sessionUser, $classId)) {
    faculty_send_json(['success' => false, 'message' => 'You are not allowed to access class members.'], 403);
}

if (!faculty_table_exists($conn, 'class_students')) {
    faculty_send_json([
        'success' => false,
        'message' => 'Table class_students is missing. Apply the SQL migration first.'
    ], 500);
}

$sql = 'SELECT cs.class_student_id, cs.class_id, cs.student_id, cs.invitation_source, cs.status,
               cs.requested_at, cs.invited_at, cs.approved_at,
               s.id_number,
               COALESCE(s.first_name, u.first_name) AS first_name,
               COALESCE(s.last_name, u.last_name) AS last_name,
               COALESCE(s.email, u.email) AS email
        FROM class_students cs
        INNER JOIN students s ON s.student_id = cs.student_id
        LEFT JOIN users u ON u.user_id = s.user_id
        WHERE cs.class_id = ?
          AND cs.status IN (\'pending\', \'approved\')
        ORDER BY
            CASE cs.status WHEN \'pending\' THEN 0 ELSE 1 END,
            COALESCE(cs.requested_at, cs.invited_at, cs.approved_at, cs.created_at) DESC';

$stmt = $conn->prepare($sql);
if (!$stmt) {
    faculty_send_json(['success' => false, 'message' => 'Failed to prepare members query.'], 500);
}

$stmt->bind_param('i', $classId);
$stmt->execute();
$result = $stmt->get_result();

$members = [];
$pending = [];
while ($row = $result->fetch_assoc()) {
    $entry = [
        'classStudentId' => (int) ($row['class_student_id'] ?? 0),
        'classId' => (int) ($row['class_id'] ?? 0),
        'studentId' => (int) ($row['student_id'] ?? 0),
        'idNumber' => (string) ($row['id_number'] ?? ''),
        'firstName' => trim((string) ($row['first_name'] ?? '')),
        'lastName' => trim((string) ($row['last_name'] ?? '')),
        'email' => trim((string) ($row['email'] ?? '')),
        'invitationSource' => (string) ($row['invitation_source'] ?? ''),
        'status' => (string) ($row['status'] ?? ''),
        'requestedAt' => $row['requested_at'],
        'invitedAt' => $row['invited_at'],
        'approvedAt' => $row['approved_at']
    ];

    if (($row['status'] ?? '') === 'pending') {
        $pending[] = $entry;
    } else {
        $members[] = $entry;
    }
}
$stmt->close();

faculty_send_json([
    'success' => true,
    'pending' => $pending,
    'members' => $members
]);
