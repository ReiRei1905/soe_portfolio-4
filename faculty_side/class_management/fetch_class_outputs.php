<?php

declare(strict_types=1);

require_once __DIR__ . '/../faculty_access_common.php';

$sessionUser = faculty_require_verified_faculty($conn);
$classId = isset($_GET['class_id']) ? (int) $_GET['class_id'] : 0;

if ($classId <= 0) {
    faculty_send_json(['success' => false, 'message' => 'Invalid class_id.'], 400);
}

if (!faculty_can_handle_class($conn, $sessionUser, $classId)) {
    faculty_send_json(['success' => false, 'message' => 'You are not allowed to access outputs for this class.'], 403);
}

$sql = 'SELECT output_id, output_name, total_score, required_file_format, created_at, updated_at
        FROM class_outputs
        WHERE class_id = ?
    ORDER BY created_at DESC, output_id DESC';
$stmt = $conn->prepare($sql);
if (!$stmt) {
    faculty_send_json(['success' => false, 'message' => 'Failed to prepare output query.'], 500);
}

$stmt->bind_param('i', $classId);
$stmt->execute();
$result = $stmt->get_result();
$outputs = [];
while ($row = $result->fetch_assoc()) {
    $outputs[] = $row;
}
$stmt->close();

faculty_send_json(['success' => true, 'outputs' => $outputs]);
