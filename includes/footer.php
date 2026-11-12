<footer class="cu-footer pt-5 pb-3 mt-auto">
    <div class="container">
        <!-- Main Footer Links Grid -->
        <div class="row g-4 border-bottom cu-footer-divider pb-4">
            
            <!-- Brand Info & Contact -->
            <div class="col-lg-4 col-md-6">
                <div class="cu-footer-brand mb-3"><span class="cu-footer-mark"><i class="bi bi-bag-heart-fill"></i></span><span>Canuuni Electronic</span></div>
                <p class="text-secondary small mb-3">
                    Your one-stop destination for high-quality electronics and wholesale pricing.
                </p>
                <ul class="list-unstyled text-secondary small mb-0">
                    <li class="mb-2"><i class="bi bi-geo-alt me-2 text-primary"></i>Bakaro Market-Warta-nabada, Mugadishu City</li>
                    <li class="mb-2"><i class="bi bi-envelope me-2 text-primary"></i>support@canuuni.com</li>
                    <li><i class="bi bi-telephone me-2 text-primary"></i>+252 (617) 665667</li>
                </ul>
            </div>

            <!-- Quick Links -->
            <div class="col-lg-2 col-md-6 col-6">
                <h6 class="fw-bold text-white mb-3">Quick Links</h6>
                <ul class="list-unstyled small mb-0">
                    <li class="mb-2"><a href="index.php" class="text-secondary text-decoration-none">Home</a></li>
                    <li class="mb-2"><a href="products.php" class="text-secondary text-decoration-none">Products</a></li>
                    <li class="mb-2"><a href="cart.php" class="text-secondary text-decoration-none">Shopping Cart</a></li>
                    <li class="mb-2"><a href="customer/login.php" class="text-secondary text-decoration-none">My Accounts</a></li>
                </ul>
            </div>

            <!-- Customer Service -->
            <div class="col-lg-2 col-md-6 col-6">
                <h6 class="fw-bold text-white mb-3">Support</h6>
                <ul class="list-unstyled small mb-0">
                    <li class="mb-2"><a href="privacy-policy.php" class="text-secondary text-decoration-none">Privacy Policy</a></li>
                    <li class="mb-2"><a href="terms.php" class="text-secondary text-decoration-none">Terms & Conditions</a></li>
                    <li class="mb-2"><a href="faq.php" class="text-secondary text-decoration-none">FAQs</a></li>
                    <li class="mb-2"><a href="contact.php" class="text-secondary text-decoration-none">Contact Us</a></li>
                </ul>
            </div>

            <!-- Newsletter Signup & Socials -->
            <div class="col-lg-4 col-md-6">
                <h6 class="fw-bold text-white mb-3">Subscribe to Newsletter</h6>
                <p class="text-secondary small">Get the latest deals and exclusive wholesale offers straight to your inbox.</p>
                
                <form id="newsletter-form" action="#" method="POST" class="mb-3">
                    <div class="input-group input-group-sm">
                        <input type="email" class="form-control bg-secondary text-white border-0" placeholder="Enter your email" required>
                        <button class="btn btn-primary fw-semibold px-3" type="submit">Join</button>
                    </div>
                </form>

                <div class="d-flex gap-3 fs-5 pt-1">
                    <a href="#" class="text-secondary" title="Facebook"><i class="bi bi-facebook"></i></a>
                    <a href="#" class="text-secondary" title="X / Twitter"><i class="bi bi-twitter-x"></i></a>
                    <a href="#" class="text-secondary" title="Instagram"><i class="bi bi-instagram"></i></a>
                    <a href="#" class="text-secondary" title="LinkedIn"><i class="bi bi-linkedin"></i></a>
                </div>
            </div>

        </div>

        <!-- Bottom Footer (Copyright & Payment Icons) -->
        <div class="d-flex flex-column flex-sm-row justify-content-between align-items-center pt-3 small text-secondary">
            <p class="mb-2 mb-sm-0">&copy; <?= date('Y') ?> Canuuni Electronic Inc. All rights reserved.</p>
            
            <!-- Payment Badges -->
            <div class="d-flex gap-2 fs-5">
                <i class="bi bi-credit-card" title="Credit Card"></i>
                <i class="bi bi-paypal" title="PayPal"></i>
                <i class="bi bi-wallet2" title="Digital Wallet"></i>
            </div>
        </div>
    </div>
</footer>

<!-- External Libraries & Core Scripts -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="<?= htmlspecialchars($cu_asset_base . '/assets/js/cart.js', ENT_QUOTES, 'UTF-8') ?>"></script>
<script src="<?= htmlspecialchars($cu_asset_base . '/assets/js/catalogue-2026.js', ENT_QUOTES, 'UTF-8') ?>"></script>
</body>
</html>