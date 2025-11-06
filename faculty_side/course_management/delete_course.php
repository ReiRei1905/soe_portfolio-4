<?php
header('Content-Type: application/json');

$connection = new mysqli("localhost", "root", "", "soe_portfolio");

if ($connection->connect_error) {
    die(json_encode(["success" => false, "message" => "Database connection failed"]));
}

$courseId = $_POST['courseId'] ?? '';

if (!empty($courseId)) {
    $stmt = $connection->prepare("DELETE FROM courses WHERE course_id = ?");
    $stmt->bind_param("i", $courseId);
    if ($stmt->execute()) {
        echo json_encode(["success" => true, "message" => "Course deleted successfully"]);
    } else {
        echo json_encode(["success" => false, "message" => "Failed to delete course"]);
    }
    $stmt->close();
} else {
    echo json_encode(["success" => false, "message" => "Invalid course ID"]);
}

$connection->close();
?>
