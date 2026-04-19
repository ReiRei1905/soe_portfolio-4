<?php

declare(strict_types=1);

require_once __DIR__ . '/../faculty_access_common.php';

$sessionUser = faculty_require_verified_faculty($conn);

$roleLabel = faculty_normalize_role_label((string) ($sessionUser['faculty_role'] ?? ''));
$isProgramDirectorView = faculty_is_program_director($sessionUser);
$isProfessorView = faculty_is_verified_faculty($sessionUser)
    && str_contains($roleLabel, 'professor')
    && !faculty_is_executive_director($sessionUser)
    && !faculty_is_program_director($sessionUser);

$query = 'SELECT c.class_id, c.class_name, c.term_number, c.start_year, c.end_year, co.course_name, co.course_code';

if (faculty_table_exists($conn, 'class_professor_assignments')) {
    $query .= ', cpa.professor_user_id AS assigned_professor_user_id,
               CONCAT(COALESCE(fp.first_name, up.first_name), " ", COALESCE(fp.last_name, up.last_name)) AS assigned_professor_name,
               CONCAT(COALESCE(af.first_name, au.first_name), " ", COALESCE(af.last_name, au.last_name)) AS assigned_by_program_director_name';
}

$query .= ' FROM classes c
            INNER JOIN courses co ON c.course_id = co.course_id';

if (faculty_table_exists($conn, 'class_professor_assignments')) {
    $query .= ' LEFT JOIN class_professor_assignments cpa ON cpa.class_id = c.class_id
                LEFT JOIN users up ON up.user_id = cpa.professor_user_id
                LEFT JOIN faculty fp ON fp.user_id = up.user_id
                LEFT JOIN users au ON au.user_id = cpa.assigned_by_user_id
                LEFT JOIN faculty af ON af.user_id = au.user_id';
}

if ($isProfessorView) {
    if (!faculty_table_exists($conn, 'class_professor_assignments')) {
        faculty_send_json(['success' => true, 'classes' => []]);
    }

    $query .= ' WHERE cpa.professor_user_id = ' . (int) ($sessionUser['user_id'] ?? 0);
}

$result = $conn->query($query);
if (!$result) {
    faculty_send_json(['success' => false, 'message' => 'Failed to fetch classes.'], 500);
}

$classes = [];
while ($row = $result->fetch_assoc()) {
    $row['is_pending_request'] = 0;
    $row['request_status'] = 'approved';
    $classes[] = $row;
}

if (faculty_table_exists($conn, 'class_creation_requests')) {
    $pendingSql =
        'SELECT
            0 AS class_id,
            r.request_id,
            r.course_id,
            r.class_name,
            r.term_number,
            r.start_year,
            r.end_year,
            co.course_name,
            co.course_code,
            0 AS assigned_professor_user_id,
            "" AS assigned_professor_name,
            CONCAT(COALESCE(af.first_name, au.first_name), " ", COALESCE(af.last_name, au.last_name)) AS assigned_by_program_director_name,
            CONCAT(COALESCE(rf.first_name, ru.first_name), " ", COALESCE(rf.last_name, ru.last_name)) AS created_by_professor_name,
            CONCAT(COALESCE(pdf.first_name, pdu.first_name), " ", COALESCE(pdf.last_name, pdu.last_name)) AS program_director_name,
            1 AS is_pending_request,
            r.request_status
         FROM class_creation_requests r
         INNER JOIN courses co ON co.course_id = r.course_id
         LEFT JOIN users au ON au.user_id = r.reviewed_by_user_id
         LEFT JOIN faculty af ON af.user_id = au.user_id
         LEFT JOIN users ru ON ru.user_id = r.requested_by_user_id
         LEFT JOIN faculty rf ON rf.user_id = ru.user_id
         LEFT JOIN users pdu ON pdu.user_id = r.program_director_user_id
         LEFT JOIN faculty pdf ON pdf.user_id = pdu.user_id
         WHERE r.request_status = ?';

    $pendingParams = [];
    $pendingTypes = 's';
    $pendingStatus = 'pending';
    $pendingParams[] = $pendingStatus;

    if ($isProfessorView) {
        $pendingSql .= ' AND r.requested_by_user_id = ?';
        $pendingTypes .= 'i';
        $pendingParams[] = (int) ($sessionUser['user_id'] ?? 0);
    } elseif (!$isProgramDirectorView && !faculty_is_executive_director($sessionUser)) {
        $pendingSql .= ' AND 1 = 0';
    }

    $pendingStmt = $conn->prepare($pendingSql);
    if ($pendingStmt) {
        $bindArgs = [$pendingTypes];
        foreach ($pendingParams as $idx => $value) {
            $bindArgs[] = &$pendingParams[$idx];
        }
        call_user_func_array([$pendingStmt, 'bind_param'], $bindArgs);

        $pendingStmt->execute();
        $pendingRes = $pendingStmt->get_result();
        while ($pendingRes && ($pendingRow = $pendingRes->fetch_assoc())) {
            if ($isProgramDirectorView && !faculty_can_review_class_request_by_course($conn, $sessionUser, (int) ($pendingRow['course_id'] ?? 0))) {
                continue;
            }

            $sharedDirectorNames = faculty_get_program_director_names_by_course($conn, (int) ($pendingRow['course_id'] ?? 0));
            $sharedDirectorLabel = faculty_format_name_list($sharedDirectorNames);
            if ($sharedDirectorLabel !== '') {
                $pendingRow['program_director_name'] = $sharedDirectorLabel;
            }

            $classes[] = $pendingRow;
        }
        $pendingStmt->close();
    }
}

faculty_send_json(['success' => true, 'classes' => $classes]);
