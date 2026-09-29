<?php
$page_title = 'My Cart';
ob_start();
include 'header.php';

// Handle POST actions — redirect after each action to prevent re-submit
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['delete_cart'])) {
        $Cart->deleteCart((int)$_POST['item_id'], 'cart', $current_user_id);
        header('Location: cart.php');
        exit;
    }
    if (isset($_POST['save_for_later'])) {
        $Cart->saveForLater((int)$_POST['item_id'], $current_user_id);
        header('Location: cart.php');
        exit;
    }
    if (isset($_POST['move_to_cart'])) {
        $Cart->moveToCart((int)$_POST['item_id'], $current_user_id);
        header('Location: cart.php');
        exit;
    }
    if (isset($_POST['delete_wishlist'])) {
        $Cart->deleteCart((int)$_POST['item_id'], 'wishlist', $current_user_id);
        header('Location: cart.php');
        exit;
    }
}

// Fetch cart & wishlist
$cart_items     = $auth->isLoggedIn() ? $Cart->getCartByUser($current_user_id)     : [];
$wishlist_items = $auth->isLoggedIn() ? $Cart->getWishlistByUser($current_user_id) : [];

$subtotal = 0;
foreach ($cart_items as $item) {
    $subtotal += $item['item_price'] * $item['qty'];
}

$rate   = (float)(getSetting('usd_to_birr') ?: 57);
$is_etb = ($_SESSION['currency'] ?? 'USD') === 'ETB';

function cartFmt($usd, $is_etb, $rate) {
    if ($is_etb) return number_format($usd * $rate, 2) . ' Birr';
    return '$' . number_format($usd, 2);
}
?>

<section class="py-4" style="background:#f8f9fa;min-height:80vh;">
<div class="container">

    <!-- Heading + currency switcher -->
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <h4 class="section-heading mb-0">
            <i class="fas fa-shopping-cart me-2"></i>Shopping Cart
            <?php if (!empty($cart_items)): ?>
            <span class="badge bg-secondary ms-2" style="font-size:14px;" id="cart-count-badge">
                <?php echo array_sum(array_column($cart_items,'qty')); ?> items
            </span>
            <?php endif; ?>
        </h4>
        <div class="d-flex align-items-center gap-2">
            <span style="font-size:13px;color:#666;">Currency:</span>
            <a href="?set_currency=USD" class="btn btn-sm <?php echo !$is_etb ? 'btn-primary' : 'btn-outline-secondary'; ?>">USD</a>
            <a href="?set_currency=ETB" class="btn btn-sm <?php echo $is_etb  ? 'btn-warning text-dark' : 'btn-outline-secondary'; ?>">ETB Birr</a>
        </div>
    </div>

    <?php if (!$auth->isLoggedIn()): ?>
    <!-- Not logged in -->
    <div class="text-center py-5 bg-white rounded-3 shadow-sm">
        <i class="fas fa-lock fa-3x text-muted mb-3"></i>
        <h5>Please login to view your cart</h5>
        <button class="btn btn-primary-custom mt-3 open-auth-modal">Login / Register</button>
    </div>

    <?php elseif (empty($cart_items)): ?>
    <!-- Empty cart -->
    <div class="text-center py-5 bg-white rounded-3 shadow-sm">
        <img src="assets/blog/empty_cart.png" alt="Empty Cart" style="max-width:160px;" class="mb-3">
        <h5 class="text-muted">Your cart is empty</h5>
        <a href="search.php" class="btn btn-primary-custom mt-2 me-2">Shop Phones</a>
        <a href="accessories.php" class="btn btn-outline-primary mt-2">Shop Accessories</a>
    </div>

    <?php else: ?>
    <div class="row g-4">

        <!-- Cart Items -->
        <div class="col-lg-8">
            <div class="bg-white rounded-3 shadow-sm p-3" id="cart-items-container">
                <?php foreach ($cart_items as $item):
                    $line_usd = $item['item_price'] * $item['qty'];
                ?>
                <div class="cart-item d-flex gap-3 align-items-start" id="cart-row-<?php echo $item['item_id']; ?>">
                    <a href="product.php?item_id=<?php echo $item['item_id']; ?>" style="flex-shrink:0;">
                        <img src="<?php echo htmlspecialchars($item['item_image']); ?>"
                             alt="<?php echo htmlspecialchars($item['item_name']); ?>">
                    </a>
                    <div class="flex-grow-1 min-width-0">
                        <a href="product.php?item_id=<?php echo $item['item_id']; ?>" class="text-dark text-decoration-none">
                            <h6 style="font-family:'Rubik',sans-serif;font-weight:600;margin-bottom:2px;font-size:14px;">
                                <?php echo htmlspecialchars($item['item_name']); ?>
                            </h6>
                        </a>
                        <small class="text-muted">by <?php echo htmlspecialchars($item['item_brand']); ?></small>
                        <div class="text-muted mt-1" style="font-size:12px;">
                            Unit: <strong><?php echo cartFmt($item['item_price'], $is_etb, $rate); ?></strong>
                        </div>

                        <!-- Qty Controls -->
                        <div class="d-flex align-items-center gap-2 mt-2 flex-wrap">
                            <div class="d-flex align-items-center border rounded" style="overflow:hidden;">
                                <button type="button" class="btn btn-sm btn-light border-0 px-2 cart-qty-btn"
                                        data-action="decrease"
                                        data-id="<?php echo $item['item_id']; ?>"
                                        data-price="<?php echo $item['item_price']; ?>"
                                        data-rate="<?php echo $rate; ?>"
                                        data-etb="<?php echo $is_etb ? '1' : '0'; ?>">
                                    <i class="fas fa-minus" style="font-size:10px;"></i>
                                </button>
                                <input type="number" class="cart-qty-input text-center border-0 bg-white"
                                       id="qty-<?php echo $item['item_id']; ?>"
                                       data-id="<?php echo $item['item_id']; ?>"
                                       data-price="<?php echo $item['item_price']; ?>"
                                       data-rate="<?php echo $rate; ?>"
                                       data-etb="<?php echo $is_etb ? '1' : '0'; ?>"
                                       value="<?php echo $item['qty']; ?>"
                                       min="1" max="20"
                                       style="width:46px;font-size:13px;font-weight:600;">
                                <button type="button" class="btn btn-sm btn-light border-0 px-2 cart-qty-btn"
                                        data-action="increase"
                                        data-id="<?php echo $item['item_id']; ?>"
                                        data-price="<?php echo $item['item_price']; ?>"
                                        data-rate="<?php echo $rate; ?>"
                                        data-etb="<?php echo $is_etb ? '1' : '0'; ?>">
                                    <i class="fas fa-plus" style="font-size:10px;"></i>
                                </button>
                            </div>

                            <form method="post" class="d-inline">
                                <input type="hidden" name="item_id" value="<?php echo $item['item_id']; ?>">
                                <button type="submit" name="delete_cart" class="btn btn-sm btn-outline-danger">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </form>

                            <form method="post" class="d-inline">
                                <input type="hidden" name="item_id" value="<?php echo $item['item_id']; ?>">
                                <button type="submit" name="save_for_later" class="btn btn-sm btn-outline-secondary">
                                    <i class="fas fa-heart"></i>
                                </button>
                            </form>
                        </div>
                    </div>

                    <!-- Line Price -->
                    <div class="text-end" style="min-width:80px;flex-shrink:0;">
                        <span class="text-danger fw-bold line-price"
                              id="line-price-<?php echo $item['item_id']; ?>"
                              data-usd="<?php echo $line_usd; ?>"
                              style="font-size:15px;">
                            <?php echo cartFmt($line_usd, $is_etb, $rate); ?>
                        </span>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Order Summary -->
        <div class="col-lg-4">
            <div class="cart-subtotal-box" id="order-summary">
                <h6 style="font-family:'Rubik',sans-serif;font-weight:700;color:#003859;">Order Summary</h6>
                <hr>
                <div class="d-flex justify-content-between mb-2" style="font-size:14px;">
                    <span>Items (<span id="summary-count"><?php echo array_sum(array_column($cart_items,'qty')); ?></span>)</span>
                    <span class="text-danger fw-bold" id="summary-subtotal">
                        <?php echo cartFmt($subtotal, $is_etb, $rate); ?>
                    </span>
                </div>
                <div class="d-flex justify-content-between mb-2" style="font-size:14px;">
                    <span>Delivery</span>
                    <span class="text-success fw-bold">FREE</span>
                </div>
                <hr>
                <div class="d-flex justify-content-between mb-3" style="font-size:18px;font-weight:700;">
                    <span>Total</span>
                    <span class="text-danger" id="summary-total">
                        <?php echo cartFmt($subtotal, $is_etb, $rate); ?>
                    </span>
                </div>
                <p class="text-success" style="font-size:12px;">
                    <i class="fas fa-check-circle me-1"></i>FREE delivery on all orders
                </p>
                <p style="font-size:12px;color:#555;margin-bottom:10px;">
                    <i class="fas fa-tag me-1 text-warning"></i>Got a promo code? Apply it at checkout to save.
                </p>
                <a href="checkout.php" class="btn btn-secondary-custom w-100 py-2">
                    <i class="fas fa-lock me-2"></i>Proceed to Checkout
                </a>
                <a href="search.php" class="btn btn-outline-secondary w-100 mt-2" style="font-size:13px;">
                    Continue Shopping
                </a>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Wishlist / Saved for Later — always visible -->
    <div class="mt-5" id="wishlist">
        <h5 class="section-heading">
            <i class="fas fa-heart me-2 text-danger"></i>Wishlist / Saved for Later
            <span class="badge bg-danger ms-2"><?php echo count($wishlist_items); ?></span>
        </h5>
        <?php if (!$auth->isLoggedIn()): ?>
        <div class="text-center py-4 bg-white rounded-3 shadow-sm">
            <i class="fas fa-lock fa-2x text-muted mb-2"></i>
            <p class="text-muted mb-2">Please login to view your wishlist</p>
            <button class="btn btn-primary-custom open-auth-modal">Login / Register</button>
        </div>
        <?php elseif (empty($wishlist_items)): ?>
        <div class="text-center py-4 bg-white rounded-3 shadow-sm">
            <i class="fas fa-heart fa-2x text-muted mb-2"></i>
            <p class="text-muted mb-2">Your wishlist is empty</p>
            <a href="search.php" class="btn btn-primary-custom">Browse Products</a>
        </div>
        <?php else: ?>
        <div class="row row-cols-2 row-cols-sm-3 row-cols-md-4 row-cols-lg-5 g-3">
            <?php foreach ($wishlist_items as $w): ?>
            <div class="col">
                <div class="product-card">
                    <div class="card-img-wrap">
                        <img src="<?php echo htmlspecialchars($w['item_image']); ?>"
                             alt="<?php echo htmlspecialchars($w['item_name']); ?>">
                    </div>
                    <div class="card-body">
                        <div class="card-brand"><?php echo htmlspecialchars($w['item_brand']); ?></div>
                        <div class="card-title"><?php echo htmlspecialchars($w['item_name']); ?></div>
                        <div class="card-price mb-2"><?php echo cartFmt($w['item_price'], $is_etb, $rate); ?></div>
                        <form method="post" class="mb-1">
                            <input type="hidden" name="item_id" value="<?php echo $w['item_id']; ?>">
                            <button type="submit" name="move_to_cart" class="btn-cart">
                                <i class="fas fa-cart-plus me-1"></i>Move to Cart
                            </button>
                        </form>
                        <form method="post">
                            <input type="hidden" name="item_id" value="<?php echo $w['item_id']; ?>">
                            <button type="submit" name="delete_wishlist" class="btn btn-sm btn-outline-danger w-100 mt-1">
                                <i class="fas fa-trash me-1"></i>Remove
                            </button>
                        </form>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>

</div>
</section>

<?php include 'footer.php'; ?>

<script>
$(document).ready(function(){
    var CART_AJAX = window.location.pathname.replace(/\/[^\/]*$/, '/') + 'cart_ajax.php';

    // +/- buttons
    $(document).on('click', '.cart-qty-btn', function(e){
        e.preventDefault();
        var action = $(this).data('action');
        var id     = $(this).data('id');
        var price  = parseFloat($(this).data('price'));
        var rate   = parseFloat($(this).data('rate'));
        var isEtb  = $(this).data('etb') == '1';
        var $input = $('#qty-' + id);
        var qty    = parseInt($input.val()) || 1;

        if (action === 'increase' && qty < 20) qty++;
        else if (action === 'decrease' && qty > 1) qty--;
        else return;

        $input.val(qty);
        updateQty(id, qty, price, rate, isEtb);
    });

    // Manual input change
    $(document).on('change', '.cart-qty-input', function(){
        var id    = $(this).data('id');
        var price = parseFloat($(this).data('price'));
        var rate  = parseFloat($(this).data('rate'));
        var isEtb = $(this).data('etb') == '1';
        var qty   = Math.min(20, Math.max(1, parseInt($(this).val()) || 1));
        $(this).val(qty);
        updateQty(id, qty, price, rate, isEtb);
    });

    function fmt(usd, isEtb, rate) {
        if (isEtb) return (usd * rate).toFixed(2) + ' Birr';
        return '$' + usd.toFixed(2);
    }

    function updateQty(id, qty, price, rate, isEtb) {
        var lineUSD = price * qty;

        // Update line price display
        $('#line-price-' + id).text(fmt(lineUSD, isEtb, rate)).data('usd', lineUSD);

        // Recalculate totals from all line prices
        var totalUSD = 0, totalQty = 0;
        $('.line-price').each(function(){
            totalUSD += parseFloat($(this).data('usd')) || 0;
        });
        $('.cart-qty-input').each(function(){
            totalQty += parseInt($(this).val()) || 0;
        });

        // Update summary
        $('#summary-subtotal').text(fmt(totalUSD, isEtb, rate));
        $('#summary-total').text(fmt(totalUSD, isEtb, rate));
        $('#summary-count').text(totalQty);
        $('#cart-count-badge').text(totalQty + ' items');

        // Update nav badge
        if ($('.nav-cart-badge').length) {
            $('.nav-cart-badge').text(totalQty);
        } else {
            $('.nav-cart-btn').append('<span class="nav-cart-badge">' + totalQty + '</span>');
        }

        // Save to server
        $.post(CART_AJAX, { action: 'update_qty', item_id: id, qty: qty });
    }
});
</script>
