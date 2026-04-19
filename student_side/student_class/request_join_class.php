<?php

declare(strict_types=1);

require_once __DIR__ . '/../api/common.php';
require_once __DIR__ . '/../../user_info_V3/notification_service.php';

function class_label_for_notification(mysqli $conn, int $classId): string
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

function student_name_for_notification(mysqli $conn, int $studentId): string
{
    $stmt = $conn->prepare('SELECT first_name, last_name, email FROM students WHERE student_id = ? LIMIT 1');
    if (!$stmt) {
        return 'A student';
    }

    $stmt->bind_param('i', $studentId);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$row) {
        return 'A student';
    }

    $fullName = trim((string) (($row['first_name'] ?? '') . ' ' . ($row['last_name'] ?? '')));
    if ($fullName !== '') {
        return $fullName;
    }

    $email = trim((string) ($row['email'] ?? ''));
    return $email !== '' ? $email : 'A student';
}

function notify_assigned_professors_of_request(mysqli $conn, int $classId, int $studentId): void
{
    $stmt = $conn->prepare('SELECT DISTINCT professor_user_id FROM class_professor_assignments WHERE class_id = ?');
    if (!$stmt) {
        return;
    }

    $stmt->bind_param('i', $classId);
    $stmt->execute();
    $result = $stmt->get_result();

    $professorUserIds = [];
    while ($result && ($row = $result->fetch_assoc())) {
        $userId = (int) ($row['professor_user_id'] ?? 0);
        if ($userId > 0) {
            $professorUserIds[] = $userId;
        }
    }

    $stmt->close();

    if (empty($professorUserIds)) {
        return;
    }

    $studentName = student_name_for_notification($conn, $studentId);
    $classLabel = class_label_for_notification($conn, $classId);
    $message = sprintf('%s requested to join your class %s.', $studentName, $classLabel);
    $subject = 'New Class Enrollment Request';
    $emailBody = sprintf('%s requested to join your class %s. Please review the request in the class management page.', $studentName, $classLabel);

    $contactStmt = $conn->prepare('SELECT first_name, last_name, email FROM users WHERE user_id = ? LIMIT 1');

    foreach (array_values(array_unique($professorUserIds)) as $professorUserId) {
        add_system_notification($conn, $professorUserId, $message);

        if ($contactStmt) {
            $contactStmt->bind_param('i', $professorUserId);
            $contactStmt->execute();
            $contact = $contactStmt->get_result()->fetch_assoc();
            if ($contact) {
                $email = trim((string) ($contact['email'] ?? ''));
                $fullName = trim((string) (($contact['first_name'] ?? '') . ' ' . ($contact['last_name'] ?? '')));
                if ($email !== '') {
                    send_user_email_notification($email, $fullName, $subject, $emailBody);
                }
            }
        }
    }

    if ($contactStmt) {
        $contactStmt->close();
    }
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(405, ['success' => false, 'message' => 'Method not allowed.']);
}

$conn = db_connect();

if (!isset($_SESSION['email']) || trim((string) $_SESSION['email']) === '') {
    json_response(401, ['success' => false, 'message' => 'Unauthorized. Please log in.']);
}

$classId = isset($_POST['class_id']) ? (int) $_POST['class_id'] : 0;
$studentId = current_student_id($conn);

if ($classId <= 0) {
    json_response(400, ['success' => false, 'message' => 'Invalid class reference.']);
}

$classStudentsExists = (bool) $conn->query("SHOW TABLES LIKE 'class_students'")->fetch_assoc();
if (!$classStudentsExists) {
    json_response(500, ['success' => false, 'message' => 'Table class_students is missing. Apply SQL migration first.']);
}

$checkStmt = $conn->prepare('SELECT status FROM class_students WHERE class_id = ? AND student_id = ? LIMIT 1');
$checkStmt->bind_param('ii', $classId, $studentId);
$checkStmt->execute();
$existing = $checkStmt->get_result()->fetch_assoc();
$checkStmt->close();

if ($existing) {
    $status = (string) ($existing['status'] ?? '');
    if ($status === 'approved') {
        json_response(200, ['success' => true, 'status' => 'approved', 'message' => 'You are already enrolled in this class.']);
    }

    if ($status === 'pending') {
        json_response(200, ['success' => true, 'status' => 'pending', 'message' => 'Your enrollment is still pending approval.']);
    }

    $updateStmt = $conn->prepare('UPDATE class_students
                                  SET invitation_source = \'requested\',
                                      status = \'pending\',
                                      requested_at = NOW(),
                                      invited_at = NULL,
                                      approved_at = NULL,
                                      removed_at = NULL,
                                      updated_at = NOW()
                                  WHERE class_id = ? AND student_id = ?');
    $updateStmt->bind_param('ii', $classId, $studentId);
    $updateStmt->execute();
    $updateStmt->close();

    notify_assigned_professors_of_request($conn, $classId, $studentId);

    json_response(200, ['success' => true, 'status' => 'pending', 'message' => 'Enrollment request submitted.']);
}

$insertStmt = $conn->prepare('INSERT INTO class_students (
                                class_id, student_id, invitation_source, status,
                                requested_at, created_at, updated_at
                              ) VALUES (?, ?, \'requested\', \'pending\', NOW(), NOW(), NOW())');
$insertStmt->bind_param('ii', $classId, $studentId);
$insertStmt->execute();
$insertStmt->close();

notify_assigned_professors_of_request($conn, $classId, $studentId);

json_response(200, ['success' => true, 'status' => 'pending', 'message' => 'Enrollment request submitted.']);
