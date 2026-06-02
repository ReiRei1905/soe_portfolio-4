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
        ['assessments', 'Top assessments', 3],
        ['other_files', 'Top external files', 4]
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
    // Properly JOIN the students and users tables so we find the name using student_id
    $stmt = $conn->prepare("
        SELECT u.first_name, u.last_name 
        FROM users u 
        JOIN students s ON u.user_id = s.user_id 
        WHERE s.student_id = ? LIMIT 1
    ");
    $stmt->bind_param('i', $studentId);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if ($row) {
        $full = trim(((string) ($row['first_name'] ?? '')) . ' ' . ((string) ($row['last_name'] ?? '')));
        if ($full !== '') {
            return $full;
        }
    }

    return 'Student Name';
}

try {
    $conn = db_connect();
    // Use the view_student if present, otherwise fallback to the logged-in user
    $viewId = isset($_GET['view_student']) ? (int)$_GET['view_student'] : 0;
    $studentId = ($viewId > 0) ? $viewId : current_student_id($conn);

    ensure_default_extracurricular_portfolios($conn, $studentId);

    // --- FIRST UPDATE: We added profile_picture_path to this SELECT statement ---
    $profileStmt = $conn->prepare('SELECT display_name, bio, profile_picture_path FROM student_homepage_profiles WHERE student_id = ? LIMIT 1');
    $profileStmt->bind_param('i', $studentId);
    $profileStmt->execute();
    $profileRow = $profileStmt->get_result()->fetch_assoc();
    $profileStmt->close();

// Ensure the path is explicitly cast to a string
$profilePic = isset($profileRow['profile_picture_path']) ? (string)$profileRow['profile_picture_path'] : '';

    $accountName = resolve_account_name($conn, $studentId);
    $displayName = sanitize_name((string) ($profileRow['display_name'] ?? ''), 120);
    $bio = sanitize_name((string) ($profileRow['bio'] ?? ''), 160);
    
    // Grab the picture path
    $profilePic = (string) ($profileRow['profile_picture_path'] ?? '');
    // -----------------------------------------------------------------------------

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

    // --- SECOND UPDATE: We added 'profilePicture' => $profilePic to the JSON response ---
    json_response(200, [
        'ok' => true,
        'profile' => [
            'accountName' => $accountName,
            'displayName' => $displayName,
            'bio' => $bio,
            'profilePicture' => '/' . ltrim($profilePic, '/'),
        ],
        'quickCards' => $quickCards
    ]);
    // ------------------------------------------------------------------------------------

} catch (Throwable $error) {
    json_response(500, ['ok' => false, 'message' => $error->getMessage()]);
}
?>