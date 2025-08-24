<?php
session_start();
require '../config/db.php';

$error = '';
$success = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $u = trim($_POST['username'] ?? '');
    $p = trim($_POST['password'] ?? '');

    if ($u === '' || $p === '') {
        $error = 'Please fill in both username and password.';
    } else {
        try {
            // Insert new user (consider hashing password in production)
            $stmt = $pdo->prepare(
                'INSERT INTO users (username, password) VALUES (?, ?)'
            );
            $stmt->execute([$u, $p]);

            // Redirect to avoid form resubmission and show success
            header('Location: users_dashboard.php?success=1');
            exit();
        } catch (PDOException $e) {
            // Check for duplicate entry error code (SQLSTATE 23000)
            if ($e->getCode() == 23000) {
                $error =
                    "Username '" .
                    htmlspecialchars($u) .
                    "' already exists. Please choose a different username.";
            } else {
                $error = 'Database error: ' . $e->getMessage();
            }
        }
    }
}

// Fetch current users for display
$users = $pdo->query('SELECT * FROM users')->fetchAll();

// Check for success message after redirect
if (isset($_GET['success']) && $_GET['success'] == '1') {
    $success = 'User added successfully.';
}
?>
<!DOCTYPE html>
<html>
<head>
    <link rel="stylesheet" href="/attendance-sim/css/style.css?v=<?= time() ?>" />
    <title>Manage Users</title>
    <style>
        .error {
            color: red;
            font-weight: bold;
            margin-bottom: 15px;
        }
        .success {
            color: green;
            font-weight: bold;
            margin-bottom: 15px;
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
    <h1>Users</h1>
</header>

<?php include_once __DIR__ . '/../nav.php'; ?>

<?php if ($error): ?>
    <p class="error"><?= $error ?></p>
<?php endif; ?>

<?php if ($success): ?>
    <p class="success"><?= $success ?></p>
<?php endif; ?>

<table>
    <thead>
        <tr><th>Username</th></tr>
    </thead>
    <tbody>
        <?php foreach ($users as $user): ?>
            <tr><td><?= htmlspecialchars($user['username']) ?></td></tr>
        <?php endforeach; ?>
    </tbody>
</table>

<h3>Add User</h3>
<form method="post" novalidate>
    <input name="username" placeholder="Username" required />
    <input name="password" type="password" placeholder="Password" required />
    <button type="submit">Add</button>
</form>

</body>
</html>
