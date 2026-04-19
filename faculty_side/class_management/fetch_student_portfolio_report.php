<?php

declare(strict_types=1);

require_once __DIR__ . '/../faculty_access_common.php';

$sessionUser = faculty_require_verified_faculty($conn);
$classId = isset($_GET['class_id']) ? (int) $_GET['class_id'] : 0;
$studentId = isset($_GET['student_id']) ? (int) $_GET['student_id'] : 0;

if ($classId <= 0 || $studentId <= 0) {
    faculty_send_json(['success' => false, 'message' => 'Invalid class or student reference.'], 400);
}

if (!faculty_can_handle_class($conn, $sessionUser, $classId)) {
    faculty_send_json(['success' => false, 'message' => 'You are not allowed to access this class portfolio report.'], 403);
}

if (!faculty_table_exists($conn, 'class_students')) {
    faculty_send_json(['success' => false, 'message' => 'Table class_students is missing. Apply SQL migration first.'], 500);
}

if (!faculty_table_exists($conn, 'class_portfolio_submissions')) {
    faculty_send_json(['success' => false, 'message' => 'Table class_portfolio_submissions is missing. Apply SQL migration first.'], 500);
}

if (!faculty_table_exists($conn, 'class_portfolio_reviews')) {
    faculty_send_json(['success' => false, 'message' => 'Table class_portfolio_reviews is missing. Apply SQL migration first.'], 500);
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

$studentSql = 'SELECT
                cs.student_id,
                cs.requested_at,
                cs.invited_at,
                cs.approved_at,
                s.id_number,
                TRIM(COALESCE(NULLIF(CONCAT(s.first_name, " ", s.last_name), ""), CONCAT(u.first_name, " ", u.last_name), "Student")) AS student_name,
                COALESCE(s.email, u.email) AS email,
                cps.status AS portfolio_status,
                cps.submitted_at AS portfolio_submitted_at,
                cps.undone_at AS portfolio_undone_at,
                cpr.decision AS review_decision,
                cpr.final_grade,
                cpr.final_percentage,
                cpr.reviewed_at
              FROM class_students cs
              INNER JOIN students s ON s.student_id = cs.student_id
              LEFT JOIN users u ON u.user_id = s.user_id
              LEFT JOIN class_portfolio_submissions cps
                ON cps.class_id = cs.class_id
               AND cps.student_id = cs.student_id
              LEFT JOIN class_portfolio_reviews cpr
                ON cpr.class_id = cs.class_id
               AND cpr.student_id = cs.student_id
              WHERE cs.class_id = ?
                AND cs.student_id = ?
                AND cs.status = "approved"
              LIMIT 1';

if ($hasRejectionReasonColumn) {
        $studentSql = str_replace('cpr.reviewed_at', 'cpr.reviewed_at, cpr.rejection_reason', $studentSql);
}

$studentStmt = $conn->prepare($studentSql);
if (!$studentStmt) {
    faculty_send_json(['success' => false, 'message' => 'Failed to prepare student portfolio query.'], 500);
}

$studentStmt->bind_param('ii', $classId, $studentId);
$studentStmt->execute();
$studentRow = $studentStmt->get_result()->fetch_assoc();
$studentStmt->close();

if (!$studentRow) {
    faculty_send_json(['success' => false, 'message' => 'Student is not an approved member of this class.'], 404);
}

$portfolioStatus = strtolower(trim((string) ($studentRow['portfolio_status'] ?? '')));
if ($portfolioStatus !== 'submitted') {
    faculty_send_json(['success' => false, 'message' => 'This student has no active submitted portfolio.'], 422);
}

function normalizeApprovedFinalGrade($rawGrade): string
{
    if ($rawGrade === null) {
        return '';
    }

    $grade = strtoupper(trim((string) $rawGrade));
    if ($grade === '') {
        return '';
    }

    // Some databases coerce non-numeric grade "R" into 0.0 when final_grade is numeric.
    // Since 0.0 is not a valid grade in this flow, treat it as "R" for display consistency.
    if ($grade === '0' || $grade === '0.0' || $grade === '0.00') {
        return 'R';
    }

    return $grade;
}

$outputsSql = 'SELECT
                o.output_id,
                o.output_name,
                o.total_score,
                os.student_score,
                os.status,
                os.submitted_at,
                os.submitted_file_name
              FROM class_outputs o
              LEFT JOIN output_submissions os
                ON os.output_id = o.output_id
               AND os.student_id = ?
              WHERE o.class_id = ?
              ORDER BY o.created_at ASC, o.output_id ASC';

$outputsStmt = $conn->prepare($outputsSql);
if (!$outputsStmt) {
    faculty_send_json(['success' => false, 'message' => 'Failed to prepare portfolio outputs query.'], 500);
}

$outputsStmt->bind_param('ii', $studentId, $classId);
$outputsStmt->execute();
$outputsResult = $outputsStmt->get_result();

$outputs = [];
$submittedCount = 0;
while ($row = $outputsResult->fetch_assoc()) {
    $status = strtolower(trim((string) ($row['status'] ?? '')));
    if ($status === 'submitted') {
        $submittedCount++;
    }

    $outputId = (int) ($row['output_id'] ?? 0);
    $fileName = trim((string) ($row['submitted_file_name'] ?? ''));

    $outputs[] = [
        'outputId' => $outputId,
        'outputName' => (string) ($row['output_name'] ?? ''),
        'totalScore' => (float) ($row['total_score'] ?? 0),
        'studentScore' => $row['student_score'] !== null ? (float) $row['student_score'] : null,
        'status' => $status,
        'submittedAt' => $row['submitted_at'] ?? null,
        'submittedFileName' => $fileName,
        'hasFile' => $fileName !== '',
        'fileViewUrl' => $fileName !== ''
            ? sprintf('view_submitted_output.php?class_id=%d&student_id=%d&output_id=%d', $classId, $studentId, $outputId)
            : ''
    ];
}
$outputsStmt->close();

$review = null;
if (!empty($studentRow['review_decision'])) {
    $reviewDecision = strtolower(trim((string) ($studentRow['review_decision'] ?? '')));
    $review = [
        'decision' => $reviewDecision,
        'finalGrade' => $reviewDecision === 'approved' ? normalizeApprovedFinalGrade($studentRow['final_grade'] ?? null) : '',
        'finalPercentage' => $reviewDecision === 'approved' && $studentRow['final_percentage'] !== null ? (string) $studentRow['final_percentage'] : '',
        'rejectionReason' => $reviewDecision === 'rejected' && $hasRejectionReasonColumn ? (string) ($studentRow['rejection_reason'] ?? '') : '',
        'reviewedAt' => $studentRow['reviewed_at'] ?? null
    ];
}

faculty_send_json([
    'success' => true,
    'student' => [
        'studentId' => (int) ($studentRow['student_id'] ?? 0),
        'studentName' => (string) ($studentRow['student_name'] ?? 'Student'),
        'idNumber' => (string) ($studentRow['id_number'] ?? ''),
        'email' => (string) ($studentRow['email'] ?? ''),
        'joinedAt' => $studentRow['approved_at'] ?? $studentRow['invited_at'] ?? $studentRow['requested_at'] ?? null,
        'portfolioStatus' => $portfolioStatus,
        'portfolioSubmittedAt' => $studentRow['portfolio_submitted_at'] ?? null,
        'outputsSubmittedCount' => $submittedCount,
        'outputsTotalCount' => count($outputs)
    ],
    'outputs' => $outputs,
    'review' => $review
]);
