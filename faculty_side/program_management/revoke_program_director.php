<?php

declare(strict_types=1);

require_once __DIR__ . '/program_access_common.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    send_program_json(['success' => false, 'message' => 'Method not allowed.'], 405);
}

require_executive_director($conn);

$programId = isset($_POST['programId']) ? (int) $_POST['programId'] : 0;
if ($programId <= 0) {
    send_program_json(['success' => false, 'message' => 'Invalid program id.'], 400);
}

$programStmt = $conn->prepare('SELECT program_name FROM programs WHERE program_id = ? LIMIT 1');
if (!$programStmt) {
    send_program_json(['success' => false, 'message' => 'Failed to validate program.'], 500);
}
$programStmt->bind_param('i', $programId);
$programStmt->execute();
$programRes = $programStmt->get_result();
$programRow = $programRes ? $programRes->fetch_assoc() : null;
$programStmt->close();

if (!$programRow) {
    send_program_json(['success' => false, 'message' => 'Program not found.'], 404);
}

$currentStmt = $conn->prepare('SELECT program_director_user_id FROM program_director_assignments WHERE program_id = ? LIMIT 1');
if (!$currentStmt) {
    send_program_json(['success' => false, 'message' => 'Failed to load assignment.'], 500);
}
$currentStmt->bind_param('i', $programId);
$currentStmt->execute();
$currentRes = $currentStmt->get_result();
$currentRow = $currentRes ? $currentRes->fetch_assoc() : null;
$currentStmt->close();

$currentDirectorUserId = (int) ($currentRow['program_director_user_id'] ?? 0);
if ($currentDirectorUserId <= 0) {
    send_program_json(['success' => true, 'message' => 'No Program Director is currently assigned to this program.']);
}

try {
    $conn->begin_transaction();

    $deleteStmt = $conn->prepare('DELETE FROM program_director_assignments WHERE program_id = ?');
    if (!$deleteStmt) {
        throw new RuntimeException('Failed to prepare revoke statement.');
    }
    $deleteStmt->bind_param('i', $programId);
    $deleteStmt->execute();
    $deleteStmt->close();

    $remainingStmt = $conn->prepare('SELECT COUNT(*) AS cnt FROM program_director_assignments WHERE program_director_user_id = ?');
    if (!$remainingStmt) {
        throw new RuntimeException('Failed to validate remaining assignments.');
    }
    $remainingStmt->bind_param('i', $currentDirectorUserId);
    $remainingStmt->execute();
    $remainingRes = $remainingStmt->get_result();
    $remainingRow = $remainingRes ? $remainingRes->fetch_assoc() : null;
    $remainingStmt->close();

    if ((int) ($remainingRow['cnt'] ?? 0) === 0) {
        $newRole = 'professor';
        $roleStmt = $conn->prepare('UPDATE faculty SET faculty_role = ? WHERE user_id = ?');
        if (!$roleStmt) {
            throw new RuntimeException('Failed to reset faculty role after revoking assignment.');
        }
        $roleStmt->bind_param('si', $newRole, $currentDirectorUserId);
        $roleStmt->execute();
        $roleStmt->close();
    }

    $conn->commit();
} catch (Throwable $error) {
    $conn->rollback();
    send_program_json([
        'success' => false,
        'message' => 'Failed to revoke Program Director assignment.',
        'error' => $error->getMessage()
    ], 500);
}

send_program_json([
    'success' => true,
    'message' => 'Program Director assignment revoked successfully.',
    'program' => (string) ($programRow['program_name'] ?? '')
]);
