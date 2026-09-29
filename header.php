<?php
ob_start();
require_once 'functions.php';

// Cart count
$cart_count = $auth->isLoggedIn() ? $Cart->countCart($current_user_id) : 0;

// Search query
$search_query = isset($_GET['q']) ? htmlspecialchars(trim($_GET['q'])) : '';

// Load all brands from DB dynamically
$nav_brands_raw = $db->con->query("SELECT brand_name FROM brand ORDER BY brand_name ASC");
$nav_brands = [];
while ($nb = $nav_brands_raw->fetch_assoc()) {
    $nav_brands[] = $nb['brand_name'];
}

// Brand icon map — add more as needed
$brand_icons = [
    'Samsung'  => '<img src="https://upload.wikimedia.org/wikipedia/commons/thumb/2/24/Samsung_Logo.svg/32px-Samsung_Logo.svg.png" alt="" style="height:14px;" class="me-2">',
    'Apple'    => '<i class="fab fa-apple me-2"></i>',
    'Redmi'    => '<i class="fas fa-mobile me-2"></i>',
    'Huawei'   => '<i class="fas fa-mobile-alt me-2"></i>',
    'Tecno'    => '<i class="fas fa-mobile me-2"></i>',
    'Infinix'  => '<i class="fas fa-mobile me-2"></i>',
    'Nokia'    => '<i class="fas fa-mobile-alt me-2"></i>',
    'Oppo'     => '<i class="fas fa-mobile me-2"></i>',
    'Vivo'     => '<i class="fas fa-mobile me-2"></i>',
    'OnePlus'  => '<i class="fas fa-mobile-alt me-2"></i>',
];
$default_brand_icon = '<i class="fas fa-mobile me-2"></i>';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? htmlspecialchars($page_title) . ' | ' : ''; ?>Isa &amp; Dagi Mobile Shop | Merkato, Addis Ababa, Ethiopia</title>

    <!-- SEO Meta Tags -->
    <?php
    $meta_desc = isset($page_meta_description)
        ? $page_meta_description
        : (getSetting('meta_description') ?: 'Isa & Dagi Mobile Shop — Buy the latest smartphones and accessories in Addis Ababa, Ethiopia. Samsung, Apple, Redmi, Tecno and more.');
    $meta_keys = getSetting('meta_keywords') ?: 'mobile shop, smartphones, Addis Ababa, Samsung, Apple, Redmi, Ethiopia';
    ?>
    <meta name="description" content="<?php echo htmlspecialchars($meta_desc); ?>">
    <meta name="keywords" content="<?php echo htmlspecialchars($meta_keys); ?>">
    <meta name="author" content="Isa &amp; Dagi Mobile Shop">
    <meta property="og:title" content="<?php echo isset($page_title) ? htmlspecialchars($page_title) . ' | ' : ''; ?>Isa &amp; Dagi Mobile Shop">
    <meta property="og:description" content="<?php echo htmlspecialchars($meta_desc); ?>">
    <meta property="og:type" content="website">

    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Owl Carousel -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/OwlCarousel2/2.3.4/assets/owl.carousel.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/OwlCarousel2/2.3.4/assets/owl.theme.default.css">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Raleway:wght@400;500;600;700&family=Rubik:wght@400;500;600;700&display=swap" rel="stylesheet">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="style.css">
    <!-- Favicon -->
    <link rel="icon" type="image/png" href="assets/logo.png">
    <link rel="shortcut icon" href="assets/logo.png">
    <style>
        .auth-input-with-icon { padding-left: 36px !important; }
        .auth-input-icon {
            position: absolute; left: 11px; top: 50%; transform: translateY(-50%);
            color: #aaa; font-size: 13px; pointer-events: none;
        }
    </style>
</head>
<body>

<!-- ===================== TOP BAR ===================== -->
<div id="top-bar" class="top-bar">
    <div class="container-fluid px-4">
        <div class="d-flex justify-content-between align-items-center">
            <!-- Brand -->
            <div class="top-bar-brand">
                <i class="fas fa-map-marker-alt top-bar-brand-loc-icon"></i>
                <span class="top-bar-brand-info">Isa and Dagi · Mobile Shop · Merkato, Samson Building, Addis Ababa</span>
            </div>
            <div class="d-flex align-items-center gap-3 top-bar-links">
                <?php if ($auth->isLoggedIn()): ?>
                    <div class="dropdown">
                        <a href="#" class="top-bar-link dropdown-toggle" data-bs-toggle="dropdown">
                            <i class="fas fa-user-circle me-1"></i>
                            Hi, <strong><?php echo htmlspecialchars($_SESSION['first_name']); ?></strong>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end shadow border-0">
                            <?php if ($auth->canAccessAdmin()): ?>
                            <li><a class="dropdown-item" href="admin/index.php"><i class="fas fa-tachometer-alt me-2 text-primary"></i>
                                <?php echo $auth->isSeller() ? 'Seller Panel' : 'Admin Panel'; ?>
                            </a></li>
                            <li><hr class="dropdown-divider"></li>
                            <?php endif; ?>
                            <li><a class="dropdown-item" href="orders.php"><i class="fas fa-box me-2 text-success"></i>My Orders</a></li>
                            <li><a class="dropdown-item" href="profile.php"><i class="fas fa-user-edit me-2 text-primary"></i>My Profile</a></li>
                            <li><a class="dropdown-item" href="cart.php"><i class="fas fa-heart me-2 text-danger"></i>Wishlist</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item text-danger" href="logout.php"><i class="fas fa-sign-out-alt me-2"></i>Logout</a></li>
                        </ul>
                    </div>
                <?php else: ?>
                    <a href="#" class="top-bar-link" id="openAuthModal">
                        <i class="fas fa-user me-1"></i> Login / Register
                    </a>
                <?php endif; ?>
                <!-- Wishlist with count -->
                <?php $wish_count = $auth->isLoggedIn() ? $Cart->countWishlist($current_user_id) : 0; ?>
                <a href="cart.php#wishlist" class="top-bar-link position-relative" style="z-index:1030;">
                    <i class="fas fa-heart me-1"></i> Wishlist
                    <?php if ($wish_count > 0): ?>
                    <span class="badge rounded-pill bg-danger ms-1" style="font-size:9px;"><?php echo $wish_count; ?></span>
                    <?php endif; ?>
                </a>
                <!-- Currency switcher -->
                <?php $is_etb_nav = ($_SESSION['currency'] ?? 'USD') === 'ETB'; ?>
                <a href="?set_currency=<?php echo $is_etb_nav ? 'USD' : 'ETB'; ?>" class="top-bar-link">
                    <i class="fas fa-exchange-alt me-1"></i>
                    <?php echo $is_etb_nav ? 'USD' : 'ETB Birr'; ?>
                </a>
            </div>
        </div>
    </div>
</div>

<!-- ===================== MAIN NAVBAR ===================== -->
<nav id="main-nav" class="navbar navbar-expand-lg sticky-top">
    <div class="container-fluid px-4">

        <!-- Logo -->
        <a class="navbar-brand nav-logo" href="index.php" style="padding:0;">
            <?php
            $nav_logo = getSetting('shop_logo');
            $nav_logo_src = ($nav_logo && file_exists($nav_logo)) ? $nav_logo : 'assets/logo.svg';
            ?>
            <img src="<?php echo htmlspecialchars($nav_logo_src); ?>"
                 alt="Isa &amp; Dagi Mobile Shop"
                 style="height:48px;width:auto;display:block;">
        </a>

        <!-- Mobile toggle -->
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNavbar">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="mainNavbar">

            <!-- Search Bar (Amazon style) -->
            <form class="nav-search mx-auto" action="search.php" method="GET">
                <div class="nav-search-inner">
                    <select name="category" class="nav-search-cat">
                        <option value="">All</option>
                        <optgroup label="── Brands ──">
                        <?php foreach ($nav_brands as $nb): ?>
                        <option value="<?php echo htmlspecialchars($nb); ?>"
                            <?php echo (isset($_GET['category']) && $_GET['category'] === $nb) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($nb); ?>
                        </option>
                        <?php endforeach; ?>
                        </optgroup>
                        <optgroup label="── Type ──">
                            <option value="onsale"   <?php echo (isset($_GET['filter']) && $_GET['filter']==='onsale')   ? 'selected':''; ?>>On Sale</option>
                            <option value="featured" <?php echo (isset($_GET['filter']) && $_GET['filter']==='featured') ? 'selected':''; ?>>Featured</option>
                        </optgroup>
                    </select>
                    <input type="text" name="q" class="nav-search-input"
                           placeholder="Search phones, brands..."
                           value="<?php echo $search_query; ?>" autocomplete="off">
                    <button type="submit" class="nav-search-btn">
                        <i class="fas fa-search"></i>
                    </button>
                </div>
            </form>

            <!-- Nav Links -->
            <ul class="navbar-nav ms-3 align-items-lg-center">
                <li class="nav-item">
                    <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'index.php' ? 'active' : ''; ?>" href="index.php">Home</a>
                </li>

                <!-- Brands Dropdown — dynamic from DB -->
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" data-bs-toggle="dropdown">Brands</a>
                    <ul class="dropdown-menu shadow border-0 nav-dropdown">
                        <?php foreach ($nav_brands as $nb):
                            $icon = $brand_icons[$nb] ?? $default_brand_icon;
                        ?>
                        <li>
                            <a class="dropdown-item" href="search.php?category=<?php echo urlencode($nb); ?>">
                                <?php echo $icon; ?><?php echo htmlspecialchars($nb); ?>
                            </a>
                        </li>
                        <?php endforeach; ?>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item" href="search.php"><i class="fas fa-th me-2 text-secondary"></i>All Brands</a></li>
                    </ul>
                </li>

                <!-- Products Dropdown -->
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" data-bs-toggle="dropdown">Products</a>
                    <ul class="dropdown-menu shadow border-0 nav-dropdown">
                        <li><a class="dropdown-item" href="search.php"><i class="fas fa-th me-2"></i>All Products</a></li>
                        <li><a class="dropdown-item" href="search.php?filter=onsale"><i class="fas fa-tag me-2 text-danger"></i>On Sale</a></li>
                        <li><a class="dropdown-item" href="search.php?filter=featured"><i class="fas fa-star me-2 text-warning"></i>Featured</a></li>
                    </ul>
                </li>

                <li class="nav-item">
                    <a class="nav-link" href="search.php?filter=onsale">
                        <span class="badge bg-danger me-1">SALE</span>On Sale
                    </a>
                </li>

                <!-- Accessories Dropdown -->
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" data-bs-toggle="dropdown">Accessories</a>
                    <ul class="dropdown-menu shadow border-0 nav-dropdown">
                        <li><a class="dropdown-item" href="accessories.php?cat=Cases"><i class="fas fa-shield-alt me-2 text-primary"></i>Phone Cases</a></li>
                        <li><a class="dropdown-item" href="accessories.php?cat=Chargers"><i class="fas fa-bolt me-2 text-warning"></i>Chargers &amp; Cables</a></li>
                        <li><a class="dropdown-item" href="accessories.php?cat=Earphones"><i class="fas fa-headphones me-2 text-info"></i>Earphones</a></li>
                        <li><a class="dropdown-item" href="accessories.php?cat=Powerbanks"><i class="fas fa-battery-full me-2 text-success"></i>Power Banks</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item" href="accessories.php"><i class="fas fa-th me-2"></i>All Accessories</a></li>
                    </ul>
                </li>

                <li class="nav-item">
                    <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'blog.php' ? 'active' : ''; ?>" href="blog.php">Blog</a>
                </li>

                <!-- More Dropdown -->
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" data-bs-toggle="dropdown">More</a>
                    <ul class="dropdown-menu shadow border-0 nav-dropdown">
                        <li><a class="dropdown-item" href="about.php"><i class="fas fa-store me-2 text-primary"></i>About Us</a></li>
                        <li><a class="dropdown-item" href="support.php"><i class="fas fa-headset me-2 text-success"></i>Support</a></li>
                        <li><a class="dropdown-item" href="support.php#faq"><i class="fas fa-question-circle me-2 text-warning"></i>FAQ</a></li>
                        <li><a class="dropdown-item" href="support.php#contact"><i class="fas fa-envelope me-2 text-info"></i>Contact Us</a></li>
                    </ul>
                </li>
            </ul>

            <!-- Cart Icon -->
            <a href="cart.php" class="nav-cart-btn ms-3" style="position:relative;z-index:1030;">
                <i class="fas fa-shopping-cart"></i>
                <?php if ($cart_count > 0): ?>
                <span class="nav-cart-badge"><?php echo $cart_count; ?></span>
                <?php endif; ?>
            </a>

        </div>
    </div>
</nav>

<!-- ===================== AUTH MODAL ===================== -->
<div class="modal fade" id="authModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered auth-modal-dialog">
        <div class="modal-content auth-modal-content">
            <button type="button" class="auth-close-btn" data-bs-dismiss="modal">
                <i class="fas fa-times"></i>
            </button>
            <div class="modal-body auth-modal-body">
                <!-- Header -->
                <div class="text-center mb-4">
                    <div class="auth-shield-icon mb-3">
                        <i class="fas fa-shield-alt"></i>
                    </div>
                    <h4 class="auth-title" id="authModalTitle">Register / Sign in</h4>
                    <p class="auth-subtitle" id="authModalSubtitle">
                        <i class="fas fa-check-circle text-success me-1"></i>
                        Your information is protected
                    </p>
                </div>

                <!-- Alert -->
                <div id="authAlert" class="auth-alert d-none"></div>

                <!-- Step 1: Email -->
                <div id="authStep1">
                    <div class="auth-input-group mb-3">
                        <input type="email" id="authEmail" class="auth-input" placeholder="Enter your email" autocomplete="email">
                    </div>
                    <button class="auth-continue-btn w-100" id="authContinueBtn">Continue</button>
                    <div class="text-center mt-2 d-flex justify-content-center gap-3" style="font-size:13px;">
                        <a href="#" class="auth-trouble-link" id="showRegisterDirect">New customer? Register</a>
                        <span style="color:#ccc;">|</span>
                        <a href="#" class="auth-trouble-link" id="showForgotInline">Forgot password?</a>
                    </div>

                    <!-- Social login divider -->
                    <div class="auth-divider my-3">Or continue with</div>

                    <!-- Social buttons -->
                    <div class="auth-social-grid">
                        <!-- Guest / Account icon -->
                        <button class="auth-social-btn" title="Guest" data-social="Guest">
                            <svg width="22" height="22" viewBox="0 0 24 24" fill="none">
                                <circle cx="12" cy="8" r="4" fill="#5f6368"/>
                                <path d="M4 20c0-4 3.6-7 8-7s8 3 8 7" stroke="#5f6368" stroke-width="2" stroke-linecap="round" fill="none"/>
                                <circle cx="18" cy="18" r="5" fill="#1a73e8"/>
                                <text x="18" y="22" text-anchor="middle" font-size="8" fill="#fff" font-weight="bold">+</text>
                            </svg>
                        </button>
                        <!-- Google -->
                        <button class="auth-social-btn" title="Google" data-social="Google">
                            <svg width="22" height="22" viewBox="0 0 48 48">
                                <path fill="#EA4335" d="M24 9.5c3.1 0 5.6 1.1 7.6 2.9l5.6-5.6C33.5 3.5 29.1 1.5 24 1.5 14.9 1.5 7.2 7.1 4 15l6.6 5.1C12.3 13.6 17.6 9.5 24 9.5z"/>
                                <path fill="#4285F4" d="M46.5 24.5c0-1.6-.1-3.1-.4-4.5H24v8.5h12.7c-.6 3-2.3 5.5-4.8 7.2l7.4 5.7c4.3-4 6.8-9.9 6.8-16.9z"/>
                                <path fill="#FBBC05" d="M10.6 28.6A14.5 14.5 0 0 1 9.5 24c0-1.6.3-3.2.8-4.6L3.7 14.3A22.5 22.5 0 0 0 1.5 24c0 3.6.9 7 2.4 10l6.7-5.4z"/>
                                <path fill="#34A853" d="M24 46.5c5.1 0 9.4-1.7 12.5-4.6l-7.4-5.7c-1.7 1.1-3.9 1.8-5.1 1.8-6.4 0-11.7-4.1-13.4-9.6l-6.6 5.1C7.2 40.9 14.9 46.5 24 46.5z"/>
                                <path fill="none" d="M1.5 1.5h45v45h-45z"/>
                            </svg>
                        </button>
                        <!-- Facebook -->
                        <button class="auth-social-btn" title="Facebook" data-social="Facebook">
                            <svg width="22" height="22" viewBox="0 0 48 48">
                                <path fill="#1877F2" d="M48 24C48 10.7 37.3 0 24 0S0 10.7 0 24c0 12 8.8 21.9 20.3 23.7V30.9h-6.1V24h6.1v-5.3c0-6 3.6-9.3 9-9.3 2.6 0 5.4.5 5.4.5v5.9h-3c-3 0-3.9 1.9-3.9 3.8V24h6.6l-1.1 6.9h-5.6v16.8C39.2 45.9 48 36 48 24z"/>
                                <path fill="#fff" d="M33.4 30.9l1.1-6.9h-6.6v-4.4c0-1.9.9-3.8 3.9-3.8h3v-5.9s-2.7-.5-5.4-.5c-5.5 0-9 3.3-9 9.3V24h-6.1v6.9h6.1v16.8a24.2 24.2 0 0 0 7.4 0V30.9h5.6z"/>
                            </svg>
                        </button>
                        <!-- Apple -->
                        <button class="auth-social-btn" title="Apple" data-social="Apple">
                            <svg width="22" height="22" viewBox="0 0 814 1000">
                                <path d="M788.1 340.9c-5.8 4.5-108.2 62.2-108.2 190.5 0 148.4 130.3 200.9 134.2 202.2-.6 3.2-20.7 71.9-68.7 141.9-42.8 61.6-87.5 123.1-155.5 123.1s-85.5-39.5-164-39.5c-76 0-103.7 40.8-165.9 40.8s-105-57.8-155.5-127.4C46 790.7 0 663 0 541.8c0-207.5 135.4-317.3 269-317.3 70.1 0 128.4 46.4 172.5 46.4 42.8 0 109.6-49 192.5-49 30.8 0 108.2 2.6 168.6 71.9zm-174.5-89.3c-27.6-32.7-67.4-56.4-111.5-56.4-5.8 0-11.6.6-17.4 1.3 1.3-6.5 1.9-13 1.9-19.5 0-57.8-24.4-117.9-70.1-158.1-41.5-37-93.2-58.4-143.7-58.4-5.8 0-11.6.6-17.4 1.3 1.3-6.5 1.9-13 1.9-19.5 0-57.8-24.4-117.9-70.1-158.1C147.2 5.8 95.5-15.6 45-15.6c-5.8 0-11.6.6-17.4 1.3" fill="#000"/>
                            </svg>
                        </button>
                        <!-- X (Twitter) -->
                        <button class="auth-social-btn" title="X (Twitter)" data-social="X">
                            <svg width="20" height="20" viewBox="0 0 1200 1227" fill="none">
                                <path d="M714.163 519.284L1160.89 0H1055.03L667.137 450.887L357.328 0H0L468.492 681.821L0 1226.37H105.866L515.491 750.218L842.672 1226.37H1200L714.163 519.284ZM569.165 687.828L521.697 619.934L144.011 79.6944H306.615L611.412 515.685L658.88 583.579L1055.08 1150.3H892.476L569.165 687.828Z" fill="black"/>
                            </svg>
                        </button>
                    </div>
                </div>

                <!-- Step 2: Password / Register -->
                <div id="authStep2" class="d-none">
                    <!-- Login -->
                    <div id="loginForm">
                        <p class="auth-step-label text-center mb-3">
                            Sign in as <strong id="loginEmailDisplay"></strong>
                            <a href="#" class="auth-change-link ms-1" id="changeEmailBtn">Change</a>
                        </p>
                        <div class="auth-input-group mb-3">
                            <input type="password" id="loginPassword" class="auth-input" placeholder="Password" autocomplete="current-password">
                            <button type="button" class="auth-eye-btn" id="toggleLoginPwd"><i class="fas fa-eye"></i></button>
                        </div>
                        <!-- Remember me -->
                        <div class="d-flex align-items-center justify-content-between mb-3" style="font-size:13px;">
                            <label class="d-flex align-items-center gap-2 mb-0" style="cursor:pointer;">
                                <input type="checkbox" id="rememberMe" style="accent-color:#00A5C4;width:15px;height:15px;">
                                <span style="color:#555;">Remember me</span>
                            </label>
                            <a href="#" class="auth-trouble-link" id="forgotPwdLink">Forgot password?</a>
                        </div>
                        <button class="auth-continue-btn w-100" id="loginSubmitBtn">Sign In</button>
                        <div class="text-center mt-2">
                            <a href="#" class="auth-trouble-link" id="switchToRegister">New here? Create account</a>
                        </div>
                    </div>
                    <!-- Register -->
                    <div id="registerForm" class="d-none">
                        <p class="auth-step-label text-center mb-3">
                            Create account for <strong id="registerEmailDisplay"></strong>
                            <a href="#" class="auth-change-link ms-1" id="changeEmailBtn2">Change</a>
                        </p>
                        <div class="row g-2 mb-2">
                            <div class="col-6">
                                <input type="text" id="regFirstName" class="auth-input" placeholder="First Name *">
                            </div>
                            <div class="col-6">
                                <input type="text" id="regLastName" class="auth-input" placeholder="Last Name">
                            </div>
                        </div>
                        <div class="auth-input-group mb-2">
                            <span class="auth-input-icon"><i class="fas fa-phone"></i></span>
                            <input type="tel" id="regPhone" class="auth-input auth-input-with-icon" placeholder="Phone Number (e.g. +251 9XX XXX XXX)">
                        </div>
                        <div class="auth-input-group mb-2">
                            <input type="password" id="regPassword" class="auth-input" placeholder="Password (min 6 chars)" autocomplete="new-password">
                            <button type="button" class="auth-eye-btn" id="toggleRegPwd"><i class="fas fa-eye"></i></button>
                        </div>
                        <div class="auth-input-group mb-3">
                            <input type="password" id="regConfirmPassword" class="auth-input" placeholder="Confirm Password" autocomplete="new-password">
                            <button type="button" class="auth-eye-btn" id="toggleRegConfirmPwd"><i class="fas fa-eye"></i></button>
                        </div>
                        <!-- Password strength -->
                        <div class="mb-3">
                            <div style="height:4px;background:#eee;border-radius:4px;overflow:hidden;">
                                <div id="pwStrengthBar" style="height:100%;width:0;border-radius:4px;transition:width 0.3s,background 0.3s;"></div>
                            </div>
                            <small id="pwStrengthText" style="font-size:11px;color:#888;"></small>
                        </div>
                        <!-- Terms -->
                        <div class="mb-3 d-flex align-items-start gap-2" style="font-size:12px;">
                            <input type="checkbox" id="agreeTerms" style="accent-color:#00A5C4;width:14px;height:14px;margin-top:2px;flex-shrink:0;">
                            <label for="agreeTerms" style="color:#555;cursor:pointer;">
                                I agree to the <a href="terms.php" target="_blank" style="color:#00A5C4;">Terms of Service</a> and <a href="privacy.php" target="_blank" style="color:#00A5C4;">Privacy Policy</a>
                            </label>
                        </div>
                        <button class="auth-continue-btn w-100" id="registerSubmitBtn">Create Account</button>
                        <div class="text-center mt-2">
                            <a href="#" class="auth-trouble-link" id="switchToLogin">Already have an account? Sign in</a>
                        </div>
                    </div>
                </div>

                <!-- Step 3: Inline Forgot Password -->
                <div id="authStepForgot" class="d-none">
                    <p class="auth-step-label text-center mb-3">
                        Enter your email and we'll send you a reset link.
                    </p>
                    <div class="auth-input-group mb-3">
                        <input type="email" id="forgotEmail" class="auth-input" placeholder="Enter your email" autocomplete="email">
                    </div>
                    <button class="auth-continue-btn w-100" id="forgotSubmitBtn">
                        <i class="fas fa-paper-plane me-2"></i>Send Reset Link
                    </button>
                    <div class="text-center mt-2">
                        <a href="#" class="auth-trouble-link" id="backToLoginFromForgot">
                            <i class="fas fa-arrow-left me-1"></i>Back to Sign In
                        </a>
                    </div>
                </div>

                <p class="auth-terms text-center mt-3">
                    By continuing, you agree to our <a href="terms.php" target="_blank">Terms</a> and <a href="privacy.php" target="_blank">Privacy Policy</a>.
                </p>
            </div>
        </div>
    </div>
</div>

<!-- ===================== PAGE CONTENT START ===================== -->
<main id="main-site">
