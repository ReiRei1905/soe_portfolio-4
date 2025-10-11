<?php
$conn = new mysqli("localhost", "root", "", "soe_portfolio");
$class_id = $_GET['class_id'] ?? null;
if ($class_id) {
    $stmt = $conn->prepare("SELECT output_id, output_name, total_score FROM class_outputs WHERE class_id = ?");
    $stmt->bind_param("i", $class_id);
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