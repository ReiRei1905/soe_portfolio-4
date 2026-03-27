<?php
require_once __DIR__ . '/common.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(405, ['ok' => false, 'message' => 'Method not allowed.']);
}

try {
    $conn = db_connect();
    $studentId = current_student_id($conn);

    $entryType = get_request_string('entry_type');
    $entryId = (int) get_request_string('entry_id');
    $newName = sanitize_name(get_request_string('new_name'));

    if (!in_array($entryType, ['file', 'folder'], true)) {
        json_response(422, ['ok' => false, 'message' => 'Invalid entry type.']);
    }
    if ($entryId <= 0) {
        json_response(422, ['ok' => false, 'message' => 'Invalid entry id.']);
    }
    if ($newName === '') {
        json_response(422, ['ok' => false, 'message' => 'New name is required.']);
    }

    if ($entryType === 'folder') {
        $stmt = $conn->prepare('UPDATE portfolio_folders SET folder_name = ?, updated_at = CURRENT_TIMESTAMP WHERE folder_id = ? AND student_id = ?');
        $stmt->bind_param('sii', $newName, $entryId, $studentId);
    } else {
        $stmt = $conn->prepare('UPDATE portfolio_files SET original_file_name = ?, updated_at = CURRENT_TIMESTAMP WHERE file_id = ? AND student_id = ?');
        $stmt->bind_param('sii', $newName, $entryId, $studentId);
    }

    $stmt->execute();
    $affected = $stmt->affected_rows;
    $stmt->close();

    if ($affected < 0) {
        json_response(500, ['ok' => false, 'message' => 'Rename failed.']);
    }

    json_response(200, ['ok' => true, 'message' => 'Entry renamed successfully.']);
} catch (Throwable $error) {
    json_response(500, ['ok' => false, 'message' => $error->getMessage()]);
}
