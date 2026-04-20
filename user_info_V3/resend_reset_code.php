<?php
require 'vendor/autoload.php';
require_once 'mail_settings.php';

use PHPMailer\PHPMailer\PHPMailer;

session_start();
include 'connect.php';

function redirect_with_status(string $status): void
{
    header('Location: notification.html?v=2&status=' . urlencode($status));
    exit;
}

function log_password_recovery_mail_event(string $email, string $status, string $details = ''): void
{
    $logPath = __DIR__ . DIRECTORY_SEPARATOR . 'password_recovery_mail.log';
    $line = sprintf(
        "[%s] email=%s status=%s details=%s%s",
        date('Y-m-d H:i:s'),
        $email,
        $status,
        str_replace(["\r", "\n"], ' ', $details),
        PHP_EOL
    );

    @file_put_contents($logPath, $line, FILE_APPEND);
}

function find_pending_token_record(mysqli $conn, string $email, string $rawToken): ?array
{
    $stmt = $conn->prepare('SELECT id, token_hash, expires_at, used_at FROM password_reset_tokens WHERE email = ? AND used_at IS NULL ORDER BY id DESC');
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

if ($email === '' || $token === '') {
    redirect_with_status('session-missing');
}

$record = find_pending_token_record($conn, $email, $token);
if (!$record) {
    redirect_with_status('session-missing');
}

$tokenExpiresAt = strtotime((string) ($record['expires_at'] ?? ''));
if (!empty($record['used_at']) || $tokenExpiresAt === false || $tokenExpiresAt < time()) {
    redirect_with_status('session-missing');
}

$tokenId = (int) ($record['id'] ?? 0);
if ($tokenId <= 0) {
    redirect_with_status('session-missing');
}

$newCode = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
$newCodeHash = password_hash($newCode, PASSWORD_DEFAULT);
$newCodeExpiresAt = date('Y-m-d H:i:s', time() + (60 * 10));
$resetUrl = buildAppUrl('reset_psw.php?token=' . urlencode($token));

$updateStmt = $conn->prepare('UPDATE password_reset_tokens SET code_hash = ?, code_expires_at = ?, code_verified_at = NULL WHERE id = ? LIMIT 1');
if (!$updateStmt) {
    redirect_with_status('send-failed');
}

$updateStmt->bind_param('ssi', $newCodeHash, $newCodeExpiresAt, $tokenId);
$updated = $updateStmt->execute();
$updateStmt->close();

if (!$updated) {
    redirect_with_status('send-failed');
}

$mail = new PHPMailer(true);
try {
    configureSmtpMailer($mail, 'Password Reset');
    $mail->addAddress($email);

    $mail->isHTML(true);
    $mail->Subject = 'Your password reset verification code';
    $mail->Body = "<b>Dear User</b>\n"
        . "<p>Here is your verification code:</p>\n"
        . "<h2 style='letter-spacing:4px;'>{$newCode}</h2>\n"
        . "<p>This code expires in 10 minutes.</p>\n"
        . "<p>You can also use your reset link (30 minutes): <a href='{$resetUrl}'>Reset Password</a></p>\n"
        . "<p>With regards,</p><b>SOE Portfolio</b>";

    $mail->AltBody = "Verification code: {$newCode}\n"
        . "Code expiry: 10 minutes\n"
        . "Reset link: {$resetUrl}\n";

    $mail->send();

    $messageId = method_exists($mail, 'getLastMessageID') ? (string) $mail->getLastMessageID() : '';
    log_password_recovery_mail_event(
        $email,
        'smtp-accepted-code-resend',
        'host=' . (string) $mail->Host . '; from=' . (string) $mail->From . '; message_id=' . $messageId
    );

    redirect_with_status('code-sent');
} catch (\Throwable $e) {
    $mailError = trim((string) $mail->ErrorInfo);
    if ($mailError === '') {
        $mailError = $e->getMessage();
    }

    log_password_recovery_mail_event($email, 'smtp-failed-code-resend', $mailError);
    redirect_with_status('send-failed');
}
?>
