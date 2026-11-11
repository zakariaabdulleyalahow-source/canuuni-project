<?php
include("../config/db.php");

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Safe fallback helper function to prevent undefined function fatal error
if (!function_exists('e')) {
    function e($string) {
        return htmlspecialchars($string ?? '', ENT_QUOTES, 'UTF-8');
    }
}

$token = $_GET['token'] ?? '';
$error_message = "";
$success_message = "";
$valid_token = false;
$customer_id = null;

if (empty($token)) {
    $error_message = "Invalid or missing password reset token.";
} else {
    // Hash the URL token to match the database stored hash
    $token_hash = hash("sha256", $token);

    try {
        // Validate token existence and check expiration time
        $stmt = $pdo->prepare("SELECT id FROM customers WHERE reset_token_hash = :hash AND reset_token_expires_at > NOW() LIMIT 1");
        $stmt->execute([':hash' => $token_hash]);
        $customer = $stmt->fetch();

        if ($customer) {
            $valid_token = true;
            $customer_id = $customer['id'];
        } else {
            $error_message = "This password reset link is invalid or has expired.";
        }
    } catch (\PDOException $e) {
        $error_message = "Database Error: " . $e->getMessage();
    }
}

// Handle Form Submission when resetting password
if ($_SERVER["REQUEST_METHOD"] === "POST" && $valid_token) {
    $new_password     = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    if (!csrf_verify_request()) {
        $error_message = "Your session expired or the request was invalid. Please refresh and try again.";
    } elseif (strlen($new_password) < 6) {
        $error_message = "Password must be at least 6 characters long.";
    } elseif ($new_password !== $confirm_password) {
        $error_message = "Passwords do not match.";
    } else {
        try {
            $hashed_password = password_hash($new_password, PASSWORD_BCRYPT);

            // Update password and clear token fields so it cannot be used again
            $update_stmt = $pdo->prepare("UPDATE customers SET password = :password, reset_token_hash = NULL, reset_token_expires_at = NULL WHERE id = :id");
            $update_stmt->execute([
                ':password' => $hashed_password,
                ':id'       => $customer_id
            ]);

            $success_message = "Your password has been updated successfully! You can now log in.";
            $valid_token = false; // Hide form on success
        } catch (\PDOException $e) {
            $error_message = "Failed to update password: " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password - Canuuni</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light d-flex align-items-center min-vh-100">
    <div class="container" style="max-width: 480px;">
        <div class="card shadow-sm p-4 border-0 rounded-4">
            <h3 class="fw-bold text-center mb-3">Set New Password</h3>

            <?php if (!empty($error_message)): ?>
                <div class="alert alert-danger py-2 small"><?= e($error_message) ?></div>
            <?php endif; ?>

            <?php if (!empty($success_message)): ?>
                <div class="alert alert-success py-2 small"><?= e($success_message) ?></div>
                <div class="text-center mt-3">
                    <a href="login" class="btn btn-primary rounded-pill px-4 fw-bold">Proceed to Login</a>
                </div>
            <?php endif; ?>

            <?php if ($valid_token): ?>
                <form method="POST" action="">
                    <?= csrf_field() ?>
                    <div class="form-floating mb-3">
                        <input type="password" name="password" class="form-control" id="password" placeholder="New Password" minlength="6" required>
                        <label for="password">New Password</label>
                    </div>
                    <div class="form-floating mb-3">
                        <input type="password" name="confirm_password" class="form-control" id="confirm_password" placeholder="Confirm Password" minlength="6" required>
                        <label for="confirm_password">Confirm New Password</label>
                    </div>
                    <button type="submit" class="btn btn-primary w-100 py-2 rounded-pill fw-bold">Update Password</button>
                </form>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>