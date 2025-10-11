<?php
header('Content-Type: application/json');

// Database connection
$connection = new mysqli("localhost", "root", "", "soe_portfolio");

if ($connection->connect_error) {
    die(json_encode(["success" => false, "message" => "Database connection failed"]));
}

// Get data from POST request
$programId = $_POST['programId'] ?? '';
$courseName = $_POST['courseName'] ?? '';

// Debugging: Log received data
error_log("Received programId: $programId, courseName: $courseName");

if (!empty($programId) && !empty($courseName)) {
    $stmt = $connection->prepare("INSERT INTO courses (program_id, course_name) VALUES (?, ?)");
    if (!$stmt) {
        error_log("Prepare failed: " . $connection->error);
        echo json_encode(["success" => false, "message" => "Failed to prepare statement"]);
        exit;
    }

    $stmt->bind_param("is", $programId, $courseName);

    if ($stmt->execute()) {
        error_log("Course created successfully");
        echo json_encode(["success" => true, "message" => "Course created successfully"]);
    } else {
        error_log("Failed to create course: " . $stmt->error);
        echo json_encode(["success" => false, "message" => "Failed to create course"]);
    }

    $stmt->close();
} else {
    error_log("Invalid program ID or course name");
    echo json_encode(["success" => false, "message" => "Invalid program ID or course name"]);
}

$connection->close();
?>