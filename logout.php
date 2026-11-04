<?php
declare(strict_types=1);

// Use the same cookie settings as login.php so the deletion matches the
// cookie that was actually created.
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

// 1. Clear all session data in memory.
$_SESSION = [];

// 2. Expire the session cookie in the browser, reusing the current settings.
if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();

    setcookie(session_name(), '', [
        'expires'  => time() - 42000,
        'path'     => $params['path'],
        'domain'   => $params['domain'],
        'secure'   => $params['secure'],
        'httponly' => $params['httponly'],
        'samesite' => $params['samesite'] ?? 'Lax',
    ]);
}

// 3. Destroy the session file on the server.
session_destroy();

header('Location: login.php');
exit;
