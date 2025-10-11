<?php
header('Content-Type: application/json');

// Database connection
$connection = new mysqli("localhost", "root", "", "soe_portfolio");

if ($connection->connect_error) {
    echo json_encode(["success" => false, "message" => "Database connection failed."]);
    exit;
}

// Get course ID and new course name from POST request
$courseId = isset($_POST['courseId']) ? intval($_POST['courseId']) : 0;
$newCourseName = isset($_POST['newCourseName']) ? trim($_POST['newCourseName']) : "";

if ($courseId > 0 && !empty($newCourseName)) {
    $stmt = $connection->prepare("UPDATE courses SET course_name = ? WHERE course_id = ?");
    $stmt->bind_param("si", $newCourseName, $courseId);

    if ($stmt->execute()) {
        echo json_encode(["success" => true, "message" => "Course updated successfully."]);
    } else {
        echo json_encode(["success" => false, "message" => "Failed to update course."]);
    }

    $stmt->close();
} else {
    echo json_encode(["success" => false, "message" => "Invalid input."]);
}

$connection->close();
?>