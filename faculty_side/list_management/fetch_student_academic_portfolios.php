<?php

declare(strict_types=1);

require_once __DIR__ . '/list_access_common.php';

$sessionUser = list_require_access($conn);
$listId = isset($_GET['listId']) ? (int) $_GET['listId'] : 0;
$studentId = isset($_GET['studentId']) ? (int) $_GET['studentId'] : 0;

if ($listId <= 0 || $studentId <= 0) {
    faculty_send_json(['success' => false, 'message' => 'Invalid list or student reference.'], 400);
}

if (!faculty_table_exists($conn, 'students') || !faculty_table_exists($conn, 'classes') || !faculty_table_exists($conn, 'class_students')) {
    faculty_send_json(['success' => false, 'message' => 'Required class/student tables are missing.'], 500);
}

if (!faculty_table_exists($conn, 'class_outputs') || !faculty_table_exists($conn, 'output_submissions')) {
    faculty_send_json(['success' => false, 'message' => 'Required class output tables are missing.'], 500);
}

if (!faculty_table_exists($conn, 'class_portfolio_submissions') || !faculty_table_exists($conn, 'class_portfolio_reviews')) {
    faculty_send_json(['success' => false, 'message' => 'Portfolio tracking tables are missing.'], 500);
}

$schema = list_require_student_lists_schema($conn);
$yearColumn = list_resolve_student_lists_year_column($conn, $schema);

$listStmt = $conn->prepare(
    'SELECT sl.' . $schema['listId'] . ' AS list_id, sl.' . $schema['programId'] . ' AS program_id, sl.' . $schema['batchName'] . ' AS batch_name, sl.' . $yearColumn . ' AS year_of_enrollment, p.program_name
     FROM student_lists sl
     INNER JOIN programs p ON p.program_id = sl.' . $schema['programId'] . '
     WHERE sl.' . $schema['listId'] . ' = ?
     LIMIT 1'
);
if (!$listStmt) {
    faculty_send_json(['success' => false, 'message' => 'Failed to prepare list query.'], 500);
}

$listStmt->bind_param('i', $listId);
$listStmt->execute();
$listRow = $listStmt->get_result()->fetch_assoc();
$listStmt->close();

if (!$listRow) {
    faculty_send_json(['success' => false, 'message' => 'List not found.'], 404);
}

$programId = (int) ($listRow['program_id'] ?? 0);
$yearOfEnrollment = (int) ($listRow['year_of_enrollment'] ?? 0);

if (!list_can_manage_program($conn, $sessionUser, $programId)) {
    faculty_send_json(['success' => false, 'message' => 'You are not allowed to access this list.'], 403);
}

$studentStmt = $conn->prepare(
    'SELECT s.student_id,
            s.id_number,
            s.program_id,
            s.year_of_enrollment,
            COALESCE(s.first_name, u.first_name) AS first_name,
            COALESCE(s.last_name, u.last_name) AS last_name,
            COALESCE(s.email, u.email) AS email,
            u.created_at AS joined_at
     FROM students s
     LEFT JOIN users u ON u.user_id = s.user_id
     WHERE s.student_id = ?
     LIMIT 1'
);
if (!$studentStmt) {
    faculty_send_json(['success' => false, 'message' => 'Failed to prepare student query.'], 500);
}

$studentStmt->bind_param('i', $studentId);
$studentStmt->execute();
$studentRow = $studentStmt->get_result()->fetch_assoc();
$studentStmt->close();

if (!$studentRow) {
    faculty_send_json(['success' => false, 'message' => 'Student not found.'], 404);
}

if ((int) ($studentRow['program_id'] ?? 0) !== $programId || (int) ($studentRow['year_of_enrollment'] ?? 0) !== $yearOfEnrollment) {
    faculty_send_json(['success' => false, 'message' => 'This student is not part of the selected list scope.'], 403);
}

$hasRejectionReasonColumn = false;
$columnStmt = $conn->prepare('SELECT 1
                              FROM information_schema.columns
                              WHERE table_schema = DATABASE()
                                AND table_name = "class_portfolio_reviews"
                                AND column_name = "rejection_reason"
                              LIMIT 1');
if ($columnStmt) {
    $columnStmt->execute();
    $hasRejectionReasonColumn = (bool) ($columnStmt->get_result()->fetch_assoc() ?: null);
    $columnStmt->close();
}

$classSql = 'SELECT
                c.class_id,
                c.class_name,
                c.term_number,
                c.start_year,
                c.end_year,
                COALESCE(crs.course_name, c.class_name) AS course_name,
                cs.approved_at,
                cps.status AS portfolio_status,
                cps.submitted_at AS portfolio_submitted_at,
                cpr.decision AS review_decision,
                cpr.final_grade,
                cpr.final_percentage,
                cpr.reviewed_at';

if ($hasRejectionReasonColumn) {
    $classSql .= ', cpr.rejection_reason';
}

$classSql .= ' FROM class_students cs
               INNER JOIN classes c ON c.class_id = cs.class_id
               LEFT JOIN courses crs ON crs.course_id = c.course_id
               LEFT JOIN class_portfolio_submissions cps
                 ON cps.class_id = cs.class_id
                AND cps.student_id = cs.student_id
               LEFT JOIN class_portfolio_reviews cpr
                 ON cpr.class_id = cs.class_id
                AND cpr.student_id = cs.student_id
               WHERE cs.student_id = ?
                 AND cs.status = "approved"
               ORDER BY c.start_year DESC, c.term_number DESC, c.class_id DESC';

$classStmt = $conn->prepare($classSql);
if (!$classStmt) {
    faculty_send_json(['success' => false, 'message' => 'Failed to prepare class portfolio query.'], 500);
}

$classStmt->bind_param('i', $studentId);
$classStmt->execute();
$classResult = $classStmt->get_result();

$outputStmt = $conn->prepare(
    'SELECT
        o.output_id,
        o.output_name,
        o.total_score,
        os.student_score,
        os.status,
        os.submitted_file_name
     FROM class_outputs o
     LEFT JOIN output_submissions os
       ON os.output_id = o.output_id
      AND os.student_id = ?
     WHERE o.class_id = ?
     ORDER BY o.created_at ASC, o.output_id ASC'
);

if (!$outputStmt) {
    $classStmt->close();
    faculty_send_json(['success' => false, 'message' => 'Failed to prepare output query.'], 500);
}

$portfolios = [];
while ($classResult && ($classRow = $classResult->fetch_assoc())) {
    $classId = (int) ($classRow['class_id'] ?? 0);
    $portfolioStatus = strtolower(trim((string) ($classRow['portfolio_status'] ?? '')));

    $outputStmt->bind_param('ii', $studentId, $classId);
    $outputStmt->execute();
    $outputResult = $outputStmt->get_result();

    $outputs = [];
    while ($outputResult && ($outputRow = $outputResult->fetch_assoc())) {
        $status = strtolower(trim((string) ($outputRow['status'] ?? '')));
        $outputId = (int) ($outputRow['output_id'] ?? 0);
        $submittedFileName = trim((string) ($outputRow['submitted_file_name'] ?? ''));

        $outputs[] = [
            'outputId' => $outputId,
            'outputName' => trim((string) ($outputRow['output_name'] ?? '')),
            'totalScore' => (float) ($outputRow['total_score'] ?? 0),
            'studentScore' => $outputRow['student_score'] !== null ? (float) $outputRow['student_score'] : null,
            'status' => $status,
            'submittedFileName' => $submittedFileName,
            'hasFile' => $submittedFileName !== '',
            'fileViewUrl' => $submittedFileName !== ''
                ? sprintf('../class_management/view_submitted_output.php?class_id=%d&student_id=%d&output_id=%d', $classId, $studentId, $outputId)
                : ''
        ];
    }

    $reviewDecision = strtolower(trim((string) ($classRow['review_decision'] ?? '')));
    $review = null;
    if ($reviewDecision !== '') {
        $review = [
            'decision' => $reviewDecision,
            'finalGrade' => trim((string) ($classRow['final_grade'] ?? '')),
            'finalPercentage' => $classRow['final_percentage'] !== null ? (string) $classRow['final_percentage'] : '',
            'rejectionReason' => $hasRejectionReasonColumn ? trim((string) ($classRow['rejection_reason'] ?? '')) : '',
            'reviewedAt' => $classRow['reviewed_at'] ?? null
        ];
    }

    $courseName = trim((string) ($classRow['course_name'] ?? ''));
    $className = trim((string) ($classRow['class_name'] ?? ''));
    $termNumber = (int) ($classRow['term_number'] ?? 0);
    $startYear = (int) ($classRow['start_year'] ?? 0);
    $endYear = (int) ($classRow['end_year'] ?? 0);

    $classLabel = $className;
    if ($classLabel === '') {
        $classLabel = trim(sprintf('Term %d %d-%d', $termNumber, $startYear, $endYear));
    }

    $portfolios[] = [
        'classId' => $classId,
        'courseName' => $courseName !== '' ? $courseName : $className,
        'classLabel' => $classLabel,
        'portfolioStatus' => $portfolioStatus,
        'submittedAt' => $classRow['portfolio_submitted_at'] ?? null,
        'joinedAt' => $classRow['approved_at'] ?? null,
        'outputs' => $outputs,
        'review' => $review
    ];
}

$outputStmt->close();
$classStmt->close();

faculty_send_json([
    'success' => true,
    'student' => [
        'studentId' => (int) ($studentRow['student_id'] ?? 0),
        'firstName' => trim((string) ($studentRow['first_name'] ?? '')),
        'lastName' => trim((string) ($studentRow['last_name'] ?? '')),
        'idNumber' => trim((string) ($studentRow['id_number'] ?? '')),
        'email' => trim((string) ($studentRow['email'] ?? '')),
        'joinedAt' => $studentRow['joined_at'] ?? null,
        'programName' => trim((string) ($listRow['program_name'] ?? '')),
        'yearOfEnrollment' => $yearOfEnrollment
    ],
    'list' => [
        'listId' => (int) ($listRow['list_id'] ?? 0),
        'batchName' => trim((string) ($listRow['batch_name'] ?? '')),
        'programName' => trim((string) ($listRow['program_name'] ?? '')),
        'yearOfEnrollment' => $yearOfEnrollment
    ],
    'academicPortfolios' => $portfolios
]);
