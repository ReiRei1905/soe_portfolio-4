<?php
// Enable error logging (do not display errors to users)
ini_set('display_errors', 0);
ini_set('log_errors', 1);
error_reporting(E_ALL);
ini_set('error_log', 'php_errors.log');

// Database connection
$conn = new mysqli("localhost", "root", "", "soe_portfolio");

if ($conn->connect_error) {
    error_log("Database connection failed: " . $conn->connect_error);
    die(json_encode(["success" => false, "message" => "Connection failed: " . $conn->connect_error]));
}

// Get data from POST request and normalize types
$courseId = isset($_POST['courseId']) ? intval($_POST['courseId']) : 0;
$className = isset($_POST['className']) ? trim($_POST['className']) : '';
$termNumber = isset($_POST['termNumber']) ? intval($_POST['termNumber']) : 0;
$startYear = isset($_POST['startYear']) ? intval($_POST['startYear']) : 0;
$endYear = isset($_POST['endYear']) ? intval($_POST['endYear']) : 0;

error_log("Received data: courseId=$courseId, className=$className, termNumber=$termNumber, startYear=$startYear, endYear=$endYear");

if ($courseId > 0 && $className !== '' && $termNumber > 0 && $startYear > 0 && $endYear > 0) {
    $stmt = $conn->prepare("INSERT INTO classes (course_id, class_name, term_number, start_year, end_year) VALUES (?, ?, ?, ?, ?)");
    if (!$stmt) {
        error_log("Prepare failed: " . $conn->error);
        die(json_encode(["success" => false, "message" => "Failed to prepare statement"]));
    }

    // types: i = int, s = string. course_id (i), class_name (s), term_number (i), start_year (i), end_year (i)
    $stmt->bind_param("isiii", $courseId, $className, $termNumber, $startYear, $endYear);

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
