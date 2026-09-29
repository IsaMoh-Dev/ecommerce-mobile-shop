<?php
$page_title = 'About Us';
ob_start();
include 'header.php';

// Pull real stats from DB
$real_users    = (int)$db->con->query("SELECT COUNT(*) as c FROM user WHERE role='customer'")->fetch_assoc()['c'];
$real_products = (int)$db->con->query("SELECT COUNT(*) as c FROM product")->fetch_assoc()['c'];
$real_acc      = (int)$db->con->query("SELECT COUNT(*) as c FROM accessories")->fetch_assoc()['c'];
$total_stock   = $real_products + $real_acc;
// Show at least the real number, or a marketing minimum
$display_customers = max($real_users, 500);
$display_products  = max($total_stock, 50);
?>

<section class="py-5" style="background:#f8f9fa; min-height:80vh;">
<div class="container">

    <!-- Hero -->
    <div class="row align-items-center g-5 mb-5">
        <div class="col-lg-6">
            <p class="text-uppercase fw-bold mb-2" style="color:var(--secondary); font-size:13px; letter-spacing:2px;">
                <i class="fas fa-store me-1"></i> Our Story
            </p>
            <h2 style="font-family:'Rubik',sans-serif; font-weight:700; color:var(--primary); font-size:36px; line-height:1.3;">
                Addis Ababa's Trusted<br>Mobile Destination
            </h2>
            <p class="text-muted mt-3" style="font-size:15px; line-height:1.8;">
                Isa &amp; Dagi Mobile Shop was founded with one goal — to bring the latest smartphones and accessories to customers in Addis Ababa at honest prices. Located in the heart of Merkato's Samson Building, we've been serving thousands of happy customers with genuine products and reliable after-sales support.
            </p>
            <p class="text-muted" style="font-size:15px; line-height:1.8;">
                Whether you're looking for the newest iPhone, a budget-friendly Redmi, or a Samsung flagship, we have it in stock. We believe everyone deserves a great phone — and great service to go with it.
            </p>
            <div class="d-flex gap-3 mt-4 flex-wrap">
                <a href="search.php" class="btn btn-primary-custom px-4">
                    <i class="fas fa-shopping-bag me-2"></i>Shop Now
                </a>
                <a href="support.php#contact" class="btn btn-outline-secondary px-4">
                    <i class="fas fa-envelope me-2"></i>Contact Us
                </a>
            </div>
        </div>
        <div class="col-lg-6">
            <div class="row g-3">
                <div class="col-6">
                    <div class="about-stat-card text-center p-4">
                        <div class="about-stat-icon mb-2"><i class="fas fa-users"></i></div>
                        <h3 style="font-family:'Rubik',sans-serif; font-weight:700; color:var(--primary);"><?php echo number_format($display_customers); ?>+</h3>
                        <p class="text-muted mb-0" style="font-size:13px;">Happy Customers</p>
                    </div>
                </div>
                <div class="col-6">
                    <div class="about-stat-card text-center p-4" style="margin-top:24px;">
                        <div class="about-stat-icon mb-2" style="background:rgba(0,165,196,0.1); color:var(--secondary);"><i class="fas fa-box"></i></div>
                        <h3 style="font-family:'Rubik',sans-serif; font-weight:700; color:var(--primary);"><?php echo $display_products; ?>+</h3>
                        <p class="text-muted mb-0" style="font-size:13px;">Products in Stock</p>
                    </div>
                </div>
                <div class="col-6">
                    <div class="about-stat-card text-center p-4" style="margin-top:-12px;">
                        <div class="about-stat-icon mb-2" style="background:rgba(40,167,69,0.1); color:#28a745;"><i class="fas fa-star"></i></div>
                        <h3 style="font-family:'Rubik',sans-serif; font-weight:700; color:var(--primary);">4.8★</h3>
                        <p class="text-muted mb-0" style="font-size:13px;">Average Rating</p>
                    </div>
                </div>
                <div class="col-6">
                    <div class="about-stat-card text-center p-4">
                        <div class="about-stat-icon mb-2" style="background:rgba(255,193,7,0.1); color:#ffc107;"><i class="fas fa-calendar-check"></i></div>
                        <h3 style="font-family:'Rubik',sans-serif; font-weight:700; color:var(--primary);">5+ Yrs</h3>
                        <p class="text-muted mb-0" style="font-size:13px;">In Business</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Why Choose Us -->
    <div class="mb-5">
        <h4 class="section-heading text-center" style="text-align:center !important;">Why Choose Us</h4>
        <p class="text-center text-muted mb-4" style="font-size:14px;">We go beyond just selling phones — we build lasting relationships with our customers.</p>
        <div class="row g-4">
            <div class="col-md-3 col-sm-6">
                <div class="about-feature-card text-center p-4">
                    <div class="about-feature-icon mb-3"><i class="fas fa-certificate"></i></div>
                    <h6 style="font-family:'Rubik',sans-serif; font-weight:600;">100% Genuine</h6>
                    <p class="text-muted mb-0" style="font-size:13px;">All products are original and come with official warranty.</p>
                </div>
            </div>
            <div class="col-md-3 col-sm-6">
                <div class="about-feature-card text-center p-4">
                    <div class="about-feature-icon mb-3" style="background:rgba(0,165,196,0.1); color:var(--secondary);"><i class="fas fa-truck"></i></div>
                    <h6 style="font-family:'Rubik',sans-serif; font-weight:600;">Fast Delivery</h6>
                    <p class="text-muted mb-0" style="font-size:13px;">Same-day delivery within Addis Ababa on orders before 2 PM.</p>
                </div>
            </div>
            <div class="col-md-3 col-sm-6">
                <div class="about-feature-card text-center p-4">
                    <div class="about-feature-icon mb-3" style="background:rgba(40,167,69,0.1); color:#28a745;"><i class="fas fa-headset"></i></div>
                    <h6 style="font-family:'Rubik',sans-serif; font-weight:600;">24/7 Support</h6>
                    <p class="text-muted mb-0" style="font-size:13px;">Our team is always ready to help you via phone, Telegram, or in-store.</p>
                </div>
            </div>
            <div class="col-md-3 col-sm-6">
                <div class="about-feature-card text-center p-4">
                    <div class="about-feature-icon mb-3" style="background:rgba(220,53,69,0.1); color:#dc3545;"><i class="fas fa-retweet"></i></div>
                    <h6 style="font-family:'Rubik',sans-serif; font-weight:600;">Easy Returns</h6>
                    <p class="text-muted mb-0" style="font-size:13px;">10-day hassle-free return policy on all products.</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Brands We Carry -->
    <div class="mb-5">
        <h4 class="section-heading">Brands We Carry</h4>
        <div class="d-flex flex-wrap gap-3 align-items-center">
            <a href="search.php?category=Samsung" class="about-brand-badge">
                <i class="fas fa-mobile-alt me-2"></i>Samsung
            </a>
            <a href="search.php?category=Apple" class="about-brand-badge">
                <i class="fab fa-apple me-2"></i>Apple
            </a>
            <a href="search.php?category=Redmi" class="about-brand-badge">
                <i class="fas fa-mobile me-2"></i>Redmi
            </a>
            <a href="search.php?category=Huawei" class="about-brand-badge">
                <i class="fas fa-mobile-alt me-2"></i>Huawei
            </a>
            <a href="search.php?category=Tecno" class="about-brand-badge">
                <i class="fas fa-mobile me-2"></i>Tecno
            </a>
            <a href="search.php?category=Infinix" class="about-brand-badge">
                <i class="fas fa-mobile me-2"></i>Infinix
            </a>
        </div>
    </div>

    <!-- Visit Us -->
    <div class="about-visit-card p-4 p-md-5 rounded-3">
        <div class="row align-items-center g-4">
            <div class="col-md-8">
                <h4 style="font-family:'Rubik',sans-serif; font-weight:700; color:#fff; margin-bottom:10px;">
                    <i class="fas fa-map-marker-alt me-2" style="color:var(--accent);"></i>Visit Our Store
                </h4>
                <p style="color:rgba(255,255,255,0.8); margin-bottom:6px; font-size:14px;">
                    <i class="fas fa-building me-2" style="color:var(--accent);"></i>
                    Merkato, Samson Building — Addis Ababa, Ethiopia
                </p>
                <p style="color:rgba(255,255,255,0.8); margin-bottom:6px; font-size:14px;">
                    <i class="fas fa-clock me-2" style="color:var(--accent);"></i>
                    Mon – Sat: 8:00 AM – 8:00 PM &nbsp;|&nbsp; Sun: 10:00 AM – 6:00 PM
                </p>
                <p style="color:rgba(255,255,255,0.8); margin-bottom:0; font-size:14px;">
                    <i class="fas fa-phone me-2" style="color:var(--accent);"></i>
                    +251 929 346 248
                </p>
            </div>
            <div class="col-md-4 text-md-end">
                <a href="support.php#contact" class="btn px-4 py-2 fw-bold" style="background:var(--accent); color:var(--primary); border-radius:8px;">
                    <i class="fas fa-envelope me-2"></i>Send a Message
                </a>
            </div>
        </div>
    </div>

</div>
</section>

<?php include 'footer.php'; ?>
