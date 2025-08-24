<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: ../users/login.php');
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<title>Manage Students</title>
<link rel="stylesheet" href="/attendance-sim/css/style.css?v=<?= time() ?>" />
<style>
    body {
        font-family: Arial, sans-serif;
        background: #f9f9f9;
        margin: 0;
        padding: 0;
    }
    nav a.active {
        font-weight: bold;
    }
    header, nav {
        padding: 1em;
        background: #004080;
        color: white;
    }
    nav a {
        color: white;
        margin-right: 1em;
        text-decoration: none;
    }
    main {
        max-width: 900px;
        margin: 30px auto;
        padding: 0 20px;
    }
    .button-container {
        display: flex;
        gap: 30px;
        justify-content: center;
        margin-bottom: 40px;
        flex-wrap: wrap;
    }
    .btn {
        background-color: #004080;
        color: white;
        font-size: 1.2em;
        padding: 24px 40px;
        border: none;
        border-radius: 8px;
        cursor: pointer;
        min-width: 220px;
        text-align: center;
        transition: background-color 0.3s ease;
        user-select: none;
    }
    .btn:hover, .btn:focus {
        background-color: #003060;
        outline: none;
    }
    #bulk-upload-section {
        max-width: 800px;
        margin: 0 auto;
        background: white;
        border-radius: 8px;
        box-shadow: 0 0 12px rgba(0,0,0,0.05);
        padding: 20px;
        display: none;
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
<script>
    document.addEventListener('DOMContentLoaded', () => {
        const addBtn = document.getElementById('add-student-btn');
        const bulkBtn = document.getElementById('bulk-upload-btn');
        const bulkSection = document.getElementById('bulk-upload-section');

        addBtn.addEventListener('click', () => {
            window.location.href = 'add_student.php'; // redirect to add_student.php
        });

        bulkBtn.addEventListener('click', () => {
            // Show bulk upload section and load bulk upload form via AJAX
            bulkSection.style.display = 'block';
            bulkSection.innerHTML = '<p>Loading...</p>';
            fetch('bulk_upload_students.php')
                .then(res => res.text())
                .then(html => {
                    bulkSection.innerHTML = html;
                })
                .catch(err => {
                    bulkSection.innerHTML = '<p style="color:red;">Failed to load bulk upload form.</p>';
                });
        });
    });
</script>
</head>
<body>

<header>
    <h1>Manage Students</h1>
</header>

<?php include_once __DIR__ . '/../nav.php'; ?>

<main>
    <div class="button-container">
        <button id="add-student-btn" class="btn" type="button" aria-label="Add Student">Add Student</button>
        <button id="bulk-upload-btn" class="btn" type="button" aria-label="Bulk Upload or Update Students">Bulk Upload/Update Students</button>
    </div>

    <section id="bulk-upload-section" aria-live="polite" aria-atomic="true"></section>
</main>

</body>
</html>
