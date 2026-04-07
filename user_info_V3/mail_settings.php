<?php

use PHPMailer\PHPMailer\PHPMailer;

/**
 * Shared SMTP setup for all outgoing emails.
 *
 * Environment variables (optional):
 * SMTP_HOST, SMTP_PORT, SMTP_USER, SMTP_PASS, SMTP_FROM_EMAIL, SMTP_FROM_NAME
 */
function configureSmtpMailer(PHPMailer $mail, string $fromName = 'SOE Portfolio'): void
{
    $smtpHost = getenv('SMTP_HOST') ?: 'smtp.gmail.com';
    $smtpPort = (int) (getenv('SMTP_PORT') ?: 587);
    $smtpUser = getenv('SMTP_USER') ?: 'neloangelo4123@gmail.com';

    // Google shows app passwords grouped in 4s; strip spaces if pasted that way.
    $smtpPassRaw = getenv('SMTP_PASS') ?: 'cdwa snhj qxnz oadf';
    $smtpPass = preg_replace('/\s+/', '', $smtpPassRaw);

    $fromEmail = getenv('SMTP_FROM_EMAIL') ?: $smtpUser;
    $displayFromName = getenv('SMTP_FROM_NAME') ?: $fromName;

    $mail->isSMTP();
    $mail->Host = $smtpHost;
    $mail->Port = $smtpPort;
    $mail->SMTPAuth = true;
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Username = $smtpUser;
    $mail->Password = $smtpPass;
    $mail->setFrom($fromEmail, $displayFromName);
}

function buildAppUrl(string $path): string
{
    $isHttps = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
    $scheme = $isHttps ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';

    $basePath = rtrim(dirname($_SERVER['SCRIPT_NAME'] ?? ''), '/\\');
    $normalizedPath = '/' . ltrim($path, '/');

    return $scheme . '://' . $host . $basePath . $normalizedPath;
}

function isLocalDevelopmentRequest(): bool
{
    $host = strtolower($_SERVER['HTTP_HOST'] ?? '');
    $hostOnly = explode(':', $host)[0] ?? '';

    return in_array($hostOnly, ['localhost', '127.0.0.1'], true);
}

function isLocalMailFallbackEnabled(): bool
{
    // On localhost, fallback is enabled by default for testing.
    // Set MAIL_FALLBACK_LOCAL=0 to force SMTP-only behavior.
    $fallback = getenv('MAIL_FALLBACK_LOCAL');

    if ($fallback === false || $fallback === '') {
        return isLocalDevelopmentRequest();
    }

    return isLocalDevelopmentRequest() && $fallback === '1';
}
