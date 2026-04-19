<?php

declare(strict_types=1);

require_once __DIR__ . '/../faculty_access_common.php';

$sessionUser = faculty_require_verified_faculty($conn);
$courseId = isset($_GET['courseId']) ? (int) $_GET['courseId'] : 0;
$programId = isset($_GET['programId']) ? (int) $_GET['programId'] : 0;

if ($courseId <= 0) {
    faculty_send_json(['success' => false, 'message' => 'Invalid course ID.'], 400);
}

if (!faculty_can_manage_course($conn, $sessionUser, $courseId)) {
    faculty_send_json(['success' => false, 'message' => 'You are not allowed to manage this course.'], 403);
}

$mappingEnabled = faculty_table_exists($conn, 'program_course_links');
$linkedProgramCount = 0;
$hasSharedLinks = false;

if ($mappingEnabled) {
    if ($programId > 0 && !faculty_can_manage_program($conn, $sessionUser, $programId)) {
        faculty_send_json(['success' => false, 'message' => 'You are not allowed to manage this course for the selected program.'], 403);
    }

    $linkStmt = $conn->prepare('SELECT COUNT(*) AS cnt FROM program_course_links WHERE course_id = ?');
    if (!$linkStmt) {
        faculty_send_json(['success' => false, 'message' => 'Failed to prepare shared link check.'], 500);
    }
    $linkStmt->bind_param('i', $courseId);
    $linkStmt->execute();
    $linkRes = $linkStmt->get_result();
    $linkRow = $linkRes ? $linkRes->fetch_assoc() : null;
    $linkedProgramCount = (int) ($linkRow['cnt'] ?? 0);
    $hasSharedLinks = $linkedProgramCount > 1;
    $linkStmt->close();
}

$stmt = $conn->prepare('SELECT COUNT(*) AS cnt FROM classes WHERE course_id = ?');
if (!$stmt) {
    faculty_send_json(['success' => false, 'message' => 'Failed to prepare statement.'], 500);
}

$stmt->bind_param('i', $courseId);
$stmt->execute();
$res = $stmt->get_result();
$row = $res ? $res->fetch_assoc() : null;
$count = (int) ($row['cnt'] ?? 0);
$stmt->close();

faculty_send_json([
    'success' => true,
    'count' => $count,
    'linkedProgramCount' => $linkedProgramCount,
    'hasSharedLinks' => $hasSharedLinks
]);
