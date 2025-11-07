<?php
header('Content-Type: application/json');
$conn = new mysqli("localhost", "root", "", "soe_portfolio");
if ($conn->connect_error) {
    echo json_encode(["success" => false, "message" => "Database connection failed."]);
    exit;
}

$classId = isset($_GET['class_id']) ? intval($_GET['class_id']) : 0;

if ($classId > 0) {
    $sql = "SELECT 
            c.class_id, c.class_name, c.term_number, c.start_year, c.end_year,
            co.course_id, co.course_name,
            p.program_id, p.program_name,
            c.deadline_at
        FROM classes c
        JOIN courses co ON c.course_id = co.course_id
        JOIN programs p ON co.program_id = p.program_id
        WHERE c.class_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $classId);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        echo json_encode(["success" => true, "details" => $row]);
    } else {
        echo json_encode(["success" => false, "message" => "Class not found."]);
    }
    $stmt->close();
} else {
    echo json_encode(["success" => false, "message" => "Invalid class ID."]);
}
$conn->close();
?>
