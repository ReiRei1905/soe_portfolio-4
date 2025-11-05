<?php
$host = "localhost";
$user = "root";
$password = "";
$dbname = "soe_portfolio";

// filepath: c:\xampp\htdocs\SOE-portfolio\faculty_side\program_management\create_program.php
header('Content-Type: application/json');

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
