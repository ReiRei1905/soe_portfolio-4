<?php
session_start();
header('Content-Type: application/json');

$conn = new mysqli("localhost", "root", "", "soe_portfolio");
if ($conn->connect_error) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'DB connection failed']);
    exit;
}

// Require student to be logged in. During local testing you can set $_SESSION['student_id'] manually.
if (!isset($_SESSION['student_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Not authenticated as student']);
    exit;
}

$student_id = intval($_SESSION['student_id']);
$output_id = isset($_POST['output_id']) ? intval($_POST['output_id']) : 0;
$student_score = isset($_POST['student_score']) ? floatval($_POST['student_score']) : null;

if ($output_id <= 0 || $student_score === null) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Missing parameters']);
    exit;
}

// 1) Validate output exists and get total_score and class_id
$stmt = $conn->prepare('SELECT class_id, total_score FROM class_outputs WHERE output_id = ? LIMIT 1');
$stmt->bind_param('i', $output_id);
$stmt->execute();
$res = $stmt->get_result();
if (!$row = $res->fetch_assoc()) {
    http_response_code(404);
    echo json_encode(['success' => false, 'error' => 'Output not found']);
    exit;
}
$class_id = intval($row['class_id']);
$total_score = floatval($row['total_score']);
$stmt->close();

// 2) Optional: check deadline on classes table
$stmt = $conn->prepare('SELECT deadline_at FROM classes WHERE class_id = ? LIMIT 1');
$stmt->bind_param('i', $class_id);
$stmt->execute();
$res = $stmt->get_result();
if ($c = $res->fetch_assoc()) {
    if (!empty($c['deadline_at']) && $c['deadline_at'] !== '0000-00-00 00:00:00') {
        $deadline = $c['deadline_at'];
        $now = date('Y-m-d H:i:s');
        if ($now > $deadline) {
            http_response_code(403);
            echo json_encode(['success' => false, 'error' => 'Submission deadline has passed']);
            exit;
        }
    }
}
$stmt->close();

// 3) Validate enrollment if class_students table exists
$enrolled = true; // default allow
$check = $conn->query("SHOW TABLES LIKE 'class_students'");
if ($check && $check->num_rows > 0) {
    $stmt = $conn->prepare('SELECT 1 FROM class_students WHERE class_id = ? AND student_id = ? LIMIT 1');
    $stmt->bind_param('ii', $class_id, $student_id);
    $stmt->execute();
    $res = $stmt->get_result();
    if (!$res->fetch_assoc()) {
        http_response_code(403);
        echo json_encode(['success' => false, 'error' => 'Student not enrolled in class']);
        exit;
    }
    $stmt->close();
}

// 4) Validate score bounds
if ($student_score < 0 || $student_score > $total_score) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid score']);
    exit;
}

// 5) Upsert submission (insert or update existing)
$sql = "INSERT INTO output_submissions (output_id, student_id, student_score, status, submitted_at, created_at, updated_at)
        VALUES (?, ?, ?, 'submitted', NOW(), NOW(), NOW())
        ON DUPLICATE KEY UPDATE
          student_score = VALUES(student_score),
          status = 'submitted',
          submitted_at = NOW(),
          updated_at = NOW()";
$stmt = $conn->prepare($sql);
$stmt->bind_param('iid', $output_id, $student_id, $student_score);
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
