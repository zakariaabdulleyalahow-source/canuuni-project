<?php
// includes/csrf.php
// Reusable CSRF token generation + validation.
// Include this AFTER session_start() (config/db.php already starts the session).

if (!function_exists('csrf_token')) {
    /**
     * Returns the current CSRF token, generating one if it doesn't exist yet.
     */
    function csrf_token() {
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }
}

if (!function_exists('csrf_field')) {
    /**
     * Echo-ready hidden input field containing the CSRF token.
     * Usage inside a <form>: <?= csrf_field() ?>
     */
    function csrf_field() {
        $token = htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8');
        return '<input type="hidden" name="csrf_token" value="' . $token . '">';
    }
}

if (!function_exists('csrf_verify')) {
    /**
     * Validates a submitted CSRF token against the one stored in the session.
     * Returns true if valid, false otherwise. Uses constant-time comparison.
     */
    function csrf_verify($submittedToken) {
        if (empty($_SESSION['csrf_token']) || empty($submittedToken)) {
            return false;
        }
        return hash_equals($_SESSION['csrf_token'], (string) $submittedToken);
    }
}

if (!function_exists('csrf_verify_request')) {
    /**
     * Convenience helper: verifies the CSRF token from $_POST['csrf_token'].
     * Returns true/false. Does NOT die/exit -- caller decides how to handle failure,
     * so existing error-message flows in each page keep working unchanged.
     */
    function csrf_verify_request() {
        $submitted = $_POST['csrf_token'] ?? '';
        return csrf_verify($submitted);
    }
}
