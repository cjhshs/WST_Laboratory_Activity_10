<?php
declare(strict_types=1);

// Harden the session cookie before the session starts:
// - HttpOnly stops JavaScript from reading the cookie.
// - SameSite=Lax reduces cross-site request forgery risk.
// - Secure is enabled automatically when the site is served over HTTPS.
$isHttps = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';

session_set_cookie_params([
    'lifetime' => 0,
    'path'     => '/',
    'domain'   => '',
    'secure'   => $isHttps,
    'httponly' => true,
    'samesite' => 'Lax',
]);
session_start();

require_once __DIR__ . '/db.php';

// Already signed in? Send the visitor straight to the dashboard.
if (isset($_SESSION['user_id'])) {
    header('Location: dashboard.php');
    exit;
}

$error = '';
$email = '';

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    $email = trim((string) ($_POST['email'] ?? ''));
    $password = (string) ($_POST['password'] ?? '');

    // 1. Look the account up with a prepared statement.
    $stmt = $conn->prepare('SELECT id, email, password_hash, role FROM users WHERE email = ? LIMIT 1');

    if ($stmt === false) {
        error_log('Login prepare failed: ' . $conn->error);
        http_response_code(500);
        exit('A server error occurred. Please try again later.');
    }

    $stmt->bind_param('s', $email);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    $stmt->close();

    // 2. Verify the submitted password against the stored hash.
    if ($user !== null && password_verify($password, $user['password_hash'])) {
        // 3. New session ID after login prevents session fixation.
        session_regenerate_id(true);

        $_SESSION['user_id'] = (int) $user['id'];
        $_SESSION['email'] = $user['email'];
        $_SESSION['role'] = $user['role'];

        $conn->close();
        header('Location: dashboard.php');
        exit;
    }

    // 4. Same generic message for unknown email and wrong password, so the
    //    page does not reveal which accounts exist.
    $error = 'Invalid email or password.';
}

$conn->close();
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Log in | Student Information System</title>
    <link rel="stylesheet" href="style.css">
</head>
<body class="auth-page">
    <main class="card">
        <h1>Welcome back</h1>
        <p class="subtitle">Student Information Management System</p>

        <?php if ($error !== ''): ?>
            <div class="alert alert-error"><?= e($error) ?></div>
        <?php endif; ?>

        <form method="post" action="login.php">
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
                autocomplete="current-password"
                required
            >

            <button type="submit" class="btn">Log in</button>
        </form>

        <p class="switch">No account yet? <a href="register.php">Register</a></p>
    </main>
</body>
</html>
