<?php
// product-details.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once 'config/db.php';
require_once 'includes/slug.php';

$branch_id = $_SESSION['branch_id'] ?? 1;
$product_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($product_id <= 0) {
    header("Location: /index");
    exit;
}

// 1. Fetch Product details filtered by active branch and ID securely
$product_query = "
    SELECT 
        products.*,
        categories.category_name,
        suppliers.supplier_name,
        suppliers.phone AS supplier_phone,
        suppliers.address AS supplier_address,
        users.name AS user_name,
        users.branch_id
    FROM products
    LEFT JOIN categories ON products.category_id = categories.id
    LEFT JOIN suppliers ON products.sid = suppliers.id
    LEFT JOIN users ON products.uid = users.id
    WHERE products.id = ? AND users.branch_id = ?
    LIMIT 1
";

$stmt = $pdo->prepare($product_query);
$stmt->execute([$product_id, $branch_id]);
$product = $stmt->fetch();

if (!$product) {
    http_response_code(404);
    include '404.php';
    exit;
}

$advantage_text = !empty($product['advantage']) ? e($product['advantage']) : null;
$calculated_selling_price = (float)($product['unitprice'] ?? 0) + (float)($product['advantage'] ?? 0);
$stock_status = $product['quantity'] > 0 ? 'In Stock' : 'Out of Stock';
$stock_badge = $product['quantity'] > 0 ? 'bg-success-subtle text-success border border-success-subtle' : 'bg-danger-subtle text-danger border border-danger-subtle';

$image_url = !empty($product['productimage']) ? "https://db.canuuni.com/modules/productsimage/" . $product['productimage'] : null;

// 2. Fetch Related Items
$related_query = "
    SELECT 
        products.*,
        categories.category_name,
        users.branch_id
    FROM products
    LEFT JOIN categories ON products.category_id = categories.id
    LEFT JOIN users ON products.uid = users.id
    WHERE products.category_id = ? 
      AND products.id != ? 
      AND users.branch_id = ?
    ORDER BY products.id DESC
    LIMIT 4
";
$stmtRelated = $pdo->prepare($related_query);
$stmtRelated->execute([$product['category_id'], $product_id, $branch_id]);
$related_products = $stmtRelated->fetchAll();

include 'includes/header.php';
include 'includes/navbar.php';
?>

<style>
/* Legacy page styles retained only for compatibility; Canuuni 2026 styles take precedence below. */
:root {
    --primary-color: #0d6efd;
    --border-radius-lg: 1rem;
    --transition-speed: 0.2s ease-in-out;
}

body {
    background-color: #f8f9fa;
}

.product-details-card {
    border: none;
    border-radius: var(--border-radius-lg);
    box-shadow: 0 0.5rem 1.5rem rgba(0, 0, 0, 0.04);
    background: #ffffff;
}

.product-img-wrapper {
    background: #fdfdfe;
    border: 1px solid #edf2f7;
    border-radius: var(--border-radius-lg);
    position: relative;
    overflow: hidden;
    height: 420px;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 1.5rem;
}

.product-img-wrapper img {
    max-height: 100%;
    object-fit: contain;
    transition: transform var(--transition-speed);
}

.product-img-wrapper img:hover {
    transform: scale(1.03);
}

.price-tag {
    font-size: 2.25rem;
    font-weight: 800;
    color: var(--primary-color);
    letter-spacing: -0.02em;
}

.qty-input-group {
    max-width: 130px;
}

.qty-input-group input {
    border-radius: 50rem !important;
    font-weight: 600;
}

.action-btn {
    border-radius: 50rem;
    font-weight: 600;
    padding: 0.75rem 1.75rem;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
    transition: all var(--transition-speed);
}

.related-card {
    border: none;
    border-radius: var(--border-radius-lg);
    box-shadow: 0 0.25rem 1rem rgba(0, 0, 0, 0.03);
    transition: transform var(--transition-speed), box-shadow var(--transition-speed);
    background: #ffffff;
}

.related-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 0.75rem 1.5rem rgba(0, 0, 0, 0.08);
}

.related-img-container {
    height: 180px;
    background: #f8f9fa;
    border-top-left-radius: var(--border-radius-lg);
    border-top-right-radius: var(--border-radius-lg);
    overflow: hidden;
    padding: 1rem;
}

@media (max-width: 768px) {
    .product-img-wrapper {
        height: 280px !important;
    }
}
</style>

<main class="container cu-store-main flex-grow-1">
    
    <!-- Breadcrumb Navigation -->
    <nav aria-label="breadcrumb" class="mb-4">
        <ol class="breadcrumb mb-0 py-2 px-3 bg-white rounded-pill shadow-sm d-inline-flex">
            <li class="breadcrumb-item">
                <a href="/index" class="text-decoration-none text-muted">Home</a>
            </li>
            <li class="breadcrumb-item">
                <a href="/index?category=<?= urlencode($product['category_id']); ?>" class="text-decoration-none text-muted">
                    <?= e($product['category_name']); ?>
                </a>
            </li>
            <li class="breadcrumb-item active text-dark fw-semibold" aria-current="page"><?= e($product['product_name']); ?></li>
        </ol>
    </nav>

    <!-- Main Product Section -->
    <div class="cu-product-detail mb-5">
        <div class="row g-5 align-items-center">
            
            <!-- Product Image Display -->
            <div class="col-lg-5">
                <div class="cu-product-gallery">
                    <?php if($image_url): ?>
                        <img src="<?= e($image_url) ?>" 
                             alt="<?= e($product['product_name']) ?>" 
                             class="img-fluid"
                             onerror="this.onerror=null; this.parentElement.innerHTML='<i class=\'bi bi-image display-1 text-muted opacity-25\'></i>';">
                    <?php else: ?>
                        <div class="text-muted text-center">
                            <i class="bi bi-box-seam display-1 opacity-50"></i>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Product Meta & Actions -->
            <div class="col-lg-7">
                <div class="d-flex align-items-center flex-wrap gap-2 mb-3">
                    <span class="badge <?= $stock_badge ?> px-3 py-2 fw-semibold"><?= $stock_status ?></span>
                    <?php if($advantage_text): ?>
                        <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle fw-bold px-3 py-2">
                            <?= $advantage_text ?>
                        </span>
                    <?php endif; ?>
                    <span class="text-muted ms-auto small">Barcode: <strong class="text-dark"><?= e($product['barcode']) ?></strong></span>
                </div>

                <h1 class="cu-product-title mb-2"><?= e($product['product_name']) ?></h1>
                
                <p class="text-muted mb-4 small">
                    Category: <a href="/index?category=<?= urlencode($product['category_id']); ?>" class="text-decoration-none fw-semibold text-primary"><?= e($product['category_name']) ?></a>
                </p>

                <div class="mb-4 p-3 rounded-4 d-flex align-items-center bg-light">
                    <span class="cu-product-price">
                        $<?= number_format($calculated_selling_price, 2) ?>
                    </span>
                </div>

                <div class="mb-4">
                    <h6 class="fw-bold text-dark mb-2">Description</h6>
                    <p class="cu-product-description mb-0">
                        <?= !empty($product['description']) ? nl2br(e($product['description'])) : 'No detailed description available for this item.' ?>
                    </p>
                </div>

                <!-- Add to Cart / Pre-Order Control Area -->
                <div class="d-flex flex-wrap gap-3 align-items-center pt-4 border-top">
                    <?php if ($product['quantity'] > 0): ?>
                        <div class="cu-qty-stepper" aria-label="Choose quantity">
                            <button type="button" data-qty-step="-1" aria-label="Decrease quantity"><i class="bi bi-dash"></i></button>
                            <input type="number" id="product-qty" value="1" min="1" max="<?= (int)$product['quantity'] ?>" inputmode="numeric" aria-label="Quantity">
                            <button type="button" data-qty-step="1" aria-label="Increase quantity"><i class="bi bi-plus"></i></button>
                        </div>
                        <button class="btn btn-primary action-btn shadow-sm btn-add-to-cart flex-grow-1 flex-lg-grow-0"
                                data-id="<?= e($product['id']) ?>"
                                data-name="<?= e($product['product_name']) ?>"
                                data-price="<?= $calculated_selling_price ?>">
                            <i class="bi bi-cart-plus fs-5"></i> Add to Cart
                        </button>
                    <?php else: ?>
                        <button class="btn btn-warning action-btn btn-preorder text-dark shadow-sm flex-grow-1 flex-lg-grow-0"
                                data-id="<?= e($product['id']) ?>">
                            <i class="bi bi-clock-history fs-5"></i> Pre-Order Item
                        </button>
                    <?php endif; ?>
                    <?php endif; ?>
                    <button type="button" class="cu-save-detail" data-cu-detail-save data-id="<?= e($product['id']) ?>" aria-pressed="false">
                        <i class="bi bi-heart me-2"></i>Save for later
                    </button>
                </div>
                <div class="cu-detail-trust">
                    <div class="cu-detail-trust-item"><i class="bi bi-shield-check"></i><span><strong class="d-block text-dark">Quality selection</strong>Carefully listed products</span></div>
                    <div class="cu-detail-trust-item"><i class="bi bi-headset"></i><span><strong class="d-block text-dark">Customer support</strong>We're here to help</span></div>
                    <div class="cu-detail-trust-item"><i class="bi bi-lock"></i><span><strong class="d-block text-dark">Secure checkout</strong>Protected order process</span></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Related Products Section -->
    <?php if (!empty($related_products)): ?>
        <section class="mt-5 pt-3">
            <h3 class="fw-bold mb-4 text-dark">Related Products</h3>
            <div class="row row-cols-2 row-cols-md-3 row-cols-xl-4 g-3 g-md-4">
                <?php foreach ($related_products as $rel): ?>
                    <?php 
                    $rel_selling_price = (float)($rel['unitprice'] ?? 0) + (float)($rel['advantage'] ?? 0);
                    $rel_image_url = !empty($rel['productimage']) ? "https://db.canuuni.com/modules/productsimage/" . $rel['productimage'] : null;
                    ?>
                    <div class="col">
                        <div class="cu-related-card d-flex flex-column">
                            <div class="cu-related-image d-flex align-items-center justify-content-center">
                                <?php if($rel_image_url): ?>
                                    <img src="<?= e($rel_image_url) ?>" alt="<?= e($rel['product_name']) ?>" class="img-fluid h-100 object-fit-contain" onerror="this.onerror=null; this.parentElement.innerHTML='<i class=\'bi bi-image display-5 text-muted opacity-25\'></i>';">
                                <?php else: ?>
                                    <div class="text-muted text-center"><i class="bi bi-box-seam display-5 opacity-50"></i></div>
                                <?php endif; ?>
                            </div>
                            <div class="card-body d-flex flex-column p-3">
                                <h6 class="card-title fw-bold text-dark text-truncate mb-2">
                                    <a href="<?= e(product_url($rel['id'], $rel['product_name'])) ?>" class="text-decoration-none text-dark stretched-link">
                                        <?= e($rel['product_name']) ?>
                                    </a>
                                </h6>
                                <div class="mt-auto d-flex justify-content-between align-items-center pt-2">
                                    <span class="fs-6 fw-bold text-primary">$<?= number_format($rel_selling_price, 2) ?></span>
                                    <span class="btn btn-outline-primary btn-sm rounded-pill px-3 position-relative z-1">View</span>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </section>
    <?php endif; ?>

</main>

<!-- Page-specific script embedded directly inside product-details.php -->
<script>
document.addEventListener('DOMContentLoaded', function () {
    const qtyInput = document.getElementById('product-qty');

    // Ensure value doesn't go below 1 or exceed stock constraints locally
    if (qtyInput) {
        qtyInput.addEventListener('input', function () {
            let val = parseInt(this.value);
            let max = parseInt(this.getAttribute('max')) || 999;
            if (val < 1 || isNaN(val)) this.value = 1;
            if (val > max) this.value = max;
        });
    }
});
</script>

<?php include 'includes/footer.php'; ?>