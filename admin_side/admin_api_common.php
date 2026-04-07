<?php

declare(strict_types=1);

session_start();

require_once __DIR__ . '/../user_info_V3/connect.php';

if (!isset($conn) || !($conn instanceof mysqli)) {
    http_response_code(500);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        'success' => false,
        'message' => 'Database connection is unavailable.'
    ]);
    exit;
}

$conn->set_charset('utf8mb4');

function send_json(array $payload, int $statusCode = 200): void
{
    http_response_code($statusCode);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($payload);
    exit;
}

function normalize_filter_value(string $value): string
{
    $normalized = strtolower(trim($value));

    $aliases = [
        'executivedirector' => 'executive director',
        'programdirector' => 'program director',
        'professor' => 'professor',
        'student' => 'student',
        'admin' => 'admin',
        'all' => 'all',
        'executive director' => 'executive director',
        'program director' => 'program director'
    ];

    return $aliases[$normalized] ?? $normalized;
}

function to_display_role(string $roleType, ?string $facultyRole): string
{
    $roleType = strtolower(trim($roleType));

    if ($roleType === 'faculty') {
        $facultyRole = strtolower(trim((string) $facultyRole));
        return match ($facultyRole) {
            'executive director' => 'Executive Director',
            'program director' => 'Program Director',
            default => 'Professor',
        };
    }

    return match ($roleType) {
        'admin' => 'Admin',
        'student' => 'Student',
        default => ucfirst($roleType),
    };
}

function to_filter_role(string $roleType, ?string $facultyRole): string
{
    $roleType = strtolower(trim($roleType));

    if ($roleType === 'faculty') {
        $facultyRole = strtolower(trim((string) $facultyRole));
        return match ($facultyRole) {
            'executive director' => 'executive director',
            'program director' => 'program director',
            default => 'professor',
        };
    }

    return $roleType;
}

function is_auto_verified_domain(string $email): bool
{
    $email = strtolower(trim($email));
    return str_ends_with($email, '@student.apc.edu.ph') || str_ends_with($email, '@apc.edu.ph');
}

function to_status_label(string $email, int $status, int $isVerified = 0): string
{
    if ($status === 1 && (is_auto_verified_domain($email) || $isVerified === 1)) {
        return 'Verified';
    }

    return 'Not Verified';
}

function format_signup_date(?string $dateTime): string
{
    if (empty($dateTime)) {
        return 'N/A';
    }

    $timestamp = strtotime($dateTime);
    if ($timestamp === false) {
        return 'N/A';
    }

    return date('d/m/Y', $timestamp);
}

function infer_year_from_id_number(?string $idNumber): ?string
{
    if (empty($idNumber)) {
        return null;
    }

    if (preg_match('/^(\d{4})/', $idNumber, $matches)) {
        return $matches[1];
    }

    return null;
}

function to_access_role_key(string $roleType, ?string $facultyRole): string
{
    $roleType = strtolower(trim($roleType));

    if ($roleType === 'admin') {
        return 'admin';
    }

    if ($roleType === 'student') {
        return 'student';
    }

    if ($roleType === 'faculty') {
        $facultyRole = strtolower(trim((string) $facultyRole));
        return match ($facultyRole) {
            'executive director' => 'executiveDirector',
            'program director' => 'programDirector',
            default => 'professor',
        };
    }

    return 'student';
}

function is_local_owner_mode(): bool
{
    $host = strtolower((string) ($_SERVER['HTTP_HOST'] ?? ''));
    $hostOnly = explode(':', $host)[0] ?? '';
    $remoteAddr = (string) ($_SERVER['REMOTE_ADDR'] ?? '');

    return in_array($hostOnly, ['localhost', '127.0.0.1'], true)
        || in_array($remoteAddr, ['127.0.0.1', '::1'], true);
}

$localOwnerMode = is_local_owner_mode();

if (empty($_SESSION['email']) && !$localOwnerMode) {
    send_json([
        'success' => false,
        'message' => 'Unauthorized access.'
    ], 401);
}

if (empty($_SESSION['email']) && $localOwnerMode) {
    return;
}

$sessionEmail = trim((string) $_SESSION['email']);
$sessionStmt = $conn->prepare('SELECT user_id, email, role_type, status, is_verified FROM users WHERE email = ? LIMIT 1');
if (!$sessionStmt) {
    send_json([
        'success' => false,
        'message' => 'Unable to validate session.',
        'error' => $conn->error
    ], 500);
}

$sessionStmt->bind_param('s', $sessionEmail);
$sessionStmt->execute();
$sessionResult = $sessionStmt->get_result();
$sessionUser = $sessionResult ? $sessionResult->fetch_assoc() : null;
$sessionStmt->close();

if (!$sessionUser && !$localOwnerMode) {
    send_json([
        'success' => false,
        'message' => 'Session user not found.'
    ], 401);
}

if (!$sessionUser && $localOwnerMode) {
    return;
}

$isTrustedAdminDomain = is_auto_verified_domain((string) $sessionUser['email']);
$isAdminApproved = (int) ($sessionUser['is_verified'] ?? 0) === 1;
$isAdminAllowed =
    strtolower((string) ($sessionUser['role_type'] ?? '')) === 'admin'
    && (int) ($sessionUser['status'] ?? 0) === 1
    && ($isTrustedAdminDomain || $isAdminApproved);

if (!$isAdminAllowed && !$localOwnerMode) {
    send_json([
        'success' => false,
        'message' => 'You do not have admin access.'
    ], 403);
}

if ($sessionUser) {
    $_SESSION['user_id'] = (int) ($sessionUser['user_id'] ?? 0);
}
