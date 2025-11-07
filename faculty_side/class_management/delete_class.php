<?php
// Database connection
$conn = new mysqli("localhost", "root", "", "soe_portfolio");

if ($conn->connect_error) {
    die(json_encode(["success" => false, "message" => "Connection failed: " . $conn->connect_error]));
}

// Get class ID from POST request
$classId = isset($_POST['classId']) ? intval($_POST['classId']) : 0;

if ($classId > 0) {
    $stmt = $conn->prepare("DELETE FROM classes WHERE class_id = ?");
    if (!$stmt) {
        echo json_encode(["success" => false, "message" => "Failed to prepare statement: " . $conn->error]);
        exit;
    }
    $stmt->bind_param("i", $classId);

    if ($stmt->execute()) {
        echo json_encode(["success" => true, "message" => "Class deleted successfully."]);
    } else {
        echo json_encode(["success" => false, "message" => "Failed to delete class."]);
    }

    $stmt->close();
} else {
    echo json_encode(["success" => false, "message" => "Invalid class ID."]);
}

$conn->close();
?>
