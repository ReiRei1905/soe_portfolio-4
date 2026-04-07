<?php

declare(strict_types=1);

session_start();
require_once __DIR__ . '/connect.php';
require_once __DIR__ . '/notification_service.php';

header('Content-Type: application/json; charset=utf-8');

$userId = (int) ($_SESSION['user_id'] ?? 0);
$email = trim((string) ($_SESSION['email'] ?? ''));

if ($userId <= 0 && $email !== '') {
    $stmt = $conn->prepare('SELECT user_id FROM users WHERE email = ? LIMIT 1');
    if ($stmt) {
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result ? $result->fetch_assoc() : null;
        $stmt->close();

        if ($row) {
            $userId = (int) $row['user_id'];
            $_SESSION['user_id'] = $userId;
        }
    }
}

if ($userId <= 0) {
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'message' => 'User is not logged in.'
    ]);
    exit;
}

$method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
if ($method === 'POST') {
    $action = strtolower(trim((string) ($_POST['action'] ?? '')));
    if ($action === 'mark_read') {
        mark_notifications_read($conn, $userId);
    }
}

$notifications = fetch_user_notifications($conn, $userId, 30);
$unreadCount = count_unread_notifications($conn, $userId);

echo json_encode([
    'success' => true,
    'unreadCount' => $unreadCount,
    'notifications' => $notifications
]);
