<?php

declare(strict_types=1);

require_once __DIR__ . '/../faculty_access_common.php';
require_once __DIR__ . '/../../user_info_V3/notification_service.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    faculty_send_json(['success' => false, 'message' => 'Method not allowed.'], 405);
}

$sessionUser = faculty_require_verified_faculty($conn);
$classId = isset($_POST['class_id']) ? (int) $_POST['class_id'] : 0;
$studentId = isset($_POST['student_id']) ? (int) $_POST['student_id'] : 0;
$decision = strtolower(trim((string) ($_POST['decision'] ?? '')));
$gradeRaw = trim((string) ($_POST['final_grade'] ?? ''));
$percentRaw = trim((string) ($_POST['final_percentage'] ?? ''));
$rejectionReasonRaw = trim((string) ($_POST['rejection_reason'] ?? ''));

$finalGrade = '';
$finalPercentage = 0.0;

if ($classId <= 0 || $studentId <= 0) {
    faculty_send_json(['success' => false, 'message' => 'Invalid class or student reference.'], 400);
}

if (!in_array($decision, ['approved', 'rejected'], true)) {
    faculty_send_json(['success' => false, 'message' => 'Invalid review decision.'], 422);
}

if ($decision === 'rejected' && $rejectionReasonRaw === '') {
    faculty_send_json(['success' => false, 'message' => 'Please provide a rejection reason.'], 422);
}

if ($decision === 'approved') {
    $normalizedGrade = strtoupper($gradeRaw);
    $allowedGrades = ['1.0', '1.5', '2.0', '2.5', '3.0', '3.5', '4.0', 'R'];
    if (!in_array($normalizedGrade, $allowedGrades, true)) {
        faculty_send_json(['success' => false, 'message' => 'Final grade must be one of: 1.0, 1.5, 2.0, 2.5, 3.0, 3.5, 4.0, or R.'], 422);
    }

    $normalizedPercent = rtrim(str_replace('%', '', $percentRaw));
    if (!preg_match('/^(?:100(?:\\.0+)?|[0-9]{1,2}(?:\\.[0-9]+)?)$/', $normalizedPercent)) {
        faculty_send_json(['success' => false, 'message' => 'Final percentage must be numeric (e.g., 60, 90.1, 95%).'], 422);
    }

    $finalPercentage = (float) $normalizedPercent;
    if ($finalPercentage < 0 || $finalPercentage > 100) {
        faculty_send_json(['success' => false, 'message' => 'Final percentage must be between 0 and 100.'], 422);
    }

    $finalGrade = $normalizedGrade;
}

if (!faculty_can_handle_class($conn, $sessionUser, $classId)) {
    faculty_send_json(['success' => false, 'message' => 'You are not allowed to review this class portfolio.'], 403);
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

$memberStmt = $conn->prepare('SELECT status FROM class_students WHERE class_id = ? AND student_id = ? LIMIT 1');
if (!$memberStmt) {
    faculty_send_json(['success' => false, 'message' => 'Failed to validate class member.'], 500);
}
$memberStmt->bind_param('ii', $classId, $studentId);
$memberStmt->execute();
$memberRow = $memberStmt->get_result()->fetch_assoc();
$memberStmt->close();

if (!$memberRow || (string) ($memberRow['status'] ?? '') !== 'approved') {
    faculty_send_json(['success' => false, 'message' => 'Student is not an approved class member.'], 404);
}

$portfolioStmt = $conn->prepare('SELECT status FROM class_portfolio_submissions WHERE class_id = ? AND student_id = ? LIMIT 1');
if (!$portfolioStmt) {
    faculty_send_json(['success' => false, 'message' => 'Failed to validate portfolio submission.'], 500);
}
$portfolioStmt->bind_param('ii', $classId, $studentId);
$portfolioStmt->execute();
$portfolioRow = $portfolioStmt->get_result()->fetch_assoc();
$portfolioStmt->close();

if (!$portfolioRow || strtolower((string) ($portfolioRow['status'] ?? '')) !== 'submitted') {
    faculty_send_json(['success' => false, 'message' => 'Student has no active submitted portfolio.'], 422);
}

$reviewerUserId = (int) ($sessionUser['user_id'] ?? 0);

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

if ($hasRejectionReasonColumn) {
        $upsertSql = 'INSERT INTO class_portfolio_reviews (
                                        class_id, student_id, decision, final_grade, final_percentage,
                                        rejection_reason, reviewed_by_user_id, reviewed_at, created_at, updated_at
                                    ) VALUES (?, ?, ?, ?, ?, ?, ?, NOW(), NOW(), NOW())
                                    ON DUPLICATE KEY UPDATE
                                        decision = VALUES(decision),
                                        final_grade = VALUES(final_grade),
                                        final_percentage = VALUES(final_percentage),
                                        rejection_reason = VALUES(rejection_reason),
                                        reviewed_by_user_id = VALUES(reviewed_by_user_id),
                                        reviewed_at = NOW(),
                                        updated_at = NOW()';
} else {
        $upsertSql = 'INSERT INTO class_portfolio_reviews (
                                        class_id, student_id, decision, final_grade, final_percentage,
                                        reviewed_by_user_id, reviewed_at, created_at, updated_at
                                    ) VALUES (?, ?, ?, ?, ?, ?, NOW(), NOW(), NOW())
                                    ON DUPLICATE KEY UPDATE
                                        decision = VALUES(decision),
                                        final_grade = VALUES(final_grade),
                                        final_percentage = VALUES(final_percentage),
                                        reviewed_by_user_id = VALUES(reviewed_by_user_id),
                                        reviewed_at = NOW(),
                                        updated_at = NOW()';
}

$upsertStmt = $conn->prepare($upsertSql);
if (!$upsertStmt) {
    faculty_send_json(['success' => false, 'message' => 'Failed to save portfolio review.'], 500);
}

$normalizedRejectionReason = $decision === 'rejected' ? $rejectionReasonRaw : '';
if ($hasRejectionReasonColumn) {
    $upsertStmt->bind_param('iissdsi', $classId, $studentId, $decision, $finalGrade, $finalPercentage, $normalizedRejectionReason, $reviewerUserId);
} else {
    $upsertStmt->bind_param('iissdi', $classId, $studentId, $decision, $finalGrade, $finalPercentage, $reviewerUserId);
}
$ok = $upsertStmt->execute();
$upsertStmt->close();

if (!$ok) {
    faculty_send_json(['success' => false, 'message' => 'Failed to save portfolio review.'], 500);
}

$studentStmt = $conn->prepare('SELECT
                                s.user_id,
                                COALESCE(s.email, u.email) AS email,
                                TRIM(COALESCE(NULLIF(CONCAT(s.first_name, " ", s.last_name), ""), CONCAT(u.first_name, " ", u.last_name), "Student")) AS student_name
                              FROM students s
                              LEFT JOIN users u ON u.user_id = s.user_id
                              WHERE s.student_id = ?
                              LIMIT 1');
$studentStmt->bind_param('i', $studentId);
$studentStmt->execute();
$studentRow = $studentStmt->get_result()->fetch_assoc();
$studentStmt->close();

$classStmt = $conn->prepare('SELECT class_name FROM classes WHERE class_id = ? LIMIT 1');
$classStmt->bind_param('i', $classId);
$classStmt->execute();
$classRow = $classStmt->get_result()->fetch_assoc();
$classStmt->close();

$reviewerStmt = $conn->prepare('SELECT TRIM(COALESCE(NULLIF(CONCAT(f.first_name, " ", f.last_name), ""), CONCAT(u.first_name, " ", u.last_name), "Professor")) AS reviewer_name
                                FROM users u
                                LEFT JOIN faculty f ON f.user_id = u.user_id
                                WHERE u.user_id = ?
                                LIMIT 1');
$reviewerStmt->bind_param('i', $reviewerUserId);
$reviewerStmt->execute();
$reviewerRow = $reviewerStmt->get_result()->fetch_assoc();
$reviewerStmt->close();

$studentUserId = (int) ($studentRow['user_id'] ?? 0);
$studentEmail = trim((string) ($studentRow['email'] ?? ''));
$studentName = trim((string) ($studentRow['student_name'] ?? 'Student'));
$className = trim((string) ($classRow['class_name'] ?? 'the class'));
$reviewerName = trim((string) ($reviewerRow['reviewer_name'] ?? 'Professor'));

$percentLabel = rtrim(rtrim(number_format($finalPercentage, 2, '.', ''), '0'), '.');

$notificationMessage = $decision === 'approved'
    ? sprintf(
        "Professor %s, has already approved your submitted portfolio with the following details:\nFinal Percentage: %s%%\nFinal Grade: %s",
        $reviewerName,
        $percentLabel,
        $finalGrade
    )
    : sprintf(
        "Professor %s, has rejected your submitted portfolio by the following reasons:\n\"%s\" please thoroughly review what needs to be submited portfolio for your revision",
        $reviewerName,
        $normalizedRejectionReason
    );

if ($studentUserId > 0) {
    add_system_notification($conn, $studentUserId, $notificationMessage);
}

if ($studentEmail !== '') {
    $subject = $decision === 'approved' ? 'Portfolio approved' : 'Portfolio rejected';
    send_user_email_notification($studentEmail, $studentName, $subject, $notificationMessage);
}

faculty_send_json([
    'success' => true,
    'message' => $decision === 'approved' ? 'Portfolio approved successfully.' : 'Portfolio rejected successfully.',
    'review' => [
        'decision' => $decision,
        'finalGrade' => $decision === 'approved' ? $finalGrade : '',
        'finalPercentage' => $decision === 'approved' ? $percentLabel : '',
        'rejectionReason' => $decision === 'rejected' ? $normalizedRejectionReason : '',
        'reviewedAt' => date('Y-m-d H:i:s')
    ]
]);
