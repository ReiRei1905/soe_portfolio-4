<?php

declare(strict_types=1);

require_once __DIR__ . '/program_access_common.php';

require_executive_director($conn);

$query = "
    SELECT
        u.user_id,
        CONCAT(COALESCE(f.first_name, u.first_name), ' ', COALESCE(f.last_name, u.last_name)) AS full_name,
        COALESCE(f.faculty_role, '') AS faculty_role,
        u.email
    FROM users u
    INNER JOIN faculty f ON f.user_id = u.user_id
    WHERE u.role_type = 'faculty'
      AND u.status = 1
      AND u.is_verified = 1
    ORDER BY full_name ASC
";

$result = $conn->query($query);
if (!$result) {
    send_program_json(['success' => false, 'message' => 'Failed to load faculty users.'], 500);
}

$facultyUsers = [];
while ($row = $result->fetch_assoc()) {
    $facultyUsers[] = [
        'userId' => (int) ($row['user_id'] ?? 0),
        'fullName' => (string) ($row['full_name'] ?? ''),
        'facultyRole' => (string) ($row['faculty_role'] ?? ''),
        'email' => (string) ($row['email'] ?? '')
    ];
}

send_program_json([
    'success' => true,
    'facultyUsers' => $facultyUsers
]);
