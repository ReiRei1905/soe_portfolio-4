<?php
// Database connection
$conn = new mysqli("localhost", "root", "", "soe_portfolio");

$class_id = $_POST['class_id'] ?? null;
$desc = $_POST['requirement_desc'] ?? '';

if ($class_id && $desc !== '') {
    $stmt = $conn->prepare("INSERT INTO requirements (class_id, requirement_desc, created_at) VALUES (?, ?, NOW())");
    $stmt->bind_param("is", $class_id, $desc);
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'requirement_id' => $stmt->insert_id]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Insert failed']);
    }
    $stmt->close();
} else {
    echo json_encode(['success' => false, 'error' => 'Missing data']);
}
$conn->close();
?>
