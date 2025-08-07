<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: users/login.php');
    exit;
}

require 'config/db.php';

$total = $pdo->query("SELECT COUNT(*) FROM students")->fetchColumn();
$in = $pdo->query("SELECT COUNT(*) FROM attendance WHERE type = 'IN' AND DATE(time) = CURDATE()")->fetchColumn();
$out = $pdo->query("SELECT COUNT(*) FROM attendance WHERE type = 'OUT' AND DATE(time) = CURDATE()")->fetchColumn();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <title>Dashboard</title>
    <link rel="stylesheet" href="css/style.css" />
</head>
<body>
<header>
    <h1>Dashboard</h1>
</header>

<nav>
  <a href="./dashboard.php">Home</a>  
  <a href="./students/student_dashboard.php">Students</a>
  <a href="./index.php">Attendance</a>
  <a href="./users/users_dashboard.php">Users</a>
  <a href="./users/logout.php" class="logout-button">Logout</a>
</nav>

<div class="stats">
    <div>Total Students<br><span><?= $total ?></span></div>
    <div>Today In<br><span><?= $in ?></span></div>
    <div>Today Out<br><span><?= $out ?></span></div>
</div>

</body>
</html>
