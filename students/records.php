<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: ../users/login.php');
    exit();
}

require '../config/db.php';

// Fetch all students
$students = $pdo
    ->query(
        'SELECT id, first_name, last_name FROM students ORDER BY last_name, first_name'
    )
    ->fetchAll();

// Fetch distinct sections
$sections = $pdo
    ->query(
        'SELECT DISTINCT section FROM students WHERE section IS NOT NULL AND section <> "" ORDER BY section'
    )
    ->fetchAll(PDO::FETCH_COLUMN);

// Process filters
$student_id = $_GET['student_id'] ?? '';
$section = $_GET['section'] ?? '';
$date_from = $_GET['date_from'] ?? '';
$date_to = $_GET['date_to'] ?? '';

$whereClauses = [];
$params = [];

// Date range filter
if ($date_from !== '' && $date_to !== '') {
    $whereClauses[] = 'a.time BETWEEN ? AND ?';
    $params[] = $date_from . ' 00:00:00';
    $params[] = $date_to . ' 23:59:59';
}

// Student filter
if ($student_id !== '' && is_numeric($student_id)) {
    $whereClauses[] = 'a.student_id = ?';
    $params[] = $student_id;
}

// Section filter
if ($section !== '') {
    $whereClauses[] = 's.section = ?';
    $params[] = $section;
}

$whereSQL = $whereClauses ? 'WHERE ' . implode(' AND ', $whereClauses) : '';

// Fetch attendance records
$sql = "
SELECT
    s.id           AS student_id,
    s.first_name,
    s.last_name,
    s.grade_level,
    s.section,
    a.time,
    a.type
FROM attendance a
JOIN students s ON a.student_id = s.id
{$whereSQL}
ORDER BY s.last_name, s.first_name, a.time ASC
";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$attendanceRecords = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Pair IN and OUT
$pairedRecords = [];
$currentStudent = null;
$pendingInRecord = null;

function formatDate($dt)
{
    return date('M d, Y', strtotime($dt));
}
function formatTime($dt)
{
    return date('h:i A', strtotime($dt));
}

foreach ($attendanceRecords as $rec) {
    if ($currentStudent !== $rec['student_id']) {
        $currentStudent = $rec['student_id'];
        $pendingInRecord = null;
    }
    if ($rec['type'] === 'IN') {
        if ($pendingInRecord) {
            $pairedRecords[] = [
                'info' => $pendingInRecord,
                'time_out' => null,
            ];
        }
        $pendingInRecord = $rec;
    } else {
        if ($pendingInRecord) {
            $pairedRecords[] = [
                'info' => $pendingInRecord,
                'time_out' => $rec['time'],
            ];
            $pendingInRecord = null;
        } else {
            $pairedRecords[] = [
                'info' => $rec,
                'time_in' => null,
                'time_out' => $rec['time'],
            ];
        }
    }
}
if ($pendingInRecord) {
    $pairedRecords[] = [
        'info' => $pendingInRecord,
        'time_out' => null,
    ];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <title>Attendance Records</title>
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0/dist/css/select2.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="/attendance-sim/css/style.css?v=<?= time() ?>" />
    <style>
        /* Container: filter section + export buttons aligned */
        .filter-export-container {
            display: flex;
            align-items: flex-end;
            justify-content: space-between;
            margin-bottom: 20px;
            flex-wrap: wrap;
            gap: 10px;
        }

        /* Filters container */
        form.filters {
            display: flex;
            flex-wrap: wrap;
            gap: 12px 16px;
            align-items: flex-end;
            max-width: calc(100% - 310px); /* leave space for export buttons */
            min-width: 250px;
        }

        form.filters label {
            font-weight: bold;
            margin-bottom: 4px;
            display: block;
            font-size: 14px;
        }

        form.filters select,
        form.filters input[type="date"] {
            height: 30px;
            font-size: 14px;
            padding: 2px 6px;
            min-width: 150px;
            max-width: 180px;
            border: 1px solid #ccc;
            border-radius: 4px;
            box-sizing: border-box;
        }

        form.filters button.search-btn {
            background-color: #004080;
            color: white;
            font-weight: bold;
            padding: 6px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            height: 34px;
            white-space: nowrap;
            transition: background-color 0.3s ease;
            min-width: 80px;
        }

        form.filters button.search-btn:hover {
            background-color: #003060;
        }

        /* Export buttons container aligned vertically with filters */
        .export-buttons {
            display: flex;
            flex-direction: column;
            justify-content: flex-end;
            gap: 8px;
            flex-shrink: 0;
            width: 300px;
        }

        .export-buttons form {
            margin: 0;
        }

        .export-buttons button.button {
            width: 100%;
            height: 34px;
            font-size: 14px;
            font-weight: bold;
            background-color: #007b00;
            border: none;
            border-radius: 4px;
            color: white;
            cursor: pointer;
            white-space: nowrap;
            transition: background-color 0.3s ease;
        }

        .export-buttons button.button:hover {
            background-color: #005a00;
        }

        table.records {
            width: 100%;
            border-collapse: collapse;
            font-size: 14px;
        }

        table.records th, table.records td {
            border: 1px solid #ccc;
            padding: 6px 10px;
            text-align: left;
        }

        table.records th {
            background-color: #f0f0f0;
        }
        nav a.active {
            background-color: #ffffffff; /* light blue */
            color: #003366;            /* dark blue */
            font-weight: bold;
            text-decoration: none;     /* no underline */
            padding: 8px 12px;         /* optionally add some padding */
            border-radius: 4px;        /* optional rounded corners */
        }
    </style>
</head>
<body>

<header><h1>Attendance Records</h1></header>

<?php include_once __DIR__ . '/../nav.php'; ?>

<div class="filter-export-container">
    <!-- Filters -->
    <form method="get" class="filters" action="records.php" autocomplete="off">
        <div>
            <label for="student_id">Student</label>
            <select name="student_id" id="student_id" style="min-width: 200px;">
                <option value="">-- All Students --</option>
                <?php foreach ($students as $st): ?>
                    <option value="<?= $st['id'] ?>" <?= $student_id ==
$st['id']
    ? 'selected'
    : '' ?>>
                        <?= htmlspecialchars(
                            $st['first_name'] . ' ' . $st['last_name']
                        ) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div>
            <label for="section">Section</label>
            <select name="section" id="section" style="min-width: 100px;">
                <option value="">-- All Sections --</option>
                <?php foreach ($sections as $sec): ?>
                    <option value="<?= htmlspecialchars(
                        $sec
                    ) ?>" <?= $section == $sec ? 'selected' : '' ?>>
                        <?= htmlspecialchars($sec) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div>
            <label for="date_from">From</label>
            <input type="date" name="date_from" id="date_from" value="<?= htmlspecialchars(
                $date_from
            ) ?>" />
        </div>

        <div>
            <label for="date_to">To</label>
            <input type="date" name="date_to" id="date_to" value="<?= htmlspecialchars(
                $date_to
            ) ?>" />
        </div>

        <div>
            <button type="submit" class="search-btn">Search</button>
        </div>
    </form>

    <!-- Export buttons -->
    <div class="export-buttons">
        <form method="get" action="export_csv.php" class="export-form">
            <?php foreach ($_GET as $k => $v): ?>
                <input type="hidden" name="<?= htmlspecialchars(
                    $k
                ) ?>" value="<?= htmlspecialchars($v) ?>" />
            <?php endforeach; ?>
            <button type="submit" class="button">Export CSV</button>
        </form>

        <form method="get" action="export_pdf.php" class="export-form">
            <?php foreach ($_GET as $k => $v): ?>
                <input type="hidden" name="<?= htmlspecialchars(
                    $k
                ) ?>" value="<?= htmlspecialchars($v) ?>" />
            <?php endforeach; ?>
            <button type="submit" class="button">Export PDF</button>
        </form>
    </div>
</div>

<table class="records">
    <thead>
        <tr>
            <th>Name</th>
            <th>Grade</th>
            <th>Section</th>
            <th>Date</th>
            <th>In</th>
            <th>Out</th>
        </tr>
    </thead>
    <tbody>
        <?php if ($pairedRecords): ?>
            <?php foreach ($pairedRecords as $pr):
                $r = $pr['info']; ?>
            <tr>
                <td><?= htmlspecialchars(
                    $r['first_name'] . ' ' . $r['last_name']
                ) ?></td>
                <td><?= htmlspecialchars($r['grade_level']) ?></td>
                <td><?= htmlspecialchars($r['section']) ?></td>
                <td><?= formatDate($r['time']) ?></td>
                <td><?= isset($pr['time_in']) && $pr['time_in'] === null
                    ? '-'
                    : formatTime($r['time']) ?></td>
                <td><?= $pr['time_out']
                    ? formatTime($pr['time_out'])
                    : '-' ?></td>
            </tr>
            <?php
            endforeach; ?>
        <?php else: ?>
            <tr><td colspan="6">No records found.</td></tr>
        <?php endif; ?>
    </tbody>
</table>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0/dist/js/select2.min.js"></script>
<script>
    $(document).ready(function() {
        $('#student_id').select2({
            placeholder: '-- All Students --',
            allowClear: true,
            width: '220px'
        });
    });
</script>

</body>
</html>
