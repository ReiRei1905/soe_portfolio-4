<?php
session_start();
include 'connect.php';

function redirect_with_status(string $status): void
{
    header('Location: notification.html?v=2&status=' . urlencode($status));
    exit;
}

function find_pending_token_record(mysqli $conn, string $email, string $rawToken): ?array
{
    $stmt = $conn->prepare('SELECT id, token_hash, code_hash, code_expires_at, expires_at, used_at FROM password_reset_tokens WHERE email = ? AND used_at IS NULL ORDER BY id DESC');
    if (!$stmt) {
        return null;
    }

    $stmt->bind_param('s', $email);
    $stmt->execute();
    $result = $stmt->get_result();
    $match = null;

    while ($result && ($row = $result->fetch_assoc())) {
        if (!empty($row['token_hash']) && password_verify($rawToken, (string) $row['token_hash'])) {
            $match = $row;
            break;
        }
    }

    $stmt->close();
    return $match;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect_with_status('session-missing');
}

$email = strtolower(trim((string) ($_SESSION['password_reset_pending_email'] ?? '')));
$token = trim((string) ($_SESSION['password_reset_pending_token'] ?? ''));
$code = preg_replace('/\D+/', '', (string) ($_POST['verification_code'] ?? ''));

if ($email === '' || $token === '') {
    redirect_with_status('session-missing');
}

if (strlen($code) !== 6) {
    redirect_with_status('invalid-code');
}

$record = find_pending_token_record($conn, $email, $token);
if (!$record) {
    redirect_with_status('session-missing');
}

$tokenExpiresAt = strtotime((string) ($record['expires_at'] ?? ''));
if (!empty($record['used_at']) || $tokenExpiresAt === false || $tokenExpiresAt < time()) {
    redirect_with_status('session-missing');
}

$codeHash = (string) ($record['code_hash'] ?? '');
$codeExpiresAt = strtotime((string) ($record['code_expires_at'] ?? ''));

if ($codeHash === '' || $codeExpiresAt === false || $codeExpiresAt < time()) {
    redirect_with_status('expired-code');
}

if (!password_verify($code, $codeHash)) {
    redirect_with_status('invalid-code');
}

$tokenId = (int) ($record['id'] ?? 0);
if ($tokenId > 0) {
    $updateStmt = $conn->prepare('UPDATE password_reset_tokens SET code_verified_at = NOW() WHERE id = ? LIMIT 1');
    if ($updateStmt) {
        $updateStmt->bind_param('i', $tokenId);
        $updateStmt->execute();
        $updateStmt->close();
    }
}

header('Location: reset_psw.php?token=' . urlencode($token));
exit;
?>
