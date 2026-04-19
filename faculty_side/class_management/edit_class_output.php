<?php

declare(strict_types=1);

require_once __DIR__ . '/../faculty_access_common.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    faculty_send_json(['success' => false, 'message' => 'Method not allowed.'], 405);
}

$sessionUser = faculty_require_verified_faculty($conn);
$outputId = isset($_POST['output_id']) ? (int) $_POST['output_id'] : 0;
$outputName = trim((string) ($_POST['output_name'] ?? ''));
$totalScore = isset($_POST['total_score']) ? (int) $_POST['total_score'] : 0;
$requiredFileFormat = trim((string) ($_POST['required_file_format'] ?? ''));

$allowedFormats = ['.docx', '.pdf', '.xlsx', '.png/.jpg'];

if ($outputId <= 0 || $outputName === '' || $totalScore <= 0 || !in_array($requiredFileFormat, $allowedFormats, true)) {
    faculty_send_json(['success' => false, 'message' => 'Invalid output payload.'], 400);
}

$classStmt = $conn->prepare('SELECT class_id FROM class_outputs WHERE output_id = ? LIMIT 1');
if (!$classStmt) {
    faculty_send_json(['success' => false, 'message' => 'Failed to validate output.'], 500);
}
$classStmt->bind_param('i', $outputId);
$classStmt->execute();
$classResult = $classStmt->get_result();
$classRow = $classResult ? $classResult->fetch_assoc() : null;
$classStmt->close();

if (!$classRow) {
    faculty_send_json(['success' => false, 'message' => 'Output not found.'], 404);
}

$classId = (int) ($classRow['class_id'] ?? 0);
if (!faculty_can_handle_class($conn, $sessionUser, $classId)) {
    faculty_send_json(['success' => false, 'message' => 'You are not allowed to edit outputs for this class.'], 403);
}

$stmt = $conn->prepare('UPDATE class_outputs SET output_name = ?, total_score = ?, required_file_format = ? WHERE output_id = ?');
if (!$stmt) {
    faculty_send_json(['success' => false, 'message' => 'Failed to prepare output update.'], 500);
}

$stmt->bind_param('sisi', $outputName, $totalScore, $requiredFileFormat, $outputId);
$ok = $stmt->execute();
$stmt->close();

if (!$ok) {
    faculty_send_json(['success' => false, 'message' => 'Failed to update output.'], 500);
}

faculty_send_json(['success' => true]);
