<?php
// Admin sidebar — included in all admin pages
// $active_page should be set before including this file
$active_page = $active_page ?? '';

$unread_msgs = 0;
$r = $db->con->query("SELECT COUNT(*) as c FROM contact_messages WHERE is_read=0");
if ($r) $unread_msgs = (int)$r->fetch_assoc()['c'];

$logo_path  = getSetting('shop_logo');
$my_role    = $auth->getRole();
$is_super   = $auth->isSuperAdmin();
$is_admin   = $auth->isAdmin();   // true for admin + superadmin
$is_seller  = $auth->isSeller();

$role_labels = [
    'superadmin' => ['label' => 'Super Admin', 'color' => '#dc3545'],
    'admin'      => ['label' => 'Admin',        'color' => '#fd7e14'],
    'seller'     => ['label' => 'Seller',       'color' => '#28a745'],
    'customer'   => ['label' => 'Customer',     'color' => '#6c757d'],
];
$role_info = $role_labels[$my_role] ?? $role_labels['customer'];
?>
<div class="admin-sidebar">
    <div class="admin-logo">
        <?php if ($logo_path && file_exists('../' . ltrim($logo_path, './'))) : ?>
        <img src="../<?php echo htmlspecialchars(ltrim($logo_path, './')); ?>" alt="Shop Logo" style="height:36px;width:auto;">
        <?php else : ?>
        <span class="logo-fallback"><i class="fas fa-mobile-alt"></i>Isa &amp; Dagi Admin</span>
        <?php endif; ?>
        <!-- Role badge -->
        <div class="mt-2" style="font-size:11px;">
            <span style="background:<?php echo $role_info['color']; ?>;color:#fff;padding:2px 10px;border-radius:20px;font-weight:600;">
                <?php echo $role_info['label']; ?>
            </span>
            <span style="color:rgba(255,255,255,0.6);margin-left:6px;font-size:11px;">
                <?php echo htmlspecialchars($_SESSION['first_name'] ?? ''); ?>
            </span>
        </div>
    </div>
    <nav class="nav flex-column mt-2">

        <!-- Dashboard — all roles -->
        <a class="nav-link <?php echo $active_page==='dashboard'?'active':''; ?>" href="index.php">
            <i class="fas fa-tachometer-alt"></i>Dashboard
        </a>

        <!-- Products — all roles -->
        <a class="nav-link <?php echo $active_page==='products'?'active':''; ?>" href="products.php">
            <i class="fas fa-box"></i>Products
        </a>

        <!-- Accessories — all roles -->
        <a class="nav-link <?php echo $active_page==='accessories'?'active':''; ?>" href="accessories.php">
            <i class="fas fa-headphones"></i>Accessories
        </a>

        <!-- Orders — all roles -->
        <a class="nav-link <?php echo $active_page==='orders'?'active':''; ?>" href="orders.php">
            <i class="fas fa-shopping-bag"></i>Orders
        </a>

        <!-- Blog — admin + superadmin only -->
        <?php if ($is_admin): ?>
        <a class="nav-link <?php echo $active_page==='blog'?'active':''; ?>" href="blog.php">
            <i class="fas fa-newspaper"></i>Blog
        </a>
        <?php endif; ?>

        <!-- Banners — admin + superadmin only -->
        <?php if ($is_admin): ?>
        <a class="nav-link <?php echo $active_page==='banners'?'active':''; ?>" href="banners.php">
            <i class="fas fa-images"></i>Banners
        </a>
        <?php endif; ?>

        <!-- Reviews — admin + superadmin only -->
        <?php if ($is_admin): ?>
        <a class="nav-link <?php echo $active_page==='reviews'?'active':''; ?>" href="reviews.php">
            <i class="fas fa-star"></i>Reviews
        </a>
        <?php endif; ?>

        <!-- Coupons — admin + superadmin only -->
        <?php if ($is_admin): ?>
        <a class="nav-link <?php echo $active_page==='coupons'?'active':''; ?>" href="coupons.php">
            <i class="fas fa-tag"></i>Coupons
        </a>
        <?php endif; ?>

        <!-- Users — admin + superadmin only -->
        <?php if ($is_admin): ?>
        <a class="nav-link <?php echo $active_page==='users'?'active':''; ?>" href="users.php">
            <i class="fas fa-users"></i>Users
        </a>
        <?php endif; ?>

        <!-- Sellers — admin + superadmin only -->
        <?php if ($is_admin): ?>
        <a class="nav-link <?php echo $active_page==='sellers'?'active':''; ?>" href="sellers.php">
            <i class="fas fa-user-tag"></i>Sellers
        </a>
        <?php endif; ?>

        <hr style="border-color:rgba(255,255,255,0.1);margin:10px 20px;">

        <!-- Settings — admin + superadmin only -->
        <?php if ($is_admin): ?>
        <a class="nav-link <?php echo $active_page==='settings'?'active':''; ?>" href="settings.php">
            <i class="fas fa-cog"></i>Settings
            <?php if ($unread_msgs > 0): ?>
            <span class="badge bg-danger ms-1" style="font-size:9px;"><?php echo $unread_msgs; ?></span>
            <?php endif; ?>
        </a>
        <?php endif; ?>

        <a class="nav-link" href="../index.php" target="_blank">
            <i class="fas fa-store"></i>View Shop
        </a>
        <a class="nav-link text-danger" href="../logout.php">
            <i class="fas fa-sign-out-alt"></i>Logout
        </a>
    </nav>
</div>
