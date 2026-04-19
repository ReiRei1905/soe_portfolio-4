<?php

declare(strict_types=1);

require_once __DIR__ . '/../faculty_access_common.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    faculty_send_json(['success' => false, 'message' => 'Method not allowed.'], 405);
}

$sessionUser = faculty_require_verified_faculty($conn);
$programId = isset($_POST['programId']) ? (int) $_POST['programId'] : 0;
$courseName = trim((string) ($_POST['courseName'] ?? ''));
$courseCode = trim((string) ($_POST['courseCode'] ?? ''));

if ($programId <= 0 || $courseName === '') {
    faculty_send_json(['success' => false, 'message' => 'Invalid program ID or course name.'], 400);
}

if (!faculty_can_manage_program($conn, $sessionUser, $programId)) {
    faculty_send_json(['success' => false, 'message' => 'You are not allowed to create courses for this program.'], 403);
}

$mappingEnabled = faculty_table_exists($conn, 'program_course_links');
$normalizedName = strtolower(trim($courseName));
$normalizedCode = strtoupper(trim($courseCode));

if ($mappingEnabled) {
    if ($normalizedCode !== '') {
        $dupStmt = $conn->prepare(
            'SELECT 1
             FROM program_course_links pcl
             INNER JOIN courses c ON c.course_id = pcl.course_id
             WHERE pcl.program_id = ?
               AND UPPER(TRIM(COALESCE(c.course_code, ""))) = ?
             LIMIT 1'
        );
        if (!$dupStmt) {
            faculty_send_json(['success' => false, 'message' => 'Failed to prepare duplicate course check.'], 500);
        }
        $dupStmt->bind_param('is', $programId, $normalizedCode);
    } else {
        $dupStmt = $conn->prepare(
            'SELECT 1
             FROM program_course_links pcl
             INNER JOIN courses c ON c.course_id = pcl.course_id
             WHERE pcl.program_id = ?
               AND LOWER(TRIM(c.course_name)) = ?
               AND COALESCE(TRIM(c.course_code), "") = ""
             LIMIT 1'
        );
        if (!$dupStmt) {
            faculty_send_json(['success' => false, 'message' => 'Failed to prepare duplicate course check.'], 500);
        }
        $dupStmt->bind_param('is', $programId, $normalizedName);
    }

    $dupStmt->execute();
    $dupRes = $dupStmt->get_result();
    $alreadyExistsInProgram = (bool) ($dupRes && $dupRes->fetch_assoc());
    $dupStmt->close();

    if ($alreadyExistsInProgram) {
        faculty_send_json(['success' => false, 'message' => 'This course already exists in the selected program.'], 409);
    }
}

if ($mappingEnabled) {
    if ($normalizedCode !== '') {
        $lookupStmt = $conn->prepare(
            'SELECT course_id FROM courses WHERE UPPER(TRIM(COALESCE(course_code, ""))) = ? LIMIT 1'
        );
        if (!$lookupStmt) {
            faculty_send_json(['success' => false, 'message' => 'Failed to prepare course lookup.'], 500);
        }

        $lookupStmt->bind_param('s', $normalizedCode);
    } else {
        $lookupStmt = $conn->prepare(
            'SELECT course_id FROM courses WHERE LOWER(TRIM(course_name)) = LOWER(TRIM(?)) AND COALESCE(TRIM(course_code), "") = "" LIMIT 1'
        );
        if (!$lookupStmt) {
            faculty_send_json(['success' => false, 'message' => 'Failed to prepare course lookup.'], 500);
        }

        $lookupStmt->bind_param('s', $courseName);
    }

    $lookupStmt->execute();
    $lookupResult = $lookupStmt->get_result();
    $existingCourse = $lookupResult ? $lookupResult->fetch_assoc() : null;
    $lookupStmt->close();

    $courseId = (int) ($existingCourse['course_id'] ?? 0);

    if ($courseId <= 0) {
        $insertStmt = $conn->prepare('INSERT INTO courses (program_id, course_name, course_code) VALUES (?, ?, ?)');
        if (!$insertStmt) {
            faculty_send_json(['success' => false, 'message' => 'Failed to prepare course insert.'], 500);
        }

        $insertStmt->bind_param('iss', $programId, $courseName, $courseCode);
        $okInsert = $insertStmt->execute();
        $courseId = (int) $insertStmt->insert_id;
        $insertStmt->close();

        if (!$okInsert || $courseId <= 0) {
            faculty_send_json(['success' => false, 'message' => 'Failed to create course.'], 500);
        }
    }

    $linkStmt = $conn->prepare(
        'INSERT INTO program_course_links (program_id, course_id, linked_by_user_id)
         VALUES (?, ?, ?)
         ON DUPLICATE KEY UPDATE linked_by_user_id = VALUES(linked_by_user_id), linked_at = CURRENT_TIMESTAMP'
    );
    if (!$linkStmt) {
        faculty_send_json(['success' => false, 'message' => 'Failed to prepare course-program link.'], 500);
    }

    $linkedByUserId = (int) ($sessionUser['user_id'] ?? 0);
    $linkStmt->bind_param('iii', $programId, $courseId, $linkedByUserId);
    $okLink = $linkStmt->execute();
    $linkStmt->close();

    if (!$okLink) {
        faculty_send_json(['success' => false, 'message' => 'Failed to link course to program.'], 500);
    }

    faculty_send_json(['success' => true, 'message' => 'Course saved successfully.', 'courseId' => $courseId]);
}

$legacyDupStmt = null;
if ($normalizedCode !== '') {
    $legacyDupStmt = $conn->prepare(
        'SELECT 1 FROM courses WHERE program_id = ? AND UPPER(TRIM(COALESCE(course_code, ""))) = ? LIMIT 1'
    );
    if (!$legacyDupStmt) {
        faculty_send_json(['success' => false, 'message' => 'Failed to prepare duplicate course check.'], 500);
    }
    $legacyDupStmt->bind_param('is', $programId, $normalizedCode);
} else {
    $legacyDupStmt = $conn->prepare(
        'SELECT 1 FROM courses WHERE program_id = ? AND LOWER(TRIM(course_name)) = ? AND COALESCE(TRIM(course_code), "") = "" LIMIT 1'
    );
    if (!$legacyDupStmt) {
        faculty_send_json(['success' => false, 'message' => 'Failed to prepare duplicate course check.'], 500);
    }
    $legacyDupStmt->bind_param('is', $programId, $normalizedName);
}

$legacyDupStmt->execute();
$legacyDupRes = $legacyDupStmt->get_result();
$legacyAlreadyExists = (bool) ($legacyDupRes && $legacyDupRes->fetch_assoc());
$legacyDupStmt->close();

if ($legacyAlreadyExists) {
    faculty_send_json(['success' => false, 'message' => 'This course already exists in the selected program.'], 409);
}

$stmt = $conn->prepare('INSERT INTO courses (program_id, course_name, course_code) VALUES (?, ?, ?)');
if (!$stmt) {
    faculty_send_json(['success' => false, 'message' => 'Failed to prepare course insert.'], 500);
}

$stmt->bind_param('iss', $programId, $courseName, $courseCode);
$ok = $stmt->execute();
$stmt->close();

if (!$ok) {
    faculty_send_json(['success' => false, 'message' => 'Failed to create course.'], 500);
}

faculty_send_json(['success' => true, 'message' => 'Course created successfully.']);
