<?php

declare(strict_types=1);

require_once __DIR__ . '/../faculty_access_common.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    faculty_send_json(['success' => false, 'message' => 'Method not allowed.'], 405);
}

$sessionUser = faculty_require_verified_faculty($conn);
$classId = isset($_POST['classId']) ? (int) $_POST['classId'] : 0;
$professorUserId = isset($_POST['professorUserId']) ? (int) $_POST['professorUserId'] : 0;

if ($classId <= 0 || $professorUserId <= 0) {
    faculty_send_json(['success' => false, 'message' => 'Invalid assignment payload.'], 400);
}

if (!faculty_can_manage_class($conn, $sessionUser, $classId)) {
    faculty_send_json(['success' => false, 'message' => 'You are not allowed to assign professors for this class.'], 403);
}

if (!faculty_table_exists($conn, 'class_professor_assignments')) {
    faculty_send_json([
        'success' => false,
        'message' => 'Table class_professor_assignments is missing. Please run the required SQL migration first.'
    ], 500);
}

$profStmt = $conn->prepare(
    "SELECT u.user_id, u.status, u.is_verified, u.role_type, f.faculty_role,
            CONCAT(COALESCE(f.first_name, u.first_name), ' ', COALESCE(f.last_name, u.last_name)) AS full_name
     FROM users u
     INNER JOIN faculty f ON f.user_id = u.user_id
     WHERE u.user_id = ?
     LIMIT 1"
);
if (!$profStmt) {
    faculty_send_json(['success' => false, 'message' => 'Failed to validate selected professor.'], 500);
}

$profStmt->bind_param('i', $professorUserId);
$profStmt->execute();
$profRes = $profStmt->get_result();
$profRow = $profRes ? $profRes->fetch_assoc() : null;
$profStmt->close();

if (!$profRow) {
    faculty_send_json(['success' => false, 'message' => 'Selected user is not a faculty account.'], 400);
}

$isVerifiedFaculty = strtolower((string) ($profRow['role_type'] ?? '')) === 'faculty'
    && (int) ($profRow['status'] ?? 0) === 1
    && (int) ($profRow['is_verified'] ?? 0) === 1;

if (!$isVerifiedFaculty) {
    faculty_send_json(['success' => false, 'message' => 'Selected user must be a verified Faculty account.'], 400);
}

$actingUserId = (int) ($sessionUser['user_id'] ?? 0);
$upsert = $conn->prepare(
    'INSERT INTO class_professor_assignments (class_id, professor_user_id, assigned_by_user_id)
     VALUES (?, ?, ?)
     ON DUPLICATE KEY UPDATE professor_user_id = VALUES(professor_user_id), assigned_by_user_id = VALUES(assigned_by_user_id), assigned_at = CURRENT_TIMESTAMP'
);
if (!$upsert) {
    faculty_send_json(['success' => false, 'message' => 'Failed to save professor assignment.'], 500);
}

$upsert->bind_param('iii', $classId, $professorUserId, $actingUserId);
$ok = $upsert->execute();
$upsert->close();

if (!$ok) {
    faculty_send_json(['success' => false, 'message' => 'Failed to assign professor.'], 500);
}

faculty_send_json([
    'success' => true,
    'message' => 'Professor assigned successfully.',
    'assignedProfessorName' => (string) ($profRow['full_name'] ?? '')
]);
