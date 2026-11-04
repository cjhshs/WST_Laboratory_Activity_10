<?php
declare(strict_types=1);

require_once __DIR__ . '/db.php';

$message = '';
$messageType = '';
$email = '';

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    // 1. Trim the email and read the raw password.
    $email = trim((string) ($_POST['email'] ?? ''));
    $password = (string) ($_POST['password'] ?? '');

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = 'Please enter a valid email address.';
        $messageType = 'error';
    } elseif (strlen($password) < 12) {
        $message = 'Password must be at least 12 characters long.';
        $messageType = 'error';
    } else {
        // 2. Never store the plain password - store a one-way hash instead.
        //    PASSWORD_DEFAULT currently produces a bcrypt hash and may change
        //    to a stronger algorithm in future PHP versions.
        $hash = password_hash($password, PASSWORD_DEFAULT);

        // 3. Parameterized INSERT: the values are data, never SQL code.
        $stmt = $conn->prepare('INSERT INTO users (email, password_hash) VALUES (?, ?)');

        if ($stmt === false) {
            error_log('Register prepare failed: ' . $conn->error);
            http_response_code(500);
            exit('A server error occurred. Please try again later.');
        }

        $stmt->bind_param('ss', $email, $hash);

        if ($stmt->execute()) {
            $message = 'Account created successfully. You can now log in.';
            $messageType = 'success';
            $email = '';
        } elseif ($stmt->errno === 1062) {
            // MySQL error 1062 = duplicate entry on the unique email index.
            $message = 'That email address is already registered. Try logging in instead.';
            $messageType = 'error';
        } else {
            error_log('Register insert failed: ' . $stmt->error);
            $message = 'Registration failed. Please try again later.';
            $messageType = 'error';
        }

        $stmt->close();
    }
}

$conn->close();
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Register | Student Information System</title>
    <link rel="stylesheet" href="style.css">
</head>
<body class="auth-page">
    <main class="card">
        <h1>Create an account</h1>
        <p class="subtitle">Student Information Management System</p>

        <?php if ($message !== ''): ?>
            <div class="alert <?= $messageType === 'success' ? 'alert-success' : 'alert-error' ?>">
                <?= e($message) ?>
            </div>
        <?php endif; ?>

        <form method="post" action="register.php">
            <label for="email">Email address</label>
            <input
                type="email"
                id="email"
                name="email"
                value="<?= e($email) ?>"
                maxlength="150"
                autocomplete="email"
                required
            >

            <label for="password">Password</label>
            <input
                type="password"
                id="password"
                name="password"
                minlength="12"
                autocomplete="new-password"
                required
            >
            <p class="hint">Use at least 12 characters.</p>

            <button type="submit" class="btn">Register</button>
        </form>

        <p class="switch">Already registered? <a href="login.php">Log in</a></p>
    </main>
</body>
</html>
