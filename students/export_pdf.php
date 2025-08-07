<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: ../users/login.php');
    exit;
}

require '../config/db.php';
require_once '../tcpdf/tcpdf.php';  // Adjust path if necessary

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

// Initialize TCPDF
$pdf = new TCPDF('L', PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

$pdf->SetCreator('Student Attendance System');
$pdf->SetAuthor('Student Attendance System');
$pdf->SetTitle('Attendance Records');
$pdf->SetSubject('Attendance Records Export');

$pdf->setPrintHeader(false);
$pdf->setPrintFooter(false);
$pdf->SetMargins(10, 10, 10);
$pdf->SetAutoPageBreak(TRUE, 10);
$pdf->AddPage();

// Title
$pdf->SetFont('helvetica', 'B', 16);
$pdf->Cell(0, 10, 'Attendance Records', 0, 1, 'C');

// Filters info
$pdf->SetFont('helvetica', '', 10);
$filterInfo = "Date Filter: " . ucfirst($date_filter) . ", Date: " . $date_value;
$pdf->MultiCell(0, 5, $filterInfo, 0, 'L', 0, 1);

// Table
$pdf->SetFont('helvetica', 'B', 12);
$html = <<<EOD
<table border="1" cellpadding="4">
    <tr style="background-color:#f0f0f0;font-weight:bold;">
        <th>Name</th>
        <th>Grade Level</th>
        <th>Section</th>
        <th>Date</th>
        <th>Time In</th>
        <th>Time Out</th>
    </tr>
EOD;

$pdf->SetFont('helvetica', '', 11);
foreach ($records as $rec) {
    $html .= '<tr>';
    $html .= '<td>' . htmlspecialchars($rec['first_name'] . ' ' . $rec['last_name']) . '</td>';
    $html .= '<td>' . htmlspecialchars($rec['grade_level']) . '</td>';
    $html .= '<td>' . htmlspecialchars($rec['section']) . '</td>';
    $html .= '<td>' . date('M d, Y', strtotime($rec['attendance_date'])) . '</td>';
    $html .= '<td>' . ($rec['time_in'] ? date('h:i A', strtotime($rec['time_in'])) : '-') . '</td>';
    $html .= '<td>' . ($rec['time_out'] ? date('h:i A', strtotime($rec['time_out'])) : '-') . '</td>';
    $html .= '</tr>';
}

$html .= '</table>';

$pdf->writeHTML($html, true, false, true, false, '');

$pdf->Output('attendance_records_' . date('Ymd_His') . '.pdf', 'D');
exit;
