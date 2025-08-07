<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: ../users/login.php');
    exit;
}

require '../config/db.php';

// Fetch students for dropdown
$students = $pdo->query("SELECT id, first_name, last_name, grade_level, section FROM students ORDER BY last_name, first_name")->fetchAll();

// Initialize filter variables with defaults or from GET parameters
$student_id = $_GET['student_id'] ?? '';
$section = trim($_GET['section'] ?? '');
$grade_level = $_GET['grade_level'] ?? '';
$date_filter = $_GET['date_filter'] ?? 'daily'; // 'daily', 'weekly', 'monthly'
$date_value = $_GET['date_value'] ?? date('Y-m-d'); // Default today

// Build where clauses for filters
$whereClauses = [];
$params = [];

if ($student_id && is_numeric($student_id)) {
    $whereClauses[] = "a.student_id = ?";
    $params[] = $student_id;
}

if ($section !== '') {
    $whereClauses[] = "s.section LIKE ?";
    $params[] = "%{$section}%";
}

if ($grade_level !== '') {
    $whereClauses[] = "s.grade_level = ?";
    $params[] = $grade_level;
}

// Date filtering
$whereDate = '';
$dateStart = '';
$dateEnd = '';
try {
    $date = new DateTime($date_value);
} catch (Exception $e) {
    $date = new DateTime();
    $date_value = $date->format('Y-m-d');
}

switch ($date_filter) {
    case 'weekly':
        // Get the week start (Monday) and week end (Sunday) of given date
        $weekStart = clone $date;
        $weekStart->modify('monday this week');
        $weekEnd = clone $weekStart;
        $weekEnd->modify('sunday this week 23:59:59');

        $dateStart = $weekStart->format('Y-m-d 00:00:00');
        $dateEnd = $weekEnd->format('Y-m-d 23:59:59');
        break;
    case 'monthly':
        // Start and end of the month
        $monthStart = $date->format('Y-m-01 00:00:00');
        $monthEnd = $date->format('Y-m-t 23:59:59');

        $dateStart = $monthStart;
        $dateEnd = $monthEnd;
        break;
    case 'daily':
    default:
        $dateStart = $date->format('Y-m-d 00:00:00');
        $dateEnd = $date->format('Y-m-d 23:59:59');
}

// Add date range filter
$whereClauses[] = "a.time BETWEEN ? AND ?";
$params[] = $dateStart;
$params[] = $dateEnd;

$whereSQL = '';
if ($whereClauses) {
    $whereSQL = 'WHERE ' . implode(' AND ', $whereClauses);
}

// Fetch attendance records joined with student data
$sql = "
SELECT
    a.id,
    s.first_name,
    s.last_name,
    s.grade_level,
    s.section,
    a.type,
    a.time
FROM attendance a
JOIN students s ON a.student_id = s.id
{$whereSQL}
ORDER BY a.time DESC
LIMIT 100
";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$records = $stmt->fetchAll();

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <title>Attendance Records</title>
    <link rel="stylesheet" href="../css/style.css" />
    <style>
        form.filters {
            margin-bottom: 15px;
        }
        form.filters label {
            margin-right: 10px;
            font-weight: bold;
        }
        form.filters input, form.filters select {
            margin-right: 20px;
            padding: 4px 8px;
        }
        table.records {
            width: 100%;
            border-collapse: collapse;
        }
        table.records th, table.records td {
            border: 1px solid #ccc;
            padding: 6px 10px;
            text-align: left;
        }
        table.records th {
            background-color: #f0f0f0;
        }
    </style>
</head>
<body>

<header>
    <h1>Attendance Records</h1>
</header>

<nav>
    <a href="../dashboard.php">Home</a>
    <a href="student_dashboard.php">Students</a>
    <a href="records.php" class="active">Records</a>
    <a href="../index.php">Attendance</a>
    <a href="../users/users_dashboard.php">Users</a>
    <a href="../users/logout.php" class="logout-button">Logout</a>
</nav>

<form method="get" class="filters">
    <label for="student_id">Student</label>
    <select name="student_id" id="student_id">
        <option value="">-- All Students --</option>
        <?php foreach ($students as $st): 
            $selected = ($student_id == $st['id']) ? 'selected' : '';
            $fullName = htmlspecialchars($st['first_name'] . ' ' . $st['last_name']);
            ?>
            <option value="<?= $st['id'] ?>" <?= $selected ?>><?= $fullName ?> (Grade <?= $st['grade_level'] ?> - <?= htmlspecialchars($st['section']) ?>)</option>
        <?php endforeach; ?>
    </select>

    <label for="grade_level">Grade Level</label>
    <select name="grade_level" id="grade_level">
        <option value="">All</option>
        <?php for ($i = 7; $i <= 12; $i++): 
            $selected = ($grade_level == $i) ? 'selected' : '';
        ?>
            <option value="<?= $i ?>" <?= $selected ?>>Grade <?= $i ?></option>
        <?php endfor; ?>
    </select>

    <label for="section">Section</label>
    <input type="text" id="section" name="section" value="<?= htmlspecialchars($section) ?>" placeholder="Section (e.g. A, B, 1)" />

    <label for="date_filter">Filter by Date</label>
    <select name="date_filter" id="date_filter">
        <option value="daily" <?= ($date_filter == 'daily') ? 'selected' : '' ?>>Daily</option>
        <option value="weekly" <?= ($date_filter == 'weekly') ? 'selected' : '' ?>>Weekly</option>
        <option value="monthly" <?= ($date_filter == 'monthly') ? 'selected' : '' ?>>Monthly</option>
    </select>

    <input type="date" name="date_value" id="date_value" value="<?= htmlspecialchars($date_value) ?>" required />

    <button type="submit">Filter</button>
</form>

<table class="records">
    <thead>
        <tr>
            <th>Name</th>
            <th>Grade Level</th>
            <th>Section</th>
            <th>Type</th>
            <th>Time</th>
        </tr>
    </thead>
    <tbody>
        <?php if ($records): ?>
            <?php foreach ($records as $rec): ?>
                <tr>
                    <td><?= htmlspecialchars($rec['first_name'] . ' ' . $rec['last_name']) ?></td>
                    <td><?= htmlspecialchars($rec['grade_level']) ?></td>
                    <td><?= htmlspecialchars($rec['section']) ?></td>
                    <td><?= htmlspecialchars($rec['type']) ?></td>
                    <td><?= date('M d, Y h:i A', strtotime($rec['time'])) ?></td>
                </tr>
            <?php endforeach; ?>
        <?php else: ?>
            <tr><td colspan="5">No records found for the selected filters.</td></tr>
        <?php endif; ?>
    </tbody>
</table>

</body>
</html>
