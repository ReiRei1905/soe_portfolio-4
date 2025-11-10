<?php
header('Content-Type: application/json');

$connection = new mysqli("localhost", "root", "", "soe_portfolio");

if ($connection->connect_error) {
    die(json_encode(["success" => false, "message" => "Database connection failed"]));
}

$courseId = $_POST['courseId'] ?? '';

if (!empty($courseId)) {
    $stmt = $connection->prepare("DELETE FROM courses WHERE course_id = ?");
    $stmt->bind_param("i", $courseId);
    // Check for existing classes that reference this course
    $checkStmt = $connection->prepare("SELECT COUNT(*) AS cnt FROM classes WHERE course_id = ?");
    if (!$checkStmt) {
        echo json_encode(["success" => false, "message" => "Failed to prepare dependency check."]);
        exit;
    }
    $checkStmt->bind_param("i", $courseId);
    $checkStmt->execute();
    $res = $checkStmt->get_result();
    $row = $res->fetch_assoc();
    $count = intval($row['cnt'] ?? 0);
    $checkStmt->close();

    if ($count > 0) {
        // Prevent deletion when there are dependent classes. Return helpful message.
        echo json_encode(["success" => false, "message" => "Cannot delete course: it is used by {$count} class(es). Please remove or reassign those classes first."]);
        exit;
    }

    // No dependent classes found — safe to delete
    if (!$stmt) {
        echo json_encode(["success" => false, "message" => "Failed to prepare delete statement."]);
        exit;
    }
    $stmt->bind_param("i", $courseId);
    if ($stmt->execute()) {
        echo json_encode(["success" => true, "message" => "Course deleted successfully"]);
    } else {
        echo json_encode(["success" => false, "message" => "Failed to delete course"]);
    }
    $stmt->close();
} else {
    echo json_encode(["success" => false, "message" => "Invalid course ID"]);
}

$connection->close();
?>
