<?php
require_once __DIR__ . '/common.php';

function ensure_default_extracurricular_portfolios(mysqli $conn, int $studentId): void
{
    $countStmt = $conn->prepare('SELECT COUNT(*) AS total FROM extracurricular_portfolios WHERE student_id = ?');
    $countStmt->bind_param('i', $studentId);
    $countStmt->execute();
    $countRow = $countStmt->get_result()->fetch_assoc();
    $countStmt->close();

    if (!empty($countRow['total'])) {
        return;
    }

    $defaults = [
        ['projects', 'Top projects', 1],
        ['certificates', 'Top certificates/awards', 2],
        ['assessments', 'Top assessments', 3]
    ];

    $insertStmt = $conn->prepare('INSERT INTO extracurricular_portfolios (student_id, portfolio_key, title, sort_order, is_default) VALUES (?, ?, ?, ?, 1)');
    foreach ($defaults as $item) {
        [$key, $title, $order] = $item;
        $insertStmt->bind_param('issi', $studentId, $key, $title, $order);
        $insertStmt->execute();
    }
    $insertStmt->close();
}

function resolve_account_name(mysqli $conn, int $studentId): string
{
    $studentStmt = $conn->prepare('SELECT first_name, last_name FROM students WHERE student_id = ? OR user_id = ? ORDER BY CASE WHEN student_id = ? THEN 0 ELSE 1 END LIMIT 1');
    $studentStmt->bind_param('iii', $studentId, $studentId, $studentId);
    $studentStmt->execute();
    $studentRow = $studentStmt->get_result()->fetch_assoc();
    $studentStmt->close();

    if ($studentRow) {
        $full = trim(((string) ($studentRow['first_name'] ?? '')) . ' ' . ((string) ($studentRow['last_name'] ?? '')));
        if ($full !== '') {
            return $full;
        }
    }

    $userStmt = $conn->prepare('SELECT first_name, last_name FROM users WHERE user_id = ? LIMIT 1');
    $userStmt->bind_param('i', $studentId);
    $userStmt->execute();
    $userRow = $userStmt->get_result()->fetch_assoc();
    $userStmt->close();

    if ($userRow) {
        $full = trim(((string) ($userRow['first_name'] ?? '')) . ' ' . ((string) ($userRow['last_name'] ?? '')));
        if ($full !== '') {
            return $full;
        }
    }

    return 'Student Name';
}

try {
    $conn = db_connect();
    $studentId = current_student_id($conn);

    ensure_default_extracurricular_portfolios($conn, $studentId);

    $profileStmt = $conn->prepare('SELECT display_name, bio FROM student_homepage_profiles WHERE student_id = ? LIMIT 1');
    $profileStmt->bind_param('i', $studentId);
    $profileStmt->execute();
    $profileRow = $profileStmt->get_result()->fetch_assoc();
    $profileStmt->close();

    $accountName = resolve_account_name($conn, $studentId);
    $displayName = sanitize_name((string) ($profileRow['display_name'] ?? ''), 120);
    $bio = sanitize_name((string) ($profileRow['bio'] ?? ''), 160);

    $cardsStmt = $conn->prepare('SELECT portfolio_id, portfolio_key, title, sort_order, is_default FROM extracurricular_portfolios WHERE student_id = ? ORDER BY sort_order ASC, portfolio_id ASC');
    $cardsStmt->bind_param('i', $studentId);
    $cardsStmt->execute();
    $cardsResult = $cardsStmt->get_result();

    $quickCards = [];
    while ($row = $cardsResult->fetch_assoc()) {
        $quickCards[] = [
            'id' => (int) $row['portfolio_id'],
            'portfolioKey' => (string) $row['portfolio_key'],
            'title' => (string) $row['title'],
            'sortOrder' => (int) $row['sort_order'],
            'isDefault' => ((int) ($row['is_default'] ?? 0)) === 1
        ];
    }
    $cardsStmt->close();

    json_response(200, [
        'ok' => true,
        'profile' => [
            'accountName' => $accountName,
            'displayName' => $displayName,
            'bio' => $bio
        ],
        'quickCards' => $quickCards
    ]);
} catch (Throwable $error) {
    json_response(500, ['ok' => false, 'message' => $error->getMessage()]);
}
