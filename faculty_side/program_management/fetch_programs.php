<?php

declare(strict_types=1);

require_once __DIR__ . '/../faculty_access_common.php';

faculty_require_verified_faculty($conn);

$query = "
    SELECT
        p.program_id AS id,
        p.program_name AS name,
        pda.program_director_user_id AS assignedProgramDirectorUserId,
        CONCAT(COALESCE(f.first_name, u.first_name), ' ', COALESCE(f.last_name, u.last_name)) AS assignedProgramDirectorName
    FROM programs p
    LEFT JOIN program_director_assignments pda ON pda.program_id = p.program_id
    LEFT JOIN users u ON u.user_id = pda.program_director_user_id
    LEFT JOIN faculty f ON f.user_id = u.user_id
    ORDER BY p.program_name ASC
";

$result = $conn->query($query);
if (!$result) {
    faculty_send_json(['success' => false, 'message' => 'Failed to fetch programs'], 500);
}

$programs = [];
while ($row = $result->fetch_assoc()) {
    $programs[] = [
        'id' => (int) ($row['id'] ?? 0),
        'name' => (string) ($row['name'] ?? ''),
        'assignedProgramDirectorUserId' => isset($row['assignedProgramDirectorUserId']) ? (int) $row['assignedProgramDirectorUserId'] : null,
        'assignedProgramDirectorName' => trim((string) ($row['assignedProgramDirectorName'] ?? '')),
    ];
}

header('Content-Type: application/json; charset=utf-8');
echo json_encode($programs);
