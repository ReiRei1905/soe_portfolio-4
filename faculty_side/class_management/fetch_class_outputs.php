<?php
session_start();
header('Content-Type: application/json');
$conn = new mysqli("localhost", "root", "", "soe_portfolio");
$class_id = isset($_GET['class_id']) ? intval($_GET['class_id']) : 0;

function resolve_student_id_for_testing(mysqli $conn): int
{
    if (!empty($_SESSION['student_id'])) {
        return intval($_SESSION['student_id']);
    }

    // Optional override for local/manual testing, if it exists in students table.
    if (!empty($_GET['test_student_id'])) {
        $candidateId = intval($_GET['test_student_id']);
        $checkStmt = $conn->prepare('SELECT student_id FROM students WHERE student_id = ? LIMIT 1');
        $checkStmt->bind_param('i', $candidateId);
        $checkStmt->execute();
        $row = $checkStmt->get_result()->fetch_assoc();
        $checkStmt->close();
        if (!empty($row['student_id'])) {
            return intval($row['student_id']);
        }
    }

    // Local dev fallback: choose an existing student row.
    $firstStmt = $conn->prepare('SELECT student_id FROM students ORDER BY student_id ASC LIMIT 1');
    $firstStmt->execute();
    $first = $firstStmt->get_result()->fetch_assoc();
    $firstStmt->close();

    return !empty($first['student_id']) ? intval($first['student_id']) : 0;
}

$student_id = resolve_student_id_for_testing($conn);

if ($class_id > 0) {
    // Always join submission state for the resolved student id so student preview can be tested without login.
    if ($student_id > 0) {
        $sql = "SELECT co.output_id, co.output_name, co.total_score, co.required_file_format,
                       os.submission_id, os.student_score, os.professor_score, os.status, os.submitted_at,
                       os.submitted_file_name, os.submitted_file_mime, os.submitted_file_size
                FROM class_outputs co
                LEFT JOIN output_submissions os ON os.output_id = co.output_id AND os.student_id = ?
                WHERE co.class_id = ? ORDER BY co.created_at";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('ii', $student_id, $class_id);
    } else {
        $sql = "SELECT output_id, output_name, total_score, required_file_format FROM class_outputs WHERE class_id = ? ORDER BY created_at";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('i', $class_id);
    }

    $stmt->execute();
    $result = $stmt->get_result();
    $outputs = [];
    while ($row = $result->fetch_assoc()) {
        $outputs[] = $row;
    }
    echo json_encode(['success' => true, 'outputs' => $outputs]);
    $stmt->close();
} else {
    echo json_encode(['success' => false]);
}
$conn->close();
?>
