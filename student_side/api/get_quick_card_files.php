<?php
require_once __DIR__ . '/common.php';

function is_allowed_file_for_portfolio(string $mimeType, string $fileName): bool
{
    $mime = strtolower(trim($mimeType));
    $name = strtolower(trim($fileName));

    if ($mime === 'application/pdf' || strpos($mime, 'image/png') === 0 || strpos($mime, 'image/jpeg') === 0) {
        return true;
    }

    return preg_match('/\.(pdf|png|jpe?g)$/i', $name) === 1;
}

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    json_response(405, ['ok' => false, 'message' => 'Method not allowed.']);
}

try {
    $conn = db_connect();
    $studentId = current_student_id($conn);
    $portfolioId = (int) get_request_string('portfolio_id');

    if ($portfolioId <= 0) {
        json_response(422, ['ok' => false, 'message' => 'Invalid portfolio id.']);
    }

    $portfolioStmt = $conn->prepare('SELECT portfolio_id, portfolio_key, title FROM extracurricular_portfolios WHERE portfolio_id = ? AND student_id = ? LIMIT 1');
    $portfolioStmt->bind_param('ii', $portfolioId, $studentId);
    $portfolioStmt->execute();
    $portfolio = $portfolioStmt->get_result()->fetch_assoc();
    $portfolioStmt->close();

    if (!$portfolio) {
        json_response(404, ['ok' => false, 'message' => 'Portfolio not found.']);
    }

    $filesStmt = $conn->prepare('SELECT f.file_id, f.original_file_name, f.mime_type, f.category_id, ac.category_key, ac.category_label, fo.folder_name FROM files f INNER JOIN academic_categories ac ON ac.category_id = f.category_id LEFT JOIN folders fo ON fo.folder_id = f.folder_id WHERE f.student_id = ? ORDER BY ac.category_key ASC, fo.folder_name ASC, f.original_file_name ASC');
    $filesStmt->bind_param('i', $studentId);
    $filesStmt->execute();
    $filesResult = $filesStmt->get_result();

    $availableFiles = [];
    while ($row = $filesResult->fetch_assoc()) {
        $fileId = (int) $row['file_id'];
        $name = (string) ($row['original_file_name'] ?? '');
        $mimeType = (string) ($row['mime_type'] ?? 'application/octet-stream');

        if (!is_allowed_file_for_portfolio($mimeType, $name)) {
            continue;
        }

        $availableFiles[] = [
            'id' => $fileId,
            'name' => $name,
            'mimeType' => $mimeType,
            'categoryKey' => (string) ($row['category_key'] ?? ''),
            'categoryLabel' => (string) ($row['category_label'] ?? ''),
            'folderName' => (string) ($row['folder_name'] ?? '')
        ];
    }
    $filesStmt->close();

    $selectedStmt = $conn->prepare('SELECT epf.file_id FROM extracurricular_portfolio_files epf INNER JOIN files f ON f.file_id = epf.file_id WHERE epf.portfolio_id = ? AND f.student_id = ? ORDER BY epf.created_at ASC');
    $selectedStmt->bind_param('ii', $portfolioId, $studentId);
    $selectedStmt->execute();
    $selectedResult = $selectedStmt->get_result();

    $selectedFileIds = [];
    while ($row = $selectedResult->fetch_assoc()) {
        $selectedFileIds[] = (int) $row['file_id'];
    }
    $selectedStmt->close();

    $selectedLookup = array_flip($selectedFileIds);
    $attachedFiles = [];
    foreach ($availableFiles as $file) {
        if (isset($selectedLookup[$file['id']])) {
            $attachedFiles[] = $file;
        }
    }

    json_response(200, [
        'ok' => true,
        'portfolio' => [
            'id' => (int) $portfolio['portfolio_id'],
            'portfolioKey' => (string) $portfolio['portfolio_key'],
            'title' => (string) $portfolio['title']
        ],
        'availableFiles' => $availableFiles,
        'selectedFileIds' => $selectedFileIds,
        'attachedFiles' => $attachedFiles
    ]);
} catch (Throwable $error) {
    json_response(500, ['ok' => false, 'message' => $error->getMessage()]);
}
