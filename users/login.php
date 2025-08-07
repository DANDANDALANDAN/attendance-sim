<?php
session_start();
require '../config/db.php';

// If already logged in, redirect to default page or stored redirect page
if (isset($_SESSION['user_id'])) {
    $redirect = $_SESSION['redirect_after_login'] ?? '../dashboard.php';
    unset($_SESSION['redirect_after_login']);
    header("Location: $redirect");
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if ($username === '' || $password === '') {
        $error = 'Please enter username and password.';
    } else {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
        $stmt->execute([$username]);
        $user = $stmt->fetch();

        if ($user && $password === $user['password']) {
            // Valid credentials — set session
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role'] ?? 'admin';

            // Redirect to requested page or default dashboard
            $redirect = $_SESSION['redirect_after_login'] ?? '../dashboard.php';
            unset($_SESSION['redirect_after_login']);
            header("Location: $redirect");
            exit;
        } else {
            $error = 'Invalid username or password.';
        }
    }
} else {
    // Store the currently requested page to redirect after login
    if (empty($_SESSION['user_id'])) {
        // Save referer only if not coming directly from login page itself
        $referer = $_SERVER['HTTP_REFERER'] ?? '';
        if ($referer && !str_contains($referer, 'login.php')) {
            $_SESSION['redirect_after_login'] = $referer;
        }
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <title>Login - Student Attendance System</title>
    <link rel="stylesheet" href="../css/style.css" />
</head>
<body>
    <form method="post" class="login-form">
        <h2>Login</h2>
        <?php if ($error): ?>
            <p class="error"><?= htmlspecialchars($error) ?></p>
        <?php endif; ?>
        <input type="text" name="username" placeholder="Username" required autofocus />
        <input type="password" name="password" placeholder="Password" required />
        <button type="submit">Login</button>
    </form>
</body>
</html>
