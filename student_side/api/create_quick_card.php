<?php
require_once __DIR__ . '/common.php';

function portfolio_key_from_title(string $title): string
{
    $value = strtolower(trim($title));
    $value = preg_replace('/[^a-z0-9]+/', '-', $value ?? '');
    $value = trim((string) $value, '-');
    if ($value === '') {
        return 'portfolio';
    }
    if (strlen($value) > 64) {
        $value = substr($value, 0, 64);
        $value = rtrim($value, '-');
    }
    return $value;
}

function unique_portfolio_key(mysqli $conn, int $studentId, string $baseKey): string
{
    $candidate = $baseKey;
    $suffix = 2;

    while (true) {
        $stmt = $conn->prepare('SELECT portfolio_id FROM extracurricular_portfolios WHERE student_id = ? AND portfolio_key = ? LIMIT 1');
        $stmt->bind_param('is', $studentId, $candidate);
        $stmt->execute();
        $exists = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$exists) {
            return $candidate;
        }

        $tail = '-' . $suffix;
        $maxBase = 64 - strlen($tail);
        $candidate = substr($baseKey, 0, max(1, $maxBase)) . $tail;
        $suffix++;
    }
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(405, ['ok' => false, 'message' => 'Method not allowed.']);
}

try {
    $conn = db_connect();
    $studentId = current_student_id($conn);

    $title = sanitize_name(get_request_string('title'), 120);
    if ($title === '') {
        json_response(422, ['ok' => false, 'message' => 'Portfolio title is required.']);
    }

    $baseKey = portfolio_key_from_title($title);
    $portfolioKey = unique_portfolio_key($conn, $studentId, $baseKey);

    $orderStmt = $conn->prepare('SELECT COALESCE(MAX(sort_order), 0) + 1 AS next_sort_order FROM extracurricular_portfolios WHERE student_id = ?');
    $orderStmt->bind_param('i', $studentId);
    $orderStmt->execute();
    $orderRow = $orderStmt->get_result()->fetch_assoc();
    $orderStmt->close();
    $nextOrder = (int) ($orderRow['next_sort_order'] ?? 1);

    $insertStmt = $conn->prepare('INSERT INTO extracurricular_portfolios (student_id, portfolio_key, title, sort_order, is_default) VALUES (?, ?, ?, ?, 0)');
    $insertStmt->bind_param('issi', $studentId, $portfolioKey, $title, $nextOrder);
    $insertStmt->execute();
    $portfolioId = (int) $insertStmt->insert_id;
    $insertStmt->close();

    json_response(200, [
        'ok' => true,
        'message' => 'Portfolio created successfully.',
        'quickCard' => [
            'id' => $portfolioId,
            'portfolioKey' => $portfolioKey,
            'title' => $title,
            'sortOrder' => $nextOrder,
            'isDefault' => false
        ]
    ]);
} catch (Throwable $error) {
    json_response(500, ['ok' => false, 'message' => $error->getMessage()]);
}
