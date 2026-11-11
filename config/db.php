<?php
// config/db.php

// --- Hardened session configuration ---
// Must run BEFORE session_start(). Safe to call even if a session is already
// active elsewhere in the request chain (ini_set is skipped in that case by PHP).
if (session_status() === PHP_SESSION_NONE) {
    $isHttps = (
        (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ||
        (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https')
    );

    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'domain'   => '',
        'secure'   => $isHttps,   // only send cookie over HTTPS when the site is served over HTTPS
        'httponly' => true,       // JS cannot read the session cookie (mitigates XSS session theft)
        'samesite' => 'Lax',      // mitigates CSRF via cross-site navigations
    ]);
    ini_set('session.use_strict_mode', '1'); // reject uninitialized session IDs (helps prevent session fixation)

    session_start();
}

// --- Session timeout (idle) + periodic ID regeneration (mitigates session hijacking/fixation) ---
$SESSION_IDLE_TIMEOUT = 1800; // 30 minutes of inactivity
$SESSION_REGEN_INTERVAL = 900; // regenerate session id every 15 minutes

if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity']) > $SESSION_IDLE_TIMEOUT) {
    // Session expired due to inactivity: wipe it and start fresh
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
    }
    session_destroy();
    session_start();
}
$_SESSION['last_activity'] = time();

if (!isset($_SESSION['created_at'])) {
    $_SESSION['created_at'] = time();
} elseif (time() - $_SESSION['created_at'] > $SESSION_REGEN_INTERVAL) {
    session_regenerate_id(true);
    $_SESSION['created_at'] = time();
}

// Default branch session safety fallback
if (!isset($_SESSION['branch_id'])) {
    $_SESSION['branch_id'] = 1;
}

$host = 'localhost';
$db   = 'oaldtnte_canuuni';
$user = 'oaldtnte_WP0AM';
$pass = 'JRe9er5gL)yy';
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (\PDOException $e) {
    // In production, log error instead of raw output
    throw new \PDOException($e->getMessage(), (int)$e->getCode());
}

// Global robust helper function to sanitize string output against XSS
if (!function_exists('e')) {
    function e($string) {
        return htmlspecialchars($string ?? '', ENT_QUOTES, 'UTF-8');
    }
}

// Reusable CSRF helper functions (csrf_token(), csrf_field(), csrf_verify_request())
require_once __DIR__ . '/../includes/csrf.php';
