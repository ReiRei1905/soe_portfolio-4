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

    if (!in_array($entryType, ['file', 'folder'], true)) {
        json_response(422, ['ok' => false, 'message' => 'Invalid entry type.']);
    }
    if ($entryId <= 0) {
        json_response(422, ['ok' => false, 'message' => 'Invalid entry id.']);
    }

    if ($entryType === 'file') {
        $pathStmt = $conn->prepare('SELECT file_path FROM portfolio_files WHERE file_id = ? AND student_id = ? LIMIT 1');
        $pathStmt->bind_param('ii', $entryId, $studentId);
        $pathStmt->execute();
        $pathRow = $pathStmt->get_result()->fetch_assoc();
        $pathStmt->close();

        $deleteStmt = $conn->prepare('DELETE FROM portfolio_files WHERE file_id = ? AND student_id = ?');
        $deleteStmt->bind_param('ii', $entryId, $studentId);
        $deleteStmt->execute();
        $deleteStmt->close();

        if (!empty($pathRow['file_path'])) {
            $absolutePath = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, (string) $pathRow['file_path']);
            if (is_file($absolutePath)) {
                @unlink($absolutePath);
            }
        }
    } else {
        $folderStmt = $conn->prepare('SELECT folder_id FROM portfolio_folders WHERE folder_id = ? AND student_id = ? LIMIT 1');
        $folderStmt->bind_param('ii', $entryId, $studentId);
        $folderStmt->execute();
        $folder = $folderStmt->get_result()->fetch_assoc();
        $folderStmt->close();

        if ($folder) {
            $deleteFiles = $conn->prepare('DELETE FROM portfolio_files WHERE folder_id = ? AND student_id = ?');
            $deleteFiles->bind_param('ii', $entryId, $studentId);
            $deleteFiles->execute();
            $deleteFiles->close();

            $deleteFolder = $conn->prepare('DELETE FROM portfolio_folders WHERE folder_id = ? AND student_id = ?');
            $deleteFolder->bind_param('ii', $entryId, $studentId);
            $deleteFolder->execute();
            $deleteFolder->close();
        }
    }

    json_response(200, ['ok' => true, 'message' => 'Entry deleted successfully.']);
} catch (Throwable $error) {
    json_response(500, ['ok' => false, 'message' => $error->getMessage()]);
}
