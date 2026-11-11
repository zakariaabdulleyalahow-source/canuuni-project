<!-- includes/navbar.php -->
<header class="sticky-top bg-white shadow-sm">
    <!-- Top Bar -->
    <div class="bg-dark text-white py-1 fs-7">
        <div class="container d-flex justify-content-between align-items-center px-3">
            <span class="text-truncate" style="max-width: 65%;"><i class="bi bi-telephone-fill me-2"></i> Support: +252 617 665667</span>
            <span class="d-none d-sm-inline">Free shipping on orders over $50!</span>
        </div>
    </div>
    
    <!-- Main Navbar -->
    <nav class="navbar navbar-expand-lg navbar-light bg-white py-3">
        <div class="container">
            <a class="navbar-brand fw-bold text-primary fs-4 fs-lg-3 text-truncate" href="/">
                <i class="bi bi-bag-heart-fill me-2"></i>Canuuni E-Commerce
            </a>

            <?php 
            // Calculate current cart item count from session
            $initial_cart_count = 0;
            if (isset($_SESSION['cart']) && is_array($_SESSION['cart'])) {
                foreach ($_SESSION['cart'] as $item) {
                    $initial_cart_count += (int)($item['quantity'] ?? 1);
                }
            }
            ?>

            <!-- Mobile Action Icons (Cart & Hamburger) -->
            <div class="d-flex align-items-center gap-2 d-lg-none">
                <a class="nav-link position-relative px-2" href="cart" title="View Cart">
                    <i class="bi bi-cart3 fs-4 text-dark"></i>
                    <span id="cart-counter-badge-mobile" class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                        <?= $initial_cart_count ?>
                    </span>
                </a>
                <button class="navbar-toggler border-0 p-1" type="button" data-bs-toggle="offcanvas" data-bs-target="#mobileNavbarDrawer" aria-controls="mobileNavbarDrawer" aria-label="Toggle navigation">
                    <span class="navbar-toggler-icon"></span>
                </button>
            </div>

            <!-- Desktop Navbar Content -->
            <div class="collapse navbar-collapse" id="navbarNav">
                <!-- Search Bar -->
                <form class="d-flex mx-auto w-50 my-2 my-lg-0" method="GET" action="index">
                    <?php if(!empty($_GET['category'])): ?>
                        <input type="hidden" name="category" value="<?= e($_GET['category']) ?>">
                    <?php endif; ?>
                    <div class="input-group">
                        <input class="form-control" type="search" name="search" value="<?= e($_GET['search'] ?? '') ?>" placeholder="Search products by name or barcode..." aria-label="Search">
                        <button class="btn btn-primary" type="submit"><i class="bi bi-search"></i></button>
                    </div>
                </form>
                <!-- User Actions -->
                <div class="navbar-nav ms-auto align-items-center gap-3">
                    <!-- Cart Link pointing to cart.php view page -->
                    <a class="nav-link position-relative px-2" href="cart" title="View Cart">
                        <i class="bi bi-cart3 fs-4 text-dark"></i>
                        <span id="cart-counter-badge" class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                            <?= $initial_cart_count ?>
                        </span>
                    </a>

                    <?php if (isset($_SESSION['customer_id'])): ?>
                        <a class="btn btn-outline-primary btn-sm rounded-pill px-3" href="customer/dashboard">Dashboard</a>
                        <a class="btn btn-primary btn-sm rounded-pill px-3" href="customer/logout">Logout</a>
                    <?php else: ?>
                        <a class="btn btn-outline-primary btn-sm rounded-pill px-3" href="customer/login">Login</a>
                        <a class="btn btn-primary btn-sm rounded-pill px-3" href="customer/register">Register</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </nav>
</header>

<!-- Mobile Offcanvas Drawer Navbar (Mobile Only) -->
<div class="offcanvas offcanvas-end" tabindex="-1" id="mobileNavbarDrawer" aria-labelledby="mobileNavbarDrawerLabel">
    <div class="offcanvas-header border-bottom">
        <h5 class="offcanvas-title fw-bold text-primary" id="mobileNavbarDrawerLabel">
            <i class="bi bi-bag-heart-fill me-2"></i>Canuuni Menu
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
    </div>
    <div class="offcanvas-body d-flex flex-column gap-3">
        <!-- Mobile Search -->
        <form class="d-flex" method="GET" action="index">
            <?php if(!empty($_GET['category'])): ?>
                <input type="hidden" name="category" value="<?= e($_GET['category']) ?>">
            <?php endif; ?>
            <div class="input-group">
                <input class="form-control" type="search" name="search" value="<?= e($_GET['search'] ?? '') ?>" placeholder="Search products..." aria-label="Search">
                <button class="btn btn-primary" type="submit"><i class="bi bi-search"></i></button>
            </div>
        </form>

        <!-- Navigation Links & Categories in Drawer -->
        <div class="list-group list-group-flush border-0">
            <a href="index.php" class="list-group-item list-group-item-action border-0 rounded py-2 px-3 mb-1" onclick="bootstrap.Offcanvas.getInstance(document.getElementById('mobileNavbarDrawer')).hide();">
                <i class="bi bi-house-door me-2 text-primary"></i> Home
            </a>
            <a href="cart.php" class="list-group-item list-group-item-action border-0 rounded py-2 px-3 mb-1 d-flex justify-content-between align-items-center" onclick="bootstrap.Offcanvas.getInstance(document.getElementById('mobileNavbarDrawer')).hide();">
                <span><i class="bi bi-cart3 me-2 text-primary"></i> Shopping Cart</span>
                <span class="badge bg-danger rounded-pill"><?= $initial_cart_count ?></span>
            </a>
        </div>

        <hr class="text-muted my-1">

        <h6 class="fw-bold text-secondary text-uppercase fs-7 px-2 mb-0">Account</h6>
        <div class="d-flex flex-column gap-2 px-2">
            <?php if (isset($_SESSION['customer_id'])): ?>
                <a class="btn btn-outline-primary rounded-pill w-100" href="customer/dashboard" onclick="bootstrap.Offcanvas.getInstance(document.getElementById('mobileNavbarDrawer')).hide();">Dashboard</a>
                <a class="btn btn-primary rounded-pill w-100" href="customer/logout" onclick="bootstrap.Offcanvas.getInstance(document.getElementById('mobileNavbarDrawer')).hide();">Logout</a>
            <?php else: ?>
                <a class="btn btn-outline-primary rounded-pill w-100" href="customer/login" onclick="bootstrap.Offcanvas.getInstance(document.getElementById('mobileNavbarDrawer')).hide();">Login</a>
                <a class="btn btn-primary rounded-pill w-100" href="customer/register" onclick="bootstrap.Offcanvas.getInstance(document.getElementById('mobileNavbarDrawer')).hide();">Register</a>
            <?php endif; ?>
        </div>
    </div>
</div>