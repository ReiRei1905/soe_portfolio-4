<?php
$conn = new mysqli("localhost", "root", "", "soe_portfolio");

$requirement_id = $_POST['requirement_id'] ?? null;
$requirement_desc = $_POST['requirement_desc'] ?? '';

if ($requirement_id && $requirement_desc !== '') {
    $stmt = $conn->prepare("UPDATE requirements SET requirement_desc = ?, updated_at = NOW() WHERE requirement_id = ?");
    $stmt->bind_param("si", $requirement_desc, $requirement_id);
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
