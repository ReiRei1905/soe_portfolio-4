<?php

declare(strict_types=1);

require_once __DIR__ . '/../faculty_access_common.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    faculty_send_json(['success' => false, 'message' => 'Method not allowed.'], 405);
}

$sessionUser = faculty_require_verified_faculty($conn);
$classId = isset($_POST['classId']) ? (int) $_POST['classId'] : 0;

if ($classId <= 0) {
    faculty_send_json(['success' => false, 'message' => 'Invalid class id.'], 400);
}

if (!faculty_can_manage_class($conn, $sessionUser, $classId)) {
    faculty_send_json(['success' => false, 'message' => 'You are not allowed to revoke professor assignments for this class.'], 403);
}

if (!faculty_table_exists($conn, 'class_professor_assignments')) {
    faculty_send_json([
        'success' => false,
        'message' => 'Table class_professor_assignments is missing. Please run the required SQL migration first.'
    ], 500);
}

$currentStmt = $conn->prepare(
    "SELECT cpa.professor_user_id,
            CONCAT(COALESCE(f.first_name, u.first_name), ' ', COALESCE(f.last_name, u.last_name)) AS full_name
     FROM class_professor_assignments cpa
     LEFT JOIN users u ON u.user_id = cpa.professor_user_id
     LEFT JOIN faculty f ON f.user_id = u.user_id
     WHERE cpa.class_id = ?
     LIMIT 1"
);

if (!$currentStmt) {
    faculty_send_json(['success' => false, 'message' => 'Failed to load current class professor assignment.'], 500);
}

$currentStmt->bind_param('i', $classId);
$currentStmt->execute();
$currentRes = $currentStmt->get_result();
$currentRow = $currentRes ? $currentRes->fetch_assoc() : null;
$currentStmt->close();

$currentProfessorUserId = (int) ($currentRow['professor_user_id'] ?? 0);
if ($currentProfessorUserId <= 0) {
    faculty_send_json(['success' => true, 'message' => 'No professor is currently assigned to this class.']);
}

$deleteStmt = $conn->prepare('DELETE FROM class_professor_assignments WHERE class_id = ?');
if (!$deleteStmt) {
    faculty_send_json(['success' => false, 'message' => 'Failed to prepare revoke statement.'], 500);
}

$deleteStmt->bind_param('i', $classId);
$ok = $deleteStmt->execute();
$deleteStmt->close();

if (!$ok) {
    faculty_send_json(['success' => false, 'message' => 'Failed to revoke class professor assignment.'], 500);
}

faculty_send_json([
    'success' => true,
    'message' => 'Professor assignment revoked successfully.',
    'previousProfessorName' => (string) ($currentRow['full_name'] ?? '')
]);
