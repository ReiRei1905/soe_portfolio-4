<?php

declare(strict_types=1);

require_once __DIR__ . '/list_access_common.php';

$sessionUser = list_require_access($conn);
$schema = list_get_student_lists_schema($conn);
if ($schema === null) {
    faculty_send_json([
        'success' => true,
        'groups' => []
    ]);
}

$columnsResult = $conn->query('SHOW COLUMNS FROM student_lists');
$existingColumns = [];
while ($columnsResult && ($columnRow = $columnsResult->fetch_assoc())) {
    $columnName = trim((string) ($columnRow['Field'] ?? ''));
    if ($columnName !== '') {
        $existingColumns[$columnName] = true;
    }
}

if (isset($existingColumns['year_of_enrollment'])) {
    $yearColumn = 'year_of_enrollment';
} elseif (isset($existingColumns['year_enrollment'])) {
    $yearColumn = 'year_enrollment';
} elseif (isset($existingColumns['enrollment_year'])) {
    $yearColumn = 'enrollment_year';
} else {
    faculty_send_json([
        'success' => false,
        'message' => 'student_lists is missing a year column (expected year_of_enrollment or year_enrollment).'
    ], 500);
}

$programs = list_get_visible_programs($conn, $sessionUser);
if (empty($programs)) {
    faculty_send_json([
        'success' => true,
        'groups' => []
    ]);
}

$programIds = array_values(array_map(static fn($p) => (int) ($p['id'] ?? 0), $programs));
$programNameById = [];
foreach ($programs as $program) {
    $programNameById[(int) $program['id']] = (string) $program['name'];
}

$placeholders = implode(',', array_fill(0, count($programIds), '?'));
$types = str_repeat('i', count($programIds));

$sql =
        'SELECT sl.' . $schema['listId'] . ' AS list_id,
                        sl.' . $schema['programId'] . ' AS program_id,
                        sl.' . $schema['batchName'] . ' AS batch_name,
                        sl.' . $yearColumn . ' AS year_of_enrollment,
                        ' . ($schema['createdBy'] ? ('sl.' . $schema['createdBy']) : '0') . ' AS created_by_user_id,
            COUNT(s.student_id) AS student_count
     FROM student_lists sl
     LEFT JOIN students s
                ON s.program_id = sl.' . $schema['programId'] . '
             AND s.year_of_enrollment = sl.' . $yearColumn . '
         WHERE sl.' . $schema['programId'] . ' IN (' . $placeholders . ')
         GROUP BY sl.' . $schema['listId'] . ', sl.' . $schema['programId'] . ', sl.' . $schema['batchName'] . ', sl.' . $yearColumn . ($schema['createdBy'] ? (', sl.' . $schema['createdBy']) : '') . '
         ORDER BY sl.' . $schema['programId'] . ' ASC, sl.' . $yearColumn . ' DESC, sl.' . $schema['batchName'] . ' ASC';

$stmt = $conn->prepare($sql);
if (!$stmt) {
    faculty_send_json(['success' => false, 'message' => 'Failed to prepare list query.'], 500);
}

$bindArgs = [$types];
foreach ($programIds as $idx => $value) {
    $bindArgs[] = &$programIds[$idx];
}
call_user_func_array([$stmt, 'bind_param'], $bindArgs);

$stmt->execute();
$result = $stmt->get_result();

$groupMap = [];
foreach ($programs as $program) {
    $programId = (int) ($program['id'] ?? 0);
    $groupMap[$programId] = [
        'programId' => $programId,
        'programName' => (string) ($program['name'] ?? ''),
        'lists' => []
    ];
}

while ($result && ($row = $result->fetch_assoc())) {
    $programId = (int) ($row['program_id'] ?? 0);
    if (!isset($groupMap[$programId])) {
        continue;
    }

    $groupMap[$programId]['lists'][] = [
        'listId' => (int) ($row['list_id'] ?? 0),
        'programId' => $programId,
        'programName' => (string) ($programNameById[$programId] ?? ''),
        'batchName' => trim((string) ($row['batch_name'] ?? '')),
        'yearOfEnrollment' => (int) ($row['year_of_enrollment'] ?? 0),
        'studentCount' => (int) ($row['student_count'] ?? 0),
        'canManage' => list_can_manage_program($conn, $sessionUser, $programId)
    ];
}

$stmt->close();

$groups = [];
foreach ($groupMap as $group) {
    // Only send the program to the screen if it actually has lists!
    if (!empty($group['lists'])) {
        $groups[] = $group;
    }
}

faculty_send_json([
    'success' => true,
    'groups' => $groups
]);
