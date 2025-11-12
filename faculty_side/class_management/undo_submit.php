<?php
session_start();
header('Content-Type: application/json');

$conn = new mysqli("localhost", "root", "", "soe_portfolio");
if ($conn->connect_error) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'DB connection failed']);
    exit;
}

if (!isset($_SESSION['student_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Not authenticated']);
    exit;
}

$student_id = intval($_SESSION['student_id']);
$output_id = isset($_POST['output_id']) ? intval($_POST['output_id']) : 0;

if ($output_id <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Missing output_id']);
    exit;
}

// Optional: check if graded already and disallow undo if graded
$stmt = $conn->prepare('SELECT professor_score FROM output_submissions WHERE output_id = ? AND student_id = ? LIMIT 1');
$stmt->bind_param('ii', $output_id, $student_id);
$stmt->execute();
$res = $stmt->get_result();
if ($row = $res->fetch_assoc()) {
    if (!is_null($row['professor_score'])) {
        http_response_code(403);
        echo json_encode(['success' => false, 'error' => 'Cannot undo after grading']);
        exit;
    }
}
$stmt->close();

$stmt = $conn->prepare('UPDATE output_submissions SET status = "undone", undone_at = NOW(), updated_at = NOW() WHERE output_id = ? AND student_id = ?');
$stmt->bind_param('ii', $output_id, $student_id);
$ok = $stmt->execute();
if ($ok) {
    echo json_encode(['success' => true]);
} else {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'DB error']);
}
$stmt->close();
$conn->close();

?>
