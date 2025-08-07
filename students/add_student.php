<?php
session_start();

// Redirect to login if not logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: ../users/login.php');
    exit;
}

require '../config/db.php';

$edit = isset($_GET['id']);
$data = []; // initialize empty student data array

if ($edit) {
    // Fetch existing student data for editing
    $stmt = $pdo->prepare("SELECT * FROM students WHERE id = ?");
    $stmt->execute([$_GET['id']]);
    $data = $stmt->fetch();

    // If no student found with that ID, redirect to student dashboard
    if (!$data) {
        header('Location: student_dashboard.php');
        exit;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Get and sanitize POST inputs
    $f = trim($_POST['first_name'] ?? '');
    $l = trim($_POST['last_name'] ?? '');
    $g = (int)($_POST['grade_level'] ?? 0);
    $sec = trim($_POST['section'] ?? '');
    $ec = trim($_POST['emergency_contact'] ?? '');

    // Basic validation could be added here if desired

    if ($edit) {
        $stmt = $pdo->prepare("UPDATE students SET first_name = ?, last_name = ?, grade_level = ?, section = ?, emergency_contact = ? WHERE id = ?");
        $stmt->execute([$f, $l, $g, $sec, $ec, $_GET['id']]);
    } else {
        $stmt = $pdo->prepare("INSERT INTO students (first_name, last_name, grade_level, section, emergency_contact) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$f, $l, $g, $sec, $ec]);
    }

    header('Location: student_dashboard.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <title><?= $edit ? 'Edit' : 'Add' ?> Student</title>
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

<form method="post" autocomplete="off" novalidate>
    <h2><?= $edit ? 'Edit' : 'Add' ?> Student</h2>
    <input type="text" name="first_name" required placeholder="First Name" value="<?= htmlspecialchars($data['first_name'] ?? '') ?>" />
    <input type="text" name="last_name" required placeholder="Last Name" value="<?= htmlspecialchars($data['last_name'] ?? '') ?>" />
    <select name="grade_level" required>
        <option value="">Select Grade Level</option>
        <?php for ($i = 7; $i <= 12; $i++): ?>
            <option value="<?= $i ?>" <?= (isset($data['grade_level']) && (int)$data['grade_level'] === $i) ? 'selected' : '' ?>>Grade <?= $i ?></option>
        <?php endfor; ?>
    </select>
    <input type="text" name="section" required placeholder="Section" value="<?= htmlspecialchars($data['section'] ?? '') ?>" />
    <input type="text" name="emergency_contact" required placeholder="Emergency Contact" value="<?= htmlspecialchars($data['emergency_contact'] ?? '') ?>" />
    <button type="submit"><?= $edit ? 'Update' : 'Add' ?></button>
</form>

</body>
</html>
