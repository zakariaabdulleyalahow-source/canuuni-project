<?php
// checkout.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once 'config/db.php';

// Auth Guard
if (!isset($_SESSION['customer_id'])) {
    header("Location: customer/login?redirect=checkout");
    exit();
}

if (empty($_SESSION['cart'])) {
    header("Location: cart");
    exit();
}

$customer_id = $_SESSION['customer_id'];
$uid = $_SESSION['user_id'] ?? 1; // Used for branch/user identification
$error_message = "";
$success = false;

// Delivery Fee Configuration
$delivery_fee = 1.00;

// Fetch Available Payment Methods for the current branch/user
$payment_methods = [];
try {
    $stmtMethods = $pdo->prepare("SELECT `id`, `method_name`, `uid` FROM `payment_methods` WHERE `uid` = ?");
    $stmtMethods->execute([$uid]);
    $payment_methods = $stmtMethods->fetchAll(PDO::FETCH_ASSOC);
} catch (\Exception $e) {
    // Fallback error logging if table fetch fails
    error_log("Failed to fetch payment methods: " . $e->getMessage());
}

// Calculate Totals
$cart_subtotal = 0;
foreach ($_SESSION['cart'] as $item) {
    $cart_subtotal += (float)($item['price'] ?? 0) * (int)($item['quantity'] ?? 1);
}
$grand_total = $cart_subtotal + $delivery_fee;

// Handle Order Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'place_order') {
    $address = trim($_POST['address'] ?? '');
    $payment_methods_id = (int)($_POST['payment_method_id'] ?? 0);
    $notes = trim($_POST['comment'] ?? '');

    if (!csrf_verify_request()) {
        $error_message = "Your session expired or the request was invalid. Please refresh and try again.";
    } elseif (empty($address)) {
        $error_message = "Delivery address is required.";
    } elseif ($payment_methods_id <= 0) {
        $error_message = "Please select a valid payment method.";
    } else {
        try {
            $pdo->beginTransaction();

            // Insert into `preorder` table with exact schema matching
            $stmtSales = $pdo->prepare("
                INSERT INTO `preorder` (`customer_id`, `product_id`, `amount`, `payment_methods_id`, `location`, `date`, `status`, `comment`, `uid`) 
                VALUES (?, ?, ?, ?, ?, NOW(), 'Pending', ?, ?)
            ");

            $stmtStock = $pdo->prepare("UPDATE products SET quantity = quantity - ? WHERE id = ?");

            foreach ($_SESSION['cart'] as $item) {
                $productId = (int)$item['id'];
                $quantity = (int)$item['quantity'];
                $itemAmount = (float)($item['price'] ?? 0) * $quantity;

                // Insert each cart item into `preorder`
                $stmtSales->execute([
                    $customer_id,
                    $productId,
                    $itemAmount,
                    $payment_methods_id,
                    $address,
                    $notes,
                    $uid
                ]);

                // Deduct Product Quantity
                $stmtStock->execute([$quantity, $productId]);
            }

            $pdo->commit();

            // Clear Cart
            $_SESSION['cart'] = [];
            $success = true;
        } catch (\Exception $e) {
            $pdo->rollBack();
            $error_message = "Failed to complete transaction: " . $e->getMessage();
        }
    }
}

include 'includes/header.php';
include 'includes/navbar.php';
?>

<main class="container my-5 flex-grow-1">
    <?php if ($success): ?>
        <div class="card border-0 shadow-sm text-center p-5 rounded-4">
            <div class="card-body">
                <i class="bi bi-check-circle-fill display-1 text-success mb-3"></i>
                <h2 class="fw-bold">Order Placed Successfully!</h2>
                <p class="text-muted">Thank you for your purchase. Your order has been placed and is being processed.</p>
                <a href="customer/dashboard" class="btn btn-primary rounded-pill px-4 mt-3">View Order Status</a>
            </div>
        </div>
    <?php else: ?>
        <h2 class="fw-bold mb-4"><i class="bi bi-credit-card me-2"></i>Checkout</h2>

        <?php if (!empty($error_message)): ?>
            <div class="alert alert-danger mb-4"><?= e($error_message) ?></div>
        <?php endif; ?>

        <form method="POST" action="">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="place_order">
            <div class="row g-4">
                
                <!-- Left Side: Shipping & Payment Options -->
                <div class="col-lg-7">
                    <div class="card border-0 shadow-sm p-4 rounded-4 mb-4">
                        <h5 class="fw-bold mb-3">Shipping Information</h5>
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Delivery Address / Location</label>
                            <textarea name="address" class="form-control" rows="3" required placeholder="Enter full delivery address and location details..."></textarea>
                        </div>
                        <div class="mb-0">
                            <label class="form-label fw-semibold">Additional Notes (Optional)</label>
                            <input type="text" name="comment" class="form-control" placeholder="Any specific instructions for delivery...">
                        </div>
                    </div>

                    <div class="card border-0 shadow-sm p-4 rounded-4">
                        <h5 class="fw-bold mb-3">Select Payment Method</h5>
                        <?php if (!empty($payment_methods)): ?>
                            <?php foreach ($payment_methods as $index => $method): ?>
                                <div class="form-check mb-2">
                                    <input class="form-check-input" type="radio" name="payment_method_id" id="pm_<?= (int)$method['id'] ?>" value="<?= (int)$method['id'] ?>" <?= $index === 0 ? 'checked' : '' ?> required>
                                    <label class="form-check-label fw-semibold" for="pm_<?= (int)$method['id'] ?>">
                                        <?= e($method['method_name']) ?>
                                    </label>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <p class="text-muted small">No dynamic payment methods configured. Please contact support.</p>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Right Side: Order Summary -->
                <div class="col-lg-5">
                    <div class="card border-0 shadow-sm p-4 rounded-4 sticky-top" style="top: 100px;">
                        <h5 class="fw-bold mb-3">Order Summary</h5>
                        <ul class="list-group list-group-flush mb-3">
                            <?php foreach ($_SESSION['cart'] as $item): ?>
                                <li class="list-group-item d-flex justify-content-between align-items-center px-0 bg-transparent">
                                    <div>
                                        <h6 class="my-0"><?= e($item['name']) ?></h6>
                                        <small class="text-muted">Qty: <?= (int)$item['quantity'] ?></small>
                                    </div>
                                    <span class="fw-bold">$<?= number_format((float)$item['price'] * (int)$item['quantity'], 2) ?></span>
                                </li>
                            <?php endforeach; ?>
                            
                            <li class="list-group-item d-flex justify-content-between align-items-center px-0 bg-transparent">
                                <div>
                                    <h6 class="my-0">Delivery Fee</h6>
                                    <small class="text-muted">Standard Local Delivery</small>
                                </div>
                                <span class="fw-bold">$<?= number_format($delivery_fee, 2) ?></span>
                            </li>
                        </ul>
                        <hr>
                        <div class="d-flex justify-content-between fs-5 fw-bold mb-4">
                            <span>Total Amount</span>
                            <span class="text-primary">$<?= number_format($grand_total, 2) ?></span>
                        </div>
                        <button type="submit" class="btn btn-primary btn-lg rounded-pill w-100">Confirm & Place Order</button>
                    </div>
                </div>

            </div>
        </form>
    <?php endif; ?>
</main>

<?php include 'includes/footer.php'; ?>