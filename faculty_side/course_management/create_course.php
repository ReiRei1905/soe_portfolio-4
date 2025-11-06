<?php
header('Content-Type: application/json');

$connection = new mysqli("localhost", "root", "", "soe_portfolio");

if ($connection->connect_error) {
    die(json_encode(["success" => false, "message" => "Database connection failed"]));
}

$programId = $_POST['programId'] ?? '';
$courseName = $_POST['courseName'] ?? '';
$courseCode = $_POST['courseCode'] ?? '';

if (!empty($programId) && !empty($courseName)) {
    $stmt = $connection->prepare("INSERT INTO courses (program_id, course_name, course_code) VALUES (?, ?, ?)");
    if (!$stmt) {
        echo json_encode(["success" => false, "message" => "Prepare failed: " . $connection->error]);
        exit;
    }
    $stmt->bind_param("iss", $programId, $courseName, $courseCode);
    if ($stmt->execute()) {
        echo json_encode(["success" => true, "message" => "Course created successfully"]);
    } else {
        echo json_encode(["success" => false, "message" => "Failed to create course: " . $stmt->error]);
    }
    $stmt->close();
} else {
    echo json_encode(["success" => false, "message" => "Invalid program ID or course name"]);
}

$connection->close();
?>
