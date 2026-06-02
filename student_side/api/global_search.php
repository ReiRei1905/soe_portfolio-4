<?php
require_once __DIR__ . '/common.php';

try {
    $conn = db_connect();
    
    // Get the search query
    $search = isset($_GET['q']) ? trim($_GET['q']) : '';
    if ($search === '') {
        json_response(200, ['ok' => true, 'results' => []]);
    }
    
    $like = '%' . $search . '%';
    
    // Search the database for matching students
    $stmt = $conn->prepare("
        SELECT s.student_id, u.first_name, u.last_name, u.email
        FROM students s
        JOIN users u ON s.user_id = u.user_id
        WHERE u.first_name LIKE ? OR u.last_name LIKE ? OR CONCAT(u.first_name, ' ', u.last_name) LIKE ?
        LIMIT 10
    ");
    $stmt->bind_param('sss', $like, $like, $like);
    $stmt->execute();
    $res = $stmt->get_result();
    
    $results = [];
    while($row = $res->fetch_assoc()){
        $results[] = [
            'student_id' => (int)$row['student_id'],
            'name' => trim($row['first_name'] . ' ' . $row['last_name']),
            'email' => $row['email']
        ];
    }
    
    $stmt->close();
    json_response(200, ['ok' => true, 'results' => $results]);

} catch (Throwable $error) {
    json_response(500, ['ok' => false, 'message' => $error->getMessage()]);
}
?>