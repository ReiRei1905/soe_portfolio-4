<?php

declare(strict_types=1);

require_once __DIR__ . '/../api/common.php';

$conn = db_connect();

if (!isset($_SESSION['email']) || trim((string) $_SESSION['email']) === '') {
    json_response(401, ['success' => false, 'message' => 'Unauthorized. Please log in.']);
}

$studentId = current_student_id($conn);
$search = trim((string) ($_GET['q'] ?? ''));
$programFilter = isset($_GET['program_id']) ? (int) $_GET['program_id'] : 0;

$studentStmt = $conn->prepare('SELECT student_id, program_id FROM students WHERE student_id = ? LIMIT 1');
$studentStmt->bind_param('i', $studentId);
$studentStmt->execute();
$studentRow = $studentStmt->get_result()->fetch_assoc();
$studentStmt->close();

if (!$studentRow) {
    json_response(404, ['success' => false, 'message' => 'Student profile not found.']);
}

$studentProgramId = (int) ($studentRow['program_id'] ?? 0);

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
            p.program_id,
            p.program_name,
            cs.status AS enrollment_status,
            cs.invitation_source,
            CONCAT(COALESCE(fp.first_name, up.first_name), " ", COALESCE(fp.last_name, up.last_name)) AS professor_name
        FROM classes c
        INNER JOIN courses co ON co.course_id = c.course_id
        INNER JOIN programs p ON p.program_id = co.program_id
        LEFT JOIN class_students cs ON cs.class_id = c.class_id AND cs.student_id = ?
        LEFT JOIN class_professor_assignments cpa ON cpa.class_id = c.class_id
        LEFT JOIN users up ON up.user_id = cpa.professor_user_id
        LEFT JOIN faculty fp ON fp.user_id = up.user_id
           WHERE 1 = 1';

    $params = [$studentId];
    $types = 'i';

    if ($programFilter > 0) {
        $sql .= ' AND (co.program_id = ?
                OR EXISTS (
                    SELECT 1
                    FROM program_course_links pcl
                    WHERE pcl.course_id = c.course_id AND pcl.program_id = ?
                ))';
        $types .= 'ii';
        $params[] = $programFilter;
        $params[] = $programFilter;
    }

if ($search !== '') {
    $sql .= ' AND (c.class_name LIKE CONCAT("%", ?, "%")
               OR co.course_name LIKE CONCAT("%", ?, "%")
               OR co.course_code LIKE CONCAT("%", ?, "%"))';
    $types .= 'sss';
    $params[] = $search;
    $params[] = $search;
    $params[] = $search;
}

$sql .= ' ORDER BY c.created_at DESC';

$stmt = $conn->prepare($sql);
if (!$stmt) {
    json_response(500, ['success' => false, 'message' => 'Failed to prepare classes query.']);
}

$bind = [$types];
foreach ($params as $idx => $value) {
    $bind[] = &$params[$idx];
}
call_user_func_array([$stmt, 'bind_param'], $bind);

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
        'programId' => (int) ($row['program_id'] ?? 0),
        'programName' => (string) ($row['program_name'] ?? ''),
        'enrollmentStatus' => $row['enrollment_status'],
        'invitationSource' => $row['invitation_source'],
        'professorName' => trim((string) ($row['professor_name'] ?? ''))
    ];
}
$stmt->close();

$programSql = 'SELECT DISTINCT p.program_id, p.program_name
               FROM programs p
               WHERE EXISTS (
                    SELECT 1
                    FROM courses co
                    INNER JOIN classes c ON c.course_id = co.course_id
                    WHERE co.program_id = p.program_id
               )
               OR EXISTS (
                    SELECT 1
                    FROM program_course_links pcl
                    INNER JOIN classes c ON c.course_id = pcl.course_id
                    WHERE pcl.program_id = p.program_id
               )
               ORDER BY p.program_name ASC';

$programResult = $conn->query($programSql);
$programOptions = [];
if ($programResult instanceof mysqli_result) {
    while ($programRow = $programResult->fetch_assoc()) {
        $programOptions[] = [
            'programId' => (int) ($programRow['program_id'] ?? 0),
            'programName' => (string) ($programRow['program_name'] ?? '')
        ];
    }
    $programResult->free();
}

json_response(200, [
    'success' => true,
    'studentProgramId' => $studentProgramId,
    'programOptions' => $programOptions,
    'classes' => $classes
]);
