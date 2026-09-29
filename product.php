<?php
$page_title = 'Product';
ob_start();
include 'header.php';

$item_id = isset($_GET['item_id']) ? (int)$_GET['item_id'] : 1;
$items   = $product->getProduct($item_id);

// Fix cart check
$in_cart_items = $auth->isLoggedIn() ? $Cart->getCartByUser($current_user_id) : [];
$in_cart       = $Cart->getCartId($in_cart_items);

// Handle add to cart
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_to_cart'])) {
    if ($auth->isLoggedIn()) {
        $Cart->addToCart($current_user_id, $item_id);
        header("Location: product.php?item_id={$item_id}");
        exit;
    } else {
        header("Location: product.php?item_id={$item_id}&login=1");
        exit;
    }
}

// Handle Buy Now — add to cart then go straight to checkout
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['buy_now'])) {
    if ($auth->isLoggedIn()) {
        // Add to cart (or increase qty if already there)
        $uid = $current_user_id;
        $db->con->query(
            "INSERT INTO cart (user_id, item_id, qty) VALUES ({$uid},{$item_id},1)
             ON DUPLICATE KEY UPDATE qty = qty + 1"
        );
        header("Location: checkout.php");
        exit;
    } else {
        header("Location: product.php?item_id={$item_id}&login=1");
        exit;
    }
}

if (empty($items)) {
    echo '<div class="container py-5 text-center"><h4>Product not found.</h4><a href="search.php" class="btn btn-primary mt-3">Back to Products</a></div>';
    include 'footer.php';
    exit;
}

$item       = $items[0];
$page_title = htmlspecialchars($item['item_name']);

$related = array_filter($product->getByBrand($item['item_brand']), fn($p) => $p['item_id'] != $item_id);
$related = array_slice($related, 0, 4);

$discount = 0;
if (!empty($item['item_old_price']) && $item['item_old_price'] > 0)
    $discount = round((($item['item_old_price'] - $item['item_price']) / $item['item_old_price']) * 100);

// Build specs array — only non-empty ones
$specs = [];
if (!empty($item['item_processor'])) $specs[] = ['icon'=>'fas fa-microchip',    'label'=>'Processor',  'value'=>$item['item_processor']];
if (!empty($item['item_ram']))       $specs[] = ['icon'=>'fas fa-memory',        'label'=>'RAM',        'value'=>$item['item_ram']];
if (!empty($item['item_storage']))   $specs[] = ['icon'=>'fas fa-hdd',           'label'=>'Storage',    'value'=>$item['item_storage']];
if (!empty($item['item_display']))   $specs[] = ['icon'=>'fas fa-mobile-alt',    'label'=>'Display',    'value'=>$item['item_display']];
if (!empty($item['item_camera']))    $specs[] = ['icon'=>'fas fa-camera',        'label'=>'Camera',     'value'=>$item['item_camera']];
if (!empty($item['item_battery']))   $specs[] = ['icon'=>'fas fa-battery-full',  'label'=>'Battery',    'value'=>$item['item_battery']];
if (!empty($item['item_os']))        $specs[] = ['icon'=>'fab fa-android',       'label'=>'OS',         'value'=>$item['item_os']];

// ---- Reviews ----
// Create table if not exists (safe)
$db->con->query("CREATE TABLE IF NOT EXISTS `reviews` (
    `review_id`  INT NOT NULL AUTO_INCREMENT,
    `item_id`    INT NOT NULL,
    `user_id`    INT NOT NULL,
    `rating`     TINYINT(1) NOT NULL DEFAULT 5,
    `comment`    TEXT DEFAULT NULL,
    `created_at` DATETIME DEFAULT NOW(),
    PRIMARY KEY (`review_id`),
    UNIQUE KEY `unique_review` (`item_id`, `user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

$review_msg = '';
$review_err = '';

// Handle submit review
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_review'])) {
    if (!$auth->isLoggedIn()) {
        $review_err = 'Please login to leave a review.';
    } else {
        $rating  = min(5, max(1, (int)($_POST['rating'] ?? 5)));
        $comment = trim($_POST['comment'] ?? '');
        if (empty($comment)) {
            $review_err = 'Please write a comment.';
        } else {
            $stmt = $db->con->prepare(
                "INSERT INTO reviews (item_id, user_id, rating, comment)
                 VALUES (?, ?, ?, ?)
                 ON DUPLICATE KEY UPDATE rating=VALUES(rating), comment=VALUES(comment), created_at=NOW()"
            );
            $stmt->bind_param("iiis", $item_id, $current_user_id, $rating, $comment);
            $stmt->execute();
            $stmt->close();
            $review_msg = 'Your review has been submitted!';
        }
    }
}

// Fetch reviews
$reviews = [];
$rev_res = $db->con->query(
    "SELECT r.*, u.first_name, u.last_name FROM reviews r
     JOIN user u ON r.user_id = u.user_id
     WHERE r.item_id = {$item_id} ORDER BY r.created_at DESC"
);
if ($rev_res) while ($row = $rev_res->fetch_assoc()) $reviews[] = $row;

$avg_rating  = count($reviews) ? round(array_sum(array_column($reviews, 'rating')) / count($reviews), 1) : 0;
$review_count = count($reviews);

// Check if current user already reviewed
$user_reviewed = false;
if ($auth->isLoggedIn()) {
    foreach ($reviews as $rv) {
        if ($rv['user_id'] == $current_user_id) { $user_reviewed = true; break; }
    }
}
?>

<style>
/* ---- Product Detail Page ---- */
#product-detail { background: #f8f9fa; }

.product-img-box {
    background: #fff;
    border-radius: 16px;
    padding: 24px;
    box-shadow: 0 2px 16px rgba(0,0,0,0.06);
    position: sticky;
    top: 80px;
}

/* Main image display */
.main-product-img {
    width: 100%;
    max-height: 320px;
    object-fit: contain;
    display: block;
    margin: 0 auto;
    transition: opacity 0.2s;
    border-radius: 10px;
    background: #f8f9fa;
    padding: 12px;
}

/* Thumbnail strip */
.product-thumbs {
    display: flex;
    gap: 10px;
    justify-content: center;
    margin-top: 16px;
    flex-wrap: wrap;
}
.product-thumb {
    width: 72px;
    height: 72px;
    object-fit: contain;
    border-radius: 10px;
    border: 2px solid #e0e0e0;
    background: #f8f9fa;
    padding: 6px;
    cursor: pointer;
    transition: border-color 0.2s, box-shadow 0.2s;
}
.product-thumb:hover,
.product-thumb.active {
    border-color: #00A5C4;
    box-shadow: 0 0 0 3px rgba(0,165,196,0.15);
}

.product-info-card {
    background: #fff;
    border-radius: 16px;
    padding: 28px 32px;
    box-shadow: 0 2px 16px rgba(0,0,0,0.06);
}

/* Description */
.product-description {
    font-family: 'Raleway', sans-serif;
    font-size: 15px;
    line-height: 1.85;
    color: #444;
    background: linear-gradient(135deg, #f0f9ff 0%, #fafafa 100%);
    border-left: 4px solid #00A5C4;
    border-radius: 0 10px 10px 0;
    padding: 16px 20px;
    margin: 16px 0;
    position: relative;
}
.product-description::before {
    content: '\f10d';
    font-family: 'Font Awesome 6 Free';
    font-weight: 900;
    position: absolute;
    top: 10px;
    left: 14px;
    font-size: 18px;
    color: #00A5C4;
    opacity: 0.25;
}

/* Specs table */
.specs-card {
    background: #fff;
    border-radius: 16px;
    padding: 24px 28px;
    box-shadow: 0 2px 16px rgba(0,0,0,0.06);
    margin-top: 20px;
}
.specs-title {
    font-family: 'Rubik', sans-serif;
    font-size: 16px;
    font-weight: 700;
    color: #003859;
    margin-bottom: 16px;
    display: flex;
    align-items: center;
    gap: 8px;
}
.specs-title::after {
    content: '';
    flex: 1;
    height: 2px;
    background: linear-gradient(90deg, #00A5C4, transparent);
    border-radius: 2px;
}
.spec-row {
    display: flex;
    align-items: center;
    padding: 10px 0;
    border-bottom: 1px solid #f0f0f0;
    gap: 12px;
}
.spec-row:last-child { border-bottom: none; }
.spec-icon-wrap {
    width: 34px; height: 34px;
    background: linear-gradient(135deg, #003859, #00A5C4);
    border-radius: 8px;
    display: flex; align-items: center; justify-content: center;
    flex-shrink: 0;
}
.spec-icon-wrap i { color: #fff; font-size: 13px; }
.spec-label {
    font-size: 12px;
    font-weight: 600;
    color: #888;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    width: 90px;
    flex-shrink: 0;
}
.spec-value {
    font-size: 14px;
    font-weight: 600;
    color: #222;
    font-family: 'Rubik', sans-serif;
}

/* Policy badges */
.policy-badge {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 4px;
    padding: 12px 16px;
    background: #f8f9fa;
    border-radius: 10px;
    border: 1px solid #e9ecef;
    min-width: 80px;
    text-align: center;
}
.policy-badge i { font-size: 20px; color: #00A5C4; }
.policy-badge span { font-size: 11px; color: #666; line-height: 1.3; }
</style>

<section id="product-detail" class="py-4">
    <div class="container">

        <!-- Breadcrumb -->
        <nav aria-label="breadcrumb" class="mb-3">
            <ol class="breadcrumb" style="font-size:13px;">
                <li class="breadcrumb-item"><a href="index.php">Home</a></li>
                <li class="breadcrumb-item"><a href="search.php">Products</a></li>
                <li class="breadcrumb-item"><a href="search.php?category=<?php echo urlencode($item['item_brand']); ?>"><?php echo htmlspecialchars($item['item_brand']); ?></a></li>
                <li class="breadcrumb-item active"><?php echo htmlspecialchars($item['item_name']); ?></li>
            </ol>
        </nav>

        <div class="row g-4">

            <!-- LEFT: Image Gallery -->
            <div class="col-lg-5 col-md-6">
                <div class="product-img-box text-center">
                    <?php if ($discount > 0): ?>
                    <span class="badge bg-danger mb-3 px-3 py-2 d-inline-block" style="font-size:13px;"><?php echo $discount; ?>% OFF</span>
                    <?php endif; ?>

                    <!-- Main image -->
                    <img id="mainProductImg"
                         src="<?php echo htmlspecialchars($item['item_image']); ?>"
                         alt="<?php echo htmlspecialchars($item['item_name']); ?>"
                         class="main-product-img img-fluid">

                    <!-- Thumbnail strip — only show if extra images exist -->
                    <?php
                    $all_images = array_filter([
                        $item['item_image'],
                        $item['item_image2'] ?? '',
                        $item['item_image3'] ?? '',
                    ]);
                    $all_images = array_values($all_images);
                    ?>
                    <?php if (count($all_images) > 1): ?>
                    <div class="product-thumbs">
                        <?php foreach ($all_images as $idx => $img): ?>
                        <img src="<?php echo htmlspecialchars($img); ?>"
                             alt="View <?php echo $idx + 1; ?>"
                             class="product-thumb <?php echo $idx === 0 ? 'active' : ''; ?>"
                             onclick="switchImage(this, '<?php echo htmlspecialchars($img); ?>')">
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- RIGHT: Info -->
            <div class="col-lg-7 col-md-6">
                <div class="product-info-card">

                    <p class="mb-1" style="font-size:12px;text-transform:uppercase;letter-spacing:1.5px;color:#00A5C4;font-weight:700;">
                        <?php echo htmlspecialchars($item['item_brand']); ?>
                    </p>
                    <h1 style="font-family:'Rubik',sans-serif;font-size:22px;font-weight:700;color:#003859;line-height:1.3;">
                        <?php echo htmlspecialchars($item['item_name']); ?>
                    </h1>

                    <!-- Rating (live from reviews table) -->
                    <div class="d-flex align-items-center gap-2 mb-3">
                        <div class="text-warning" style="font-size:13px;">
                            <?php if ($avg_rating > 0):
                                for ($i = 1; $i <= 5; $i++):
                                    if ($i <= $avg_rating) echo '<i class="fas fa-star"></i>';
                                    elseif ($i - 0.5 <= $avg_rating) echo '<i class="fas fa-star-half-alt"></i>';
                                    else echo '<i class="far fa-star"></i>';
                                endfor;
                            else: ?>
                            <i class="far fa-star"></i><i class="far fa-star"></i><i class="far fa-star"></i><i class="far fa-star"></i><i class="far fa-star"></i>
                            <?php endif; ?>
                        </div>
                        <?php if ($review_count > 0): ?>
                        <span style="font-size:12px;font-weight:700;color:#333;"><?php echo $avg_rating; ?></span>
                        <a href="#reviews" style="font-size:12px;color:#888;text-decoration:none;">(<?php echo $review_count; ?> review<?php echo $review_count !== 1 ? 's' : ''; ?>)</a>
                        <?php else: ?>
                        <span class="text-muted" style="font-size:12px;">No reviews yet</span>
                        <?php endif; ?>
                        <?php if ($item['item_onsale']): ?>
                        <span class="badge bg-danger ms-1">ON SALE</span>
                        <?php endif; ?>
                        <?php if ($item['item_featured']): ?>
                        <span class="badge bg-warning text-dark ms-1"><i class="fas fa-star me-1"></i>Featured</span>
                        <?php endif; ?>
                    </div>

                    <hr style="border-color:#f0f0f0;">

                    <!-- Price -->
                    <div class="mb-3 d-flex align-items-baseline gap-3 flex-wrap">
                        <span style="font-size:30px;font-weight:800;color:#dc3545;font-family:'Rubik',sans-serif;">
                            <?php echo formatPrice($item['item_price']); ?>
                        </span>
                        <?php if (!empty($item['item_old_price'])): ?>
                        <span class="text-muted text-decoration-line-through" style="font-size:17px;">
                            <?php echo formatPrice($item['item_old_price']); ?>
                        </span>
                        <?php if ($discount > 0): ?>
                        <span class="badge bg-danger" style="font-size:13px;">Save <?php echo $discount; ?>%</span>
                        <?php endif; ?>
                        <?php endif; ?>
                    </div>

                    <!-- Stock -->
                    <p class="mb-3" style="font-size:13px;">
                        <?php if ($item['item_stock'] > 0): ?>
                        <span class="text-success fw-bold"><i class="fas fa-check-circle me-1"></i>In Stock</span>
                        <span class="text-muted ms-1">(<?php echo $item['item_stock']; ?> units available)</span>
                        <?php else: ?>
                        <span class="text-danger fw-bold"><i class="fas fa-times-circle me-1"></i>Out of Stock</span>
                        <?php endif; ?>
                    </p>

                    <!-- Description -->
                    <?php if (!empty($item['item_description'])): ?>
                    <div class="product-description">
                        <?php echo nl2br(htmlspecialchars($item['item_description'])); ?>
                    </div>
                    <?php endif; ?>

                    <!-- Quick specs chips (if available) -->
                    <?php if (!empty($specs)): ?>
                    <div class="d-flex flex-wrap gap-2 my-3">
                        <?php foreach (array_slice($specs, 0, 4) as $s): ?>
                        <span style="background:#f0f9ff;border:1px solid #bee3f8;border-radius:20px;padding:4px 12px;font-size:12px;font-weight:600;color:#003859;">
                            <i class="<?php echo $s['icon']; ?> me-1" style="color:#00A5C4;"></i><?php echo htmlspecialchars($s['value']); ?>
                        </span>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>

                    <hr style="border-color:#f0f0f0;">

                    <!-- Add to Cart -->
                    <form method="post" class="d-flex gap-3 align-items-center flex-wrap mb-4">
                        <input type="hidden" name="item_id" value="<?php echo $item['item_id']; ?>">
                        <?php if (in_array($item['item_id'], $in_cart)): ?>
                        <button type="button" class="btn btn-success px-4 py-2 fw-bold" disabled>
                            <i class="fas fa-check me-2"></i>Already in Cart
                        </button>
                        <a href="cart.php" class="btn btn-outline-primary px-4 py-2">View Cart</a>
                        <?php else: ?>
                        <button type="submit" name="add_to_cart"
                                class="btn px-4 py-2 fw-bold text-white"
                                style="background:linear-gradient(135deg,#003859,#00A5C4);border:none;border-radius:10px;font-size:15px;">
                            <i class="fas fa-cart-plus me-2"></i>Add to Cart
                        </button>
                        <a href="checkout.php?buy_now=<?php echo $item['item_id']; ?>"
                           class="btn px-4 py-2 fw-bold"
                           style="background:#FFD289;color:#003859;border:none;border-radius:10px;font-size:15px;">
                            <i class="fas fa-bolt me-2"></i>Buy Now
                        </a>
                        <?php endif; ?>
                    </form>

                    <!-- Policy badges -->
                    <div class="d-flex gap-2 flex-wrap">
                        <div class="policy-badge"><i class="fas fa-retweet"></i><span>10 Days<br>Return</span></div>
                        <div class="policy-badge"><i class="fas fa-truck"></i><span>Fast<br>Delivery</span></div>
                        <div class="policy-badge"><i class="fas fa-shield-alt"></i><span>1 Year<br>Warranty</span></div>
                        <div class="policy-badge"><i class="fas fa-headset"></i><span>24/7<br>Support</span></div>
                    </div>
                </div>

                <!-- Full Specs Card -->
                <?php if (!empty($specs)): ?>
                <div class="specs-card">
                    <div class="specs-title">
                        <i class="fas fa-list-ul" style="color:#00A5C4;"></i> Full Specifications
                    </div>
                    <?php foreach ($specs as $s): ?>
                    <div class="spec-row">
                        <div class="spec-icon-wrap"><i class="<?php echo $s['icon']; ?>"></i></div>
                        <div class="spec-label"><?php echo $s['label']; ?></div>
                        <div class="spec-value"><?php echo htmlspecialchars($s['value']); ?></div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>

            </div><!-- end col -->
        </div><!-- end row -->

        <!-- Related Products -->
        <?php if (!empty($related)): ?>
        <div class="mt-5">
            <h4 class="section-heading">Related Products</h4>
            <div class="row row-cols-2 row-cols-sm-3 row-cols-md-4 g-3">
                <?php foreach ($related as $r): ?>
                <div class="col">
                    <div class="product-card">
                        <div class="card-img-wrap">
                            <a href="product.php?item_id=<?php echo $r['item_id']; ?>">
                                <img src="<?php echo htmlspecialchars($r['item_image']); ?>" alt="<?php echo htmlspecialchars($r['item_name']); ?>">
                            </a>
                        </div>
                        <div class="card-body">
                            <div class="card-brand"><?php echo htmlspecialchars($r['item_brand']); ?></div>
                            <div class="card-title"><?php echo htmlspecialchars($r['item_name']); ?></div>
                            <div class="card-price"><?php echo formatPrice($r['item_price']); ?></div>
                            <a href="product.php?item_id=<?php echo $r['item_id']; ?>" class="btn-cart mt-2 d-block text-center text-decoration-none">View Product</a>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- ===== REVIEWS ===== -->
        <div class="mt-5" id="reviews">
            <h4 class="section-heading">
                Customer Reviews
                <?php if ($review_count > 0): ?>
                <span class="text-warning ms-2" style="font-size:16px;">
                    <?php for ($i=1;$i<=5;$i++) echo $i<=$avg_rating ? '<i class="fas fa-star"></i>' : ($i-0.5<=$avg_rating ? '<i class="fas fa-star-half-alt"></i>' : '<i class="far fa-star"></i>'); ?>
                </span>
                <span class="text-muted" style="font-size:14px;font-weight:400;"><?php echo $avg_rating; ?> / 5 &nbsp;·&nbsp; <?php echo $review_count; ?> review<?php echo $review_count!==1?'s':''; ?></span>
                <?php endif; ?>
            </h4>

            <div class="row g-4">
                <!-- Review Form -->
                <div class="col-lg-4">
                    <div class="bg-white rounded-3 shadow-sm p-4">
                        <h6 style="font-family:'Rubik',sans-serif;font-weight:700;color:#003859;margin-bottom:16px;">
                            <i class="fas fa-pen me-2"></i><?php echo $user_reviewed ? 'Update Your Review' : 'Write a Review'; ?>
                        </h6>
                        <?php if ($review_msg): ?>
                        <div class="alert alert-success py-2" style="font-size:13px;border-radius:8px;">
                            <i class="fas fa-check-circle me-1"></i><?php echo $review_msg; ?>
                        </div>
                        <?php endif; ?>
                        <?php if ($review_err): ?>
                        <div class="alert alert-danger py-2" style="font-size:13px;border-radius:8px;">
                            <?php echo htmlspecialchars($review_err); ?>
                        </div>
                        <?php endif; ?>
                        <?php if ($auth->isLoggedIn()): ?>
                        <form method="post">
                            <div class="mb-3">
                                <label style="font-size:13px;font-weight:600;color:#444;">Your Rating</label>
                                <div class="star-rating-input d-flex gap-1 mt-1" id="starInput">
                                    <?php for ($i=1;$i<=5;$i++): ?>
                                    <i class="far fa-star star-btn" data-val="<?php echo $i; ?>"
                                       style="font-size:24px;cursor:pointer;color:#ffc107;transition:transform 0.1s;"
                                       onclick="setRating(<?php echo $i; ?>)"
                                       onmouseover="hoverRating(<?php echo $i; ?>)"
                                       onmouseout="resetHover()"></i>
                                    <?php endfor; ?>
                                    <input type="hidden" name="rating" id="ratingInput" value="5">
                                </div>
                            </div>
                            <div class="mb-3">
                                <label style="font-size:13px;font-weight:600;color:#444;">Your Comment</label>
                                <textarea name="comment" class="form-control mt-1" rows="4"
                                          placeholder="Share your experience with this product..."
                                          style="border:1.5px solid #ddd;border-radius:8px;font-size:13px;"
                                          required><?php
                                    // Pre-fill if user already reviewed
                                    foreach ($reviews as $rv) {
                                        if ($rv['user_id'] == $current_user_id) echo htmlspecialchars($rv['comment']);
                                    }
                                ?></textarea>
                            </div>
                            <button type="submit" name="submit_review"
                                    class="btn w-100 fw-bold text-white"
                                    style="background:linear-gradient(135deg,#003859,#00A5C4);border:none;border-radius:8px;">
                                <i class="fas fa-paper-plane me-2"></i><?php echo $user_reviewed ? 'Update Review' : 'Submit Review'; ?>
                            </button>
                        </form>
                        <?php else: ?>
                        <div class="text-center py-3">
                            <i class="fas fa-lock fa-2x text-muted mb-2 d-block"></i>
                            <p class="text-muted" style="font-size:13px;">Please login to leave a review.</p>
                            <button class="btn btn-primary-custom" data-bs-toggle="modal" data-bs-target="#authModal">Login / Register</button>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Reviews List -->
                <div class="col-lg-8">
                    <?php if (empty($reviews)): ?>
                    <div class="bg-white rounded-3 shadow-sm p-4 text-center text-muted">
                        <i class="fas fa-comment-slash fa-2x mb-2 d-block"></i>
                        No reviews yet. Be the first to review this product!
                    </div>
                    <?php else: ?>
                    <div class="d-flex flex-column gap-3">
                        <?php foreach ($reviews as $rv): ?>
                        <div class="bg-white rounded-3 shadow-sm p-3">
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <div class="d-flex align-items-center gap-2">
                                    <div style="width:36px;height:36px;background:linear-gradient(135deg,#003859,#00A5C4);border-radius:50%;display:flex;align-items:center;justify-content:center;color:#fff;font-weight:700;font-size:14px;">
                                        <?php echo strtoupper(substr($rv['first_name'],0,1)); ?>
                                    </div>
                                    <div>
                                        <div style="font-weight:600;font-size:13px;"><?php echo htmlspecialchars($rv['first_name'] . ' ' . $rv['last_name']); ?></div>
                                        <div style="font-size:11px;color:#888;"><?php echo date('M d, Y', strtotime($rv['created_at'])); ?></div>
                                    </div>
                                </div>
                                <div class="text-warning" style="font-size:13px;">
                                    <?php for ($i=1;$i<=5;$i++) echo $i<=$rv['rating'] ? '<i class="fas fa-star"></i>' : '<i class="far fa-star"></i>'; ?>
                                </div>
                            </div>
                            <p style="font-size:13px;color:#444;margin:0;line-height:1.6;"><?php echo nl2br(htmlspecialchars($rv['comment'])); ?></p>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

    </div>
</section>

<?php include 'footer.php'; ?>

<script>
function switchImage(thumb, src) {
    var main = document.getElementById('mainProductImg');
    main.style.opacity = '0';
    setTimeout(function() {
        main.src = src;
        main.style.opacity = '1';
    }, 150);
    document.querySelectorAll('.product-thumb').forEach(function(t) {
        t.classList.remove('active');
    });
    thumb.classList.add('active');
}

// Star rating input
var currentRating = 5;
function setRating(val) {
    currentRating = val;
    document.getElementById('ratingInput').value = val;
    updateStars(val);
}
function hoverRating(val) { updateStars(val); }
function resetHover() { updateStars(currentRating); }
function updateStars(val) {
    document.querySelectorAll('.star-btn').forEach(function(s, i) {
        s.className = (i < val) ? 'fas fa-star star-btn' : 'far fa-star star-btn';
        s.style.fontSize = '24px';
        s.style.cursor = 'pointer';
        s.style.color = '#ffc107';
        s.style.transition = 'transform 0.1s';
    });
}
// Init stars to 5
document.addEventListener('DOMContentLoaded', function() { updateStars(5); });
</script>
