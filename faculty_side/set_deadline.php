<?php
$conn = new mysqli("localhost", "root", "", "soe_portfolio");

$class_id = $_POST['class_id'] ?? null;
$deadline_at = $_POST['deadline_at'] ?? null;

if ($class_id && $deadline_at) {
    $stmt = $conn->prepare("UPDATE classes SET deadline_at = ? WHERE class_id = ?");
    $stmt->bind_param("si", $deadline_at, $class_id);
    if ($stmt->execute()) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Update failed']);
    }
    $stmt->close();
} else {
    echo json_encode(['success' => false, 'error' => 'Missing data']);
}
$conn->close();
?>