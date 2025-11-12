<?php
session_start();
header('Content-Type: application/json');
$conn = new mysqli("localhost", "root", "", "soe_portfolio");
$class_id = isset($_GET['class_id']) ? intval($_GET['class_id']) : 0;
$student_id = isset($_SESSION['student_id']) ? intval($_SESSION['student_id']) : null;

if ($class_id > 0) {
    // If student is logged in, left join their submissions so client can show submitted state
    if ($student_id) {
        $sql = "SELECT co.output_id, co.output_name, co.total_score,
                       os.submission_id, os.student_score, os.professor_score, os.status, os.submitted_at
                FROM class_outputs co
                LEFT JOIN output_submissions os ON os.output_id = co.output_id AND os.student_id = ?
                WHERE co.class_id = ? ORDER BY co.created_at";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('ii', $student_id, $class_id);
    } else {
        $sql = "SELECT output_id, output_name, total_score FROM class_outputs WHERE class_id = ? ORDER BY created_at";
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
