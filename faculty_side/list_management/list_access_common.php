<?php

declare(strict_types=1);

require_once __DIR__ . '/../faculty_access_common.php';

function list_normalize_role(string $value): string
{
    return faculty_normalize_role_label($value);
}

function list_can_access_module(array $sessionUser): bool
{
    if (!faculty_is_verified_faculty($sessionUser)) {
        return false;
    }

    $role = list_normalize_role((string) ($sessionUser['faculty_role'] ?? ''));
    // Ensure we check the core EXD function so it doesn't fail if the text just says "Program Director"
    return str_contains($role, 'program director') || str_contains($role, 'executive director') || faculty_is_executive_director($sessionUser);
}

function list_require_access(mysqli $conn): array
{
    $sessionUser = faculty_require_verified_faculty($conn);

    if (!list_can_access_module($sessionUser)) {
        faculty_send_json([
            'success' => false,
            'message' => 'Only Program Directors can access list management.'
        ], 403);
    }

    return $sessionUser;
}

function list_get_assigned_program_ids(mysqli $conn, array $sessionUser): array
{
    if (!faculty_table_exists($conn, 'program_director_assignments')) {
        return [];
    }

    $programIds = [];
    $userId = (int) ($sessionUser['user_id'] ?? 0);
    $stmt = $conn->prepare('SELECT program_id FROM program_director_assignments WHERE program_director_user_id = ?');
    if (!$stmt) {
        return [];
    }

    $stmt->bind_param('i', $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($result && ($row = $result->fetch_assoc())) {
        $programIds[] = (int) ($row['program_id'] ?? 0);
    }
    $stmt->close();

    $programIds = array_values(array_filter(array_unique($programIds), static fn($id) => $id > 0));
    sort($programIds);

    return $programIds;
}

function list_get_visible_programs(mysqli $conn, array $sessionUser): array
{
    if (!list_can_access_module($sessionUser)) {
        return [];
    }

    // Rely on the core system function to properly detect EXD
    $isExd = faculty_is_executive_director($sessionUser);
    
    $assignedIds = list_get_assigned_program_ids($conn, $sessionUser);

    $programs = [];
    $result = $conn->query('SELECT program_id, program_name FROM programs ORDER BY program_name ASC');
    if (!$result) {
        return [];
    }

    while ($result && ($row = $result->fetch_assoc())) {
        $pid = (int) ($row['program_id'] ?? 0);
        // EXD sees all. PD sees only assigned.
        if ($isExd || in_array($pid, $assignedIds, true)) {
            $programs[] = [
                'id' => $pid,
                'name' => trim((string) ($row['program_name'] ?? ''))
            ];
        }
    }

    return $programs;
}

function list_get_creatable_programs(mysqli $conn, array $sessionUser): array
{
    if (!list_can_access_module($sessionUser)) {
        return [];
    }

    // Both EXD and PD can ONLY create for programs they are officially assigned to
    $assignedIds = list_get_assigned_program_ids($conn, $sessionUser);
    if (empty($assignedIds)) {
        return [];
    }

    $placeholders = implode(',', array_fill(0, count($assignedIds), '?'));
    $stmt = $conn->prepare("SELECT program_id, program_name FROM programs WHERE program_id IN ($placeholders) ORDER BY program_name ASC");
    if (!$stmt) {
        return [];
    }

    $types = str_repeat('i', count($assignedIds));
    $bindArgs = [$types];
    foreach ($assignedIds as $idx => $value) {
        $bindArgs[] = &$assignedIds[$idx];
    }
    call_user_func_array([$stmt, 'bind_param'], $bindArgs);
    
    $stmt->execute();
    $result = $stmt->get_result();
    $programs = [];
    while ($result && ($row = $result->fetch_assoc())) {
        $programs[] = [
            'id' => (int) ($row['program_id'] ?? 0),
            'name' => trim((string) ($row['program_name'] ?? ''))
        ];
    }
    $stmt->close();

    return $programs;
}

function list_can_manage_program(mysqli $conn, array $sessionUser, int $programId): bool
{
    if ($programId <= 0 || !list_can_access_module($sessionUser)) {
        return false;
    }

    // To CREATE/MANAGE, they must be explicitly assigned to this specific program ID
    $assignedIds = list_get_assigned_program_ids($conn, $sessionUser);
    return in_array($programId, $assignedIds, true);
}

function list_find_student_lists_column(mysqli $conn, array $candidates): ?string
{
    if (!faculty_table_exists($conn, 'student_lists') || empty($candidates)) {
        return null;
    }

    $result = $conn->query('SHOW COLUMNS FROM student_lists');
    if (!$result) {
        return null;
    }

    $existing = [];
    while ($result && ($row = $result->fetch_assoc())) {
        $name = trim((string) ($row['Field'] ?? ''));
        if ($name !== '') {
            $existing[$name] = true;
        }
    }

    foreach ($candidates as $candidate) {
        if (isset($existing[$candidate])) {
            return $candidate;
        }
    }

    return null;
}

function list_get_student_lists_schema(mysqli $conn): ?array
{
    if (!faculty_table_exists($conn, 'student_lists')) {
        return null;
    }

    $listIdColumn = list_find_student_lists_column($conn, ['list_id', 'id']);
    $programIdColumn = list_find_student_lists_column($conn, ['program_id']);
    $batchNameColumn = list_find_student_lists_column($conn, ['batch_name', 'batch']);
    $yearColumn = list_find_student_lists_column($conn, ['year_of_enrollment', 'year_enrollment', 'enrollment_year']);
    $createdByColumn = list_find_student_lists_column($conn, ['created_by_user_id', 'created_by']);

    if (!$listIdColumn || !$programIdColumn || !$batchNameColumn || !$yearColumn) {
        return null;
    }

    return [
        'listId' => $listIdColumn,
        'programId' => $programIdColumn,
        'batchName' => $batchNameColumn,
        'year' => $yearColumn,
        'createdBy' => $createdByColumn,
    ];
}

function list_require_student_lists_schema(mysqli $conn): array
{
    $schema = list_get_student_lists_schema($conn);
    if ($schema === null) {
        faculty_send_json([
            'success' => false,
            'message' => 'Table student_lists is missing required columns. Apply the latest SQL migration.'
        ], 500);
    }

    return $schema;
}

function list_probe_student_lists_column(mysqli $conn, string $columnName): bool
{
    if ($columnName === '') {
        return false;
    }

    $sql = 'SELECT sl.' . $columnName . ' FROM student_lists sl LIMIT 1';

    try {
        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            return false;
        }
        $stmt->execute();
        $stmt->close();
        return true;
    } catch (Throwable $error) {
        return false;
    }
}

function list_resolve_student_lists_year_column(mysqli $conn, array $schema): string
{
    $candidates = array_values(array_unique(array_filter([
        (string) ($schema['year'] ?? ''),
        'year_of_enrollment',
        'year_enrollment',
        'enrollment_year'
    ])));

    foreach ($candidates as $candidate) {
        if (list_probe_student_lists_column($conn, $candidate)) {
            return $candidate;
        }
    }

    return (string) ($schema['year'] ?? 'year_of_enrollment');
}
