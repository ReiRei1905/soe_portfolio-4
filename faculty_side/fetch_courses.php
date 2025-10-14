<?php
// Connection to the airhub-soe.apc.edu.ph server
$host = "localhost"; // Remote server hostname
$user = "root"; // MySQL username
$pass = ""; // MySQL password
$db   = "soe_portfolio"; // Database name

$conn = new mysqli("localhost", "root", "", "soe_portfolio");

if ($conn->connect_error) {
    die(json_encode(["success" => false, "message" => "Database connection failed"]));
}

// Get program ID and search term from GET request
$programId = $_GET['programId'] ?? '';
$searchTerm = $_GET['searchTerm'] ?? '';

$searchTerm = '%' . $searchTerm . '%'; // Add wildcard here

if (!empty($programId)) {
    // Fetch courses for the given program ID and search term
    // Search both course_name and course_code so users can search by either
    $stmt = $conn->prepare("SELECT course_id AS id, course_name AS name, course_code FROM courses WHERE program_id = ? AND (course_name LIKE ? OR course_code LIKE ?)");
    $stmt->bind_param("iss", $programId, $searchTerm, $searchTerm);
} else {
    // Fetch courses for all program IDs if no program ID is provided
    // Search both course_name and course_code so users can search by either
    $stmt = $conn->prepare("SELECT course_id AS id, course_name AS name, course_code FROM courses WHERE course_name LIKE ? OR course_code LIKE ?");
    $stmt->bind_param("ss", $searchTerm, $searchTerm);
}

$stmt->execute();
$result = $stmt->get_result();

$courses = [];
while ($row = $result->fetch_assoc()) {
    $courses[] = $row;
}

echo json_encode($courses);
$stmt->close();
$conn->close();
?>