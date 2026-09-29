<?php
$brand  = array_unique(array_column($product_shuffle, 'item_brand'));
sort($brand);
shuffle($product_shuffle);
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['special_price_submit'])) {
    if ($auth->isLoggedIn()) {
        $Cart->addToCart($current_user_id, (int)$_POST['item_id']);
        header('Location: ' . $_SERVER['PHP_SELF']);
        exit;
    }
}
$in_cart_items    = $auth->isLoggedIn() ? $Cart->getCartByUser($current_user_id) : [];
$in_cart          = $Cart->getCartId($in_cart_items);

// Fill to at least 10 items (2 rows of 5) so the grid has no empty right-side gaps
$special_items = fillToMin($product_shuffle, 10, 20);
?>
<section id="special-price" class="py-5" style="background:#fafaf8;">
    <div class="container">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
            <h4 class="section-heading mb-0">Special Price</h4>
            <a href="search.php" style="font-size:13px;color:var(--secondary);font-weight:600;">
                View All <i class="fas fa-arrow-right ms-1"></i>
            </a>
        </div>
        <div class="row row-cols-2 row-cols-sm-3 row-cols-md-4 row-cols-lg-5 g-3">
            <?php foreach ($special_items as $item): ?>
            <div class="col">
                <div class="product-card">
                    <div class="card-img-wrap">
                        <?php if ($item['item_onsale']): ?><span class="badge-sale">SALE</span><?php endif; ?>
                        <a href="product.php?item_id=<?php echo $item['item_id']; ?>">
                            <img src="<?php echo htmlspecialchars($item['item_image']); ?>" alt="<?php echo htmlspecialchars($item['item_name']); ?>">
                        </a>
                    </div>
                    <div class="card-body">
                        <div class="card-brand"><?php echo htmlspecialchars($item['item_brand']); ?></div>
                        <div class="card-title"><?php echo htmlspecialchars($item['item_name']); ?></div>
                        <div class="card-price-wrap mb-2"><span class="card-price"><?php echo formatPrice($item['item_price']); ?></span></div>
                        <form method="post">
                            <input type="hidden" name="item_id" value="<?php echo $item['item_id']; ?>">
                            <?php if (in_array($item['item_id'], $in_cart)): ?>
                            <button type="button" class="btn-cart in-cart" disabled><i class="fas fa-check me-1"></i>In Cart</button>
                            <?php else: ?>
                            <button type="submit" name="special_price_submit" class="btn-cart"><i class="fas fa-cart-plus me-1"></i>Add to Cart</button>
                            <?php endif; ?>
                        </form>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>
