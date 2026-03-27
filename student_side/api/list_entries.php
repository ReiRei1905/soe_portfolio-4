<?php
require_once __DIR__ . '/common.php';

try {
    $conn = db_connect();
    $categoryKey = require_category_key(get_request_string('category'));
    $studentId = current_student_id($conn);
    $categoryId = category_id_by_key($conn, $categoryKey);

    $entries = [];

    $folderStmt = $conn->prepare('SELECT folder_id, folder_name, created_at, updated_at FROM portfolio_folders WHERE student_id = ? AND category_id = ? AND parent_folder_id IS NULL ORDER BY created_at DESC');
    $folderStmt->bind_param('ii', $studentId, $categoryId);
    $folderStmt->execute();
    $folders = $folderStmt->get_result();
    while ($row = $folders->fetch_assoc()) {
        $entries[] = [
            'id' => (int) $row['folder_id'],
            'name' => $row['folder_name'],
            'category' => $categoryKey,
            'folder' => $row['folder_name'],
            'entryType' => 'folder',
            'timestamp' => $row['updated_at'] ?? $row['created_at']
        ];
    }
    $folderStmt->close();

    $fileStmt = $conn->prepare('SELECT f.file_id, f.original_file_name, f.mime_type, f.file_size, f.created_at, f.updated_at, d.folder_name FROM portfolio_files f LEFT JOIN portfolio_folders d ON d.folder_id = f.folder_id WHERE f.student_id = ? AND f.category_id = ? ORDER BY f.created_at DESC');
    $fileStmt->bind_param('ii', $studentId, $categoryId);
    $fileStmt->execute();
    $files = $fileStmt->get_result();
    while ($row = $files->fetch_assoc()) {
        $entries[] = [
            'id' => (int) $row['file_id'],
            'name' => $row['original_file_name'],
            'category' => $categoryKey,
            'folder' => $row['folder_name'] ?? '',
            'entryType' => 'file',
            'timestamp' => $row['updated_at'] ?? $row['created_at'],
            'fileSize' => (int) ($row['file_size'] ?? 0),
            'mimeType' => $row['mime_type'] ?? 'application/octet-stream'
        ];
    }
    $fileStmt->close();

    json_response(200, ['ok' => true, 'entries' => $entries]);
} catch (Throwable $error) {
    json_response(500, ['ok' => false, 'message' => $error->getMessage()]);
}
