<?php

declare(strict_types=1);

require_once __DIR__ . '/admin_api_common.php';

$userId = isset($_GET['user_id']) ? (int) $_GET['user_id'] : 0;
if ($userId <= 0) {
    send_json([
        'success' => false,
        'message' => 'Invalid user id.'
    ], 400);
}

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
    WHERE u.user_id = ?
    LIMIT 1
";

$stmt = $conn->prepare($sql);
if (!$stmt) {
    send_json([
        'success' => false,
        'message' => 'Unable to prepare user details query.',
        'error' => $conn->error
    ], 500);
}

$stmt->bind_param('i', $userId);
$stmt->execute();
$result = $stmt->get_result();
$row = $result ? $result->fetch_assoc() : null;
$stmt->close();

if (!$row) {
    send_json([
        'success' => false,
        'message' => 'User not found.'
    ], 404);
}

$firstName = trim((string) $row['first_name']);
$middleName = trim((string) $row['middle_name']);
$lastName = trim((string) $row['last_name']);
$suffix = trim((string) $row['suffix']);
$idNumber = trim((string) $row['id_number']);
$email = trim((string) $row['email']);
$fullName = trim(implode(' ', array_filter([$firstName, $middleName, $lastName, $suffix])));
$yearEnrolled = $row['year_of_enrollment'] !== null
    ? (string) $row['year_of_enrollment']
    : (infer_year_from_id_number($idNumber) ?? 'N/A');

send_json([
    'success' => true,
    'user' => [
        'id' => (int) $row['user_id'],
        'fullName' => $fullName,
        'role' => to_display_role((string) $row['role_type'], $row['faculty_role'] ?? null),
        'accessRole' => to_access_role_key((string) $row['role_type'], $row['faculty_role'] ?? null),
        'email' => $email,
        'program' => trim((string) $row['program_name']) !== '' ? (string) $row['program_name'] : 'N/A',
        'yearEnroll' => $yearEnrolled,
        'idNumber' => $idNumber !== '' ? $idNumber : 'N/A',
        'signUpDate' => format_signup_date($row['created_at'] ?? null),
        'status' => to_status_label($email, (int) $row['status'], (int) ($row['is_verified'] ?? 0))
    ]
]);
