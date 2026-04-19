<?php

declare(strict_types=1);

require_once __DIR__ . '/../api/common.php';

$conn = db_connect();

if (!isset($_SESSION['email']) || trim((string) $_SESSION['email']) === '') {
    json_response(401, ['success' => false, 'message' => 'Unauthorized. Please log in.']);
}

$studentId = current_student_id($conn);

$classStudentsExists = (bool) $conn->query("SHOW TABLES LIKE 'class_students'")->fetch_assoc();
if (!$classStudentsExists) {
    json_response(500, ['success' => false, 'message' => 'Table class_students is missing. Apply SQL migration first.']);
}

$sql = 'SELECT
            c.class_id,
            c.class_name,
            c.term_number,
            c.start_year,
            c.end_year,
            co.course_name,
            co.course_code,
            p.program_name,
            cs.invitation_source,
            cs.approved_at,
            CONCAT(COALESCE(fp.first_name, up.first_name), " ", COALESCE(fp.last_name, up.last_name)) AS professor_name
        FROM class_students cs
        INNER JOIN classes c ON c.class_id = cs.class_id
        INNER JOIN courses co ON co.course_id = c.course_id
        INNER JOIN programs p ON p.program_id = co.program_id
        LEFT JOIN class_professor_assignments cpa ON cpa.class_id = c.class_id
        LEFT JOIN users up ON up.user_id = cpa.professor_user_id
        LEFT JOIN faculty fp ON fp.user_id = up.user_id
        WHERE cs.student_id = ?
          AND cs.status = \'approved\'
        ORDER BY COALESCE(cs.approved_at, cs.updated_at, cs.created_at) DESC';

$stmt = $conn->prepare($sql);
if (!$stmt) {
    json_response(500, ['success' => false, 'message' => 'Failed to prepare my classes query.']);
}

$stmt->bind_param('i', $studentId);
$stmt->execute();
$result = $stmt->get_result();

$classes = [];
while ($row = $result->fetch_assoc()) {
    $classes[] = [
        'classId' => (int) ($row['class_id'] ?? 0),
        'className' => (string) ($row['class_name'] ?? ''),
        'termNumber' => (string) ($row['term_number'] ?? ''),
        'startYear' => (string) ($row['start_year'] ?? ''),
        'endYear' => (string) ($row['end_year'] ?? ''),
        'courseName' => (string) ($row['course_name'] ?? ''),
        'courseCode' => (string) ($row['course_code'] ?? ''),
        'programName' => (string) ($row['program_name'] ?? ''),
        'invitationSource' => (string) ($row['invitation_source'] ?? ''),
        'professorName' => trim((string) ($row['professor_name'] ?? '')),
        'approvedAt' => $row['approved_at']
    ];
}
$stmt->close();

json_response(200, ['success' => true, 'classes' => $classes]);
