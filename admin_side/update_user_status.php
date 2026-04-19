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

if ($userId <= 0 || !in_array($action, ['approve', 'reject', 'revoke'], true)) {
    send_json([
        'success' => false,
        'message' => 'Invalid request payload.'
    ], 400);
}

function notify_all_admins(mysqli $conn, string $message, int $excludeUserId = 0): void
{
    if (trim($message) === '') {
        return;
    }

    $adminStmt = $conn->prepare('SELECT user_id FROM users WHERE role_type = ?');
    if (!$adminStmt) {
        return;
    }

    $adminRole = 'admin';
    $adminStmt->bind_param('s', $adminRole);
    $adminStmt->execute();
    $result = $adminStmt->get_result();
    $adminIds = [];
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $adminId = (int) ($row['user_id'] ?? 0);
            if ($adminId > 0 && $adminId !== $excludeUserId) {
                $adminIds[] = $adminId;
            }
        }
    }
    $adminStmt->close();

    if (empty($adminIds)) {
        return;
    }

    foreach ($adminIds as $adminId) {
        add_system_notification($conn, $adminId, $message);
    }
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

$isApprovalAction = $action === 'approve';
$status = $isApprovalAction ? 1 : 0;
$isVerified = $isApprovalAction ? 1 : 0;
$emailNotificationSent = false;
$deferredEmailPayload = null;

function send_json_and_continue(array $payload, int $statusCode = 200): void
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        session_write_close();
    }

    ignore_user_abort(true);

    $json = json_encode($payload);
    if ($json === false) {
        $json = '{"success":false,"message":"Failed to encode response payload."}';
        $statusCode = 500;
    }

    http_response_code($statusCode);
    header('Content-Type: application/json; charset=utf-8');
    header('Connection: close');
    header('Content-Length: ' . strlen($json));
    echo $json;

    while (ob_get_level() > 0) {
        @ob_end_flush();
    }
    flush();

    if (function_exists('fastcgi_finish_request')) {
        fastcgi_finish_request();
    }
}

$userStmt = $conn->prepare('SELECT u.user_id, u.first_name, u.last_name, u.email, u.password, u.role_type, u.status, u.is_verified, f.faculty_role, f.id_number AS faculty_id_number, f.program_id AS faculty_program_id, s.id_number AS student_id_number, s.year_of_enrollment AS student_year_of_enrollment, s.program_id AS student_program_id, a.id_number AS admin_id_number FROM users u LEFT JOIN faculty f ON f.user_id = u.user_id LEFT JOIN students s ON s.user_id = u.user_id LEFT JOIN admins a ON a.user_id = u.user_id WHERE u.user_id = ? LIMIT 1');
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

$fullName = trim((string) ($targetUser['first_name'] ?? '') . ' ' . (string) ($targetUser['last_name'] ?? ''));
$currentStatusLabel = to_status_label(
    (string) ($targetUser['email'] ?? ''),
    (int) ($targetUser['status'] ?? 0),
    (int) ($targetUser['is_verified'] ?? 0)
);

if ($action === 'approve' && $currentStatusLabel === 'Verified') {
    send_json([
        'success' => false,
        'message' => "The {$fullName} has already been approved.",
        'currentStatus' => $currentStatusLabel
    ], 409);
}

if ($action === 'reject' && $currentStatusLabel === 'Not Verified') {
    send_json([
        'success' => false,
        'message' => "The {$fullName} is already set to Not Verified.",
        'currentStatus' => $currentStatusLabel
    ], 409);
}

if ($action === 'revoke' && $currentStatusLabel === 'Not Verified') {
    send_json([
        'success' => false,
        'message' => "The {$fullName}'s access has already been revoked.",
        'currentStatus' => $currentStatusLabel
    ], 409);
}

$currentRoleType = strtolower(trim((string) ($targetUser['role_type'] ?? '')));
$currentFacultyRole = strtolower(trim((string) ($targetUser['faculty_role'] ?? '')));
$currentRoleLabel = to_display_role($currentRoleType, $currentFacultyRole !== '' ? $currentFacultyRole : null);

if ($action === 'revoke' && $currentStatusLabel !== 'Verified') {
    send_json([
        'success' => false,
        'message' => 'Revoke Access is only available for verified accounts.'
    ], 400);
}

try {
    $conn->begin_transaction();

    if ($action === 'approve') {
        $targetRoleType = $accessRoleMap[$accessRole]['roleType'];
        $targetFacultyRole = $accessRoleMap[$accessRole]['facultyRole'];

        $firstName = (string) ($targetUser['first_name'] ?? '');
        $lastName = (string) ($targetUser['last_name'] ?? '');
        $email = (string) ($targetUser['email'] ?? '');
        $password = (string) ($targetUser['password'] ?? '');
        $middleName = '';
        $suffix = '';
        $resolvedIdNumber = trim((string) ($targetUser['admin_id_number'] ?? ''));
        if ($resolvedIdNumber === '') {
            $resolvedIdNumber = trim((string) ($targetUser['faculty_id_number'] ?? ''));
        }
        if ($resolvedIdNumber === '') {
            $resolvedIdNumber = trim((string) ($targetUser['student_id_number'] ?? ''));
        }
        $resolvedProgramId = $targetUser['faculty_program_id'] ?? null;
        if ($resolvedProgramId === null) {
            $resolvedProgramId = $targetUser['student_program_id'] ?? null;
        }
        $resolvedProgramId = $resolvedProgramId !== null ? (int) $resolvedProgramId : null;
        $resolvedYearOfEnrollment = $targetUser['student_year_of_enrollment'] ?? null;
        if ($resolvedYearOfEnrollment === null) {
            $inferredYear = infer_year_from_id_number($resolvedIdNumber);
            $resolvedYearOfEnrollment = $inferredYear !== null ? (int) $inferredYear : null;
        } else {
            $resolvedYearOfEnrollment = (int) $resolvedYearOfEnrollment;
        }

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
                $facultyUpdate = $conn->prepare('UPDATE faculty SET first_name = ?, middle_name = ?, last_name = ?, suffix = ?, id_number = NULLIF(?,\'\'), program_id = ?, faculty_role = ?, email = ?, password = ? WHERE user_id = ?');
                if (!$facultyUpdate) {
                    throw new RuntimeException('Unable to update faculty role.');
                }
                $facultyUpdate->bind_param('sssssisssi', $firstName, $middleName, $lastName, $suffix, $resolvedIdNumber, $resolvedProgramId, $targetFacultyRole, $email, $password, $userId);
                $facultyUpdate->execute();
                $facultyUpdate->close();
            } else {
                $facultyInsert = $conn->prepare(
                    'INSERT INTO faculty (user_id, first_name, middle_name, last_name, suffix, id_number, program_id, faculty_role, email, password) VALUES (?, ?, ?, ?, ?, NULLIF(?,\'\'), ?, ?, ?, ?)'
                );
                if (!$facultyInsert) {
                    throw new RuntimeException('Unable to create faculty profile for selected role.');
                }
                $facultyInsert->bind_param('isssssisss', $userId, $firstName, $middleName, $lastName, $suffix, $resolvedIdNumber, $resolvedProgramId, $targetFacultyRole, $email, $password);
                $facultyInsert->execute();
                $facultyInsert->close();
            }
        } elseif ($targetRoleType === 'admin') {
            $adminCheck = $conn->prepare('SELECT admin_id FROM admins WHERE user_id = ? LIMIT 1');
            if (!$adminCheck) {
                throw new RuntimeException('Unable to validate admin profile assignment.');
            }

            $adminCheck->bind_param('i', $userId);
            $adminCheck->execute();
            $adminResult = $adminCheck->get_result();
            $adminRow = $adminResult ? $adminResult->fetch_assoc() : null;
            $adminCheck->close();

            if ($adminRow) {
                $adminUpdate = $conn->prepare('UPDATE admins SET first_name = ?, middle_name = ?, last_name = ?, suffix = ?, id_number = ?, email = ?, password = ? WHERE user_id = ?');
                if (!$adminUpdate) {
                    throw new RuntimeException('Unable to update admin profile.');
                }
                $adminUpdate->bind_param('sssssssi', $firstName, $middleName, $lastName, $suffix, $resolvedIdNumber, $email, $password, $userId);
                $adminUpdate->execute();
                $adminUpdate->close();
            } else {
                $adminInsert = $conn->prepare('INSERT INTO admins (user_id, first_name, middle_name, last_name, suffix, id_number, email, password) VALUES (?, ?, ?, ?, ?, ?, ?, ?)');
                if (!$adminInsert) {
                    throw new RuntimeException('Unable to create admin profile.');
                }
                $adminInsert->bind_param('isssssss', $userId, $firstName, $middleName, $lastName, $suffix, $resolvedIdNumber, $email, $password);
                $adminInsert->execute();
                $adminInsert->close();
            }
        } elseif ($targetRoleType === 'student') {
            $studentCheck = $conn->prepare('SELECT student_id FROM students WHERE user_id = ? LIMIT 1');
            if (!$studentCheck) {
                throw new RuntimeException('Unable to validate student profile assignment.');
            }

            $studentCheck->bind_param('i', $userId);
            $studentCheck->execute();
            $studentResult = $studentCheck->get_result();
            $studentRow = $studentResult ? $studentResult->fetch_assoc() : null;
            $studentCheck->close();

            if ($studentRow) {
                $studentUpdate = $conn->prepare('UPDATE students SET first_name = ?, middle_name = ?, last_name = ?, suffix = ?, year_of_enrollment = ?, id_number = NULLIF(?,\'\'), program_id = ?, email = ?, password = ? WHERE user_id = ?');
                if (!$studentUpdate) {
                    throw new RuntimeException('Unable to update student profile.');
                }
                $studentUpdate->bind_param('ssssisissi', $firstName, $middleName, $lastName, $suffix, $resolvedYearOfEnrollment, $resolvedIdNumber, $resolvedProgramId, $email, $password, $userId);
                $studentUpdate->execute();
                $studentUpdate->close();
            } else {
                $studentInsert = $conn->prepare('INSERT INTO students (user_id, first_name, middle_name, last_name, suffix, year_of_enrollment, id_number, program_id, email, password) VALUES (?, ?, ?, ?, ?, ?, NULLIF(?,\'\'), ?, ?, ?)');
                if (!$studentInsert) {
                    throw new RuntimeException('Unable to create student profile.');
                }
                $studentInsert->bind_param('issssissss', $userId, $firstName, $middleName, $lastName, $suffix, $resolvedYearOfEnrollment, $resolvedIdNumber, $resolvedProgramId, $email, $password);
                $studentInsert->execute();
                $studentInsert->close();
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

    $targetEmail = trim((string) ($targetUser['email'] ?? ''));
    $userNotificationMessage = '';
    $userEmailSubject = '';
    $adminAuditMessage = '';

    if ($action === 'approve') {
        $approvedRoleLabel = to_display_role(
            (string) ($accessRoleMap[$accessRole]['roleType'] ?? 'student'),
            $accessRoleMap[$accessRole]['facultyRole'] ?? null
        );

        $userNotificationMessage = "Your account has been approved as {$approvedRoleLabel} by the admin.";
        $userEmailSubject = 'Your account has been approved';
        $adminAuditMessage = "Account approved: {$fullName} was approved as {$approvedRoleLabel}.";

        add_system_notification(
            $conn,
            $userId,
            $userNotificationMessage
        );
        $deferredEmailPayload = [
            'email' => $targetEmail,
            'fullName' => $fullName,
            'subject' => $userEmailSubject,
            'message' => $userNotificationMessage
        ];
    } elseif ($action === 'revoke') {
        $userNotificationMessage = "Your account access as {$currentRoleLabel} has been officially revoked by the admin. If you think this is a mistake, please contact the admin.";
        $userEmailSubject = 'Your account access has been revoked';
        $adminAuditMessage = "Access revoked: {$fullName} ({$currentRoleLabel}) is now set to Not Verified.";

        add_system_notification(
            $conn,
            $userId,
            $userNotificationMessage
        );

        $actingAdminUserId = (int) ($_SESSION['user_id'] ?? 0);
        if ($actingAdminUserId > 0) {
            add_system_notification($conn, $actingAdminUserId, $adminAuditMessage);
            notify_all_admins($conn, $adminAuditMessage, $actingAdminUserId);
        } else {
            notify_all_admins($conn, $adminAuditMessage);
        }

        $deferredEmailPayload = [
            'email' => $targetEmail,
            'fullName' => $fullName,
            'subject' => $userEmailSubject,
            'message' => $userNotificationMessage
        ];
    } else {
        $userNotificationMessage = 'Your account has been rejected and is currently set to pending approval.';
        $userEmailSubject = 'Your account has been rejected';
        $adminAuditMessage = "Account rejected: {$fullName} is now set to Not Verified.";

        add_system_notification(
            $conn,
            $userId,
            $userNotificationMessage
        );

        $actingAdminUserId = (int) ($_SESSION['user_id'] ?? 0);
        if ($actingAdminUserId > 0) {
            add_system_notification($conn, $actingAdminUserId, $adminAuditMessage);
        }

        $deferredEmailPayload = [
            'email' => $targetEmail,
            'fullName' => $fullName,
            'subject' => $userEmailSubject,
            'message' => $userNotificationMessage
        ];
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

$responsePayload = [
    'success' => true,
    'message' => ($action === 'approve'
        ? 'User approved successfully.'
        : ($action === 'revoke' ? 'User access revoked successfully.' : 'User rejected successfully.'))
        . ' Email notification dispatch queued.',
    'emailNotificationSent' => $emailNotificationSent,
    'emailDispatchMode' => 'deferred',
    'status' => to_status_label((string) ($targetUser['email'] ?? ''), $status, $isVerified)
];

send_json_and_continue($responsePayload);

if (is_array($deferredEmailPayload) && !empty($deferredEmailPayload['email'])) {
    $emailNotificationSent = send_user_email_notification(
        (string) $deferredEmailPayload['email'],
        (string) ($deferredEmailPayload['fullName'] ?? ''),
        (string) ($deferredEmailPayload['subject'] ?? ''),
        (string) ($deferredEmailPayload['message'] ?? '')
    );

    if (!$emailNotificationSent) {
        error_log('Deferred admin user-status email notification failed for user_id=' . $userId . ', action=' . $action);
    }
}

exit;
