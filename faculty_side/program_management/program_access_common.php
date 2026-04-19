<?php

declare(strict_types=1);

session_start();
require_once __DIR__ . '/../../user_info_V3/connect.php';

if (!isset($conn) || !($conn instanceof mysqli)) {
    http_response_code(500);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['success' => false, 'message' => 'Database connection is unavailable.']);
    exit;
}

$conn->set_charset('utf8mb4');

function send_program_json(array $payload, int $statusCode = 200): void
{
    http_response_code($statusCode);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($payload);
    exit;
}

function is_local_owner_mode(): bool
{
    $host = strtolower((string) ($_SERVER['HTTP_HOST'] ?? ''));
    $hostOnly = explode(':', $host)[0] ?? '';
    $remoteAddr = (string) ($_SERVER['REMOTE_ADDR'] ?? '');

    return in_array($hostOnly, ['localhost', '127.0.0.1'], true)
        || in_array($remoteAddr, ['127.0.0.1', '::1'], true);
}

function resolve_local_owner_user(mysqli $conn): ?array
{
    $preferredEmail = trim((string) ($_COOKIE['local_owner_email'] ?? ''));
    if ($preferredEmail !== '' && filter_var($preferredEmail, FILTER_VALIDATE_EMAIL)) {
        $preferredStmt = $conn->prepare(
            'SELECT u.user_id, u.email, u.role_type, u.status, u.is_verified, f.faculty_role
             FROM users u
             INNER JOIN faculty f ON f.user_id = u.user_id
             WHERE u.email = ?
               AND u.role_type = "faculty"
               AND u.status = 1
               AND u.is_verified = 1
             LIMIT 1'
        );
        if ($preferredStmt) {
            $preferredStmt->bind_param('s', $preferredEmail);
            $preferredStmt->execute();
            $preferredResult = $preferredStmt->get_result();
            $preferredRow = $preferredResult ? $preferredResult->fetch_assoc() : null;
            $preferredStmt->close();
            if ($preferredRow) {
                return $preferredRow;
            }
        }
    }

    $sql =
        'SELECT u.user_id, u.email, u.role_type, u.status, u.is_verified, f.faculty_role
         FROM users u
         INNER JOIN faculty f ON f.user_id = u.user_id
         WHERE u.role_type = "faculty"
           AND u.status = 1
           AND u.is_verified = 1
         ORDER BY CASE
             WHEN LOWER(TRIM(f.faculty_role)) = "executive director" THEN 1
             WHEN LOWER(TRIM(f.faculty_role)) = "program director" THEN 2
             WHEN LOWER(TRIM(f.faculty_role)) = "professor" THEN 3
             ELSE 4
         END,
         u.user_id ASC
         LIMIT 1';

    $result = $conn->query($sql);
    if (!$result) {
        return null;
    }

    $row = $result->fetch_assoc();
    return $row ?: null;
}

function get_faculty_session_user(mysqli $conn): ?array
{
    $email = trim((string) ($_SESSION['email'] ?? ''));
    if ($email === '') {
        if (!is_local_owner_mode()) {
            return null;
        }

        $localOwnerUser = resolve_local_owner_user($conn);
        if (!$localOwnerUser) {
            return null;
        }

        $_SESSION['user_id'] = (int) ($localOwnerUser['user_id'] ?? 0);
        $_SESSION['role_type'] = (string) ($localOwnerUser['role_type'] ?? 'faculty');
        $_SESSION['is_verified'] = (int) ($localOwnerUser['is_verified'] ?? 1);

        return $localOwnerUser;
    }

    $stmt = $conn->prepare(
        'SELECT u.user_id, u.email, u.role_type, u.status, u.is_verified, f.faculty_role
         FROM users u
         LEFT JOIN faculty f ON f.user_id = u.user_id
         WHERE u.email = ?
         LIMIT 1'
    );

    if (!$stmt) {
        return null;
    }

    $stmt->bind_param('s', $email);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result ? $result->fetch_assoc() : null;
    $stmt->close();

    if ($row) {
        $_SESSION['user_id'] = (int) ($row['user_id'] ?? 0);
    }

    return $row ?: null;
}

function is_faculty_verified(array $row): bool
{
    return strtolower((string) ($row['role_type'] ?? '')) === 'faculty'
        && (int) ($row['status'] ?? 0) === 1
        && (int) ($row['is_verified'] ?? 0) === 1;
}

function is_executive_director(array $row): bool
{
    return is_faculty_verified($row)
        && strtolower(trim((string) ($row['faculty_role'] ?? ''))) === 'executive director';
}

function require_executive_director(mysqli $conn): array
{
    $sessionUser = get_faculty_session_user($conn);
    if (!$sessionUser) {
        send_program_json(['success' => false, 'message' => 'Unauthorized. Please log in.'], 401);
    }

    if (!is_executive_director($sessionUser)) {
        send_program_json(['success' => false, 'message' => 'Only Executive Directors can manage programs.'], 403);
    }

    return $sessionUser;
}
