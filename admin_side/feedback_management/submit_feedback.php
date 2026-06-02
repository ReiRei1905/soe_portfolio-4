<?php

declare(strict_types=1);

session_start();
require_once __DIR__ . '/../../user_info_V3/connect.php';
require_once __DIR__ . '/../../user_info_V3/notification_service.php';

if (!isset($conn) || !($conn instanceof mysqli)) {
    send_json(['success' => false, 'message' => 'Database connection is unavailable.'], 500);
}

$subject = trim((string) ($_POST['subject'] ?? ''));
$message = trim((string) ($_POST['message'] ?? ''));

if ($subject === '' || $message === '') {
    send_json(['success' => false, 'message' => 'Subject and message are required.'], 400);
}

$userId = (int) ($_SESSION['user_id'] ?? 0);

if ($userId <= 0) {
    $email = trim((string) ($_SESSION['email'] ?? ''));
    if ($email !== '') {
        $stmt = $conn->prepare('SELECT user_id FROM users WHERE email = ? LIMIT 1');
        if ($stmt) {
            $stmt->bind_param('s', $email);
            $stmt->execute();
            $result = $stmt->get_result();
            $row = $result ? $result->fetch_assoc() : null;
            $stmt->close();
            if ($row) {
                $userId = (int) $row['user_id'];
            }
        }

        if ($userId <= 0) {
            $stmt = $conn->prepare('SELECT user_id FROM students WHERE email = ? LIMIT 1');
            if ($stmt) {
                $stmt->bind_param('s', $email);
                $stmt->execute();
                $result = $stmt->get_result();
                $row = $result ? $result->fetch_assoc() : null;
                $stmt->close();
                if ($row) {
                    $userId = (int) $row['user_id'];
                }
            }
        }

        if ($userId <= 0) {
            $stmt = $conn->prepare('SELECT user_id FROM faculty WHERE email = ? LIMIT 1');
            if ($stmt) {
                $stmt->bind_param('s', $email);
                $stmt->execute();
                $result = $stmt->get_result();
                $row = $result ? $result->fetch_assoc() : null;
                $stmt->close();
                if ($row) {
                    $userId = (int) $row['user_id'];
                }
            }
        }
    }
}

if ($userId <= 0) {
    send_json(['success' => false, 'message' => 'Unauthorized. Please log in.'], 401);
}

$userStmt = $conn->prepare(
    'SELECT u.first_name, u.last_name, u.email, u.role_type, f.faculty_role
     FROM users u
     LEFT JOIN faculty f ON f.user_id = u.user_id
     WHERE u.user_id = ?'
);
if (!$userStmt) {
    send_json(['success' => false, 'message' => 'Unable to load user profile.'], 500);
}

$userStmt->bind_param('i', $userId);
$userStmt->execute();
$user = $userStmt->get_result()->fetch_assoc();
$userStmt->close();

if (!$user) {
    send_json(['success' => false, 'message' => 'User profile not found.'], 404);
}

$fullName = trim((string) ($user['first_name'] ?? '') . ' ' . (string) ($user['last_name'] ?? ''));
$userEmail = (string) ($user['email'] ?? '');
$roleType = strtolower(trim((string) ($user['role_type'] ?? '')));
$facultyRole = strtolower(trim((string) ($user['faculty_role'] ?? '')));

$roleLabel = $roleType;
if ($roleType === 'faculty') {
    $roleLabel = $facultyRole !== '' ? $facultyRole : 'professor';
}

$roleLabel = normalize_role_label($roleLabel);

// --- UNIFIED IMAGE UPLOAD LOGIC ---
$screenshotPath = '';
$screenshotName = '';

if (!empty($_FILES['screenshot']) && $_FILES['screenshot']['error'] === UPLOAD_ERR_OK) {
    $file = $_FILES['screenshot'];
    $allowedTypes = ['image/png' => 'png', 'image/jpeg' => 'jpg'];
    $mimeType = mime_content_type($file['tmp_name']);

    if (!isset($allowedTypes[$mimeType])) {
        send_json(['success' => false, 'message' => 'Screenshot must be PNG or JPG.'], 400);
    }

    $maxSize = 5 * 1024 * 1024;
    if ((int) $file['size'] > $maxSize) {
        send_json(['success' => false, 'message' => 'Screenshot must be 5MB or less.'], 400);
    }

    $extension = $allowedTypes[$mimeType];
    $randomToken = bin2hex(random_bytes(4));
    
    // Generate a unique filename
    $fileName = "feedback_{$userId}_" . time() . "_{$randomToken}.{$extension}";

    $targetDir = __DIR__ . '/../../images/feedbacks';
    if (!is_dir($targetDir)) {
        mkdir($targetDir, 0755, true);
    }

    $targetPath = $targetDir . '/' . $fileName;
    if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
        send_json(['success' => false, 'message' => 'Unable to save screenshot.'], 500);
    }

    // Save the path exactly how your resolveScreenshotUrl() function expects it
    $screenshotPath = 'images/feedbacks/' . $fileName;
    $screenshotName = (string) ($file['name'] ?? '');
}

// --- DATABASE INSERTION ---
$insert = $conn->prepare(
    'INSERT INTO feedbacks (user_id, user_role, user_email, subject, message, screenshot_path, screenshot_name, status)
     VALUES (?, ?, ?, ?, ?, NULLIF(?, \'\'), NULLIF(?, \'\'), "new")'
);

if (!$insert) {
    send_json(['success' => false, 'message' => 'Unable to save feedback.'], 500);
}

$insert->bind_param(
    'issssss',
    $userId,
    $roleLabel,
    $userEmail,
    $subject,
    $message,
    $screenshotPath,
    $screenshotName
);

if (!$insert->execute()) {
    $insert->close();
    send_json(['success' => false, 'message' => 'Unable to save feedback.'], 500);
}

$insert->close();

// --- ADMIN NOTIFICATIONS ---
$adminStmt = $conn->prepare(
        'SELECT user_id, first_name, last_name, email
         FROM users
         WHERE role_type = "admin"
             AND status = 1
             AND (is_verified = 1 OR email LIKE "%@student.apc.edu.ph" OR email LIKE "%@apc.edu.ph")'
);

if ($adminStmt) {
    $adminStmt->execute();
    $admins = $adminStmt->get_result();
    $adminStmt->close();

    $notificationMessage = "New feedback submitted by {$fullName} ({$roleLabel}). Subject: {$subject}.";
    $emailSubject = 'New feedback submitted';
    $emailBody = "A new feedback report has been submitted. Subject: {$subject}. Message: {$message}.";

    while ($admin = $admins->fetch_assoc()) {
        $adminId = (int) ($admin['user_id'] ?? 0);
        if ($adminId > 0) {
            add_system_notification($conn, $adminId, $notificationMessage);
        }

        $adminEmail = (string) ($admin['email'] ?? '');
        $adminName = trim((string) ($admin['first_name'] ?? '') . ' ' . (string) ($admin['last_name'] ?? ''));
        send_user_email_notification($adminEmail, $adminName, $emailSubject, $emailBody);
    }
}

send_json(['success' => true, 'message' => 'Feedback submitted successfully.']);

// --- HELPER FUNCTIONS ---
function normalize_role_label(string $role): string
{
    $role = strtolower(trim($role));
    return match ($role) {
        'executive director' => 'executive director',
        'program director' => 'program director',
        'professor' => 'professor',
        'student' => 'student',
        'admin' => 'admin',
        default => $role !== '' ? $role : 'student'
    };
}

function send_json(array $payload, int $statusCode = 200): void
{
    http_response_code($statusCode);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($payload);
    exit;
}