<?php

declare(strict_types=1);

require_once __DIR__ . '/program_access_common.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    send_program_json(['success' => false, 'message' => 'Method not allowed.'], 405);
}

$actingUser = require_executive_director($conn);
$actingUserId = (int) ($actingUser['user_id'] ?? 0);
$programId = isset($_POST['programId']) ? (int) $_POST['programId'] : 0;
$programDirectorUserId = isset($_POST['programDirectorUserId']) ? (int) $_POST['programDirectorUserId'] : 0;

if ($programId <= 0 || $programDirectorUserId <= 0) {
    send_program_json(['success' => false, 'message' => 'Invalid assignment payload.'], 400);
}

$programStmt = $conn->prepare('SELECT program_name FROM programs WHERE program_id = ? LIMIT 1');
if (!$programStmt) {
    send_program_json(['success' => false, 'message' => 'Failed to validate program.'], 500);
}
$programStmt->bind_param('i', $programId);
$programStmt->execute();
$programResult = $programStmt->get_result();
$programRow = $programResult ? $programResult->fetch_assoc() : null;
$programStmt->close();

if (!$programRow) {
    send_program_json(['success' => false, 'message' => 'Program not found.'], 404);
}

$facultyStmt = $conn->prepare(
    "SELECT u.user_id, u.status, u.is_verified, u.role_type, f.faculty_role,
            CONCAT(COALESCE(f.first_name, u.first_name), ' ', COALESCE(f.last_name, u.last_name)) AS full_name
     FROM users u
     INNER JOIN faculty f ON f.user_id = u.user_id
     WHERE u.user_id = ?
     LIMIT 1"
);
if (!$facultyStmt) {
    send_program_json(['success' => false, 'message' => 'Failed to validate faculty user.'], 500);
}
$facultyStmt->bind_param('i', $programDirectorUserId);
$facultyStmt->execute();
$facultyResult = $facultyStmt->get_result();
$facultyRow = $facultyResult ? $facultyResult->fetch_assoc() : null;
$facultyStmt->close();

if (!$facultyRow) {
    send_program_json(['success' => false, 'message' => 'Selected user is not a faculty account.'], 400);
}

if ((int) ($facultyRow['status'] ?? 0) !== 1 || (int) ($facultyRow['is_verified'] ?? 0) !== 1) {
    send_program_json(['success' => false, 'message' => 'Selected faculty user must be verified first.'], 400);
}

$currentAssignmentStmt = $conn->prepare(
    'SELECT program_id FROM program_director_assignments WHERE program_director_user_id = ? AND program_id <> ? LIMIT 1'
);
if (!$currentAssignmentStmt) {
    send_program_json(['success' => false, 'message' => 'Failed to validate existing Program Director assignments.'], 500);
}
$currentAssignmentStmt->bind_param('ii', $programDirectorUserId, $programId);
$currentAssignmentStmt->execute();
$currentAssignmentResult = $currentAssignmentStmt->get_result();
$existingAssignment = $currentAssignmentResult ? $currentAssignmentResult->fetch_assoc() : null;
$currentAssignmentStmt->close();

if ($existingAssignment) {
    send_program_json([
        'success' => false,
        'message' => 'This Program Director is already assigned to another program. One Program Director can only be assigned to one program.'
    ], 409);
}

$previousPdStmt = $conn->prepare('SELECT program_director_user_id FROM program_director_assignments WHERE program_id = ? LIMIT 1');
if (!$previousPdStmt) {
    send_program_json(['success' => false, 'message' => 'Failed to load current Program Director assignment.'], 500);
}
$previousPdStmt->bind_param('i', $programId);
$previousPdStmt->execute();
$previousPdResult = $previousPdStmt->get_result();
$previousPdRow = $previousPdResult ? $previousPdResult->fetch_assoc() : null;
$previousPdStmt->close();

$previousProgramDirectorUserId = (int) ($previousPdRow['program_director_user_id'] ?? 0);

try {
    $conn->begin_transaction();

    $upsert = $conn->prepare(
        'INSERT INTO program_director_assignments (program_id, program_director_user_id, assigned_by_user_id)
         VALUES (?, ?, ?)
         ON DUPLICATE KEY UPDATE program_director_user_id = VALUES(program_director_user_id), assigned_by_user_id = VALUES(assigned_by_user_id), assigned_at = CURRENT_TIMESTAMP'
    );
    if (!$upsert) {
        throw new RuntimeException('Failed to save assignment.');
    }

    $upsert->bind_param('iii', $programId, $programDirectorUserId, $actingUserId);
    $upsert->execute();
    $upsert->close();

    $newFacultyRole = 'program director';
    $roleStmt = $conn->prepare('UPDATE faculty SET faculty_role = ? WHERE user_id = ?');
    if (!$roleStmt) {
        throw new RuntimeException('Failed to update faculty role.');
    }
    $roleStmt->bind_param('si', $newFacultyRole, $programDirectorUserId);
    $roleStmt->execute();
    $roleStmt->close();

    if ($previousProgramDirectorUserId > 0 && $previousProgramDirectorUserId !== $programDirectorUserId) {
        $countStmt = $conn->prepare('SELECT COUNT(*) AS cnt FROM program_director_assignments WHERE program_director_user_id = ?');
        if (!$countStmt) {
            throw new RuntimeException('Failed to validate previous Program Director assignment count.');
        }

        $countStmt->bind_param('i', $previousProgramDirectorUserId);
        $countStmt->execute();
        $countResult = $countStmt->get_result();
        $countRow = $countResult ? $countResult->fetch_assoc() : null;
        $countStmt->close();

        $remainingAssignments = (int) ($countRow['cnt'] ?? 0);
        if ($remainingAssignments === 0) {
            $revertRole = 'professor';
            $revertStmt = $conn->prepare('UPDATE faculty SET faculty_role = ? WHERE user_id = ?');
            if (!$revertStmt) {
                throw new RuntimeException('Failed to revert previous Program Director role.');
            }
            $revertStmt->bind_param('si', $revertRole, $previousProgramDirectorUserId);
            $revertStmt->execute();
            $revertStmt->close();
        }
    }

    $conn->commit();
} catch (Throwable $error) {
    $conn->rollback();
    send_program_json(['success' => false, 'message' => 'Failed to assign Program Director.', 'error' => $error->getMessage()], 500);
}

send_program_json([
    'success' => true,
    'message' => 'Program Director assigned successfully.',
    'program' => (string) ($programRow['program_name'] ?? ''),
    'assignedTo' => (string) ($facultyRow['full_name'] ?? '')
]);
