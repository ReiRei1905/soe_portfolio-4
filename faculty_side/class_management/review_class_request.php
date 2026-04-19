<?php

declare(strict_types=1);

require_once __DIR__ . '/../faculty_access_common.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    faculty_send_json(['success' => false, 'message' => 'Method not allowed.'], 405);
}

$sessionUser = faculty_require_verified_faculty($conn);
$requestId = isset($_POST['requestId']) ? (int) $_POST['requestId'] : 0;
$action = strtolower(trim((string) ($_POST['action'] ?? '')));
$rejectionReason = trim((string) ($_POST['rejectionReason'] ?? ''));

if ($requestId <= 0 || !in_array($action, ['approve', 'reject'], true)) {
    faculty_send_json(['success' => false, 'message' => 'Invalid request payload.'], 400);
}

if (!faculty_table_exists($conn, 'class_creation_requests')) {
    faculty_send_json(['success' => false, 'message' => 'Class request workflow is not ready.'], 500);
}

$requestStmt = $conn->prepare(
    'SELECT request_id, course_id, program_id, class_name, term_number, start_year, end_year,
            requested_by_user_id, program_director_user_id, request_status
     FROM class_creation_requests
     WHERE request_id = ?
     LIMIT 1'
);

if (!$requestStmt) {
    faculty_send_json(['success' => false, 'message' => 'Failed to load class request.'], 500);
}

$requestStmt->bind_param('i', $requestId);
$requestStmt->execute();
$requestRes = $requestStmt->get_result();
$requestRow = $requestRes ? $requestRes->fetch_assoc() : null;
$requestStmt->close();

if (!$requestRow) {
    faculty_send_json(['success' => false, 'message' => 'Class request not found.'], 404);
}

if (strtolower((string) ($requestRow['request_status'] ?? '')) !== 'pending') {
    faculty_send_json(['success' => false, 'message' => 'This class request has already been reviewed.'], 409);
}

$courseIdForReview = (int) ($requestRow['course_id'] ?? 0);
if (!faculty_can_review_class_request_by_course($conn, $sessionUser, $courseIdForReview)) {
    faculty_send_json(['success' => false, 'message' => 'You are not allowed to review this class request.'], 403);
}

$reviewedByUserId = (int) ($sessionUser['user_id'] ?? 0);

if ($action === 'reject') {
    try {
        $conn->begin_transaction();

        $courseId = (int) ($requestRow['course_id'] ?? 0);
        $className = (string) ($requestRow['class_name'] ?? '');
        $termNumber = (int) ($requestRow['term_number'] ?? 0);
        $startYear = (int) ($requestRow['start_year'] ?? 0);
        $endYear = (int) ($requestRow['end_year'] ?? 0);

        $existingClassId = 0;
        $findClassStmt = $conn->prepare(
            'SELECT class_id
             FROM classes
             WHERE course_id = ?
               AND class_name = ?
               AND term_number = ?
               AND start_year = ?
               AND end_year = ?
             LIMIT 1'
        );

        if (!$findClassStmt) {
            throw new RuntimeException('Failed to check existing class for rejected request.');
        }

        $findClassStmt->bind_param('isiii', $courseId, $className, $termNumber, $startYear, $endYear);
        $findClassStmt->execute();
        $existingRes = $findClassStmt->get_result();
        $existingRow = $existingRes ? $existingRes->fetch_assoc() : null;
        $findClassStmt->close();

        if ($existingRow) {
            $existingClassId = (int) ($existingRow['class_id'] ?? 0);
        }

        if ($existingClassId <= 0) {
            $insertClassStmt = $conn->prepare(
                'INSERT INTO classes (course_id, class_name, term_number, start_year, end_year)
                 VALUES (?, ?, ?, ?, ?)'
            );

            if (!$insertClassStmt) {
                throw new RuntimeException('Failed to create class from rejected request.');
            }

            $insertClassStmt->bind_param('isiii', $courseId, $className, $termNumber, $startYear, $endYear);
            $okInsertClass = $insertClassStmt->execute();
            $existingClassId = (int) $insertClassStmt->insert_id;
            $insertClassStmt->close();

            if (!$okInsertClass || $existingClassId <= 0) {
                throw new RuntimeException('Failed to persist class after rejection.');
            }
        }

        $rejectedStatus = 'rejected';
        $updateStmt = $conn->prepare(
            'UPDATE class_creation_requests
             SET request_status = ?, reviewed_by_user_id = ?, reviewed_at = NOW(), rejection_reason = ?, approved_class_id = ?
             WHERE request_id = ?'
        );

        if (!$updateStmt) {
            throw new RuntimeException('Failed to update class request status.');
        }

        $updateStmt->bind_param('sisii', $rejectedStatus, $reviewedByUserId, $rejectionReason, $existingClassId, $requestId);
        $okReject = $updateStmt->execute();
        $updateStmt->close();

        if (!$okReject) {
            throw new RuntimeException('Failed to reject class request.');
        }

        $conn->commit();
    } catch (Throwable $error) {
        $conn->rollback();
        faculty_send_json([
            'success' => false,
            'message' => 'Failed to reject class request.',
            'error' => $error->getMessage()
        ], 500);
    }

    faculty_send_json(['success' => true, 'message' => 'Class request rejected. The class remains available for Program Director management.']);
}

try {
    $conn->begin_transaction();

    $courseId = (int) ($requestRow['course_id'] ?? 0);
    $className = (string) ($requestRow['class_name'] ?? '');
    $termNumber = (int) ($requestRow['term_number'] ?? 0);
    $startYear = (int) ($requestRow['start_year'] ?? 0);
    $endYear = (int) ($requestRow['end_year'] ?? 0);
    $requestedByUserId = (int) ($requestRow['requested_by_user_id'] ?? 0);

    $createClassStmt = $conn->prepare(
        'INSERT INTO classes (course_id, class_name, term_number, start_year, end_year)
         VALUES (?, ?, ?, ?, ?)'
    );

    if (!$createClassStmt) {
        throw new RuntimeException('Failed to prepare class creation.');
    }

    $createClassStmt->bind_param('isiii', $courseId, $className, $termNumber, $startYear, $endYear);
    $okClass = $createClassStmt->execute();
    $classId = (int) $createClassStmt->insert_id;
    $createClassStmt->close();

    if (!$okClass || $classId <= 0) {
        throw new RuntimeException('Failed to create class from approved request.');
    }

    if (faculty_table_exists($conn, 'class_professor_assignments') && $requestedByUserId > 0) {
        $assignStmt = $conn->prepare(
            'INSERT INTO class_professor_assignments (class_id, professor_user_id, assigned_by_user_id)
             VALUES (?, ?, ?)
             ON DUPLICATE KEY UPDATE
                professor_user_id = VALUES(professor_user_id),
                assigned_by_user_id = VALUES(assigned_by_user_id),
                assigned_at = CURRENT_TIMESTAMP'
        );

        if (!$assignStmt) {
            throw new RuntimeException('Failed to assign approved professor to class.');
        }

        $assignStmt->bind_param('iii', $classId, $requestedByUserId, $reviewedByUserId);
        $assignStmt->execute();
        $assignStmt->close();
    }

    $approvedStatus = 'approved';
    $updateRequestStmt = $conn->prepare(
        'UPDATE class_creation_requests
         SET request_status = ?, reviewed_by_user_id = ?, reviewed_at = NOW(), approved_class_id = ?
         WHERE request_id = ?'
    );

    if (!$updateRequestStmt) {
        throw new RuntimeException('Failed to update class request after approval.');
    }

    $updateRequestStmt->bind_param('siii', $approvedStatus, $reviewedByUserId, $classId, $requestId);
    $updateRequestStmt->execute();
    $updateRequestStmt->close();

    $conn->commit();
} catch (Throwable $error) {
    $conn->rollback();
    faculty_send_json([
        'success' => false,
        'message' => 'Failed to approve class request.',
        'error' => $error->getMessage()
    ], 500);
}

faculty_send_json([
    'success' => true,
    'message' => 'Class request approved. The professor has been assigned automatically.'
]);
