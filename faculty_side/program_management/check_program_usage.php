<?php

declare(strict_types=1);

require_once __DIR__ . '/program_access_common.php';

require_executive_director($conn);

$programId = isset($_GET['programId']) ? (int) $_GET['programId'] : 0;

if ($programId <= 0) {
    send_program_json(['success' => false, 'message' => 'Invalid program ID'], 400);
}

$out = ['success' => true, 'courses' => 0, 'faculty' => 0];

$stmt = $conn->prepare('SELECT COUNT(*) AS cnt FROM courses WHERE program_id = ?');
if ($stmt) {
    $stmt->bind_param('i', $programId);
    $stmt->execute();
    $res = $stmt->get_result();
    $row = $res ? $res->fetch_assoc() : null;
    $out['courses'] = (int) ($row['cnt'] ?? 0);
    $stmt->close();
}

$stmt2 = $conn->prepare('SELECT COUNT(*) AS cnt FROM faculty WHERE program_id = ?');
if ($stmt2) {
    $stmt2->bind_param('i', $programId);
    $stmt2->execute();
    $res2 = $stmt2->get_result();
    $row2 = $res2 ? $res2->fetch_assoc() : null;
    $out['faculty'] = (int) ($row2['cnt'] ?? 0);
    $stmt2->close();
}

send_program_json($out);
