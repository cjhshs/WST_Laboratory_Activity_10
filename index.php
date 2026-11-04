<?php
declare(strict_types=1);

session_start();
require_once __DIR__ . '/db.php';

// Comments are only visible to signed-in users.
if (!isset($_SESSION['user_id'])) {
    $conn->close();
    header('Location: login.php');
    exit;
}

$userId = (int) $_SESSION['user_id'];
$email = (string) ($_SESSION['email'] ?? '');
$error = '';
$posted = isset($_GET['posted']);

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    $comment = trim((string) ($_POST['comment'] ?? ''));

    if ($comment === '') {
        $error = 'Comment cannot be empty.';
    } else {
        // Prepared statement: user input is bound as data, not concatenated
        // into the SQL string.
        $stmt = $conn->prepare('INSERT INTO student_comments (user_id, comment) VALUES (?, ?)');

        if ($stmt === false) {
            error_log('Comment prepare failed: ' . $conn->error);
            http_response_code(500);
            exit('A server error occurred. Please try again later.');
        }

        $stmt->bind_param('is', $userId, $comment);

        if ($stmt->execute()) {
            $stmt->close();
            $conn->close();
            // Redirect after POST so refreshing the page does not repost.
            header('Location: index.php?posted=1');
            exit;
        }

        error_log('Comment insert failed: ' . $stmt->error);
        $error = 'Could not save your comment. Please try again.';
        $stmt->close();
    }
}

// Newest comments first, joined with the author's email.
$comments = [];
$result = $conn->query(
    'SELECT c.id, c.comment, c.created_at, u.email
       FROM student_comments c
       INNER JOIN users u ON u.id = c.user_id
      ORDER BY c.created_at DESC, c.id DESC'
);

if ($result !== false) {
    while ($row = $result->fetch_assoc()) {
        $comments[] = $row;
    }
    // Release the result set before closing the connection.
    $result->free();
}

$conn->close();
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Student Comments | Student Information System</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <header class="topbar">
        <span class="brand">Student Information System</span>
        <nav>
            <a href="dashboard.php">Dashboard</a>
            <a href="admin.php">Admin</a>
            <a href="logout.php" class="logout">Log out</a>
        </nav>
    </header>

    <main class="page">
        <section class="card">
            <h1>Student Comments</h1>
            <p class="subtitle">Signed in as <?= e($email) ?></p>

            <?php if ($posted): ?>
                <div class="alert alert-success">Your comment was posted.</div>
            <?php endif; ?>

            <?php if ($error !== ''): ?>
                <div class="alert alert-error"><?= e($error) ?></div>
            <?php endif; ?>

            <form method="post" action="index.php">
                <label for="comment">Add a comment</label>
                <textarea id="comment" name="comment" rows="4" maxlength="5000" required></textarea>
                <button type="submit" class="btn">Post comment</button>
            </form>
        </section>

        <section class="card">
            <h2>All comments <span class="count"><?= count($comments) ?></span></h2>

            <?php if ($comments === []): ?>
                <p class="empty">No comments yet. Be the first to post one.</p>
            <?php else: ?>
                <ul class="comment-list">
                    <?php foreach ($comments as $row): ?>
                        <li class="comment">
                            <div class="comment-meta">
                                <strong><?= e($row['email']) ?></strong>
                                <time><?= e(date('M j, Y g:i A', strtotime($row['created_at']))) ?></time>
                            </div>
                            <!-- Output encoding neutralizes any HTML/JS in the comment. -->
                            <p class="comment-body"><?= nl2br(e($row['comment'])) ?></p>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </section>
    </main>
</body>
</html>
