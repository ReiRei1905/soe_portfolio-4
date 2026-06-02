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

function buildProfessorDashboard($connection, $userId) {
    $pendingQuery = "
        SELECT COALESCE(COUNT(*), 0) AS pending_count
        FROM class_portfolio_submissions cps
        INNER JOIN class_professor_assignments cpa ON cps.class_id = cpa.class_id
        LEFT JOIN class_portfolio_reviews cpr
            ON cps.class_id = cpr.class_id AND cps.student_id = cpr.student_id
        WHERE cpa.professor_user_id = ?
          AND cps.status = 'submitted'
          AND cpr.review_id IS NULL
    ";

    $pendingStmt = $connection->prepare($pendingQuery);
    if (!$pendingStmt) {
        throw new Exception('Prepare failed: ' . $connection->error);
    }
    $pendingStmt->bind_param("i", $userId);
    $pendingStmt->execute();
    $pendingResult = $pendingStmt->get_result();
    $pendingRow = $pendingResult->fetch_assoc();
    $pendingCount = (int)$pendingRow['pending_count'];

    $gradedQuery = "
        SELECT COALESCE(COUNT(*), 0) AS graded_count
        FROM class_portfolio_reviews cpr
        INNER JOIN class_professor_assignments cpa ON cpr.class_id = cpa.class_id
        WHERE cpa.professor_user_id = ?
          AND cpr.reviewed_by_user_id = ?
    ";

    $gradedStmt = $connection->prepare($gradedQuery);
    if (!$gradedStmt) {
        throw new Exception('Prepare failed: ' . $connection->error);
    }
    $gradedStmt->bind_param("ii", $userId, $userId);
    $gradedStmt->execute();
    $gradedResult = $gradedStmt->get_result();
    $gradedRow = $gradedResult->fetch_assoc();
    $gradedCount = (int)$gradedRow['graded_count'];

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
        WHERE cpa.professor_user_id = ?
        GROUP BY cl.class_id, cl.class_name
        ORDER BY cl.class_name ASC
    ";

    $classStmt = $connection->prepare($classQuery);
    if (!$classStmt) {
        throw new Exception('Prepare failed: ' . $connection->error);
    }
    $classStmt->bind_param("i", $userId);
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
        'subtitle' => 'Your grading workload and active classes',
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

try {
    if (!$connection) {
        throw new Exception('Database connection failed');
    }
    
    // Fetch faculty role and program_id
    $facultyQuery = "SELECT faculty_id, faculty_role, program_id FROM faculty WHERE user_id = ?";
    $facultyStmt = $connection->prepare($facultyQuery);
    
    if (!$facultyStmt) {
        throw new Exception('Prepare failed: ' . $connection->error);
    }
    
    $facultyStmt->bind_param("i", $user_id);
    $facultyStmt->execute();
    $facultyResult = $facultyStmt->get_result();
    
    if ($facultyResult->num_rows === 0) {
        http_response_code(403);
        die(json_encode(['error' => 'Faculty not found']));
    }
    
    $faculty = $facultyResult->fetch_assoc();
    $facultyRole = trim($faculty['faculty_role']);
    $facultyId = (int)$faculty['faculty_id'];
    $programId = (int)$faculty['program_id'];
    
    $data = [
        'faculty_role' => $facultyRole,
        'program_id' => $programId,
        'dashboard' => []
    ];
    
    if ($facultyRole === 'executive director') {
        // Executive Director: Total submissions per program
        $query = "
            SELECT 
                p.program_name,
                COALESCE(COUNT(co.output_id), 0) as total_submissions
            FROM programs p
            LEFT JOIN courses c ON p.program_id = c.program_id
            LEFT JOIN classes cl ON c.course_id = cl.course_id
            LEFT JOIN class_outputs co ON cl.class_id = co.class_id
            GROUP BY p.program_id, p.program_name
            ORDER BY total_submissions DESC
        ";
        
        $result = $connection->query($query);
        if (!$result) {
            throw new Exception('Query failed: ' . $connection->error);
        }
        
        $participationData = [];
        while ($row = $result->fetch_assoc()) {
            if (!empty($row['program_name'])) {
                $participationData[] = [
                    'label' => $row['program_name'],
                    'value' => (int)$row['total_submissions']
                ];
            }
        }
        
        $data['dashboard'] = [
            'type' => 'executive_director',
            'title' => 'Participation per Program',
            'subtitle' => 'Total Submissions by Program',
            'data' => $participationData,
            'chart_type' => 'vertical_bar'
        ];

        $data['professor_dashboard'] = buildProfessorDashboard($connection, $user_id);
        
    } else if ($facultyRole === 'program director') {
        // Program Director: Top students by submission count
        $query = "
            SELECT 
                CONCAT(COALESCE(s.first_name, 'Student'), ' ', COALESCE(s.last_name, '')) as student_name,
                COUNT(cps.class_id) as submission_count
            FROM students s
            INNER JOIN class_students cs ON s.student_id = cs.student_id
            INNER JOIN classes cl ON cs.class_id = cl.class_id
            INNER JOIN courses c ON cl.course_id = c.course_id
            LEFT JOIN class_portfolio_submissions cps ON s.student_id = cps.student_id 
                AND cl.class_id = cps.class_id 
                AND cps.status = 'submitted'
            WHERE c.program_id = ?
            GROUP BY s.student_id, s.first_name, s.last_name
            ORDER BY submission_count DESC, student_name ASC
            LIMIT 15
        ";
        
        $stmt = $connection->prepare($query);
        if (!$stmt) {
            throw new Exception('Prepare failed: ' . $connection->error);
        }
        
        $stmt->bind_param("i", $programId);
        $stmt->execute();
        $result = $stmt->get_result();
        $participationData = [];
        
        while ($row = $result->fetch_assoc()) {
            $participationData[] = [
                'label' => trim($row['student_name']),
                'value' => (int)$row['submission_count']
            ];
        }
        
        // Get total students in program
        $totalStudentQuery = "
            SELECT COUNT(DISTINCT s.student_id) as total_students
            FROM students s
            INNER JOIN class_students cs ON s.student_id = cs.student_id
            INNER JOIN classes cl ON cs.class_id = cl.class_id
            INNER JOIN courses c ON cl.course_id = c.course_id
            WHERE c.program_id = ?
        ";
        
        $totalStmt = $connection->prepare($totalStudentQuery);
        if ($totalStmt) {
            $totalStmt->bind_param("i", $programId);
            $totalStmt->execute();
            $totalResult = $totalStmt->get_result();
            $totalRow = $totalResult->fetch_assoc();
            $totalStudents = (int)$totalRow['total_students'];
            $inactiveCount = max(0, $totalStudents - count($participationData));
        } else {
            $inactiveCount = 0;
        }
        
        $data['dashboard'] = [
            'type' => 'program_director',
            'title' => 'Participation per Student',
            'subtitle' => 'Top 15 Most Active Students in Your Program',
            'data' => $participationData,
            'chart_type' => 'horizontal_bar',
            'metadata' => [
                'inactive_count' => $inactiveCount
            ]
        ];

        $data['professor_dashboard'] = buildProfessorDashboard($connection, $user_id);
        
    } else if ($facultyRole === 'professor') {
        // Professor: workload-focused summary and class status list
        $pendingQuery = "
            SELECT COALESCE(COUNT(*), 0) AS pending_count
            FROM class_portfolio_submissions cps
            INNER JOIN class_professor_assignments cpa ON cps.class_id = cpa.class_id
            LEFT JOIN class_portfolio_reviews cpr
                ON cps.class_id = cpr.class_id AND cps.student_id = cpr.student_id
            WHERE cpa.professor_user_id = ?
              AND cps.status = 'submitted'
              AND cpr.review_id IS NULL
        ";

        $pendingStmt = $connection->prepare($pendingQuery);
        if (!$pendingStmt) {
            throw new Exception('Prepare failed: ' . $connection->error);
        }
        $pendingStmt->bind_param("i", $user_id);
        $pendingStmt->execute();
        $pendingResult = $pendingStmt->get_result();
        $pendingRow = $pendingResult->fetch_assoc();
        $pendingCount = (int)$pendingRow['pending_count'];

        $gradedQuery = "
            SELECT COALESCE(COUNT(*), 0) AS graded_count
            FROM class_portfolio_reviews cpr
            INNER JOIN class_professor_assignments cpa ON cpr.class_id = cpa.class_id
            WHERE cpa.professor_user_id = ?
              AND cpr.reviewed_by_user_id = ?
        ";

        $gradedStmt = $connection->prepare($gradedQuery);
        if (!$gradedStmt) {
            throw new Exception('Prepare failed: ' . $connection->error);
        }
        $gradedStmt->bind_param("ii", $user_id, $user_id);
        $gradedStmt->execute();
        $gradedResult = $gradedStmt->get_result();
        $gradedRow = $gradedResult->fetch_assoc();
        $gradedCount = (int)$gradedRow['graded_count'];

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
            WHERE cpa.professor_user_id = ?
            GROUP BY cl.class_id, cl.class_name
            ORDER BY cl.class_name ASC
        ";

        $classStmt = $connection->prepare($classQuery);
        if (!$classStmt) {
            throw new Exception('Prepare failed: ' . $connection->error);
        }
        $classStmt->bind_param("i", $user_id);
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

        $data['dashboard'] = buildProfessorDashboard($connection, $user_id);
    }
    
    // Clear any buffered output
    ob_end_clean();
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    
} catch (Exception $e) {
    ob_end_clean();
    http_response_code(500);
    echo json_encode([
        'error' => $e->getMessage(),
        'type' => 'exception'
    ]);
}

if (isset($connection)) {
    $connection->close();
}
