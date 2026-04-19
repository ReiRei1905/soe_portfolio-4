<?php

declare(strict_types=1);

require_once __DIR__ . '/portfolio_submission_common.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(405, ['success' => false, 'message' => 'Method not allowed.']);
}

$conn = db_connect();

if (!isset($_SESSION['email']) || trim((string) $_SESSION['email']) === '') {
    json_response(401, ['success' => false, 'message' => 'Unauthorized. Please log in.']);
}

$classId = isset($_POST['class_id']) ? (int) $_POST['class_id'] : 0;
$rating = strtolower(trim((string) ($_POST['difficulty_rating'] ?? '')));
$studentId = current_student_id($conn);

if ($classId <= 0) {
    json_response(400, ['success' => false, 'message' => 'Invalid class reference.']);
}

$allowedRatings = ['easy', 'normal', 'hard'];
if (!in_array($rating, $allowedRatings, true)) {
    json_response(422, ['success' => false, 'message' => 'Invalid difficulty rating.']);
}

require_approved_student_in_class($conn, $classId, $studentId);

$tableExists = (bool) $conn->query("SHOW TABLES LIKE 'class_difficulty_ratings'")->fetch_assoc();
if (!$tableExists) {
    json_response(500, [
        'success' => false,
        'message' => 'Table class_difficulty_ratings is missing. Apply SQL migration first.'
    ]);
}

$upsertSql = 'INSERT INTO class_difficulty_ratings (
                class_id, student_id, difficulty_rating, created_at, updated_at
              ) VALUES (?, ?, ?, NOW(), NOW())
              ON DUPLICATE KEY UPDATE
                difficulty_rating = VALUES(difficulty_rating),
                updated_at = NOW()';

$stmt = $conn->prepare($upsertSql);
if (!$stmt) {
    json_response(500, ['success' => false, 'message' => 'Failed to save class difficulty rating.']);
}

$stmt->bind_param('iis', $classId, $studentId, $rating);
$ok = $stmt->execute();
$stmt->close();

if (!$ok) {
    json_response(500, ['success' => false, 'message' => 'Failed to save class difficulty rating.']);
}

json_response(200, [
    'success' => true,
    'rating' => $rating,
    'message' => 'Class difficulty rating saved.'
]);
