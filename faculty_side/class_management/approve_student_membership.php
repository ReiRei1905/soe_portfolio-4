<?php

declare(strict_types=1);

require_once __DIR__ . '/../faculty_access_common.php';
require_once __DIR__ . '/../../user_info_V3/notification_service.php';

function faculty_class_label(mysqli $conn, int $classId): string
{
    $stmt = $conn->prepare('SELECT class_name, term_number, start_year, end_year FROM classes WHERE class_id = ? LIMIT 1');
    if (!$stmt) {
        return 'this class';
    }

    $stmt->bind_param('i', $classId);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$row) {
        return 'this class';
    }

    $className = trim((string) ($row['class_name'] ?? ''));
    $term = trim((string) ($row['term_number'] ?? ''));
    $startYear = trim((string) ($row['start_year'] ?? ''));
    $endYear = trim((string) ($row['end_year'] ?? ''));
    $years = $startYear !== '' && $endYear !== '' ? $startYear . '-' . $endYear : '';

    if ($className === '') {
        return 'this class';
    }

    if ($years !== '' && (stripos($className, 'term') !== false || strpos($className, $years) !== false)) {
        return $className;
    }

    $suffix = trim('Term ' . $term . ' ' . $years);
    return trim($className . ' ' . $suffix);
}

function student_user_id_from_student(mysqli $conn, int $studentId): int
{
    $stmt = $conn->prepare('SELECT user_id FROM students WHERE student_id = ? LIMIT 1');
    if (!$stmt) {
        return 0;
    }

    $stmt->bind_param('i', $studentId);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    return (int) ($row['user_id'] ?? 0);
}

function student_contact_from_student(mysqli $conn, int $studentId): array
{
    $stmt = $conn->prepare('SELECT user_id, first_name, last_name, email FROM students WHERE student_id = ? LIMIT 1');
    if (!$stmt) {
        return ['user_id' => 0, 'full_name' => '', 'email' => ''];
    }

    $stmt->bind_param('i', $studentId);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$row) {
        return ['user_id' => 0, 'full_name' => '', 'email' => ''];
    }

    return [
        'user_id' => (int) ($row['user_id'] ?? 0),
        'full_name' => trim((string) (($row['first_name'] ?? '') . ' ' . ($row['last_name'] ?? ''))),
        'email' => trim((string) ($row['email'] ?? ''))
    ];
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    faculty_send_json(['success' => false, 'message' => 'Method not allowed.'], 405);
}

$sessionUser = faculty_require_verified_faculty($conn);
$classId = isset($_POST['class_id']) ? (int) $_POST['class_id'] : 0;
$studentId = isset($_POST['student_id']) ? (int) $_POST['student_id'] : 0;
$actorUserId = (int) ($sessionUser['user_id'] ?? 0);

if ($classId <= 0 || $studentId <= 0) {
    faculty_send_json(['success' => false, 'message' => 'Missing class or student reference.'], 400);
}

if (!faculty_can_handle_class($conn, $sessionUser, $classId)) {
    faculty_send_json(['success' => false, 'message' => 'You are not allowed to approve enrollments for this class.'], 403);
}

if (!faculty_table_exists($conn, 'class_students')) {
    faculty_send_json([
        'success' => false,
        'message' => 'Table class_students is missing. Apply the SQL migration first.'
    ], 500);
}

$sql = 'UPDATE class_students
        SET status = \'approved\',
            approved_by_user_id = ?,
            approved_at = NOW(),
            removed_at = NULL,
            updated_at = NOW()
        WHERE class_id = ?
          AND student_id = ?';

$stmt = $conn->prepare($sql);
if (!$stmt) {
    faculty_send_json(['success' => false, 'message' => 'Failed to prepare approval statement.'], 500);
}

$stmt->bind_param('iii', $actorUserId, $classId, $studentId);
$stmt->execute();
$affected = $stmt->affected_rows;
$stmt->close();

if ($affected <= 0) {
    faculty_send_json(['success' => false, 'message' => 'No pending member found for approval.'], 404);
}

$studentContact = student_contact_from_student($conn, $studentId);
$studentUserId = (int) ($studentContact['user_id'] ?? 0);
if ($studentUserId > 0) {
    $classLabel = faculty_class_label($conn, $classId);
    $message = sprintf('You are added to the %s class.', $classLabel);
    add_system_notification($conn, $studentUserId, $message);

    $studentEmail = (string) ($studentContact['email'] ?? '');
    if ($studentEmail !== '') {
        send_user_email_notification(
            $studentEmail,
            (string) ($studentContact['full_name'] ?? ''),
            'Class Enrollment Approved',
            $message . ' You can now open and submit outputs in this class.'
        );
    }
}

faculty_send_json(['success' => true, 'message' => 'Student membership approved.']);
