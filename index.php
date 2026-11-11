<?php
// index.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once 'config/db.php';
require_once 'includes/slug.php';

$branch_id = $_SESSION['branch_id'] ?? 1;

// 1. Fetch Categories for the current active branch
$stmtCats = $pdo->prepare("
    SELECT DISTINCT c.id, c.category_name 
    FROM categories c
    INNER JOIN users u ON c.uid = u.id
    WHERE u.branch_id = ?
    ORDER BY c.category_name ASC
");
$stmtCats->execute([$branch_id]);
$categories = $stmtCats->fetchAll();

// 2. Handle Filter and Search parameters securely
$category_filter = isset($_GET['category']) ? (int)$_GET['category'] : 0;
$search_filter   = isset($_GET['search']) ? trim($_GET['search']) : '';

$whereConditions = ["users.branch_id = ?"];
$params = [$branch_id];

if ($category_filter > 0) {
    $whereConditions[] = "products.category_id = ?";
    $params[] = $category_filter;
}

if (!empty($search_filter)) {
    $whereConditions[] = "(products.product_name LIKE ? OR products.barcode LIKE ?)";
    $params[] = "%$search_filter%";
    $params[] = "%$search_filter%";
}

$whereClause = implode(" AND ", $whereConditions);

// 3. Fetch Products for catalog display
$product_query = "
    SELECT 
        products.*,
        categories.category_name,
        suppliers.supplier_name,
        suppliers.phone,
        users.name AS user_name,
        users.branch_id
    FROM products
    LEFT JOIN categories ON products.category_id = categories.id
    LEFT JOIN suppliers ON products.sid = suppliers.id
    LEFT JOIN users ON products.uid = users.id
    WHERE $whereClause
    ORDER BY RAND()
";

$stmtProds = $pdo->prepare($product_query);
$stmtProds->execute($params);
$products = $stmtProds->fetchAll();

// Find active category name for mobile toggle display title
$active_category_name = "All Products";
if ($category_filter > 0 && !empty($categories)) {
    foreach ($categories as $cat) {
        if ((int)$cat['id'] === $category_filter) {
            $active_category_name = $cat['category_name'];
            break;
        }
    }
}

include 'includes/header.php';
include 'includes/navbar.php';
?>

<main class="container my-4 my-md-5 flex-grow-1">
    <div class="row g-4">
        
        <!-- Sidebar: Responsive & Expandable Category Navigation -->
        <aside class="col-lg-3">
            <!-- Mobile Expandable Toggle Button (Hidden on Desktop) -->
            <div class="d-lg-none mb-3">
                <button class="btn.category-toggle-btn w-100 d-flex justify-content-between align-items-center bg-white border shadow-sm p-3 rounded-4 fw-bold text-dark" 
                        type="button" 
                        data-bs-toggle="collapse" 
                        data-bs-target="#mobileCategoryCollapse" 
                        aria-expanded="false" 
                        aria-controls="mobileCategoryCollapse">
                    <span>
                        <i class="bi bi-filter-left text-primary me-2"></i> 
                        Categories: <span class="text-muted fw-normal"><?= e($active_category_name) ?></span>
                    </span>
                    <i class="bi bi-chevron-down fs-7"></i>
                </button>
            </div>

            <!-- Category Container (Collapsible on Mobile, Permanent Sidebar on Desktop) -->
            <div class="collapse d-lg-block" id="mobileCategoryCollapse">
                <div class="card border-0 shadow-sm p-3 sticky-sm-top rounded-4" style="top: 100px;">
                    <h5 class="fw-bold mb-3 text-secondary d-none d-lg-block"><i class="bi bi-filter-left me-2"></i>Categories</h5>
                    <div class="list-group list-group-flush category-list-group">
                        <a href="index<?= !empty($search_filter) ? '?search=' . urlencode($search_filter) : '' ?>" 
                           class="list-group-item list-group-item-action <?= empty($category_filter) ? 'active' : '' ?> rounded mb-1 border-0">
                            All Products
                        </a>
                        <?php if (!empty($categories)): ?>
                            <?php foreach ($categories as $cat): ?>
                                <?php 
                                $isActive = ($category_filter === (int)$cat['id']) ? 'active' : '';
                                $url = 'index?category=' . urlencode($cat['id']);
                                if (!empty($search_filter)) {
                                    $url .= '&search=' . urlencode($search_filter);
                                }
                                ?>
                                <a href="<?= $url; ?>" class="list-group-item list-group-item-action <?= $isActive; ?> rounded mb-1 border-0 text-truncate">
                                    <?= e($cat['category_name']); ?>
                                </a>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <p class="text-muted p-2 fs-7 mb-0">No categories found for this branch.</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </aside>

        <!-- Main Product Grid Section -->
        <section class="col-lg-9">
            <div class="p-3 p-md-5 mb-4 rounded-4 text-body-emphasis bg-white shadow-sm border-start border-primary border-5">
                <div class="col-lg-8 px-0">
                    <h1 class="h3 h-lg-display-6 fw-bold">Exclusive Deals & Quality Products</h1>
                    <p class="lead fs-6 fs-lg-lead my-2 my-lg-3 text-muted">Explore top-tier items curated specifically for your workflow with competitive wholesale values.</p>
                </div>
            </div>

            <div class="d-flex justify-content-between align-items-center mb-3 mb-md-4">
                <h4 class="fw-bold text-dark mb-0 fs-5 fs-lg-4">Products</h4>
            </div>
            
            <!-- Product Grid: 2 columns on mobile -->
            <div class="row row-cols-2 row-cols-md-2 row-cols-xl-3 g-2 g-md-4 mobile-product-grid">
                <?php if (!empty($products)): ?>
                    <?php foreach ($products as $product): ?>
                        <?php 
                        $advantage_text = !empty($product['advantage']) ? e($product['advantage']) : null;
                        $stock_status = $product['quantity'] > 0 ? 'In Stock' : 'Out of Stock';
                        $stock_badge = $product['quantity'] > 0 ? 'bg-success-subtle text-success' : 'bg-danger-subtle text-danger';
                        
                        $calculated_selling_price = (float)($product['unitprice'] ?? 0) + (float)($product['advantage'] ?? 0);
                        $image_url = !empty($product['productimage']) ? "https://db.Maaldhowr.com/modules/productsimage/" . $product['productimage'] : null;
                        $details_url = product_url($product['id'], $product['product_name']);
                        ?>
                        <div class="col">
                            <div class="card h-100 border-0 shadow-sm product-card position-relative rounded-3 rounded-md-4 overflow-hidden">
                                
                                <?php if($advantage_text): ?>
                                    <span class="position-absolute top-0 start-0 m-2 m-md-3 badge bg-warning text-dark fw-bold shadow-sm" style="z-index: 2;">
                                        <?= $advantage_text ?>
                                    </span>
                                <?php endif; ?>

                                <a href="<?= $details_url ?>" class="text-decoration-none">
                                    <div class="product-img-container d-flex align-items-center justify-content-center bg-light">
                                        <?php if($image_url): ?>
                                            <img src="<?= e($image_url) ?>" alt="<?= e($product['product_name']) ?>" class="product-img img-fluid mh-100" onerror="this.onerror=null; this.parentElement.innerHTML='<i class=\'bi bi-image display-4 opacity-20\'></i>';">
                                        <?php else: ?>
                                            <div class="text-muted text-center">
                                                <i class="bi bi-box-seam display-5 display-md-4 opacity-50"></i>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </a>

                                <div class="card-body d-flex flex-column p-3 p-md-4">
                                    <div class="d-flex justify-content-between align-items-center mb-1 mb-md-2">
                                        <span class="badge <?= $stock_badge ?> border-0"><?= $stock_status ?></span>
                                        <small class="text-muted fs-7 d-none d-sm-inline">Code: <?= e($product['barcode']) ?></small>
                                    </div>
                                    
                                    <h5 class="card-title fw-bold text-dark text-truncate mb-1" title="<?= e($product['product_name']) ?>">
                                        <a href="<?= $details_url ?>" class="text-decoration-none text-dark">
                                            <?= e($product['product_name']) ?>
                                        </a>
                                    </h5>
                                    
                                    <p class="card-text text-muted small flex-grow-1 text-truncate-2 mb-2 mb-md-3">
                                        <?= e($product['description'] ?? '') ?>
                                    </p>

                                    <div class="pt-2 border-top mt-auto">
                                        <div class="d-flex justify-content-between align-items-baseline mb-2 mb-md-3">
                                            <div>
                                                <span class="fs-5 fs-md-4 fw-bold text-primary">
                                                    $<?= number_format($calculated_selling_price, 2) ?>
                                                </span>
                                            </div>
                                        </div>
                                        <?php if ($product['quantity'] > 0): ?>
                                            <button 
                                                class="btn btn-primary w-100 rounded-pill py-2 btn-add-to-cart"
                                                data-id="<?= e($product['id']) ?>"
                                                data-name="<?= e($product['product_name']) ?>"
                                                data-price="<?= $calculated_selling_price ?>">
                                                <i class="bi bi-cart-plus me-1 me-md-2"></i>Add to Cart
                                            </button>
                                        <?php else: ?>
                                            <button 
                                                class="btn btn-warning w-100 rounded-pill py-2 btn-preorder"
                                                data-id="<?= e($product['id']) ?>">
                                                <i class="bi bi-clock-history me-1 me-md-2"></i>Pre-Order
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="col-12">
                        <div class="alert alert-info rounded-4 border-0 shadow-sm p-4 text-center">
                            <i class="bi bi-info-circle display-5 mb-2 d-block"></i>
                            No products are available matching your query parameters for this branch location.
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </section>

    </div>
</main>

<script src="assets/js/cart.js"></script>
<?php include 'includes/footer.php'; ?>