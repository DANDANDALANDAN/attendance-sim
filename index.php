<?php
session_start();

// Redirect to login if not logged in
if (!isset($_SESSION['user_id'])) {
    // Save the current URL as redirect target after login
    $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
    header('Location: users/login.php');
    exit();
}

require 'config/db.php';

// Fetch all students for select dropdowns
$students = $pdo
    ->query('SELECT * FROM students ORDER BY last_name, first_name')
    ->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <title>Attendance</title>
    <link rel="stylesheet" href="/attendance-sim/css/style.css?v=<?= time() ?>" />
    <style>
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

<header>
    <h1>Scanners</h1>
</header>

<?php include_once __DIR__ . '/nav.php'; ?>

<h2>Simulated Scanners</h2>
<div class="scanners">
    <?php for ($i = 1; $i <= 4; $i++): ?>
        <div class="scanner">
            <h3>Scanner <?= $i ?></h3>
            <select onchange="processScan(this.value)">
                <option value="">-- Select Student --</option>
                <?php foreach ($students as $student): ?>
                    <option value="<?= htmlspecialchars($student['id']) ?>">
                        <?= htmlspecialchars(
                            $student['first_name'] . ' ' . $student['last_name']
                        ) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
    <?php endfor; ?>
</div>

<div id="display"></div>

<script src="js/attendance.js"></script>

</body>
</html>
