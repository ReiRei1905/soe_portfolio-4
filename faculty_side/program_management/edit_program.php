<?php

declare(strict_types=1);

require_once __DIR__ . '/program_access_common.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    send_program_json(['success' => false, 'message' => 'Method not allowed.'], 405);
}

require_executive_director($conn);

$programId = isset($_POST['programId']) ? (int) $_POST['programId'] : 0;
$newProgramName = trim((string) ($_POST['newProgramName'] ?? ''));

if ($programId <= 0 || $newProgramName === '') {
    send_program_json(['success' => false, 'message' => 'Invalid input.'], 400);
}

$stmt = $conn->prepare('UPDATE programs SET program_name = ? WHERE program_id = ?');
if (!$stmt) {
    send_program_json(['success' => false, 'message' => 'Failed to update program.'], 500);
}
$stmt->bind_param('si', $newProgramName, $programId);
$ok = $stmt->execute();
$stmt->close();

if (!$ok) {
    send_program_json(['success' => false, 'message' => 'Failed to update program.'], 500);
}

send_program_json(['success' => true, 'message' => 'Program updated successfully.']);
