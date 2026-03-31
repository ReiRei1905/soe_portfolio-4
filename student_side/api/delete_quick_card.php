<?php
require_once __DIR__ . '/common.php';

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

    $stmt = $conn->prepare('DELETE FROM extracurricular_portfolios WHERE portfolio_id = ? AND student_id = ?');
    $stmt->bind_param('ii', $portfolioId, $studentId);
    $stmt->execute();
    $stmt->close();

    json_response(200, ['ok' => true, 'message' => 'Portfolio deleted successfully.']);
} catch (Throwable $error) {
    json_response(500, ['ok' => false, 'message' => $error->getMessage()]);
}
