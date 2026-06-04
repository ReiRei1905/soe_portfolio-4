<?php

declare(strict_types=1);

require_once __DIR__ . '/../faculty_access_common.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    faculty_send_json(['success' => false, 'message' => 'Method not allowed.'], 405);
}

$sessionUser = faculty_require_verified_faculty($conn);
$classId = isset($_POST['class_id']) ? (int) $_POST['class_id'] : 0;

if ($classId <= 0) {
    faculty_send_json(['success' => false, 'message' => 'Invalid class reference.'], 400);
}

if (!faculty_can_handle_class($conn, $sessionUser, $classId)) {
    faculty_send_json(['success' => false, 'message' => 'You are not allowed to invite students to this class.'], 403);
}

if (!faculty_table_exists($conn, 'class_invite_links')) {
    faculty_send_json([
        'success' => false,
        'message' => 'Table class_invite_links is missing. Please run the required SQL migration first.'
    ], 500);
}

function generate_invite_token(): string
{
    return bin2hex(random_bytes(16));
}

$token = '';
$maxAttempts = 5;

for ($attempt = 0; $attempt < $maxAttempts; $attempt++) {
    $token = generate_invite_token();

    $checkStmt = $conn->prepare('SELECT invite_id FROM class_invite_links WHERE token = ? LIMIT 1');
    if (!$checkStmt) {
        faculty_send_json(['success' => false, 'message' => 'Failed to validate invite token.'], 500);
    }

    $checkStmt->bind_param('s', $token);
    $checkStmt->execute();
    $existing = $checkStmt->get_result()->fetch_assoc();
    $checkStmt->close();

    if (!$existing) {
        break;
    }

    $token = '';
}

if ($token === '') {
    faculty_send_json(['success' => false, 'message' => 'Unable to generate a unique invite token.'], 500);
}

$createdBy = (int) ($sessionUser['user_id'] ?? 0);
$insertStmt = $conn->prepare(
    'INSERT INTO class_invite_links (class_id, token, created_by_user_id, expires_at)
     VALUES (?, ?, ?, DATE_ADD(NOW(), INTERVAL 14 DAY))'
);

if (!$insertStmt) {
    faculty_send_json(['success' => false, 'message' => 'Failed to create invite link.'], 500);
}

$insertStmt->bind_param('isi', $classId, $token, $createdBy);
$ok = $insertStmt->execute();
$insertStmt->close();

if (!$ok) {
    faculty_send_json(['success' => false, 'message' => 'Failed to save invite link.'], 500);
}

$scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
$scriptPath = $_SERVER['SCRIPT_NAME'] ?? '';
$rootPath = rtrim(dirname(dirname(dirname($scriptPath))), '/\\');
$invitePath = $rootPath . '/student_side/student_class/join_class_invite.php?token=' . urlencode($token);

faculty_send_json([
    'success' => true,
    'inviteUrl' => $scheme . '://' . $host . $invitePath,
    'token' => $token
]);
