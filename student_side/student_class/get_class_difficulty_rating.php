<?php

declare(strict_types=1);

require_once __DIR__ . '/portfolio_submission_common.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    json_response(405, ['success' => false, 'message' => 'Method not allowed.']);
}

$conn = db_connect();

if (!isset($_SESSION['email']) || trim((string) $_SESSION['email']) === '') {
    json_response(401, ['success' => false, 'message' => 'Unauthorized. Please log in.']);
}

$classId = isset($_GET['class_id']) ? (int) $_GET['class_id'] : 0;
$studentId = current_student_id($conn);

if ($classId <= 0) {
    json_response(400, ['success' => false, 'message' => 'Invalid class reference.']);
}

require_approved_student_in_class($conn, $classId, $studentId);

$tableExists = (bool) $conn->query("SHOW TABLES LIKE 'class_difficulty_ratings'")->fetch_assoc();
if (!$tableExists) {
    json_response(500, [
        'success' => false,
        'message' => 'Table class_difficulty_ratings is missing. Apply SQL migration first.'
    ]);
}

$stmt = $conn->prepare('SELECT difficulty_rating, updated_at FROM class_difficulty_ratings WHERE class_id = ? AND student_id = ? LIMIT 1');
if (!$stmt) {
    json_response(500, ['success' => false, 'message' => 'Failed to load class difficulty rating.']);
}

$stmt->bind_param('ii', $classId, $studentId);
$stmt->execute();
$row = $stmt->get_result()->fetch_assoc();
$stmt->close();

json_response(200, [
    'success' => true,
    'rating' => $row ? strtolower(trim((string) ($row['difficulty_rating'] ?? ''))) : '',
    'updated_at' => $row['updated_at'] ?? null
]);
