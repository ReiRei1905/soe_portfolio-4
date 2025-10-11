<?php
$host = "localhost";
$user = "root";
$password = "";
$dbname = "soe_portfolio";

// filepath: c:\xampp\htdocs\SOE-portfolio\faculty_side\create_program.php
header('Content-Type: application/json');

/*
// Create connection
$conn = new mysqli($host, $user, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Check if the request is POST
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $programName = $_POST['programName'];

    // Insert program into the database
    $stmt = $conn->prepare("INSERT INTO programs (program_name) VALUES (?)");
    $stmt->bind_param("s", $programName);

    if ($stmt->execute()) {
        echo json_encode(["success" => true, "message" => "Program created successfully"]);
    } else {
        echo json_encode(["success" => false, "message" => "Error: " . $stmt->error]);
    }

    $stmt->close();
}

$conn->close();*/
$connection = new mysqli("localhost", "root", "", "soe_portfolio");

if ($connection->connect_error) {
    die(json_encode(["success" => false, "message" => "Database connection failed"]));
}

$programName = $_POST['programName'] ?? '';

if (!empty($programName)) {
    $stmt = $connection->prepare("INSERT INTO programs (program_name) VALUES (?)");
    $stmt->bind_param("s", $programName);
    if ($stmt->execute()) {
        echo json_encode(["success" => true]);
    } else {
        echo json_encode(["success" => false, "message" => "Failed to create program"]);
    }
    $stmt->close();
} else {
    echo json_encode(["success" => false, "message" => "Invalid program name"]);
}

$connection->close();
?>