<?php
shuffle($product_shuffle);
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['top_sale_submit'])) {
    if ($auth->isLoggedIn()) {
        $Cart->addToCart($current_user_id, (int)$_POST['item_id']);
        header('Location: ' . $_SERVER['PHP_SELF']);
        exit;
    } else {
        header('Location: ' . $_SERVER['PHP_SELF'] . '?need_login=1');
        exit;
    }
}
$in_cart_items = $auth->isLoggedIn() ? $Cart->getCartByUser($current_user_id) : [];
$in_cart       = $Cart->getCartId($in_cart_items);

// Fill to at least 8 items so the carousel always looks full
$top_sale_items = fillToMin($product_shuffle, 8, 16);
?>
<section id="top-sale" class="py-5" style="background:#f0f7fa;">
    <div class="container">
        <h4 class="section-heading">Top Sale</h4>
        <div class="owl-carousel owl-theme">
            <?php foreach ($top_sale_items as $item): ?>
            <div class="item px-2">
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
                        <div class="card-rating"><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="far fa-star"></i></div>                        <div class="card-price-wrap mb-2">
                            <span class="card-price"><?php echo formatPrice($item['item_price']); ?></span>
                            <?php if (!empty($item['item_old_price'])): ?>
                            <span class="card-old-price"><?php echo formatPrice($item['item_old_price']); ?></span>
                            <?php endif; ?>
                        </div>
                        <form method="post">
                            <input type="hidden" name="item_id" value="<?php echo $item['item_id']; ?>">
                            <?php if (in_array($item['item_id'], $in_cart)): ?>
                            <button type="button" class="btn-cart in-cart" disabled><i class="fas fa-check me-1"></i>In Cart</button>
                            <?php else: ?>
                            <button type="submit" name="top_sale_submit" class="btn-cart"><i class="fas fa-cart-plus me-1"></i>Add to Cart</button>
                            <?php endif; ?>
                        </form>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>
