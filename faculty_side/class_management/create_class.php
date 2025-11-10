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
    // Validate that the course exists and get its code/name so we can build a
    // friendly, consistent class_name in the database (e.g. "PROGLOD CpE-231 Term 1 2023-2024").
    $courseStmt = $conn->prepare("SELECT course_code, course_name FROM courses WHERE course_id = ?");
    if (!$courseStmt) {
        error_log("Prepare failed (course lookup): " . $conn->error);
        die(json_encode(["success" => false, "message" => "Failed to prepare course lookup"]));
    }
    $courseStmt->bind_param("i", $courseId);
    $courseStmt->execute();
    $courseRes = $courseStmt->get_result();
    $courseRow = $courseRes->fetch_assoc();
    $courseStmt->close();

    if (!$courseRow) {
        error_log("Course not found for id: " . $courseId);
        echo json_encode(["success" => false, "message" => "Course not found."]);
        $conn->close();
        exit;
    }

    $courseCode = trim($courseRow['course_code'] ?? '');
    // Build a normalized class name. If the user already typed the course code at
    // the start of the className, avoid duplicating it.
    $inputClassName = trim($className);
    if ($courseCode !== '' && stripos($inputClassName, $courseCode) === 0) {
        // Remove the leading courseCode from user input to avoid duplication
        $inputClassName = trim(substr($inputClassName, strlen($courseCode)));
    }

    // Ensure termNumber is a plain number for display
    $termDisplay = intval($termNumber);

    $assembledClassName = trim(($courseCode ? $courseCode . ' ' : '') . $inputClassName);
    $assembledClassName .= ' Term ' . $termDisplay . ' ' . intval($startYear) . '-' . intval($endYear);

    $stmt = $conn->prepare("INSERT INTO classes (course_id, class_name, term_number, start_year, end_year) VALUES (?, ?, ?, ?, ?)");
    if (!$stmt) {
        error_log("Prepare failed (insert): " . $conn->error);
        die(json_encode(["success" => false, "message" => "Failed to prepare statement"]));
    }

    // types: i = int, s = string. course_id (i), class_name (s), term_number (i), start_year (i), end_year (i)
    $stmt->bind_param("isiii", $courseId, $assembledClassName, $termNumber, $startYear, $endYear);

    if ($stmt->execute()) {
        echo json_encode(["success" => true, "message" => "Class created successfully", "class_name" => $assembledClassName]);
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
