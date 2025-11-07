<?php
$conn = new mysqli("localhost", "root", "", "soe_portfolio");
$output_id = $_POST['output_id'] ?? null;

if ($output_id) {
    $stmt = $conn->prepare("DELETE FROM class_outputs WHERE output_id = ?");
    $stmt->bind_param("i", $output_id);
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
