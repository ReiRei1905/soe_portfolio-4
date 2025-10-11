<?php
header('Content-Type: application/json');

// Database connection
$connection = new mysqli("localhost", "root", "", "soe_portfolio");

if ($connection->connect_error) {
    echo json_encode(["success" => false, "message" => "Database connection failed."]);
    exit;
}

$programId = $_POST['programId'] ?? '';

if (!empty($programId)) {
    // Check if there are courses associated with the program
    $stmt = $connection->prepare("SELECT COUNT(*) FROM courses WHERE program_id = ?");
    $stmt->bind_param("i", $programId);
    $stmt->execute();
    $stmt->bind_result($courseCount);
    $stmt->fetch();
    $stmt->close();

    // Check if there are users (faculty) associated with the program
    $stmt = $connection->prepare("SELECT COUNT(*) FROM faculty WHERE program_id = ?");
    $stmt->bind_param("i", $programId);
    $stmt->execute();
    $stmt->bind_result($facultyCount);
    $stmt->fetch();
    $stmt->close();

    // If there are related courses or faculty, prevent deletion
    if ($courseCount > 0 || $facultyCount > 0) {
        echo json_encode([
            "success" => false,
            "message" => "Cannot delete program. There are courses or faculty associated with this program."
        ]);
    } else {
        // Proceed with deletion if no dependencies
        $stmt = $connection->prepare("DELETE FROM programs WHERE program_id = ?");
        $stmt->bind_param("i", $programId);
        if ($stmt->execute() && $stmt->affected_rows > 0) {
            echo json_encode([
                "success" => true,
                "message" => "Program deleted successfully."
            ]);
        } else {
            // Log the error for debugging
            error_log("Failed to delete program with ID: $programId. Error: " . $stmt->error);
            echo json_encode([
                "success" => false,
                "message" => "Failed to delete program. Please try again."
            ]);
        }
        $stmt->close();
    }
} else {
    echo json_encode([
        "success" => false,
        "message" => "Invalid program ID."
    ]);
}

$connection->close();
?>