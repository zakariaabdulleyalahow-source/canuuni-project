<?php
// 404.php
require_once 'config/db.php';
http_response_code(404);
include 'includes/header.php';
include 'includes/navbar.php';
?>
<main class="container my-5 flex-grow-1 d-flex align-items-center justify-content-center">
    <div class="text-center">
        <i class="bi bi-signpost-split display-1 text-primary mb-3"></i>
        <h1 class="fw-bold display-5">404</h1>
        <p class="text-muted mb-4">Sorry, we couldn't find the page you're looking for.</p>
        <a href="index" class="btn btn-primary rounded-pill px-4">
            <i class="bi bi-house-door me-1"></i> Back to Home
        </a>
    </div>
</main>
<?php include 'includes/footer.php'; ?>
