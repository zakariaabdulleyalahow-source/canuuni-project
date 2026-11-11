<?php
// Include database connection
include("../config/db.php");

// Safe Session Starter
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Fallback helper function to safely escape HTML output
if (!function_exists('e')) {
    function e($string) {
        return htmlspecialchars($string ?? '', ENT_QUOTES, 'UTF-8');
    }
}

// Fallback session variables for staging environments
if (!isset($_SESSION['branch_id'])) {
    $_SESSION['branch_id'] = 1; 
}

// System user identifier defaults for self-registered customers
$default_uid = 1; 

// Initial state messages
$error_message = "";
$success_message = "";

// Cryptographically secure Customer Code Generator
function generateCustomerCode() {
    return "CUST-" . random_int(100000, 999999);
}

// Handle Form Submission
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    // Collect and sanitize user inputs
    $fullname = isset($_POST['fullname']) ? trim($_POST['fullname']) : '';
    $gender   = isset($_POST['gender']) ? $_POST['gender'] : '';
    $phone    = isset($_POST['phone']) ? trim($_POST['phone']) : '';
    $email    = isset($_POST['email']) ? trim($_POST['email']) : '';
    $password = isset($_POST['password']) ? trim($_POST['password']) : '';
    $address  = isset($_POST['address']) ? trim($_POST['address']) : '';
    
    // Validation
    if (!csrf_verify_request()) {
        $error_message = "Your session expired or the request was invalid. Please refresh and try again.";
    } elseif (empty($fullname) || empty($gender) || empty($phone) || empty($email) || empty($password)) {
        $error_message = "All fields marked with an asterisk (*) are required.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error_message = "Please enter a valid email address.";
    } else {
        try {
            // 1. Secure check if email or phone already exists using PDO
            $check_query = "SELECT id FROM customers WHERE email = :email OR phone = :phone LIMIT 1";
            $check_stmt = $pdo->prepare($check_query);
            $check_stmt->execute([
                ':email' => $email,
                ':phone' => $phone
            ]);

            if ($check_stmt->fetch()) {
                $error_message = "An account with this email or phone number already exists.";
            } else {
                // 2. Setup automated values
                $customer_code   = generateCustomerCode();
                $hashed_password = password_hash($password, PASSWORD_BCRYPT);
                $status          = 'Active'; // Updated to match string check in login.php
                $created_by      = 1;
                
                // 3. Database insert using PDO
                $insert_query = "INSERT INTO `customers` 
                    (`customer_code`, `fullname`, `gender`, `phone`, `email`, `password`, `address`, `status`, `created_by`, `uid`, `created_at`) 
                    VALUES (:customer_code, :fullname, :gender, :phone, :email, :password, :address, :status, :created_by, :uid, NOW())";
                    
                $stmt = $pdo->prepare($insert_query);
                $executed = $stmt->execute([
                    ':customer_code' => $customer_code,
                    ':fullname'      => $fullname,
                    ':gender'        => $gender,
                    ':phone'         => $phone,
                    ':email'         => $email,
                    ':password'      => $hashed_password,
                    ':address'       => $address,
                    ':status'        => $status,
                    ':created_by'    => $created_by,
                    ':uid'           => $default_uid
                ]);

                if ($executed) {
                    $success_message = "Account created successfully! You can now log in.";
                    // Clear input variables to reset form fields on success
                    $fullname = $gender = $phone = $email = $address = "";
                } else {
                    $error_message = "Database Error: Could not complete registration.";
                }
            }
        } catch (\PDOException $e) {
            // Catch and output the database error message during debugging
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
        <div class="w-100" style="max-width: 650px;">
            <div class="card signup-card shadow-sm p-4 p-md-5 bg-white">
                
                <div class="text-center mb-4">
                    <h2 class="fw-bold text-dark mb-1">Create an Account</h2>
                    <p class="text-muted small">Join Canuuni E-Commerce to place tracking orders effortlessly</p>
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

                <!-- Registration Form -->
                <form action="" method="POST" class="needs-validation" novalidate>
                    <?= csrf_field() ?>
                    <div class="row g-3">
                        
                        <!-- Full Name -->
                        <div class="col-md-12">
                            <div class="form-floating">
                                <input type="text" class="form-control" id="fullname" name="fullname" placeholder="John Doe" value="<?= e($fullname ?? '') ?>" required>
                                <label for="fullname">Full Name *</label>
                            </div>
                        </div>

                        <!-- Email Address -->
                        <div class="col-md-7">
                            <div class="form-floating">
                                <input type="email" class="form-control" id="email" name="email" placeholder="name@example.com" value="<?= e($email ?? '') ?>" required>
                                <label for="email">Email Address *</label>
                            </div>
                        </div>

                        <!-- Gender Selector -->
                        <div class="col-md-5">
                            <div class="form-floating">
                                <select class="form-select" id="gender" name="gender" required>
                                    <option value="" disabled <?= empty($gender) ? 'selected' : '' ?>>Select...</option>
                                    <option value="Male" <?= (isset($gender) && $gender === 'Male') ? 'selected' : '' ?>>Male</option>
                                    <option value="Female" <?= (isset($gender) && $gender === 'Female') ? 'selected' : '' ?>>Female</option>
                                    <option value="Other" <?= (isset($gender) && $gender === 'Other') ? 'selected' : '' ?>>Other</option>
                                </select>
                                <label for="gender">Gender *</label>
                            </div>
                        </div>

                        <!-- Phone Number -->
                        <div class="col-md-6">
                            <div class="form-floating">
                                <input type="tel" class="form-control" id="phone" name="phone" placeholder="+252..." value="<?= e($phone ?? '') ?>" required>
                                <label for="phone">Phone Number *</label>
                            </div>
                        </div>

                        <!-- Password -->
                        <div class="col-md-6">
                            <div class="form-floating">
                                <input type="password" class="form-control" id="password" name="password" placeholder="Password" minlength="6" required>
                                <label for="password">Password *</label>
                            </div>
                        </div>

                        <!-- Address -->
                        <div class="col-12">
                            <div class="form-floating">
                                <textarea class="form-control" id="address" name="address" placeholder="Address Details" style="height: 100px"><?= e($address ?? '') ?></textarea>
                                <label for="address">Delivery Address (Optional)</label>
                            </div>
                        </div>

                        <!-- Terms Checkbox -->
                        <div class="col-12 my-2">
                            <div class="form-check small text-muted">
                                <input class="form-check-input" type="checkbox" value="" id="termsCheck" required>
                                <label class="form-check-label" for="termsCheck">
                                    I agree to the Canuuni Electronic terms & conditions structure.
                                </label>
                            </div>
                        </div>

                        <!-- Submit Button -->
                        <div class="col-12">
                            <button type="submit" class="btn btn-primary w-100 py-3 rounded-pill fw-bold shadow-sm">
                                <i class="bi bi-person-plus-fill me-2"></i>Register Account
                            </button>
                        </div>
                    </div>
                </form>

                <div class="text-center mt-4 border-top pt-3">
                    <p class="text-muted small mb-0">Already have a customer profile? <a href="login" class="text-decoration-none fw-bold">Log In here</a></p>
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
