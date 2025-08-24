<?php
$current_page = basename($_SERVER['PHP_SELF']);
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: users/login.php');
    exit();
}

require 'config/db.php';

$total = $pdo->query('SELECT COUNT(*) FROM students')->fetchColumn();
$in = $pdo
    ->query(
        "SELECT COUNT(*) FROM attendance WHERE type = 'IN' AND DATE(time) = CURDATE()"
    )
    ->fetchColumn();
$out = $pdo
    ->query(
        "SELECT COUNT(*) FROM attendance WHERE type = 'OUT' AND DATE(time) = CURDATE()"
    )
    ->fetchColumn();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <title>Dashboard</title>
    <link rel="stylesheet" href="/attendance-sim/css/style.css?v=<?= time() ?>" />
    <style>
        /* Current Date and Time container */
        .current-datetime {
            text-align: center;
            margin: 40px 0 30px;
            color: #004080;
            font-weight: 700;
            font-size: 2.4rem;
            user-select: none;
            letter-spacing: 0.02em;
        }

        /* Stats section */
        .stats {
            display: flex;
            gap: 3rem;
            justify-content: center;
            background: #f9fafb;
            border-radius: 12px;
            box-shadow: 0 0 25px rgba(0, 64, 128, 0.08);
            padding: 40px 20px;
            max-width: 900px;
            margin: 0 auto 60px;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        .stats > div {
            background: white;
            flex: 1;
            border-radius: 12px;
            padding: 40px 0;
            text-align: center;
            box-shadow: 0 7px 18px rgba(0, 64, 128, 0.08);
            transition: box-shadow 0.3s ease;
            cursor: default;
            user-select: none;
        }

        .stats > div:hover {
            box-shadow: 0 10px 30px rgba(0, 64, 128, 0.12);
        }

        .stats > div label {
            display: block;
            font-size: 1.5rem;
            font-weight: 700;
            color: #27374D;
            margin-bottom: 15px;
            letter-spacing: 0.05em;
        }

        .stats > div span {
            display: block;
            font-size: 4rem;
            font-weight: 900;
            color: #004080;
            line-height: 1;
            letter-spacing: 0.05em;
        }

        /* Responsive */
        @media (max-width: 700px) {
            .stats {
                flex-direction: column;
                gap: 25px;
                max-width: 100%;
            }
            .stats > div {
                padding: 30px 15px;
            }
            .stats > div span {
                font-size: 3.5rem;
            }
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
<header>
    <h1>Dashboard</h1>
</header>

<?php include_once __DIR__ . '/nav.php'; ?>

<!-- Current Date and Time above stats -->
<div class="current-datetime" aria-live="polite" aria-atomic="true" role="timer">
    <span id="currentDate"></span> &nbsp;&nbsp;|&nbsp;&nbsp; <span id="currentTime"></span>
</div>

<div class="stats" role="region" aria-label="Attendance statistics">
    <div>
        <label for="totalStudents">Total Students</label>
        <span id="totalStudents"><?= $total ?></span>
    </div>
    <div>
        <label for="todayIn">Today In</label>
        <span id="todayIn"><?= $in ?></span>
    </div>
    <div>
        <label for="todayOut">Today Out</label>
        <span id="todayOut"><?= $out ?></span>
    </div>
</div>

<script>
    function updateDateTime() {
        const now = new Date();

        const optionsDate = { weekday: 'long', year: 'numeric', month: 'short', day: 'numeric' };
        const formattedDate = now.toLocaleDateString(undefined, optionsDate);

        const formattedTime = now.toLocaleTimeString(undefined, { hour12: true });

        document.getElementById('currentDate').textContent = formattedDate;
        document.getElementById('currentTime').textContent = formattedTime;
    }

    updateDateTime();
    setInterval(updateDateTime, 1000);
</script>
</body>
</html>
