<?php
$current_page = basename($_SERVER['PHP_SELF']);
$base_url = '/attendance-sim'; // Your root folder name

$menu_items = [
    [
        'label' => 'Home',
        'file' => 'dashboard.php',
        'href' => $base_url . '/dashboard.php',
    ],
    [
        'label' => 'Manage Students',
        'file' => 'student_dashboard.php',
        'href' => $base_url . '/students/student_dashboard.php',
    ],
    [
        'label' => 'Attendance Records',
        'file' => 'records.php',
        'href' => $base_url . '/students/records.php',
    ],
    [
        'label' => 'Scanners',
        'file' => 'index.php',
        'href' => $base_url . '/index.php',
    ],
    [
        'label' => 'Users',
        'file' => 'users_dashboard.php',
        'href' => $base_url . '/users/users_dashboard.php',
    ],
];

$logout_href = $base_url . '/users/logout.php';
?>

<nav>
<?php foreach ($menu_items as $item): ?>
    <a href="<?= htmlspecialchars($item['href']) ?>"
       class="<?= $current_page == $item['file'] ? 'active' : '' ?>">
       <?= htmlspecialchars($item['label']) ?>
    </a>
<?php endforeach; ?>
<a href="<?= htmlspecialchars($logout_href) ?>" class="logout-button">Logout</a>
</nav>
