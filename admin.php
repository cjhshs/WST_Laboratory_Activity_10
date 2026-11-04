<?php
declare(strict_types=1);

session_start();
require_once __DIR__ . '/db.php';

// 1. Authentication: visitors without a session go to the login page.
if (!isset($_SESSION['user_id'])) {
    $conn->close();
    header('Location: login.php');
    exit;
}

// 2. Authorization: a valid session is not enough - the role must be admin.
//    This check runs on the server, so hiding or editing links in the browser
//    cannot grant access.
if (($_SESSION['role'] ?? '') !== 'admin') {
    http_response_code(403);
    $conn->close();
    ?>
    <!doctype html>
    <html lang="en">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>403 Forbidden | Student Information System</title>
        <link rel="stylesheet" href="style.css">
    </head>
    <body class="auth-page">
        <main class="card">
            <p class="status-code">403 Forbidden</p>
            <h1>Access denied</h1>
            <p>Access denied. Administrator permission is required.</p>
            <p class="switch"><a href="dashboard.php">Back to dashboard</a></p>
        </main>
    </body>
    </html>
    <?php
    exit;
}

// 3. Authorized: load the accounts for the administrator view.
$users = [];
$userResult = $conn->query('SELECT id, email, role, created_at FROM users ORDER BY id');
if ($userResult !== false) {
    while ($row = $userResult->fetch_assoc()) {
        $users[] = $row;
    }
    $userResult->free();
}

$commentCount = 0;
$countResult = $conn->query('SELECT COUNT(*) AS total FROM student_comments');
if ($countResult !== false) {
    $commentCount = (int) ($countResult->fetch_assoc()['total'] ?? 0);
    $countResult->free();
}

$conn->close();
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Administrator | Student Information System</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <header class="topbar">
        <span class="brand">Student Information System</span>
        <nav>
            <a href="dashboard.php">Dashboard</a>
            <a href="index.php">Comments</a>
            <a href="logout.php" class="logout">Log out</a>
        </nav>
    </header>

    <main class="page">
        <section class="card">
            <p class="status-code">Administrator area</p>
            <h1>Administrator Dashboard</h1>
            <p class="welcome">
                Signed in as <strong><?= e((string) ($_SESSION['email'] ?? '')) ?></strong>
                <span class="badge badge-admin">admin</span>
            </p>

            <div class="stats">
                <div class="stat">
                    <span class="stat-value"><?= count($users) ?></span>
                    <span class="stat-label">Registered users</span>
                </div>
                <div class="stat">
                    <span class="stat-value"><?= $commentCount ?></span>
                    <span class="stat-label">Student comments</span>
                </div>
            </div>

            <h2>Registered accounts</h2>
            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Email</th>
                            <th>Role</th>
                            <th>Created</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users as $user): ?>
                            <tr>
                                <td><?= (int) $user['id'] ?></td>
                                <td><?= e($user['email']) ?></td>
                                <td><?= e($user['role']) ?></td>
                                <td><?= e($user['created_at']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </section>
    </main>
</body>
</html>
