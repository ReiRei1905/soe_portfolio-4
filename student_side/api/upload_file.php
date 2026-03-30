<?php
require_once __DIR__ . '/common.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(405, ['ok' => false, 'message' => 'Method not allowed.']);
}

try {
    $conn = db_connect();
    $categoryKey = require_category_key(get_request_string('category'));
    $displayName = sanitize_name(get_request_string('display_name'));
    $folderName = sanitize_name(get_request_string('folder_name'));

    $studentId = current_student_id($conn);
    $categoryId = category_id_by_key($conn, $categoryKey);

    $folderId = null;
    if ($folderName !== '') {
        $folderId = ensure_folder($conn, $studentId, $categoryId, $folderName);
    }

    $hasFile = isset($_FILES['file']) && is_array($_FILES['file']) && ($_FILES['file']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE;
    $mimeType = 'application/octet-stream';
    $fileSize = 0;
    $storedFileName = '';
    $relativePath = '';

    if (!$hasFile) {
        json_response(422, ['ok' => false, 'message' => 'Please select an actual file to upload.']);
    }

    if ((int) $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
        json_response(422, ['ok' => false, 'message' => 'Upload failed.']);
    }

    $sourceName = sanitize_name((string) $_FILES['file']['name']);
    if ($displayName === '') {
        $displayName = $sourceName;
    }

    $extension = pathinfo($sourceName, PATHINFO_EXTENSION);
    $storedFileName = uniqid('pf_', true) . ($extension ? '.' . $extension : '');

    $baseDir = upload_root_dir();
    $studentDir = $baseDir . DIRECTORY_SEPARATOR . 'student_' . $studentId . DIRECTORY_SEPARATOR . $categoryKey;
    if (!is_dir($studentDir)) {
        mkdir($studentDir, 0775, true);
    }

    $absolutePath = $studentDir . DIRECTORY_SEPARATOR . $storedFileName;
    if (!move_uploaded_file($_FILES['file']['tmp_name'], $absolutePath)) {
        json_response(500, ['ok' => false, 'message' => 'Could not save uploaded file.']);
    }

    $mimeType = (string) ($_FILES['file']['type'] ?? 'application/octet-stream');
    $fileSize = (int) ($_FILES['file']['size'] ?? 0);
    $relativePath = relative_path_from_repo($absolutePath);

    $stmt = $conn->prepare('INSERT INTO files (student_id, category_id, folder_id, original_file_name, stored_file_name, file_path, mime_type, file_size) VALUES (?, ?, ?, ?, ?, ?, ?, ?)');
    $stmt->bind_param('iiissssi', $studentId, $categoryId, $folderId, $displayName, $storedFileName, $relativePath, $mimeType, $fileSize);
    $stmt->execute();
    $fileId = (int) $stmt->insert_id;
    $stmt->close();

    json_response(200, [
        'ok' => true,
        'entry' => [
            'id' => $fileId,
            'name' => $displayName,
            'category' => $categoryKey,
            'folder' => $folderName,
            'entryType' => 'file',
            'timestamp' => date('Y-m-d H:i:s')
        ]
    ]);
} catch (Throwable $error) {
    json_response(500, ['ok' => false, 'message' => $error->getMessage()]);
}
