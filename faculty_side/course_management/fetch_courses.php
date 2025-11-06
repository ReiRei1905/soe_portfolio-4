    <?php
$conn = new mysqli("localhost", "root", "", "soe_portfolio");

if ($conn->connect_error) {
    die(json_encode(["success" => false, "message" => "Database connection failed"]));
}

$programId = $_GET['programId'] ?? '';
$searchTerm = $_GET['searchTerm'] ?? '';
$searchTerm = '%' . $searchTerm . '%';

if (!empty($programId)) {
    $stmt = $conn->prepare("SELECT course_id AS id, course_name AS name, course_code FROM courses WHERE program_id = ? AND (course_name LIKE ? OR course_code LIKE ?)");
    $stmt->bind_param("iss", $programId, $searchTerm, $searchTerm);
} else {
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
