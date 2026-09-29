</main>
<!-- ===================== FLOATING CONTACT BUTTONS ===================== -->
<div class="float-contact-group">
    <!-- WhatsApp -->
    <?php $phone = getSetting('shop_phone') ?: '+251929346248'; $wa_phone = preg_replace('/[^0-9]/', '', $phone); ?>
    <a href="https://wa.me/<?php echo htmlspecialchars($wa_phone); ?>" target="_blank" class="float-btn float-whatsapp" title="WhatsApp">
        <i class="fab fa-whatsapp"></i>
        <span class="float-btn-label">WhatsApp</span>
    </a>
    <!-- Telegram -->
    <a href="https://t.me/<?php echo htmlspecialchars(getSetting('telegram') ?: 'isadagishop'); ?>" target="_blank" class="float-btn float-telegram" title="Telegram">
        <i class="fab fa-telegram-plane"></i>
        <span class="float-btn-label">Telegram</span>
    </a>
</div>

<!-- ===================== FOOTER ===================== -->
<footer id="footer">
    <div class="footer-top">
        <div class="container">
            <div class="row g-4">

                <!-- Brand Info -->
                <div class="col-lg-3 col-md-6">
                    <div class="footer-brand">
                        <h4><i class="fas fa-mobile-alt me-2"></i>Isa &amp; Dagi</h4>
                        <p>Your trusted mobile destination in Addis Ababa. We bring the latest smartphones and accessories to Merkato's Samson Building.</p>
                        <div class="footer-social">
                            <?php
                            $fb  = getSetting('facebook');
                            $ig  = getSetting('instagram');
                            $tg  = getSetting('telegram');
                            $yt  = getSetting('youtube');
                            $tt  = getSetting('tiktok');
                            $tw  = getSetting('twitter');
                            if ($fb): ?><a href="https://facebook.com/<?php echo htmlspecialchars($fb); ?>" target="_blank" title="Facebook"><i class="fab fa-facebook-f"></i></a><?php endif; ?>
                            <?php if ($ig): ?><a href="https://instagram.com/<?php echo htmlspecialchars($ig); ?>" target="_blank" title="Instagram"><i class="fab fa-instagram"></i></a><?php endif; ?>
                            <?php if ($tg): ?><a href="https://t.me/<?php echo htmlspecialchars($tg); ?>" target="_blank" title="Telegram"><i class="fab fa-telegram-plane"></i></a><?php endif; ?>
                            <?php if ($yt): ?><a href="https://youtube.com/@<?php echo htmlspecialchars($yt); ?>" target="_blank" title="YouTube"><i class="fab fa-youtube"></i></a><?php endif; ?>
                            <?php if ($tt): ?><a href="https://tiktok.com/@<?php echo htmlspecialchars($tt); ?>" target="_blank" title="TikTok"><i class="fab fa-tiktok"></i></a><?php endif; ?>
                            <?php if ($tw): ?><a href="https://x.com/<?php echo htmlspecialchars($tw); ?>" target="_blank" title="X (Twitter)"><i class="fab fa-x-twitter"></i></a><?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Quick Links -->
                <div class="col-lg-2 col-md-6">
                    <h5 class="footer-heading">Quick Links</h5>
                    <ul class="footer-links">
                        <li><a href="index.php"><i class="fas fa-chevron-right me-1"></i>Home</a></li>
                        <li><a href="search.php"><i class="fas fa-chevron-right me-1"></i>All Products</a></li>
                        <li><a href="search.php?filter=onsale"><i class="fas fa-chevron-right me-1"></i>On Sale</a></li>
                        <li><a href="cart.php"><i class="fas fa-chevron-right me-1"></i>Cart</a></li>
                        <li><a href="blog.php"><i class="fas fa-chevron-right me-1"></i>Blog</a></li>
                        <li><a href="about.php"><i class="fas fa-chevron-right me-1"></i>About Us</a></li>
                        <li><a href="support.php"><i class="fas fa-chevron-right me-1"></i>Support</a></li>
                    </ul>
                </div>

                <!-- Brands -->
                <div class="col-lg-2 col-md-6">
                    <h5 class="footer-heading">Brands</h5>
                    <ul class="footer-links">
                        <li><a href="search.php?category=Samsung"><i class="fas fa-chevron-right me-1"></i>Samsung</a></li>
                        <li><a href="search.php?category=Apple"><i class="fas fa-chevron-right me-1"></i>Apple</a></li>
                        <li><a href="search.php?category=Redmi"><i class="fas fa-chevron-right me-1"></i>Redmi</a></li>
                        <li><a href="search.php?category=Tecno"><i class="fas fa-chevron-right me-1"></i>Tecno</a></li>
                        <li><a href="search.php?category=Infinix"><i class="fas fa-chevron-right me-1"></i>Infinix</a></li>
                        <li><a href="search.php?category=Huawei"><i class="fas fa-chevron-right me-1"></i>Huawei</a></li>
                    </ul>
                </div>

                <!-- Customer Care -->
                <div class="col-lg-2 col-md-6">
                    <h5 class="footer-heading">Customer Care</h5>
                    <ul class="footer-links">
                        <li><a href="orders.php"><i class="fas fa-chevron-right me-1"></i>My Orders</a></li>
                        <li><a href="support.php#contact"><i class="fas fa-chevron-right me-1"></i>Contact Us</a></li>
                        <li><a href="support.php#faq"><i class="fas fa-chevron-right me-1"></i>FAQ</a></li>
                        <li><a href="support.php"><i class="fas fa-chevron-right me-1"></i>Warranty Policy</a></li>
                        <li><a href="support.php"><i class="fas fa-chevron-right me-1"></i>Returns</a></li>
                    </ul>
                </div>

                <!-- Newsletter -->
                <div class="col-lg-3 col-md-6">
                    <h5 class="footer-heading">Get Exclusive Deals</h5>
                    <p class="footer-text">Subscribe for price alerts on new iPhones and Samsungs.</p>
                    <form class="footer-newsletter" id="newsletterForm" onsubmit="subscribeNewsletter(event)">
                        <input type="email" id="newsletterEmail" placeholder="Your email address" required>
                        <button type="submit">JOIN</button>
                    </form>
                    <div id="newsletterMsg" style="font-size:12px;margin-top:6px;display:none;"></div>
                    <div class="footer-contact mt-3">
                        <p><i class="fas fa-phone me-2"></i><?php echo htmlspecialchars(getSetting('shop_phone') ?: '+251 929 346 248'); ?></p>
                        <p><i class="fas fa-envelope me-2"></i><?php echo htmlspecialchars(getSetting('shop_email') ?: 'isamohammedabere@gmail.com'); ?></p>
                        <p><i class="fas fa-map-marker-alt me-2"></i><?php echo htmlspecialchars(getSetting('shop_address') ?: 'Merkato, Samson Building'); ?></p>
                    </div>
                </div>

            </div>
        </div>
    </div>

    <!-- Payment Methods Bar -->
    <div class="footer-payment">
        <div class="container">
            <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
                <span class="footer-text">We Accept:</span>
                <div class="d-flex gap-3 flex-wrap align-items-center">
                    <span class="payment-badge"><i class="fas fa-money-bill-wave me-1"></i>Cash on Delivery</span>
                    <span class="payment-badge"><i class="fas fa-university me-1"></i>Bank Transfer</span>
                    <span class="payment-badge"><i class="fas fa-mobile-alt me-1"></i>Telebirr</span>
                    <span class="payment-badge"><i class="fas fa-credit-card me-1"></i>CBE Birr</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Copyright -->
    <div class="footer-bottom">
        <div class="container">
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
                <p class="m-0">&copy; 2026 <strong>Isa &amp; Dagi Mobile Shop</strong>. All Rights Reserved.</p>
                <div class="d-flex gap-3 align-items-center flex-wrap">
                    <a href="terms.php" style="color:rgba(255,255,255,0.5);font-size:12px;" class="footer-legal-link">Terms of Service</a>
                    <span style="color:rgba(255,255,255,0.2);">|</span>
                    <a href="privacy.php" style="color:rgba(255,255,255,0.5);font-size:12px;" class="footer-legal-link">Privacy Policy</a>
                    <span style="color:rgba(255,255,255,0.2);">|</span>
                    <p class="m-0" style="font-size:12px;">Designed &amp; Developed by <a href="#" style="color:var(--secondary);">Isa &amp; Dagi</a></p>
                </div>
            </div>
        </div>
    </div>
</footer>

<!-- ===================== SCRIPTS ===================== -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.0/jquery.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/OwlCarousel2/2.3.4/owl.carousel.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery.isotope/3.0.6/isotope.pkgd.min.js"></script>
<script src="index.js?v=<?php echo filemtime(__DIR__ . '/index.js'); ?>"></script>
<script>
function subscribeNewsletter(e) {
    e.preventDefault();
    var email = document.getElementById('newsletterEmail').value.trim();
    var msg   = document.getElementById('newsletterMsg');
    if (!email) return;
    fetch('newsletter.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: 'email=' + encodeURIComponent(email)
    })
    .then(r => r.json())
    .then(data => {
        msg.style.display = 'block';
        msg.style.color   = data.success ? '#90ee90' : '#ffaaaa';
        msg.textContent   = data.message;
        if (data.success) document.getElementById('newsletterEmail').value = '';
    })
    .catch(() => {
        msg.style.display = 'block';
        msg.style.color   = '#ffaaaa';
        msg.textContent   = 'Something went wrong. Please try again.';
    });
}
</script>
</body>
</html>
