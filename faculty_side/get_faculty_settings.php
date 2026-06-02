<?php
declare(strict_types=1);
require_once __DIR__ . '/faculty_access_common.php';

$sessionUser = faculty_require_verified_faculty($conn);
$userId = (int) ($sessionUser['user_id'] ?? 0);

$query = "SELECT u.first_name, u.last_name,
                 COALESCE(f.email, u.email) AS email,
                 f.id_number,
                 u.profile_picture,
                 f.faculty_role,
                 p.program_name
          FROM users u
          INNER JOIN faculty f ON u.user_id = f.user_id
          LEFT JOIN programs p ON f.program_id = p.program_id
          WHERE u.user_id = ?";

$stmt = $conn->prepare($query);
$stmt->bind_param('i', $userId);
$stmt->execute();
$result = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$result) {
    faculty_send_json(['success' => false, 'message' => 'User not found'], 404);
}

faculty_send_json(['success' => true, 'data' => $result]);