<?php
// Database connection
$host = "localhost"; // Change to your database host if needed
$user = "root";
$pass = "";
$db   = "soe_portfolio";

$conn = new mysqli("localhost", "root", "", "soe_portfolio");

if ($conn->connect_error) {
    die(json_encode(["success" => false, "message" => "Connection failed: " . $conn->connect_error]));
}

// Get class ID and new class name from POST request
$classId = isset($_POST['classId']) ? intval($_POST['classId']) : 0;
$newClassName = isset($_POST['newClassName']) ? trim($_POST['newClassName']) : "";

if ($classId > 0 && !empty($newClassName)) {
    $stmt = $conn->prepare("UPDATE classes SET class_name = ?, updated_at = NOW() WHERE class_id = ?");
    $stmt->bind_param("si", $newClassName, $classId);

    if ($stmt->execute()) {
        echo json_encode(["success" => true, "message" => "Class name updated successfully."]);
    } else {
        echo json_encode(["success" => false, "message" => "Failed to update class name."]);
    }

    $stmt->close();
} else {
    echo json_encode(["success" => false, "message" => "Invalid input."]);
}

$conn->close();
?>