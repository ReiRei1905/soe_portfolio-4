<?php

declare(strict_types=1);

require_once __DIR__ . '/admin_api_common.php';
require_once __DIR__ . '/../user_info_V3/notification_service.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    send_json([
        'success' => false,
        'message' => 'Method not allowed.'
    ], 405);
}

$userId = isset($_POST['user_id']) ? (int) $_POST['user_id'] : 0;
$action = strtolower(trim((string) ($_POST['action'] ?? '')));
$accessRole = trim((string) ($_POST['access_role'] ?? ''));

if ($userId <= 0 || !in_array($action, ['approve', 'reject'], true)) {
    send_json([
        'success' => false,
        'message' => 'Invalid request payload.'
    ], 400);
}

$accessRoleMap = [
    'admin' => ['roleType' => 'admin', 'facultyRole' => null],
    'student' => ['roleType' => 'student', 'facultyRole' => null],
    'executiveDirector' => ['roleType' => 'faculty', 'facultyRole' => 'executive director'],
    'programDirector' => ['roleType' => 'faculty', 'facultyRole' => 'program director'],
    'professor' => ['roleType' => 'faculty', 'facultyRole' => 'professor']
];

if ($action === 'approve' && !isset($accessRoleMap[$accessRole])) {
    send_json([
        'success' => false,
        'message' => 'Please select an access role before approval.'
    ], 400);
}

$status = $action === 'approve' ? 1 : 0;
$isVerified = $action === 'approve' ? 1 : 0;

$userStmt = $conn->prepare('SELECT user_id, first_name, last_name, email, password, role_type FROM users WHERE user_id = ? LIMIT 1');
if (!$userStmt) {
    send_json([
        'success' => false,
        'message' => 'Unable to load target user.',
        'error' => $conn->error
    ], 500);
}

$userStmt->bind_param('i', $userId);
$userStmt->execute();
$userResult = $userStmt->get_result();
$targetUser = $userResult ? $userResult->fetch_assoc() : null;
$userStmt->close();

if (!$targetUser) {
    send_json([
        'success' => false,
        'message' => 'User not found.'
    ], 404);
}

try {
    $conn->begin_transaction();

    if ($action === 'approve') {
        $targetRoleType = $accessRoleMap[$accessRole]['roleType'];
        $targetFacultyRole = $accessRoleMap[$accessRole]['facultyRole'];

        $roleStmt = $conn->prepare('UPDATE users SET role_type = ? WHERE user_id = ?');
        if (!$roleStmt) {
            throw new RuntimeException('Unable to update target role type.');
        }
        $roleStmt->bind_param('si', $targetRoleType, $userId);
        $roleStmt->execute();
        $roleStmt->close();

        if ($targetRoleType === 'faculty' && $targetFacultyRole !== null) {
            $facultyCheck = $conn->prepare('SELECT faculty_id FROM faculty WHERE user_id = ? LIMIT 1');
            if (!$facultyCheck) {
                throw new RuntimeException('Unable to validate faculty assignment.');
            }

            $facultyCheck->bind_param('i', $userId);
            $facultyCheck->execute();
            $facultyResult = $facultyCheck->get_result();
            $facultyRow = $facultyResult ? $facultyResult->fetch_assoc() : null;
            $facultyCheck->close();

            if ($facultyRow) {
                $facultyUpdate = $conn->prepare('UPDATE faculty SET faculty_role = ? WHERE user_id = ?');
                if (!$facultyUpdate) {
                    throw new RuntimeException('Unable to update faculty role.');
                }
                $facultyUpdate->bind_param('si', $targetFacultyRole, $userId);
                $facultyUpdate->execute();
                $facultyUpdate->close();
            } else {
                $firstName = (string) ($targetUser['first_name'] ?? '');
                $lastName = (string) ($targetUser['last_name'] ?? '');
                $email = (string) ($targetUser['email'] ?? '');
                $password = (string) ($targetUser['password'] ?? '');

                $facultyInsert = $conn->prepare(
                    'INSERT INTO faculty (user_id, first_name, middle_name, last_name, suffix, id_number, program_id, faculty_role, email, password) VALUES (?, ?, NULL, ?, NULL, NULL, NULL, ?, ?, ?)'
                );
                if (!$facultyInsert) {
                    throw new RuntimeException('Unable to create faculty profile for selected role.');
                }
                $facultyInsert->bind_param('issss', $userId, $firstName, $lastName, $targetFacultyRole, $email, $password);
                $facultyInsert->execute();
                $facultyInsert->close();
            }
        }
    }

    $statusStmt = $conn->prepare('UPDATE users SET status = ?, is_verified = ? WHERE user_id = ?');
    if (!$statusStmt) {
        throw new RuntimeException('Unable to update status.');
    }
    $statusStmt->bind_param('iii', $status, $isVerified, $userId);
    $statusStmt->execute();
    $statusStmt->close();

    $fullName = trim((string) ($targetUser['first_name'] ?? '') . ' ' . (string) ($targetUser['last_name'] ?? ''));
    if ($action === 'approve') {
        add_system_notification(
            $conn,
            $userId,
            "Hello {$fullName}, you have been approved by the owner of the system."
        );
    } else {
        add_system_notification(
            $conn,
            $userId,
            "Hello {$fullName}, your account is currently set to pending approval."
        );
    }

    $conn->commit();
} catch (Throwable $error) {
    $conn->rollback();

    send_json([
        'success' => false,
        'message' => 'Failed to update user approval.',
        'error' => $error->getMessage()
    ], 500);
}

$emailStmt = $conn->prepare('SELECT email, status, is_verified FROM users WHERE user_id = ? LIMIT 1');
if (!$emailStmt) {
    send_json([
        'success' => true,
        'message' => 'User status updated.',
        'status' => $status === 1 ? 'Verified' : 'Not Verified'
    ]);
}

$emailStmt->bind_param('i', $userId);
$emailStmt->execute();
$userResult = $emailStmt->get_result();
$userRow = $userResult ? $userResult->fetch_assoc() : null;
$emailStmt->close();

if (!$userRow) {
    send_json([
        'success' => true,
        'message' => 'User status updated.',
        'status' => $status === 1 ? 'Verified' : 'Not Verified'
    ]);
}

send_json([
    'success' => true,
    'message' => 'User approval updated successfully.',
    'status' => to_status_label((string) $userRow['email'], (int) $userRow['status'], (int) ($userRow['is_verified'] ?? 0))
]);
