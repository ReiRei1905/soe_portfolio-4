<?php
header('Content-Type: application/json');

$connection = new mysqli("localhost", "root", "", "soe_portfolio");

if ($connection->connect_error) {
    echo json_encode(["success" => false, "message" => "Database connection failed."]);
    exit;
}

$courseId = isset($_POST['courseId']) ? intval($_POST['courseId']) : 0;
$newCourseName = isset($_POST['newCourseName']) ? trim($_POST['newCourseName']) : "";
$newCourseCode = isset($_POST['newCourseCode']) ? trim($_POST['newCourseCode']) : "";

if ($courseId > 0 && !empty($newCourseName)) {
    // Update both name and code. Allow code to be empty string if not provided.
    $stmt = $connection->prepare("UPDATE courses SET course_name = ?, course_code = ? WHERE course_id = ?");
    $stmt->bind_param("ssi", $newCourseName, $newCourseCode, $courseId);

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
