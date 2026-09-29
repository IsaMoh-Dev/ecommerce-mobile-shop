<?php
$page_title = 'Checkout';
ob_start();
include 'header.php';

// Must be logged in
if (!$auth->isLoggedIn()) {
    header('Location: cart.php');
    exit;
}

// Handle Buy Now — add item to cart then proceed
if (isset($_GET['buy_now'])) {
    $buy_item_id = (int)$_GET['buy_now'];
    if ($buy_item_id > 0) {
        $Cart->addToCart($current_user_id, $buy_item_id);
    }
    header('Location: checkout.php');
    exit;
}

$cart_items = $Cart->getCartByUser($current_user_id);

// Empty cart — nothing to checkout
if (empty($cart_items)) {
    header('Location: cart.php');
    exit;
}

$rate   = (float)(getSetting('usd_to_birr') ?: 57);
$is_etb = ($_SESSION['currency'] ?? 'USD') === 'ETB';

$subtotal = 0;
foreach ($cart_items as $item) {
    $subtotal += $item['item_price'] * $item['qty'];
}

function chkFmt($usd, $is_etb, $rate) {
    if ($is_etb) return number_format($usd * $rate, 2) . ' Birr';
    return '$' . number_format($usd, 2);
}

// Pre-fill from user profile
$stmt = $db->con->prepare("SELECT first_name, last_name, email, phone, default_address, default_city FROM user WHERE user_id=? LIMIT 1");
$stmt->bind_param("i", $current_user_id);
$stmt->execute();
$user_row = $stmt->get_result()->fetch_assoc();
$stmt->close();

// Delivery fee from settings
$delivery_fee_usd    = (float)(getSetting('delivery_fee') ?: 0);
$free_above_usd      = (float)(getSetting('free_delivery_above') ?: 0);
$delivery_note       = getSetting('delivery_note') ?: 'Same-day delivery within Addis Ababa for orders before 2 PM.';
$min_order_usd       = (float)(getSetting('min_order_amount') ?: 0);

// Apply free delivery threshold
if ($free_above_usd > 0 && $subtotal >= $free_above_usd) {
    $delivery_fee_usd = 0;
}
$grand_total = $subtotal + $delivery_fee_usd;

$errors       = [];
$order_placed = false;
$order_id     = null;

// Coupon handling
$coupon_discount = 0;
$coupon_code     = '';
$coupon_id       = 0;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['place_order'])) {
    $full_name      = trim($_POST['full_name']      ?? '');
    $email          = trim($_POST['email']          ?? '');
    $phone          = trim($_POST['phone']          ?? '');
    $address        = trim($_POST['address']        ?? '');
    $city           = trim($_POST['city']           ?? '');
    $payment_method = trim($_POST['payment_method'] ?? 'cod');
    $coupon_code    = strtoupper(trim($_POST['coupon_code'] ?? ''));
    $coupon_id      = (int)($_POST['coupon_id'] ?? 0);
    $coupon_discount = (float)($_POST['coupon_discount'] ?? 0);

    // Re-validate coupon server-side
    if ($coupon_code && $coupon_id > 0) {
        $cid = $coupon_id;
        $cres = $db->con->query("SELECT * FROM coupons WHERE coupon_id={$cid} AND is_active=1 LIMIT 1");
        if ($cres && $cres->num_rows > 0) {
            $cpn = $cres->fetch_assoc();
            $expired = !empty($cpn['expires_at']) && strtotime($cpn['expires_at']) < time();
            $maxed   = $cpn['used_count'] >= $cpn['max_uses'];
            if (!$expired && !$maxed && $grand_total >= $cpn['min_order']) {
                $coupon_discount = $cpn['type'] === 'percent'
                    ? round($grand_total * ($cpn['value'] / 100), 2)
                    : min($cpn['value'], $grand_total);
            } else {
                $coupon_discount = 0;
                $coupon_id = 0;
            }
        } else {
            $coupon_discount = 0;
            $coupon_id = 0;
        }
    }

    $final_total = max(0, $grand_total - $coupon_discount);

    if (!$full_name)   $errors[] = 'Full name is required.';
    if (!$email || !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Valid email is required.';
    if (!$phone)       $errors[] = 'Phone number is required.';
    if (!$address)     $errors[] = 'Delivery address is required.';
    if (!$city)        $errors[] = 'City is required.';

    if (empty($errors)) {
        $result = $Order->placeOrder($current_user_id, [
            'full_name'      => $full_name,
            'email'          => $email,
            'phone'          => $phone,
            'address'        => $address,
            'city'           => $city,
            'total'          => $final_total,
            'payment_method' => $payment_method,
        ], $cart_items);

        if ($result['success']) {
            // Increment coupon usage
            if ($coupon_id > 0) {
                $db->con->query("UPDATE coupons SET used_count = used_count + 1 WHERE coupon_id = {$coupon_id}");
            }
            $order_placed = true;
            $order_id     = $result['order_id'];
        } else {
            $errors[] = $result['message'];
        }
    }
}
?>

<section class="checkout-section">
<div class="container">

<?php if ($order_placed): ?>
<!-- ===== ORDER SUCCESS ===== -->
<div class="row justify-content-center">
    <div class="col-lg-6 text-center py-5">
        <div style="width:80px;height:80px;background:linear-gradient(135deg,#003859,#00A5C4);border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto 20px;">
            <i class="fas fa-check" style="font-size:36px;color:#fff;"></i>
        </div>
        <h3 style="font-family:'Rubik',sans-serif;font-weight:700;color:#003859;">Order Placed!</h3>
        <p class="text-muted mb-1">Thank you for your order. We'll contact you shortly.</p>
        <p style="font-size:14px;color:#555;">Order ID: <strong>#<?php echo $order_id; ?></strong></p>
        <div class="d-flex gap-3 justify-content-center mt-4 flex-wrap">
            <a href="orders.php" class="btn btn-primary-custom px-4">
                <i class="fas fa-box me-2"></i>View My Orders
            </a>
            <a href="index.php" class="btn btn-outline-secondary px-4">
                <i class="fas fa-home me-2"></i>Back to Home
            </a>
        </div>
    </div>
</div>

<?php else: ?>
<!-- ===== CHECKOUT FORM ===== -->

<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <h4 class="section-heading mb-0"><i class="fas fa-lock me-2"></i>Checkout</h4>
    <a href="cart.php" class="btn btn-outline-secondary btn-sm">
        <i class="fas fa-arrow-left me-1"></i>Back to Cart
    </a>
</div>

<?php if (!empty($errors)): ?>
<div class="alert alert-danger mb-4" style="border-radius:10px;">
    <i class="fas fa-exclamation-circle me-2"></i>
    <?php echo implode('<br>', array_map('htmlspecialchars', $errors)); ?>
</div>
<?php endif; ?>

<div class="row g-4">

    <!-- LEFT: Delivery + Payment -->
    <div class="col-lg-7">

        <!-- Delivery Info -->
        <div class="checkout-card mb-4">
            <h5><i class="fas fa-map-marker-alt me-2 text-danger"></i>Delivery Information</h5>
            <form method="post" id="checkoutForm">

                <div class="row g-3">
                    <div class="col-sm-6">
                        <label class="form-label fw-semibold" style="font-size:13px;">Full Name *</label>
                        <input type="text" name="full_name" class="form-control"
                               value="<?php echo htmlspecialchars(($_POST['full_name'] ?? '') ?: ($user_row['first_name'] . ' ' . $user_row['last_name'])); ?>"
                               placeholder="Your full name" required>
                    </div>
                    <div class="col-sm-6">
                        <label class="form-label fw-semibold" style="font-size:13px;">Email *</label>
                        <input type="email" name="email" class="form-control"
                               value="<?php echo htmlspecialchars(($_POST['email'] ?? '') ?: $user_row['email']); ?>"
                               placeholder="your@email.com" required>
                    </div>
                    <div class="col-sm-6">
                        <label class="form-label fw-semibold" style="font-size:13px;">Phone *</label>
                        <input type="tel" name="phone" class="form-control"
                               value="<?php echo htmlspecialchars(($_POST['phone'] ?? '') ?: ($user_row['phone'] ?? '')); ?>"
                               placeholder="+251 9XX XXX XXX" required>
                    </div>
                    <div class="col-sm-6">
                        <label class="form-label fw-semibold" style="font-size:13px;">City *</label>
                        <input type="text" name="city" class="form-control"
                               value="<?php echo htmlspecialchars($_POST['city'] ?? $user_row['default_city'] ?? 'Addis Ababa'); ?>"
                               placeholder="City" required>
                    </div>
                    <div class="col-12">
                        <label class="form-label fw-semibold" style="font-size:13px;">Delivery Address *</label>
                        <textarea name="address" class="form-control" rows="2"
                                  placeholder="Street, building, area..." required><?php echo htmlspecialchars($_POST['address'] ?? $user_row['default_address'] ?? ''); ?></textarea>
                        <?php if ($delivery_note): ?>
                        <small class="text-muted" style="font-size:11px;">
                            <i class="fas fa-truck me-1"></i><?php echo htmlspecialchars($delivery_note); ?>
                        </small>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Payment Method -->
                <h5 class="mt-4"><i class="fas fa-credit-card me-2 text-primary"></i>Payment Method</h5>
                <div class="row g-3">
                    <?php
                    $payments = [
                        'cod'      => ['icon'=>'fas fa-money-bill-wave', 'color'=>'#28a745', 'label'=>'Cash on Delivery'],
                        'bank'     => ['icon'=>'fas fa-university',      'color'=>'#003859', 'label'=>'Bank Transfer'],
                        'telebirr' => ['icon'=>'fas fa-mobile-alt',      'color'=>'#00A5C4', 'label'=>'Telebirr'],
                        'cbe'      => ['icon'=>'fas fa-credit-card',     'color'=>'#e63946', 'label'=>'CBE Birr'],
                    ];
                    $selected_pay = $_POST['payment_method'] ?? 'cod';
                    foreach ($payments as $val => $p):
                    ?>
                    <div class="col-6 col-sm-3">
                        <label class="payment-option <?php echo $selected_pay === $val ? 'selected' : ''; ?>"
                               style="cursor:pointer;display:block;border:2px solid <?php echo $selected_pay === $val ? '#00A5C4' : '#dee2e6'; ?>;border-radius:10px;padding:14px 10px;text-align:center;transition:all 0.2s;">
                            <input type="radio" name="payment_method" value="<?php echo $val; ?>"
                                   <?php echo $selected_pay === $val ? 'checked' : ''; ?>
                                   style="display:none;" class="pay-radio">
                            <i class="<?php echo $p['icon']; ?>" style="font-size:22px;color:<?php echo $p['color']; ?>;display:block;margin-bottom:6px;"></i>
                            <span style="font-size:11px;font-weight:600;color:#333;"><?php echo $p['label']; ?></span>
                        </label>
                    </div>
                    <?php endforeach; ?>
                </div>

                <!-- Submit -->
                <button type="submit" name="place_order" class="btn btn-secondary-custom w-100 py-3 mt-4" style="font-size:16px;">
                    <i class="fas fa-check-circle me-2"></i>Place Order
                </button>

                <!-- Hidden coupon fields (populated by JS) -->
                <input type="hidden" name="coupon_code"     id="hiddenCouponCode"     value="">
                <input type="hidden" name="coupon_id"       id="hiddenCouponId"       value="0">
                <input type="hidden" name="coupon_discount" id="hiddenCouponDiscount" value="0">

            </form>
        </div>
    </div>

    <!-- RIGHT: Order Summary -->
    <div class="col-lg-5">
        <div class="checkout-card">
            <h5><i class="fas fa-receipt me-2 text-success"></i>Order Summary</h5>
            <div style="max-height:320px;overflow-y:auto;">
                <?php foreach ($cart_items as $item): ?>
                <div class="d-flex align-items-center gap-3 py-2 border-bottom">
                    <img src="<?php echo htmlspecialchars($item['item_image']); ?>"
                         alt="<?php echo htmlspecialchars($item['item_name']); ?>"
                         style="width:52px;height:52px;object-fit:contain;background:#f8f9fa;border-radius:8px;padding:4px;flex-shrink:0;">
                    <div class="flex-grow-1">
                        <div style="font-size:13px;font-weight:600;"><?php echo htmlspecialchars($item['item_name']); ?></div>
                        <div style="font-size:12px;color:#888;">Qty: <?php echo $item['qty']; ?> × <?php echo chkFmt($item['item_price'], $is_etb, $rate); ?></div>
                    </div>
                    <div class="text-danger fw-bold" style="font-size:13px;white-space:nowrap;">
                        <?php echo chkFmt($item['item_price'] * $item['qty'], $is_etb, $rate); ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <hr>
            <div class="d-flex justify-content-between mb-2" style="font-size:14px;">
                <span>Subtotal</span>
                <span class="fw-bold"><?php echo chkFmt($subtotal, $is_etb, $rate); ?></span>
            </div>
            <div class="d-flex justify-content-between mb-2" style="font-size:14px;">
                <span>Delivery</span>
                <?php if ($delivery_fee_usd > 0): ?>
                <span class="fw-bold text-danger"><?php echo chkFmt($delivery_fee_usd, $is_etb, $rate); ?></span>
                <?php else: ?>
                <span class="text-success fw-bold">FREE</span>
                <?php endif; ?>
            </div>
            <?php if ($free_above_usd > 0 && $delivery_fee_usd > 0): ?>
            <div style="font-size:11px;color:#888;margin-bottom:8px;">
                <i class="fas fa-info-circle me-1"></i>Free delivery on orders above <?php echo chkFmt($free_above_usd, $is_etb, $rate); ?>
            </div>
            <?php endif; ?>

            <!-- Coupon Code -->
            <div class="mt-3 mb-2">
                <p style="font-size:12px;color:#555;margin-bottom:6px;">
                    <i class="fas fa-tag me-1 text-success"></i>
                    Have a promo code? Enter it below to get a discount.
                </p>
                <div class="input-group" style="font-size:13px;">
                    <input type="text" id="couponInput" class="form-control" placeholder="Promo / Coupon code"
                           style="border-radius:8px 0 0 8px;font-size:13px;text-transform:uppercase;">
                    <button type="button" class="btn btn-outline-success" id="applyCouponBtn"
                            onclick="applyCoupon()" style="border-radius:0 8px 8px 0;font-size:13px;">
                        Apply
                    </button>
                </div>
                <div id="couponMsg" style="font-size:12px;margin-top:5px;display:none;"></div>
            </div>

            <!-- Discount row (hidden until coupon applied) -->
            <div id="discountRow" class="d-flex justify-content-between mb-2" style="font-size:14px;display:none!important;">
                <span class="text-success"><i class="fas fa-tag me-1"></i>Discount (<span id="couponLabel"></span>)</span>
                <span class="text-success fw-bold" id="discountAmt"></span>
            </div>

            <hr>
            <div class="d-flex justify-content-between" style="font-size:18px;font-weight:700;">
                <span>Total</span>
                <span class="text-danger" id="finalTotalDisplay"><?php echo chkFmt($grand_total, $is_etb, $rate); ?></span>
            </div>
            <div class="mt-3 p-3 rounded-3" style="background:#f0f9ff;border:1px solid #bee3f8;">
                <p class="mb-1" style="font-size:12px;color:#003859;font-weight:600;">
                    <i class="fas fa-shield-alt me-1"></i>Secure Checkout
                </p>
                <p class="mb-0" style="font-size:11px;color:#555;">
                    Your order is protected. We'll confirm via phone/Telegram after placing.
                </p>
            </div>
        </div>
    </div>

</div>
<?php endif; ?>

</div>
</section>

<?php include 'footer.php'; ?>

<script>
// Payment option highlight
$(document).ready(function(){
    $('.pay-radio').on('change', function(){
        $('.payment-option').css({'border-color':'#dee2e6'});
        $(this).closest('.payment-option').css({'border-color':'#00A5C4'});
    });
    $('label.payment-option').on('click', function(){
        $('.payment-option').css({'border-color':'#dee2e6'});
        $(this).css({'border-color':'#00A5C4'});
        $(this).find('.pay-radio').prop('checked', true);
    });
});

// Coupon logic
var grandTotal = <?php echo $grand_total; ?>;
var isEtb      = <?php echo $is_etb ? 'true' : 'false'; ?>;
var rate       = <?php echo $rate; ?>;

function fmtPrice(usd) {
    if (isEtb) return (usd * rate).toFixed(2) + ' Birr';
    return '$' + usd.toFixed(2);
}

function applyCoupon() {
    var code = document.getElementById('couponInput').value.trim().toUpperCase();
    var msg  = document.getElementById('couponMsg');
    if (!code) { showCouponMsg('Please enter a coupon code.', false); return; }

    document.getElementById('applyCouponBtn').textContent = '...';

    fetch('coupon_check.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: 'code=' + encodeURIComponent(code) + '&total=' + grandTotal
    })
    .then(r => r.json())
    .then(data => {
        document.getElementById('applyCouponBtn').textContent = 'Apply';
        if (data.success) {
            showCouponMsg(data.message, true);
            document.getElementById('hiddenCouponCode').value     = data.code;
            document.getElementById('hiddenCouponId').value       = data.coupon_id;
            document.getElementById('hiddenCouponDiscount').value = data.discount;
            document.getElementById('couponLabel').textContent    = data.code;
            document.getElementById('discountAmt').textContent    = '-' + fmtPrice(data.discount);
            document.getElementById('discountRow').style.display  = 'flex';
            var newTotal = Math.max(0, grandTotal - data.discount);
            document.getElementById('finalTotalDisplay').textContent = fmtPrice(newTotal);
            document.getElementById('applyCouponBtn').textContent = '✓ Applied';
            document.getElementById('applyCouponBtn').disabled    = true;
        } else {
            showCouponMsg(data.message, false);
            clearCoupon();
        }
    })
    .catch(() => {
        document.getElementById('applyCouponBtn').textContent = 'Apply';
        showCouponMsg('Something went wrong. Please try again.', false);
    });
}

function showCouponMsg(text, success) {
    var el = document.getElementById('couponMsg');
    el.style.display = 'block';
    el.style.color   = success ? '#28a745' : '#dc3545';
    el.textContent   = text;
}

function clearCoupon() {
    document.getElementById('hiddenCouponCode').value     = '';
    document.getElementById('hiddenCouponId').value       = '0';
    document.getElementById('hiddenCouponDiscount').value = '0';
    document.getElementById('discountRow').style.display  = 'none';
    document.getElementById('finalTotalDisplay').textContent = fmtPrice(grandTotal);
}

// Allow pressing Enter in coupon field
document.addEventListener('DOMContentLoaded', function() {
    var inp = document.getElementById('couponInput');
    if (inp) inp.addEventListener('keydown', function(e) {
        if (e.key === 'Enter') { e.preventDefault(); applyCoupon(); }
    });
});
</script>
