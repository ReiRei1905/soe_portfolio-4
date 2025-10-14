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
$courseCode = $_POST['courseCode'] ?? '';

// Debugging: Log received data
error_log("Received programId: $programId, courseName: $courseName, courseCode: $courseCode");

if (!empty($programId) && !empty($courseName)) {
    // Use a query that includes course_code. Ensure the column exists in the database.
    $stmt = $connection->prepare("INSERT INTO courses (program_id, course_name, course_code) VALUES (?, ?, ?)");
    if (!$stmt) {
        error_log("Prepare failed: " . $connection->error);
        echo json_encode(["success" => false, "message" => "Failed to prepare statement"]);
        exit;
    }

    $stmt->bind_param("iss", $programId, $courseName, $courseCode);

    if ($stmt->execute()) {
        error_log("Course created successfully");
        echo json_encode(["success" => true, "message" => "Course created successfully"]);
    } else {
        error_log("Failed to create course: " . $stmt->error);
        echo json_encode(["success" => false, "message" => "Failed to create course: " . $stmt->error]);
    }

    $stmt->close();
} else {
    error_log("Invalid program ID or course name");
    echo json_encode(["success" => false, "message" => "Invalid program ID or course name"]);
}

$connection->close();
?>