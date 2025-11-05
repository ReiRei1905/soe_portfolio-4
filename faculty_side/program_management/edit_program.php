<?php
header('Content-Type: application/json');

// Database connection
$connection = new mysqli("localhost", "root", "", "soe_portfolio");

if ($connection->connect_error) {
    echo json_encode(["success" => false, "message" => "Database connection failed."]);
    exit;
}

// Get program ID and new program name from POST request
$programId = isset($_POST['programId']) ? intval($_POST['programId']) : 0;
$newProgramName = isset($_POST['newProgramName']) ? trim($_POST['newProgramName']) : "";

if ($programId > 0 && !empty($newProgramName)) {
    $stmt = $connection->prepare("UPDATE programs SET program_name = ? WHERE program_id = ?");
    $stmt->bind_param("si", $newProgramName, $programId);

    if ($stmt->execute()) {
        echo json_encode(["success" => true, "message" => "Program updated successfully."]);
    } else {
        echo json_encode(["success" => false, "message" => "Failed to update program."]);
    }

    $stmt->close();
} else {
    echo json_encode(["success" => false, "message" => "Invalid input."]);
}

$connection->close();
?>
