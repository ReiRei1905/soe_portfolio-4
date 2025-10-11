<?php
$conn = new mysqli("localhost", "root", "", "soe_portfolio");

$requirement_id = $_POST['requirement_id'] ?? null;

if ($requirement_id) {
    $stmt = $conn->prepare("DELETE FROM requirements WHERE requirement_id = ?");
    $stmt->bind_param("i", $requirement_id);
    if ($stmt->execute()) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Delete failed']);
    }
    $stmt->close();
} else {
    echo json_encode(['success' => false, 'error' => 'Missing requirement_id']);
}
$conn->close();
?>