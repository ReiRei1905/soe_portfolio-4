<?php

declare(strict_types=1);

require_once __DIR__ . '/../faculty_access_common.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    faculty_send_json(['success' => false, 'message' => 'Method not allowed.'], 405);
}

$sessionUser = faculty_require_verified_faculty($conn);
$classId = isset($_POST['class_id']) ? (int) $_POST['class_id'] : 0;
$outputName = trim((string) ($_POST['output_name'] ?? ''));
$totalScore = isset($_POST['total_score']) ? (int) $_POST['total_score'] : 0;
$requiredFileFormat = trim((string) ($_POST['required_file_format'] ?? ''));

$allowedFormats = ['.docx', '.pdf', '.xlsx', '.png/.jpg'];

if ($classId <= 0 || $outputName === '' || $totalScore <= 0 || !in_array($requiredFileFormat, $allowedFormats, true)) {
    faculty_send_json(['success' => false, 'message' => 'Invalid output payload.'], 400);
}

if (!faculty_can_handle_class($conn, $sessionUser, $classId)) {
    faculty_send_json(['success' => false, 'message' => 'You are not allowed to add outputs for this class.'], 403);
}

$stmt = $conn->prepare('INSERT INTO class_outputs (class_id, output_name, total_score, required_file_format) VALUES (?, ?, ?, ?)');
if (!$stmt) {
    faculty_send_json(['success' => false, 'message' => 'Failed to prepare output insert.'], 500);
}

$stmt->bind_param('isis', $classId, $outputName, $totalScore, $requiredFileFormat);
$ok = $stmt->execute();
$outputId = (int) $stmt->insert_id;
$stmt->close();

if (!$ok) {
    faculty_send_json(['success' => false, 'message' => 'Failed to add output.'], 500);
}

faculty_send_json(['success' => true, 'output_id' => $outputId]);
