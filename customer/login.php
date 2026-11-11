<?php
// Start output buffering to prevent "Headers already sent" errors
ob_start();

// Include database connection
include("../config/db.php");

// Safe Session Starter
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Redirect if already logged in
if (isset($_SESSION['customer_id'])) {
    header("Location: dashboard");
    exit();
}

// Helper function to escape HTML
if (!function_exists('e')) {
    function e($string) {
        return htmlspecialchars($string ?? '', ENT_QUOTES, 'UTF-8');
    }
}

$error_message = "";
$success_message = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $email    = isset($_POST['email']) ? trim($_POST['email']) : '';
    $password = isset($_POST['password']) ? $_POST['password'] : '';

    if (!csrf_verify_request()) {
        $error_message = "Your session expired or the request was invalid. Please try again.";
    } elseif (empty($email) || empty($password)) {
        $error_message = "Please enter both email and password.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error_message = "Please enter a valid email address.";
    } else {
        try {
            $stmt = $pdo->prepare("SELECT id, customer_code, fullname, gender, phone, email, password, status FROM customers WHERE email = :email LIMIT 1");
            $stmt->execute([':email' => $email]);
            $customer = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($customer && password_verify($password, $customer['password'])) {
                
                // Flexible check for ENUM 'Active' or int 1
                $is_active = ($customer['status'] == 1 || strcasecmp((string)$customer['status'], 'Active') === 0);

                if (!$is_active) {
                    $error_message = "Your account is inactive or suspended. Please contact support.";
                } else {
                    // Regenerate session ID
                    session_regenerate_id(true);

                    // Set customer session variables
                    $_SESSION['customer_id']     = $customer['id'];
                    $_SESSION['customer_code']   = $customer['customer_code'];
                    $_SESSION['customer_name']   = $customer['fullname'];
                    $_SESSION['customer_email']  = $customer['email'];
                    $_SESSION['customer_phone']  = $customer['phone'];
                    $_SESSION['customer_gender'] = $customer['gender'];

                    // Clean output buffer before redirect
                    ob_end_clean();

                    // Redirect to dashboard
                    header("Location: dashboard");
                    exit(); // Ensure execution stops immediately
                }
            } else {
                $error_message = "Invalid email address or password.";
            }
        } catch (\PDOException $e) {
            $error_message = "Database Error: " . $e->getMessage();
        }
    }
}
?>
<?php
include '../includes/header.php';
include '../includes/navbar.php';
?>
    <!-- ========================= BODY / CONTENT ========================= -->
    <main class="container my-5 flex-grow-1 d-flex align-items-center justify-content-center">
        <div class="w-100" style="max-width: 500px;">
            <div class="card login-card shadow-sm p-4 p-md-5 bg-white">
                
                <div class="text-center mb-4">
                    <h2 class="fw-bold text-dark mb-1">Welcome Back</h2>
                    <p class="text-muted small">Log in to track your orders and manage profile details</p>
                </div>

                <!-- Alert Messages -->
                <?php if (!empty($error_message)): ?>
                    <div class="alert alert-danger d-flex align-items-center" role="alert">
                        <i class="bi bi-exclamation-triangle-fill me-2"></i>
                        <div><?= e($error_message) ?></div>
                    </div>
                <?php endif; ?>

                <?php if (!empty($success_message)): ?>
                    <div class="alert alert-success d-flex align-items-center" role="alert">
                        <i class="bi bi-check-circle-fill me-2"></i>
                        <div><?= e($success_message) ?></div>
                    </div>
                <?php endif; ?>

                <!-- Login Form -->
                <form action="" method="POST" class="needs-validation" novalidate>
                    <?= csrf_field() ?>
                    <div class="row g-3">
                        
                        <!-- Email Address -->
                        <div class="col-12">
                            <div class="form-floating">
                                <input type="email" class="form-control" id="email" name="email" placeholder="name@example.com" value="<?= e($email ?? '') ?>" required>
                                <label for="email">Email Address *</label>
                            </div>
                        </div>

                        <!-- Password -->
                        <div class="col-12">
                            <div class="form-floating">
                                <input type="password" class="form-control" id="password" name="password" placeholder="Password" required>
                                <label for="password">Password *</label>
                            </div>
                        </div>

                        <!-- Remember me & Forgot Password -->
                        <div class="col-12 d-flex justify-content-between align-items-center small my-2">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" value="" id="rememberMe">
                                <label class="form-check-label text-muted" for="rememberMe">
                                    Remember me
                                </label>
                            </div>
                            <a href="forgot_password" class="text-decoration-none fw-bold">Forgot password?</a>
                        </div>

                        <!-- Submit Button -->
                        <div class="col-12">
                            <button type="submit" class="btn btn-primary w-100 py-3 rounded-pill fw-bold shadow-sm">
                                <i class="bi bi-box-arrow-in-right me-2"></i>Log In
                            </button>
                        </div>
                    </div>
                </form>

                <div class="text-center mt-4 border-top pt-3">
                    <p class="text-muted small mb-0">Don't have an account yet? <a href="register" class="text-decoration-none fw-bold">Register here</a></p>
                </div>

            </div>
        </div>
    </main>
<?php
include '../includes/footer.php';
?>

    <!-- Bootstrap 5 Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        let cart = JSON.parse(localStorage.getItem('canuuni_cart')) || [];
        let totalQuantity = cart.reduce((total, item) => total + item.quantity, 0);
        document.getElementById('cart-counter-badge').innerText = totalQuantity;

        const forms = document.querySelectorAll('.needs-validation');
        Array.from(forms).forEach(form => {
            form.addEventListener('submit', event => {
                if (!form.checkValidity()) {
                    event.preventDefault();
                    event.stopPropagation();
                }
                form.classList.add('was-validated');
            }, false);
        });
    });
    </script>
</body>
</html>