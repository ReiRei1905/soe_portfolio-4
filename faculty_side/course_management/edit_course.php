<?php

declare(strict_types=1);

require_once __DIR__ . '/../faculty_access_common.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    faculty_send_json(['success' => false, 'message' => 'Method not allowed.'], 405);
}

$sessionUser = faculty_require_verified_faculty($conn);
$courseId = isset($_POST['courseId']) ? (int) $_POST['courseId'] : 0;
$programId = isset($_POST['programId']) ? (int) $_POST['programId'] : 0;
$newCourseName = trim((string) ($_POST['newCourseName'] ?? ''));
$newCourseCode = trim((string) ($_POST['newCourseCode'] ?? ''));

if ($courseId <= 0 || $newCourseName === '') {
    faculty_send_json(['success' => false, 'message' => 'Invalid input.'], 400);
}

if (!faculty_can_manage_course($conn, $sessionUser, $courseId)) {
    faculty_send_json(['success' => false, 'message' => 'You are not allowed to edit this course.'], 403);
}

$mappingEnabled = faculty_table_exists($conn, 'program_course_links');

if ($mappingEnabled && $programId > 0) {
    if (!faculty_can_manage_program($conn, $sessionUser, $programId)) {
        faculty_send_json(['success' => false, 'message' => 'You are not allowed to edit this course for the selected program.'], 403);
    }

    $courseStmt = $conn->prepare('SELECT course_name, COALESCE(course_code, "") AS course_code FROM courses WHERE course_id = ? LIMIT 1');
    if (!$courseStmt) {
        faculty_send_json(['success' => false, 'message' => 'Failed to load current course details.'], 500);
    }
    $courseStmt->bind_param('i', $courseId);
    $courseStmt->execute();
    $courseRes = $courseStmt->get_result();
    $courseRow = $courseRes ? $courseRes->fetch_assoc() : null;
    $courseStmt->close();

    if (!$courseRow) {
        faculty_send_json(['success' => false, 'message' => 'Course not found.'], 404);
    }

    $oldName = trim((string) ($courseRow['course_name'] ?? ''));
    $oldCode = strtoupper(trim((string) ($courseRow['course_code'] ?? '')));
    $normalizedNewCode = strtoupper($newCourseCode);
    $hasChanged = ($oldName !== $newCourseName) || ($oldCode !== $normalizedNewCode);

    if (!$hasChanged) {
        faculty_send_json(['success' => true, 'message' => 'No changes detected.']);
    }

    $linkCountStmt = $conn->prepare('SELECT COUNT(*) AS cnt FROM program_course_links WHERE course_id = ?');
    if (!$linkCountStmt) {
        faculty_send_json(['success' => false, 'message' => 'Failed to check course link usage.'], 500);
    }
    $linkCountStmt->bind_param('i', $courseId);
    $linkCountStmt->execute();
    $linkRes = $linkCountStmt->get_result();
    $linkRow = $linkRes ? $linkRes->fetch_assoc() : null;
    $linkedProgramCount = (int) ($linkRow['cnt'] ?? 0);
    $linkCountStmt->close();

    $linkCheckStmt = $conn->prepare('SELECT 1 FROM program_course_links WHERE program_id = ? AND course_id = ? LIMIT 1');
    if (!$linkCheckStmt) {
        faculty_send_json(['success' => false, 'message' => 'Failed to validate program-course link.'], 500);
    }
    $linkCheckStmt->bind_param('ii', $programId, $courseId);
    $linkCheckStmt->execute();
    $linkCheckRes = $linkCheckStmt->get_result();
    $isLinkedToProgram = (bool) ($linkCheckRes && $linkCheckRes->fetch_assoc());
    $linkCheckStmt->close();

    if (!$isLinkedToProgram) {
        faculty_send_json(['success' => false, 'message' => 'Selected course is not linked to this program.'], 400);
    }

    if ($linkedProgramCount > 1) {
        try {
            $conn->begin_transaction();

            $insertStmt = $conn->prepare('INSERT INTO courses (program_id, course_name, course_code) VALUES (?, ?, ?)');
            if (!$insertStmt) {
                throw new RuntimeException('Failed to prepare course split insert.');
            }
            $insertStmt->bind_param('iss', $programId, $newCourseName, $normalizedNewCode);
            $okInsert = $insertStmt->execute();
            $newCourseId = (int) $insertStmt->insert_id;
            $insertStmt->close();

            if (!$okInsert || $newCourseId <= 0) {
                throw new RuntimeException('Failed to create program-specific course copy.');
            }

            $unlinkStmt = $conn->prepare('DELETE FROM program_course_links WHERE program_id = ? AND course_id = ?');
            if (!$unlinkStmt) {
                throw new RuntimeException('Failed to prepare unlink statement.');
            }
            $unlinkStmt->bind_param('ii', $programId, $courseId);
            $unlinkStmt->execute();
            $unlinkStmt->close();

            $linkedBy = (int) ($sessionUser['user_id'] ?? 0);
            $linkStmt = $conn->prepare(
                'INSERT INTO program_course_links (program_id, course_id, linked_by_user_id)
                 VALUES (?, ?, ?)
                 ON DUPLICATE KEY UPDATE linked_by_user_id = VALUES(linked_by_user_id), linked_at = CURRENT_TIMESTAMP'
            );
            if (!$linkStmt) {
                throw new RuntimeException('Failed to prepare link insert for split course.');
            }
            $linkStmt->bind_param('iii', $programId, $newCourseId, $linkedBy);
            $linkStmt->execute();
            $linkStmt->close();

            $conn->commit();
        } catch (Throwable $e) {
            $conn->rollback();
            faculty_send_json(['success' => false, 'message' => 'Failed to update shared course for this program.'], 500);
        }

        faculty_send_json(['success' => true, 'message' => 'Course updated for this program only.']);
    }
}

$stmt = $conn->prepare('UPDATE courses SET course_name = ?, course_code = ? WHERE course_id = ?');
if (!$stmt) {
    faculty_send_json(['success' => false, 'message' => 'Failed to prepare update statement.'], 500);
}

$stmt->bind_param('ssi', $newCourseName, $newCourseCode, $courseId);
$ok = $stmt->execute();
$stmt->close();

if (!$ok) {
    faculty_send_json(['success' => false, 'message' => 'Failed to update course.'], 500);
}

faculty_send_json(['success' => true, 'message' => 'Course updated successfully.']);
