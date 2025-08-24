<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: ../users/login.php');
    exit();
}

require '../config/db.php';

// Get filters from GET
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

// Determine date range based on filter
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

$whereClauses = ['a.time BETWEEN ? AND ?'];
$params = [$dateStart, $dateEnd];

if ($student_id !== '' && is_numeric($student_id)) {
    $whereClauses[] = 'a.student_id = ?';
    $params[] = $student_id;
}

if ($section !== '') {
    $whereClauses[] = 's.section LIKE ?';
    $params[] = "%$section%";
}

if ($grade_level !== '') {
    $whereClauses[] = 's.grade_level = ?';
    $params[] = $grade_level;
}

$whereSQL = 'WHERE ' . implode(' AND ', $whereClauses);

// Fetch detailed attendance records matching filters, ordered by student and time ascending
$sql = "
SELECT
    s.id AS student_id,
    s.first_name,
    s.last_name,
    s.grade_level,
    s.section,
    a.time,
    a.type
FROM attendance a
JOIN students s ON a.student_id = s.id
{$whereSQL}
ORDER BY s.last_name, s.first_name, s.id, a.time ASC
";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$attendanceRecords = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Pair IN and OUT sequentially as in records.php display logic
$pairedRecords = [];
$currentStudentId = null;
$pendingInRecord = null;

// Helper functions
function formatDate($datetimeStr)
{
    return date('M d, Y', strtotime($datetimeStr));
}
function formatTime($datetimeStr)
{
    return date('h:i A', strtotime($datetimeStr));
}

foreach ($attendanceRecords as $rec) {
    if ($currentStudentId !== $rec['student_id']) {
        $currentStudentId = $rec['student_id'];
        $pendingInRecord = null;
    }

    if ($rec['type'] === 'IN') {
        if ($pendingInRecord !== null) {
            // save unmatched IN with null OUT
            $pairedRecords[] = [
                'first_name' => $pendingInRecord['first_name'],
                'last_name' => $pendingInRecord['last_name'],
                'grade_level' => $pendingInRecord['grade_level'],
                'section' => $pendingInRecord['section'],
                'date' => formatDate($pendingInRecord['time']),
                'time_in' => $pendingInRecord['time'],
                'time_out' => null,
            ];
        }
        $pendingInRecord = $rec;
    } elseif ($rec['type'] === 'OUT') {
        if ($pendingInRecord !== null) {
            $pairedRecords[] = [
                'first_name' => $pendingInRecord['first_name'],
                'last_name' => $pendingInRecord['last_name'],
                'grade_level' => $pendingInRecord['grade_level'],
                'section' => $pendingInRecord['section'],
                'date' => formatDate($pendingInRecord['time']),
                'time_in' => $pendingInRecord['time'],
                'time_out' => $rec['time'],
            ];
            $pendingInRecord = null;
        } else {
            // OUT without IN
            $pairedRecords[] = [
                'first_name' => $rec['first_name'],
                'last_name' => $rec['last_name'],
                'grade_level' => $rec['grade_level'],
                'section' => $rec['section'],
                'date' => formatDate($rec['time']),
                'time_in' => null,
                'time_out' => $rec['time'],
            ];
        }
    }
}

if ($pendingInRecord !== null) {
    $pairedRecords[] = [
        'first_name' => $pendingInRecord['first_name'],
        'last_name' => $pendingInRecord['last_name'],
        'grade_level' => $pendingInRecord['grade_level'],
        'section' => $pendingInRecord['section'],
        'date' => formatDate($pendingInRecord['time']),
        'time_in' => $pendingInRecord['time'],
        'time_out' => null,
    ];
}

// Output CSV headers
header('Content-Type: text/csv; charset=utf-8');
header(
    'Content-Disposition: attachment; filename=attendance_records_' .
        date('Ymd_His') .
        '.csv'
);

$output = fopen('php://output', 'w');

fputcsv($output, [
    'Name',
    'Grade Level',
    'Section',
    'Date',
    'Time In',
    'Time Out',
]);

// Output rows
foreach ($pairedRecords as $rec) {
    fputcsv($output, [
        $rec['first_name'] . ' ' . $rec['last_name'],
        $rec['grade_level'],
        $rec['section'],
        $rec['date'],
        $rec['time_in'] ? formatTime($rec['time_in']) : '',
        $rec['time_out'] ? formatTime($rec['time_out']) : '',
    ]);
}

fclose($output);
exit();
