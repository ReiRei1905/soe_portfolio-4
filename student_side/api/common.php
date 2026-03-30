<?php
session_start();

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

function db_connect(): mysqli
{
    $conn = new mysqli('localhost', 'root', '', 'soe_portfolio');
    $conn->set_charset('utf8mb4');
    return $conn;
}

function json_response(int $statusCode, array $payload): void
{
    http_response_code($statusCode);
    header('Content-Type: application/json');
    echo json_encode($payload);
    exit;
}

function get_request_string(string $key, string $default = ''): string
{
    if (isset($_POST[$key])) return trim((string) $_POST[$key]);
    if (isset($_GET[$key])) return trim((string) $_GET[$key]);
    return $default;
}

function require_category_key(string $key): string
{
    $allowed = ['assessment', 'projects', 'certificates'];
    if (!in_array($key, $allowed, true)) {
        json_response(422, ['ok' => false, 'message' => 'Invalid category.']);
    }
    return $key;
}

function sanitize_name(string $name, int $max = 255): string
{
    $value = trim($name);
    $value = preg_replace('/\s+/', ' ', $value ?? '');
    $value = str_replace(["\0", '/', '\\'], '', $value);
    if ($value === '') return '';
    if (mb_strlen($value) > $max) {
        $value = mb_substr($value, 0, $max);
    }
    return $value;
}

function current_student_id(mysqli $conn): int
{
    foreach (['student_id', 'user_id', 'id'] as $sessionKey) {
        if (!empty($_SESSION[$sessionKey])) {
            return (int) $_SESSION[$sessionKey];
        }
    }

    $email = trim((string) ($_SESSION['email'] ?? ''));
    if ($email !== '') {
        $stmt = $conn->prepare('SELECT user_id FROM users WHERE email = ? LIMIT 1');
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!empty($result['user_id'])) {
            $_SESSION['user_id'] = (int) $result['user_id'];
            return (int) $result['user_id'];
        }
    }

    // Dev fallback for solo/local testing.
    return 1;
}

function category_id_by_key(mysqli $conn, string $categoryKey): int
{
    $stmt = $conn->prepare('SELECT category_id FROM academic_categories WHERE category_key = ? LIMIT 1');
    $stmt->bind_param('s', $categoryKey);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$row) {
        json_response(500, ['ok' => false, 'message' => 'Category seed data missing. Run SQL setup first.']);
    }

    return (int) $row['category_id'];
}

function ensure_folder(mysqli $conn, int $studentId, int $categoryId, string $folderName): int
{
    $cleanName = sanitize_name($folderName);
    if ($cleanName === '') {
        json_response(422, ['ok' => false, 'message' => 'Folder name is required.']);
    }

    $select = $conn->prepare('SELECT folder_id FROM folders WHERE student_id = ? AND category_id = ? AND folder_name = ? LIMIT 1');
    $select->bind_param('iis', $studentId, $categoryId, $cleanName);
    $select->execute();
    $found = $select->get_result()->fetch_assoc();
    $select->close();

    if (!empty($found['folder_id'])) {
        return (int) $found['folder_id'];
    }

    $insert = $conn->prepare('INSERT INTO folders (student_id, category_id, folder_name) VALUES (?, ?, ?)');
    $insert->bind_param('iis', $studentId, $categoryId, $cleanName);
    $insert->execute();
    $folderId = (int) $insert->insert_id;
    $insert->close();

    return $folderId;
}

function upload_root_dir(): string
{
    $dir = rtrim(sys_get_temp_dir(), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'soe_portfolio_uploads';
    if (!is_dir($dir)) {
        mkdir($dir, 0775, true);
    }
    return $dir;
}

function relative_path_from_repo(string $absolutePath): string
{
    if (preg_match('/^[A-Za-z]:\\\\|^\//', $absolutePath) === 1) {
        return $absolutePath;
    }

    $repoRoot = dirname(__DIR__, 2);
    $repoRoot = rtrim(str_replace('\\', '/', $repoRoot), '/');
    $target = str_replace('\\', '/', $absolutePath);

    if (strpos($target, $repoRoot) === 0) {
        return ltrim(substr($target, strlen($repoRoot)), '/');
    }

    return $target;
}
