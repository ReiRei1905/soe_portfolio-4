<?php
require_once __DIR__ . '/common.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(405, ['ok' => false, 'message' => 'Method not allowed.']);
}

try {
    $conn = db_connect();
    $studentId = current_student_id($conn);

    $portfolioId = (int) get_request_string('portfolio_id');
    $title = sanitize_name(get_request_string('title'), 120);

    if ($portfolioId <= 0) {
        json_response(422, ['ok' => false, 'message' => 'Invalid portfolio id.']);
    }
    if ($title === '') {
        json_response(422, ['ok' => false, 'message' => 'Portfolio title is required.']);
    }

    $stmt = $conn->prepare('UPDATE extracurricular_portfolios SET title = ?, updated_at = CURRENT_TIMESTAMP WHERE portfolio_id = ? AND student_id = ?');
    $stmt->bind_param('sii', $title, $portfolioId, $studentId);
    $stmt->execute();
    $affected = $stmt->affected_rows;
    $stmt->close();

    if ($affected < 0) {
        json_response(500, ['ok' => false, 'message' => 'Failed to update portfolio title.']);
    }

    json_response(200, [
        'ok' => true,
        'message' => 'Portfolio title updated.',
        'quickCard' => [
            'id' => $portfolioId,
            'title' => $title
        ]
    ]);
} catch (Throwable $error) {
    json_response(500, ['ok' => false, 'message' => $error->getMessage()]);
}
