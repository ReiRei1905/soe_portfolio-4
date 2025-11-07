<?php
$conn = new mysqli("localhost", "root", "", "soe_portfolio");
$output_id = $_POST['output_id'] ?? null;
$output_name = $_POST['output_name'] ?? '';
$total_score = $_POST['total_score'] ?? '';

if ($output_id && $output_name && $total_score) {
    $stmt = $conn->prepare("UPDATE class_outputs SET output_name = ?, total_score = ? WHERE output_id = ?");
    $stmt->bind_param("sii", $output_name, $total_score, $output_id);
    if ($stmt->execute()) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false]);
    }
    $stmt->close();
} else {
    echo json_encode(['success' => false]);
}
$conn->close();
?>
