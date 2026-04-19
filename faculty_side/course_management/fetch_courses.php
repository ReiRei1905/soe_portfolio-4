<?php

declare(strict_types=1);

require_once __DIR__ . '/../faculty_access_common.php';

faculty_require_verified_faculty($conn);

$programId = isset($_GET['programId']) ? (int) $_GET['programId'] : 0;
$searchTerm = '%' . trim((string) ($_GET['searchTerm'] ?? '')) . '%';

$mappingEnabled = faculty_table_exists($conn, 'program_course_links');

/**
 * Collapse logically duplicated courses (same code/name) into one canonical option.
 * This keeps class creation search stable when legacy duplicates exist across programs.
 */
function dedupe_logical_courses(array $rows): array
{
    $deduped = [];

    foreach ($rows as $row) {
        $courseId = (int) ($row['id'] ?? 0);
        $courseName = trim((string) ($row['name'] ?? ''));
        $courseCode = strtoupper(trim((string) ($row['course_code'] ?? '')));
        $normalizedName = strtolower(preg_replace('/\s+/', ' ', $courseName) ?? $courseName);

        $key = $courseCode !== ''
            ? ('code:' . $courseCode)
            : ('name:' . $normalizedName);

        if (!isset($deduped[$key])) {
            $deduped[$key] = [
                'id' => $courseId,
                'name' => $courseName,
                'course_code' => $courseCode
            ];
            continue;
        }

        $existing = $deduped[$key];
        $existingId = (int) ($existing['id'] ?? 0);
        $existingHasCode = trim((string) ($existing['course_code'] ?? '')) !== '';
        $currentHasCode = $courseCode !== '';

        $shouldReplace = false;
        if (!$existingHasCode && $currentHasCode) {
            $shouldReplace = true;
        } elseif ($existingId <= 0 || ($courseId > 0 && $courseId < $existingId)) {
            $shouldReplace = true;
        }

        if ($shouldReplace) {
            $deduped[$key] = [
                'id' => $courseId,
                'name' => $courseName,
                'course_code' => $courseCode
            ];
        }
    }

    $courses = array_values($deduped);
    usort($courses, static function (array $a, array $b): int {
        $nameCmp = strcasecmp((string) ($a['name'] ?? ''), (string) ($b['name'] ?? ''));
        if ($nameCmp !== 0) {
            return $nameCmp;
        }

        return strcasecmp((string) ($a['course_code'] ?? ''), (string) ($b['course_code'] ?? ''));
    });

    return $courses;
}

if ($mappingEnabled) {
    if ($programId > 0) {
        $stmt = $conn->prepare(
            'SELECT DISTINCT c.course_id AS id, c.course_name AS name, c.course_code
             FROM courses c
             INNER JOIN program_course_links pcl ON pcl.course_id = c.course_id
             WHERE pcl.program_id = ?
               AND (c.course_name LIKE ? OR c.course_code LIKE ?)
             ORDER BY c.course_name ASC'
        );
        if (!$stmt) {
            faculty_send_json(['success' => false, 'message' => 'Failed to prepare course query.'], 500);
        }
        $stmt->bind_param('iss', $programId, $searchTerm, $searchTerm);
    } else {
        $stmt = $conn->prepare(
            'SELECT DISTINCT c.course_id AS id, c.course_name AS name, c.course_code
             FROM courses c
             LEFT JOIN program_course_links pcl ON pcl.course_id = c.course_id
             WHERE c.course_name LIKE ? OR c.course_code LIKE ?
             ORDER BY c.course_name ASC'
        );
        if (!$stmt) {
            faculty_send_json(['success' => false, 'message' => 'Failed to prepare course query.'], 500);
        }
        $stmt->bind_param('ss', $searchTerm, $searchTerm);
    }

    $stmt->execute();
    $result = $stmt->get_result();
    $courses = [];
    while ($row = $result->fetch_assoc()) {
        $courses[] = $row;
    }
    $stmt->close();

    $courses = dedupe_logical_courses($courses);

    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($courses);
    exit;
}

if ($programId > 0) {
    $stmt = $conn->prepare('SELECT course_id AS id, course_name AS name, course_code FROM courses WHERE program_id = ? AND (course_name LIKE ? OR course_code LIKE ?)');
    if (!$stmt) {
        faculty_send_json(['success' => false, 'message' => 'Failed to prepare course query.'], 500);
    }
    $stmt->bind_param('iss', $programId, $searchTerm, $searchTerm);
} else {
    $stmt = $conn->prepare('SELECT course_id AS id, course_name AS name, course_code FROM courses WHERE course_name LIKE ? OR course_code LIKE ?');
    if (!$stmt) {
        faculty_send_json(['success' => false, 'message' => 'Failed to prepare course query.'], 500);
    }
    $stmt->bind_param('ss', $searchTerm, $searchTerm);
}

$stmt->execute();
$result = $stmt->get_result();
$courses = [];
while ($row = $result->fetch_assoc()) {
    $courses[] = $row;
}
$stmt->close();

$courses = dedupe_logical_courses($courses);

header('Content-Type: application/json; charset=utf-8');
echo json_encode($courses);
