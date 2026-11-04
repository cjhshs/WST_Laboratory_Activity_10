<?php
declare(strict_types=1);

/**
 * Shared MySQL connection (mysqli).
 *
 * Local XAMPP defaults: host "localhost", user "root", empty password.
 * Never use an empty password for a public deployment - create a dedicated
 * database user with a strong password instead.
 */

require_once __DIR__ . '/helpers.php';

// Turn off mysqli exceptions so connection/query failures can be handled
// explicitly (log the details, show the visitor a generic message).
mysqli_report(MYSQLI_REPORT_OFF);

$dbHost = 'localhost';
$dbUser = 'root';
$dbPass = '';
$dbName = 'wst_week13';

$conn = @new mysqli($dbHost, $dbUser, $dbPass, $dbName);

if ($conn->connect_errno) {
    // Log the real reason for the administrator, but never show it publicly.
    error_log('Database connection failed: ' . $conn->connect_error);
    http_response_code(500);
    exit('A server error occurred. Please try again later.');
}

// utf8mb4 supports the full Unicode range (including emoji and CJK text).
$conn->set_charset('utf8mb4');
