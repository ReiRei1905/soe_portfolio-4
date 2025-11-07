<?php
// Database connection
$conn = new mysqli("localhost", "root", "", "soe_portfolio");

$class_id = $_GET['class_id'] ?? null;

if ($class_id) {
    $stmt = $conn->prepare("SELECT requirement_id, requirement_desc FROM requirements WHERE class_id = ?");
    $stmt->bind_param("i", $class_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $requirements = [];
    while ($row = $result->fetch_assoc()) {
        $requirements[] = $row;
    }
    echo json_encode(['success' => true, 'requirements' => $requirements]);
    $stmt->close();
} else {
    echo json_encode(['success' => false, 'error' => 'Missing class_id']);
}
$conn->close();
?>
