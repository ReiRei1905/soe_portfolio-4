<?php
require_once __DIR__ . '/common.php';

try {
    $conn = db_connect();
    $studentId = current_student_id($conn);

    $fileId = (int) get_request_string('file_id');
    $download = get_request_string('download') === '1';

    if ($fileId <= 0) {
        http_response_code(422);
        header('Content-Type: text/plain; charset=utf-8');
        echo 'Invalid file id.';
        exit;
    }

    $stmt = $conn->prepare('SELECT original_file_name, mime_type, file_path FROM portfolio_files WHERE file_id = ? AND student_id = ? LIMIT 1');
    $stmt->bind_param('ii', $fileId, $studentId);
    $stmt->execute();
    $file = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$file) {
        http_response_code(404);
        header('Content-Type: text/plain; charset=utf-8');
        echo 'File not found.';
        exit;
    }

    $filePath = (string) ($file['file_path'] ?? '');
    $absolutePath = '';
    if ($filePath !== '') {
        if (preg_match('/^[A-Za-z]:\\\\|^\//', $filePath) === 1) {
            $absolutePath = $filePath;
        } else {
            $absolutePath = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $filePath);
        }
    }

    if ($absolutePath === '' || !is_file($absolutePath)) {
        http_response_code(404);
        header('Content-Type: text/plain; charset=utf-8');
        echo 'File content is unavailable. It may have been uploaded in metadata-only mode.';
        exit;
    }

    $safeName = basename((string) ($file['original_file_name'] ?? 'downloaded_file'));
    $mimeType = (string) ($file['mime_type'] ?? 'application/octet-stream');

    header('Content-Type: ' . $mimeType);
    header('Content-Length: ' . filesize($absolutePath));

    $disposition = $download ? 'attachment' : 'inline';
    header("Content-Disposition: {$disposition}; filename=\"" . addslashes($safeName) . "\"");
    header('X-Content-Type-Options: nosniff');

    readfile($absolutePath);
    exit;
} catch (Throwable $error) {
    http_response_code(500);
    header('Content-Type: text/plain; charset=utf-8');
    echo 'Unable to access file: ' . $error->getMessage();
    exit;
}
