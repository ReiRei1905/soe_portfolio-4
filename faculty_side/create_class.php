<?php
// Enable error reporting for debugging
ini_set('display_errors', 0); // Disable direct error display
ini_set('log_errors', 1); // Enable error logging
error_reporting(E_ALL); // Report all errors
ini_set('error_log', 'php_errors.log'); // Log errors to a file

// Database connection
$host = "localhost";
$user = "root";
$pass = "";
$db   = "soe_portfolio";

$conn = new mysqli("localhost", "root", "", "soe_portfolio");

if ($conn->connect_error) {
    error_log("Database connection failed: " . $conn->connect_error);
    die(json_encode(["success" => false, "message" => "Connection failed: " . $conn->connect_error]));
}

// Get data from POST request
$courseId = $_POST['courseId'] ?? '';
$className = $_POST['className'] ?? '';
$termNumber = $_POST['termNumber'] ?? '';
$startYear = $_POST['startYear'] ?? '';
$endYear = $_POST['endYear'] ?? '';

// Log the received data for debugging
error_log("Received data: courseId=$courseId, className=$className, termNumber=$termNumber, startYear=$startYear, endYear=$endYear");

if (!empty($courseId) && !empty($className) && !empty($termNumber) && !empty($startYear) && !empty($endYear)) {
    $stmt = $conn->prepare("INSERT INTO classes (course_id, class_name, term_number, start_year, end_year) VALUES (?, ?, ?, ?, ?)");
    if (!$stmt) {
        error_log("Prepare failed: " . $conn->error);
        die(json_encode(["success" => false, "message" => "Failed to prepare statement"]));
    }

    $stmt->bind_param("issii", $courseId, $className, $termNumber, $startYear, $endYear);

    if ($stmt->execute()) {
        echo json_encode(["success" => true, "message" => "Class created successfully"]);
    } else {
        error_log("Execute failed: " . $stmt->error);
        echo json_encode(["success" => false, "message" => "Failed to create class"]);
    }

    $stmt->close();
} else {
    error_log("Invalid input data: " . json_encode($_POST));
    echo json_encode(["success" => false, "message" => "Invalid input data"]);
}

$conn->close();
?>