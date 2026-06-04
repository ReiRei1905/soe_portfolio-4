<?php

declare(strict_types=1);

require_once __DIR__ . '/../admin_api_common.php';

$role = strtolower(trim((string) ($_GET['role'] ?? 'all')));
$status = strtolower(trim((string) ($_GET['status'] ?? 'all')));
$search = trim((string) ($_GET['search'] ?? ''));
$sortField = trim((string) ($_GET['sort'] ?? 'f.created_at'));
$sortOrder = strtolower(trim((string) ($_GET['order'] ?? 'desc')));

$dir = ($sortOrder === 'asc') ? 'ASC' : 'DESC';
$orderBy = "f.created_at {$dir}, f.feedback_id {$dir}";

$allowedSortColumns = [
    'f.subject', 'f.message', 'f.user_role', 'f.user_email', 'f.status', 'f.created_at', 'full_name'
];

if (in_array($sortField, $allowedSortColumns, true)) {
    if ($sortField === 'full_name') {
        $orderBy = "u.first_name {$dir}, u.last_name {$dir}, f.created_at DESC";
    } else {
        $orderBy = "{$sortField} {$dir}, f.created_at DESC";
    }
}

$where = [];
$params = [];
$types = '';

if ($role !== '' && $role !== 'all') {
    $where[] = 'f.user_role = ?';
    $types .= 's';
    $params[] = $role;
}

if ($status !== '' && $status !== 'all') {
    $where[] = 'f.status = ?';
    $types .= 's';
    $params[] = $status;
}

if ($search !== '') {
    $where[] = '(f.subject LIKE ? OR f.message LIKE ? OR f.user_email LIKE ? OR CONCAT(u.first_name, " ", u.last_name) LIKE ?)';
    $like = '%' . $search . '%';
    $types .= 'ssss';
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
}

$sql = "
    SELECT f.feedback_id, f.user_id, f.user_role, f.user_email, f.subject, f.message,
           f.screenshot_path, f.screenshot_name, f.status, f.created_at,
           u.first_name, u.last_name
    FROM feedbacks f
    LEFT JOIN users u ON u.user_id = f.user_id
";

if ($where) {
    $sql .= ' WHERE ' . implode(' AND ', $where);
}

$sql .= " ORDER BY {$orderBy} LIMIT 200";

$stmt = $conn->prepare($sql);
if (!$stmt) {
    send_json(['success' => false, 'message' => 'Unable to load feedbacks.'], 500);
}

if ($params) {
    $stmt->bind_param($types, ...$params);
}

$stmt->execute();
$result = $stmt->get_result();

$rows = [];
$today = date('Y-m-d');

while ($row = $result->fetch_assoc()) {
    $roleLabel = format_role_label((string) ($row['user_role'] ?? ''));
    $createdAt = (string) ($row['created_at'] ?? '');
    $submissionDate = date('Y-m-d', strtotime($createdAt));
    $isNew = ($submissionDate === $today);

    $rows[] = [
        'feedback_id' => (int) $row['feedback_id'],
        'user_id' => (int) $row['user_id'],
        'user_email' => (string) ($row['user_email'] ?? ''),
        'subject' => (string) ($row['subject'] ?? ''),
        'message' => (string) ($row['message'] ?? ''),
        'screenshot_path' => (string) ($row['screenshot_path'] ?? ''),
        'screenshot_name' => (string) ($row['screenshot_name'] ?? ''),
        'status' => (string) ($row['status'] ?? 'new'),
        'is_new_submission' => $isNew,
        'created_at' => $createdAt !== '' ? date('d/m/Y h:i A', strtotime($createdAt)) : 'N/A',
        'full_name' => trim((string) ($row['first_name'] ?? '') . ' ' . (string) ($row['last_name'] ?? '')),
        'role_label' => $roleLabel
    ];
}

$stmt->close();

send_json(['success' => true, 'feedbacks' => $rows]);

function format_role_label(string $role): string
{
    $role = strtolower(trim($role));
    return match ($role) {
        'executive director' => 'Executive Director',
        'program director' => 'Program Director',
        'professor' => 'Professor',
        'student' => 'Student',
        'admin' => 'Admin',
        default => $role !== '' ? ucfirst($role) : 'Unknown'
    };
}
