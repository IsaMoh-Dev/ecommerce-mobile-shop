<?php
$page_title = 'Accessories';
ob_start();
include 'header.php';

$cat     = isset($_GET['cat'])    ? trim($_GET['cat'])    : '';
$q       = isset($_GET['q'])      ? trim($_GET['q'])      : '';
$page_num = max(1, (int)($_GET['page'] ?? 1));
$per_page = 20;

$categories = ['Cases', 'Chargers', 'Earphones', 'Powerbanks', 'Other'];

$cat_icons = [
    'Cases'      => ['icon' => 'fas fa-shield-alt',    'color' => '#0d6efd'],
    'Chargers'   => ['icon' => 'fas fa-bolt',          'color' => '#ffc107'],
    'Earphones'  => ['icon' => 'fas fa-headphones',    'color' => '#0dcaf0'],
    'Powerbanks' => ['icon' => 'fas fa-battery-full',  'color' => '#198754'],
    'Other'      => ['icon' => 'fas fa-box-open',      'color' => '#6c757d'],
];

// Build query
$where = "1=1";
$params = [];
if (!empty($cat)) {
    $safe_cat = mysqli_real_escape_string($db->con, $cat);
    $where .= " AND acc_category = '{$safe_cat}'";
}
if (!empty($q)) {
    $safe_q = mysqli_real_escape_string($db->con, $q);
    $where .= " AND (acc_name LIKE '%{$safe_q}%' OR acc_description LIKE '%{$safe_q}%')";
}

$all_acc = [];
$res = $db->con->query("SELECT * FROM accessories WHERE {$where} ORDER BY acc_category, acc_name");
if ($res) while ($row = $res->fetch_assoc()) $all_acc[] = $row;

// Counts per category for badges
$cat_counts = [];
foreach ($categories as $c) {
    $safe_c = mysqli_real_escape_string($db->con, $c);
    $r = $db->con->query("SELECT COUNT(*) as n FROM accessories WHERE acc_category='{$safe_c}'");
    $cat_counts[$c] = $r ? (int)$r->fetch_assoc()['n'] : 0;
}

// Pagination
$total      = count($all_acc);
$total_pages = max(1, (int)ceil($total / $per_page));
$page_num   = min($page_num, $total_pages);
$paged      = array_slice($all_acc, ($page_num - 1) * $per_page, $per_page);

// Fill to at least 10 items (2 rows of 5 columns) so grid has no empty gaps
$paged = fillToMin($paged, 10, $per_page);

$url_params = array_filter(['cat' => $cat, 'q' => $q]);
$base_url   = 'accessories.php?' . http_build_query($url_params) . '&page=';

// Handle add to cart
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_acc_to_cart'])) {
    if ($auth->isLoggedIn()) {
        $acc_id  = (int)$_POST['acc_id'];
        $user_id = $current_user_id;
        // Accessories use the same cart table — we store acc_id as item_id with a flag
        // Simple approach: insert into cart with negative id to distinguish, or use a separate acc_cart
        // Best simple approach: just add to cart using item_id = acc_id (they won't clash if product ids are separate)
        $db->con->query(
            "INSERT INTO cart (user_id, item_id, qty) VALUES ({$user_id}, {$acc_id}, 1)
             ON DUPLICATE KEY UPDATE qty = qty + 1"
        );
        header('Location: accessories.php?' . http_build_query(array_filter(['cat'=>$cat,'q'=>$q,'page'=>$page_num])));
        exit;
    }
}

$heading = !empty($cat) ? $cat : (!empty($q) ? 'Search: ' . htmlspecialchars($q) : 'All Accessories');
?>

<section class="py-4" style="background:#f8f9fa;min-height:80vh;">
<div class="container-fluid px-4">
<div class="row g-4">

    <!-- Sidebar -->
    <div class="col-lg-2 col-md-3">
        <div class="filter-sidebar">
            <h6><i class="fas fa-filter me-2"></i>Filter</h6>
            <hr>

            <!-- Search -->
            <form method="get" class="mb-3">
                <?php if ($cat): ?><input type="hidden" name="cat" value="<?php echo htmlspecialchars($cat); ?>"><?php endif; ?>
                <div class="input-group input-group-sm">
                    <input type="text" name="q" class="form-control" placeholder="Search..." value="<?php echo htmlspecialchars($q); ?>">
                    <button type="submit" class="btn btn-primary btn-sm"><i class="fas fa-search"></i></button>
                </div>
            </form>

            <p class="mb-2" style="font-size:12px;font-weight:600;color:#888;text-transform:uppercase;">Category</p>
            <ul class="list-unstyled mb-3">
                <li class="mb-1">
                    <a href="accessories.php" class="d-flex justify-content-between text-decoration-none <?php echo empty($cat) ? 'fw-bold text-primary' : 'text-muted'; ?>" style="font-size:13px;">
                        <span>All Accessories</span>
                        <span class="badge bg-secondary"><?php echo array_sum($cat_counts); ?></span>
                    </a>
                </li>
                <?php foreach ($categories as $c):
                    $ci = $cat_icons[$c] ?? ['icon'=>'fas fa-box','color'=>'#888'];
                ?>
                <li class="mb-1">
                    <a href="accessories.php?cat=<?php echo urlencode($c); ?>"
                       class="d-flex justify-content-between text-decoration-none <?php echo $cat === $c ? 'fw-bold text-primary' : 'text-muted'; ?>"
                       style="font-size:13px;">
                        <span><i class="<?php echo $ci['icon']; ?> me-1" style="color:<?php echo $ci['color']; ?>;"></i><?php echo $c; ?></span>
                        <span class="badge bg-light text-dark"><?php echo $cat_counts[$c] ?? 0; ?></span>
                    </a>
                </li>
                <?php endforeach; ?>
            </ul>

            <hr>
            <p class="mb-2" style="font-size:12px;font-weight:600;color:#888;text-transform:uppercase;">Phones</p>
            <ul class="list-unstyled">
                <li class="mb-1"><a href="search.php" class="text-muted text-decoration-none" style="font-size:13px;"><i class="fas fa-mobile-alt me-1"></i>All Phones</a></li>
                <li class="mb-1"><a href="search.php?filter=onsale" class="text-muted text-decoration-none" style="font-size:13px;"><i class="fas fa-tag me-1 text-danger"></i>On Sale</a></li>
            </ul>
        </div>
    </div>

    <!-- Main Content -->
    <div class="col-lg-10 col-md-9">

        <!-- Category hero chips -->
        <?php if (empty($cat) && empty($q)): ?>
        <div class="d-flex gap-3 flex-wrap mb-4">
            <?php foreach ($categories as $c):
                $ci = $cat_icons[$c] ?? ['icon'=>'fas fa-box','color'=>'#888'];
            ?>
            <a href="accessories.php?cat=<?php echo urlencode($c); ?>"
               class="text-decoration-none d-flex align-items-center gap-2 px-4 py-2 bg-white rounded-pill shadow-sm border"
               style="font-size:13px;font-weight:600;color:#003859;transition:all 0.2s;"
               onmouseover="this.style.background='#003859';this.style.color='#fff';"
               onmouseout="this.style.background='#fff';this.style.color='#003859';">
                <i class="<?php echo $ci['icon']; ?>" style="color:<?php echo $ci['color']; ?>;font-size:16px;"></i>
                <?php echo $c; ?>
                <span class="badge bg-secondary ms-1" style="font-size:10px;"><?php echo $cat_counts[$c] ?? 0; ?></span>
            </a>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <!-- Heading + count -->
        <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
            <h5 class="section-heading mb-0"><?php echo $heading; ?></h5>
            <span class="text-muted" style="font-size:13px;">
                <?php echo $total; ?> item<?php echo $total !== 1 ? 's' : ''; ?> found
                <?php if ($total_pages > 1): ?>— page <?php echo $page_num; ?> of <?php echo $total_pages; ?><?php endif; ?>
            </span>
        </div>

        <?php if (empty($paged)): ?>
        <div class="text-center py-5 bg-white rounded-3 shadow-sm">
            <i class="fas fa-search fa-3x text-muted mb-3"></i>
            <h5 class="text-muted">No accessories found</h5>
            <a href="accessories.php" class="btn btn-primary-custom mt-3">View All Accessories</a>
        </div>
        <?php else: ?>

        <div class="row row-cols-2 row-cols-sm-3 row-cols-md-3 row-cols-lg-4 row-cols-xl-5 g-3">
            <?php foreach ($paged as $acc):
                $discount = 0;
                if (!empty($acc['acc_old_price']) && $acc['acc_old_price'] > 0)
                    $discount = round((($acc['acc_old_price'] - $acc['acc_price']) / $acc['acc_old_price']) * 100);
                $ci = $cat_icons[$acc['acc_category']] ?? ['icon'=>'fas fa-box','color'=>'#888'];
            ?>
            <div class="col">
                <div class="product-card">
                    <div class="card-img-wrap">
                        <?php if ($acc['acc_onsale']): ?>
                        <span class="badge-sale">SALE</span>
                        <?php endif; ?>
                        <?php if ($discount > 0): ?>
                        <span style="position:absolute;top:10px;right:10px;background:#dc3545;color:#fff;font-size:10px;font-weight:700;padding:2px 6px;border-radius:4px;">-<?php echo $discount; ?>%</span>
                        <?php endif; ?>
                        <div style="display:flex;align-items:center;justify-content:center;height:100%;">
                            <img src="<?php echo htmlspecialchars($acc['acc_image']); ?>"
                                 alt="<?php echo htmlspecialchars($acc['acc_name']); ?>"
                                 style="max-height:140px;object-fit:contain;">
                        </div>
                    </div>
                    <div class="card-body">
                        <!-- Category badge -->
                        <div class="mb-1">
                            <span style="font-size:10px;font-weight:600;color:<?php echo $ci['color']; ?>;text-transform:uppercase;letter-spacing:0.5px;">
                                <i class="<?php echo $ci['icon']; ?> me-1"></i><?php echo $acc['acc_category']; ?>
                            </span>
                        </div>
                        <div class="card-title"><?php echo htmlspecialchars($acc['acc_name']); ?></div>
                        <?php if (!empty($acc['acc_description'])): ?>
                        <div style="font-size:11px;color:#888;margin-bottom:6px;line-height:1.4;">
                            <?php echo htmlspecialchars(mb_substr($acc['acc_description'], 0, 55)) . '...'; ?>
                        </div>
                        <?php endif; ?>
                        <div class="card-price-wrap mb-2">
                            <span class="card-price"><?php echo formatPrice($acc['acc_price']); ?></span>
                            <?php if (!empty($acc['acc_old_price'])): ?>
                            <span class="card-old-price"><?php echo formatPrice($acc['acc_old_price']); ?></span>
                            <?php endif; ?>
                        </div>
                        <?php if ($acc['acc_stock'] > 0): ?>
                        <form method="post">
                            <input type="hidden" name="acc_id" value="<?php echo $acc['acc_id']; ?>">
                            <button type="submit" name="add_acc_to_cart" class="btn-cart">
                                <i class="fas fa-cart-plus me-1"></i>Add to Cart
                            </button>
                        </form>
                        <?php else: ?>
                        <button class="btn-cart in-cart" disabled>Out of Stock</button>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <!-- Pagination -->
        <?php if ($total_pages > 1): ?>
        <nav class="mt-5 d-flex justify-content-center">
            <ul class="pagination pagination-custom">
                <li class="page-item <?php echo $page_num <= 1 ? 'disabled' : ''; ?>">
                    <a class="page-link" href="<?php echo $base_url . ($page_num - 1); ?>"><i class="fas fa-chevron-left"></i></a>
                </li>
                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                <li class="page-item <?php echo $i === $page_num ? 'active' : ''; ?>">
                    <a class="page-link" href="<?php echo $base_url . $i; ?>"><?php echo $i; ?></a>
                </li>
                <?php endfor; ?>
                <li class="page-item <?php echo $page_num >= $total_pages ? 'disabled' : ''; ?>">
                    <a class="page-link" href="<?php echo $base_url . ($page_num + 1); ?>"><i class="fas fa-chevron-right"></i></a>
                </li>
            </ul>
        </nav>
        <?php endif; ?>

        <?php endif; ?>
    </div>
</div>
</div>
</section>

<?php include 'footer.php'; ?>
