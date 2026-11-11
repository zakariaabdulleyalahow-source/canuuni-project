<?php
// admin/login.php
//
// Authenticates against the real `users` table schema:
// id, name, username, password, role ('admin'|'manager'|'staff'), branch_id.
//
// NOTE ON PASSWORDS: some existing accounts in this table still have
// plain-text passwords (a legacy issue from before this table used hashing).
// To fix this without locking anyone out, login here does BOTH:
//   1. If the stored value looks like a bcrypt hash, verify normally via password_verify().
//   2. If it's still plain text and matches exactly, log the user in AND
//      transparently rehash+store it as bcrypt right then -- so every account
//      is automatically migrated to a secure hash the next time it's used,
//      with no forced password reset and no downtime.
ob_start();

require_once '../config/db.php';

// Redirect if already logged in
if (isset($_SESSION['user_id'])) {
    header("Location: index");
    exit();
}

$error_message = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $username = isset($_POST['username']) ? trim($_POST['username']) : '';
    $password = isset($_POST['password']) ? $_POST['password'] : '';

    if (!csrf_verify_request()) {
        $error_message = "Your session expired or the request was invalid. Please try again.";
    } elseif (empty($username) || empty($password)) {
        $error_message = "Please enter both username and password.";
    } else {
        try {
            $stmt = $pdo->prepare("SELECT id, name, username, password, role, branch_id FROM users WHERE username = :username LIMIT 1");
            $stmt->execute([':username' => $username]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            $authenticated = false;

            if ($user) {
                $stored = (string) $user['password'];
                $looksHashed = (bool) preg_match('/^\$2[ayb]\$/', $stored);

                if ($looksHashed) {
                    $authenticated = password_verify($password, $stored);
                } elseif ($stored !== '' && hash_equals($stored, $password)) {
                    // Legacy plain-text password matched. Authenticate, then
                    // migrate this account to a proper bcrypt hash right now.
                    $authenticated = true;
                    $newHash = password_hash($password, PASSWORD_BCRYPT);
                    $migrate = $pdo->prepare("UPDATE users SET password = :password WHERE id = :id");
                    $migrate->execute([':password' => $newHash, ':id' => $user['id']]);
                }
            }

            if ($authenticated) {
                session_regenerate_id(true);

                $_SESSION['user_id']    = $user['id'];
                $_SESSION['user_name']  = $user['name'];
                $_SESSION['user_role']  = $user['role'];
                $_SESSION['branch_id']  = $user['branch_id'];

                ob_end_clean();
                header("Location: index");
                exit();
            } else {
                $error_message = "Invalid username or password.";
            }
        } catch (\PDOException $e) {
            // In production, log this instead of displaying it.
            $error_message = "Database error. Please try again shortly.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - Canuuni</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
</head>
<body class="bg-dark d-flex align-items-center justify-content-center min-vh-100">
    <div class="container" style="max-width: 420px;">
        <div class="card shadow-sm p-4 p-md-5 border-0 rounded-4">
            <div class="text-center mb-4">
                <h3 class="fw-bold text-dark mb-1"><i class="bi bi-shield-lock-fill me-2 text-primary"></i>Admin Login</h3>
                <p class="text-muted small mb-0">Branch admin access only</p>
            </div>

            <?php if (!empty($error_message)): ?>
                <div class="alert alert-danger py-2 small"><?= e($error_message) ?></div>
            <?php endif; ?>

            <form method="POST" action="">
                <?= csrf_field() ?>
                <div class="form-floating mb-3">
                    <input type="text" class="form-control" id="username" name="username" placeholder="Username" value="<?= e($_POST['username'] ?? '') ?>" required autofocus>
                    <label for="username">Username</label>
                </div>
                <div class="form-floating mb-3">
                    <input type="password" class="form-control" id="password" name="password" placeholder="Password" required>
                    <label for="password">Password</label>
                </div>
                <button type="submit" class="btn btn-primary w-100 py-2 rounded-pill fw-bold">
                    <i class="bi bi-box-arrow-in-right me-2"></i>Log In
                </button>
            </form>
        </div>
    </div>
</body>
</html>
