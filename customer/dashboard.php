<?php
// Start output buffering to prevent header errors
ob_start();

include("../config/db.php");

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Session Auth Guard
if (!isset($_SESSION['customer_id'])) {
    header("Location: login");
    exit();
}

if (!function_exists('e')) {
    function e($string) {
        return htmlspecialchars($string ?? '', ENT_QUOTES, 'UTF-8');
    }
}

$customer_id = $_SESSION['customer_id'];
$error_message = "";
$success_message = "";

// ---------------------------------------------------------
// 1. FETCH LOGGED-IN CUSTOMER DETAILS
// ---------------------------------------------------------
try {
    $stmt = $pdo->prepare("SELECT id, customer_code, fullname, gender, phone, email, address, status, created_at FROM customers WHERE id = :id LIMIT 1");
    $stmt->execute([':id' => $customer_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        session_destroy();
        header("Location: login");
        exit();
    }
} catch (\PDOException $e) {
    die("Error loading profile: " . $e->getMessage());
}

// ---------------------------------------------------------
// 2. FETCH CUSTOMER SALES (Completed Orders)
// ---------------------------------------------------------
$sales = [];
try {
    $sales_stmt = $pdo->prepare("
        SELECT s.sale_id, s.qty, s.amount, s.datenow, p.product_name, p.productimage
        FROM sales s
        LEFT JOIN products p ON s.prid = p.id
        WHERE s.customer_id = :customer_id
        ORDER BY s.sale_id DESC
    ");
    $sales_stmt->execute([':customer_id' => $customer_id]);
    $sales = $sales_stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (\PDOException $e) {
    $sales = [];
}

// ---------------------------------------------------------
// 3. FETCH CUSTOMER PREORDERS (Tracking)
// ---------------------------------------------------------
$preorders = [];
try {
    $preorder_stmt = $pdo->prepare("
        SELECT p.id, p.product_id, p.amount, p.location, p.date, p.status, p.comment, p.uid, pr.product_name, pr.unitprice, pr.productimage, pm.method_name
        FROM preorder p
        LEFT JOIN products pr ON p.product_id = pr.id
        LEFT JOIN payment_methods pm ON p.payment_methods_id = pm.id
        WHERE p.customer_id = :customer_id
        ORDER BY p.date DESC
    ");
    $preorder_stmt->execute([':customer_id' => $customer_id]);
    $preorders = $preorder_stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (\PDOException $e) {
    $preorders = [];
}

// ---------------------------------------------------------
// 4. POST FORM HANDLERS
// ---------------------------------------------------------
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['action'])) {
    
    // Action A: Update Profile Details
    if ($_POST['action'] === 'update_profile') {
        $fullname = trim($_POST['fullname'] ?? '');
        $email    = trim($_POST['email'] ?? '');
        $phone    = trim($_POST['phone'] ?? '');
        $gender   = trim($_POST['gender'] ?? '');

        if (empty($fullname) || empty($email) || empty($phone)) {
            $error_message = "All primary profile fields are required.";
        } else {
            try {
                $up_stmt = $pdo->prepare("UPDATE customers SET fullname = :fullname, email = :email, phone = :phone, gender = :gender WHERE id = :id");
                $up_stmt->execute([
                    ':fullname' => $fullname,
                    ':email'    => $email,
                    ':phone'    => $phone,
                    ':gender'   => $gender,
                    ':id'       => $customer_id
                ]);
                $user['fullname'] = $fullname;
                $user['email']    = $email;
                $user['phone']    = $phone;
                $user['gender']   = $gender;
                $success_message  = "Profile updated successfully!";
            } catch (\PDOException $e) {
                $error_message = "Failed to update profile: " . $e->getMessage();
            }
        }
    }

    // Action B: Update Address Book
    elseif ($_POST['action'] === 'update_address') {
        $address = trim($_POST['address'] ?? '');
        if (empty($address)) {
            $error_message = "Address field cannot be empty.";
        } else {
            try {
                $addr_stmt = $pdo->prepare("UPDATE customers SET address = :address WHERE id = :id");
                $addr_stmt->execute([':address' => $address, ':id' => $customer_id]);
                $user['address'] = $address;
                $success_message = "Address book updated successfully!";
            } catch (\PDOException $e) {
                $error_message = "Failed to update address: " . $e->getMessage();
            }
        }
    }

    // Action C: Password Update
    elseif ($_POST['action'] === 'update_password') {
        $current_password = $_POST['current_password'] ?? '';
        $new_password     = $_POST['new_password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';

        if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
            $error_message = "All password fields are required.";
        } elseif ($new_password !== $confirm_password) {
            $error_message = "New passwords do not match.";
        } elseif (strlen($new_password) < 6) {
            $error_message = "Password must be at least 6 characters long.";
        } else {
            try {
                $pwd_stmt = $pdo->prepare("SELECT password FROM customers WHERE id = :id LIMIT 1");
                $pwd_stmt->execute([':id' => $customer_id]);
                $db_pass = $pwd_stmt->fetchColumn();

                if (password_verify($current_password, $db_pass) || $current_password === $db_pass) {
                    $new_hash = password_hash($new_password, PASSWORD_BCRYPT);
                    $update_pwd = $pdo->prepare("UPDATE customers SET password = :password WHERE id = :id");
                    $update_pwd->execute([':password' => $new_hash, ':id' => $customer_id]);
                    $success_message = "Password updated successfully!";
                } else {
                    $error_message = "Incorrect current password.";
                }
            } catch (\PDOException $e) {
                $error_message = "Database Error: " . $e->getMessage();
            }
        }
    }
}

include '../includes/header.php';
include '../includes/navbar.php';
?>

<!-- Tracking Timeline Styles -->
<style>
.tracking-timeline {
    position: relative;
    display: flex;
    justify-content: space-between;
    margin: 20px 0;
}
.tracking-timeline::before {
    content: '';
    position: absolute;
    top: 20px;
    left: 10%;
    right: 10%;
    height: 4px;
    background: #e9ecef;
    z-index: 1;
}
.tracking-step {
    position: relative;
    z-index: 2;
    text-align: center;
    flex: 1;
}
.tracking-icon {
    width: 42px;
    height: 42px;
    border-radius: 50%;
    background: #e9ecef;
    color: #6c757d;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 8px;
    font-size: 18px;
    transition: all 0.3s ease;
}
.tracking-step.completed .tracking-icon {
    background: #0d6efd;
    color: #fff;
}
.tracking-step.active .tracking-icon {
    background: #ffc107;
    color: #000;
    box-shadow: 0 0 0 4px rgba(255, 193, 7, 0.25);
}
.tracking-step.cancelled .tracking-icon {
    background: #dc3545;
    color: #fff;
}
.tracking-label {
    font-size: 12px;
    font-weight: 600;
    color: #495057;
}
</style>

<main class="container my-5 flex-grow-1">

    <?php if (!empty($error_message)): ?>
        <div class="alert alert-danger alert-dismissible fade show mb-4 rounded-4 border-0 shadow-sm" role="alert">
            <i class="bi bi-exclamation-triangle-fill me-2"></i><?= e($error_message) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <?php if (!empty($success_message)): ?>
        <div class="alert alert-success alert-dismissible fade show mb-4 rounded-4 border-0 shadow-sm" role="alert">
            <i class="bi bi-check-circle-fill me-2"></i><?= e($success_message) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <div class="row g-4">
        <!-- Sidebar Navigation -->
        <div class="col-lg-3">
            <div class="card border-0 shadow-sm rounded-4 p-3 bg-white">
                <div class="text-center py-3 border-bottom mb-3">
                    <div class="bg-primary text-white rounded-circle d-inline-flex align-items-center justify-content-center mb-2 shadow-sm" style="width: 65px; height: 65px; font-size: 26px;">
                        <?= strtoupper(substr($user['fullname'] ?? 'U', 0, 1)) ?>
                    </div>
                    <h6 class="fw-bold mb-0"><?= e($user['fullname']) ?></h6>
                    <small class="text-muted"><?= e($user['customer_code']) ?></small>
                </div>

                <div class="nav flex-column nav-pills" id="v-pills-tab" role="tablist">
                    <button class="nav-link active text-start mb-1" id="tab-tracking-btn" data-bs-toggle="pill" data-bs-target="#tab-tracking" type="button">
                        <i class="bi bi-geo-alt me-2"></i> Order Tracking
                    </button>
                    <button class="nav-link text-start mb-1" id="tab-orders-btn" data-bs-toggle="pill" data-bs-target="#tab-orders" type="button">
                        <i class="bi bi-bag-check me-2"></i> Purchase History
                    </button>
                    <button class="nav-link text-start mb-1" id="tab-profile-btn" data-bs-toggle="pill" data-bs-target="#tab-profile" type="button">
                        <i class="bi bi-person me-2"></i> My Profile
                    </button>
                    <button class="nav-link text-start mb-1" id="tab-address-btn" data-bs-toggle="pill" data-bs-target="#tab-address" type="button">
                        <i class="bi bi-journals me-2"></i> Address Book
                    </button>
                    <button class="nav-link text-start mb-1" id="tab-wishlist-btn" data-bs-toggle="pill" data-bs-target="#tab-wishlist" type="button">
                        <i class="bi bi-heart me-2"></i> Wishlist
                    </button>
                    <button class="nav-link text-start mb-1" id="tab-notifications-btn" data-bs-toggle="pill" data-bs-target="#tab-notifications" type="button">
                        <i class="bi bi-bell me-2"></i> Notifications
                    </button>
                    <button class="nav-link text-start mb-1" id="tab-password-btn" data-bs-toggle="pill" data-bs-target="#tab-password" type="button">
                        <i class="bi bi-shield-lock me-2"></i> Change Password
                    </button>
                </div>
            </div>
        </div>

        <!-- Tab Content -->
        <div class="col-lg-9">
            <div class="tab-content" id="v-pills-tabContent">
                
                <!-- 1. ORDER TRACKING TAB -->
                <div class="tab-pane fade show active" id="tab-tracking" role="tabpanel">
                    <div class="card border-0 shadow-sm rounded-4 p-4 bg-white">
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <h4 class="fw-bold mb-0"><i class="bi bi-geo-alt text-primary me-2"></i>Preorder Status & Tracking</h4>
                            <span class="badge bg-primary rounded-pill px-3 py-2"><?= count($preorders) ?> Active Preorder(s)</span>
                        </div>

                        <?php if (empty($preorders)): ?>
                            <div class="text-center py-5">
                                <i class="bi bi-calendar-x display-4 text-muted mb-3 d-block"></i>
                                <p class="text-muted">You have not placed any preorders yet.</p>
                                <a href="../index" class="btn btn-outline-primary btn-sm rounded-pill px-4">Browse Products</a>
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-hover align-middle">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Preorder ID</th>
                                            <th>Product</th>
                                            <th>Amount</th>
                                            <th>Date</th>
                                            <th>Status</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($preorders as $row): 
                                            $status = ucfirst(strtolower($row['status'] ?? 'Pending'));
                                            $status_class = match ($status) {
                                                'Delivering' => 'bg-info text-dark',
                                                'Arrived'    => 'bg-success text-white',
                                                'Cancelled'  => 'bg-danger text-white',
                                                default      => 'bg-warning text-dark', // Pending
                                            };
                                        ?>
                                            <tr>
                                                <td><strong>#PRE-<?= e($row['id']) ?></strong></td>
                                                <td><span class="fw-bold text-dark"><?= e($row['product_name'] ?? 'Product #' . $row['product_id']) ?></span></td>
                                                <td>$<?= number_format((float)($row['amount'] ?? 0), 2) ?></td>
                                                <td class="small text-muted"><?= !empty($row['date']) ? date('M d, Y', strtotime($row['date'])) : 'N/A' ?></td>
                                                <td><span class="badge rounded-pill px-3 py-2 <?= $status_class ?>"><?= e($status) ?></span></td>
                                                <td>
                                                    <button class="btn btn-sm btn-light border rounded-pill px-3 shadow-sm" data-bs-toggle="modal" data-bs-target="#trackModal<?= $row['id'] ?>">
                                                        <i class="bi bi-geo me-1"></i>Track
                                                    </button>

                                                    <!-- Tracking Modal -->
                                                    <div class="modal fade" id="trackModal<?= $row['id'] ?>" tabindex="-1">
                                                        <div class="modal-dialog modal-dialog-centered modal-lg">
                                                            <div class="modal-content rounded-4 border-0 shadow">
                                                                <div class="modal-header">
                                                                    <h5 class="modal-title fw-bold">Track Preorder #PRE-<?= e($row['id']) ?></h5>
                                                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                                </div>
                                                                <div class="modal-body p-4">
                                                                    
                                                                    <!-- TRACKING TIMELINE BAR -->
                                                                    <?php if ($status === 'Cancelled'): ?>
                                                                        <div class="alert alert-danger text-center rounded-3 mb-4">
                                                                            <i class="bi bi-x-circle-fill me-2"></i> This preorder has been cancelled.
                                                                        </div>
                                                                    <?php else: ?>
                                                                        <div class="tracking-timeline">
                                                                            <div class="tracking-step <?= in_array($status, ['Pending', 'Delivering', 'Arrived']) ? 'completed' : '' ?>">
                                                                                <div class="tracking-icon"><i class="bi bi-hourglass-split"></i></div>
                                                                                <div class="tracking-label">Pending</div>
                                                                            </div>
                                                                            <div class="tracking-step <?= in_array($status, ['Delivering', 'Arrived']) ? 'completed' : ($status === 'Delivering' ? 'active' : '') ?>">
                                                                                <div class="tracking-icon"><i class="bi bi-truck"></i></div>
                                                                                <div class="tracking-label">Delivering</div>
                                                                            </div>
                                                                            <div class="tracking-step <?= $status === 'Arrived' ? 'completed' : '' ?>">
                                                                                <div class="tracking-icon"><i class="bi bi-house-door-fill"></i></div>
                                                                                <div class="tracking-label">Arrived</div>
                                                                            </div>
                                                                        </div>
                                                                    <?php endif; ?>

                                                                    <div class="row g-3 mt-2">
                                                                        <div class="col-md-6">
                                                                            <p class="mb-1"><strong>Item:</strong> <?= e($row['product_name'] ?? 'Product #' . $row['product_id']) ?></p>
                                                                            <p class="mb-1"><strong>Payment Method:</strong> <?= e($row['method_name'] ?: 'Standard Method') ?></p>
                                                                        </div>
                                                                        <div class="col-md-6">
                                                                            <p class="mb-1"><strong>Delivery Location:</strong> <?= e($row['location'] ?: $user['address']) ?></p>
                                                                            <p class="mb-1"><strong>Date Placed:</strong> <?= e($row['date']) ?></p>
                                                                        </div>
                                                                    </div>

                                                                    <hr>
                                                                    <p class="fw-bold mb-2">Fulfillment Notes:</p>
                                                                    <div class="alert alert-secondary mb-0 small rounded-3">
                                                                        <i class="bi bi-info-circle me-1"></i> <?= e($row['comment'] ?: 'Your order is being prepared for fulfillment.') ?>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- 2. PURCHASE HISTORY (SALES) TAB -->
                <div class="tab-pane fade" id="tab-orders" role="tabpanel">
                    <div class="card border-0 shadow-sm rounded-4 p-4 bg-white">
                        <h4 class="fw-bold mb-4"><i class="bi bi-bag-check text-primary me-2"></i>Purchase History</h4>
                        <?php if (empty($sales)): ?>
                            <div class="text-center py-5">
                                <i class="bi bi-cart-x display-4 text-muted mb-3 d-block"></i>
                                <p class="text-muted">No past sales records found for your account.</p>
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-hover align-middle">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Sale ID</th>
                                            <th>Product Name</th>
                                            <th>Qty</th>
                                            <th>Total Amount</th>
                                            <th>Date</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($sales as $sale): ?>
                                            <tr>
                                                <td><strong>#SALE-<?= e($sale['sale_id']) ?></strong></td>
                                                <td><?= e($sale['product_name'] ?? 'Direct Purchase') ?></td>
                                                <td><?= (int)$sale['qty'] ?></td>
                                                <td class="fw-bold text-success">$<?= number_format((float)$sale['amount'], 2) ?></td>
                                                <td class="small text-muted"><?= e($sale['datenow']) ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- 3. PROFILE TAB -->
                <div class="tab-pane fade" id="tab-profile" role="tabpanel">
                    <div class="card border-0 shadow-sm rounded-4 p-4 bg-white">
                        <h4 class="fw-bold mb-4"><i class="bi bi-person text-primary me-2"></i>My Profile</h4>
                        <form method="POST" action="">
                            <input type="hidden" name="action" value="update_profile">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label text-muted small mb-1">Customer ID</label>
                                    <input type="text" class="form-control rounded-3" value="<?= e($user['customer_code']) ?>" disabled>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label text-muted small mb-1">Full Name</label>
                                    <input type="text" name="fullname" class="form-control rounded-3" value="<?= e($user['fullname']) ?>" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label text-muted small mb-1">Email Address</label>
                                    <input type="email" name="email" class="form-control rounded-3" value="<?= e($user['email']) ?>" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label text-muted small mb-1">Phone Number</label>
                                    <input type="text" name="phone" class="form-control rounded-3" value="<?= e($user['phone']) ?>" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label text-muted small mb-1">Gender</label>
                                    <select name="gender" class="form-select rounded-3">
                                        <option value="Male" <?= ($user['gender'] === 'Male') ? 'selected' : '' ?>>Male</option>
                                        <option value="Female" <?= ($user['gender'] === 'Female') ? 'selected' : '' ?>>Female</option>
                                        <option value="Other" <?= ($user['gender'] === 'Other') ? 'selected' : '' ?>>Other</option>
                                    </select>
                                </div>
                            </div>
                            <button type="submit" class="btn btn-primary rounded-pill px-4 mt-4 fw-bold shadow-sm">Save Changes</button>
                        </form>
                    </div>
                </div>

                <!-- 4. ADDRESS BOOK TAB -->
                <div class="tab-pane fade" id="tab-address" role="tabpanel">
                    <div class="card border-0 shadow-sm rounded-4 p-4 bg-white">
                        <h4 class="fw-bold mb-4"><i class="bi bi-journals text-primary me-2"></i>Address Book</h4>
                        <form method="POST" action="">
                            <input type="hidden" name="action" value="update_address">
                            <div class="mb-3">
                                <label class="form-label text-muted small mb-1">Primary Shipping Address</label>
                                <textarea name="address" class="form-control rounded-3" rows="4" required><?= e($user['address']) ?></textarea>
                            </div>
                            <button type="submit" class="btn btn-primary rounded-pill px-4 fw-bold shadow-sm">Update Address</button>
                        </form>
                    </div>
                </div>

                <!-- 5. WISHLIST TAB -->
                <div class="tab-pane fade" id="tab-wishlist" role="tabpanel">
                    <div class="card border-0 shadow-sm rounded-4 p-4 bg-white">
                        <h4 class="fw-bold mb-4"><i class="bi bi-heart text-primary me-2"></i>Saved Wishlist</h4>
                        <div class="text-center py-5">
                            <i class="bi bi-suit-heart display-4 text-muted mb-3 d-block"></i>
                            <p class="text-muted">Your wishlist is currently empty.</p>
                            <a href="../index" class="btn btn-outline-primary btn-sm rounded-pill px-4">Explore Products</a>
                        </div>
                    </div>
                </div>

                <!-- 6. NOTIFICATIONS TAB -->
                <div class="tab-pane fade" id="tab-notifications" role="tabpanel">
                    <div class="card border-0 shadow-sm rounded-4 p-4 bg-white">
                        <h4 class="fw-bold mb-4"><i class="bi bi-bell text-primary me-2"></i>Account Notifications</h4>
                        <div class="list-group list-group-flush">
                            <div class="list-group-item px-0 py-3">
                                <div class="d-flex w-100 justify-content-between">
                                    <h6 class="mb-1 fw-bold">Welcome to your dashboard!</h6>
                                    <small class="text-muted">System</small>
                                </div>
                                <p class="mb-1 text-muted small">Manage your orders, preorders, and account settings seamlessly from this panel.</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 7. CHANGE PASSWORD TAB -->
                <div class="tab-pane fade" id="tab-password" role="tabpanel">
                    <div class="card border-0 shadow-sm rounded-4 p-4 bg-white">
                        <h4 class="fw-bold mb-4"><i class="bi bi-shield-lock text-primary me-2"></i>Change Password</h4>
                        <form method="POST" action="">
                            <input type="hidden" name="action" value="update_password">
                            <div class="form-floating mb-3">
                                <input type="password" name="current_password" class="form-control rounded-3" id="currPass" placeholder="Current Password" required>
                                <label for="currPass">Current Password</label>
                            </div>
                            <div class="form-floating mb-3">
                                <input type="password" name="new_password" class="form-control rounded-3" id="newPass" placeholder="New Password" minlength="6" required>
                                <label for="newPass">New Password</label>
                            </div>
                            <div class="form-floating mb-4">
                                <input type="password" name="confirm_password" class="form-control rounded-3" id="confPass" placeholder="Confirm Password" minlength="6" required>
                                <label for="confPass">Confirm New Password</label>
                            </div>
                            <button type="submit" class="btn btn-primary rounded-pill px-4 py-2 fw-bold shadow-sm">Update Password</button>
                        </form>
                    </div>
                </div>

            </div>
        </div>
    </div>
</main>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<!-- AJAX Auto-Refresh Script (Every 10 seconds without page reload) -->
<script>
document.addEventListener("DOMContentLoaded", function() {
    setInterval(function() {
        // Skip refresh if a tracking modal is currently open so it doesn't close on the user
        if (document.querySelector('.modal.show')) return;

        fetch(window.location.href)
            .then(response => response.text())
            .then(html => {
                const parser = new DOMParser();
                const doc = parser.parseFromString(html, 'text/html');

                // Update Order Tracking tab content
                const newTracking = doc.querySelector('#tab-tracking');
                const currentTracking = document.querySelector('#tab-tracking');
                if (newTracking && currentTracking) {
                    currentTracking.innerHTML = newTracking.innerHTML;
                }

                // Update Purchase History tab content
                const newOrders = doc.querySelector('#tab-orders');
                const currentOrders = document.querySelector('#tab-orders');
                if (newOrders && currentOrders) {
                    currentOrders.innerHTML = newOrders.innerHTML;
                }
            })
            .catch(err => console.error('Background refresh failed:', err));
    }, 10000); // 10000ms = 10 seconds
});
</script>

<?php
include '../includes/footer.php';
?>