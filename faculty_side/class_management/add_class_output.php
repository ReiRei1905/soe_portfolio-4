<?php
$conn = new mysqli("localhost", "root", "", "soe_portfolio");
$class_id = $_POST['class_id'] ?? null;
$output_name = $_POST['output_name'] ?? '';
$total_score = $_POST['total_score'] ?? '';

if ($class_id && $output_name && $total_score) {
    $stmt = $conn->prepare("INSERT INTO class_outputs (class_id, output_name, total_score) VALUES (?, ?, ?)");
    $stmt->bind_param("isi", $class_id, $output_name, $total_score);
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'output_id' => $stmt->insert_id]);
    } else {
        echo json_encode(['success' => false]);
    }
    $stmt->close();
} else {
    echo json_encode(['success' => false]);
}
$conn->close();
?>
