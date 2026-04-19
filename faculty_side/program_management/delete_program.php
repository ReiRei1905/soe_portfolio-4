<?php

declare(strict_types=1);

require_once __DIR__ . '/program_access_common.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    send_program_json(['success' => false, 'message' => 'Method not allowed.'], 405);
}

require_executive_director($conn);

$programId = isset($_POST['programId']) ? (int) $_POST['programId'] : 0;
if ($programId <= 0) {
    send_program_json(['success' => false, 'message' => 'Invalid program ID.'], 400);
}

$stmt = $conn->prepare('DELETE FROM programs WHERE program_id = ?');
if (!$stmt) {
    send_program_json(['success' => false, 'message' => 'Failed to delete program.'], 500);
}

$stmt->bind_param('i', $programId);
$ok = $stmt->execute();
$affected = $stmt->affected_rows;
$stmt->close();

if (!$ok || $affected <= 0) {
    send_program_json(['success' => false, 'message' => 'Failed to delete program.'], 500);
}

send_program_json(['success' => true, 'message' => 'Program deleted successfully.']);
