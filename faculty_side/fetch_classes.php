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

// Fetch classes from the database
$query = "SELECT c.class_id, c.class_name, c.term_number, c.start_year, c.end_year, co.course_name 
          FROM classes c 
          JOIN courses co ON c.course_id = co.course_id";

$result = $conn->query($query);

if ($result) {
    $classes = [];
    while ($row = $result->fetch_assoc()) {
        $classes[] = $row;
    }
    echo json_encode(["success" => true, "classes" => $classes]);
} else {
    error_log("Query failed: " . $conn->error);
    echo json_encode(["success" => false, "message" => "Failed to fetch classes"]);
}

$conn->close();
?>