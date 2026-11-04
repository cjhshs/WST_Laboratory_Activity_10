<?php
declare(strict_types=1);

session_start();

require_once __DIR__ . '/helpers.php';

// Session protection: no logged-in user, no dashboard.
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$email = (string) ($_SESSION['email'] ?? '');
$role = (string) ($_SESSION['role'] ?? 'student');
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Dashboard | Student Information System</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <header class="topbar">
        <span class="brand">Student Information System</span>
        <nav>
            <a href="index.php">Comments</a>
            <a href="admin.php">Admin</a>
            <a href="logout.php" class="logout">Log out</a>
        </nav>
    </header>

    <main class="page">
        <section class="card">
            <h1>Dashboard</h1>
            <p class="welcome">
                Welcome, <strong><?= e($email) ?></strong>!
                <span class="badge <?= $role === 'admin' ? 'badge-admin' : 'badge-student' ?>">
                    <?= e($role) ?>
                </span>
            </p>
            <p class="subtitle">You are signed in with a protected PHP session.</p>

            <ul class="link-list">
                <li>
                    <a href="index.php">
                        <strong>Student Comments</strong>
                        <span>Read and post comments in the shared board.</span>
                    </a>
                </li>
                <li>
                    <a href="admin.php">
                        <strong>Administrator Dashboard</strong>
                        <span>Administrator permission is required.</span>
                    </a>
                </li>
                <li>
                    <a href="logout.php">
                        <strong>Log Out</strong>
                        <span>End this session on the server.</span>
                    </a>
                </li>
            </ul>
        </section>
    </main>
</body>
</html>
