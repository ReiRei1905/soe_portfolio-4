<?php

declare(strict_types=1);

require_once __DIR__ . '/admin_api_common.php';

$sql = "
    SELECT DISTINCT p.program_name 
    FROM programs p
    JOIN (
        SELECT program_id FROM students WHERE program_id IS NOT NULL
        UNION
        SELECT program_id FROM faculty WHERE program_id IS NOT NULL
    ) u ON u.program_id = p.program_id
    ORDER BY p.program_name ASC
";
$result = $conn->query($sql);

if (!$result) {
    send_json([
        'success' => false,
        'message' => 'Unable to fetch programs.',
        'error' => $conn->error
    ], 500);
}

$programs = [];
while ($row = $result->fetch_assoc()) {
    $programs[] = $row['program_name'];
}

send_json([
    'success' => true,
    'programs' => $programs
]);
