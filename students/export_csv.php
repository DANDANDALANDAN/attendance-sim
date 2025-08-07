<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: ../users/login.php');
    exit;
}

require '../config/db.php';

$student_id = $_GET['student_id'] ?? '';
$section = trim($_GET['section'] ?? '');
$grade_level = $_GET['grade_level'] ?? '';
$date_filter = $_GET['date_filter'] ?? 'daily';
$date_value = $_GET['date_value'] ?? date('Y-m-d');

try {
    $date = new DateTime($date_value);
} catch (Exception $e) {
    $date = new DateTime();
    $date_value = $date->format('Y-m-d');
}

switch ($date_filter) {
    case 'weekly':
        $weekStart = clone $date;
        $weekStart->modify('monday this week');
        $weekEnd = clone $weekStart;
        $weekEnd->modify('sunday this week 23:59:59');
        $dateStart = $weekStart->format('Y-m-d 00:00:00');
        $dateEnd = $weekEnd->format('Y-m-d 23:59:59');
        break;
    case 'monthly':
        $dateStart = $date->format('Y-m-01 00:00:00');
        $dateEnd = $date->format('Y-m-t 23:59:59');
        break;
    case 'daily':
    default:
        $dateStart = $date->format('Y-m-d 00:00:00');
        $dateEnd = $date->format('Y-m-d 23:59:59');
        break;
}

$whereClauses = ["a.time BETWEEN ? AND ?"];
$params = [$dateStart, $dateEnd];

if ($student_id !== '' && is_numeric($student_id)) {
    $whereClauses[] = "a.student_id = ?";
    $params[] = $student_id;
}

if ($section !== '') {
    $whereClauses[] = "s.section LIKE ?";
    $params[] = "%$section%";
}

if ($grade_level !== '') {
    $whereClauses[] = "s.grade_level = ?";
    $params[] = $grade_level;
}

$whereSQL = 'WHERE ' . implode(' AND ', $whereClauses);

$sql = "
SELECT 
    s.first_name,
    s.last_name,
    s.grade_level,
    s.section,
    DATE(a.time) AS attendance_date,
    MIN(CASE WHEN a.type = 'IN' THEN a.time ELSE NULL END) AS time_in,
    MAX(CASE WHEN a.type = 'OUT' THEN a.time ELSE NULL END) AS time_out
FROM attendance a
JOIN students s ON a.student_id = s.id
{$whereSQL}
GROUP BY s.id, attendance_date
ORDER BY attendance_date DESC, s.last_name, s.first_name
";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$records = $stmt->fetchAll();

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=attendance_records_' . date('Ymd_His') . '.csv');

$output = fopen('php://output', 'w');

fputcsv($output, ['Name', 'Grade Level', 'Section', 'Date', 'Time In', 'Time Out']);

foreach ($records as $rec) {
    fputcsv($output, [
        $rec['first_name'] . ' ' . $rec['last_name'],
        $rec['grade_level'],
        $rec['section'],
        date('Y-m-d', strtotime($rec['attendance_date'])),
        $rec['time_in'] ? date('h:i A', strtotime($rec['time_in'])) : '',
        $rec['time_out'] ? date('h:i A', strtotime($rec['time_out'])) : '',
    ]);
}

fclose($output);
exit;
