<?php

declare(strict_types=1);

require_once __DIR__ . '/../faculty_access_common.php';

$sessionUser = faculty_require_verified_faculty($conn);
$classId = isset($_GET['classId']) ? (int) $_GET['classId'] : 0;

if ($classId <= 0) {
    faculty_send_json(['success' => false, 'message' => 'Invalid classId.'], 400);
}

if (!faculty_can_manage_class($conn, $sessionUser, $classId)) {
    faculty_send_json(['success' => false, 'message' => 'You are not allowed to assign professors for this class.'], 403);
}

$stmt = $conn->prepare(
    "SELECT u.user_id,
            CONCAT(COALESCE(f.first_name, u.first_name), ' ', COALESCE(f.last_name, u.last_name)) AS full_name,
                        u.email,
                        COALESCE(f.faculty_role, '') AS faculty_role
     FROM users u
     INNER JOIN faculty f ON f.user_id = u.user_id
     WHERE u.role_type = 'faculty'
       AND u.status = 1
       AND u.is_verified = 1
     ORDER BY full_name ASC"
);

if (!$stmt) {
    faculty_send_json(['success' => false, 'message' => 'Failed to load professor list.'], 500);
}

$stmt->execute();
$result = $stmt->get_result();
$professors = [];
while ($row = $result->fetch_assoc()) {
    $professors[] = [
        'userId' => (int) ($row['user_id'] ?? 0),
        'fullName' => (string) ($row['full_name'] ?? ''),
        'email' => (string) ($row['email'] ?? ''),
        'facultyRole' => (string) ($row['faculty_role'] ?? '')
    ];
}
$stmt->close();

faculty_send_json(['success' => true, 'professors' => $professors]);
