<?php

declare(strict_types=1);

require_once __DIR__ . '/admin_api_common.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    send_json([
        'success' => false,
        'message' => 'Method not allowed.'
    ], 405);
}

$userId = isset($_POST['user_id']) ? (int) $_POST['user_id'] : 0;
if ($userId <= 0) {
    send_json([
        'success' => false,
        'message' => 'Invalid user id.'
    ], 400);
}

try {
    $conn->begin_transaction();

    $deleteStudent = $conn->prepare('DELETE FROM students WHERE user_id = ?');
    $deleteFaculty = $conn->prepare('DELETE FROM faculty WHERE user_id = ?');
    $deleteAdmin = $conn->prepare('DELETE FROM admins WHERE user_id = ?');
    $deleteUser = $conn->prepare('DELETE FROM users WHERE user_id = ?');

    if (!$deleteStudent || !$deleteFaculty || !$deleteAdmin || !$deleteUser) {
        throw new RuntimeException('Unable to prepare delete statements.');
    }

    foreach ([$deleteStudent, $deleteFaculty, $deleteAdmin, $deleteUser] as $stmt) {
        $stmt->bind_param('i', $userId);
        $stmt->execute();
    }

    $removed = $deleteUser->affected_rows > 0;

    $deleteStudent->close();
    $deleteFaculty->close();
    $deleteAdmin->close();
    $deleteUser->close();

    if (!$removed) {
        $conn->rollback();
        send_json([
            'success' => false,
            'message' => 'User not found or already removed.'
        ], 404);
    }

    $conn->commit();

    send_json([
        'success' => true,
        'message' => 'User removed successfully.'
    ]);
} catch (Throwable $error) {
    $conn->rollback();

    send_json([
        'success' => false,
        'message' => 'Failed to remove user.',
        'error' => $error->getMessage()
    ], 500);
}
