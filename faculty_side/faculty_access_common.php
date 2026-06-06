<?php

declare(strict_types=1);

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

require_once __DIR__ . '/../user_info_V3/connect.php';

if (!isset($conn) || !($conn instanceof mysqli)) {
    http_response_code(500);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['success' => false, 'message' => 'Database connection is unavailable.']);
    exit;
}

$conn->set_charset('utf8mb4');

function faculty_send_json(array $payload, int $statusCode = 200): void
{
    http_response_code($statusCode);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($payload);
    exit;
}

function faculty_is_local_owner_mode(): bool
{
    $host = strtolower((string) ($_SERVER['HTTP_HOST'] ?? ''));
    $hostOnly = explode(':', $host)[0] ?? '';
    $remoteAddr = (string) ($_SERVER['REMOTE_ADDR'] ?? '');

    return in_array($hostOnly, ['localhost', '127.0.0.1'], true)
        || in_array($remoteAddr, ['127.0.0.1', '::1'], true);
}

function faculty_resolve_local_owner_user(mysqli $conn): ?array
{
    $preferredEmail = trim((string) ($_COOKIE['local_owner_email'] ?? ''));
    if ($preferredEmail !== '' && filter_var($preferredEmail, FILTER_VALIDATE_EMAIL)) {
        $preferredStmt = $conn->prepare(
            'SELECT u.user_id, u.email, u.role_type, u.status, u.is_verified, f.faculty_role
             FROM users u
             INNER JOIN faculty f ON f.user_id = u.user_id
             WHERE u.email = ?
               AND u.role_type = "faculty"
               AND u.status = 1
               AND u.is_verified = 1
             LIMIT 1'
        );
        if ($preferredStmt) {
            $preferredStmt->bind_param('s', $preferredEmail);
            $preferredStmt->execute();
            $preferredResult = $preferredStmt->get_result();
            $preferredRow = $preferredResult ? $preferredResult->fetch_assoc() : null;
            $preferredStmt->close();
            if ($preferredRow) {
                return $preferredRow;
            }
        }
    }

    $sql =
        'SELECT u.user_id, u.email, u.role_type, u.status, u.is_verified, f.faculty_role
         FROM users u
         INNER JOIN faculty f ON f.user_id = u.user_id
         WHERE u.role_type = "faculty"
           AND u.status = 1
           AND u.is_verified = 1
         ORDER BY CASE
             WHEN LOWER(TRIM(f.faculty_role)) = "executive director" THEN 1
             WHEN LOWER(TRIM(f.faculty_role)) = "program director" THEN 2
             WHEN LOWER(TRIM(f.faculty_role)) = "professor" THEN 3
             ELSE 4
         END,
         u.user_id ASC
         LIMIT 1';

    $result = $conn->query($sql);
    if (!$result) {
        return null;
    }

    $row = $result->fetch_assoc();
    return $row ?: null;
}

function faculty_get_session_user(mysqli $conn): ?array
{
    $email = trim((string) ($_SESSION['email'] ?? ''));
    if ($email === '') {
        if (!faculty_is_local_owner_mode()) {
            return null;
        }

        $localOwnerUser = faculty_resolve_local_owner_user($conn);
        if (!$localOwnerUser) {
            return null;
        }

        $_SESSION['user_id'] = (int) ($localOwnerUser['user_id'] ?? 0);
        $_SESSION['role_type'] = (string) ($localOwnerUser['role_type'] ?? 'faculty');
        $_SESSION['is_verified'] = (int) ($localOwnerUser['is_verified'] ?? 1);

        return $localOwnerUser;
    }

    $stmt = $conn->prepare(
        'SELECT u.user_id, u.email, u.role_type, u.status, u.is_verified, f.faculty_role
         FROM users u
         LEFT JOIN faculty f ON f.user_id = u.user_id
         WHERE u.email = ?
         LIMIT 1'
    );

    if (!$stmt) {
        return null;
    }

    $stmt->bind_param('s', $email);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result ? $result->fetch_assoc() : null;
    $stmt->close();

    if ($row) {
        $_SESSION['user_id'] = (int) ($row['user_id'] ?? 0);
    }

    return $row ?: null;
}

function faculty_is_verified_faculty(array $user): bool
{
    return strtolower((string) ($user['role_type'] ?? '')) === 'faculty'
        && (int) ($user['status'] ?? 0) === 1
        && (int) ($user['is_verified'] ?? 0) === 1;
}

function faculty_normalize_role_label(?string $value): string
{
    $normalized = strtolower(trim((string) $value));
    $normalized = str_replace(['_', '-'], ' ', $normalized);
    $normalized = preg_replace('/\s+/', ' ', $normalized) ?? $normalized;
    return trim($normalized);
}

function faculty_is_executive_director(array $user): bool
{
    $role = faculty_normalize_role_label((string) ($user['faculty_role'] ?? ''));
    return faculty_is_verified_faculty($user)
        && str_contains($role, 'executive director');
}

function faculty_is_program_director(array $user): bool
{
    $role = faculty_normalize_role_label((string) ($user['faculty_role'] ?? ''));
    return faculty_is_verified_faculty($user)
    && str_contains($role, 'program director');
}

function faculty_require_verified_faculty(mysqli $conn): array
{
    $sessionUser = faculty_get_session_user($conn);
    if (!$sessionUser) {
        faculty_send_json(['success' => false, 'message' => 'Unauthorized. Please log in.'], 401);
    }

    if (!faculty_is_verified_faculty($sessionUser)) {
        faculty_send_json(['success' => false, 'message' => 'Only verified faculty users can access this endpoint.'], 403);
    }

    return $sessionUser;
}

function faculty_table_exists(mysqli $conn, string $tableName): bool
{
    static $cache = [];

    if (array_key_exists($tableName, $cache)) {
        return $cache[$tableName];
    }

    $stmt = $conn->prepare('SELECT 1 FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = ? LIMIT 1');
    if (!$stmt) {
        $cache[$tableName] = false;
        return false;
    }

    $stmt->bind_param('s', $tableName);
    $stmt->execute();
    $result = $stmt->get_result();
    $exists = (bool) ($result && $result->fetch_assoc());
    $stmt->close();

    $cache[$tableName] = $exists;
    return $exists;
}

function faculty_is_program_assigned_to_director(mysqli $conn, int $userId, int $programId): bool
{
    if ($userId <= 0 || $programId <= 0) {
        return false;
    }

    if (!faculty_table_exists($conn, 'program_director_assignments')) {
        return false;
    }

    $stmt = $conn->prepare('SELECT 1 FROM program_director_assignments WHERE program_id = ? AND program_director_user_id = ? LIMIT 1');
    if (!$stmt) {
        return false;
    }

    $stmt->bind_param('ii', $programId, $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    $found = (bool) ($result && $result->fetch_assoc());
    $stmt->close();

    return $found;
}

function faculty_can_manage_program(mysqli $conn, array $user, int $programId): bool
{
    if ($programId <= 0) {
        return false;
    }

    // Even if the user is an Executive Director, they must be explicitly
    // assigned as the Program Director for this specific program to manage its contents (courses, etc.)
    return faculty_is_program_assigned_to_director($conn, (int) ($user['user_id'] ?? 0), $programId);
}

function faculty_get_related_course_ids(mysqli $conn, int $courseId): array
{
    if ($courseId <= 0) {
        return [];
    }

    $baseStmt = $conn->prepare('SELECT course_name, course_code FROM courses WHERE course_id = ? LIMIT 1');
    if (!$baseStmt) {
        return [$courseId];
    }

    $baseStmt->bind_param('i', $courseId);
    $baseStmt->execute();
    $baseRes = $baseStmt->get_result();
    $baseRow = $baseRes ? $baseRes->fetch_assoc() : null;
    $baseStmt->close();

    if (!$baseRow) {
        return [$courseId];
    }

    $courseCode = strtoupper(trim((string) ($baseRow['course_code'] ?? '')));
    $courseName = trim((string) ($baseRow['course_name'] ?? ''));

    $relatedIds = [$courseId];

    if ($courseCode !== '') {
        $relStmt = $conn->prepare('SELECT course_id FROM courses WHERE UPPER(TRIM(COALESCE(course_code, ""))) = ?');
        if ($relStmt) {
            $relStmt->bind_param('s', $courseCode);
            $relStmt->execute();
            $relRes = $relStmt->get_result();
            while ($relRes && ($relRow = $relRes->fetch_assoc())) {
                $relatedIds[] = (int) ($relRow['course_id'] ?? 0);
            }
            $relStmt->close();
        }
    } elseif ($courseName !== '') {
        $relStmt = $conn->prepare(
            'SELECT course_id
             FROM courses
             WHERE LOWER(TRIM(course_name)) = LOWER(TRIM(?))
               AND COALESCE(TRIM(course_code), "") = ""'
        );
        if ($relStmt) {
            $relStmt->bind_param('s', $courseName);
            $relStmt->execute();
            $relRes = $relStmt->get_result();
            while ($relRes && ($relRow = $relRes->fetch_assoc())) {
                $relatedIds[] = (int) ($relRow['course_id'] ?? 0);
            }
            $relStmt->close();
        }
    }

    $relatedIds = array_values(array_unique(array_filter($relatedIds, static fn($id) => (int) $id > 0)));
    sort($relatedIds);

    return $relatedIds;
}

function faculty_get_program_ids_by_course(mysqli $conn, int $courseId): array
{
    if ($courseId <= 0) {
        return [];
    }

    $courseIds = faculty_get_related_course_ids($conn, $courseId);
    if (empty($courseIds)) {
        return [];
    }

    $programIds = [];

    if (faculty_table_exists($conn, 'program_course_links')) {
        $placeholders = implode(',', array_fill(0, count($courseIds), '?'));
        $types = str_repeat('i', count($courseIds));
        $sql = "SELECT DISTINCT program_id FROM program_course_links WHERE course_id IN ({$placeholders})";
        $stmt = $conn->prepare($sql);
        if ($stmt) {
            $bindArgs = [$types];
            foreach ($courseIds as $idx => $value) {
                $bindArgs[] = &$courseIds[$idx];
            }
            call_user_func_array([$stmt, 'bind_param'], $bindArgs);
            $stmt->execute();
            $result = $stmt->get_result();
            while ($result && ($row = $result->fetch_assoc())) {
                $programIds[] = (int) ($row['program_id'] ?? 0);
            }
            $stmt->close();
        }
    }

    if (empty($programIds)) {
        $placeholders = implode(',', array_fill(0, count($courseIds), '?'));
        $types = str_repeat('i', count($courseIds));
        $sql = "SELECT DISTINCT program_id FROM courses WHERE course_id IN ({$placeholders})";
        $stmt = $conn->prepare($sql);
        if ($stmt) {
            $bindArgs = [$types];
            foreach ($courseIds as $idx => $value) {
                $bindArgs[] = &$courseIds[$idx];
            }
            call_user_func_array([$stmt, 'bind_param'], $bindArgs);
            $stmt->execute();
            $result = $stmt->get_result();
            while ($result && ($row = $result->fetch_assoc())) {
                $programIds[] = (int) ($row['program_id'] ?? 0);
            }
            $stmt->close();
        }
    }

    $programIds = array_values(array_unique(array_filter($programIds, static fn($id) => (int) $id > 0)));
    sort($programIds);

    return $programIds;
}

function faculty_get_program_id_by_course(mysqli $conn, int $courseId): ?int
{
    $programIds = faculty_get_program_ids_by_course($conn, $courseId);
    if (empty($programIds)) {
        return null;
    }

    return (int) $programIds[0];
}

function faculty_get_program_id_by_class(mysqli $conn, int $classId): ?int
{
    if ($classId <= 0) {
        return null;
    }

    $stmt = $conn->prepare(
        'SELECT co.program_id
         FROM classes c
         INNER JOIN courses co ON co.course_id = c.course_id
         WHERE c.class_id = ?
         LIMIT 1'
    );

    if (!$stmt) {
        return null;
    }

    $stmt->bind_param('i', $classId);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result ? $result->fetch_assoc() : null;
    $stmt->close();

    if (!$row || !isset($row['program_id'])) {
        return null;
    }

    return (int) $row['program_id'];
}

function faculty_get_program_id_by_output(mysqli $conn, int $outputId): ?int
{
    if ($outputId <= 0) {
        return null;
    }

    $stmt = $conn->prepare(
        'SELECT co.program_id
         FROM class_outputs o
         INNER JOIN classes c ON c.class_id = o.class_id
         INNER JOIN courses co ON co.course_id = c.course_id
         WHERE o.output_id = ?
         LIMIT 1'
    );

    if (!$stmt) {
        return null;
    }

    $stmt->bind_param('i', $outputId);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result ? $result->fetch_assoc() : null;
    $stmt->close();

    if (!$row || !isset($row['program_id'])) {
        return null;
    }

    return (int) $row['program_id'];
}

function faculty_get_program_id_by_requirement(mysqli $conn, int $requirementId): ?int
{
    if ($requirementId <= 0) {
        return null;
    }

    $stmt = $conn->prepare(
        'SELECT co.program_id
         FROM requirements r
         INNER JOIN classes c ON c.class_id = r.class_id
         INNER JOIN courses co ON co.course_id = c.course_id
         WHERE r.requirement_id = ?
         LIMIT 1'
    );

    if (!$stmt) {
        return null;
    }

    $stmt->bind_param('i', $requirementId);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result ? $result->fetch_assoc() : null;
    $stmt->close();

    if (!$row || !isset($row['program_id'])) {
        return null;
    }

    return (int) $row['program_id'];
}

function faculty_can_manage_course(mysqli $conn, array $user, int $courseId): bool
{
    if ($courseId <= 0) {
        return false;
    }

    $programIds = faculty_get_program_ids_by_course($conn, $courseId);
    if (empty($programIds)) {
        return false;
    }

    foreach ($programIds as $programId) {
        if (faculty_can_manage_program($conn, $user, $programId)) {
            return true;
        }
    }

    return false;
}

function faculty_can_manage_class(mysqli $conn, array $user, int $classId): bool
{
    if ($classId <= 0) {
        return false;
    }

    $stmt = $conn->prepare('SELECT course_id FROM classes WHERE class_id = ? LIMIT 1');
    if (!$stmt) {
        return false;
    }

    $stmt->bind_param('i', $classId);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result ? $result->fetch_assoc() : null;
    $stmt->close();

    $courseId = (int) ($row['course_id'] ?? 0);
    if ($courseId <= 0) {
        return false;
    }

    return faculty_can_manage_course($conn, $user, $courseId);
}

function faculty_is_assigned_professor(mysqli $conn, int $classId, int $userId): bool
{
    if ($classId <= 0 || $userId <= 0) {
        return false;
    }

    if (!faculty_table_exists($conn, 'class_professor_assignments')) {
        return false;
    }

    $stmt = $conn->prepare('SELECT 1 FROM class_professor_assignments WHERE class_id = ? AND professor_user_id = ? LIMIT 1');
    if (!$stmt) {
        return false;
    }

    $stmt->bind_param('ii', $classId, $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    $found = (bool) ($result && $result->fetch_assoc());
    $stmt->close();

    return $found;
}

function faculty_can_handle_class(mysqli $conn, array $user, int $classId): bool
{
    if ($classId <= 0) {
        return false;
    }

    if (faculty_can_manage_class($conn, $user, $classId)) {
        return true;
    }

    $role = faculty_normalize_role_label((string) ($user['faculty_role'] ?? ''));
    if (!str_contains($role, 'professor')) {
        return false;
    }

    return faculty_is_assigned_professor($conn, $classId, (int) ($user['user_id'] ?? 0));
}

function faculty_is_active_professor(array $user): bool
{
    if (!faculty_is_verified_faculty($user)) {
        return false;
    }

    $role = faculty_normalize_role_label((string) ($user['faculty_role'] ?? ''));
    return str_contains($role, 'professor')
        && !str_contains($role, 'program director')
        && !str_contains($role, 'executive director');
}

function faculty_can_review_class_request(mysqli $conn, array $user, int $programId): bool
{
    if ($programId <= 0) {
        return false;
    }

    if (faculty_is_executive_director($user)) {
        return true;
    }

    if (!faculty_is_program_director($user)) {
        return false;
    }

    return faculty_is_program_assigned_to_director($conn, (int) ($user['user_id'] ?? 0), $programId);
}

function faculty_can_review_class_request_by_course(mysqli $conn, array $user, int $courseId): bool
{
    if ($courseId <= 0) {
        return false;
    }

    if (faculty_is_executive_director($user)) {
        return true;
    }

    if (!faculty_is_program_director($user)) {
        return false;
    }

    $programIds = faculty_get_program_ids_by_course($conn, $courseId);
    foreach ($programIds as $programId) {
        if (faculty_is_program_assigned_to_director($conn, (int) ($user['user_id'] ?? 0), (int) $programId)) {
            return true;
        }
    }

    return false;
}

function faculty_get_program_director_names_by_course(mysqli $conn, int $courseId): array
{
    if ($courseId <= 0 || !faculty_table_exists($conn, 'program_director_assignments')) {
        return [];
    }

    $programIds = faculty_get_program_ids_by_course($conn, $courseId);
    if (empty($programIds)) {
        return [];
    }

    $placeholders = implode(',', array_fill(0, count($programIds), '?'));
    $types = str_repeat('i', count($programIds));

    $sql =
        'SELECT DISTINCT
            CONCAT(COALESCE(f.first_name, u.first_name), " ", COALESCE(f.last_name, u.last_name)) AS full_name
         FROM program_director_assignments pda
         LEFT JOIN users u ON u.user_id = pda.program_director_user_id
         LEFT JOIN faculty f ON f.user_id = u.user_id
         WHERE pda.program_id IN (' . $placeholders . ')';

    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        return [];
    }

    $bindArgs = [$types];
    foreach ($programIds as $idx => $value) {
        $bindArgs[] = &$programIds[$idx];
    }

    call_user_func_array([$stmt, 'bind_param'], $bindArgs);
    $stmt->execute();
    $result = $stmt->get_result();

    $names = [];
    while ($result && ($row = $result->fetch_assoc())) {
        $fullName = trim((string) ($row['full_name'] ?? ''));
        if ($fullName !== '') {
            $names[] = $fullName;
        }
    }

    $stmt->close();

    $names = array_values(array_unique($names));
    sort($names, SORT_NATURAL | SORT_FLAG_CASE);

    return $names;
}

function faculty_format_name_list(array $names): string
{
    $names = array_values(array_filter(array_map(static fn($name) => trim((string) $name), $names), static fn($name) => $name !== ''));
    $count = count($names);

    if ($count === 0) {
        return '';
    }

    if ($count === 1) {
        return $names[0];
    }

    if ($count === 2) {
        return $names[0] . ' and ' . $names[1];
    }

    $lastName = array_pop($names);
    return implode(', ', $names) . ', and ' . $lastName;
}
