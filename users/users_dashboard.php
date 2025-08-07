<?php
session_start();
require '../config/db.php';

$users = $pdo->query("SELECT * FROM users")->fetchAll();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $u = $_POST['username'];
    $p = $_POST['password']; // No hashing, plain text password
    $pdo->prepare("INSERT INTO users (username, password) VALUES (?, ?)")->execute([$u, $p]);
    header('Location: manage.php');
    exit;
}
?>
<!DOCTYPE html>
<html>
<head>
    <link rel="stylesheet" href="../css/style.css">
    <title>Manage Users</title>
</head>
<body>

<header>
    <h1>Users</h1>
</header>

<nav>
  <a href="../dashboard.php">Home</a>  
  <a href="../students/student_dashboard.php">Students</a>
  <a href="../index.php">Attendance</a>
  <a href="users_dashboard.php">Users</a>
  <a href="./logout.php" class="logout-button">Logout</a>
</nav>

<table>
    <tr><th>Username</th></tr>
    <?php foreach ($users as $u): ?>
        <tr><td><?= htmlspecialchars($u['username']) ?></td></tr>
    <?php endforeach; ?>
</table>
<h3>Add User</h3>
<form method="post">
    <input name="username" placeholder="Username" required>
    <input name="password" type="password" placeholder="Password" required>
    <button type="submit">Add</button>
</form>
</body>
</html>
