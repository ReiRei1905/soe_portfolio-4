<?php

declare(strict_types=1);

function add_system_notification(mysqli $conn, int $userId, string $message): bool
{
    if ($userId <= 0 || trim($message) === '') {
        return false;
    }

    $stmt = $conn->prepare('INSERT INTO notifications (user_id, message, is_read) VALUES (?, ?, 0)');
    if (!$stmt) {
        return false;
    }

    $stmt->bind_param('is', $userId, $message);
    $ok = $stmt->execute();
    $stmt->close();

    return $ok;
}

function fetch_user_notifications(mysqli $conn, int $userId, int $limit = 20): array
{
    $limit = max(1, min($limit, 100));

    $sql = "
        SELECT id, message, is_read, created_at
        FROM notifications
        WHERE user_id = ?
        ORDER BY created_at DESC, id DESC
        LIMIT {$limit}
    ";

    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        return [];
    }

    $stmt->bind_param('i', $userId);
    $stmt->execute();
    $result = $stmt->get_result();

    $rows = [];
    while ($row = $result->fetch_assoc()) {
        $rows[] = [
            'id' => (int) $row['id'],
            'message' => (string) $row['message'],
            'isRead' => (int) $row['is_read'] === 1,
            'createdAt' => date('d/m/Y h:i A', strtotime((string) $row['created_at']))
        ];
    }

    $stmt->close();
    return $rows;
}

function count_unread_notifications(mysqli $conn, int $userId): int
{
    $stmt = $conn->prepare('SELECT COUNT(*) AS unread_count FROM notifications WHERE user_id = ? AND is_read = 0');
    if (!$stmt) {
        return 0;
    }

    $stmt->bind_param('i', $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result ? $result->fetch_assoc() : null;
    $stmt->close();

    return $row ? (int) $row['unread_count'] : 0;
}

function mark_notifications_read(mysqli $conn, int $userId): bool
{
    $stmt = $conn->prepare('UPDATE notifications SET is_read = 1 WHERE user_id = ? AND is_read = 0');
    if (!$stmt) {
        return false;
    }

    $stmt->bind_param('i', $userId);
    $ok = $stmt->execute();
    $stmt->close();

    return $ok;
}
