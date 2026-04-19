<?php

declare(strict_types=1);

require_once __DIR__ . '/../faculty_access_common.php';

$sessionUser = faculty_require_verified_faculty($conn);
$classId = isset($_GET['class_id']) ? (int) $_GET['class_id'] : 0;

if ($classId <= 0) {
    faculty_send_json(['success' => false, 'message' => 'Invalid class ID.'], 400);
}

if (!faculty_can_handle_class($conn, $sessionUser, $classId)) {
    faculty_send_json(['success' => false, 'message' => 'You are not allowed to access class reports.'], 403);
}

if (!faculty_table_exists($conn, 'class_students')) {
    faculty_send_json([
        'success' => false,
        'message' => 'Table class_students is missing. Apply SQL migration first.'
    ], 500);
}

$portfolioTableExists = faculty_table_exists($conn, 'class_portfolio_submissions');
$reviewTableExists = faculty_table_exists($conn, 'class_portfolio_reviews');
$difficultyTableExists = faculty_table_exists($conn, 'class_difficulty_ratings');

$sql = 'SELECT
            cs.student_id,
            cs.requested_at,
            cs.invited_at,
            cs.approved_at,
            s.id_number,
            COALESCE(s.first_name, u.first_name) AS first_name,
            COALESCE(s.last_name, u.last_name) AS last_name,
            COALESCE(s.email, u.email) AS email';

if ($portfolioTableExists) {
    $sql .= ', cps.status AS portfolio_status,
              cps.submitted_at AS portfolio_submitted_at,
              cps.undone_at AS portfolio_undone_at';

    if ($reviewTableExists) {
        $sql .= ', cpr.decision AS review_decision,
                  cpr.reviewed_at AS review_reviewed_at,
                  cpr.final_grade AS review_final_grade,
                  cpr.final_percentage AS review_final_percentage';
    }
}

if ($difficultyTableExists) {
    $sql .= ', cdr.difficulty_rating AS difficulty_rating';
}

$sql .= ' FROM class_students cs
          INNER JOIN students s ON s.student_id = cs.student_id
          LEFT JOIN users u ON u.user_id = s.user_id';

if ($portfolioTableExists) {
    $sql .= ' LEFT JOIN class_portfolio_submissions cps
              ON cps.class_id = cs.class_id
             AND cps.student_id = cs.student_id';

    if ($reviewTableExists) {
        $sql .= ' LEFT JOIN class_portfolio_reviews cpr
                  ON cpr.class_id = cs.class_id
                 AND cpr.student_id = cs.student_id';
    }
}

if ($difficultyTableExists) {
    $sql .= ' LEFT JOIN class_difficulty_ratings cdr
              ON cdr.class_id = cs.class_id
             AND cdr.student_id = cs.student_id';
}

$sql .= ' WHERE cs.class_id = ?
          AND cs.status = "approved"
          ORDER BY COALESCE(cs.approved_at, cs.invited_at, cs.requested_at, cs.created_at) DESC';

$stmt = $conn->prepare($sql);
if (!$stmt) {
    faculty_send_json(['success' => false, 'message' => 'Failed to prepare reports query.'], 500);
}

$stmt->bind_param('i', $classId);
$stmt->execute();
$result = $stmt->get_result();

$students = [];
while ($row = $result->fetch_assoc()) {
    $portfolioStatus = $portfolioTableExists ? strtolower(trim((string) ($row['portfolio_status'] ?? ''))) : '';
    $portfolioSubmittedAt = $portfolioTableExists ? ($row['portfolio_submitted_at'] ?? null) : null;
    $reviewDecision = $reviewTableExists ? strtolower(trim((string) ($row['review_decision'] ?? ''))) : '';
    $reviewedAt = $reviewTableExists ? ($row['review_reviewed_at'] ?? null) : null;

    $portfolioDisplayStatus = 'none';
    if ($portfolioStatus === 'submitted') {
        $portfolioDisplayStatus = 'submitted';

        $submittedTs = $portfolioSubmittedAt ? strtotime((string) $portfolioSubmittedAt) : false;
        $reviewedTs = $reviewedAt ? strtotime((string) $reviewedAt) : false;
        if ($submittedTs !== false && $reviewedTs !== false && $submittedTs <= $reviewedTs) {
            if ($reviewDecision === 'approved') {
                $portfolioDisplayStatus = 'approved';
            } elseif ($reviewDecision === 'rejected') {
                $portfolioDisplayStatus = 'revised';
            }
        }
    }

    $students[] = [
        'studentId' => (int) ($row['student_id'] ?? 0),
        'idNumber' => (string) ($row['id_number'] ?? ''),
        'firstName' => trim((string) ($row['first_name'] ?? '')),
        'lastName' => trim((string) ($row['last_name'] ?? '')),
        'email' => trim((string) ($row['email'] ?? '')),
        'joinedAt' => $row['approved_at'] ?? $row['invited_at'] ?? $row['requested_at'] ?? null,
        'portfolioStatus' => $portfolioTableExists ? (string) ($row['portfolio_status'] ?? '') : '',
        'portfolioSubmittedAt' => $portfolioSubmittedAt,
        'portfolioUndoneAt' => $portfolioTableExists ? ($row['portfolio_undone_at'] ?? null) : null,
        'reviewDecision' => $reviewTableExists ? (string) ($row['review_decision'] ?? '') : '',
        'reviewedAt' => $reviewTableExists ? ($row['review_reviewed_at'] ?? null) : null,
        'reviewFinalGrade' => $reviewTableExists ? ($row['review_final_grade'] ?? null) : null,
        'reviewFinalPercentage' => $reviewTableExists ? ($row['review_final_percentage'] ?? null) : null,
        'difficultyRating' => $difficultyTableExists ? (string) ($row['difficulty_rating'] ?? '') : '',
        'portfolioDisplayStatus' => $portfolioDisplayStatus
    ];
}
$stmt->close();

faculty_send_json([
    'success' => true,
    'students' => $students,
    'portfolio_tracking_enabled' => $portfolioTableExists
]);
