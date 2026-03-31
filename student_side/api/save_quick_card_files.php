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

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(405, ['ok' => false, 'message' => 'Method not allowed.']);
}

try {
    $conn = db_connect();
    $studentId = current_student_id($conn);
    $portfolioId = (int) get_request_string('portfolio_id');

    if ($portfolioId <= 0) {
        json_response(422, ['ok' => false, 'message' => 'Invalid portfolio id.']);
    }

    $portfolioStmt = $conn->prepare('SELECT portfolio_id FROM extracurricular_portfolios WHERE portfolio_id = ? AND student_id = ? LIMIT 1');
    $portfolioStmt->bind_param('ii', $portfolioId, $studentId);
    $portfolioStmt->execute();
    $portfolio = $portfolioStmt->get_result()->fetch_assoc();
    $portfolioStmt->close();

    if (!$portfolio) {
        json_response(404, ['ok' => false, 'message' => 'Portfolio not found.']);
    }

    $rawIds = $_POST['file_ids'] ?? [];
    if (!is_array($rawIds)) {
        $rawIds = [];
    }

    $requestedIds = [];
    foreach ($rawIds as $value) {
        $id = (int) $value;
        if ($id > 0) {
            $requestedIds[$id] = true;
        }
    }
    $requestedIds = array_keys($requestedIds);

    $allowedIds = [];
    if (!empty($requestedIds)) {
        $fileStmt = $conn->prepare('SELECT file_id, original_file_name, mime_type FROM files WHERE student_id = ? AND file_id = ? LIMIT 1');
        foreach ($requestedIds as $candidateId) {
            $candidateId = (int) $candidateId;
            if ($candidateId <= 0) continue;

            $fileStmt->bind_param('ii', $studentId, $candidateId);
            $fileStmt->execute();
            $row = $fileStmt->get_result()->fetch_assoc();
            if (!$row) continue;

            $name = (string) ($row['original_file_name'] ?? '');
            $mimeType = (string) ($row['mime_type'] ?? 'application/octet-stream');
            if (is_allowed_file_for_portfolio($mimeType, $name)) {
                $allowedIds[] = (int) $row['file_id'];
            }
        }
        $fileStmt->close();
    }

    $conn->begin_transaction();

    $deleteStmt = $conn->prepare('DELETE FROM extracurricular_portfolio_files WHERE portfolio_id = ?');
    $deleteStmt->bind_param('i', $portfolioId);
    $deleteStmt->execute();
    $deleteStmt->close();

    if (!empty($allowedIds)) {
        $insertStmt = $conn->prepare('INSERT INTO extracurricular_portfolio_files (portfolio_id, file_id) VALUES (?, ?)');
        foreach ($allowedIds as $fileId) {
            $insertStmt->bind_param('ii', $portfolioId, $fileId);
            $insertStmt->execute();
        }
        $insertStmt->close();
    }

    $conn->commit();

    json_response(200, [
        'ok' => true,
        'message' => 'Portfolio files updated successfully.',
        'selectedFileIds' => $allowedIds
    ]);
} catch (Throwable $error) {
    if (isset($conn) && $conn instanceof mysqli) {
        try {
            $conn->rollback();
        } catch (Throwable $ignored) {
        }
    }
    json_response(500, ['ok' => false, 'message' => $error->getMessage()]);
}
