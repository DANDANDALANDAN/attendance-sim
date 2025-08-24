<?php
session_start();

// Redirect to login if not logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: ../users/login.php');
    exit();
}

require '../config/db.php';

// Fetch all students ordered by last and first names
$students = $pdo
    ->query('SELECT * FROM students ORDER BY last_name, first_name')
    ->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <title>Manage Students</title>
    <link rel="stylesheet" href="/attendance-sim/css/style.css?v=<?= time() ?>" />
    <style>
        /* Keep button style as originally simple */
        .button {
            background-color: #004080;
            color: white;
            padding: 10px 20px;
            text-decoration: none;
            border-radius: 6px;
            display: inline-block;
            cursor: pointer;
            border: none;
            margin-left: 20px;
        }
        .button:hover {
            background-color: #003060;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        table th, table td {
            padding: 10px;
            border: 1px solid #ccc;
            text-align: left;
        }
        table th {
            background-color: #f0f0f0;
        }
        a.action-link {
            margin-right: 12px;
            color: #004080;
            text-decoration: none;
            font-weight: bold;
            cursor: pointer;
        }
        a.action-link:hover {
            text-decoration: underline;
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
    <h1>Manage Students</h1>
</header>

<?php include_once __DIR__ . '/../nav.php'; ?>

<main>
    <p>
        <a href="add_student.php" class="button">Add Student</a>
        <a href="bulk_upload_students.php" class="button">Bulk Upload/Update Students</a>
    </p>

    <table role="grid" aria-label="Students List">
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
        <?php if (empty($students)): ?>
            <tr><td colspan="5" style="text-align:center;">No students found.</td></tr>
        <?php else: ?>
            <?php foreach ($students as $student): ?>
                <tr>
                    <td><?= htmlspecialchars(
                        $student['first_name'] . ' ' . $student['last_name']
                    ) ?></td>
                    <td><?= htmlspecialchars($student['grade_level']) ?></td>
                    <td><?= htmlspecialchars($student['section']) ?></td>
                    <td><?= htmlspecialchars($student['parent_name']) ?></td>
                    <td>
                        <a href="add_student.php?id=<?= urlencode(
                            $student['id']
                        ) ?>" class="action-link" aria-label="Edit student <?= htmlspecialchars(
    $student['first_name']
) ?>">Edit</a>
                        <a href="#" class="action-link" onclick="archiveStudent(<?= htmlspecialchars(
                            $student['id']
                        ) ?>); return false;" aria-label="Archive student <?= htmlspecialchars(
    $student['first_name']
) ?>">Archive</a>
                    </td>
                </tr>
            <?php endforeach; ?>
        <?php endif; ?>
        </tbody>
    </table>
</main>

<script>
function archiveStudent(id) {
    if (confirm('Are you sure you want to archive this student?')) {
        // Implement archiving logic here (e.g., AJAX request)
        alert('Archive feature not implemented yet for student ID ' + id);
    }
}
</script>

</body>
</html>
