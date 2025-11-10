<?php
header('Content-Type: application/json');

$connection = new mysqli("localhost", "root", "", "soe_portfolio");

if ($connection->connect_error) {
    echo json_encode(["success" => false, "message" => "Database connection failed"]);
    exit;
}

$courseId = isset($_GET['courseId']) ? intval($_GET['courseId']) : 0;

if ($courseId <= 0) {
    echo json_encode(["success" => false, "message" => "Invalid course ID"]);
    $connection->close();
    exit;
}

$stmt = $connection->prepare("SELECT COUNT(*) AS cnt FROM classes WHERE course_id = ?");
if (!$stmt) {
    echo json_encode(["success" => false, "message" => "Failed to prepare statement"]);
    $connection->close();
    exit;
}
$stmt->bind_param("i", $courseId);
$stmt->execute();
$res = $stmt->get_result();
$row = $res->fetch_assoc();
$count = intval($row['cnt'] ?? 0);
$stmt->close();
$connection->close();

echo json_encode(["success" => true, "count" => $count]);

?>
