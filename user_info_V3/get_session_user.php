<?php

declare(strict_types=1);

session_start();
require_once __DIR__ . '/connect.php';
require_once __DIR__ . '/notification_service.php';
require_once __DIR__ . '/user_access_common.php';

header('Content-Type: application/json; charset=utf-8');

function is_local_owner_mode(): bool
{
    $host = strtolower((string) ($_SERVER['HTTP_HOST'] ?? ''));
    $hostOnly = explode(':', $host)[0] ?? '';
    $remoteAddr = (string) ($_SERVER['REMOTE_ADDR'] ?? '');

    return in_array($hostOnly, ['localhost', '127.0.0.1'], true)
        || in_array($remoteAddr, ['127.0.0.1', '::1'], true);
}

function resolve_local_owner_user(mysqli $conn): ?array
{
    $preferredEmail = trim((string) ($_COOKIE['local_owner_email'] ?? ''));
    if ($preferredEmail !== '' && filter_var($preferredEmail, FILTER_VALIDATE_EMAIL)) {
        $preferredStmt = $conn->prepare(
                'SELECT u.user_id, u.first_name, u.last_name, u.email, u.role_type, u.status, u.is_verified, u.profile_picture,
                    f.faculty_role
             FROM users u
             LEFT JOIN faculty f ON f.user_id = u.user_id
             WHERE u.email = ?
               AND u.role_type = "faculty"
               AND u.status = 1
               AND u.is_verified = 1
             LIMIT 1'
        );
        if ($preferredStmt) {
            $preferredStmt->bind_param('s', $preferredEmail);
            $preferredStmt->execute();
            $preferredResult = $preferredStmt->get_result();
            $preferredRow = $preferredResult ? $preferredResult->fetch_assoc() : null;
            $preferredStmt->close();
            if ($preferredRow) {
                return $preferredRow;
            }
        }
    }

    $sql =
        'SELECT u.user_id, u.first_name, u.last_name, u.email, u.role_type, u.status, u.is_verified,
                f.faculty_role
         FROM users u
         LEFT JOIN faculty f ON f.user_id = u.user_id
         WHERE u.role_type = "faculty"
           AND u.status = 1
           AND u.is_verified = 1
         ORDER BY CASE
             WHEN LOWER(TRIM(COALESCE(f.faculty_role, ""))) = "executive director" THEN 1
             WHEN LOWER(TRIM(COALESCE(f.faculty_role, ""))) = "program director" THEN 2
             WHEN LOWER(TRIM(COALESCE(f.faculty_role, ""))) = "professor" THEN 3
             ELSE 4
         END,
         u.user_id ASC
         LIMIT 1';

    $result = $conn->query($sql);
    if (!$result) {
        return null;
    }

    $row = $result->fetch_assoc();
    return $row ?: null;
}

$isLocalOwnerFallback = false;
$email = trim((string) ($_SESSION['email'] ?? ''));
if ($email === '') {
    if (!is_local_owner_mode()) {
        http_response_code(401);
        echo json_encode([
            'success' => false,
            'message' => 'User is not logged in.'
        ]);
        exit;
    }

    $localOwner = resolve_local_owner_user($conn);
    if (!$localOwner) {
        http_response_code(401);
        echo json_encode([
            'success' => false,
            'message' => 'User is not logged in.'
        ]);
        exit;
    }

    $user = $localOwner;
    $isLocalOwnerFallback = true;
    $_SESSION['user_id'] = (int) ($user['user_id'] ?? 0);
    $_SESSION['role_type'] = (string) ($user['role_type'] ?? 'faculty');
    $_SESSION['is_verified'] = (int) ($user['is_verified'] ?? 1);
} else {
    $stmt = $conn->prepare('SELECT user_id, first_name, last_name, email, role_type, status, is_verified, profile_picture FROM users WHERE email = ? LIMIT 1');
    if (!$stmt) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'Failed to load user context.',
            'error' => $conn->error
        ]);
        exit;
    }

    $stmt->bind_param('s', $email);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result ? $result->fetch_assoc() : null;
    $stmt->close();

    if (!$user) {
        http_response_code(404);
        echo json_encode([
            'success' => false,
            'message' => 'User account not found.'
        ]);
        exit;
    }
}

$userId = (int) $user['user_id'];
$_SESSION['user_id'] = $userId;
$_SESSION['role_type'] = (string) $user['role_type'];
$_SESSION['is_verified'] = (int) $user['is_verified'];

$facultyRole = null;
if (strtolower((string) $user['role_type']) === 'faculty') {
    $facultyStmt = $conn->prepare('SELECT faculty_role FROM faculty WHERE user_id = ? LIMIT 1');
    if ($facultyStmt) {
        $facultyStmt->bind_param('i', $userId);
        $facultyStmt->execute();
        $facultyResult = $facultyStmt->get_result();
        $facultyRow = $facultyResult ? $facultyResult->fetch_assoc() : null;
        $facultyRole = $facultyRow ? (string) ($facultyRow['faculty_role'] ?? '') : null;
        $facultyStmt->close();
    }
}

$fullName = trim((string) $user['first_name'] . ' ' . (string) $user['last_name']);
$displayEmail = (string) ($user['email'] ?? '');
$unreadCount = count_unread_notifications($conn, $userId);

if ($isLocalOwnerFallback) {
    $fullName = 'Local Owner Mode';
    $displayEmail = 'localhost@owner-mode';
    $unreadCount = 0;
}
$nextPath = resolve_effective_route($user);

$isDashboardRoute = $nextPath !== 'review_user.php';

echo json_encode([
    'success' => true,
    'user' => [
        'userId' => $userId,
        'fullName' => $fullName,
        'email' => $displayEmail,
        'roleType' => (string) $user['role_type'],
        'facultyRole' => $facultyRole,
        'status' => (int) $user['status'],
        'isVerified' => (int) $user['is_verified'],
        'profile_picture' => (string) ($user['profile_picture'] ?? ''),
        'unreadNotifications' => $unreadCount,
        'nextPath' => $nextPath,
        'canAccessDashboard' => $isDashboardRoute,
        'isLocalOwnerMode' => $isLocalOwnerFallback
    ]
]);
