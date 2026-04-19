<?php

declare(strict_types=1);

require_once __DIR__ . '/../faculty_access_common.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    faculty_send_json(['success' => false, 'message' => 'Method not allowed.'], 405);
}

$sessionUser = faculty_require_verified_faculty($conn);
$courseId = isset($_POST['courseId']) ? (int) $_POST['courseId'] : 0;
$className = trim((string) ($_POST['className'] ?? ''));
$termNumber = isset($_POST['termNumber']) ? (int) $_POST['termNumber'] : 0;
$startYear = isset($_POST['startYear']) ? (int) $_POST['startYear'] : 0;
$endYear = isset($_POST['endYear']) ? (int) $_POST['endYear'] : 0;

if ($courseId <= 0 || $className === '' || $termNumber <= 0 || $startYear <= 0 || $endYear <= 0) {
    faculty_send_json(['success' => false, 'message' => 'Invalid input data.'], 400);
}

$canManageCourseDirectly = faculty_can_manage_course($conn, $sessionUser, $courseId);
$isProfessorRequester = faculty_is_active_professor($sessionUser);

if (!$canManageCourseDirectly && !$isProfessorRequester) {
    faculty_send_json(['success' => false, 'message' => 'You are not allowed to create classes for this course.'], 403);
}

$courseProgramId = faculty_get_program_id_by_course($conn, $courseId);
if ($courseProgramId === null || $courseProgramId <= 0) {
    faculty_send_json(['success' => false, 'message' => 'Unable to resolve program for the selected course.'], 400);
}

$courseStmt = $conn->prepare('SELECT course_code FROM courses WHERE course_id = ? LIMIT 1');
if (!$courseStmt) {
    faculty_send_json(['success' => false, 'message' => 'Failed to validate course.'], 500);
}

$courseStmt->bind_param('i', $courseId);
$courseStmt->execute();
$courseRes = $courseStmt->get_result();
$courseRow = $courseRes ? $courseRes->fetch_assoc() : null;
$courseStmt->close();

if (!$courseRow) {
    faculty_send_json(['success' => false, 'message' => 'Course not found.'], 404);
}

$courseCode = trim((string) ($courseRow['course_code'] ?? ''));
$inputClassName = $className;
if ($courseCode !== '' && stripos($inputClassName, $courseCode) === 0) {
    $inputClassName = trim(substr($inputClassName, strlen($courseCode)));
}

$assembledClassName = trim(($courseCode !== '' ? $courseCode . ' ' : '') . $inputClassName);
$assembledClassName .= ' Term ' . $termNumber . ' ' . $startYear . '-' . $endYear;

if ($isProfessorRequester && !$canManageCourseDirectly) {
    if (!faculty_table_exists($conn, 'class_creation_requests')) {
        faculty_send_json([
            'success' => false,
            'message' => 'Class request workflow is not ready. Please run the class request migration SQL first.'
        ], 500);
    }

    if (!faculty_table_exists($conn, 'program_director_assignments')) {
        faculty_send_json([
            'success' => false,
            'message' => 'No Program Director assignment table found. Unable to route this class request for approval.'
        ], 500);
    }

    $directorStmt = $conn->prepare(
        'SELECT pda.program_director_user_id,
                CONCAT(COALESCE(f.first_name, u.first_name), " ", COALESCE(f.last_name, u.last_name)) AS program_director_name
         FROM program_director_assignments pda
         LEFT JOIN users u ON u.user_id = pda.program_director_user_id
         LEFT JOIN faculty f ON f.user_id = u.user_id
         WHERE pda.program_id = ?
         LIMIT 1'
    );

    if (!$directorStmt) {
        faculty_send_json(['success' => false, 'message' => 'Failed to load assigned Program Director.'], 500);
    }

    $directorStmt->bind_param('i', $courseProgramId);
    $directorStmt->execute();
    $directorRes = $directorStmt->get_result();
    $directorRow = $directorRes ? $directorRes->fetch_assoc() : null;
    $directorStmt->close();

    $programDirectorUserId = (int) ($directorRow['program_director_user_id'] ?? 0);
    if ($programDirectorUserId <= 0) {
        faculty_send_json([
            'success' => false,
            'message' => 'No Program Director is currently assigned to this course program. Please wait for assignment first.'
        ], 400);
    }

    $dupStmt = $conn->prepare(
        'SELECT 1
         FROM class_creation_requests
         WHERE course_id = ?
           AND class_name = ?
           AND term_number = ?
           AND start_year = ?
           AND end_year = ?
           AND request_status = ?
         LIMIT 1'
    );

    if (!$dupStmt) {
        faculty_send_json(['success' => false, 'message' => 'Failed to validate duplicate class request.'], 500);
    }

    $pendingStatus = 'pending';
    $dupStmt->bind_param('isiiis', $courseId, $assembledClassName, $termNumber, $startYear, $endYear, $pendingStatus);
    $dupStmt->execute();
    $dupRes = $dupStmt->get_result();
    $hasPendingDuplicate = (bool) ($dupRes && $dupRes->fetch_assoc());
    $dupStmt->close();

    if ($hasPendingDuplicate) {
        faculty_send_json(['success' => false, 'message' => 'A pending request already exists for this class.'], 409);
    }

    $requesterId = (int) ($sessionUser['user_id'] ?? 0);
    $insertRequestStmt = $conn->prepare(
        'INSERT INTO class_creation_requests
            (course_id, program_id, class_name, term_number, start_year, end_year, requested_by_user_id, program_director_user_id, request_status)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)' 
    );

    if (!$insertRequestStmt) {
        faculty_send_json(['success' => false, 'message' => 'Failed to prepare class approval request.'], 500);
    }

    $insertRequestStmt->bind_param(
        'iisiiiiis',
        $courseId,
        $courseProgramId,
        $assembledClassName,
        $termNumber,
        $startYear,
        $endYear,
        $requesterId,
        $programDirectorUserId,
        $pendingStatus
    );
    $okRequest = $insertRequestStmt->execute();
    $insertRequestStmt->close();

    if (!$okRequest) {
        faculty_send_json(['success' => false, 'message' => 'Failed to submit class request for approval.'], 500);
    }

    $programDirectorName = trim((string) ($directorRow['program_director_name'] ?? ''));
    faculty_send_json([
        'success' => true,
        'message' => $programDirectorName !== ''
            ? "Class request submitted. Waiting for approval from Program Director: {$programDirectorName}."
            : 'Class request submitted. Waiting for Program Director approval.',
        'pendingApproval' => true
    ]);
}

$stmt = $conn->prepare('INSERT INTO classes (course_id, class_name, term_number, start_year, end_year) VALUES (?, ?, ?, ?, ?)');
if (!$stmt) {
    faculty_send_json(['success' => false, 'message' => 'Failed to prepare class insert.'], 500);
}

$stmt->bind_param('isiii', $courseId, $assembledClassName, $termNumber, $startYear, $endYear);
$ok = $stmt->execute();
$stmt->close();

if (!$ok) {
    faculty_send_json(['success' => false, 'message' => 'Failed to create class.'], 500);
}

faculty_send_json(['success' => true, 'message' => 'Class created successfully', 'class_name' => $assembledClassName]);
