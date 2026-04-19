<?php

declare(strict_types=1);

require_once __DIR__ . '/program_access_common.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    send_program_json(['success' => false, 'message' => 'Method not allowed.'], 405);
}

require_executive_director($conn);

$programName = trim((string) ($_POST['programName'] ?? ''));
if ($programName === '') {
    send_program_json(['success' => false, 'message' => 'Invalid program name.'], 400);
}

$stmt = $conn->prepare('INSERT INTO programs (program_name) VALUES (?)');
if (!$stmt) {
    send_program_json(['success' => false, 'message' => 'Failed to create program.'], 500);
}

$stmt->bind_param('s', $programName);
$ok = $stmt->execute();
$stmt->close();

if (!$ok) {
    send_program_json(['success' => false, 'message' => 'Failed to create program.'], 500);
}

send_program_json(['success' => true, 'message' => 'Program created successfully.']);
