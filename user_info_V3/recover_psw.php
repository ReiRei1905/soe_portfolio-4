<?php
require 'vendor/autoload.php';
require_once 'mail_settings.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

session_start();
include 'connect.php';

function ensure_password_recovery_log_file(): void
{
    $logPath = __DIR__ . DIRECTORY_SEPARATOR . 'password_recovery_mail.log';
    if (!is_file($logPath)) {
        @file_put_contents($logPath, '');
    }
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

ensure_password_recovery_log_file();

function ensure_password_reset_token_table(mysqli $conn): bool
{
    $sql = 'CREATE TABLE IF NOT EXISTS password_reset_tokens (
                id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                email VARCHAR(191) NOT NULL,
                token_hash VARCHAR(255) NOT NULL,
                code_hash VARCHAR(255) NULL,
                code_expires_at DATETIME NULL,
                code_verified_at DATETIME NULL,
                expires_at DATETIME NOT NULL,
                created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                used_at DATETIME NULL,
                PRIMARY KEY (id),
                KEY idx_password_reset_email (email),
                KEY idx_password_reset_expires (expires_at)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci';

    if (!(bool) $conn->query($sql)) {
        return false;
    }

    // Keep schema backward compatible when table already exists from older versions.
    $conn->query('ALTER TABLE password_reset_tokens ADD COLUMN IF NOT EXISTS code_hash VARCHAR(255) NULL AFTER token_hash');
    $conn->query('ALTER TABLE password_reset_tokens ADD COLUMN IF NOT EXISTS code_expires_at DATETIME NULL AFTER code_hash');
    $conn->query('ALTER TABLE password_reset_tokens ADD COLUMN IF NOT EXISTS code_verified_at DATETIME NULL AFTER code_expires_at');

    return true;
}

if (isset($_POST['recoverButton'])) {
    $email = strtolower(trim($_POST['email'] ?? ''));

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        log_password_recovery_mail_event($email, 'invalid-email');
        echo "<script>alert('Please enter a valid email address.');</script>";
    } else {
        $stmt = $conn->prepare('SELECT status FROM users WHERE email = ? LIMIT 1');
        if (!$stmt) {
            log_password_recovery_mail_event($email, 'db-prepare-failed', 'users status query prepare failed');
            echo "<script>alert('Unable to process request right now.');</script>";
        } else {
            $stmt->bind_param('s', $email);
            $stmt->execute();
            $result = $stmt->get_result();
            $fetch = $result ? $result->fetch_assoc() : null;
            $stmt->close();

            if (!$fetch) {
                log_password_recovery_mail_event($email, 'email-not-found');
                echo "<script>alert('Sorry, no email exists');</script>";
            } elseif ((int) ($fetch['status'] ?? 0) === 0) {
                log_password_recovery_mail_event($email, 'not-verified');
                echo "<script>
                    alert('Sorry, your account must be verified before you can recover your password!');
                    window.location.replace('index.php');
                </script>";
            } elseif (!ensure_password_reset_token_table($conn)) {
                log_password_recovery_mail_event($email, 'token-table-init-failed');
                echo "<script>alert('Unable to initialize password reset service. Please try again later.');</script>";
            } else {
                $token = bin2hex(random_bytes(32));
                $tokenHash = password_hash($token, PASSWORD_DEFAULT);
                $verificationCode = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
                $codeHash = password_hash($verificationCode, PASSWORD_DEFAULT);
                $expiresAt = date('Y-m-d H:i:s', time() + (60 * 30));
                $codeExpiresAt = date('Y-m-d H:i:s', time() + (60 * 10));

                $cleanupStmt = $conn->prepare('DELETE FROM password_reset_tokens WHERE email = ? AND used_at IS NULL');
                if ($cleanupStmt) {
                    $cleanupStmt->bind_param('s', $email);
                    $cleanupStmt->execute();
                    $cleanupStmt->close();
                }

                $insertStmt = $conn->prepare('INSERT INTO password_reset_tokens (email, token_hash, code_hash, code_expires_at, expires_at) VALUES (?, ?, ?, ?, ?)');
                if (!$insertStmt) {
                    log_password_recovery_mail_event($email, 'token-insert-prepare-failed');
                    echo "<script>alert('Unable to create reset token. Please try again later.');</script>";
                } else {
                    $insertStmt->bind_param('sssss', $email, $tokenHash, $codeHash, $codeExpiresAt, $expiresAt);
                    $saved = $insertStmt->execute();
                    $insertStmt->close();

                    if (!$saved) {
                        log_password_recovery_mail_event($email, 'token-insert-failed');
                        echo "<script>alert('Unable to create reset token. Please try again later.');</script>";
                    } else {
                        $_SESSION['password_reset_pending_email'] = $email;
                        $_SESSION['password_reset_pending_token'] = $token;

                        $mail = new PHPMailer(true);
                        try {
                            configureSmtpMailer($mail, 'Password Reset');
                            $mail->addAddress($email);

                            $resetUrl = buildAppUrl('reset_psw.php?token=' . urlencode($token));

                            $mail->isHTML(true);
                            $mail->Subject = 'Recover your password';
                            $mail->Body = "<b>Dear User</b>
                                <h3>We received a request to reset your password.</h3>
                                <p>Kindly click the link below to reset your password. This link expires in 30 minutes.</p>
                                <a href='{$resetUrl}'>Reset Password</a>
                                <p style='margin-top:12px;'>If you cannot open the link from your device, use this verification code in the notification page:</p>
                                <h2 style='letter-spacing:4px;'>{$verificationCode}</h2>
                                <p>This code expires in 10 minutes.</p>
                                <br><br>
                                <p>With regards,</p>
                                <b>SOE Portfolio</b>";

                            $mail->AltBody = "We received a request to reset your password.\n"
                                . "Reset link (30 minutes): {$resetUrl}\n"
                                . "Verification code (10 minutes): {$verificationCode}\n";

                            $mail->send();
                            $messageId = method_exists($mail, 'getLastMessageID') ? (string) $mail->getLastMessageID() : '';
                            log_password_recovery_mail_event(
                                $email,
                                'smtp-accepted',
                                'host=' . (string) $mail->Host . '; from=' . (string) $mail->From . '; message_id=' . $messageId
                            );
                            echo "<script>window.location.replace('notification.html?v=2');</script>";
                        } catch (\Throwable $e) {
                            $mailError = trim((string) $mail->ErrorInfo);
                            if ($mailError === '') {
                                $mailError = $e->getMessage();
                            }
                            log_password_recovery_mail_event($email, 'smtp-failed', $mailError);
                            error_log('Password recovery mail failed for ' . $email . ': ' . $mailError);

                            if (isLocalMailFallbackEnabled()) {
                                $fallbackUrl = 'reset_psw.php?token=' . rawurlencode($token);
                                echo "<script>alert('SMTP failed, local fallback is active. You will be redirected to reset password directly.'); window.location.replace('{$fallbackUrl}');</script>";
                            } else {
                                echo "<script>alert('Email service is temporarily unavailable. Please try again later.');</script>";
                            }
                        }
                    }
                }
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SOE Portfolio</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container" id="recoveryContainer">
        <h1 class="form-title">SOE-Portfolio Recover Password</h1>
        <form action="#" method="POST">
            <div class="input-group">
                <i class="fas fa-key"></i>
                <input type="email" name="email" placeholder="Enter Email" required>
            </div>
            <input type="submit" class="btn" value="Recover" name="recoverButton">
        </form>
    </div>
</body>
</html>