<?php
session_start();

// Redirect to login if not logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: ../users/login.php');
    exit;
}

require '../config/db.php';

// Fetch all students ordered by last name and first name for better user experience
$students = $pdo->query("SELECT * FROM students ORDER BY last_name, first_name")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <title>Students</title>
    <link rel="stylesheet" href="../css/style.css" />
</head>
<body>

<header>
    <h1>Students</h1>
</header>

<nav>
    <a href="../dashboard.php">Home</a>
    <a href="student_dashboard.php" class="active">Students</a>
    <a href="../index.php">Attendance</a>
    <a href="../users/users_dashboard.php">Users</a>
    <a href="../users/logout.php" class="logout-button">Logout</a>
</nav>

<p>
    <a href="add_student.php" class="button">Add Student</a>
    <a href="records.php" class="button">Records</a>
</p>

<table>
    <thead>
        <tr>
            <th>Name</th>
            <th>Grade</th>
            <th>Section</th>
            <th>Emergency Contact</th>
            <th>Actions</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($students as $student): ?>
        <tr>
            <td><?= htmlspecialchars($student['first_name'] . ' ' . $student['last_name']) ?></td>
            <td><?= htmlspecialchars($student['grade_level']) ?></td>
            <td><?= htmlspecialchars($student['section']) ?></td>
            <td><?= htmlspecialchars($student['emergency_contact']) ?></td>
            <td>
                <a href="add_student.php?id=<?= urlencode($student['id']) ?>">Edit</a>
                &nbsp;|&nbsp;
                <a href="#" onclick="deleteStudent(<?= htmlspecialchars($student['id']) ?>); return false;">Delete</a>
            </td>
        </tr>
        <?php endforeach; ?>
    </tbody>
</table>

<script src="../js/main.js"></script>
</body>
</html>
