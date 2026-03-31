<?php
require_once __DIR__ . '/common.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(405, ['ok' => false, 'message' => 'Method not allowed.']);
}

try {
    $conn = db_connect();
    $studentId = current_student_id($conn);

    $displayName = sanitize_name(get_request_string('display_name'), 120);
    $bio = sanitize_name(get_request_string('bio'), 160);

    if ($displayName === '') {
        json_response(422, ['ok' => false, 'message' => 'Display name is required.']);
    }

    $stmt = $conn->prepare('INSERT INTO student_homepage_profiles (student_id, display_name, bio) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE display_name = VALUES(display_name), bio = VALUES(bio), updated_at = CURRENT_TIMESTAMP');
    $stmt->bind_param('iss', $studentId, $displayName, $bio);
    $stmt->execute();
    $stmt->close();

    json_response(200, [
        'ok' => true,
        'message' => 'Profile updated successfully.',
        'profile' => [
            'displayName' => $displayName,
            'bio' => $bio
        ]
    ]);
} catch (Throwable $error) {
    json_response(500, ['ok' => false, 'message' => $error->getMessage()]);
}
