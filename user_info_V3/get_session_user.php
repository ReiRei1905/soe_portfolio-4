<?php

declare(strict_types=1);

session_start();
require_once __DIR__ . '/connect.php';
require_once __DIR__ . '/notification_service.php';
require_once __DIR__ . '/user_access_common.php';

header('Content-Type: application/json; charset=utf-8');

$email = trim((string) ($_SESSION['email'] ?? ''));
if ($email === '') {
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'message' => 'User is not logged in.'
    ]);
    exit;
}

$stmt = $conn->prepare('SELECT user_id, first_name, last_name, email, role_type, status, is_verified FROM users WHERE email = ? LIMIT 1');
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
$unreadCount = count_unread_notifications($conn, $userId);
$nextPath = resolve_effective_route($user);

$isDashboardRoute = $nextPath !== 'review_user.php';

echo json_encode([
    'success' => true,
    'user' => [
        'userId' => $userId,
        'fullName' => $fullName,
        'email' => (string) $user['email'],
        'roleType' => (string) $user['role_type'],
        'facultyRole' => $facultyRole,
        'status' => (int) $user['status'],
        'isVerified' => (int) $user['is_verified'],
        'unreadNotifications' => $unreadCount,
        'nextPath' => $nextPath,
        'canAccessDashboard' => $isDashboardRoute
    ]
]);
