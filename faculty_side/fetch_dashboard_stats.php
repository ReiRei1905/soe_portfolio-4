<?php
// Prevent any output before JSON
ob_start();
error_reporting(E_ALL);
ini_set('display_errors', 0);

session_start();
header('Content-Type: application/json; charset=utf-8');

require_once '../user_info_V3/connect.php';

// Use $conn instead of $connection
$connection = $conn;

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    die(json_encode(['error' => 'Unauthorized']));
}

$user_id = (int)$_SESSION['user_id'];
$selectedYear = isset($_GET['year']) ? (int)$_GET['year'] : (int)date('Y');

function buildProfessorDashboard($connection, $userId, $year) {
    // Metrics filtered by year
    $pendingQuery = "
        SELECT COALESCE(COUNT(*), 0) AS pending_count
        FROM class_portfolio_submissions cps
        INNER JOIN class_professor_assignments cpa ON cps.class_id = cpa.class_id
        INNER JOIN classes cl ON cps.class_id = cl.class_id
        LEFT JOIN class_portfolio_reviews cpr
            ON cps.class_id = cpr.class_id AND cps.student_id = cpr.student_id
        WHERE cpa.professor_user_id = ?
          AND cl.start_year = ?
          AND cps.status = 'submitted'
          AND cpr.review_id IS NULL
    ";

    $pendingStmt = $connection->prepare($pendingQuery);
    $pendingStmt->bind_param("ii", $userId, $year);
    $pendingStmt->execute();
    $pendingCount = (int)$pendingStmt->get_result()->fetch_assoc()['pending_count'];

    $gradedQuery = "
        SELECT COALESCE(COUNT(*), 0) AS graded_count
        FROM class_portfolio_reviews cpr
        INNER JOIN class_professor_assignments cpa ON cpr.class_id = cpa.class_id
        INNER JOIN classes cl ON cpr.class_id = cl.class_id
        WHERE cpa.professor_user_id = ?
          AND cl.start_year = ?
          AND cpr.reviewed_by_user_id = ?
    ";

    $gradedStmt = $connection->prepare($gradedQuery);
    $gradedStmt->bind_param("iii", $userId, $year, $userId);
    $gradedStmt->execute();
    $gradedCount = (int)$gradedStmt->get_result()->fetch_assoc()['graded_count'];

    $classQuery = "
        SELECT
            cl.class_id,
            cl.class_name,
            COALESCE(COUNT(DISTINCT CASE WHEN cps.status = 'submitted' THEN cps.student_id END), 0) AS submitted_count,
            COALESCE(COUNT(DISTINCT CASE WHEN cs.status = 'approved' THEN cs.student_id END), 0) AS total_students
        FROM classes cl
        INNER JOIN class_professor_assignments cpa ON cl.class_id = cpa.class_id
        LEFT JOIN class_students cs ON cl.class_id = cs.class_id
        LEFT JOIN class_portfolio_submissions cps ON cl.class_id = cps.class_id
        WHERE cpa.professor_user_id = ? AND cl.start_year = ?
        GROUP BY cl.class_id, cl.class_name
        ORDER BY cl.class_name ASC
    ";

    $classStmt = $connection->prepare($classQuery);
    $classStmt->bind_param("ii", $userId, $year);
    $classStmt->execute();
    $classResult = $classStmt->get_result();

    $classData = [];
    while ($row = $classResult->fetch_assoc()) {
        $totalStudents = (int)$row['total_students'];
        $submittedCount = (int)$row['submitted_count'];
        $missingCount = max(0, $totalStudents - $submittedCount);
        $classData[] = [
            'class_name' => $row['class_name'],
            'submitted_count' => $submittedCount,
            'total_students' => $totalStudents,
            'missing_count' => $missingCount
        ];
    }

    return [
        'type' => 'professor',
        'title' => 'Professor Overview',
        'subtitle' => "Grading workload for Academic Year $year",
        'data' => [
            'metrics' => [
                'pending_reviews' => $pendingCount,
                'total_graded' => $gradedCount
            ],
            'classes' => $classData
        ],
        'chart_type' => 'professor_summary'
    ];
}

function fetchCourseDifficulty($connection, $year, $programId = null) {
    $sql = "
        SELECT 
            c.course_name,
            c.course_code,
            p.program_id,
            p.program_name,
            COUNT(cdr.rating_id) as total_ratings,
            SUM(CASE WHEN cdr.difficulty_rating = 'easy' THEN 1 ELSE 0 END) as easy_count,
            SUM(CASE WHEN cdr.difficulty_rating = 'normal' THEN 1 ELSE 0 END) as normal_count,
            SUM(CASE WHEN cdr.difficulty_rating = 'hard' THEN 1 ELSE 0 END) as hard_count
        FROM courses c
        INNER JOIN programs p ON c.program_id = p.program_id
        INNER JOIN classes cl ON c.course_id = cl.course_id AND cl.start_year = ?
        INNER JOIN class_difficulty_ratings cdr ON cl.class_id = cdr.class_id
    ";

    if ($programId) {
        $sql .= " WHERE c.program_id = ? ";
    }

    $sql .= " GROUP BY c.course_id, c.course_name, c.course_code, p.program_id, p.program_name HAVING total_ratings > 0 ORDER BY hard_count DESC, normal_count DESC";

    $stmt = $connection->prepare($sql);
    if ($programId) {
        $stmt->bind_param("ii", $year, $programId);
    } else {
        $stmt->bind_param("i", $year);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    $difficultyData = [];
    while ($row = $result->fetch_assoc()) {
        $difficultyData[] = [
            'course_name' => $row['course_name'],
            'course_code' => $row['course_code'] ?? '',
            'program_id' => (int)$row['program_id'],
            'program_name' => $row['program_name'],
            'total' => (int)$row['total_ratings'],
            'easy' => (int)$row['easy_count'],
            'normal' => (int)$row['normal_count'],
            'hard' => (int)$row['hard_count']
        ];
    }
    return $difficultyData;
}

try {
    if (!$connection) {
        throw new Exception('Database connection failed');
    }
    
    // Fetch available years for the filter
    $yearsResult = $connection->query("SELECT DISTINCT start_year FROM classes ORDER BY start_year DESC");
    $availableYears = [];
    while ($yRow = $yearsResult->fetch_assoc()) {
        $availableYears[] = (int)$yRow['start_year'];
    }
    if (empty($availableYears)) $availableYears = [(int)date('Y')];
    if (!in_array($selectedYear, $availableYears)) $selectedYear = $availableYears[0];

    // Fetch faculty role and program_id
    $facultyQuery = "SELECT faculty_id, faculty_role, program_id FROM faculty WHERE user_id = ?";
    $facultyStmt = $connection->prepare($facultyQuery);
    $facultyStmt->bind_param("i", $user_id);
    $facultyStmt->execute();
    $facultyResult = $facultyStmt->get_result();
    
    if ($facultyResult->num_rows === 0) {
        http_response_code(403);
        die(json_encode(['error' => 'Faculty not found']));
    }
    
    $faculty = $facultyResult->fetch_assoc();
    $facultyRole = trim($faculty['faculty_role']);
    $programId = (int)$faculty['program_id'];
    
    $data = [
        'faculty_role' => $facultyRole,
        'selected_year' => $selectedYear,
        'available_years' => $availableYears,
        'dashboard' => []
    ];
    
    if ($facultyRole === 'executive director') {
        // ED: Total submissions per program (only show those with data)
        $query = "
            SELECT 
                p.program_id,
                p.program_name,
                COALESCE(COUNT(cps.portfolio_submission_id), 0) as total_submissions
            FROM programs p
            LEFT JOIN courses c ON p.program_id = c.program_id
            LEFT JOIN classes cl ON c.course_id = cl.course_id AND cl.start_year = ?
            LEFT JOIN class_portfolio_submissions cps ON cl.class_id = cps.class_id AND cps.status = 'submitted'
            GROUP BY p.program_id, p.program_name
            HAVING total_submissions > 0
            ORDER BY total_submissions DESC
        ";
        $stmt = $connection->prepare($query);
        $stmt->bind_param("i", $selectedYear);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $participationData = [];
        $activePrograms = [];
        while ($row = $result->fetch_assoc()) {
            $participationData[] = [
                'label' => $row['program_name'],
                'value' => (int)$row['total_submissions']
            ];
            $activePrograms[] = [
                'id' => (int)$row['program_id'],
                'name' => $row['program_name']
            ];
        }

        // ED: Course Difficulty Insights across all programs
        $difficultyData = fetchCourseDifficulty($connection, $selectedYear);
        
        $data['dashboard'] = [
            'type' => 'executive_director',
            'title' => 'School-wide Academic Insights',
            'subtitle' => "Academic Activity & Feedback for Academic Year $selectedYear",
            'participation_data' => $participationData,
            'difficulty_data' => $difficultyData,
            'available_programs' => $activePrograms,
            'chart_type' => 'split_summary'
        ];
        $data['professor_dashboard'] = buildProfessorDashboard($connection, $user_id, $selectedYear);
        
    } else if ($facultyRole === 'program director') {
        // PD: Top active students (Top 15 as requested)
        $query = "
            SELECT 
                CONCAT(COALESCE(s.first_name, 'Student'), ' ', COALESCE(s.last_name, '')) as student_name,
                COUNT(cps.portfolio_submission_id) as submission_count
            FROM students s
            INNER JOIN class_students cs ON s.student_id = cs.student_id AND cs.status = 'approved'
            INNER JOIN classes cl ON cs.class_id = cl.class_id AND cl.start_year = ?
            INNER JOIN courses c ON cl.course_id = c.course_id
            LEFT JOIN class_portfolio_submissions cps ON s.student_id = cps.student_id 
                AND cl.class_id = cps.class_id 
                AND cps.status = 'submitted'
            WHERE c.program_id = ?
            GROUP BY s.student_id, s.first_name, s.last_name
            HAVING submission_count > 0
            ORDER BY submission_count DESC, student_name ASC
            LIMIT 15
        ";
        
        $stmt = $connection->prepare($query);
        $stmt->bind_param("ii", $selectedYear, $programId);
        $stmt->execute();
        $result = $stmt->get_result();
        $topActiveStudents = [];
        while ($row = $result->fetch_assoc()) {
            $topActiveStudents[] = [
                'label' => trim($row['student_name']),
                'value' => (int)$row['submission_count']
            ];
        }

        // PD: Department-specific Difficulty Insights
        $difficultyData = fetchCourseDifficulty($connection, $selectedYear, $programId);
        
        $data['dashboard'] = [
            'type' => 'program_director',
            'title' => 'Department Academic Insights',
            'subtitle' => "Student Engagement & Course Feedback ($selectedYear)",
            'top_students' => $topActiveStudents,
            'difficulty_data' => $difficultyData,
            'chart_type' => 'split_summary'
        ];
        $data['professor_dashboard'] = buildProfessorDashboard($connection, $user_id, $selectedYear);
        
    } else if ($facultyRole === 'professor') {
        $data['dashboard'] = buildProfessorDashboard($connection, $user_id, $selectedYear);
    }
    
    ob_end_clean();
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    
} catch (Exception $e) {
    ob_end_clean();
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage(), 'type' => 'exception']);
}
if (isset($connection)) $connection->close();
