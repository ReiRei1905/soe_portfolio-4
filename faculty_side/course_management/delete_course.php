<?php

declare(strict_types=1);

require_once __DIR__ . '/../faculty_access_common.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    faculty_send_json(['success' => false, 'message' => 'Method not allowed.'], 405);
}

$sessionUser = faculty_require_verified_faculty($conn);
$courseId = isset($_POST['courseId']) ? (int) $_POST['courseId'] : 0;
$programId = isset($_POST['programId']) ? (int) $_POST['programId'] : 0;

if ($courseId <= 0) {
    faculty_send_json(['success' => false, 'message' => 'Invalid course ID.'], 400);
}

if (!faculty_can_manage_course($conn, $sessionUser, $courseId)) {
    faculty_send_json(['success' => false, 'message' => 'You are not allowed to delete this course.'], 403);
}

$mappingEnabled = faculty_table_exists($conn, 'program_course_links');
if ($mappingEnabled && $programId > 0) {
    if (!faculty_can_manage_program($conn, $sessionUser, $programId)) {
        faculty_send_json(['success' => false, 'message' => 'You are not allowed to remove this course from the selected program.'], 403);
    }

    $linkCountStmt = $conn->prepare('SELECT COUNT(*) AS cnt FROM program_course_links WHERE course_id = ?');
    if (!$linkCountStmt) {
        faculty_send_json(['success' => false, 'message' => 'Failed to prepare shared course link count check.'], 500);
    }
    $linkCountStmt->bind_param('i', $courseId);
    $linkCountStmt->execute();
    $linkRes = $linkCountStmt->get_result();
    $linkRow = $linkRes ? $linkRes->fetch_assoc() : null;
    $linkedProgramCount = (int) ($linkRow['cnt'] ?? 0);
    $linkCountStmt->close();

    if ($linkedProgramCount > 1) {
        $unlinkStmt = $conn->prepare('DELETE FROM program_course_links WHERE course_id = ? AND program_id = ?');
        if (!$unlinkStmt) {
            faculty_send_json(['success' => false, 'message' => 'Failed to prepare course unlink statement.'], 500);
        }

        $unlinkStmt->bind_param('ii', $courseId, $programId);
        $okUnlink = $unlinkStmt->execute();
        $affectedRows = $unlinkStmt->affected_rows;
        $unlinkStmt->close();

        if (!$okUnlink) {
            faculty_send_json(['success' => false, 'message' => 'Failed to unlink course from selected program.'], 500);
        }

        if ($affectedRows <= 0) {
            faculty_send_json(['success' => false, 'message' => 'Course is not linked to the selected program.'], 400);
        }

        faculty_send_json(['success' => true, 'message' => 'Course removed from this program.']);
    }
}

$checkStmt = $conn->prepare('SELECT COUNT(*) AS cnt FROM classes WHERE course_id = ?');
if (!$checkStmt) {
    faculty_send_json(['success' => false, 'message' => 'Failed to prepare dependency check.'], 500);
}

$checkStmt->bind_param('i', $courseId);
$checkStmt->execute();
$res = $checkStmt->get_result();
$row = $res ? $res->fetch_assoc() : null;
$count = (int) ($row['cnt'] ?? 0);
$checkStmt->close();

if ($count > 0) {
    faculty_send_json(['success' => false, 'message' => "Cannot delete course: it is used by {$count} class(es). Please remove or reassign those classes first."]);
}

$stmt = $conn->prepare('DELETE FROM courses WHERE course_id = ?');
if (!$stmt) {
    faculty_send_json(['success' => false, 'message' => 'Failed to prepare delete statement.'], 500);
}

$stmt->bind_param('i', $courseId);
$ok = $stmt->execute();
$stmt->close();

if (!$ok) {
    faculty_send_json(['success' => false, 'message' => 'Failed to delete course.'], 500);
}

faculty_send_json(['success' => true, 'message' => 'Course deleted successfully']);
