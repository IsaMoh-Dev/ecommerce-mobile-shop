<?php
$page_title = 'Products';
ob_start();
include 'header.php';

// ---- Filters from URL ----
$q         = isset($_GET['q'])        ? trim($_GET['q'])        : '';
$category  = isset($_GET['category']) ? trim($_GET['category']) : '';
$filter    = isset($_GET['filter'])   ? trim($_GET['filter'])   : '';
$min_price = isset($_GET['min_price']) && $_GET['min_price'] !== '' ? (float)$_GET['min_price'] : '';
$max_price = isset($_GET['max_price']) && $_GET['max_price'] !== '' ? (float)$_GET['max_price'] : '';
$sort      = isset($_GET['sort'])     ? trim($_GET['sort'])     : '';
$page_num  = max(1, (int)($_GET['page'] ?? 1));
$per_page  = 20;

// ---- Fetch products ----
if (!empty($q)) {
    $products = $product->searchProducts($q);
    $heading  = 'Search results for: <em>' . htmlspecialchars($q) . '</em>';
} elseif (!empty($category)) {
    $products = $product->getByBrand($category);
    $heading  = htmlspecialchars($category) . ' Phones';
} elseif ($filter === 'onsale') {
    $products = $product->getOnSale();
    $heading  = 'On Sale';
} elseif ($filter === 'featured') {
    $products = $product->getFeatured();
    $heading  = 'Featured Products';
} else {
    $products = $product->getData();
    $heading  = 'All Products';
}

// ---- Price range filter ----
// If user is in ETB mode, convert their input back to USD for comparison
$rate_for_filter = (float)(getSetting('usd_to_birr') ?: 57);
$is_etb_filter   = ($_SESSION['currency'] ?? 'USD') === 'ETB';
$min_usd = $min_price !== '' ? ($is_etb_filter ? $min_price / $rate_for_filter : $min_price) : '';
$max_usd = $max_price !== '' ? ($is_etb_filter ? $max_price / $rate_for_filter : $max_price) : '';
if ($min_usd !== '') $products = array_filter($products, fn($p) => $p['item_price'] >= $min_usd);
if ($max_usd !== '') $products = array_filter($products, fn($p) => $p['item_price'] <= $max_usd);
$products = array_values($products);

// ---- Sort ----
if ($sort === 'price_asc')  usort($products, fn($a,$b) => $a['item_price'] <=> $b['item_price']);
if ($sort === 'price_desc') usort($products, fn($a,$b) => $b['item_price'] <=> $a['item_price']);
if ($sort === 'name_asc')   usort($products, fn($a,$b) => strcmp($a['item_name'], $b['item_name']));

// ---- Pagination ----
$total_products = count($products);
$total_pages    = max(1, (int)ceil($total_products / $per_page));
$page_num       = min($page_num, $total_pages);
$paged_products = array_slice($products, ($page_num - 1) * $per_page, $per_page);

// Preserve all active filters in pagination URLs
$url_params = array_filter([
    'q'         => $q,
    'category'  => $category,
    'filter'    => $filter,
    'min_price' => $min_price !== '' ? $min_price : '',
    'max_price' => $max_price !== '' ? $max_price : '',
    'sort'      => $sort,
], fn($v) => $v !== '');
$base_url = 'search.php?' . http_build_query($url_params) . '&page=';

// ---- Sidebar brands ----
$all_products_list = $product->getData();
$brands = array_unique(array_column($all_products_list, 'item_brand'));
sort($brands);

// ---- Cart check ----
$in_cart_items = $auth->isLoggedIn() ? $Cart->getCartByUser($current_user_id) : [];
$in_cart       = $Cart->getCartId($in_cart_items);

// ---- Handle add to cart ----
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_to_cart'])) {
    if ($auth->isLoggedIn()) {
        $Cart->addToCart($current_user_id, (int)$_POST['item_id']);
        header('Location: search.php?' . http_build_query($_GET));
        exit;
    } else {
        // Not logged in — redirect back with flag to open login modal
        header('Location: search.php?' . http_build_query($_GET) . '&need_login=1');
        exit;
    }
}

// Price format helper — uses global formatPrice for currency switching
function searchPrice($amount) {
    return formatPrice($amount);
}
?>

<section class="py-4" style="background:#f8f9fa;min-height:80vh;">

<!-- Mobile Filter Toggle Button -->
<div class="d-md-none px-3 mb-3">
    <button class="btn btn-outline-primary w-100" type="button"
            data-bs-toggle="offcanvas" data-bs-target="#filterOffcanvas">
        <i class="fas fa-filter me-2"></i>Filter & Browse
    </button>
</div>

<!-- Mobile Offcanvas Sidebar -->
<div class="offcanvas offcanvas-start d-md-none" tabindex="-1" id="filterOffcanvas" style="width:280px;">
    <div class="offcanvas-header" style="background:var(--primary);color:#fff;">
        <h6 class="offcanvas-title mb-0" style="font-family:'Rubik',sans-serif;font-weight:700;">
            <i class="fas fa-filter me-2"></i>Filter Products
        </h6>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="offcanvas"></button>
    </div>
    <div class="offcanvas-body p-3">
        <p class="mb-2" style="font-size:12px;font-weight:600;color:#888;text-transform:uppercase;">By Brand</p>
        <ul class="list-unstyled mb-3">
            <li class="mb-1">
                <a href="search.php" class="text-decoration-none <?php echo empty($category)&&empty($filter)&&empty($q)?'fw-bold text-primary':'text-muted'; ?>" style="font-size:14px;">All Brands</a>
            </li>
            <?php foreach ($brands as $b): ?>
            <li class="mb-1">
                <a href="search.php?category=<?php echo urlencode($b); ?>"
                   class="text-decoration-none <?php echo $category===$b?'fw-bold text-primary':'text-muted'; ?>"
                   style="font-size:14px;"><?php echo htmlspecialchars($b); ?></a>
            </li>
            <?php endforeach; ?>
        </ul>
        <hr>
        <p class="mb-2" style="font-size:12px;font-weight:600;color:#888;text-transform:uppercase;">By Type</p>
        <ul class="list-unstyled mb-3">
            <li class="mb-1"><a href="search.php?filter=onsale" class="text-decoration-none <?php echo $filter==='onsale'?'fw-bold text-danger':'text-muted'; ?>" style="font-size:14px;"><i class="fas fa-tag me-1 text-danger"></i>On Sale</a></li>
            <li class="mb-1"><a href="search.php?filter=featured" class="text-decoration-none <?php echo $filter==='featured'?'fw-bold text-warning':'text-muted'; ?>" style="font-size:14px;"><i class="fas fa-star me-1 text-warning"></i>Featured</a></li>
        </ul>
        <hr>
        <p class="mb-2" style="font-size:12px;font-weight:600;color:#888;text-transform:uppercase;">Accessories</p>
        <ul class="list-unstyled">
            <li class="mb-1"><a href="accessories.php" class="text-decoration-none text-muted" style="font-size:14px;"><i class="fas fa-th me-1"></i>All Accessories</a></li>
            <li class="mb-1"><a href="accessories.php?cat=Cases" class="text-decoration-none text-muted" style="font-size:14px;"><i class="fas fa-shield-alt me-1 text-primary"></i>Cases</a></li>
            <li class="mb-1"><a href="accessories.php?cat=Chargers" class="text-decoration-none text-muted" style="font-size:14px;"><i class="fas fa-bolt me-1 text-warning"></i>Chargers</a></li>
            <li class="mb-1"><a href="accessories.php?cat=Earphones" class="text-decoration-none text-muted" style="font-size:14px;"><i class="fas fa-headphones me-1 text-info"></i>Earphones</a></li>
            <li class="mb-1"><a href="accessories.php?cat=Powerbanks" class="text-decoration-none text-muted" style="font-size:14px;"><i class="fas fa-battery-full me-1 text-success"></i>Power Banks</a></li>
        </ul>
    </div>
</div>

<div class="container-fluid px-4">
<div class="row g-4">

    <!-- ===== SIDEBAR ===== -->
    <div class="col-lg-2 col-md-3 d-none d-md-block" id="filterSidebarCol">
        <div class="filter-sidebar" id="filterSidebar">
            <h6><i class="fas fa-filter me-2"></i>Filter</h6>
            <hr>

            <!-- By Brand -->
            <p class="mb-2" style="font-size:12px;font-weight:600;color:#888;text-transform:uppercase;">By Brand</p>
            <ul class="list-unstyled mb-3">
                <li class="mb-1">
                    <a href="search.php"
                       class="text-decoration-none <?php echo empty($category)&&empty($filter)&&empty($q)?'fw-bold text-primary':'text-muted'; ?>"
                       style="font-size:13px;">All Brands</a>
                </li>
                <?php foreach ($brands as $b): ?>
                <li class="mb-1">
                    <a href="search.php?category=<?php echo urlencode($b); ?>"
                       class="text-decoration-none <?php echo $category===$b?'fw-bold text-primary':'text-muted'; ?>"
                       style="font-size:13px;"><?php echo htmlspecialchars($b); ?></a>
                </li>
                <?php endforeach; ?>
            </ul>

            <hr>
            <!-- By Type -->
            <p class="mb-2" style="font-size:12px;font-weight:600;color:#888;text-transform:uppercase;">By Type</p>
            <ul class="list-unstyled mb-3">
                <li class="mb-1">
                    <a href="search.php?filter=onsale"
                       class="text-decoration-none <?php echo $filter==='onsale'?'fw-bold text-danger':'text-muted'; ?>"
                       style="font-size:13px;"><i class="fas fa-tag me-1 text-danger"></i>On Sale</a>
                </li>
                <li class="mb-1">
                    <a href="search.php?filter=featured"
                       class="text-decoration-none <?php echo $filter==='featured'?'fw-bold text-warning':'text-muted'; ?>"
                       style="font-size:13px;"><i class="fas fa-star me-1 text-warning"></i>Featured</a>
                </li>
            </ul>

            <hr>
            <!-- Accessories -->
            <p class="mb-2" style="font-size:12px;font-weight:600;color:#888;text-transform:uppercase;">Accessories</p>
            <ul class="list-unstyled">
                <li class="mb-1"><a href="accessories.php"               class="text-decoration-none text-muted" style="font-size:13px;"><i class="fas fa-th me-1"></i>All Accessories</a></li>
                <li class="mb-1"><a href="accessories.php?cat=Cases"     class="text-decoration-none text-muted" style="font-size:13px;"><i class="fas fa-shield-alt me-1 text-primary"></i>Cases</a></li>
                <li class="mb-1"><a href="accessories.php?cat=Chargers"  class="text-decoration-none text-muted" style="font-size:13px;"><i class="fas fa-bolt me-1 text-warning"></i>Chargers</a></li>
                <li class="mb-1"><a href="accessories.php?cat=Earphones" class="text-decoration-none text-muted" style="font-size:13px;"><i class="fas fa-headphones me-1 text-info"></i>Earphones</a></li>
                <li class="mb-1"><a href="accessories.php?cat=Powerbanks"class="text-decoration-none text-muted" style="font-size:13px;"><i class="fas fa-battery-full me-1 text-success"></i>Power Banks</a></li>
            </ul>
        </div>
    </div>

    <!-- ===== MAIN CONTENT ===== -->
    <div class="col-12 col-md-9 col-lg-10">

        <!-- Heading + Sort -->
        <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
            <h5 class="section-heading mb-0"><?php echo $heading; ?></h5>
            <div class="d-flex align-items-center gap-2 flex-wrap">
                <span class="text-muted" style="font-size:13px;"><?php echo $total_products; ?> found</span>
                <form method="get" class="d-flex align-items-center gap-1">
                    <?php foreach (array_filter(['q'=>$q,'category'=>$category,'filter'=>$filter,'min_price'=>($min_price!==''?$min_price:''),'max_price'=>($max_price!==''?$max_price:'')]) as $k=>$v): ?>
                    <input type="hidden" name="<?php echo $k; ?>" value="<?php echo htmlspecialchars($v); ?>">
                    <?php endforeach; ?>
                    <select name="sort" class="form-select form-select-sm" style="font-size:12px;width:auto;" onchange="this.form.submit()">
                        <option value=""           <?php echo $sort===''          ?'selected':''; ?>>Sort: Default</option>
                        <option value="price_asc"  <?php echo $sort==='price_asc' ?'selected':''; ?>>Price: Low to High</option>
                        <option value="price_desc" <?php echo $sort==='price_desc'?'selected':''; ?>>Price: High to Low</option>
                        <option value="name_asc"   <?php echo $sort==='name_asc'  ?'selected':''; ?>>Name: A to Z</option>
                    </select>
                </form>
            </div>
        </div>

        <!-- Price Range Filter -->
        <form method="get" class="bg-white rounded-3 shadow-sm p-3 mb-3 d-flex align-items-end gap-2 flex-wrap">
            <?php
            $cur_label = ($_SESSION['currency'] ?? 'USD') === 'ETB' ? 'BIRR' : 'USD';
            foreach (array_filter(['q'=>$q,'category'=>$category,'filter'=>$filter,'sort'=>$sort]) as $k=>$v): ?>
            <input type="hidden" name="<?php echo $k; ?>" value="<?php echo htmlspecialchars($v); ?>">
            <?php endforeach; ?>
            <div>
                <label style="font-size:11px;font-weight:600;color:#888;display:block;margin-bottom:3px;">MIN PRICE (<?php echo $cur_label; ?>)</label>
                <input type="number" name="min_price" class="form-control form-control-sm" style="width:100px;"
                       placeholder="0" value="<?php echo $min_price !== '' ? $min_price : ''; ?>" min="0" step="1">
            </div>
            <div>
                <label style="font-size:11px;font-weight:600;color:#888;display:block;margin-bottom:3px;">MAX PRICE (<?php echo $cur_label; ?>)</label>
                <input type="number" name="max_price" class="form-control form-control-sm" style="width:100px;"
                       placeholder="Any" value="<?php echo $max_price !== '' ? $max_price : ''; ?>" min="0" step="1">
            </div>
            <button type="submit" class="btn btn-primary btn-sm px-3">Apply</button>
            <?php if ($min_price !== '' || $max_price !== ''): ?>
            <a href="search.php?<?php echo http_build_query(array_filter(['q'=>$q,'category'=>$category,'filter'=>$filter,'sort'=>$sort])); ?>"
               class="btn btn-outline-secondary btn-sm">Clear</a>
            <span class="text-primary" style="font-size:12px;font-weight:600;">
                <i class="fas fa-filter me-1"></i>
                <?php echo ($min_price !== '' ? $cur_label.' '.$min_price : $cur_label.' 0'); ?> &ndash;
                <?php echo ($max_price !== '' ? $cur_label.' '.$max_price : 'Any'); ?>
            </span>
            <?php endif; ?>
        </form>

        <!-- No results -->
        <?php if (empty($paged_products)): ?>
        <div class="text-center py-5 bg-white rounded-3 shadow-sm">
            <i class="fas fa-search fa-3x text-muted mb-3 d-block"></i>
            <h5 class="text-muted">No products found</h5>
            <a href="search.php" class="btn btn-primary-custom mt-3">View All Products</a>
        </div>

        <?php else: ?>
        <!-- Products Grid -->
        <div class="row row-cols-2 row-cols-sm-2 row-cols-md-3 row-cols-lg-4 row-cols-xl-5 g-3">
            <?php foreach ($paged_products as $item): ?>
            <div class="col">
                <div class="product-card">
                    <div class="card-img-wrap">
                        <?php if ($item['item_onsale']): ?>
                        <span class="badge-sale">SALE</span>
                        <?php endif; ?>
                        <a href="product.php?item_id=<?php echo $item['item_id']; ?>">
                            <img src="<?php echo htmlspecialchars($item['item_image']); ?>"
                                 alt="<?php echo htmlspecialchars($item['item_name']); ?>">
                        </a>
                    </div>
                    <div class="card-body">
                        <div class="card-brand"><?php echo htmlspecialchars($item['item_brand']); ?></div>
                        <div class="card-title">
                            <a href="product.php?item_id=<?php echo $item['item_id']; ?>" class="text-dark">
                                <?php echo htmlspecialchars($item['item_name']); ?>
                            </a>
                        </div>
                        <div class="card-price-wrap mb-2">
                            <span class="card-price"><?php echo searchPrice($item['item_price']); ?></span>
                            <?php if (!empty($item['item_old_price'])): ?>
                            <span class="card-old-price"><?php echo searchPrice($item['item_old_price']); ?></span>
                            <?php endif; ?>
                        </div>
                        <form method="post">
                            <input type="hidden" name="item_id" value="<?php echo $item['item_id']; ?>">
                            <?php if (in_array($item['item_id'], $in_cart)): ?>
                            <button type="button" class="btn-cart in-cart" disabled>
                                <i class="fas fa-check me-1"></i>In Cart
                            </button>
                            <?php else: ?>
                            <button type="submit" name="add_to_cart" class="btn-cart">
                                <i class="fas fa-cart-plus me-1"></i>Add to Cart
                            </button>
                            <?php endif; ?>
                        </form>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <!-- Pagination -->
        <?php if ($total_pages > 1): ?>
        <nav class="mt-5 d-flex justify-content-center">
            <ul class="pagination pagination-custom">
                <li class="page-item <?php echo $page_num<=1?'disabled':''; ?>">
                    <a class="page-link" href="<?php echo $base_url.($page_num-1); ?>"><i class="fas fa-chevron-left"></i></a>
                </li>
                <?php
                $start = max(1, $page_num-2);
                $end   = min($total_pages, $page_num+2);
                if ($start > 1): ?>
                <li class="page-item"><a class="page-link" href="<?php echo $base_url.'1'; ?>">1</a></li>
                <?php if ($start > 2): ?><li class="page-item disabled"><span class="page-link">…</span></li><?php endif; ?>
                <?php endif; ?>
                <?php for ($i=$start; $i<=$end; $i++): ?>
                <li class="page-item <?php echo $i===$page_num?'active':''; ?>">
                    <a class="page-link" href="<?php echo $base_url.$i; ?>"><?php echo $i; ?></a>
                </li>
                <?php endfor; ?>
                <?php if ($end < $total_pages): ?>
                <?php if ($end < $total_pages-1): ?><li class="page-item disabled"><span class="page-link">…</span></li><?php endif; ?>
                <li class="page-item"><a class="page-link" href="<?php echo $base_url.$total_pages; ?>"><?php echo $total_pages; ?></a></li>
                <?php endif; ?>
                <li class="page-item <?php echo $page_num>=$total_pages?'disabled':''; ?>">
                    <a class="page-link" href="<?php echo $base_url.($page_num+1); ?>"><i class="fas fa-chevron-right"></i></a>
                </li>
            </ul>
        </nav>
        <?php endif; ?>
        <?php endif; ?>

    </div>
</div>
</div>
</section>

<?php if (isset($_GET['need_login'])): ?>
<script>
$(document).ready(function(){
    var modal = new bootstrap.Modal(document.getElementById('authModal'));
    modal.show();
});
</script>
<?php endif; ?>

<?php include 'footer.php'; ?>
