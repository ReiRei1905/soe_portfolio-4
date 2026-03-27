<?php
require_once __DIR__ . '/common.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(405, ['ok' => false, 'message' => 'Method not allowed.']);
}

try {
    $conn = db_connect();
    $categoryKey = require_category_key(get_request_string('category'));
    $folderName = sanitize_name(get_request_string('folder_name'));

    if ($folderName === '') {
        json_response(422, ['ok' => false, 'message' => 'Folder name is required.']);
    }

    $studentId = current_student_id($conn);
    $categoryId = category_id_by_key($conn, $categoryKey);
    $folderId = ensure_folder($conn, $studentId, $categoryId, $folderName);

    json_response(200, [
        'ok' => true,
        'entry' => [
            'id' => $folderId,
            'name' => $folderName,
            'category' => $categoryKey,
            'folder' => $folderName,
            'entryType' => 'folder',
            'timestamp' => date('Y-m-d H:i:s')
        ]
    ]);
} catch (Throwable $error) {
    json_response(500, ['ok' => false, 'message' => $error->getMessage()]);
}
