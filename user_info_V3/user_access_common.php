<?php

declare(strict_types=1);

function is_trusted_school_email(string $email): bool
{
    $email = strtolower(trim($email));
    return preg_match('/@(student\.apc\.edu\.ph|apc\.edu\.ph)$/', $email) === 1;
}

function can_access_dashboard(array $userRow): bool
{
    $status = (int) ($userRow['status'] ?? 0);
    $roleType = strtolower(trim((string) ($userRow['role_type'] ?? '')));
    $email = (string) ($userRow['email'] ?? '');
    $isVerified = (int) ($userRow['is_verified'] ?? 0) === 1;

    if ($status !== 1) {
        return false;
    }

    if ($roleType === 'student') {
        return true;
    }

    if ($roleType === 'admin' || $roleType === 'faculty') {
        return is_trusted_school_email($email) || $isVerified;
    }

    return false;
}

function resolve_dashboard_path(array $userRow): string
{
    $roleType = strtolower(trim((string) ($userRow['role_type'] ?? '')));

    return match ($roleType) {
        'admin' => '../admin_side/admin_homepage.html',
        'student' => '../student_side/student_homepage/student_homepage.php',
        'faculty' => '../faculty_side/faculty_homepage.html',
        default => 'review_user.php',
    };
}

function resolve_effective_route(array $userRow): string
{
    if (can_access_dashboard($userRow)) {
        return resolve_dashboard_path($userRow);
    }

    return 'review_user.php';
}
