<?php
$conn = new mysqli("localhost", "root", "", "soe_portfolio");
$class_id = $_POST['class_id'] ?? null;
$output_name = $_POST['output_name'] ?? '';
$total_score = $_POST['total_score'] ?? '';
$required_file_format = trim($_POST['required_file_format'] ?? '');

$allowed_formats = ['.docx', '.pdf', '.xlsx', '.png/.jpg'];

if ($class_id && $output_name && $total_score && in_array($required_file_format, $allowed_formats, true)) {
    $stmt = $conn->prepare("INSERT INTO class_outputs (class_id, output_name, total_score, required_file_format) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("isis", $class_id, $output_name, $total_score, $required_file_format);
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
