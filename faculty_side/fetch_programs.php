<?php
$host = "localhost";
$user = "root";
$password = "";
$dbname = "soe_portfolio";

header('Content-Type: application/json');

/*
// Connect to the database
$conn = new mysqli($host, $user, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Fetch programs
$result = $conn->query("SELECT * FROM programs");
$programs = [];

while ($row = $result->fetch_assoc()) {
    $programs[] = $row;
}

echo json_encode($programs);

$conn->close();*/

$connection = new mysqli("localhost", "root", "", "soe_portfolio");

if ($connection->connect_error) {
    die(json_encode(["success" => false, "message" => "Database connection failed"]));
}

$query = "SELECT program_id AS id, program_name AS name FROM programs";
$result = $connection->query($query);

$programs = [];
while ($row = $result->fetch_assoc()) {
    $programs[] = $row;
}

echo json_encode($programs);
$connection->close();
?>
