<?php

declare(strict_types=1);

require_once __DIR__ . '/../api/common.php';

function render_invite_error(string $title, string $message, string $token = ''): void
{
    http_response_code(400);
    echo '<!DOCTYPE html>';
    echo '<html lang="en">';
    echo '<head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">';
    echo '<title>' . htmlspecialchars($title, ENT_QUOTES, 'UTF-8') . '</title></head>';
    echo '<body style="font-family: Arial, sans-serif; padding: 24px;">';
    echo '<h2>' . htmlspecialchars($title, ENT_QUOTES, 'UTF-8') . '</h2>';
    echo '<p>' . htmlspecialchars($message, ENT_QUOTES, 'UTF-8') . '</p>';
    
    $loginUrl = '../../user_info_V3/index.php';
    if ($token !== '') {
        $currentPage = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
        $loginUrl .= '?redirect=' . urlencode($currentPage);
    }
    
    echo '<p><a href="' . htmlspecialchars($loginUrl, ENT_QUOTES, 'UTF-8') . '">Go to login</a></p>';
    echo '</body></html>';
    exit;
}

$token = trim((string) ($_GET['token'] ?? ''));
if ($token === '') {
    render_invite_error('Invalid invite link', 'The invite token is missing.');
}

$conn = db_connect();

if (!isset($_SESSION['email']) || trim((string) $_SESSION['email']) === '') {
    render_invite_error('Sign in required', 'Please log in with your APC email to join this class.', $token);
}

$email = trim((string) ($_SESSION['email'] ?? ''));

$classInviteExists = (bool) $conn->query("SHOW TABLES LIKE 'class_invite_links'")->fetch_assoc();
if (!$classInviteExists) {
    render_invite_error('Invite unavailable', 'Invite links are not configured yet. Please contact your professor.');
}

$stmt = $conn->prepare('SELECT class_id, is_active, expires_at FROM class_invite_links WHERE token = ? LIMIT 1');
if (!$stmt) {
    render_invite_error('Invite unavailable', 'Unable to validate invite link.');
}

$stmt->bind_param('s', $token);
$stmt->execute();
$linkRow = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$linkRow) {
    render_invite_error('Invite invalid', 'This invite link is no longer valid.');
}

if ((int) ($linkRow['is_active'] ?? 0) !== 1) {
    render_invite_error('Invite inactive', 'This invite link is inactive. Please request a new one.');
}

$expiresAt = (string) ($linkRow['expires_at'] ?? '');
if ($expiresAt !== '' && strtotime($expiresAt) < time()) {
    render_invite_error('Invite expired', 'This invite link has expired. Please request a new one.');
}

if (!preg_match('/@student\.apc\.edu\.ph$/i', $email)) {
    render_invite_error('Invalid email domain', 'Please use your @student.apc.edu.ph account to join this class.');
}

$classId = (int) ($linkRow['class_id'] ?? 0);
if ($classId <= 0) {
    render_invite_error('Invite invalid', 'Invalid class reference.');
}

$classStudentsExists = (bool) $conn->query("SHOW TABLES LIKE 'class_students'")->fetch_assoc();
if (!$classStudentsExists) {
    render_invite_error('Join unavailable', 'Class enrollment is not configured yet.');
}

$studentId = current_student_id($conn);

$checkStmt = $conn->prepare('SELECT status FROM class_students WHERE class_id = ? AND student_id = ? LIMIT 1');
$checkStmt->bind_param('ii', $classId, $studentId);
$checkStmt->execute();
$existing = $checkStmt->get_result()->fetch_assoc();
$checkStmt->close();

if ($existing) {
    $status = strtolower((string) ($existing['status'] ?? ''));
    if ($status === 'approved') {
        $redirectPath = './student_class.html?class_id=' . urlencode((string) $classId) . '&already_joined=1';
        header('Location: ' . $redirectPath);
        exit;
    }

    $updateStmt = $conn->prepare('UPDATE class_students
                                  SET invitation_source = "invited",
                                      status = "approved",
                                      invited_at = NOW(),
                                      approved_at = NOW(),
                                      requested_at = NULL,
                                      removed_at = NULL,
                                      updated_at = NOW()
                                  WHERE class_id = ? AND student_id = ?');
    $updateStmt->bind_param('ii', $classId, $studentId);
    $updateStmt->execute();
    $updateStmt->close();
} else {
    $insertStmt = $conn->prepare('INSERT INTO class_students (
                                    class_id, student_id, invitation_source, status,
                                    invited_at, approved_at, created_at, updated_at
                                  ) VALUES (?, ?, "invited", "approved", NOW(), NOW(), NOW(), NOW())');
    $insertStmt->bind_param('ii', $classId, $studentId);
    $insertStmt->execute();
    $insertStmt->close();
}

$usageStmt = $conn->prepare('UPDATE class_invite_links SET used_count = used_count + 1, last_used_at = NOW() WHERE token = ?');
if ($usageStmt) {
    $usageStmt->bind_param('s', $token);
    $usageStmt->execute();
    $usageStmt->close();
}

$redirectPath = './student_class.html?class_id=' . urlencode((string) $classId) . '&joined=1';
header('Location: ' . $redirectPath);
exit;
