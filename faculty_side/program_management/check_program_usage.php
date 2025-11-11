<?php
header('Content-Type: application/json');

$connection = new mysqli("localhost", "root", "", "soe_portfolio");

if ($connection->connect_error) {
    echo json_encode(["success" => false, "message" => "Database connection failed"]);
    exit;
}

$programId = isset($_GET['programId']) ? intval($_GET['programId']) : 0;

if ($programId <= 0) {
    echo json_encode(["success" => false, "message" => "Invalid program ID"]);
    $connection->close();
    exit;
}

$out = ["success" => true, "courses" => 0, "faculty" => 0];

// count courses
$stmt = $connection->prepare("SELECT COUNT(*) AS cnt FROM courses WHERE program_id = ?");
if ($stmt) {
    $stmt->bind_param("i", $programId);
    $stmt->execute();
    $res = $stmt->get_result();
    $row = $res->fetch_assoc();
    $out['courses'] = intval($row['cnt'] ?? 0);
    $stmt->close();
}

// count faculty
$stmt2 = $connection->prepare("SELECT COUNT(*) AS cnt FROM faculty WHERE program_id = ?");
if ($stmt2) {
    $stmt2->bind_param("i", $programId);
    $stmt2->execute();
    $res2 = $stmt2->get_result();
    $row2 = $res2->fetch_assoc();
    $out['faculty'] = intval($row2['cnt'] ?? 0);
    $stmt2->close();
}

$connection->close();

echo json_encode($out);

?>
