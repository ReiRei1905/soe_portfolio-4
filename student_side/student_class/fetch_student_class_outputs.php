<?php

declare(strict_types=1);

require_once __DIR__ . '/../api/common.php';

$conn = db_connect();

if (!isset($_SESSION['email']) || trim((string) $_SESSION['email']) === '') {
    json_response(401, ['success' => false, 'message' => 'Unauthorized. Please log in.']);
}

$classId = isset($_GET['class_id']) ? (int) $_GET['class_id'] : 0;
$studentId = current_student_id($conn);

if ($classId <= 0) {
    json_response(400, ['success' => false, 'message' => 'Invalid class reference.']);
}

$classStudentsExists = (bool) $conn->query("SHOW TABLES LIKE 'class_students'")->fetch_assoc();
if (!$classStudentsExists) {
    json_response(500, ['success' => false, 'message' => 'Table class_students is missing. Apply SQL migration first.']);
}

$memberStmt = $conn->prepare('SELECT status FROM class_students WHERE class_id = ? AND student_id = ? LIMIT 1');
$memberStmt->bind_param('ii', $classId, $studentId);
$memberStmt->execute();
$memberRow = $memberStmt->get_result()->fetch_assoc();
$memberStmt->close();

if (!$memberRow || (string) ($memberRow['status'] ?? '') !== 'approved') {
    json_response(403, ['success' => false, 'message' => 'You are not enrolled in this class.']);
}

$portfolioSubmitted = false;
$portfolioStatus = '';
$portfolioReviewDecision = '';
$portfolioReviewedAt = null;
$portfolioRejectionReason = '';
$portfolioTableExists = (bool) $conn->query("SHOW TABLES LIKE 'class_portfolio_submissions'")->fetch_assoc();
if ($portfolioTableExists) {
    $portfolioStmt = $conn->prepare('SELECT status
                                     FROM class_portfolio_submissions
                                     WHERE class_id = ? AND student_id = ?
                                     LIMIT 1');
    if ($portfolioStmt) {
        $portfolioStmt->bind_param('ii', $classId, $studentId);
        $portfolioStmt->execute();
        $portfolioRow = $portfolioStmt->get_result()->fetch_assoc();
        $portfolioStmt->close();

        $portfolioStatus = strtolower(trim((string) ($portfolioRow['status'] ?? '')));
        $portfolioSubmitted = $portfolioStatus === 'submitted';
    }
}

$portfolioReviewTableExists = (bool) $conn->query("SHOW TABLES LIKE 'class_portfolio_reviews'")->fetch_assoc();
if ($portfolioReviewTableExists) {
    $hasRejectionReasonColumn = (bool) $conn->query("SELECT 1 FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'class_portfolio_reviews' AND column_name = 'rejection_reason' LIMIT 1")->fetch_assoc();

    $reviewSql = 'SELECT decision, reviewed_at' . ($hasRejectionReasonColumn ? ', rejection_reason' : '') . '
                  FROM class_portfolio_reviews
                  WHERE class_id = ? AND student_id = ?
                  LIMIT 1';
    $reviewStmt = $conn->prepare($reviewSql);
    if ($reviewStmt) {
        $reviewStmt->bind_param('ii', $classId, $studentId);
        $reviewStmt->execute();
        $reviewRow = $reviewStmt->get_result()->fetch_assoc();
        $reviewStmt->close();

        if ($reviewRow) {
            $portfolioReviewDecision = strtolower(trim((string) ($reviewRow['decision'] ?? '')));
            $portfolioReviewedAt = $reviewRow['reviewed_at'] ?? null;
            $portfolioRejectionReason = $hasRejectionReasonColumn ? trim((string) ($reviewRow['rejection_reason'] ?? '')) : '';
        }
    }
}

$sql = 'SELECT
            o.output_id,
            o.output_name,
            o.total_score,
            o.required_file_format,
            o.created_at,
            o.updated_at,
            os.student_score,
            os.status,
            os.submitted_file_name,
            os.submitted_at
        FROM class_outputs o
        LEFT JOIN output_submissions os
            ON os.output_id = o.output_id
           AND os.student_id = ?
        WHERE o.class_id = ?
        ORDER BY o.created_at ASC';

$stmt = $conn->prepare($sql);
if (!$stmt) {
    json_response(500, ['success' => false, 'message' => 'Failed to prepare outputs query.']);
}

$stmt->bind_param('ii', $studentId, $classId);
$stmt->execute();
$result = $stmt->get_result();

$outputs = [];
while ($row = $result->fetch_assoc()) {
    $outputs[] = [
        'output_id' => (int) ($row['output_id'] ?? 0),
        'output_name' => (string) ($row['output_name'] ?? ''),
        'total_score' => (int) ($row['total_score'] ?? 0),
        'required_file_format' => (string) ($row['required_file_format'] ?? ''),
        'created_at' => $row['created_at'],
        'updated_at' => $row['updated_at'],
        'student_score' => $row['student_score'] !== null ? (float) $row['student_score'] : null,
        'status' => (string) ($row['status'] ?? ''),
        'submitted_file_name' => $row['submitted_file_name'],
        'submitted_at' => $row['submitted_at']
    ];
}
$stmt->close();

json_response(200, [
    'success' => true,
    'outputs' => $outputs,
    'portfolio_submitted' => $portfolioSubmitted,
    'portfolio_status' => $portfolioStatus,
    'portfolio_review_decision' => $portfolioReviewDecision,
    'portfolio_reviewed_at' => $portfolioReviewedAt,
    'portfolio_rejection_reason' => $portfolioRejectionReason
]);
