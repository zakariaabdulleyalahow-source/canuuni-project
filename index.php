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

<main class="container cu-store-main flex-grow-1">
    <div class="row g-4">
        
        <!-- Sidebar: Responsive & Expandable Category Navigation -->
        <aside class="col-lg-3">
            <!-- Mobile Expandable Toggle Button (Hidden on Desktop) -->
            <div class="d-lg-none mb-3">
                <button class="btn category-toggle-btn w-100 d-flex justify-content-between align-items-center bg-white border shadow-sm p-3 rounded-4 fw-bold text-dark" 
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
                <div class="cu-category-panel sticky-sm-top" style="top: 100px;">
                    <div class="cu-panel-title d-none d-lg-flex"><span><i class="bi bi-grid me-2 text-primary"></i>Browse categories</span></div>
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
            <section class="cu-hero mb-4 mb-lg-5">
                <div class="position-relative" style="z-index:1;max-width:720px">
                    <span class="cu-hero-eyebrow"><i class="bi bi-stars"></i> Canuuni curated collection</span>
                    <h1 class="mt-3 mb-3">Everyday technology. <span class="text-white">Better value.</span></h1>
                    <p class="mb-4">Discover quality electronics and useful essentials selected to make everyday life easier. Find what you need at a price you’ll love.</p>
                    <a href="#canuuni-products" class="btn btn-light rounded-pill px-4 py-3">Explore products <i class="bi bi-arrow-down ms-2"></i></a>
                    <div class="cu-hero-note"><i class="bi bi-shield-check"></i> Quality picks <span class="opacity-50">•</span> Great value <span class="opacity-50">•</span> Friendly support</div>
                </div>
            </section>

            <div class="cu-section-heading">
                <div><h2>Explore our products</h2><p>Find the right product for your home, work and everyday needs.</p></div>
                <span class="cu-result-count"><span id="cu-visible-count"><?= count($products) ?></span> products</span>
            </div>
            
            <div class="cu-catalogue-toolbar mb-3">
                <div class="d-flex align-items-center gap-2 flex-wrap">
                    <span class="cu-toolbar-label"><i class="bi bi-sliders me-1"></i> Refine</span>
                    <label class="cu-filter-chip"><input class="form-check-input" type="checkbox" id="cu-stock-only"><span>In stock</span></label>
                    <label class="cu-filter-chip"><input class="form-check-input" type="checkbox" id="cu-saved-only"><span><i class="bi bi-heart me-1"></i>Saved <span id="cu-saved-count">0</span></span></label>
                    <div class="cu-price-filter">
                        <span>Price $</span>
                        <input type="number" id="cu-price-min" min="0" step="1" placeholder="Min" aria-label="Minimum price">
                        <span>to</span>
                        <input type="number" id="cu-price-max" min="0" step="1" placeholder="Max" aria-label="Maximum price">
                    </div>
                    <button type="button" id="cu-clear-filters" class="cu-clear-filters">Clear filters</button>
                </div>
                <label class="cu-sort-control"><span>Sort by</span>
                    <select class="form-select form-select-sm" id="cu-sort-products" aria-label="Sort products">
                        <option value="featured">Featured</option><option value="price-low">Price: low to high</option>
                        <option value="price-high">Price: high to low</option><option value="name">Name: A to Z</option>
                    </select>
                </label>
            </div>
            <div id="canuuni-products" class="row row-cols-2 row-cols-md-2 row-cols-xl-3 g-2 g-md-4 mobile-product-grid">
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
                        <div class="col" data-product-card data-id="<?= e($product['id']) ?>"
                             data-name="<?= e($product['product_name']) ?>" data-price="<?= $calculated_selling_price ?>"
                             data-position="<?= (int)array_search($product, $products, true) ?>"
                             data-stock="<?= $product['quantity'] > 0 ? '1' : '0' ?>"
                             data-image="<?= e($image_url ?? '') ?>" data-description="<?= e($product['description'] ?? '') ?>"
                             data-href="<?= e($details_url) ?>">
                            <div class="card h-100 border-0 shadow-sm product-card position-relative rounded-3 rounded-md-4 overflow-hidden">
                                
                                <div class="cu-card-actions">
                                    <button type="button" class="cu-icon-action cu-wishlist-btn" aria-label="Save item" aria-pressed="false"><i class="bi bi-heart"></i></button>
                                    <button type="button" class="cu-icon-action cu-quickview-btn" aria-label="Quick view"><i class="bi bi-eye"></i></button>
                                </div>
                                <?php if($advantage_text): ?>
                                    <span class="cu-product-promo badge bg-warning text-dark fw-bold" style="z-index: 2;"><?= $advantage_text ?></span>
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
                        <div class="cu-empty-state">
                            <i class="bi bi-info-circle display-5 mb-2 d-block"></i>
                            No products are available matching your query parameters for this branch location.
                        </div>
                    </div>
                <?php endif; ?>
            </div>
            <div id="cu-filter-empty" class="cu-empty-state mt-3" hidden>
                <i class="bi bi-funnel display-5 mb-2 d-block"></i><h3>No matching products</h3>
                <p class="text-muted mb-0">Change your filters to see more products.</p>
            </div>
        </section>
    </div>
</main>
<div class="modal fade" id="cuQuickViewModal" tabindex="-1" aria-labelledby="cuQuickViewTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg"><div class="modal-content cu-quickview-modal">
        <div class="modal-header border-0 pb-0"><span class="cu-modal-eyebrow">Quick product preview</span>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button></div>
        <div class="modal-body p-4 p-md-5"><div class="row g-4 align-items-center">
            <div class="col-md-5"><div class="cu-quickview-image"><img data-cu-qv-image src="" alt="" hidden><div data-cu-qv-placeholder><i class="bi bi-box-seam"></i></div></div></div>
            <div class="col-md-7"><span data-cu-qv-stock class="badge rounded-pill mb-3"></span>
                <h2 id="cuQuickViewTitle" data-cu-qv-name class="h3 fw-bold mb-2"></h2>
                <div data-cu-qv-price class="cu-quickview-price mb-3"></div><p data-cu-qv-description class="text-muted mb-4"></p>
                <div class="d-flex flex-wrap gap-2"><button type="button" data-cu-qv-add class="btn btn-primary rounded-pill px-4 btn-add-to-cart"><i class="bi bi-cart-plus me-2"></i>Add to Cart</button>
                    <a data-cu-qv-link href="#" class="btn btn-outline-primary rounded-pill px-4">View details</a></div>
            </div>
        </div></div>
    </div></div>
</div>

<?php include 'includes/footer.php'; ?>