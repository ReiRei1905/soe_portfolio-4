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

// Check for existing program with the same name (case-insensitive)
$checkStmt = $conn->prepare('SELECT 1 FROM programs WHERE LOWER(TRIM(program_name)) = LOWER(?) LIMIT 1');
if (!$checkStmt) {
    send_program_json(['success' => false, 'message' => 'Failed to prepare duplicate check.'], 500);
}
$checkStmt->bind_param('s', $programName);
$checkStmt->execute();
$exists = (bool) ($checkStmt->get_result()->fetch_assoc());
$checkStmt->close();

if ($exists) {
    send_program_json(['success' => false, 'message' => 'A program with this name already exists.'], 409);
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
