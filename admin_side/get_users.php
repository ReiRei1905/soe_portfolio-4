<?php

declare(strict_types=1);

require_once __DIR__ . '/admin_api_common.php';

$filter = normalize_filter_value($_GET['filter'] ?? 'all');
$search = strtolower(trim((string) ($_GET['search'] ?? '')));

$sql = "
    SELECT
        u.user_id,
        u.first_name AS users_first_name,
        u.last_name AS users_last_name,
        u.email,
        u.role_type,
        u.status,
        u.is_verified,
        u.created_at,
        COALESCE(s.first_name, f.first_name, a.first_name, u.first_name) AS first_name,
        COALESCE(s.middle_name, f.middle_name, a.middle_name, '') AS middle_name,
        COALESCE(s.last_name, f.last_name, a.last_name, u.last_name) AS last_name,
        COALESCE(s.suffix, f.suffix, a.suffix, '') AS suffix,
        COALESCE(s.id_number, f.id_number, a.id_number, '') AS id_number,
        s.year_of_enrollment,
        COALESCE(ps.program_name, pf.program_name, 'N/A') AS program_name,
        f.faculty_role
    FROM users u
    LEFT JOIN students s ON s.user_id = u.user_id
    LEFT JOIN faculty f ON f.user_id = u.user_id
    LEFT JOIN admins a ON a.user_id = u.user_id
    LEFT JOIN programs ps ON ps.program_id = s.program_id
    LEFT JOIN programs pf ON pf.program_id = f.program_id
    ORDER BY u.created_at DESC, u.user_id DESC
";

$result = $conn->query($sql);
if (!$result) {
    send_json([
        'success' => false,
        'message' => 'Unable to fetch users.',
        'error' => $conn->error
    ], 500);
}

$users = [];
while ($row = $result->fetch_assoc()) {
    $normalizedRole = to_filter_role((string) $row['role_type'], $row['faculty_role'] ?? null);

    if ($filter !== 'all' && $normalizedRole !== $filter) {
        continue;
    }

    $firstName = trim((string) $row['first_name']);
    $middleName = trim((string) $row['middle_name']);
    $lastName = trim((string) $row['last_name']);
    $suffix = trim((string) $row['suffix']);
    $email = trim((string) $row['email']);
    $idNumber = trim((string) $row['id_number']);

    $searchTarget = strtolower(implode(' ', [
        $firstName,
        $middleName,
        $lastName,
        $suffix,
        $email,
        $idNumber,
        to_display_role((string) $row['role_type'], $row['faculty_role'] ?? null)
    ]));

    if ($search !== '' && !str_contains($searchTarget, $search)) {
        continue;
    }

    $users[] = [
        'id' => (int) $row['user_id'],
        'firstName' => $firstName,
        'middleInitial' => $middleName !== '' ? strtoupper(substr($middleName, 0, 1)) : '',
        'lastName' => $lastName,
        'suffix' => $suffix,
        'role' => to_display_role((string) $row['role_type'], $row['faculty_role'] ?? null),
        'roleFilterKey' => $normalizedRole,
        'status' => to_status_label($email, (int) $row['status'], (int) ($row['is_verified'] ?? 0)),
        'createdAccount' => format_signup_date($row['created_at'] ?? null)
    ];
}

send_json([
    'success' => true,
    'users' => $users
]);
