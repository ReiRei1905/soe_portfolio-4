<?php
$conn = new mysqli("localhost", "root", "", "soe_portfolio");
$output_id = $_POST['output_id'] ?? null;
$output_name = $_POST['output_name'] ?? '';
$total_score = $_POST['total_score'] ?? '';
$required_file_format = trim($_POST['required_file_format'] ?? '');

$allowed_formats = ['.docx', '.pdf', '.xlsx', '.png/.jpg'];

if ($output_id && $output_name && $total_score && in_array($required_file_format, $allowed_formats, true)) {
    $stmt = $conn->prepare("UPDATE class_outputs SET output_name = ?, total_score = ?, required_file_format = ? WHERE output_id = ?");
    $stmt->bind_param("sisi", $output_name, $total_score, $required_file_format, $output_id);
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
