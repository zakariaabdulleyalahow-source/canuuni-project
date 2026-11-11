<?php
// cart.php
require_once 'config/db.php';
require_once 'includes/slug.php';

// Initialize cart array if not already set
if (!isset($_SESSION['cart']) || !is_array($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

include 'includes/header.php';
include 'includes/navbar.php';
?>

<main class="container my-5 flex-grow-1">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="fw-bold text-dark mb-0">
            <i class="bi bi-cart3 me-2"></i>Shopping Cart
        </h2>
        <?php if (!empty($_SESSION['cart'])): ?>
            <button id="btn-clear-cart" class="btn btn-outline-danger btn-sm rounded-pill px-3">
                <i class="bi bi-trash me-1"></i>Clear Cart
            </button>
        <?php endif; ?>
    </div>

    <div id="cart-wrapper">
        <?php if (empty($_SESSION['cart'])): ?>
            <!-- Empty Cart Alert -->
            <div class="card border-0 shadow-sm text-center p-5">
                <div class="card-body">
                    <i class="bi bi-cart-x display-1 text-muted opacity-50 mb-3"></i>
                    <h4 class="fw-bold">Your cart is currently empty</h4>
                    <p class="text-muted">Looks like you haven't added any items to your shopping cart yet.</p>
                    <a href="index" class="btn btn-primary rounded-pill px-4 mt-2">
                        <i class="bi bi-arrow-left me-2"></i>Continue Shopping
                    </a>
                </div>
            </div>
        <?php else: ?>
            <!-- Cart Table & Order Summary -->
            <div class="row g-4">
                
                <!-- Items Table -->
                <div class="col-lg-8">
                    <div class="card border-0 shadow-sm p-3">
                        <div class="table-responsive">
                            <table class="table align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th scope="col" style="min-width: 250px;">Product</th>
                                        <th scope="col" class="text-center">Price</th>
                                        <th scope="col" class="text-center" style="width: 130px;">Quantity</th>
                                        <th scope="col" class="text-end">Subtotal</th>
                                        <th scope="col" class="text-center">Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php 
                                    $cart_subtotal = 0;
                                    foreach ($_SESSION['cart'] as $id => $item): 
                                        $price = (float)($item['price'] ?? 0);
                                        $quantity = (int)($item['quantity'] ?? 1);
                                        $item_total = $price * $quantity;
                                        $cart_subtotal += $item_total;
                                        $img_url = !empty($item['image']) ? "https://db.canuuni.com/modules/productsimage/" . $item['image'] : null;
                                    ?>
                                        <tr id="cart-item-<?= $id ?>">
                                            <td>
                                                <div class="d-flex align-items-center gap-3">
                                                    <div class="rounded bg-light border p-1 d-flex align-items-center justify-content-center" style="width: 60px; height: 60px;">
                                                        <?php if ($img_url): ?>
                                                            <img src="<?= e($img_url) ?>" alt="<?= e($item['name'] ?? 'Product') ?>" class="img-fluid rounded" style="max-height: 100%; object-fit: contain;">
                                                        <?php else: ?>
                                                            <i class="bi bi-box-seam text-muted fs-4"></i>
                                                        <?php endif; ?>
                                                    </div>
                                                    <div>
                                                        <a href="<?= e(product_url($id, $item['name'])) ?>" class="fw-bold text-dark text-decoration-none">
                                                            <?= e($item['name'] ?? 'Product') ?>
                                                        </a>
                                                    </div>
                                                </div>
                                            </td>
                                            <td class="text-center fw-semibold">
                                                $<?= number_format($price, 2) ?>
                                            </td>
                                            <td class="text-center">
                                                <input type="number" 
                                                       class="form-control form-control-sm text-center cart-qty-input" 
                                                       data-id="<?= $id ?>" 
                                                       value="<?= $quantity ?>" 
                                                       min="1" 
                                                       max="99">
                                            </td>
                                            <td class="text-end fw-bold text-primary item-subtotal">
                                                $<?= number_format($item_total, 2) ?>
                                            </td>
                                            <td class="text-center">
                                                <button class="btn btn-link text-danger p-0 btn-remove-item" data-id="<?= $id ?>" title="Remove item">
                                                    <i class="bi bi-trash fs-5"></i>
                                                </button>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Summary & Checkout Sidebar -->
                <div class="col-lg-4">
                    <div class="card border-0 shadow-sm p-4 sticky-top" style="top: 100px;">
                        <h5 class="fw-bold text-dark mb-3">Order Summary</h5>
                        
                        <div class="d-flex justify-content-between mb-2">
                            <span class="text-muted">Subtotal</span>
                            <span id="summary-subtotal" class="fw-bold">$<?= number_format($cart_subtotal, 2) ?></span>
                        </div>
                        
                        <div class="d-flex justify-content-between mb-3">
                            <span class="text-muted">Shipping</span>
                            <span class="text-success fw-semibold">Calculated at Checkout</span>
                        </div>

                        <hr>

                        <div class="d-flex justify-content-between mb-4 fs-5">
                            <span class="fw-bold">Total</span>
                            <span id="summary-total" class="fw-bold text-primary">$<?= number_format($cart_subtotal, 2) ?></span>
                        </div>

                        <a href="checkout" class="btn btn-primary btn-lg rounded-pill w-100 mb-2">
                            Proceed to Checkout <i class="bi bi-arrow-right ms-2"></i>
                        </a>
                        
                        <a href="index" class="btn btn-outline-secondary rounded-pill w-100">
                            Continue Shopping
                        </a>
                    </div>
                </div>

            </div>
        <?php endif; ?>
    </div>
</main>

<script src="assets/js/cart.js"></script>
<?php include 'includes/footer.php'; ?>