<?php
include("../config/db.php");

// Safe fallback helper function to prevent undefined function errors
if (!function_exists('e')) {
    function e($string) {
        return htmlspecialchars($string ?? '', ENT_QUOTES, 'UTF-8');
    }
}

// Optionally include PHPMailer config if available
if (file_exists("../config/mail.php")) {
    include("../config/mail.php");
}

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$message = "";
$message_type = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $email = trim($_POST['email'] ?? '');

    if (!csrf_verify_request()) {
        $message = "Your session expired or the request was invalid. Please refresh and try again.";
        $message_type = "danger";
    } elseif (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = "Please enter a valid email address.";
        $message_type = "danger";
    } else {
        try {
            // Retrieve customer ID and Name
            $stmt = $pdo->prepare("SELECT id, fullname FROM customers WHERE email = :email LIMIT 1");
            $stmt->execute([':email' => $email]);
            $customer = $stmt->fetch();

            if ($customer) {
                // 1. Generate token and hashes
                $token      = bin2hex(random_bytes(32));
                $token_hash = hash("sha256", $token);
                $expires_at = date("Y-m-d H:i:s", time() + 3600);

                // 2. Save token hash to DB
                $update_stmt = $pdo->prepare("UPDATE customers SET reset_token_hash = :hash, reset_token_expires_at = :expires WHERE id = :id");
                $update_stmt->execute([
                    ':hash'    => $token_hash,
                    ':expires' => $expires_at,
                    ':id'      => $customer['id']
                ]);

                // 3. Construct URL pointing explicitly to reset.php
                $protocol   = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? "https" : "http";
                $reset_link = $protocol . "://" . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']) . "/reset.php?token=" . $token;

                // 4. Send Email via PHPMailer if configured, or show link on screen during testing
                if (function_exists('sendPasswordResetEmail')) {
                    $mailSent = sendPasswordResetEmail($email, $customer['fullname'], $reset_link);
                    if ($mailSent) {
                        $message = "A password reset link has been sent to your email address.";
                        $message_type = "success";
                    } else {
                        $message = "Unable to send email right now. Please try again later.";
                        $message_type = "danger";
                    }
                } else {
                    // Direct link display fallback for local testing
                    $message = "A password reset link was generated:<br><a href='" . e($reset_link) . "' class='fw-bold text-break'>" . e($reset_link) . "</a>";
                    $message_type = "success";
                }
            } else {
                // Generic response to avoid revealing if an email exists
                $message = "If an account with that email exists, a password reset link has been sent.";
                $message_type = "info";
            }
        } catch (\PDOException $e) {
            $message = "Database error: " . $e->getMessage();
            $message_type = "danger";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password - Canuuni</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light d-flex align-items-center min-vh-100">
    <div class="container" style="max-width: 480px;">
        <div class="card shadow-sm p-4 border-0 rounded-4">
            <h3 class="fw-bold text-center mb-2">Forgot Password?</h3>
            <p class="text-muted text-center small mb-4">Enter your registered email address to receive a password reset link.</p>

            <?php if (!empty($message)): ?>
                <div class="alert alert-<?= $message_type ?> py-2 small"><?= $message ?></div>
            <?php endif; ?>

            <form method="POST" action="">
                <?= csrf_field() ?>
                <div class="form-floating mb-3">
                    <input type="email" name="email" class="form-control" id="email" placeholder="name@example.com" value="<?= e($_POST['email'] ?? '') ?>" required>
                    <label for="email">Email address</label>
                </div>
                <button type="submit" class="btn btn-primary w-100 py-2 rounded-pill fw-bold">Request Reset Link</button>
            </form>

            <div class="text-center mt-3">
                <a href="login" class="text-decoration-none small text-muted">Back to Login</a>
            </div>
        </div>
    </div>
</body>
</html>