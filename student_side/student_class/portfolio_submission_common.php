<?php

declare(strict_types=1);

require_once __DIR__ . '/../api/common.php';
require_once __DIR__ . '/../../user_info_V3/notification_service.php';

function require_approved_student_in_class(mysqli $conn, int $classId, int $studentId): void
{
    if ($classId <= 0 || $studentId <= 0) {
        json_response(400, ['success' => false, 'message' => 'Invalid class or student reference.']);
    }

    $memberStmt = $conn->prepare('SELECT status FROM class_students WHERE class_id = ? AND student_id = ? LIMIT 1');
    if (!$memberStmt) {
        json_response(500, ['success' => false, 'message' => 'Failed to validate class membership.']);
    }

    $memberStmt->bind_param('ii', $classId, $studentId);
    $memberStmt->execute();
    $memberRow = $memberStmt->get_result()->fetch_assoc();
    $memberStmt->close();

    if (!$memberRow || (string) ($memberRow['status'] ?? '') !== 'approved') {
        json_response(403, ['success' => false, 'message' => 'You are not enrolled in this class.']);
    }
}

function assert_portfolio_submission_table_exists(mysqli $conn): void
{
    $exists = (bool) $conn->query("SHOW TABLES LIKE 'class_portfolio_submissions'")->fetch_assoc();
    if (!$exists) {
        json_response(500, [
            'success' => false,
            'message' => 'Table class_portfolio_submissions is missing. Apply SQL migration first.'
        ]);
    }
}

function get_class_name_by_id(mysqli $conn, int $classId): string
{
    $stmt = $conn->prepare('SELECT class_name FROM classes WHERE class_id = ? LIMIT 1');
    if (!$stmt) {
        return 'Unknown class';
    }

    $stmt->bind_param('i', $classId);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    $className = trim((string) ($row['class_name'] ?? ''));
    return $className !== '' ? $className : 'Unknown class';
}

function get_student_display_name(mysqli $conn, int $studentId): string
{
    $sql = 'SELECT
                TRIM(COALESCE(NULLIF(CONCAT(s.first_name, " ", s.last_name), ""), CONCAT(u.first_name, " ", u.last_name), "Student")) AS student_name
            FROM students s
            LEFT JOIN users u ON u.user_id = s.user_id
            WHERE s.student_id = ?
            LIMIT 1';

    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        return 'Student';
    }

    $stmt->bind_param('i', $studentId);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    $name = trim((string) ($row['student_name'] ?? ''));
    return $name !== '' ? $name : 'Student';
}

function notify_assigned_professor_for_portfolio_event(
    mysqli $conn,
    int $classId,
    string $studentDisplayName,
    string $className,
    bool $isUndo
): void {
    $sql = 'SELECT
                cpa.professor_user_id,
                TRIM(COALESCE(NULLIF(CONCAT(f.first_name, " ", f.last_name), ""), CONCAT(u.first_name, " ", u.last_name), "Professor")) AS professor_name,
                u.email AS professor_email
            FROM class_professor_assignments cpa
            LEFT JOIN users u ON u.user_id = cpa.professor_user_id
            LEFT JOIN faculty f ON f.user_id = cpa.professor_user_id
            WHERE cpa.class_id = ?
            LIMIT 1';

    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        return;
    }

    $stmt->bind_param('i', $classId);
    $stmt->execute();
    $professorRow = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    $professorUserId = (int) ($professorRow['professor_user_id'] ?? 0);
    if ($professorUserId <= 0) {
        return;
    }

    $professorName = trim((string) ($professorRow['professor_name'] ?? 'Professor'));
    $professorEmail = trim((string) ($professorRow['professor_email'] ?? ''));

    $notificationMessage = $isUndo
        ? sprintf('%s has undo their submission of portfolio in class %s.', $studentDisplayName, $className)
        : sprintf('%s has submitted a portfolio in class %s.', $studentDisplayName, $className);

    add_system_notification($conn, $professorUserId, $notificationMessage);

    if ($professorEmail !== '') {
        $subject = $isUndo
            ? 'Portfolio submission undone'
            : 'Portfolio submitted';
        send_user_email_notification($professorEmail, $professorName, $subject, $notificationMessage);
    }
}
